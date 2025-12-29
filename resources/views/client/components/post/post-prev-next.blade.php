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
            box-shadow: 0 2px 8px rgba(13, 110, 253, 0.1);
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
        }

        .post-nav-title {
            font-size: 0.95rem;
            line-height: 1.4;
            color: #212529;
        }

        .post-nav-item:hover:not(.post-nav-disabled) .post-nav-title {
            color: #667eea;
        }

        .post-nav-prev {
            text-align: left;
        }

        .post-nav-next {
            text-align: right;
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

