@extends('client.layouts.master')

@section('title', '403 - Không có quyền truy cập')

@section('content')
    <section class="error-page error-403">
        <div class="container">
            <div class="error-content">
                <div class="error-illustration">
                    <svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg">
                        <!-- Background circle -->
                        <circle cx="200" cy="150" r="120" class="error-illustration-bg" opacity="0.1"/>
                        <!-- Lock illustration -->
                        <g transform="translate(200, 150)">
                            <!-- Lock body -->
                            <rect x="-30" y="-20" width="60" height="50" rx="5" class="error-illustration-fill"/>
                            <rect x="-25" y="-15" width="50" height="40" rx="3" fill="#fff" opacity="0.2"/>
                            <!-- Lock shackle -->
                            <path d="M -20 -20 Q -20 -40, 0 -40 Q 20 -40, 20 -20" class="error-illustration-stroke" stroke-width="4" fill="none"/>
                            <!-- Keyhole -->
                            <circle cx="0" cy="5" r="8" fill="#fff" opacity="0.3"/>
                            <rect x="-3" y="5" width="6" height="15" fill="#fff" opacity="0.3"/>
                        </g>
                        <!-- 403 text decoration -->
                        <text x="200" y="200" font-family="Arial, sans-serif" font-size="24" font-weight="bold" class="error-illustration-bg" opacity="0.3" text-anchor="middle">403</text>
                    </svg>
                </div>
                <div class="error-code">403</div>
                <h1 class="error-title">Không có quyền truy cập</h1>
                <p class="error-description">
                    Xin lỗi, bạn không có quyền truy cập vào trang này. 
                    Vui lòng liên hệ quản trị viên nếu bạn cho rằng đây là lỗi.
                </p>
                <div class="error-actions">
                    <a href="{{ route('client.home') }}" class="btn btn-primary btn-lg">
                        <i class="bx bx-home me-2"></i>
                        Về trang chủ
                    </a>
                    <a href="javascript:history.back()" class="btn btn-outline-secondary btn-lg">
                        <i class="bx bx-arrow-back me-2"></i>
                        Quay lại
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection

