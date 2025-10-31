---
title: Markdown Formatting Guide
order: 2
category: Writing Documentation
---

# Markdown Formatting Guide

This guide shows you all the markdown features supported in Unified Docs.

## Headings

Use headings to structure your documentation:

```markdown
# Heading 1
## Heading 2
### Heading 3
#### Heading 4
```

## Text Formatting

Make text **bold** with double asterisks or __underscores__.

Make text *italic* with single asterisks or _underscores_.

Combine them for ***bold and italic***.

Use ~~strikethrough~~ with double tildes.

## Lists

### Unordered Lists

- First item
- Second item
  - Nested item
  - Another nested item
- Third item

### Ordered Lists

1. First step
2. Second step
   1. Sub-step A
   2. Sub-step B
3. Third step

## Links and Images

Create [inline links](https://example.com) easily.

Add images with alt text:
```markdown
![Alt text for image](image-url.jpg)
```

## Code

### Inline Code

Use `inline code` with backticks.

### Code Blocks

```php
<?php
function example() {
    return 'Hello, World!';
}
```

```javascript
const greeting = () => {
    console.log('Hello, World!');
};
```

## Blockquotes

> This is a blockquote.
> It can span multiple lines.
>
> And even include multiple paragraphs.

## Tables

| Feature | Supported | Notes |
|---------|-----------|-------|
| Headers | Yes | Auto-styled |
| Alignment | Yes | Left, center, right |
| Nested content | Yes | Most markdown works |

## Horizontal Rules

Use three dashes for a horizontal rule:

---

## Advanced Features

### Task Lists

- [x] Completed task
- [ ] Incomplete task
- [ ] Another task

### Definition Lists

Term
: Definition of the term

Another term
: Definition of another term

## Tips for Great Documentation

1. **Keep it concise** - Users scan, they don't read every word
2. **Use examples** - Code examples are worth 1000 words
3. **Add visuals** - Screenshots and videos enhance understanding
4. **Structure well** - Use headings to make content scannable
5. **Test your code** - Ensure all code examples actually work

## Special Considerations

When writing documentation for Unified Docs:

- Files are automatically discovered from `/docs` or `/documentation` folders
- Frontmatter is optional but recommended
- Video URLs should be embed URLs, not watch URLs
- Images should use relative paths or absolute URLs
- Cache refreshes automatically when files change

## Next Steps

Now that you know the formatting options, start creating comprehensive documentation for your themes and plugins!
