 <!-- Core JS -->
 <!-- build:js assets/vendor/js/core.js -->
 <script src="{{ asset_admin_url('assets/vendor/libs/jquery/jquery.js') }}"></script>
 <script src="{{ asset_admin_url('assets/vendor/libs/popper/popper.js') }}"></script>
 <script src="{{ asset_admin_url('assets/vendor/js/bootstrap.js') }}"></script>

 <!-- endbuild -->

 <!-- Main JS -->
 <script src="{{ asset_admin_url('assets/js/front-main.js') }}"></script>

<!-- Page JS -->
<script src="{{ asset_admin_url('assets/js/front-page-landing.js') }}"></script>
<!-- Toastr JS -->
<script src="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.js') }}"></script>
@vite(['resources/js/admin/common/ui/toastr-config.js'])
@stack('scripts')
