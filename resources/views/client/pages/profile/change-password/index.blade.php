@extends('client.layouts.master')
@section('title', 'Đổi mật khẩu')

@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/animate-css/animate.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/css/pages/page-profile.css') }}" />
@endpush

@section('content')
    @php
        ob_start();
    @endphp
    <!-- Change Password Content -->
    <div class="row">
        <div class="col-md-12">
            <!-- Change Password -->
            <div class="card mb-4">
                <h5 class="card-header">Đổi mật khẩu</h5>
                <div class="card-body">
                    @include('common.change-password-form', [
                        'formAction' => route('client.profile.changePassword.post'),
                        'formId' => 'formChangePassword'
                    ])
                </div>
            </div>
            <!--/ Change Password -->
        </div>
    </div>
    <!--/ Change Password Content -->
    @php
        $profileContent = ob_get_clean();
    @endphp
    @include('client.pages.profile.partials.header', ['profileContent' => $profileContent])
@endsection

@push('scripts')
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/popular.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.js') }}"></script>
    <script>
        // Truyền biến để JS có thể sử dụng
        window.hasPasswordErrors = @json($errors->has('currentPassword') || $errors->has('newPassword') || $errors->has('newPassword_confirmation'));
    </script>
    @vite(['resources/js/common/change-password.js'])
@endpush
