{{-- Previous and Next Posts Navigation --}}
@if ((isset($prevPost) && $prevPost) || (isset($nextPost) && $nextPost))
    <div class="post-prev-next mb-5">
        <div class="row g-3">
            {{-- Previous Post --}}
            <div class="col-md-6">
                @if (isset($prevPost) && $prevPost)
                    <a href="{{ route('client.post', $prevPost->slug) }}" 
                       class="post-nav-item post-nav-prev d-flex align-items-center text-decoration-none h-100">
                        <div class="post-nav-icon me-3">
                            <i class="bx bx-chevron-left fs-4"></i>
                        </div>
                        <div class="post-nav-content flex-grow-1">
                            <div class="post-nav-label text-muted small mb-1">
                                Bài viết trước
                            </div>
                            <div class="post-nav-title fw-semibold">
                                {{ \Illuminate\Support\Str::limit($prevPost->title, 60) }}
                            </div>
                        </div>
                    </a>
                @else
                    <div class="post-nav-item post-nav-disabled d-flex align-items-center h-100">
                        <div class="post-nav-icon me-3">
                            <i class="bx bx-chevron-left fs-4 text-muted"></i>
                        </div>
                        <div class="post-nav-content flex-grow-1">
                            <div class="post-nav-label text-muted small mb-1">
                                Bài viết trước
                            </div>
                            <div class="post-nav-title text-muted">
                                Không có bài viết nào
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Next Post --}}
            <div class="col-md-6">
                @if (isset($nextPost) && $nextPost)
                    <a href="{{ route('client.post', $nextPost->slug) }}" 
                       class="post-nav-item post-nav-next d-flex align-items-center text-decoration-none h-100 text-end">
                        <div class="post-nav-content flex-grow-1 text-end">
                            <div class="post-nav-label text-muted small mb-1">
                                Bài viết sau <i class="bx bx-arrow-forward ms-1"></i>
                            </div>
                            <div class="post-nav-title fw-semibold">
                                {{ \Illuminate\Support\Str::limit($nextPost->title, 60) }}
                            </div>
                        </div>
                        <div class="post-nav-icon ms-3">
                            <i class="bx bx-chevron-right fs-4"></i>
                        </div>
                    </a>
                @else
                    <div class="post-nav-item post-nav-disabled d-flex align-items-center h-100 text-end">
                        <div class="post-nav-content flex-grow-1 text-end">
                            <div class="post-nav-label text-muted small mb-1">
                                Bài viết sau <i class="bx bx-arrow-forward ms-1"></i>
                            </div>
                            <div class="post-nav-title text-muted">
                                Không có bài viết nào
                            </div>
                        </div>
                        <div class="post-nav-icon ms-3">
                            <i class="bx bx-chevron-right fs-4 text-muted"></i>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        /* Light Mode Styles - Explicit để đảm bảo không bị override */
        .light-style .post-prev-next,
        body.light-style .post-prev-next,
        html:not([data-theme="dark"]) .post-prev-next {
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 0.5rem;
            border: 1px solid #e9ecef;
        }

        .light-style .post-nav-item,
        body.light-style .post-nav-item,
        html:not([data-theme="dark"]) .post-nav-item {
            padding: 1rem;
            background: #fff;
            border-radius: 0.375rem;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            min-height: 80px;
        }

        .light-style .post-nav-item:hover:not(.post-nav-disabled),
        body.light-style .post-nav-item:hover:not(.post-nav-disabled),
        html:not([data-theme="dark"]) .post-nav-item:hover:not(.post-nav-disabled) {
            background: #f8f9fa;
            border-color: #667eea;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
        }

        .light-style .post-nav-item.post-nav-disabled,
        body.light-style .post-nav-item.post-nav-disabled,
        html:not([data-theme="dark"]) .post-nav-item.post-nav-disabled {
            background: #f8f9fa;
            opacity: 0.6;
            cursor: not-allowed;
        }

        .light-style .post-nav-icon,
        body.light-style .post-nav-icon,
        html:not([data-theme="dark"]) .post-nav-icon {
            color: #667eea;
            flex-shrink: 0;
        }

        .light-style .post-nav-disabled .post-nav-icon,
        body.light-style .post-nav-disabled .post-nav-icon,
        html:not([data-theme="dark"]) .post-nav-disabled .post-nav-icon {
            color: #adb5bd;
        }

        .light-style .post-nav-label,
        body.light-style .post-nav-label,
        html:not([data-theme="dark"]) .post-nav-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
        }

        .light-style .post-nav-title,
        body.light-style .post-nav-title,
        html:not([data-theme="dark"]) .post-nav-title {
            font-size: 0.95rem;
            line-height: 1.4;
            color: #212529;
        }

        .light-style .post-nav-item:hover:not(.post-nav-disabled) .post-nav-title,
        body.light-style .post-nav-item:hover:not(.post-nav-disabled) .post-nav-title,
        html:not([data-theme="dark"]) .post-nav-item:hover:not(.post-nav-disabled) .post-nav-title {
            color: #667eea;
        }

        .light-style .post-nav-disabled .post-nav-title,
        body.light-style .post-nav-disabled .post-nav-title,
        html:not([data-theme="dark"]) .post-nav-disabled .post-nav-title {
            color: #6c757d;
        }

        .post-nav-prev {
            text-align: left;
        }

        .post-nav-next {
            text-align: right;
        }

        /* Dark Mode Styles - Đồng bộ với hệ thống - Phải đặt trước default để có priority cao hơn */
        .dark-style .post-prev-next,
        html.dark-style .post-prev-next,
        body.dark-style .post-prev-next,
        .dark-mode .post-prev-next,
        html.dark-mode .post-prev-next,
        body.dark-mode .post-prev-next,
        html[data-theme="dark"] .post-prev-next,
        [data-theme="dark"] .post-prev-next {
            padding: 1.5rem;
            background: #2b2c40 !important;
            border-radius: 0.5rem;
            border-color: #444564 !important;
        }

        .dark-style .post-nav-item,
        html.dark-style .post-nav-item,
        body.dark-style .post-nav-item,
        .dark-mode .post-nav-item,
        html.dark-mode .post-nav-item,
        body.dark-mode .post-nav-item,
        html[data-theme="dark"] .post-nav-item,
        [data-theme="dark"] .post-nav-item {
            padding: 1rem;
            background: #2b2c40 !important;
            border-radius: 0.375rem;
            border-color: rgba(255, 255, 255, 0.2) !important;
            transition: all 0.3s ease;
            min-height: 80px;
        }

        .dark-style .post-nav-item:hover:not(.post-nav-disabled),
        html.dark-style .post-nav-item:hover:not(.post-nav-disabled),
        body.dark-style .post-nav-item:hover:not(.post-nav-disabled),
        .dark-mode .post-nav-item:hover:not(.post-nav-disabled),
        html.dark-mode .post-nav-item:hover:not(.post-nav-disabled),
        body.dark-mode .post-nav-item:hover:not(.post-nav-disabled),
        html[data-theme="dark"] .post-nav-item:hover:not(.post-nav-disabled),
        [data-theme="dark"] .post-nav-item:hover:not(.post-nav-disabled) {
            background: #3a3b5c !important;
            border-color: #667eea !important;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }

        .dark-style .post-nav-item.post-nav-disabled,
        html.dark-style .post-nav-item.post-nav-disabled,
        body.dark-style .post-nav-item.post-nav-disabled,
        .dark-mode .post-nav-item.post-nav-disabled,
        html.dark-mode .post-nav-item.post-nav-disabled,
        body.dark-mode .post-nav-item.post-nav-disabled,
        html[data-theme="dark"] .post-nav-item.post-nav-disabled,
        [data-theme="dark"] .post-nav-item.post-nav-disabled {
            background: #2b2c40 !important;
            opacity: 0.6;
            cursor: not-allowed;
        }

        .dark-style .post-nav-icon,
        html.dark-style .post-nav-icon,
        body.dark-style .post-nav-icon,
        .dark-mode .post-nav-icon,
        html.dark-mode .post-nav-icon,
        body.dark-mode .post-nav-icon,
        html[data-theme="dark"] .post-nav-icon,
        [data-theme="dark"] .post-nav-icon {
            color: #667eea !important;
            flex-shrink: 0;
        }

        .dark-style .post-nav-disabled .post-nav-icon,
        html.dark-style .post-nav-disabled .post-nav-icon,
        body.dark-style .post-nav-disabled .post-nav-icon,
        .dark-mode .post-nav-disabled .post-nav-icon,
        html.dark-mode .post-nav-disabled .post-nav-icon,
        body.dark-mode .post-nav-disabled .post-nav-icon,
        html[data-theme="dark"] .post-nav-disabled .post-nav-icon,
        [data-theme="dark"] .post-nav-disabled .post-nav-icon {
            color: #7071a4 !important;
        }

        .dark-style .post-nav-label,
        html.dark-style .post-nav-label,
        body.dark-style .post-nav-label,
        .dark-mode .post-nav-label,
        html.dark-mode .post-nav-label,
        body.dark-mode .post-nav-label,
        html[data-theme="dark"] .post-nav-label,
        [data-theme="dark"] .post-nav-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #7071a4 !important;
        }

        .dark-style .post-nav-title,
        html.dark-style .post-nav-title,
        body.dark-style .post-nav-title,
        .dark-mode .post-nav-title,
        html.dark-mode .post-nav-title,
        body.dark-mode .post-nav-title,
        html[data-theme="dark"] .post-nav-title,
        [data-theme="dark"] .post-nav-title {
            font-size: 0.95rem;
            line-height: 1.4;
            color: #cbcbe2 !important;
        }

        .dark-style .post-nav-item:hover:not(.post-nav-disabled) .post-nav-title,
        html.dark-style .post-nav-item:hover:not(.post-nav-disabled) .post-nav-title,
        body.dark-style .post-nav-item:hover:not(.post-nav-disabled) .post-nav-title,
        .dark-mode .post-nav-item:hover:not(.post-nav-disabled) .post-nav-title,
        html.dark-mode .post-nav-item:hover:not(.post-nav-disabled) .post-nav-title,
        body.dark-mode .post-nav-item:hover:not(.post-nav-disabled) .post-nav-title,
        html[data-theme="dark"] .post-nav-item:hover:not(.post-nav-disabled) .post-nav-title,
        [data-theme="dark"] .post-nav-item:hover:not(.post-nav-disabled) .post-nav-title {
            color: #8b9aff !important;
        }

        .dark-style .post-nav-disabled .post-nav-title,
        html.dark-style .post-nav-disabled .post-nav-title,
        body.dark-style .post-nav-disabled .post-nav-title,
        .dark-mode .post-nav-disabled .post-nav-title,
        html.dark-mode .post-nav-disabled .post-nav-title,
        body.dark-mode .post-nav-disabled .post-nav-title,
        html[data-theme="dark"] .post-nav-disabled .post-nav-title,
        [data-theme="dark"] .post-nav-disabled .post-nav-title {
            color: #7071a4 !important;
        }

        /* Default styles (fallback) - Chỉ áp dụng khi không có dark mode */
        .post-prev-next {
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 0.5rem;
            border: 1px solid #e9ecef;
        }

        .post-nav-item {
            padding: 1rem;
            background: #fff;
            border-radius: 0.375rem;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            min-height: 80px;
        }

        .post-nav-item:hover:not(.post-nav-disabled) {
            background: #f8f9fa;
            border-color: #667eea;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
        }

        .post-nav-item.post-nav-disabled {
            background: #f8f9fa;
            opacity: 0.6;
            cursor: not-allowed;
        }

        .post-nav-icon {
            color: #667eea;
            flex-shrink: 0;
        }

        .post-nav-disabled .post-nav-icon {
            color: #adb5bd;
        }

        .post-nav-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
        }

        .post-nav-title {
            font-size: 0.95rem;
            line-height: 1.4;
            color: #212529;
        }

        .post-nav-item:hover:not(.post-nav-disabled) .post-nav-title {
            color: #667eea;
        }

        .post-nav-disabled .post-nav-title {
            color: #6c757d;
        }

        /* Media query dark mode (prefers-color-scheme) */
        @media (prefers-color-scheme: dark) {
            html:not(.light-style) .post-prev-next {
                background: #2b2c40 !important;
                border-color: #444564 !important;
            }

            html:not(.light-style) .post-nav-item {
                background: #2b2c40 !important;
                border-color: rgba(255, 255, 255, 0.2) !important;
            }

            html:not(.light-style) .post-nav-item:hover:not(.post-nav-disabled) {
                background: #3a3b5c !important;
                border-color: #667eea !important;
            }

            html:not(.light-style) .post-nav-item.post-nav-disabled {
                background: #2b2c40 !important;
            }

            html:not(.light-style) .post-nav-icon {
                color: #667eea !important;
            }

            html:not(.light-style) .post-nav-disabled .post-nav-icon {
                color: #7071a4 !important;
            }

            html:not(.light-style) .post-nav-title {
                color: #cbcbe2 !important;
            }

            html:not(.light-style) .post-nav-item:hover:not(.post-nav-disabled) .post-nav-title {
                color: #8b9aff !important;
            }

            html:not(.light-style) .post-nav-label {
                color: #7071a4 !important;
            }

            html:not(.light-style) .post-nav-disabled .post-nav-title {
                color: #7071a4 !important;
            }
        }

        @media (max-width: 768px) {
            .post-prev-next {
                padding: 1rem;
            }

            .post-nav-item {
                padding: 0.75rem;
                min-height: 70px;
            }

            .post-nav-title {
                font-size: 0.875rem;
            }
        }
    </style>
@endif

