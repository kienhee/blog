@extends('client.layouts.master')

@section('title', '419 - Phiên làm việc hết hạn')

@section('content')
    <section class="error-page error-419">
        <div class="container">
            <div class="error-content">
                <div class="error-illustration">
                    <svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg">
                        <!-- Background circle -->
                        <circle cx="200" cy="150" r="120" class="error-illustration-bg" opacity="0.1"/>
                        <!-- Clock illustration -->
                        <g transform="translate(200, 150)">
                            <!-- Clock face -->
                            <circle cx="0" cy="0" r="40" class="error-illustration-fill" opacity="0.3"/>
                            <circle cx="0" cy="0" r="35" fill="#fff" opacity="0.2"/>
                            <!-- Clock hands -->
                            <line x1="0" y1="0" x2="0" y2="-20" class="error-illustration-stroke" stroke-width="3" stroke-linecap="round"/>
                            <line x1="0" y1="0" x2="15" y2="0" class="error-illustration-stroke" stroke-width="2" stroke-linecap="round"/>
                            <!-- Clock center -->
                            <circle cx="0" cy="0" r="3" class="error-illustration-fill"/>
                            <!-- Clock numbers -->
                            <text x="0" y="-28" font-family="Arial, sans-serif" font-size="12" font-weight="bold" class="error-illustration-fill" text-anchor="middle">12</text>
                            <text x="28" y="5" font-family="Arial, sans-serif" font-size="12" font-weight="bold" class="error-illustration-fill" text-anchor="middle">3</text>
                        </g>
                        <!-- 419 text decoration -->
                        <text x="200" y="200" font-family="Arial, sans-serif" font-size="24" font-weight="bold" class="error-illustration-bg" opacity="0.3" text-anchor="middle">419</text>
                    </svg>
                </div>
                <div class="error-code">419</div>
                <h1 class="error-title">Phiên làm việc hết hạn</h1>
                <p class="error-description">
                    Phiên làm việc của bạn đã hết hạn do bảo mật. 
                    Vui lòng làm mới trang và thử lại.
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

