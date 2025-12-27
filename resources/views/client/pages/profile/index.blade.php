@extends('client.layouts.master')
@section('title', 'Thông tin cá nhân')

@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/animate-css/animate.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/css/pages/page-profile.css') }}" />
@endpush

@section('content')
    @php
        ob_start();
    @endphp
    <div class="row">
        @include('client.pages.profile.tabs.profile')
    </div>
    @php
        $profileContent = ob_get_clean();
    @endphp
    @include('client.pages.profile.partials.header', ['profileContent' => $profileContent])
@endsection

@push('scripts')
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/popular.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.js') }}"></script>
    <script>
        // Truyền biến để JS có thể sử dụng
        window.hasProfileErrors = @json(
            $errors->has('full_name') ||
                $errors->has('email') ||
                $errors->has('phone') ||
                $errors->has('gender') ||
                $errors->has('birthday') ||
                $errors->has('description') ||
                $errors->has('avatar') ||
                $errors->has('twitter_url') ||
                $errors->has('facebook_url') ||
                $errors->has('instagram_url') ||
                $errors->has('linkedin_url'));
    </script>
    @vite(['resources/js/admin/common/uploads/upload-image-alone.js', 'resources/js/admin/common/uploads/upload-avatar.js', 'resources/js/client/pages/profile/index.js'])
    <script>
        // Handle success message from sessionStorage
        document.addEventListener('DOMContentLoaded', function() {
            const successMessage = sessionStorage.getItem('success_message');
            if (successMessage) {
                const notice = document.getElementById('success-notice');
                const noticeMessage = document.getElementById('success-notice-message');
                if (notice && noticeMessage) {
                    noticeMessage.textContent = successMessage;
                    notice.classList.remove('d-none');
                    // Auto-hide after 5 seconds
                    setTimeout(function() {
                        notice.classList.add('d-none');
                    }, 5000);
                }
                // Clear sessionStorage
                sessionStorage.removeItem('success_message');
            }
        });
    </script>
@endpush
