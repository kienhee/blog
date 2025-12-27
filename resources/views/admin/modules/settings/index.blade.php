@extends('admin.layouts.master')
@section('title', 'Cài đặt Website')

@push('styles')
<link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/select2/select2.css') }}" />
<link rel="stylesheet" href="{{   asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}"/>
<link rel="stylesheet" href="{{   asset_admin_url('assets/vendor/libs/animate-css/animate.css') }}"/>
@endpush

@section('content')
<section>
    <form method="POST" action="{{ route('admin.settings.update') }}" id="settingsForm">
        @csrf
        @include('admin.components.headingPage',
                    [
                        'description' => 'Quản lý và cấu hình các thiết lập cho website',
                        'button' => 'edit',
                        'listLink' => '',
                        'buttonPermission' => 'setting.update',
                    ])

        <div class="card">
            <div class="card-body">
                <!-- Tabs Navigation -->
                <ul class="nav nav-pills mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="pill" data-bs-target="#general" type="button" role="tab">
                            <i class="bx bx-cog me-1"></i> Thông tin chung
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="social-tab" data-bs-toggle="pill" data-bs-target="#social" type="button" role="tab">
                            <i class="bx bx-share-alt me-1"></i> Mạng xã hội
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="map-tab" data-bs-toggle="pill" data-bs-target="#map" type="button" role="tab">
                            <i class="bx bx-map me-1"></i> Bản đồ
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="display-tab" data-bs-toggle="pill" data-bs-target="#display" type="button" role="tab">
                            <i class="bx bx-slider me-1"></i> Hiển thị
                        </button>
                    </li>
                </ul>

                <!-- Tabs Content -->
                <div class="tab-content">
                    <!-- General Tab -->
                    <div class="tab-pane fade show active" id="general" role="tabpanel">
                        @include('admin.modules.settings.cards.general')
                    </div>

                    <!-- Social Tab -->
                    <div class="tab-pane fade" id="social" role="tabpanel">
                        @include('admin.modules.settings.cards.social')
                    </div>

                    <!-- Map Tab -->
                    <div class="tab-pane fade" id="map" role="tabpanel">
                        @include('admin.modules.settings.cards.map')
                    </div>

                    <!-- Display Tab -->
                    <div class="tab-pane fade" id="display" role="tabpanel">
                        @include('admin.modules.settings.cards.display', ['categories' => $categories])
                    </div>
                </div>

                <!-- Save Button -->
                <div class="d-flex justify-content-end mt-4 pt-4 border-top">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-2"></i>Lưu cài đặt
                    </button>
                </div>
            </div>
        </div>
    </form>
</section>
@endsection

@push('scripts')
<script src="{{ asset_admin_url('assets/vendor/libs/select2/select2.js') }}"></script>
<script src="{{   asset_admin_url('assets/vendor/libs/toastr/toastr.js') }}"></script>
@vite([
    'resources/js/admin/common/forms/forms-selects.js',
])
<script>
$(document).ready(function() {
    // Form validation
    $('#settingsForm').on('submit', function(e) {
        const $btn = $(this).find('button[type="submit"]');
        const originalText = $btn.html();

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Đang lưu...');

        // Re-enable after 3 seconds as fallback
        setTimeout(function() {
            $btn.prop('disabled', false).html(originalText);
        }, 3000);
    });
});
</script>
@endpush
