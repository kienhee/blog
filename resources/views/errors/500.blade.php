@extends('client.layouts.master')

@section('title', '500 - Lỗi máy chủ')

@section('content')
    <section class="error-page error-500">
        <div class="container">
            <div class="error-content">
                <div class="error-illustration">
                    <svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg">
                        <!-- Background circle -->
                        <circle cx="200" cy="150" r="120" class="error-illustration-bg" opacity="0.1"/>
                        <!-- Server illustration -->
                        <g transform="translate(200, 150)">
                            <!-- Server body -->
                            <rect x="-50" y="-40" width="100" height="80" rx="5" class="error-illustration-bg" opacity="0.3"/>
                            <rect x="-45" y="-35" width="90" height="70" rx="3" class="error-illustration-fill"/>
                            <!-- Server lines -->
                            <line x1="-40" y1="-20" x2="40" y2="-20" stroke="#fff" stroke-width="2" opacity="0.5"/>
                            <line x1="-40" y1="0" x2="40" y2="0" stroke="#fff" stroke-width="2" opacity="0.5"/>
                            <line x1="-40" y1="20" x2="40" y2="20" stroke="#fff" stroke-width="2" opacity="0.5"/>
                            <!-- Warning icon -->
                            <path d="M -15 -50 L 0 -70 L 15 -50 Z" class="error-illustration-fill"/>
                            <circle cx="0" cy="-55" r="3" fill="#fff"/>
                        </g>
                        <!-- Error waves -->
                        <path d="M 50 250 Q 100 230, 150 250 T 250 250 T 350 250" class="error-illustration-stroke" stroke-width="2" fill="none" opacity="0.3"/>
                        <path d="M 50 270 Q 100 250, 150 270 T 250 270 T 350 270" class="error-illustration-stroke" stroke-width="2" fill="none" opacity="0.2"/>
                    </svg>
                </div>
                <div class="error-code">500</div>
                <h1 class="error-title">Lỗi máy chủ</h1>
                <p class="error-description">
                    Xin lỗi, đã xảy ra lỗi trong quá trình xử lý yêu cầu của bạn. 
                    Chúng tôi đã được thông báo về sự cố này và đang khắc phục. 
                    Vui lòng thử lại sau hoặc quay về trang chủ.
                </p>
                <div class="error-actions">
                    <a href="{{ route('client.home') }}" class="btn btn-primary btn-lg">
                        <i class="bx bx-home me-2"></i>
                        Về trang chủ
                    </a>
                    <a href="javascript:location.reload()" class="btn btn-outline-secondary btn-lg">
                        <i class="bx bx-refresh me-2"></i>
                        Tải lại trang
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection

