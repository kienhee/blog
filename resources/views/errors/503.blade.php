@extends('client.layouts.master')

@section('title', '503 - Dịch vụ không khả dụng')

@section('content')
    <section class="error-page error-503">
        <div class="container">
            <div class="error-content">
                <div class="error-illustration">
                    <svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg">
                        <!-- Background circle -->
                        <circle cx="200" cy="150" r="120" class="error-illustration-bg" opacity="0.1"/>
                        <!-- Maintenance illustration -->
                        <g transform="translate(200, 150)">
                            <!-- Tool icon -->
                            <rect x="-25" y="-30" width="50" height="40" rx="3" class="error-illustration-fill"/>
                            <rect x="-20" y="-25" width="40" height="30" fill="#fff" opacity="0.2"/>
                            <!-- Wrench handle -->
                            <path d="M 15 -10 L 25 -5 L 20 0 L 10 -5 Z" class="error-illustration-fill"/>
                            <!-- Gear icon -->
                            <circle cx="-30" cy="-20" r="12" class="error-illustration-bg" opacity="0.3"/>
                            <circle cx="-30" cy="-20" r="8" fill="#fff" opacity="0.2"/>
                            <!-- Gear teeth -->
                            <rect x="-33" y="-25" width="6" height="10" class="error-illustration-bg" opacity="0.3"/>
                            <rect x="-33" y="15" width="6" height="10" class="error-illustration-bg" opacity="0.3"/>
                            <rect x="-25" y="-33" width="10" height="6" class="error-illustration-bg" opacity="0.3"/>
                            <rect x="-35" y="-33" width="10" height="6" class="error-illustration-bg" opacity="0.3"/>
                        </g>
                        <!-- 503 text decoration -->
                        <text x="200" y="200" font-family="Arial, sans-serif" font-size="24" font-weight="bold" class="error-illustration-bg" opacity="0.3" text-anchor="middle">503</text>
                    </svg>
                </div>
                <div class="error-code">503</div>
                <h1 class="error-title">Dịch vụ không khả dụng</h1>
                <p class="error-description">
                    Hệ thống đang được bảo trì hoặc tạm thời không khả dụng. 
                    Chúng tôi đang nỗ lực khắc phục và sẽ hoạt động trở lại sớm nhất có thể.
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

