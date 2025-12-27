@extends('admin.layouts.master')
@section('title', 'Thông tin cá nhân')

@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/animate-css/animate.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/css/pages/page-profile.css') }}" />
@endpush

@section('content')
    <!-- Header -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="user-profile-header-banner">
                    <img src="{{ asset_admin_url('assets/img/pages/profile-banner.png') }}" alt="Banner image"
                        class="rounded-top" />
                </div>
                <div class="user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-4">
                    @php
                        $avatar = $user->avatar ?: asset_shared_url('images/default.png');
                        $displayName = $user->full_name ?: $user->email;
                    @endphp
                    <div class="flex-shrink-0 mt-n2 mx-sm-0 mx-auto">
                        <img id="profileAvatarImg" src="{{ $avatar }}" alt="user image"
                            class="d-block h-auto ms-0 ms-sm-4 rounded user-profile-img" />
                    </div>
                    <div class="flex-grow-1 mt-3 mt-sm-5">
                        <div
                            class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-4 flex-md-row flex-column gap-4">
                            <div class="user-profile-info text-start">
                                <h4 class="mb-1" id="profileDisplayName">{{ $displayName }}</h4>
                                <ul
                                    class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-2">
                                    <li class="list-inline-item fw-medium"><i class="bx bx-envelope"></i> <span
                                            id="profileEmail">{{ $user->email }}</span></li>
                                    @if ($user->phone)
                                        <li class="list-inline-item fw-medium"><i class="bx bx-phone"></i> <span
                                                id="profilePhone">{{ $user->phone }}</span></li>
                                    @endif
                                    @if ($user->roles->count() > 0)
                                        <li class="list-inline-item fw-medium"><i class="bx bx-shield"></i>
                                            @foreach ($user->roles as $role)
                                                <span class="badge bg-label-primary">{{ $role->name }}</span>
                                            @endforeach
                                        </li>
                                    @endif
                                </ul>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--/ Header -->
    @include('admin.components.showMessage')
    <!-- Navbar pills -->
    <div class="row">
        <div class="col-md-12">
            <ul class="nav nav-pills flex-column flex-sm-row" role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                        data-bs-target="#profile-tab" aria-controls="profile-tab" aria-selected="true">
                        <i class="bx bx-user me-1"></i> Profile
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#password-tab" aria-controls="password-tab" aria-selected="false">
                        <i class="bx bx-lock-alt me-1"></i> Đổi mật khẩu
                    </button>
                </li>
            </ul>
        </div>
    </div>
    <!--/ Navbar pills -->

    <!-- Tab Content -->
    <div class="tab-content px-0">
        <!-- Profile Tab -->
        @include('admin.modules.profile.tabs.profile')
        <!--/ Profile Tab -->

        <!-- Change Password Tab -->
        @include('admin.modules.profile.tabs.change-password')
        <!--/ Change Password Tab -->
    </div>
    <!--/ Tab Content -->
@endsection

@push('scripts')
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/popular.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.js') }}"></script>
    <script>
        // Truyền biến để JS có thể sử dụng
        window.hasPasswordErrors = @json($errors->has('currentPassword') || $errors->has('newPassword') || $errors->has('newPassword_confirmation'));
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
