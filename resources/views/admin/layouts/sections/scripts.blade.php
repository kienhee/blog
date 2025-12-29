<script src="{{ asset_admin_url('assets/vendor/libs/jquery/jquery.js') }}"></script>
<script src="{{ asset_admin_url('assets/vendor/libs/popper/popper.js') }}"></script>
<script src="{{ asset_admin_url('assets/vendor/js/bootstrap.js') }}"></script>
<script src="{{ asset_admin_url('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
<script src="{{ asset_admin_url('assets/vendor/libs/hammer/hammer.js') }}"></script>
<script src="{{ asset_admin_url('assets/vendor/libs/i18n/i18n.js') }}"></script>
<script src="{{ asset_admin_url('assets/vendor/libs/typeahead-js/typeahead.js') }}"></script>
<script src="{{ asset_admin_url('assets/vendor/js/menu.js') }}"></script>
<script src="{{ asset_admin_url('assets/js/main.js') }}"></script>
@vite(['resources/js/admin/common/ui/toastr-config.js', 'resources/js/admin/common/ui/badge-count.js'])
@stack('scripts')
<script>
    // Khởi tạo badge counts cho menu
    $(document).ready(function() {
        @if(auth()->check() && auth()->user()->can('comment.read'))
            // Update comment pending count
            if (typeof updateBadgeCount !== 'undefined' && document.getElementById('admin_comments_pending')) {
                updateBadgeCount('admin_comments_pending', '{{ route('admin.comments.countPending') }}');
            }
        @endif
    });
</script>
