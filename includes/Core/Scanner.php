<?php

namespace UnifiedDocs\Core;

/**
 * Documentation Scanner
 *
 * Scans active themes and plugins for markdown documentation files
 */
class Scanner {

    /**
     * Directories to scan for documentation
     */
    private $doc_directories = ['docs', 'documentation'];

    /**
     * Get all documentation files from active themes and plugins
     *
     * @return array Array of documentation files with metadata
     */
    public function scan_all() {
        $docs = [];

        // Scan active theme
        $docs = array_merge($docs, $this->scan_theme());

        // Scan parent theme if child theme is active
        if (is_child_theme()) {
            $docs = array_merge($docs, $this->scan_theme(true));
        }

        // Scan active plugins
        $docs = array_merge($docs, $this->scan_plugins());

        return $docs;
    }

    /**
     * Scan theme for documentation
     *
     * @param bool $parent_theme Whether to scan parent theme
     * @return array Array of documentation files
     */
    private function scan_theme($parent_theme = false) {
        $theme_root = $parent_theme ? get_template_directory() : get_stylesheet_directory();
        $theme_name = $parent_theme ? get_template() : get_stylesheet();

        return $this->scan_directory($theme_root, $theme_name, 'theme');
    }

    /**
     * Scan active plugins for documentation
     *
     * @return array Array of documentation files
     */
    private function scan_plugins() {
        $docs = [];
        $active_plugins = get_option('active_plugins', []);

        foreach ($active_plugins as $plugin_file) {
            $plugin_dir = WP_PLUGIN_DIR . '/' . dirname($plugin_file);
            $plugin_slug = dirname($plugin_file);

            // Skip if no directory (single-file plugin)
            if ($plugin_slug === '.') {
                continue;
            }

            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file);
            $plugin_name = !empty($plugin_data['Name']) ? $plugin_data['Name'] : $plugin_slug;

            $docs = array_merge($docs, $this->scan_directory($plugin_dir, $plugin_name, 'plugin'));
        }

        return $docs;
    }

    /**
     * Scan a directory for markdown files
     *
     * @param string $base_path Base path to scan
     * @param string $source_name Name of the source (theme/plugin name)
     * @param string $source_type Type of source (theme/plugin)
     * @return array Array of documentation files
     */
    private function scan_directory($base_path, $source_name, $source_type) {
        $docs = [];

        foreach ($this->doc_directories as $doc_dir) {
            $full_path = $base_path . '/' . $doc_dir;

            if (!is_dir($full_path)) {
                continue;
            }

            $files = $this->get_markdown_files($full_path);

            foreach ($files as $file) {
                $relative_path = str_replace($full_path . '/', '', $file);

                $docs[] = [
                    'path' => $file,
                    'relative_path' => $relative_path,
                    'source_name' => $source_name,
                    'source_type' => $source_type,
                    'filename' => basename($file),
                    'modified' => filemtime($file),
                ];
            }
        }

        return $docs;
    }

    /**
     * Recursively get all markdown files in a directory
     *
     * @param string $dir Directory to scan
     * @return array Array of file paths
     */
    private function get_markdown_files($dir) {
        $files = [];

        if (!is_readable($dir)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'md') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Get hash of all documentation files for cache invalidation
     *
     * @return string Hash of all file modification times
     */
    public function get_files_hash() {
        $docs = $this->scan_all();
        $hash_data = [];

        foreach ($docs as $doc) {
            $hash_data[] = $doc['path'] . ':' . $doc['modified'];
        }

        return md5(implode('|', $hash_data));
    }
}
