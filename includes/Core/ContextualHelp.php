<?php

namespace UnifiedDocs\Core;

/**
 * Contextual Help Integration
 *
 * Adds documentation to WordPress admin screen help panels
 */
class ContextualHelp {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Cache of screen-to-docs mapping
     */
    private $screen_map = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('current_screen', [$this, 'add_help_tabs']);
    }

    /**
     * Add help tabs to current screen
     *
     * @param \WP_Screen $screen Current screen object
     */
    public function add_help_tabs($screen) {
        if (!$screen) {
            return;
        }

        // Get documentation for this screen
        $docs = $this->get_docs_for_screen($screen->id);

        if (empty($docs)) {
            return;
        }

        // Add help tabs for each relevant document
        foreach ($docs as $index => $doc) {
            $screen->add_help_tab([
                'id' => 'unified-docs-' . sanitize_key($doc['filename']) . '-' . $index,
                'title' => $doc['parsed']['title'] ?: $doc['filename'],
                'content' => $this->format_help_content($doc),
            ]);
        }

        // Add sidebar with link to full docs
        $screen->set_help_sidebar(
            '<p><strong>' . __('More Documentation', 'unified-docs') . '</strong></p>' .
            '<p><a href="' . admin_url('admin.php?page=unified-docs') . '">' .
            __('View all documentation', 'unified-docs') .
            '</a></p>'
        );
    }

    /**
     * Get documentation for a specific screen
     *
     * @param string $screen_id WordPress screen ID
     * @return array Array of relevant docs
     */
    private function get_docs_for_screen($screen_id) {
        // Build screen map if not already built
        if (null === $this->screen_map) {
            $this->build_screen_map();
        }

        // Normalize screen ID
        $screen_id = $this->normalize_screen_id($screen_id);

        // Return docs for this screen
        return $this->screen_map[$screen_id] ?? [];
    }

    /**
     * Build mapping of screens to docs
     */
    private function build_screen_map() {
        $this->screen_map = [];

        return;
        // Get cached docs
        $cache = new Cache();
        $organized = $cache->get_organized_docs();

        // Flatten all docs
        $all_docs = $this->flatten_docs($organized);

        // Build screen mapping
        foreach ($all_docs as $doc) {
            if (empty($doc['screens'])) {
                continue;
            }

            foreach ($doc['screens'] as $screen) {
                $screen = $this->normalize_screen_id($screen);

                if (!isset($this->screen_map[$screen])) {
                    $this->screen_map[$screen] = [];
                }

                $this->screen_map[$screen][] = $doc;
            }
        }

        // Sort docs by order within each screen
        foreach ($this->screen_map as $screen_id => &$docs) {
            usort($docs, function($a, $b) {
                return $a['order'] - $b['order'];
            });
        }
    }

    /**
     * Flatten organized docs into single array
     *
     * @param array $organized Organized documentation structure
     * @return array Flat array of docs
     */
    private function flatten_docs($organized) {
        $all_docs = [];

        if (isset($organized['categories'])) {
            foreach ($organized['categories'] as $category) {
                if (!empty($category['docs'])) {
                    $all_docs = array_merge($all_docs, $category['docs']);
                }

                if (!empty($category['subcategories'])) {
                    foreach ($category['subcategories'] as $subcat) {
                        if (!empty($subcat['docs'])) {
                            $all_docs = array_merge($all_docs, $subcat['docs']);
                        }
                    }
                }
            }
        }

        if (!empty($organized['uncategorized'])) {
            $all_docs = array_merge($all_docs, $organized['uncategorized']);
        }

        return $all_docs;
    }

    /**
     * Normalize screen ID for matching
     *
     * @param string $screen_id Screen ID
     * @return string Normalized screen ID
     */
    private function normalize_screen_id($screen_id) {
        // Remove common prefixes
        $screen_id = str_replace(['edit-', 'toplevel_page_'], '', $screen_id);

        return strtolower(trim($screen_id));
    }

    /**
     * Format documentation content for help panel
     *
     * @param array $doc Document data
     * @return string Formatted HTML content
     */
    private function format_help_content($doc) {
        $html = '';

        // Add video if present
        if (!empty($doc['video_url'])) {
            $html .= '<div style="margin-bottom: 20px;">';
            $html .= '<a href="' . esc_url($doc['video_url']) . '" target="_blank" class="button">';
            $html .= __('Watch Video Tutorial', 'unified-docs');
            $html .= '</a>';
            $html .= '</div>';
        }

        // Add documentation content
        $html .= '<div class="unified-docs-help-content">';
        $html .= wp_kses_post($doc['parsed']['html']);
        $html .= '</div>';

        // Add link to full doc
        $html .= '<p style="margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd;">';
        $html .= '<a href="' . admin_url('admin.php?page=unified-docs') . '">';
        $html .= __('View full documentation', 'unified-docs');
        $html .= '</a> | ';
        $html .= '<em>' . sprintf(__('From: %s', 'unified-docs'), esc_html($doc['source_name'])) . '</em>';
        $html .= '</p>';

        return $html;
    }

    /**
     * Invalidate screen map cache
     */
    public function invalidate_cache() {
        $this->screen_map = null;
    }
}
