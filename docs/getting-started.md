---
title: Getting Started with Unified Docs
order: 1
category: Introduction
---

# Getting Started with Unified Docs

Welcome to Unified Docs! This plugin automatically discovers and organizes documentation from all your active WordPress themes and plugins.

## How It Works

Unified Docs scans your WordPress installation for documentation files and presents them in a beautiful, searchable interface. Here's what makes it special:

### Automatic Discovery

The plugin automatically finds markdown files in:
- `/docs` directories
- `/documentation` directories

It searches through:
- Your active theme
- Your parent theme (if using a child theme)
- All active plugins

### AI-Powered Organization

When the **AI Services** plugin is installed and configured, Unified Docs uses artificial intelligence to:

- Intelligently categorize your documentation
- Create logical groups and hierarchies
- Generate helpful descriptions for each category
- Order documents in a natural learning sequence

### Smart Caching

Documentation is cached for performance:
- Cache duration: 1 week
- Auto-invalidates when files change
- Manual cache clearing available in Settings

## Adding Documentation

To add documentation to your theme or plugin:

1. Create a `/docs` or `/documentation` folder in your theme/plugin root
2. Add markdown (`.md`) files
3. Optionally include frontmatter for metadata

### Example Markdown File

```markdown
---
title: My Custom Feature
order: 5
category: Features
video: https://www.youtube.com/watch?v=dQw4w9WgXcQ
---

# My Custom Feature

Your documentation content here...
```

## Frontmatter Options

Enhance your documentation with these frontmatter fields:

| Field | Description | Example |
|-------|-------------|---------|
| `title` | Document title | `title: Getting Started` |
| `order` | Sort order (lower = first) | `order: 1` |
| `category` | Manual category | `category: Tutorials` |
| `video` | Video URL (any format) | `video: https://youtube.com/watch?v=abc123` |

## Features

### Search

Use the search bar to quickly find documentation across all sources. Search looks through:
- Document titles
- Full content
- Filenames

Results are highlighted and ranked by relevance.

### Video Support

Attach videos to any documentation page by adding a `video` field in the frontmatter. Just paste any video URL and it will automatically be converted to the proper embed format. Supported platforms:
- **YouTube** - watch URLs, shorts, youtu.be links
- **Loom** - share URLs
- **Vimeo** - standard video URLs
- Any embeddable video platform

### Modern Interface

The interface follows WordPress Site Editor design conventions:
- Sleek black sidebar
- Clean typography
- Responsive design
- Smooth animations

## Best Practices

1. **Use descriptive titles** - Clear titles help AI organize better
2. **Add frontmatter** - Even minimal metadata improves organization
3. **Include videos** - Visual documentation is powerful
4. **Keep it updated** - The cache auto-updates when files change
5. **Use markdown features** - Tables, code blocks, images all supported

## Need Help?

Check out the other documentation sections for more detailed information about specific features and configurations.
