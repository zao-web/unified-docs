<?php
/**
 * Admin Page Template
 *
 * @var array $docs Organized documentation structure
 */

if (!defined('ABSPATH')) exit;
?>

<div class="unified-docs-wrapper">
    <!-- Header with Back Button -->
    <div class="unified-docs-header">
        <div class="unified-docs-site-hub">
            <div class="unified-docs-site-icon">
                <a href="<?php echo esc_url(admin_url()); ?>" class="unified-docs-back-button" title="<?php esc_attr_e('Back to WordPress Admin', 'unified-docs'); ?>">
                    <span class="dashicons dashicons-wordpress-alt"></span>
                </a>
            </div>
            <div class="unified-docs-site-title">
                <a href="<?php echo esc_url(admin_url()); ?>" class="unified-docs-back-link">
                    <?php bloginfo('name'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content Area (Sidebar + Content) -->
    <div class="unified-docs-main-area">
        <!-- Sidebar -->
        <div class="unified-docs-sidebar">
        <div class="unified-docs-sidebar-header">
            <h2>Documentation</h2>
            <div class="unified-docs-search">
                <input
                    type="text"
                    id="unified-docs-search-input"
                    placeholder="Search documentation..."
                    autocomplete="off"
                />
                <span class="dashicons dashicons-search"></span>
            </div>
        </div>

        <div class="unified-docs-search-results" id="unified-docs-search-results" style="display: none;">
            <!-- Search results populated via JS -->
        </div>

        <nav class="unified-docs-nav" id="unified-docs-nav">
            <?php if (empty($docs['categories']) && empty($docs['uncategorized'])): ?>
                <div class="unified-docs-empty">
                    <p>No documentation found.</p>
                    <p class="description">Add markdown files to a <code>/docs</code> or <code>/documentation</code> directory in your active themes or plugins.</p>
                </div>
            <?php else: ?>
                <?php foreach ($docs['categories'] as $category): ?>
                    <div class="unified-docs-category">
                        <div class="unified-docs-category-header">
                            <h3><?php echo esc_html($category['name']); ?></h3>
                            <?php if (!empty($category['description'])): ?>
                                <p class="category-description"><?php echo esc_html($category['description']); ?></p>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($category['docs'])): ?>
                            <ul class="unified-docs-list">
                                <?php foreach ($category['docs'] as $doc): ?>
                                    <li>
                                        <a href="#"
                                           class="unified-docs-link"
                                           data-path="<?php echo esc_attr($doc['path']); ?>"
                                           data-title="<?php echo esc_attr($doc['parsed']['title']); ?>">
                                            <span class="doc-title"><?php echo esc_html($doc['parsed']['title'] ?: $doc['filename']); ?></span>
                                            <span class="doc-source"><?php echo esc_html($doc['source_name']); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if (!empty($category['subcategories'])): ?>
                            <?php foreach ($category['subcategories'] as $subcat): ?>
                                <div class="unified-docs-subcategory">
                                    <h4><?php echo esc_html($subcat['name']); ?></h4>
                                    <?php if (!empty($subcat['description'])): ?>
                                        <p class="category-description"><?php echo esc_html($subcat['description']); ?></p>
                                    <?php endif; ?>

                                    <?php if (!empty($subcat['docs'])): ?>
                                        <ul class="unified-docs-list">
                                            <?php foreach ($subcat['docs'] as $doc): ?>
                                                <li>
                                                    <a href="#"
                                                       class="unified-docs-link"
                                                       data-path="<?php echo esc_attr($doc['path']); ?>"
                                                       data-title="<?php echo esc_attr($doc['parsed']['title']); ?>">
                                                        <span class="doc-title"><?php echo esc_html($doc['parsed']['title'] ?: $doc['filename']); ?></span>
                                                        <span class="doc-source"><?php echo esc_html($doc['source_name']); ?></span>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php if (!empty($docs['uncategorized'])): ?>
                    <div class="unified-docs-category">
                        <div class="unified-docs-category-header">
                            <h3>Other Documentation</h3>
                        </div>
                        <ul class="unified-docs-list">
                            <?php foreach ($docs['uncategorized'] as $doc): ?>
                                <li>
                                    <a href="#"
                                       class="unified-docs-link"
                                       data-path="<?php echo esc_attr($doc['path']); ?>"
                                       data-title="<?php echo esc_attr($doc['parsed']['title']); ?>">
                                        <span class="doc-title"><?php echo esc_html($doc['parsed']['title'] ?: $doc['filename']); ?></span>
                                        <span class="doc-source"><?php echo esc_html($doc['source_name']); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
    </div>

    <!-- Content Area -->
    <div class="unified-docs-content">
        <div class="unified-docs-content-inner" id="unified-docs-content">
            <div class="unified-docs-welcome">
                <h1>Welcome to Documentation</h1>
                <p>Select a document from the sidebar to get started.</p>

                <?php if (!function_exists('ai_services_get_services_instance')): ?>
                    <div class="notice notice-warning inline">
                        <p><strong>AI Services plugin not detected.</strong> Documentation is organized by source. Install and configure the AI Services plugin for intelligent organization.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div> <!-- /unified-docs-main-area -->
</div>
