@extends('client.layouts.master')

@section('title', '429 - Quá nhiều yêu cầu')

@section('content')
    <section class="error-page error-429">
        <div class="container">
            <div class="error-content">
                <div class="error-illustration">
                    <svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg">
                        <!-- Background circle -->
                        <circle cx="200" cy="150" r="120" class="error-illustration-bg" opacity="0.1"/>
                        <!-- Speed limit illustration -->
                        <g transform="translate(200, 150)">
                            <!-- Circle border -->
                            <circle cx="0" cy="0" r="45" class="error-illustration-stroke" stroke-width="6" fill="none"/>
                            <!-- Speed limit text -->
                            <text x="0" y="10" font-family="Arial, sans-serif" font-size="32" font-weight="bold" class="error-illustration-fill" text-anchor="middle">!</text>
                            <!-- Warning lines -->
                            <line x1="-30" y1="-30" x2="-20" y2="-40" class="error-illustration-stroke" stroke-width="3" stroke-linecap="round"/>
                            <line x1="30" y1="-30" x2="20" y2="-40" class="error-illustration-stroke" stroke-width="3" stroke-linecap="round"/>
                        </g>
                        <!-- 429 text decoration -->
                        <text x="200" y="200" font-family="Arial, sans-serif" font-size="24" font-weight="bold" class="error-illustration-bg" opacity="0.3" text-anchor="middle">429</text>
                    </svg>
                </div>
                <div class="error-code">429</div>
                <h1 class="error-title">Quá nhiều yêu cầu</h1>
                <p class="error-description">
                    Bạn đã gửi quá nhiều yêu cầu trong thời gian ngắn. 
                    Vui lòng đợi một chút trước khi thử lại.
                </p>
                <div class="error-actions">
                    <a href="{{ route('client.home') }}" class="btn btn-primary btn-lg">
                        <i class="bx bx-home me-2"></i>
                        Về trang chủ
                    </a>
                    <a href="javascript:location.reload()" class="btn btn-outline-secondary btn-lg">
                        <i class="bx bx-refresh me-2"></i>
                        Thử lại
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection

