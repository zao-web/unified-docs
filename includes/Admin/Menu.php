<?php

namespace UnifiedDocs\Admin;

use UnifiedDocs\Core\Cache;

/**
 * Admin Menu Handler
 *
 * Creates and manages the Docs admin menu
 */
class Menu {

    /**
     * Single instance
     */
    private static $instance = null;

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
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_filter('admin_body_class', [$this, 'add_body_classes']);
    }

    /**
     * Add body classes for fullscreen mode
     *
     * @param string $classes Existing body classes
     * @return string Modified body classes
     */
    public function add_body_classes($classes) {
        $screen = get_current_screen();

        // Add fullscreen mode to main docs page (not settings)
        if ($screen && $screen->id === 'toplevel_page_unified-docs') {
            $classes .= ' unified-docs-fullscreen';
        }

        return $classes;
    }

    /**
     * Add admin menu
     */
    public function add_menu() {
        add_menu_page(
            __('Documentation', 'unified-docs'),
            __('Docs', 'unified-docs'),
            'read',
            'unified-docs',
            [$this, 'render_page'],
            'dashicons-book-alt',
            30
        );

        // Add submenu for cache clearing
        add_submenu_page(
            'unified-docs',
            __('Settings', 'unified-docs'),
            __('Settings', 'unified-docs'),
            'manage_options',
            'unified-docs-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'unified-docs') === false) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'unified-docs-admin',
            UNIFIED_DOCS_URL . 'assets/css/admin.css',
            [],
            UNIFIED_DOCS_VERSION
        );

        // Enqueue JS
        wp_enqueue_script(
            'unified-docs-admin',
            UNIFIED_DOCS_URL . 'assets/js/admin.js',
            ['jquery'],
            UNIFIED_DOCS_VERSION,
            true
        );

        // Localize script
        wp_localize_script('unified-docs-admin', 'unifiedDocsData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('unified_docs_nonce'),
        ]);
    }

    /**
     * Render main documentation page
     */
    public function render_page() {
        $cache = new Cache();
        $docs = $cache->get_organized_docs();

        include UNIFIED_DOCS_PATH . 'templates/admin-page.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Handle cache clear
        if (isset($_POST['clear_cache']) && check_admin_referer('unified_docs_clear_cache')) {
            $cache = new Cache();
            $cache->invalidate_all();
            echo '<div class="notice notice-success"><p>Cache cleared successfully!</p></div>';
        }

        $cache = new Cache();
        $stats = $cache->get_stats();

        include UNIFIED_DOCS_PATH . 'templates/settings-page.php';
    }
}
