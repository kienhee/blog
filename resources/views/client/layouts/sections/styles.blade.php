<!-- Favicon -->
<link rel="icon" type="image/png" href="{{ asset_shared_url('images/favicon.png') }}" />

<!-- Preload Critical Fonts -->
<link rel="preload" href="{{ asset_shared_url('font/Inter/Inter-VariableFont_opsz,wght.ttf') }}" as="font" type="font/ttf" crossorigin />

<!-- Core CSS - Render Blocking (Critical) -->
<link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/css/rtl/core.css') }}"
    class="template-customizer-core-css" />
<link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/css/rtl/theme-default.css') }}"
    class="template-customizer-theme-css" />
<link rel="stylesheet" href="{{ asset_admin_url('assets/css/demo.css') }}" />

<!-- Fonts - Critical for icons -->
<link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/fonts/boxicons.css') }}" />
<link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/fonts/fontawesome.css') }}" />

<!-- Inter Font - Load after core CSS to override Public Sans -->
<link rel="stylesheet" href="{{ asset_shared_url('css/inter-font.css') }}" />

<!-- Vendors CSS - Non-critical (defer) -->
<link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" media="print" onload="this.media='all'" />
<noscript><link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" /></noscript>

<!-- Fancybox CSS - Defer loading -->
<link rel="preload" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" as="style" onload="this.onload=null;this.rel='stylesheet'" />
<noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" /></noscript>

<!-- Client CSS (compiled from SCSS) -->
@vite('resources/scss/client/client.scss')

<!-- Helpers - Move to footer to avoid blocking render -->
@stack('styles')
