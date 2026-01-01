@extends('client.layouts.master')
@section('title', 'Đăng nhập')

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
                        <h4 class="mb-2 text-center">Chào mừng trở lại! 👋</h4>
                        <p class="mb-4 text-center">Vui lòng đăng nhập vào tài khoản của bạn</p>

                        @if (session('status'))
                            <div class="alert alert-success alert-dismissible" role="alert">
                                {{ session('status') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0 small">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <!-- Email/Password Login Form -->
                        <form action="{{ route('client.auth.loginHandle') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="form-label">Email / Số điện thoại</label>
                                <input type="text" class="form-control" id="email" name="email" 
                                    placeholder="Nhập email hoặc số điện thoại" value="{{ old('email') }}" required autofocus>
                            </div>
                            <div class="mb-3 form-password-toggle">
                                <div class="d-flex justify-content-between">
                                    <label class="form-label" for="password">Mật khẩu</label>
                                    <a href="{{ route('client.auth.forgot-password') }}">
                                        <small>Quên mật khẩu?</small>
                                    </a>
                                </div>
                                <div class="input-group input-group-merge">
                                    <input type="password" id="password" class="form-control" name="password"
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                        aria-describedby="password" required>
                                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary d-grid w-100">Đăng nhập</button>
                        </form>

                        {{-- TODO: Phát triển sau --}}
                        {{-- <div class="divider my-4">
                            <div class="divider-text">hoặc</div>
                        </div>

                        <!-- Social Login -->
                        <div class="d-grid gap-3 mb-4">
                            <a href="{{ route('auth.login') }}?provider=google" class="btn btn-outline-secondary">
                                <svg width="20" height="20" viewBox="0 0 24 24" class="me-2">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                                Đăng nhập với Google
                            </a>
                            <a href="{{ route('auth.login') }}?provider=facebook" class="btn btn-outline-primary">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="#1877F2" class="me-2">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                                Đăng nhập với Facebook
                            </a>
                            <a href="{{ route('auth.login') }}?provider=github" class="btn btn-outline-dark">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="me-2">
                                    <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/>
                                </svg>
                                Đăng nhập với Github
                            </a>
                        </div> --}}

                        <p class="text-center mt-4">
                            <span>Bạn chưa có tài khoản?</span>
                            <a href="{{ route('client.auth.register') }}">
                                <span>Đăng ký</span>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

