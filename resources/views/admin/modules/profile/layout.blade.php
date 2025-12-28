@extends('admin.layouts.master')
@php
    $user = auth()->user()->load('roles');
@endphp
@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/animate-css/animate.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/css/pages/page-profile.css') }}" />
@endpush
@section('content')
    <!-- Header -->
    @include('admin.modules.profile.partials.header')
    <!--/ Header -->
    @include('admin.components.showMessage')
    <!-- Profile Content -->
    <div class="row">
        <div class="col-xl-4 col-lg-5">
            @include('admin.modules.profile.partials.sidebar')
        </div>
        <div class="col-xl-8 col-lg-7">
            <!-- Navbar pills -->
            @include('admin.modules.profile.partials.navbar')
            <!--/ Navbar pills -->
            @yield('profile-content')
        </div>
    </div>
    <!--/ Profile Content -->
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
    @vite(['resources/js/admin/common/uploads/upload-image-alone.js', 'resources/js/admin/common/uploads/upload-avatar.js', 'resources/js/admin/pages/profile/index.js'])
@endpush
