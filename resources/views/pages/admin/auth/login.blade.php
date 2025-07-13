@extends('layouts.admin.auth')
@section('content')
    <main class="login-page bg-body-secondary">
        <div class="login-box">
            <div class="login-logo">
                <a href="#"><b>Admin</b>LTE</a>
            </div>
            <div class="card">
                <div class="card-body login-card-body">
                    <p class="login-box-msg">Đăng nhập để bắt đầu phiên của bạn</p>
                    <form action="{{ route('auth.login.post') }}" class="auth_form" method="POST" autocomplete="off">
                        @csrf
                        <div class="input-group mb-4">
                            <input type="email" name="email" class="form-control"
                                placeholder="Email" value="{{ old('email') }}" required autofocus />
                            <div class="input-group-text"><span class="bi bi-envelope"></span></div>
                            @error('email')
                                <span class="invalid-feedback mt-2" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="input-group mb-4">
                            <input type="password" name="password"
                                class="form-control" placeholder="Password"
                                required />
                            <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
                            @error('password')
                                <span class="invalid-feedback mt-2" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-12 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="flexCheckDefault"
                                        {{ old('remember') ? 'checked' : '' }} />
                                    <label class="form-check-label" for="flexCheckDefault"> Nhớ tài khoản </label>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <p class="mb-1"><a href="#">Quên mật khẩu</a></p>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </main>
@endsection
