<?php
namespace UnifiedDocs\Admin;

class Diagnostic {

    public static function init() {
        add_action('admin_menu', [self::class, 'add_menu']);
    }

    public static function add_menu() {
        add_submenu_page(
            null, // No parent - hidden from menu
            'Parsedown Diagnostic',
            'Parsedown Diagnostic',
            'manage_options',
            'unified-docs-diagnostic',
            [self::class, 'render_page']
        );
    }

    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }

        echo '<div class="wrap">';
        echo '<h1>Parsedown Diagnostic</h1>';

        $parsedown_path = UNIFIED_DOCS_PATH . 'lib/Parsedown.php';
        $parsedownextra_path = UNIFIED_DOCS_PATH . 'lib/ParsedownExtra.php';

        echo '<h2>File Paths</h2>';
        echo '<p><strong>Parsedown.php:</strong> <code>' . esc_html($parsedown_path) . '</code></p>';
        echo '<p><strong>ParsedownExtra.php:</strong> <code>' . esc_html($parsedownextra_path) . '</code></p>';

        echo '<h2>File Exists</h2>';
        echo '<p>Parsedown.php: ' . (file_exists($parsedown_path) ? '✅ YES' : '❌ NO') . '</p>';
        echo '<p>ParsedownExtra.php: ' . (file_exists($parsedownextra_path) ? '✅ YES' : '❌ NO') . '</p>';

        if (!file_exists($parsedown_path)) {
            echo '</div>';
            return;
        }

        echo '<h2>File Sizes</h2>';
        echo '<p>Parsedown.php: ' . number_format(filesize($parsedown_path)) . ' bytes</p>';
        echo '<p>ParsedownExtra.php: ' . number_format(filesize($parsedownextra_path)) . ' bytes</p>';

        echo '<h2>File Modified Times</h2>';
        echo '<p>Parsedown.php: <strong>' . date('Y-m-d H:i:s', filemtime($parsedown_path)) . '</strong></p>';
        echo '<p>ParsedownExtra.php: <strong>' . date('Y-m-d H:i:s', filemtime($parsedownextra_path)) . '</strong></p>';
        echo '<p><em>Current server time: ' . date('Y-m-d H:i:s') . '</em></p>';

        echo '<h2>First 15 Lines of Parsedown.php</h2>';
        echo '<pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">';
        $lines = file($parsedown_path);
        echo esc_html(implode('', array_slice($lines, 0, 15)));
        echo '</pre>';

        echo '<h2>Check for textElements Method in File</h2>';
        $content = file_get_contents($parsedown_path);
        if (strpos($content, 'function textElements') !== false) {
            echo '<p style="color: green; font-weight: bold;">✅ textElements() method FOUND in Parsedown.php file</p>';
            // Find the line number
            foreach ($lines as $num => $line) {
                if (strpos($line, 'function textElements') !== false) {
                    echo '<p>Found at line ' . ($num + 1) . '</p>';
                    echo '<pre style="background: #e7f7e7; padding: 10px; border: 1px solid #9d9;">';
                    echo esc_html(implode('', array_slice($lines, $num, 8)));
                    echo '</pre>';
                    break;
                }
            }
        } else {
            echo '<p style="color: red; font-weight: bold; background: #fee; padding: 10px;">❌ textElements() method NOT FOUND in Parsedown.php file</p>';
            echo '<p><strong>This is the problem!</strong> The file on the server is the old version.</p>';
            echo '<p>The deployment did not update this file correctly.</p>';
        }

        echo '<h2>Check Loaded Class</h2>';

        // Check if class is already loaded
        if (class_exists('Parsedown', false)) {
            echo '<p style="background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107;">⚠️  Parsedown class ALREADY LOADED before we could load our version!</p>';

            $reflection = new \ReflectionClass('Parsedown');
            $loaded_from = $reflection->getFileName();
            echo '<p><strong>Loaded from:</strong> <code>' . esc_html($loaded_from) . '</code></p>';

            // Check if it's our file or a different one
            if ($loaded_from === $parsedown_path) {
                echo '<p style="color: green;">✅ This IS our unified-docs Parsedown file</p>';
            } else {
                echo '<p style="color: red; font-weight: bold; background: #fee; padding: 10px;">❌ THIS IS A DIFFERENT FILE!</p>';
                echo '<p><strong>FOUND THE PROBLEM:</strong> Another plugin or WordPress core loaded a different Parsedown library before unified-docs could load its version.</p>';
                echo '<p><strong>Conflicting file:</strong> <code>' . esc_html($loaded_from) . '</code></p>';
            }

            // Check namespace
            $namespace = $reflection->getNamespaceName();
            if ($namespace) {
                echo '<p><strong>Namespace:</strong> <code>' . esc_html($namespace) . '</code></p>';
            } else {
                echo '<p><strong>Namespace:</strong> Global namespace (no namespace)</p>';
            }

            // Check version
            if (defined('Parsedown::version')) {
                echo '<p><strong>Version:</strong> ' . \Parsedown::version . '</p>';
            }

            if (method_exists('Parsedown', 'textElements')) {
                echo '<p style="color: green;">✅ textElements() method exists in LOADED class</p>';
            } else {
                echo '<p style="color: red; font-weight: bold;">❌ textElements() method DOES NOT exist in LOADED class</p>';
                if ($loaded_from !== $parsedown_path) {
                    echo '<p><strong>This confirms it:</strong> The conflicting file is an older version of Parsedown.</p>';
                }
            }
        } else {
            echo '<p style="color: green;">✅ Parsedown class not yet loaded - will load our version now...</p>';
            require_once $parsedown_path;

            $reflection = new \ReflectionClass('Parsedown');
            echo '<p><strong>Loaded from:</strong> <code>' . esc_html($reflection->getFileName()) . '</code></p>';

            if (method_exists('Parsedown', 'textElements')) {
                echo '<p style="color: green;">✅ textElements() method exists in NEWLY loaded class</p>';
                echo '<p>Our file is correct! The issue must be that another file loads first in production.</p>';
            } else {
                echo '<p style="color: red;">❌ textElements() method does not exist even in fresh load</p>';
            }
        }

        echo '<h2>Full Method List</h2>';
        if (class_exists('Parsedown')) {
            $methods = get_class_methods('Parsedown');
            echo '<p>Total methods: ' . count($methods) . '</p>';
            echo '<details><summary>Click to see all methods</summary>';
            echo '<pre style="background: #f5f5f5; padding: 10px;">';
            foreach ($methods as $method) {
                echo esc_html($method) . "\n";
                if ($method === 'textElements') {
                    echo "  ^^^ HERE IT IS ^^^\n";
                }
            }
            echo '</pre></details>';
        }

        echo '<hr>';
        echo '<p><strong>Access this page at:</strong> <code>wp-admin/admin.php?page=unified-docs-diagnostic</code></p>';
        echo '</div>';
    }
}
