# Unified Article Content CSS System

## Overview

This document describes the unified CSS system that ensures **100% visual consistency** between the TinyMCE editor preview (admin panel) and the client-side article view.

## Architecture

### 1. Shared CSS File
**Location:** `public/resources/shared/css/article-content.css`

This single CSS file contains all styles for article content elements:
- Headings (h1-h6)
- Paragraphs
- Lists (ul, ol, nested lists)
- Links
- Blockquotes
- Inline code and code blocks
- Tables
- Images and figures
- Text formatting (bold, italic, underline, strikethrough)
- Text alignment classes
- Horizontal rules
- Accordions
- Media (video/audio)

All styles are scoped to the `.article-content` wrapper class.

### 2. TinyMCE Configuration
**Location:** `resources/js/admin/common/tinymce-config.js`

**Key Configurations:**
- `content_css`: Loads both `article-content.css` and Highlight.js theme CSS
- `body_class: 'article-content'`: Matches client-side wrapper
- `codesample` plugin: Configured with multiple languages
- `valid_elements: '*[*]'`: Preserves HTML classes and attributes for Highlight.js
- Highlight.js initialization: Automatically highlights code blocks on content changes

### 3. Client-Side Article View
**Location:** `resources/views/public/post/show.blade.php`

**Requirements:**
- Wraps content in `<div class="article-content">`
- Loads the same `article-content.css`
- Loads the same Highlight.js theme CSS
- Initializes Highlight.js on page load

## Critical Consistency Points

✅ **Same CSS file** for both editor and client  
✅ **Same Highlight.js theme** (atom-one-dark.min.css)  
✅ **Same wrapper class** (`.article-content`)  
✅ **Same font families, sizes, spacing, colors**  
✅ **Identical code block styling** (background, padding, borders)

## Usage

### In TinyMCE Editor (Admin Panel)

The editor automatically:
1. Loads `article-content.css` via `content_css` option
2. Applies `.article-content` class to the editor body
3. Loads Highlight.js theme CSS
4. Initializes Highlight.js on code blocks when content changes

### In Client-Side View

```blade
<!-- Load CSS -->
<link rel="stylesheet" href="{{ asset_shared_url('css/article-content.css') }}">
<link rel="stylesheet" href="{{ asset_shared_url('vendor/highlight/styles/atom-one-dark.min.css') }}">

<!-- Wrap content -->
<div class="article-content">
    {!! $post->content !!}
</div>

<!-- Load and initialize Highlight.js -->
<script src="{{ asset_shared_url('vendor/highlight/highlight.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof hljs !== 'undefined') {
            document.querySelectorAll('.article-content pre code').forEach(function(block) {
                hljs.highlightElement(block);
            });
        }
    });
</script>
```

## Highlight.js Configuration

- **Version:** 11.9.0 (or latest)
- **Theme:** atom-one-dark
- **CDN Path:** `/resources/shared/vendor/highlight/`

## Supported Code Languages

- HTML/XML (markup)
- JavaScript
- CSS
- PHP
- Ruby
- Python
- Java
- C
- C#
- C++
- Go
- SQL
- JSON
- Bash/Shell
- YAML
- Markdown

## Responsive Design

The CSS includes responsive breakpoints:
- Mobile: `< 768px` - Adjusted font sizes and spacing
- Tablet/Desktop: `>= 768px` - Full styling
- Print: Print-optimized styles

## Testing Checklist

When testing the unified system:

1. ✅ Create content in TinyMCE editor
2. ✅ Verify preview matches client-side view exactly
3. ✅ Test code blocks with syntax highlighting
4. ✅ Test all content elements (headings, lists, tables, etc.)
5. ✅ Test responsive design on mobile
6. ✅ Verify Highlight.js works in both editor and client view

## Troubleshooting

### Code blocks not highlighting in editor
- Check that Highlight.js is loaded before TinyMCE initialization
- Verify `initHighlightJS` function is being called
- Check browser console for errors

### Styles not matching
- Ensure both editor and client use the same CSS file path
- Verify `.article-content` wrapper class is present
- Check that Highlight.js theme CSS is loaded in both places

### Classes being stripped
- Verify `valid_elements: '*[*]'` is set in TinyMCE config
- Check that `extended_valid_elements` includes necessary elements

## Files Modified/Created

1. ✅ `public/resources/shared/css/article-content.css` - Created comprehensive CSS
2. ✅ `resources/js/admin/common/tinymce-config.js` - Updated with unified CSS and Highlight.js
3. ✅ `resources/views/public/post/show.blade.php` - Created example client-side view

## Notes

- The CSS file is intentionally comprehensive to cover all possible content elements
- Highlight.js initialization happens automatically in both editor and client view
- The system is designed to be theme-agnostic (works with light/dark admin themes)
- All styles are mobile-responsive and print-friendly

