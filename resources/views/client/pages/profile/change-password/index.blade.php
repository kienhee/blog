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
                    <form id="formChangePassword" method="POST" action="{{ route('client.profile.changePassword.post') }}">
                      @csrf
                      <div class="row">
                        <div class="mb-3 col-md-6">
                          <label class="form-label" for="currentPassword">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                          <input class="form-control @error('currentPassword') is-invalid @enderror"
                            type="password"
                            name="currentPassword"
                            id="currentPassword"
                            placeholder="Nhập mật khẩu hiện tại"
                           />
                          @error('currentPassword')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                          @enderror
                        </div>
                      </div>
                      <div class="row">
                        <div class="mb-3 col-md-6">
                          <label class="form-label" for="newPassword">Mật khẩu mới <span class="text-danger">*</span></label>
                          <input class="form-control @error('newPassword') is-invalid @enderror"
                            type="password"
                            id="newPassword"
                            name="newPassword"
                            placeholder="Nhập mật khẩu mới"
                           />
                          @error('newPassword')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                          @enderror
                        </div>

                        <div class="mb-3 col-md-6">
                          <label class="form-label" for="newPassword_confirmation">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                          <input class="form-control @error('newPassword_confirmation') is-invalid @enderror"
                            type="password"
                            name="newPassword_confirmation"
                            id="newPassword_confirmation"
                            placeholder="Nhập lại mật khẩu mới"
                           />
                          @error('newPassword_confirmation')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                          @enderror
                        </div>
                        <div class="col-12 mt-1">
                          <button type="submit" class="btn btn-primary me-2" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            Lưu thay đổi
                          </button>
                          <button type="reset" class="btn btn-label-secondary" id="resetBtn">Hủy</button>
                        </div>
                      </div>
                    </form>
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
    @vite(['resources/js/client/pages/profile/change-password.js'])
@endpush

