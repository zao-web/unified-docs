<?php

namespace UnifiedDocs\Core;

/**
 * AI Documentation Organizer
 *
 * Uses AI Services plugin to organize documentation into logical groups
 */
class Organizer {

    /**
     * Organize documentation using AI
     *
     * @param array $docs Parsed documentation array
     * @return array Organized documentation structure
     */
    public function organize($docs) {
        // Check if AI Services plugin is available
        if (!function_exists('ai_services_get_services_instance')) {
            return $this->fallback_organization($docs);
        }

        try {
            // Get cached organization if available
            $cache_key = $this->get_cache_key($docs);
            $cached = get_transient($cache_key);

            if ($cached !== false) {
                return $cached;
            }

            // Prepare documentation summary for AI
            $doc_summary = $this->prepare_doc_summary($docs);

            // Get AI Services instance
            $ai_services = ai_services_get_services_instance();

            // Get available services
            $available_services = $ai_services->get_available_services();

            if (empty($available_services)) {
                return $this->fallback_organization($docs);
            }

            // Use the first available service
            $service_slug = array_key_first($available_services);
            $service = $ai_services->get_available_service($service_slug);

            if (!$service) {
                return $this->fallback_organization($docs);
            }

            // Create the prompt for AI
            $prompt = $this->create_organization_prompt($doc_summary);

            // Generate content using AI
            $candidates = $service->generate_text($prompt, [
                'feature' => 'unified-docs-organizer',
                'temperature' => 0.3,
            ]);

            if (empty($candidates) || !isset($candidates[0]['content'])) {
                return $this->fallback_organization($docs);
            }

            // Parse AI response
            $organized = $this->parse_ai_response($candidates[0]['content'], $docs);

            // Cache for 1 week (7 days)
            set_transient($cache_key, $organized, WEEK_IN_SECONDS);

            return $organized;

        } catch (\Exception $e) {
            error_log('Unified Docs AI Organization Error: ' . $e->getMessage());
            return $this->fallback_organization($docs);
        }
    }

    /**
     * Prepare documentation summary for AI prompt
     *
     * @param array $docs Documentation array
     * @return string Summary of all documents
     */
    private function prepare_doc_summary($docs) {
        $summary = [];

        foreach ($docs as $index => $doc) {
            $title = $doc['parsed']['title'] ?: $doc['filename'];
            $source = $doc['source_name'];
            $type = $doc['source_type'];

            // Get first few lines of content for context
            $content_preview = substr($doc['parsed']['raw'], 0, 200);

            $summary[] = sprintf(
                "[%d] %s (from %s %s)\nPreview: %s...",
                $index,
                $title,
                $type,
                $source,
                $content_preview
            );
        }

        return implode("\n\n", $summary);
    }

    /**
     * Create AI prompt for documentation organization
     *
     * @param string $doc_summary Summary of all documents
     * @return string AI prompt
     */
    private function create_organization_prompt($doc_summary) {
        return <<<PROMPT
You are organizing user documentation for a WordPress website. Below is a list of all documentation files found across themes and plugins.

Your task is to:
1. Create logical categories that group related documentation
2. Assign each document to the most appropriate category
3. Create a hierarchical structure if subcategories make sense
4. Provide a brief description for each category
5. Order documents within categories in a logical learning sequence

Documentation Files:
{$doc_summary}

Please respond with ONLY valid JSON in this exact format:
{
  "categories": [
    {
      "name": "Category Name",
      "description": "Brief description of what this category covers",
      "slug": "category-slug",
      "docs": [0, 2, 5],
      "subcategories": [
        {
          "name": "Subcategory Name",
          "description": "Brief description",
          "slug": "subcategory-slug",
          "docs": [1, 3]
        }
      ]
    }
  ],
  "uncategorized": [4]
}

Use the document numbers [0], [1], etc. from the list above.
PROMPT;
    }

    /**
     * Parse AI response and map to actual documents
     *
     * @param string $ai_response AI response JSON
     * @param array $docs Original documentation array
     * @return array Organized documentation structure
     */
    private function parse_ai_response($ai_response, $docs) {
        // Try to extract JSON from the response
        if (preg_match('/\{.*\}/s', $ai_response, $matches)) {
            $json = json_decode($matches[0], true);
        } else {
            $json = json_decode($ai_response, true);
        }

        if (!$json || !isset($json['categories'])) {
            return $this->fallback_organization($docs);
        }

        // Map document indices to actual documents
        $organized = [
            'categories' => [],
            'uncategorized' => [],
        ];

        foreach ($json['categories'] as $category) {
            $cat_data = [
                'name' => $category['name'],
                'description' => $category['description'] ?? '',
                'slug' => $category['slug'],
                'docs' => [],
                'subcategories' => [],
            ];

            // Add docs to category
            if (!empty($category['docs'])) {
                foreach ($category['docs'] as $doc_index) {
                    if (isset($docs[$doc_index])) {
                        $cat_data['docs'][] = $docs[$doc_index];
                    }
                }
            }

            // Process subcategories
            if (!empty($category['subcategories'])) {
                foreach ($category['subcategories'] as $subcat) {
                    $subcat_data = [
                        'name' => $subcat['name'],
                        'description' => $subcat['description'] ?? '',
                        'slug' => $subcat['slug'],
                        'docs' => [],
                    ];

                    if (!empty($subcat['docs'])) {
                        foreach ($subcat['docs'] as $doc_index) {
                            if (isset($docs[$doc_index])) {
                                $subcat_data['docs'][] = $docs[$doc_index];
                            }
                        }
                    }

                    $cat_data['subcategories'][] = $subcat_data;
                }
            }

            $organized['categories'][] = $cat_data;
        }

        // Add uncategorized docs
        if (!empty($json['uncategorized'])) {
            foreach ($json['uncategorized'] as $doc_index) {
                if (isset($docs[$doc_index])) {
                    $organized['uncategorized'][] = $docs[$doc_index];
                }
            }
        }

        return $organized;
    }

    /**
     * Fallback organization when AI is not available
     *
     * @param array $docs Documentation array
     * @return array Basic organization by source
     */
    private function fallback_organization($docs) {
        $organized = [
            'categories' => [],
            'uncategorized' => [],
        ];

        $by_source = [];

        foreach ($docs as $doc) {
            $source_key = $doc['source_type'] . '_' . sanitize_title($doc['source_name']);

            if (!isset($by_source[$source_key])) {
                $by_source[$source_key] = [
                    'name' => $doc['source_name'],
                    'description' => 'Documentation from ' . $doc['source_name'],
                    'slug' => $source_key,
                    'docs' => [],
                    'subcategories' => [],
                ];
            }

            $by_source[$source_key]['docs'][] = $doc;
        }

        $organized['categories'] = array_values($by_source);

        return $organized;
    }

    /**
     * Get cache key for organized documentation
     *
     * @param array $docs Documentation array
     * @return string Cache key
     */
    private function get_cache_key($docs) {
        $scanner = new Scanner();
        $files_hash = $scanner->get_files_hash();
        return 'unified_docs_organized_' . $files_hash;
    }

    /**
     * Clear organization cache
     */
    public function clear_cache() {
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_unified_docs_organized_%'"
        );
    }
}
