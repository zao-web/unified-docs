<?php

namespace UnifiedDocs\Ajax;

use UnifiedDocs\Core\Cache;

/**
 * AJAX Request Handler
 */
class Handler {

    /**
     * Get documentation content
     */
    public static function get_content() {
        check_ajax_referer('unified_docs_nonce', 'nonce');

        $doc_path = isset($_POST['doc_path']) ? sanitize_text_field($_POST['doc_path']) : '';

        if (empty($doc_path) || !file_exists($doc_path)) {
            wp_send_json_error(['message' => 'Document not found']);
        }

        $cache = new Cache();
        $docs = $cache->get_organized_docs();

        // Find the requested document
        $doc = self::find_document($docs, $doc_path);

        if (!$doc) {
            wp_send_json_error(['message' => 'Document not found in cache']);
        }

        wp_send_json_success([
            'html' => $doc['parsed']['html'],
            'title' => $doc['parsed']['title'],
            'video_url' => $doc['video_url'],
            'source' => $doc['source_name'],
        ]);
    }

    /**
     * Search documentation
     */
    public static function search() {
        check_ajax_referer('unified_docs_nonce', 'nonce');

        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';

        if (empty($query)) {
            wp_send_json_error(['message' => 'No search query provided']);
        }

        $cache = new Cache();
        $docs = $cache->get_organized_docs();

        $results = self::search_documents($docs, $query);

        wp_send_json_success(['results' => $results]);
    }

    /**
     * Find a document by path in the organized structure
     *
     * @param array $organized Organized documentation
     * @param string $path Document path
     * @return array|null Document data or null
     */
    private static function find_document($organized, $path) {
        // Search in categories
        foreach ($organized['categories'] as $category) {
            foreach ($category['docs'] as $doc) {
                if ($doc['path'] === $path) {
                    return $doc;
                }
            }

            // Search in subcategories
            foreach ($category['subcategories'] as $subcat) {
                foreach ($subcat['docs'] as $doc) {
                    if ($doc['path'] === $path) {
                        return $doc;
                    }
                }
            }
        }

        // Search in uncategorized
        foreach ($organized['uncategorized'] as $doc) {
            if ($doc['path'] === $path) {
                return $doc;
            }
        }

        return null;
    }

    /**
     * Search through all documents
     *
     * @param array $organized Organized documentation
     * @param string $query Search query
     * @return array Search results
     */
    private static function search_documents($organized, $query) {
        $results = [];
        $query_lower = strtolower($query);

        $all_docs = self::flatten_documents($organized);

        foreach ($all_docs as $doc) {
            $score = 0;
            $title = $doc['parsed']['title'];
            $content = $doc['parsed']['raw'];

            // Check title match (higher weight)
            if (stripos($title, $query) !== false) {
                $score += 10;
            }

            // Check content match
            if (stripos($content, $query) !== false) {
                $score += 5;
            }

            // Check filename match
            if (stripos($doc['filename'], $query) !== false) {
                $score += 3;
            }

            if ($score > 0) {
                // Get context snippet
                $snippet = self::get_search_snippet($content, $query);

                $results[] = [
                    'title' => $title,
                    'snippet' => $snippet,
                    'path' => $doc['path'],
                    'source' => $doc['source_name'],
                    'score' => $score,
                ];
            }
        }

        // Sort by score
        usort($results, function($a, $b) {
            return $b['score'] - $a['score'];
        });

        return array_slice($results, 0, 20); // Return top 20 results
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
     * Get a snippet of text around the search query
     *
     * @param string $content Full content
     * @param string $query Search query
     * @return string Snippet with context
     */
    private static function get_search_snippet($content, $query) {
        $pos = stripos($content, $query);

        if ($pos === false) {
            return substr($content, 0, 150) . '...';
        }

        $start = max(0, $pos - 75);
        $snippet = substr($content, $start, 200);

        if ($start > 0) {
            $snippet = '...' . $snippet;
        }

        if (strlen($content) > $start + 200) {
            $snippet .= '...';
        }

        // Highlight the search term
        $snippet = preg_replace(
            '/(' . preg_quote($query, '/') . ')/i',
            '<mark>$1</mark>',
            $snippet
        );

        return $snippet;
    }
}
