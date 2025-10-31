(function($) {
    'use strict';

    const UnifiedDocs = {
        currentDoc: null,
        searchTimeout: null,

        init: function() {
            this.bindEvents();
            this.loadFirstDoc();
        },

        bindEvents: function() {
            // Document link clicks
            $(document).on('click', '.unified-docs-link', this.handleDocClick.bind(this));

            // Search functionality
            $('#unified-docs-search-input').on('input', this.handleSearch.bind(this));

            // Click outside search results to close
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.unified-docs-search').length) {
                    $('#unified-docs-search-results').hide();
                    $('#unified-docs-nav').show();
                }
            });
        },

        handleDocClick: function(e) {
            e.preventDefault();

            const $link = $(e.currentTarget);
            const path = $link.data('path');
            const title = $link.data('title');

            // Update active state
            $('.unified-docs-link').removeClass('active');
            $link.addClass('active');

            // Load document
            this.loadDocument(path, title);
        },

        loadDocument: function(path, title) {
            const $content = $('#unified-docs-content');

            // Show loading state
            $content.html('<div class="unified-docs-loading"><span class="spinner is-active"></span><p>Loading documentation...</p></div>');

            // AJAX request
            $.ajax({
                url: unifiedDocsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'unified_docs_get_content',
                    nonce: unifiedDocsData.nonce,
                    doc_path: path
                },
                success: function(response) {
                    if (response.success) {
                        this.renderDocument(response.data);
                    } else {
                        this.showError(response.data.message || 'Failed to load document');
                    }
                }.bind(this),
                error: function() {
                    this.showError('Network error occurred');
                }.bind(this)
            });
        },

        renderDocument: function(data) {
            const $content = $('#unified-docs-content');

            let html = '<div class="unified-docs-document">';

            // Add video if present
            if (data.video_url) {
                html += '<div class="unified-docs-video-container">';
                html += '<iframe src="' + this.escapeHtml(data.video_url) + '" ';
                html += 'frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" ';
                html += 'allowfullscreen></iframe>';
                html += '</div>';
            }

            // Add document header
            html += '<div class="unified-docs-document-header">';
            html += '<h1>' + this.escapeHtml(data.title) + '</h1>';
            html += '<p class="unified-docs-document-source">From: ' + this.escapeHtml(data.source) + '</p>';
            html += '</div>';

            // Add content
            html += '<div class="unified-docs-document-content">';
            html += data.html;
            html += '</div>';

            html += '</div>';

            $content.html(html);

            // Scroll to top
            $content.scrollTop(0);
        },

        showError: function(message) {
            const $content = $('#unified-docs-content');
            $content.html(
                '<div class="unified-docs-error">' +
                '<span class="dashicons dashicons-warning"></span>' +
                '<p>' + this.escapeHtml(message) + '</p>' +
                '</div>'
            );
        },

        handleSearch: function(e) {
            const query = $(e.target).val().trim();

            // Clear previous timeout
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }

            // If query is empty, show navigation
            if (query.length === 0) {
                $('#unified-docs-search-results').hide();
                $('#unified-docs-nav').show();
                return;
            }

            // Wait for user to stop typing
            this.searchTimeout = setTimeout(function() {
                this.performSearch(query);
            }.bind(this), 300);
        },

        performSearch: function(query) {
            const $results = $('#unified-docs-search-results');

            // Show loading with AI indicator
            $results.html('<div class="unified-docs-search-loading"><span class="spinner is-active"></span><p>Thinking...</p></div>').show();
            $('#unified-docs-nav').hide();

            // Try AI search first
            $.ajax({
                url: unifiedDocsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'unified_docs_ai_search',
                    nonce: unifiedDocsData.nonce,
                    query: query
                },
                success: function(response) {
                    if (response.success) {
                        this.renderAISearchResults(response.data, query);
                    } else if (response.data && response.data.fallback) {
                        // AI not available, fall back to keyword search
                        this.performKeywordSearch(query);
                    } else {
                        $results.html('<p class="no-results">Search failed</p>');
                    }
                }.bind(this),
                error: function() {
                    // On error, try keyword search as fallback
                    this.performKeywordSearch(query);
                }.bind(this)
            });
        },

        performKeywordSearch: function(query) {
            const $results = $('#unified-docs-search-results');

            // Show loading
            $results.html('<div class="unified-docs-search-loading"><span class="spinner is-active"></span></div>').show();

            // AJAX search
            $.ajax({
                url: unifiedDocsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'unified_docs_search',
                    nonce: unifiedDocsData.nonce,
                    query: query
                },
                success: function(response) {
                    if (response.success) {
                        this.renderSearchResults(response.data.results, query);
                    } else {
                        $results.html('<p class="no-results">Search failed</p>');
                    }
                }.bind(this),
                error: function() {
                    $results.html('<p class="no-results">Search error occurred</p>');
                }
            });
        },

        renderAISearchResults: function(data, query) {
            const $results = $('#unified-docs-search-results');

            if (data.no_results) {
                $results.html('<p class="no-results">No results found for "' + this.escapeHtml(query) + '"</p>');
                return;
            }

            let html = '';

            // AI Answer Card
            if (data.answer) {
                html += '<div class="ai-answer-card">';
                html += '<div class="ai-answer-header">';
                html += '<span class="ai-badge">AI Answer</span>';
                html += '</div>';
                html += '<div class="ai-answer-content">' + this.formatAIAnswer(data.answer) + '</div>';

                // Sources
                if (data.sources && data.sources.length > 0) {
                    html += '<div class="ai-answer-sources">';
                    html += '<span class="sources-label">Sources:</span>';
                    data.sources.forEach(function(source) {
                        html += '<button class="source-badge" data-path="' + this.escapeHtml(source.path) + '" data-title="' + this.escapeHtml(source.title) + '">';
                        html += this.escapeHtml(source.title);
                        html += '</button>';
                    }.bind(this));
                    html += '</div>';
                }

                // Related topics
                if (data.related && data.related.length > 0) {
                    html += '<div class="ai-answer-related">';
                    html += '<span class="related-label">Related:</span>';
                    html += '<span class="related-topics">' + data.related.join(', ') + '</span>';
                    html += '</div>';
                }

                html += '</div>';
            }

            // Document Results
            if (data.documents && data.documents.length > 0) {
                html += '<div class="search-results-header"><strong>' + data.documents.length + '</strong> relevant document' + (data.documents.length !== 1 ? 's' : '') + '</div>';
                html += '<ul class="unified-docs-search-list">';

                data.documents.forEach(function(doc) {
                    html += '<li>';
                    html += '<a href="#" class="unified-docs-search-result" data-path="' + this.escapeHtml(doc.path) + '" data-title="' + this.escapeHtml(doc.title) + '">';
                    html += '<div class="result-title">' + this.escapeHtml(doc.title) + '</div>';
                    html += '<div class="result-snippet">' + this.escapeHtml(doc.snippet) + '</div>';
                    html += '<div class="result-source">' + this.escapeHtml(doc.source) + '</div>';
                    html += '</a>';
                    html += '</li>';
                }.bind(this));

                html += '</ul>';
            }

            $results.html(html);

            // Bind click events
            $('.unified-docs-search-result, .source-badge').on('click', function(e) {
                e.preventDefault();
                const $link = $(e.currentTarget);
                this.loadDocument($link.data('path'), $link.data('title'));

                // Clear search
                $('#unified-docs-search-input').val('');
                $results.hide();
                $('#unified-docs-nav').show();
            }.bind(this));
        },

        formatAIAnswer: function(answer) {
            // Convert citation numbers [1] to superscript
            answer = answer.replace(/\[(\d+)\]/g, '<sup>$1</sup>');

            // Convert newlines to paragraphs
            const paragraphs = answer.split('\n\n');
            return paragraphs.map(p => '<p>' + this.escapeHtml(p) + '</p>').join('');
        },

        renderSearchResults: function(results, query) {
            const $results = $('#unified-docs-search-results');

            if (results.length === 0) {
                $results.html('<p class="no-results">No results found for "' + this.escapeHtml(query) + '"</p>');
                return;
            }

            let html = '<div class="search-results-header"><strong>' + results.length + '</strong> result' + (results.length !== 1 ? 's' : '') + ' found</div>';
            html += '<ul class="unified-docs-search-list">';

            results.forEach(function(result) {
                html += '<li>';
                html += '<a href="#" class="unified-docs-search-result" data-path="' + this.escapeHtml(result.path) + '" data-title="' + this.escapeHtml(result.title) + '">';
                html += '<div class="result-title">' + this.escapeHtml(result.title) + '</div>';
                html += '<div class="result-snippet">' + result.snippet + '</div>';
                html += '<div class="result-source">' + this.escapeHtml(result.source) + '</div>';
                html += '</a>';
                html += '</li>';
            }.bind(this));

            html += '</ul>';

            $results.html(html);

            // Bind click events to search results
            $('.unified-docs-search-result').on('click', function(e) {
                e.preventDefault();
                const $link = $(e.currentTarget);
                this.loadDocument($link.data('path'), $link.data('title'));

                // Clear search
                $('#unified-docs-search-input').val('');
                $results.hide();
                $('#unified-docs-nav').show();
            }.bind(this));
        },

        loadFirstDoc: function() {
            // Auto-load first document if available
            const $firstLink = $('.unified-docs-link').first();
            if ($firstLink.length) {
                $firstLink.addClass('active');
                this.loadDocument($firstLink.data('path'), $firstLink.data('title'));
            }
        },

        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.unified-docs-wrapper').length) {
            UnifiedDocs.init();
        }
    });

})(jQuery);
