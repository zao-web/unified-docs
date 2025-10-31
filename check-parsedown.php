<?php
/**
 * Diagnostic script to check Parsedown status
 * Visit: /wp-content/mu-plugins/unified-docs/check-parsedown.php?secret=check-2025
 */

$secret = isset($_GET['secret']) ? $_GET['secret'] : '';
if ($secret !== 'check-2025') {
    die('Access denied');
}

echo '<h1>Parsedown Diagnostic</h1>';

$parsedown_path = __DIR__ . '/lib/Parsedown.php';
$parsedownextra_path = __DIR__ . '/lib/ParsedownExtra.php';

echo '<h2>File Paths</h2>';
echo '<p><strong>Parsedown.php:</strong> ' . $parsedown_path . '</p>';
echo '<p><strong>ParsedownExtra.php:</strong> ' . $parsedownextra_path . '</p>';

echo '<h2>File Exists</h2>';
echo '<p>Parsedown.php: ' . (file_exists($parsedown_path) ? '✅ YES' : '❌ NO') . '</p>';
echo '<p>ParsedownExtra.php: ' . (file_exists($parsedownextra_path) ? '✅ YES' : '❌ NO') . '</p>';

echo '<h2>File Sizes</h2>';
echo '<p>Parsedown.php: ' . filesize($parsedown_path) . ' bytes</p>';
echo '<p>ParsedownExtra.php: ' . filesize($parsedownextra_path) . ' bytes</p>';

echo '<h2>File Modified Times</h2>';
echo '<p>Parsedown.php: ' . date('Y-m-d H:i:s', filemtime($parsedown_path)) . '</p>';
echo '<p>ParsedownExtra.php: ' . date('Y-m-d H:i:s', filemtime($parsedownextra_path)) . '</p>';

echo '<h2>First 10 Lines of Parsedown.php</h2>';
echo '<pre>';
$lines = file($parsedown_path);
echo htmlspecialchars(implode('', array_slice($lines, 0, 10)));
echo '</pre>';

echo '<h2>Check for textElements Method</h2>';
$content = file_get_contents($parsedown_path);
if (strpos($content, 'function textElements') !== false) {
    echo '<p style="color: green;">✅ textElements() method FOUND in Parsedown.php</p>';
    // Find the line number
    foreach ($lines as $num => $line) {
        if (strpos($line, 'function textElements') !== false) {
            echo '<p>Found at line ' . ($num + 1) . '</p>';
            echo '<pre>' . htmlspecialchars(implode('', array_slice($lines, $num, 5))) . '</pre>';
            break;
        }
    }
} else {
    echo '<p style="color: red;">❌ textElements() method NOT FOUND in Parsedown.php</p>';
    echo '<p><strong>This is the problem!</strong> The file on the server is outdated.</p>';
}

echo '<h2>Class Check</h2>';
require_once $parsedown_path;
require_once $parsedownextra_path;

if (class_exists('Parsedown')) {
    echo '<p>✅ Parsedown class loaded</p>';
    $parsedown = new Parsedown();
    echo '<p>Parsedown version: ' . Parsedown::version . '</p>';

    if (method_exists($parsedown, 'textElements')) {
        echo '<p style="color: green;">✅ textElements() method exists in loaded class</p>';
    } else {
        echo '<p style="color: red;">❌ textElements() method DOES NOT exist in loaded class</p>';
        echo '<p>Available methods:</p><pre>';
        print_r(get_class_methods($parsedown));
        echo '</pre>';
    }
}

echo '<hr><p><strong>Delete this file after checking!</strong></p>';
