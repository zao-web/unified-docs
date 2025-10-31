<?php

namespace UnifiedDocs\Core;

/**
 * Cache Manager
 *
 * Manages caching for documentation with automatic invalidation
 */
class Cache {

    /**
     * Get organized documentation with caching
     *
     * @return array Organized documentation
     */
    public function get_organized_docs() {
        $scanner = new Scanner();
        $current_hash = $scanner->get_files_hash();

        // Check if files have changed
        $stored_hash = get_option('unified_docs_files_hash', '');

        if ($stored_hash !== $current_hash) {
            // Files have changed, invalidate cache
            $this->invalidate_all();
            update_option('unified_docs_files_hash', $current_hash);
        }

        // Try to get from cache
        $cache_key = 'unified_docs_organized_' . $current_hash;
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Generate fresh documentation
        return $this->generate_and_cache($cache_key);
    }

    /**
     * Generate and cache organized documentation
     *
     * @param string $cache_key Cache key to use
     * @return array Organized documentation
     */
    private function generate_and_cache($cache_key) {
        $scanner = new Scanner();
        $parser = new Parser();
        $organizer = new Organizer();

        // Scan all documentation files
        $files = $scanner->scan_all();

        // Parse markdown files
        $parsed_docs = $parser->parse_multiple($files);

        // Organize with AI
        $organized = $organizer->organize($parsed_docs);

        // Cache for 1 week
        set_transient($cache_key, $organized, WEEK_IN_SECONDS);

        return $organized;
    }

    /**
     * Invalidate all cached documentation
     */
    public function invalidate_all() {
        global $wpdb;

        // Delete all unified docs transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_unified_docs_%'
            OR option_name LIKE '_transient_timeout_unified_docs_%'"
        );

        delete_option('unified_docs_files_hash');
    }

    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    public function get_stats() {
        $scanner = new Scanner();
        $current_hash = $scanner->get_files_hash();
        $stored_hash = get_option('unified_docs_files_hash', '');

        $cache_key = 'unified_docs_organized_' . $current_hash;
        $cached = get_transient($cache_key);

        return [
            'is_cached' => ($cached !== false),
            'files_hash' => $current_hash,
            'hash_matches' => ($stored_hash === $current_hash),
            'cache_key' => $cache_key,
        ];
    }
}
