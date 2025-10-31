---
title: Video Support Examples
order: 4
category: Features
video: https://www.youtube.com/watch?v=dQw4w9WgXcQ
---

# Video Support Examples

The Unified Docs plugin supports embedding videos from multiple platforms. Just add a `video:` field to your frontmatter with any of these URL formats:

## Supported Platforms

### YouTube

You can use any of these YouTube URL formats:

- **Watch URL**: `https://www.youtube.com/watch?v=VIDEO_ID`
- **Short URL**: `https://youtu.be/VIDEO_ID`
- **Shorts URL**: `https://www.youtube.com/shorts/VIDEO_ID`
- **Embed URL**: `https://www.youtube.com/embed/VIDEO_ID` (already in correct format)

All of these will automatically be converted to the proper embed format.

### Loom

For Loom videos, you can use:

- **Share URL**: `https://www.loom.com/share/VIDEO_ID`
- **Embed URL**: `https://www.loom.com/embed/VIDEO_ID` (already in correct format)

### Vimeo

For Vimeo videos:

- **Standard URL**: `https://vimeo.com/VIDEO_ID`
- **Player URL**: `https://player.vimeo.com/video/VIDEO_ID` (already in correct format)

## Example Frontmatter

```markdown
---
title: My Documentation
video: https://www.loom.com/share/abc123def456
---

# My Documentation

Your content here...
```

The video will automatically appear at the top of the documentation page in a responsive 16:9 container.

## Notes

- Videos appear above the document content
- The embed is fully responsive
- If a URL isn't recognized, it will be used as-is
- Leave the `video:` field empty or omit it entirely if you don't need a video
