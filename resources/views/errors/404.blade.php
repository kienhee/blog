@extends('client.layouts.master')

@section('title', '404 - Trang không tìm thấy')

@section('content')
    <section class="error-page error-404">
        <div class="container">
            <div class="error-content">
                <div class="error-illustration">
                    <svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg">
                        <!-- Background circle -->
                        <circle cx="200" cy="150" r="120" class="error-illustration-bg" opacity="0.1"/>
                        <!-- Main character -->
                        <g transform="translate(200, 150)">
                            <!-- Head -->
                            <circle cx="0" cy="-40" r="35" class="error-illustration-bg" opacity="0.2"/>
                            <circle cx="0" cy="-40" r="25" class="error-illustration-fill"/>
                            <!-- Body -->
                            <rect x="-20" y="-15" width="40" height="50" rx="20" class="error-illustration-bg" opacity="0.3"/>
                            <!-- Arms -->
                            <ellipse cx="-35" cy="0" rx="8" ry="25" class="error-illustration-bg" opacity="0.3"/>
                            <ellipse cx="35" cy="0" rx="8" ry="25" class="error-illustration-bg" opacity="0.3"/>
                            <!-- Question mark -->
                            <text x="0" y="-30" font-family="Arial, sans-serif" font-size="40" font-weight="bold" class="error-illustration-fill" text-anchor="middle">?</text>
                        </g>
                        <!-- 404 text decoration -->
                        <text x="200" y="200" font-family="Arial, sans-serif" font-size="24" font-weight="bold" class="error-illustration-bg" opacity="0.3" text-anchor="middle">404</text>
                    </svg>
                </div>
                <div class="error-code">404</div>
                <h1 class="error-title">Trang không tìm thấy</h1>
                <p class="error-description">
                    Xin lỗi, trang bạn đang tìm kiếm không tồn tại hoặc đã bị di chuyển. 
                    Vui lòng kiểm tra lại đường dẫn hoặc quay về trang chủ.
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

