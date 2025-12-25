@extends('client.layouts.master')
@section('title', $post->title ?? 'Bài viết')
@push('styles')
    <!-- Unified CSS for Article Content -->
    <link rel="stylesheet" href="{{ asset_shared_url('css/article-content.css') }}">
    <!-- Highlight.js Theme CSS -->
    <link rel="stylesheet" href="{{ asset_shared_url('vendor/highlight/styles/atom-one-dark.min.css') }}">
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/fonts/boxicons.css') }}" />
    <!-- Post Page CSS -->
    <link rel="stylesheet" href="{{ asset_client_url('css/post.css') }}">
@endpush

@section('content')
    <div class="post-container section-py">
        <!-- Hero Section -->
        <header class="post-hero">
            <h1 class="post-title">{{ $post->title ?? 'Bài viết' }}</h1>

            <div class="post-meta">
                @if (isset($post->full_name))
                    <span class="meta-item author-meta">
                        @if (isset($post->avatar) && $post->avatar)
                            <img src="{{ $post->avatar ? thumb_path($post->avatar) : asset_shared_url('images/default.png') }}"
                                alt="{{ $post->full_name }}" class="author-avatar-small" />
                        @else
                            <i class="bx bx-user"></i>
                        @endif
                        <span>{{ $post->full_name }}</span>
                    </span>
                @endif

                @if (isset($post->created_at))
                    <span class="meta-item">
                        <i class="bx bx-calendar"></i>
                        {{ $post->created_at->format('d/m/Y') }}
                    </span>
                @endif

                @if (isset($readingTime))
                    <span class="meta-item">
                        <i class="bx bx-time"></i>
                        {{ $readingTime }} phút đọc
                    </span>
                @endif

                @if (isset($viewCount))
                    <span class="meta-item">
                        <i class="bx bx-show"></i>
                        {{ number_format($viewCount) }} lượt xem
                    </span>
                @endif
                @if (isset($post->category_name))
                    <span class="meta-item">
                        <i class="bx bx-folder"></i>
                        <span>
                            {{ $post->category_name }}
                        </span>
                    </span>
                @endif
            </div>
        </header>

        <!-- Floating Share Buttons (Left Side) -->
        <div class="floating-share">
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->fullUrl()) }}" target="_blank"
                class="floating-share-btn facebook" title="Chia sẻ trên Facebook">
                <i class="bx bxl-facebook"></i>
            </a>
            <a href="https://twitter.com/intent/tweet?url={{ urlencode(request()->fullUrl()) }}&text={{ urlencode($post->title ?? '') }}"
                target="_blank" class="floating-share-btn twitter" title="Chia sẻ trên Twitter">
                <i class="bx bxl-twitter"></i>
            </a>
            <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(request()->fullUrl()) }}&title={{ urlencode($post->title ?? '') }}"
                target="_blank" class="floating-share-btn linkedin" title="Chia sẻ trên LinkedIn">
                <i class="bx bxl-linkedin"></i>
            </a>
            <a href="https://api.whatsapp.com/send?text={{ urlencode(($post->title ?? '') . ' ' . request()->fullUrl()) }}"
                target="_blank" class="floating-share-btn whatsapp" title="Chia sẻ trên WhatsApp">
                <i class="bx bxl-whatsapp"></i>
            </a>
            <button onclick="navigator.clipboard.writeText('{{ request()->fullUrl() }}'); alert('Đã sao chép link!');"
                class="floating-share-btn" title="Sao chép link" style="border: none; cursor: pointer;">
                <i class="bx bx-link"></i>
            </button>
        </div>

        <!-- Main Layout: Content + Sidebar -->
        <div class="post-layout">
            <!-- Main Content (70%) -->
            <main class="post-main">
                <!-- Thumbnail -->
                @if (isset($post->thumbnail) && $post->thumbnail)
                    <div class="post-thumbnail">
                        <img src="{{ $post->thumbnail }}" alt="{{ $post->title ?? 'Thumbnail' }}" />
                    </div>
                @endif

                <!-- Article Content -->
                <div class="article-content post-content">
                    {!! $post->content ?? '<p>Nội dung bài viết không có sẵn.</p>' !!}
                </div>

                <!-- Tags -->
                @if (isset($hashtags) && count($hashtags) > 0)
                    <div class="post-tags">
                        <h3 class="post-tags-title">Tags:</h3>
                        <div class="post-tags-list">
                            @foreach ($hashtags as $hashtag)
                                <a href="#" class="post-tag">#{{ $hashtag['name'] }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Author Section -->
                @if (isset($post->full_name))
                    <div class="post-author">
                        @if (isset($post->avatar) && $post->avatar)
                            <img src="{{ $post->avatar ? thumb_path($post->avatar) : asset_shared_url('images/default.png') }}"
                                alt="{{ $post->full_name }}" class="post-author-avatar" />
                        @else
                            <div class="post-author-avatar"
                                style="background: #e0e0e0; display: flex; align-items: center; justify-content: center; color: #999;">
                                <i class="bx bx-user" style="font-size: 2em;"></i>
                            </div>
                        @endif
                        <div class="post-author-info">
                            <div class="post-author-label">TÁC GIẢ</div>
                            <div class="post-author-name">{{ $post->full_name }}</div>
                            @if (isset($post->description) && $post->description)
                                <div class="post-author-description">"{{ $post->description }}"</div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Related Posts -->
                @if (isset($relatedPosts) && $relatedPosts->count() > 0)
                    <div class="post-comments">
                        <h3 class="comments-title">Bài viết liên quan</h3>
                        <div class="row">
                            @foreach ($relatedPosts as $relatedPost)
                                <div class="col-md-6 mb-4">
                                    <div class="related-post-item" style="border: none; padding: 0;">
                                        @if (isset($relatedPost->thumbnail) && $relatedPost->thumbnail)
                                            <img src="{{ thumb_path($relatedPost->thumbnail) }}"
                                                alt="{{ $relatedPost->title }}" class="related-post-thumb" />
                                        @else
                                            <div class="related-post-thumb"
                                                style="background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                                <i class="bx bx-image" style="font-size: 2em; color: #999;"></i>
                                            </div>
                                        @endif
                                        <div class="related-post-info">
                                            <h4 class="related-post-title">
                                                <a
                                                    href="{{ route('client.post', $relatedPost->slug) }}">{{ $relatedPost->title }}</a>
                                            </h4>
                                            <div class="related-post-meta">
                                                @if (isset($relatedPost->created_at))
                                                    {{ $relatedPost->created_at->format('d/m/Y') }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Comments Section -->
                @if (isset($post->allow_comment) && $post->allow_comment)
                    <div class="post-comments">
                        <h3 class="comments-title">Bình luận</h3>
                        <div id="disqus_thread"></div>
                        <script>
                            (function() {
                                var d = document,
                                    s = d.createElement('script');
                                s.src = 'https://YOUR_DISQUS_SHORTNAME.disqus.com/embed.js';
                                s.setAttribute('data-timestamp', +new Date());
                                (d.head || d.body).appendChild(s);
                            })();
                        </script>
                        <noscript>Vui lòng bật JavaScript để xem bình luận.</noscript>
                    </div>
                @endif
            </main>

            <!-- Sidebar (30%) -->
            <aside class="post-sidebar">
                <!-- Table of Contents -->
                <div class="sidebar-widget">
                    <h3 class="sidebar-widget-title" onclick="toggleTOC()">
                        <span class="sidebar-widget-title-text">
                            <i class="bx bx-list-ul"></i>
                            Mục lục
                        </span>
                        <button class="sidebar-widget-toggle" type="button" aria-label="Đóng/Mở mục lục">
                            <i class="bx bx-chevron-down"></i>
                        </button>
                    </h3>
                    <div class="sidebar-widget-content" id="toc-content">
                        <ul class="toc-list" id="toc-list">
                            <!-- TOC will be generated by JavaScript -->
                        </ul>
                    </div>
                </div>



                <!-- Categories Widget -->
                @if (isset($allCategories) && $allCategories->count() > 0)
                    <div class="sidebar-widget ">
                        <h3 class="sidebar-widget-title mb-3">
                            <i class="bx bx-folder"></i>
                            Danh mục
                        </h3>
                        <ul class="category-list">
                            @foreach ($allCategories->take(10) as $category)
                                <li class="category-item">
                                    <a href="{{ route('client.posts', ['category' => $category->slug ?? '']) }}"
                                        class="category-link">
                                        <span>{{ $category->name }}</span>
                                        @if (isset($category->post_count))
                                            <span class="category-count">{{ $category->post_count }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset_shared_url('vendor/highlight/highlight.min.js') }}"></script>
    <script src="{{ asset_client_url('js/post.js') }}"></script>
@endpush
