<?php

namespace UnifiedDocs\Ajax;

use UnifiedDocs\Core\Cache;

/**
 * AI-Powered Search Handler
 *
 * Provides LLM-powered search and AI-generated answers
 */
class AISearch {

    /**
     * Perform AI-powered search
     */
    public static function search() {
        check_ajax_referer('unified_docs_nonce', 'nonce');

        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';

        if (empty($query)) {
            wp_send_json_error(['message' => 'No search query provided']);
        }

        // Check if AI Services is available
        if (!function_exists('ai_services') || !ai_services()->has_available_services()) {
            wp_send_json_error([
                'message' => 'AI Services not available',
                'fallback' => true
            ]);
        }

        $cache = new Cache();
        $docs = $cache->get_organized_docs();

        // Flatten documents for searching
        $all_docs = self::flatten_documents($docs);

        if (empty($all_docs)) {
            wp_send_json_success([
                'answer' => '',
                'sources' => [],
                'documents' => [],
                'no_results' => true
            ]);
        }

        // Use keyword search to narrow down to top 20 docs
        $relevant_docs = self::keyword_filter_documents($all_docs, $query, 20);

        // If keyword search found nothing, pass first 20 docs to LLM anyway
        // This ensures the LLM always gets a chance to answer
        if (empty($relevant_docs)) {
            $relevant_docs = array_slice($all_docs, 0, 20);
        }

        // Generate AI answer using LLM
        $ai_answer = self::generate_ai_answer($query, $relevant_docs);

        // Format document results
        $formatted_docs = array_map(function($doc) use ($query) {
            return [
                'title' => $doc['parsed']['title'],
                'path' => $doc['path'],
                'source' => $doc['source_name'],
                'snippet' => self::get_snippet($doc['parsed']['raw'], 150, $query)
            ];
        }, array_slice($relevant_docs, 0, 10));

        wp_send_json_success([
            'answer' => $ai_answer['answer'],
            'sources' => $ai_answer['sources'],
            'documents' => $formatted_docs,
            'related' => $ai_answer['related']
        ]);
    }

    /**
     * Filter documents using keyword matching
     *
     * @param array $docs All documents
     * @param string $query Search query
     * @param int $limit Max results to return
     * @return array Filtered and scored documents
     */
    private static function keyword_filter_documents($docs, $query, $limit = 20) {
        $query_lower = strtolower($query);

        // Split query into words (remove common words)
        $stop_words = ['how', 'do', 'i', 'the', 'a', 'an', 'in', 'to', 'for', 'of', 'and', 'or', 'is', 'are'];
        $words = preg_split('/\s+/', $query_lower);
        $search_words = array_filter($words, function($word) use ($stop_words) {
            return strlen($word) > 2 && !in_array($word, $stop_words);
        });

        $scored = [];

        foreach ($docs as $doc) {
            $score = 0;
            $title = strtolower($doc['parsed']['title']);
            $content = strtolower($doc['parsed']['raw']);
            $filename = strtolower($doc['filename']);

            // Check for exact phrase match (highest weight)
            if (stripos($title, $query_lower) !== false) {
                $score += 20;
            }
            if (stripos($content, $query_lower) !== false) {
                $score += 10;
            }

            // Check for individual word matches
            foreach ($search_words as $word) {
                if (stripos($title, $word) !== false) {
                    $score += 5;
                }
                if (stripos($content, $word) !== false) {
                    $score += 2;
                }
                if (stripos($filename, $word) !== false) {
                    $score += 3;
                }
            }

            if ($score > 0) {
                $scored[] = [
                    'doc' => $doc,
                    'score' => $score
                ];
            }
        }

        // Sort by score
        usort($scored, function($a, $b) {
            return $b['score'] - $a['score'];
        });

        // Return just the docs (not the scores)
        return array_map(function($item) {
            return $item['doc'];
        }, array_slice($scored, 0, $limit));
    }

    /**
     * Generate AI answer using LLM
     *
     * @param string $query User's query
     * @param array $relevant_docs Top relevant documents
     * @return array Answer data with sources and related topics
     */
    private static function generate_ai_answer($query, $relevant_docs) {
        try {
            // Get AI service
            $service = ai_services()->get_available_service();

            // Build context from documents
            $context = self::build_context_from_docs($relevant_docs);

            // Create prompt for LLM
            $prompt = self::build_prompt($query, $context);

            // Get model with text generation capability
            $model = $service->get_model([
                'feature' => 'unified_docs_search',
                'capabilities' => ['text_generation']
            ]);

            // Generate text
            $candidates = $model->generate_text($prompt, [
                'max_output_tokens' => 500
            ]);

            // Extract text from response
            $response = self::extract_text_from_candidates($candidates);

            // Parse response to extract answer, sources, and related topics
            $parsed = self::parse_ai_response($response, $relevant_docs);

            return $parsed;
        } catch (\Exception $e) {
            error_log('Unified Docs AI Search: ' . $e->getMessage());
            return [
                'answer' => '',
                'sources' => [],
                'related' => []
            ];
        }
    }

