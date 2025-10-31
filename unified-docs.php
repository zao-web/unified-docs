<?php
/**
 * Plugin Name: Unified Docs
 * Description: Scans active themes and plugins for markdown documentation, organizes it with AI, and displays it in a modern interface
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('UNIFIED_DOCS_VERSION', '1.0.0');
define('UNIFIED_DOCS_PATH', __DIR__ . '/unified-docs/');
define('UNIFIED_DOCS_URL', content_url('mu-plugins/unified-docs/'));

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    if (strpos($class, 'UnifiedDocs\\') !== 0) {
        return;
    }

    $class_file = str_replace('\\', '/', str_replace('UnifiedDocs\\', '', $class));
    $file = UNIFIED_DOCS_PATH . 'includes/' . $class_file . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Main plugin class
 */
class Unified_Docs {

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
        add_action('plugins_loaded', [$this, 'init']);
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Load dependencies
        $this->load_dependencies();

        // Initialize components
        if (is_admin()) {
            UnifiedDocs\Admin\Menu::get_instance();
            UnifiedDocs\Core\ContextualHelp::get_instance();

            // Diagnostic page (hidden from menu)
            UnifiedDocs\Admin\Diagnostic::init();
        }

        // Register AJAX handlers
        add_action('wp_ajax_unified_docs_get_content', [UnifiedDocs\Ajax\Handler::class, 'get_content']);
        add_action('wp_ajax_unified_docs_search', [UnifiedDocs\Ajax\Handler::class, 'search']);
        add_action('wp_ajax_unified_docs_ai_search', [UnifiedDocs\Ajax\AISearch::class, 'search']);
    }

    /**
     * Load dependencies
     */
    private function load_dependencies() {
        // Load Parsedown library
        if (!class_exists('Parsedown')) {
            require_once UNIFIED_DOCS_PATH . 'lib/Parsedown.php';
        }

        if (!class_exists('ParsedownExtra')) {
            require_once UNIFIED_DOCS_PATH . 'lib/ParsedownExtra.php';
        }
    }
}

// Initialize plugin
Unified_Docs::get_instance();
