<?php

namespace UnifiedDocs\Core;

/**
 * Markdown Parser
 *
 * Parses markdown files with frontmatter support
 */
class Parser {

    /**
     * ParsedownExtra instance
     */
    private $parsedown;

    /**
     * Constructor
     */
    public function __construct() {
        $this->parsedown = new \ParsedownExtra();
    }

    /**
     * Parse a markdown file
     *
     * @param string $file_path Path to markdown file
     * @return array Parsed content with metadata
     */
    public function parse_file($file_path) {
        if (!file_exists($file_path)) {
            return null;
        }

        $content = file_get_contents($file_path);
        $parsed = $this->parse_frontmatter($content);

        return [
            'frontmatter' => $parsed['frontmatter'],
            'html' => $this->parsedown->text($parsed['content']),
            'raw' => $parsed['content'],
            'title' => $this->extract_title($parsed),
        ];
    }

    /**
     * Parse frontmatter from markdown content
     *
     * @param string $content Markdown content
     * @return array Frontmatter and content
     */
    private function parse_frontmatter($content) {
        $frontmatter = [];
        $markdown_content = $content;

        // Check for YAML frontmatter (between --- delimiters)
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $content, $matches)) {
            $yaml_content = $matches[1];
            $markdown_content = $matches[2];

            // Parse YAML (simple key: value pairs and arrays)
            $lines = explode("\n", $yaml_content);
            $current_key = null;

            foreach ($lines as $line) {
                $trimmed = trim($line);

                // Handle array items
                if ($current_key && preg_match('/^-\s+(.+)$/', $trimmed, $array_matches)) {
                    if (!isset($frontmatter[$current_key])) {
                        $frontmatter[$current_key] = [];
                    }
                    $frontmatter[$current_key][] = trim($array_matches[1]);
                    continue;
                }

                // Handle key: value pairs
                if (preg_match('/^([^:]+):\s*(.*)$/', $trimmed, $line_matches)) {
                    $key = trim($line_matches[1]);
                    $value = trim($line_matches[2]);

                    // Check if this is an inline array [item1, item2]
                    if (preg_match('/^\[(.+)\]$/', $value, $array_content)) {
                        $items = array_map('trim', explode(',', $array_content[1]));
                        $frontmatter[$key] = $items;
                        $current_key = null;
                    }
                    // Empty value might mean array follows
                    elseif (empty($value)) {
                        $current_key = $key;
                    }
                    // Regular key: value pair
                    else {
                        // Remove quotes if present
                        $value = trim($value, '"\'');
                        $frontmatter[$key] = $value;
                        $current_key = null;
                    }
                }
            }
        }

        return [
            'frontmatter' => $frontmatter,
            'content' => $markdown_content,
        ];
    }

    /**
     * Extract title from parsed content
     *
     * Priority: frontmatter title > first H1 > filename
     *
     * @param array $parsed Parsed content
     * @return string Title
     */
    private function extract_title($parsed) {
        // Check frontmatter first
        if (!empty($parsed['frontmatter']['title'])) {
            return $parsed['frontmatter']['title'];
        }

        // Try to extract first H1 from content
        if (preg_match('/^#\s+(.+)$/m', $parsed['content'], $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    /**
     * Convert video URL to embed format
     *
     * Supports YouTube, Loom, and Vimeo URLs
     *
     * @param string $url Original video URL
     * @return string Embed URL or original URL if not recognized
     */
    private function convert_to_embed_url($url) {
        if (empty($url)) {
            return '';
        }

        // YouTube - Watch URL
        // https://www.youtube.com/watch?v=VIDEO_ID or https://youtu.be/VIDEO_ID
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        // YouTube - Shorts
        // https://www.youtube.com/shorts/VIDEO_ID
        if (preg_match('/youtube\.com\/shorts\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        // Loom - Share URL
        // https://www.loom.com/share/VIDEO_ID
        if (preg_match('/loom\.com\/share\/([a-zA-Z0-9]+)/', $url, $matches)) {
            return 'https://www.loom.com/embed/' . $matches[1];
        }

        // Vimeo
        // https://vimeo.com/VIDEO_ID
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
            return 'https://player.vimeo.com/video/' . $matches[1];
        }

        // If already an embed URL or unrecognized, return as-is
        return $url;
    }

    /**
     * Parse multiple files and return structured data
     *
     * @param array $files Array of file info from Scanner
     * @return array Parsed documentation structure
     */
    public function parse_multiple($files) {
        $parsed_docs = [];

        foreach ($files as $file_info) {
            $parsed = $this->parse_file($file_info['path']);

            if ($parsed) {
                $screens = $parsed['frontmatter']['screens'] ?? [];

                // Ensure screens is always an array
                if (!is_array($screens)) {
                    $screens = !empty($screens) ? [$screens] : [];
                }

                $video_url = $parsed['frontmatter']['video'] ?? '';

                $parsed_docs[] = array_merge($file_info, [
                    'parsed' => $parsed,
                    'video_url' => $this->convert_to_embed_url($video_url),
                    'order' => $parsed['frontmatter']['order'] ?? 999,
                    'category' => $parsed['frontmatter']['category'] ?? '',
                    'screens' => $screens,
                ]);
            }
        }

        return $parsed_docs;
    }
}
