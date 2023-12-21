@extends('layouts.admin.index')
@section('title', 'Đăng nhập')
@section('content')
    <!-- Login -->
    <div class="card">
        <div class="card-body">
            <!-- Logo -->
            <div class="app-brand justify-content-center">
                <a href="index.html" class="app-brand-link gap-2">

                    <span class="app-brand-text demo text-body fw-bolder" style="text-transform: uppercase">CMS</span>
                </a>
            </div>
            <!-- /Logo -->
            <h4 class="mb-2">Chào mừng bạn đã trở lại! 👋</h4>
            <p class="mb-4">Vui lòng đăng nhập vào tài khoản của bạn và bắt đầu với công việc</p>

            <form id="formAuthentication" class="mb-3" action="{{ route('auth.login') }}" method="POST">
                @csrf()
                <div class="mb-3">
                    <label for="email" class="form-label">Email </label>
                    <input type="text" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="Enter your email "
                        value="{{ old('email') }}" autofocus />
                    @error('email')
                        <p class="text-danger my-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-3 form-password-toggle">
                    <div class="d-flex justify-content-between">
                        <label class="form-label" for="password">Password</label>
                        
                    </div>
                    <div class="input-group input-group-merge">
                        <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password"
                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                            aria-describedby="password" />
                        <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                    </div>
                    @error('password')
                        <p class="text-danger my-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember"name="remember" />
                        <label class="form-check-label" for="remember"> Ghi nhớ đăng nhập</label>
                    </div>
                </div>
                <div class="mb-3">
                    <button class="btn btn-primary d-grid w-100" type="submit">Đăng nhập</button>
                </div>
            </form>

        </div>
    </div>
    <!-- /Login -->
@endsection
