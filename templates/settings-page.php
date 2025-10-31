<?php
/**
 * Settings Page Template
 *
 * @var array $stats Cache statistics
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1>Unified Docs Settings</h1>

    <div class="unified-docs-settings">
        <div class="card">
            <h2>Cache Management</h2>
            <p>Documentation is cached for one week or until files change. Clear the cache to force a refresh and re-organization.</p>

            <table class="form-table">
                <tr>
                    <th scope="row">Cache Status</th>
                    <td>
                        <?php if ($stats['is_cached']): ?>
                            <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                            <strong>Cached</strong>
                        <?php else: ?>
                            <span class="dashicons dashicons-warning" style="color: #ffb900;"></span>
                            <strong>Not Cached</strong>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Files Hash</th>
                    <td><code><?php echo esc_html($stats['files_hash']); ?></code></td>
                </tr>
                <tr>
                    <th scope="row">Hash Match</th>
                    <td>
                        <?php if ($stats['hash_matches']): ?>
                            <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> Yes
                        <?php else: ?>
                            <span class="dashicons dashicons-warning" style="color: #ffb900;"></span> No (cache will be regenerated)
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <form method="post" action="">
                <?php wp_nonce_field('unified_docs_clear_cache'); ?>
                <p>
                    <button type="submit" name="clear_cache" class="button button-secondary">
                        Clear Cache
                    </button>
                </p>
            </form>
        </div>

        <div class="card">
            <h2>AI Services Status</h2>
            <?php if (function_exists('ai_services')): ?>
                <?php
                $ai_api = ai_services();
                $has_services = $ai_api->has_available_services();
                $service_list = [];

                if ($has_services) {
                    // Get all registered service slugs
                    $registered_slugs = $ai_api->get_registered_service_slugs();

                    foreach ($registered_slugs as $slug) {
                        if ($ai_api->is_service_available($slug)) {
                            $service = $ai_api->get_available_service($slug);
                            $metadata = $ai_api->get_service_metadata($slug);

                            $service_list[] = [
                                'slug' => $slug,
                                'name' => $metadata ? $metadata->get_name() : ucfirst($slug),
                                'capabilities' => $metadata ? $metadata->get_capabilities() : []
                            ];
                        }
                    }
                }
                ?>
                <p>
                    <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                    <strong>AI Services plugin is active</strong>
                </p>

                <?php if ($has_services): ?>
                    <p>
                        <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                        <strong><?php echo count($service_list); ?> AI service(s) configured</strong>
                    </p>
                    <table class="widefat" style="margin-top: 10px;">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Model</th>
                                <th>Capabilities</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($service_list as $svc): ?>
                                <tr>
                                    <td><code><?php echo esc_html($svc['slug']); ?></code></td>
                                    <td><?php echo esc_html($svc['name']); ?></td>
                                    <td>
                                        <?php if (!empty($svc['capabilities'])): ?>
                                            <?php echo esc_html(implode(', ', $svc['capabilities'])); ?>
                                        <?php else: ?>
                                            <em>Unknown</em>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p style="margin-top: 15px;">
                        <strong>AI Search Status:</strong>
                        <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> Enabled
                    </p>
                    <p class="description">
                        AI-powered search is enabled! Users can ask natural language questions and get intelligent answers with citations.
                    </p>
                <?php else: ?>
                    <p>
                        <span class="dashicons dashicons-warning" style="color: #ffb900;"></span>
                        <strong>No AI services configured</strong>
                    </p>
                    <p class="description">
                        Go to <a href="<?php echo admin_url('admin.php?page=ai-services'); ?>">Settings → AI Services</a> to configure an AI provider (OpenAI, Anthropic, Google AI, etc.).
                    </p>
                    <p class="description">
                        Without AI configuration, the plugin will use basic keyword search instead of AI-powered semantic search.
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <p>
                    <span class="dashicons dashicons-warning" style="color: #ffb900;"></span>
                    <strong>AI Services plugin not found</strong>
                </p>
                <p class="description">
                    Install and activate the <a href="<?php echo admin_url('plugin-install.php?s=AI+Services&tab=search&type=term'); ?>">AI Services plugin</a> to enable:
                </p>
                <ul style="margin-left: 20px;">
                    <li>AI-powered semantic search</li>
                    <li>Intelligent answer generation</li>
                    <li>Smart documentation organization</li>
                </ul>
                <p class="description">
                    Without it, documentation will be organized by source (theme/plugin) and use basic keyword search.
                </p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Documentation Guide</h2>
            <h3>Adding Documentation</h3>
            <p>To add documentation to your theme or plugin:</p>
            <ol>
                <li>Create a <code>/docs</code> or <code>/documentation</code> folder in your theme or plugin directory</li>
                <li>Add markdown (.md) files to this folder</li>
                <li>Optionally, add frontmatter to your markdown files for additional metadata</li>
            </ol>

            <h3>Frontmatter Example</h3>
            <pre>---
title: Getting Started
category: Introduction
order: 1
video: https://www.youtube.com/embed/abc123
---

# Your markdown content here</pre>

            <h3>Supported Frontmatter Fields</h3>
            <ul>
                <li><strong>title:</strong> Document title (if not set, first H1 will be used)</li>
                <li><strong>category:</strong> Manual category assignment</li>
                <li><strong>order:</strong> Sort order within category (lower numbers first)</li>
                <li><strong>video:</strong> URL to an embedded video (YouTube, Vimeo, etc.)</li>
            </ul>
        </div>
    </div>
</div>

<style>
.unified-docs-settings .card {
    max-width: 800px;
    margin-bottom: 20px;
}

.unified-docs-settings pre {
    background: #f5f5f5;
    padding: 15px;
    border-left: 3px solid #2271b1;
    overflow-x: auto;
}
</style>
