# TinyMCE language packs

`post-editor.js` sets `language` / `language_url` from the page language (`window.khLang`):

- `en` — TinyMCE's built-in strings, no file needed.
- `vi` — needs `vi.js` in this folder.

The Vietnamese pack is **not vendored here**: it is a third-party translation file of several
hundred strings and is not something to hand-write. Download the official pack and drop it in:

1. https://www.tiny.cloud/get-tiny/language-packages/ → Vietnamese → `vi.js`
   (or `npm pack @tinymce/tinymce-languages` and copy `langs/vi.js`)
2. Save it as `scripts/lib/tinymce/langs/vi.js` — same licence as the editor (MIT/GPL).

Until then the editor chrome stays in English while the rest of the admin is Vietnamese;
TinyMCE simply logs that the pack is missing and falls back to English.
