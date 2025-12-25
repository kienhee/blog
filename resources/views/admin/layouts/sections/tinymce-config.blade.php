<link rel="stylesheet" href="{{ asset_shared_url('vendor/highlight/styles/atom-one-dark.min.css') }}" />
<script src="{{ asset_shared_url('vendor/highlight/highlight.min.js') }}"></script>
<script src="{{ asset_shared_url('vendor/tinymce/tinymce.min.js') }}"></script>
<script>
    // Đồng bộ CSS TinyMCE với trang show (article-content.css)
    window.TINYMCE_EDITOR_CONTENT_CSS = '{{ asset_shared_url('css/article-content.css') }}';
    window.HIGHLIGHT_JS_CSS = '{{ asset_shared_url('vendor/highlight/styles/atom-one-dark.min.css') }}';
</script>
@vite([
    'resources/js/admin/common/editor/tinymce-hljs-plugin.js',
    'resources/js/admin/common/editor/tinymce-inline-code-plugin.js',
    'resources/js/admin/common/editor/tinymce-config.js',
])
