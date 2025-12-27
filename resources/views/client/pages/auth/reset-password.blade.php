@extends('client.layouts.master')
@section('title', 'Đặt lại mật khẩu')

@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/css/pages/page-auth.css') }}" />
@endpush

@section('content')
<section class="section-py">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="card">
                    <div class="card-body p-4 p-sm-5">
                        <h4 class="mb-2 text-center">Đặt lại mật khẩu 🔑</h4>
                        <p class="mb-4 text-center">Vui lòng nhập mật khẩu mới của bạn</p>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0 small">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('client.auth.reset-password.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token ?? old('token') }}">
                            <input type="hidden" name="email" value="{{ $email ?? old('email') }}">

                            <div class="mb-3 form-password-toggle">
                                <label class="form-label" for="password">Mật khẩu mới</label>
                                <div class="input-group input-group-merge">
                                    <input type="password" id="password" class="form-control" name="password"
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                        aria-describedby="password" required autofocus>
                                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                </div>
                            </div>
                            <div class="mb-3 form-password-toggle">
                                <label class="form-label" for="password_confirmation">Xác nhận mật khẩu mới</label>
                                <div class="input-group input-group-merge">
                                    <input type="password" id="password_confirmation" class="form-control" name="password_confirmation"
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                        aria-describedby="password_confirmation" required>
                                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary d-grid w-100">Đặt lại mật khẩu</button>
                        </form>

                        <div class="text-center mt-4">
                            <a href="{{ route('client.auth.login') }}" class="d-flex align-items-center justify-content-center">
                                <i class="bx bx-chevron-left scaleX-n1-rtl me-1"></i>
                                <span>Quay lại đăng nhập</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

