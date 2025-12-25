@extends('admin.layouts.auth')

@section('title', 'Quên mật khẩu')
@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/@form-validation/form-validation.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/css/pages/page-auth.css') }}" />
@endpush
@section('content')
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-4">
                <!-- Forgot Password -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center">
                            <a href="{{ route('auth.login') }}" class="app-brand-link gap-2">
                                @include('admin.components.logo')
                            </a>
                        </div>
                        <!-- /Logo -->
                        <h4 class="mb-2">Quên mật khẩu? 🔒</h4>
                        <p class="mb-4">Nhập email của bạn và chúng tôi sẽ gửi cho bạn hướng dẫn để đặt lại mật khẩu của
                            bạn
                        </p>

                        @if (session('status'))
                            <div class="alert alert-success alert-dismissible" role="alert">
                                {{ session('status') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif

                        <form id="formAuthentication" class="mb-3" action="{{ route('auth.forgot-password.send') }}"
                            method="POST">
                            @csrf
                            @include('admin.modules.auth.showErrors')
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="Nhập email của bạn" value="{{ old('email') }}" autofocus />
                            </div>
                            <button type="submit" id="btnSubmit" class="btn btn-primary d-grid w-100">
                                <span class="btn-text">Gửi thông tin</span>
                                <span class="btn-loading d-none">
                                    <span class="spinner-border spinner-border-sm me-2" role="status"
                                        aria-hidden="true"></span>
                                    Đang gửi...
                                </span>
                            </button>
                        </form>
                        <div class="text-center">
                            <a href="{{ route('auth.login') }}" class="d-flex align-items-center justify-content-center">
                                <i class="bx bx-chevron-left scaleX-n1-rtl bx-sm"></i>
                                Quay lại đăng nhập
                            </a>
                        </div>
                    </div>
                </div>
                <!-- /Forgot Password -->
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/popular.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>
    @vite(['resources/js/admin/pages/auth/index.js'])
@endpush
