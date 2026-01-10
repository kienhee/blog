 <!-- Helpers - Load first -->
 <script src="{{ asset_admin_url('assets/vendor/js/helpers.js') }}"></script>
 <script src="{{ asset_admin_url('assets/js/front-config.js') }}"></script>

 <!-- Core JS -->
 <!-- build:js assets/vendor/js/core.js -->
 <script src="{{ asset_admin_url('assets/vendor/libs/jquery/jquery.js') }}"></script>
 <script src="{{ asset_admin_url('assets/vendor/libs/popper/popper.js') }}"></script>
 <script src="{{ asset_admin_url('assets/vendor/js/bootstrap.js') }}"></script>

 <!-- endbuild -->

 <!-- Dropdown hover (if needed) -->
 <script src="{{ asset_admin_url('assets/vendor/js/dropdown-hover.js') }}"></script>

<!-- Main JS -->
<script src="{{ asset_admin_url('assets/js/front-main.js') }}"></script>

<!-- Page JS -->
<script src="{{ asset_admin_url('assets/js/front-page-landing.js') }}"></script>
<!-- Toastr JS -->
<script src="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.js') }}"></script>
<!-- Fancybox JS - Load async (non-critical) -->
<script async src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
@vite(['resources/js/admin/common/ui/toastr-config.js'])
@stack('scripts')
