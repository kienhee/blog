@extends('client.layouts.master')
@section('title', 'Quên mật khẩu')

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
                            <h4 class="mb-2 text-center">Quên mật khẩu? 🔒</h4>
                            <p class="mb-4 text-center">
                                Nhập email của bạn và chúng tôi sẽ gửi hướng dẫn để đặt lại mật khẩu
                            </p>

                            @if (session('status'))
                                <div class="alert alert-success" role="alert">
                                    {{ session('status') }}
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

                            <form action="{{ route('client.auth.forgot-password.send') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        placeholder="Nhập email của bạn" value="{{ old('email') }}" required autofocus>
                                </div>
                                <button type="submit" class="btn btn-primary d-grid w-100">Gửi liên kết đặt lại mật
                                    khẩu</button>
                            </form>

                            <div class="text-center mt-4">
                                <a href="{{ route('client.auth.login') }}"
                                    class="d-flex align-items-center justify-content-center">
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
