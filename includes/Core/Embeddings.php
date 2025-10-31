<?php

namespace UnifiedDocs\Core;

/**
 * Embeddings Helper
 *
 * Generates and manages document embeddings for semantic search
 */
class Embeddings {

    /**
     * Generate embeddings for documents using AI Services
     *
     * @param array $docs Array of parsed documents
     * @return array Documents with embeddings added
     */
    public static function generate_embeddings($docs) {
        // Check if AI Services is available
        if (!function_exists('ai_services_get_services_instance')) {
            return $docs;
        }

        $services = ai_services_get_services_instance();

        // Check if we have an embedding-capable service
        if (!$services || !method_exists($services, 'get_available_services')) {
            return $docs;
        }

        // Get first available service
        $available_services = $services->get_available_services();
        if (empty($available_services)) {
            return $docs;
        }

        $service_slug = array_key_first($available_services);

        try {
            foreach ($docs as &$doc) {
                // Create text to embed: title + content excerpt
                $text_to_embed = $doc['parsed']['title'] . "\n\n" .
                                substr($doc['parsed']['raw'], 0, 2000);

                // Generate embedding
                $embedding = $services->get_text_embedding(
                    $text_to_embed,
                    [
                        'service' => $service_slug,
                        'feature' => 'unified_docs_search'
                    ]
                );

                // Store embedding with document
                if ($embedding && is_array($embedding)) {
                    $doc['embedding'] = $embedding;
                } else {
                    $doc['embedding'] = null;
                }
            }
        } catch (\Exception $e) {
            // Log error but don't fail - search will fall back
            error_log('Unified Docs: Failed to generate embeddings - ' . $e->getMessage());
        }

        return $docs;
    }

    /**
     * Generate embedding for search query
     *
     * @param string $query Search query
     * @return array|null Embedding vector or null on failure
     */
    public static function generate_query_embedding($query) {
        // Check if AI Services is available
        if (!function_exists('ai_services_get_services_instance')) {
            return null;
        }

        $services = ai_services_get_services_instance();

        if (!$services || !method_exists($services, 'get_available_services')) {
            return null;
        }

        $available_services = $services->get_available_services();
        if (empty($available_services)) {
            return null;
        }

        $service_slug = array_key_first($available_services);

        try {
            $embedding = $services->get_text_embedding(
                $query,
                [
                    'service' => $service_slug,
                    'feature' => 'unified_docs_search'
                ]
            );

            return $embedding && is_array($embedding) ? $embedding : null;
        } catch (\Exception $e) {
            error_log('Unified Docs: Failed to generate query embedding - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculate cosine similarity between two vectors
     *
     * @param array $vec1 First vector
     * @param array $vec2 Second vector
     * @return float Similarity score (0-1, higher is more similar)
     */
    public static function cosine_similarity($vec1, $vec2) {
        if (empty($vec1) || empty($vec2) || count($vec1) !== count($vec2)) {
            return 0.0;
        }

        $dot_product = 0.0;
        $magnitude1 = 0.0;
        $magnitude2 = 0.0;

        for ($i = 0; $i < count($vec1); $i++) {
            $dot_product += $vec1[$i] * $vec2[$i];
            $magnitude1 += $vec1[$i] * $vec1[$i];
            $magnitude2 += $vec2[$i] * $vec2[$i];
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0.0;
        }

        return $dot_product / ($magnitude1 * $magnitude2);
    }

    /**
     * Find most similar documents to a query embedding
     *
     * @param array $query_embedding Query embedding vector
     * @param array $docs Documents with embeddings
     * @param int $limit Number of results to return
     * @return array Top matching documents with similarity scores
     */
    public static function find_similar_documents($query_embedding, $docs, $limit = 10) {
        if (empty($query_embedding)) {
            return [];
        }

        $scored_docs = [];

        foreach ($docs as $doc) {
            // Skip docs without embeddings
            if (empty($doc['embedding'])) {
                continue;
            }

            $similarity = self::cosine_similarity($query_embedding, $doc['embedding']);

            if ($similarity > 0) {
                $scored_docs[] = [
                    'doc' => $doc,
                    'similarity' => $similarity,
                ];
            }
        }

        // Sort by similarity (highest first)
        usort($scored_docs, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        // Return top N results
        return array_slice($scored_docs, 0, $limit);
    }

    /**
     * Check if AI Services supports embeddings
     *
     * @return bool True if embeddings are available
     */
    public static function is_available() {
        if (!function_exists('ai_services_get_services_instance')) {
            return false;
        }

        $services = ai_services_get_services_instance();

        if (!$services || !method_exists($services, 'get_available_services')) {
            return false;
        }

        $available_services = $services->get_available_services();
        return !empty($available_services);
    }
}