    /**
     * Extract text from AI candidates response
     *
     * @param mixed $candidates Candidates from AI response
     * @return string Extracted text
     */
    private static function extract_text_from_candidates($candidates) {
        // Use AI Services helper to extract text
        if (class_exists('Felix_Arntz\AI_Services\Services\API\Helpers')) {
            $helpers = new \Felix_Arntz\AI_Services\Services\API\Helpers();
            $contents = $helpers::get_candidate_contents($candidates);
            return $helpers::get_text_from_contents($contents);
        }

        // Fallback: try to extract manually
        if (is_array($candidates) && isset($candidates[0])) {
            $candidate = $candidates[0];
            if (isset($candidate['content']) && is_array($candidate['content'])) {
                foreach ($candidate['content'] as $part) {
                    if (isset($part['text'])) {
                        return $part['text'];
                    }
                }
            }
        }

        return '';
    }

    /**
     * Build context string from documents
     *
     * @param array $docs Relevant documents
     * @return array Context entries with titles and content
     */
    private static function build_context_from_docs($docs) {
        $context = [];

        foreach ($docs as $doc) {
            $context[] = [
                'title' => $doc['parsed']['title'],
                'content' => substr($doc['parsed']['raw'], 0, 800)
            ];
        }

        return $context;
    }

    /**
     * Build prompt for LLM
     *
     * @param string $query User's query
     * @param array $context Context entries
     * @return string Formatted prompt
     */
    private static function build_prompt($query, $context) {
        $context_text = '';

        foreach ($context as $idx => $entry) {
            $num = $idx + 1;
            $context_text .= "Document {$num}: {$entry['title']}\n";
            $context_text .= "{$entry['content']}\n\n";
        }

        $prompt = <<<PROMPT
You are a helpful documentation assistant for a WordPress website. A user is searching the documentation and asked:

"{$query}"

Here are the most relevant documentation sections:

{$context_text}

Provide a clear, concise answer that:
1. Directly answers their question using the documentation provided
2. Cites which documentation sections you're referencing by number (e.g., [1], [2])
3. Includes step-by-step instructions if applicable
4. Keep your answer under 200 words and friendly in tone

After your answer, on a new line starting with "RELATED:", suggest 2-3 related topics they might want to explore (just the topic names, comma-separated).

Format your response exactly like this:
[Your helpful answer here with citation numbers like [1] or [2]]

RELATED: topic1, topic2, topic3
PROMPT;

        return $prompt;
    }

    /**
     * Parse AI response
     *
     * @param string $response AI response text
     * @param array $docs Documents for source mapping
     * @return array Parsed response with answer, sources, and related topics
     */
    private static function parse_ai_response($response, $docs) {
        // Split answer and related topics
        $parts = explode('RELATED:', $response);
        $answer = trim($parts[0]);
        $related_text = isset($parts[1]) ? trim($parts[1]) : '';

        // Extract cited document numbers from answer
        preg_match_all('/\[(\d+)\]/', $answer, $matches);
        $cited_numbers = array_unique($matches[1]);

        // Build sources array
        $sources = [];
        foreach ($cited_numbers as $num) {
            $idx = intval($num) - 1;
            if (isset($docs[$idx])) {
                $doc = $docs[$idx];
                $sources[] = [
                    'title' => $doc['parsed']['title'],
                    'path' => $doc['path']
                ];
            }
        }

        // Parse related topics
        $related = [];
        if (!empty($related_text)) {
            $topics = explode(',', $related_text);
            $related = array_map('trim', array_slice($topics, 0, 3));
        }

        return [
            'answer' => $answer,
            'sources' => $sources,
            'related' => $related
        ];
    }

    /**
     * Flatten organized documents into a single array
     *
     * @param array $organized Organized documentation
     * @return array Flat array of all documents
     */
    private static function flatten_documents($organized) {
        $all_docs = [];

        foreach ($organized['categories'] as $category) {
            $all_docs = array_merge($all_docs, $category['docs']);

            foreach ($category['subcategories'] as $subcat) {
                $all_docs = array_merge($all_docs, $subcat['docs']);
            }
        }

        $all_docs = array_merge($all_docs, $organized['uncategorized']);

        return $all_docs;
    }

    /**
     * Get a snippet of text around the query
     *
     * @param string $content Full content
     * @param int $length Max length
     * @param string $query Optional query to center snippet around
     * @return string Snippet
     */
    private static function get_snippet($content, $length = 150, $query = '') {
        if (strlen($content) <= $length) {
            return $content;
        }

        // If query provided, try to center snippet around it
        if (!empty($query)) {
            $pos = stripos($content, $query);
            if ($pos !== false) {
                $start = max(0, $pos - 75);
                $snippet = substr($content, $start, $length);

                if ($start > 0) {
                    $snippet = '...' . $snippet;
                }

                if (strlen($content) > $start + $length) {
                    $snippet .= '...';
                }

                return $snippet;
            }
        }

        return substr($content, 0, $length) . '...';
    }
}
