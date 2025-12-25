@extends('admin.layouts.auth')

@section('title', 'Đăng nhập')
@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/@form-validation/form-validation.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/css/pages/page-auth.css') }}" />
@endpush
@section('content')
<div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
      <div class="authentication-inner">
        <div class="card">
          <div class="card-body">
            <!-- Logo -->
            <div class="app-brand justify-content-center">
              <a href="{{ route('auth.login') }}" class="app-brand-link gap-2">
                @include("admin.components.logo")
              </a>
            </div>
            <!-- /Logo -->
            @if (session('status'))
                <div class="alert alert-success alert-dismissible" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            <form id="formAuthentication" class="mb-3" action="{{ route('auth.loginHandle') }}" method="POST">
              @csrf
              @include('admin.modules.auth.showErrors')
              <div class="mb-3">
                <label for="login" class="form-label">Email</label>
                <input type="text" class="form-control" id="login" name="login" value="{{ old('login') }}"
                  placeholder="Nhập email" autofocus />
              </div>
              <div class="mb-3 form-password-toggle">
                <div class="d-flex justify-content-between">
                  <label class="form-label" for="password">Mật khẩu</label>
                  <a tabindex="-1" href="{{ route('auth.forgot-password') }}">
                    <small>Quên mật khẩu?</small>
                  </a>
                </div>
                <div class="input-group input-group-merge">
                  <input type="password" id="password" class="form-control" name="password"
                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                    aria-describedby="password" />
                  <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                </div>
              </div>
              <div class="mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="remember" name="remember" />
                  <label class="form-check-label" for="remember"> Nhớ thông tin </label>
                </div>
              </div>
              <div class="mb-3">
                <button type="submit" id="btnSubmit" class="btn btn-primary d-grid w-100">
                  <span class="btn-text">Đăng nhập</span>
                  <span class="btn-loading d-none">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Đang đăng nhập...
                  </span>
                </button>
              </div>
            </form>

            </div>
          </div>
        </div>
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
