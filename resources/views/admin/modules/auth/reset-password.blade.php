@extends('admin.layouts.auth')

@section('title', 'Đặt lại mật khẩu')
@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/@form-validation/form-validation.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/css/pages/page-auth.css') }}" />
@endpush
@section('content')
     <div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
      <div class="authentication-inner py-4">
        <!-- Reset Password -->
        <div class="card">
          <div class="card-body">
            <!-- Logo -->
            <div class="app-brand justify-content-center">
              <a href="{{ route('auth.login') }}" class="app-brand-link gap-2">
                 @include("admin.components.logo")
              </a>
            </div>
            <!-- /Logo -->
            <h4 class="mb-2">Đặt lại mật khẩu  🔒</h4>
            <p class="mb-4">Cho <span class="fw-medium">{{ $email ?? '' }}</span></p>

            @if (session('status'))
                <div class="alert alert-success alert-dismissible" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form id="formAuthentication" class="mb-3" action="{{ route('auth.reset-password.update') }}" method="POST">
              @csrf
              <input type="hidden" name="token" value="{{ $token ?? '' }}">
              <input type="hidden" name="email" value="{{ $email ?? '' }}">
              @include('admin.modules.auth.showErrors')
              <div class="mb-3 form-password-toggle">
                <label class="form-label" for="password">Mật khẩu mới</label>
                <div class="input-group input-group-merge">
                  <input type="password" id="password" class="form-control" name="password"
                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                    aria-describedby="password" />
                  <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                </div>
              </div>
              <div class="mb-3 form-password-toggle">
                <label class="form-label" for="password_confirmation">Xác nhận mật khẩu</label>
                <div class="input-group input-group-merge">
                  <input type="password" id="password_confirmation" class="form-control" name="password_confirmation"
                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                    aria-describedby="password" />
                  <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                </div>
              </div>
              <button type="submit" id="btnSubmit" class="btn btn-primary d-grid w-100 mb-3">
                <span class="btn-text">Đặt mật khẩu mới</span>
                <span class="btn-loading d-none">
                  <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                  Đang xử lý...
                </span>
              </button>
              <div class="text-center">
                <a href="{{ route('auth.login') }}">
                  <i class="bx bx-chevron-left scaleX-n1-rtl bx-sm"></i>
                  Quay lại đăng nhập
                </a>
              </div>
            </form>
          </div>
        </div>
        <!-- /Reset Password -->
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
