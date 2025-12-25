 <!-- Core JS -->
 <!-- build:js assets/vendor/js/core.js -->
 <script src="{{ asset_admin_url('assets/vendor/libs/popper/popper.js') }}"></script>
 <script src="{{ asset_admin_url('assets/vendor/js/bootstrap.js') }}"></script>

 <!-- endbuild -->

 <!-- Vendors JS -->
 <script src="{{ asset_admin_url('assets/vendor/libs/nouislider/nouislider.js') }}"></script>
 <script src="{{ asset_admin_url('assets/vendor/libs/swiper/swiper.js') }}"></script>

 <!-- Main JS -->
 <script src="{{ asset_admin_url('assets/js/front-main.js') }}"></script>

 <!-- Page JS -->
 <script src="{{ asset_admin_url('assets/js/front-page-landing.js') }}"></script>
 @stack('scripts')
