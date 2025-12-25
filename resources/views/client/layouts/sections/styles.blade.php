<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="{{ asset_admin_url('assets/img/favicon/favicon.ico') }}" />

<!-- Fonts -->
<link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/fonts/boxicons.css') }}" />
<!-- Core CSS -->
<link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/css/rtl/core.css') }}"
    class="template-customizer-core-css" />
<link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/css/rtl/theme-default.css') }}"
    class="template-customizer-theme-css" />
<link rel="stylesheet" href="{{ asset_admin_url('assets/css/demo.css') }}" />
<!-- Inter Font - Load after core CSS to override Public Sans -->
<link rel="stylesheet" href="{{ asset_shared_url('css/inter-font.css') }}" />
<!-- Vendors CSS -->
<link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/nouislider/nouislider.css') }}" />
<link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/swiper/swiper.css') }}" />
<!-- Client CSS (compiled from SCSS) -->
@vite('resources/scss/client/client.scss')
<!-- Helpers -->
<script src="{{ asset_admin_url('assets/vendor/js/helpers.js') }}"></script>
<script src="{{ asset_admin_url('assets/vendor/js/template-customizer.js') }}"></script>
<script src="{{ asset_admin_url('assets/js/front-config.js') }}"></script>
@stack('styles')
