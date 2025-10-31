<?php
/**
 * Temporary file to clear PHP OPcache
 * Upload this to staging and visit it in a browser, then delete it
 */

// Security: Only allow from specific IPs or with a secret key
$secret = isset($_GET['secret']) ? $_GET['secret'] : '';
if ($secret !== 'reset-opcache-2025') {
    die('Access denied');
}

echo '<h1>OPcache Reset</h1>';

if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo '<p style="color: green;">✅ OPcache successfully cleared!</p>';
        echo '<p>The Parsedown library files should now be reloaded with the updated versions.</p>';
    } else {
        echo '<p style="color: red;">❌ Failed to clear OPcache</p>';
    }

    // Show OPcache status
    $status = opcache_get_status();
    echo '<h2>OPcache Status</h2>';
    echo '<pre>';
    print_r($status);
    echo '</pre>';
} else {
    echo '<p style="color: orange;">⚠️  OPcache is not enabled on this server</p>';
    echo '<p>The issue might be something else. Check file permissions and paths.</p>';
}

echo '<hr>';
echo '<p><strong>Important:</strong> Delete this file after use for security!</p>';
echo '<p>File location: ' . __FILE__ . '</p>';
