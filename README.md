# Unified Docs

A powerful WordPress MU plugin that automatically discovers, organizes, and beautifully displays documentation from all your active themes and plugins.

## Features

- **Automatic Discovery** - Scans active themes and plugins for markdown documentation
- **AI-Powered Organization** - Uses AI Services plugin to intelligently categorize and structure documentation
- **Modern Interface** - Beautiful, Site Editor-inspired design with black sidebar
- **Smart Caching** - Automatic cache management with file change detection
- **Client-Side Search** - Fast, instant search across all documentation
- **Video Support** - Embed videos alongside documentation via frontmatter
- **Markdown Support** - Full ParsedownExtra support with tables, code blocks, and more
- **Responsive Design** - Works great on all screen sizes

## Installation

As an MU (Must Use) plugin, installation is simple:

1. Copy the `unified-docs` folder to `/wp-content/mu-plugins/`
2. Copy the `unified-docs.php` file to `/wp-content/mu-plugins/`
3. The plugin will automatically activate

## Requirements

- WordPress 5.0+
- PHP 7.4+
- (Optional) AI Services plugin for intelligent organization

## Usage

### Adding Documentation

1. Create a `/docs` or `/documentation` folder in your theme or plugin
2. Add markdown (`.md`) files
3. Documentation appears automatically in the **Docs** menu

### Frontmatter

Enhance your documentation with YAML frontmatter:

```markdown
---
title: My Document Title
order: 1
category: Tutorial
video: https://www.youtube.com/embed/abc123
---

# Your content here
```

#### Supported Fields

- `title` - Document title (defaults to first H1 or filename)
- `order` - Sort order within category (lower numbers first)
- `category` - Manual category assignment
- `video` - Embedded video URL (YouTube, Vimeo, etc.)

### Example Documentation Structure

```
your-plugin/
├── plugin.php
└── docs/
    ├── getting-started.md
    ├── installation.md
    ├── configuration.md
    └── advanced/
        ├── api-reference.md
        └── hooks-filters.md
```

## AI Organization

When the AI Services plugin is installed and configured:

1. Documentation is automatically categorized
2. Logical hierarchies are created
3. Category descriptions are generated
4. Documents are ordered intelligently

Without AI Services, documentation is organized by source (theme/plugin name).

## Caching

- Cache duration: 1 week
- Automatically invalidates when files change
- Manual cache clearing available in Settings
- Uses WordPress transients

## Admin Interface

### Main Documentation Page

Access via **Docs** menu in WordPress admin:

- Browse documentation by category
- Click any document to view
- Search across all documentation
- Watch embedded videos

### Settings Page

Access via **Docs → Settings**:

- View cache status
- Clear cache manually
- Check AI Services integration
- View documentation guide

## Search

The search feature:

- Searches titles, content, and filenames
- Highlights matches in results
- Shows contextual snippets
- Ranks results by relevance
- Updates as you type

## File Structure

```
unified-docs/
├── unified-docs.php          # Main plugin loader
├── includes/
│   ├── Admin/
│   │   └── Menu.php          # Admin menu and pages
│   ├── Ajax/
│   │   └── Handler.php       # AJAX request handlers
│   └── Core/
│       ├── Scanner.php       # Documentation file scanner
│       ├── Parser.php        # Markdown parser with frontmatter
│       ├── Organizer.php     # AI-powered organization
│       └── Cache.php         # Cache management
├── templates/
│   ├── admin-page.php        # Main documentation interface
│   └── settings-page.php     # Settings page
├── assets/
│   ├── css/
│   │   └── admin.css         # Modern styling
│   └── js/
│       └── admin.js          # Frontend functionality
├── lib/
│   ├── Parsedown.php         # Markdown parser
│   └── ParsedownExtra.php    # Extended markdown features
└── docs/
    └── *.md                   # Plugin documentation
```

## Developer Notes

### Hooks & Filters

Currently, the plugin doesn't expose hooks, but they can be added as needed.

### Class Structure

- Namespaced under `UnifiedDocs\`
- PSR-4 autoloading
- Singleton pattern for main classes
- Static methods for AJAX handlers

### Extending

To extend functionality:

1. Add new classes to `includes/` directory
2. Follow namespace pattern `UnifiedDocs\Category\ClassName`
3. Autoloader will handle loading
4. Initialize in main plugin file

## Performance

- Lightweight: ~50KB total size (excluding Parsedown)
- Efficient caching prevents repeated AI calls
- Only loads assets on documentation pages
- Minimal database queries

## Compatibility

Tested with:
- WordPress 6.0+
- Classic themes
- Block themes
- WooCommerce
- Popular page builders

## Troubleshooting

### Documentation Not Appearing

1. Check folder name is `/docs` or `/documentation`
2. Verify files have `.md` extension
3. Ensure theme/plugin is active
4. Clear cache in Settings

### AI Organization Not Working

1. Verify AI Services plugin is installed
2. Configure at least one AI service
3. Check for PHP errors in debug log
4. Clear cache to regenerate

### Search Not Working

1. Check browser console for JavaScript errors
2. Verify AJAX URL is correct
3. Check user has proper permissions

## Support

For issues and feature requests, contact your development team.

## License

GPL v2 or later

## Changelog

### 1.0.0
- Initial release
- Automatic documentation discovery
- AI-powered organization
- Modern interface
- Search functionality
- Video support
- Caching system
