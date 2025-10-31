---
title: Using Contextual Help
order: 3
category: Features
screens: [post, page, dashboard]
---

# Using Contextual Help

This document demonstrates the contextual help feature. It will appear in the help panel on the following admin screens:

- Posts (edit and list)
- Pages (edit and list)
- Dashboard

## How It Works

When you add a `screens` field to your markdown frontmatter, the documentation automatically appears in the WordPress help panel for those screens.

### Supported Screen IDs

Common screen IDs you can use:

- `dashboard` - Main dashboard
- `post` - Post editor and list
- `page` - Page editor and list
- `edit-post` - Post list specifically
- `edit-page` - Page list specifically
- `plugins` - Plugins page
- `themes` - Themes page
- `users` - Users page
- `options-general` - General settings
- `options-writing` - Writing settings
- `options-reading` - Reading settings

### Frontmatter Format

You can specify screens in two ways:

**Inline Array:**
```markdown
---
screens: [post, page, dashboard]
---
```

**Multi-line Array:**
```markdown
---
screens:
  - post
  - page
  - dashboard
---
```

## Example Use Cases

### For Theme Developers

Create documentation that appears when users edit pages:

```markdown
---
title: Using Theme Features
screens: [page]
---

# How to Use Custom Page Templates

Your theme includes several custom page templates...
```

### For Plugin Developers

Add help for plugin settings pages:

```markdown
---
title: Configuring Plugin Settings
screens: [settings_page_my-plugin]
---

# Plugin Configuration Guide

Here's how to configure the plugin...
```

## Benefits

1. **Contextual** - Help appears exactly where users need it
2. **Automatic** - No manual WordPress API calls required
3. **Centralized** - All docs in one `/docs` folder
4. **Video Support** - Add `video` field for tutorials
5. **AI Organized** - Docs are still organized in main Docs menu

## Tips

- Use descriptive titles for help tabs
- Keep contextual docs focused and concise
- Link to full documentation for details
- Test on actual admin screens to verify
- Use `order` field to control help tab sequence

Check the Help tab at the top right of this page to see this feature in action!
