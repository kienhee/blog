
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="{{ asset_admin_url('assets/img/favicon/favicon.ico') }}" />

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
    rel="stylesheet" />

  <!-- Icons -->
  <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/fonts/boxicons.css') }}" />
  <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/fonts/fontawesome.css') }}" />
  <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/fonts/flag-icons.css') }}" />

  <!-- Core CSS -->
  <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/css/rtl/core.css') }}" class="template-customizer-core-css" />
  <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/css/rtl/theme-default.css') }}" class="template-customizer-theme-css" />
  <link rel="stylesheet" href="{{ asset_admin_url('assets/css/demo.css') }}" />

  <!-- Vendors CSS -->
  <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
  <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/typeahead-js/typeahead.css') }}" />

  <!-- Helpers -->
  <script src="{{ asset_admin_url('assets/vendor/js/helpers.js') }}"></script>
  <script src="{{ asset_admin_url('assets/vendor/js/template-customizer.js') }}"></script>
  <script src="{{ asset_admin_url('assets/js/config.js') }}"></script>
  @stack('styles')
