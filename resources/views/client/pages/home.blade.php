@extends('client.layouts.master')
@section('title', 'Trang chủ')

@section('content')
    <div data-bs-spy="scroll" class="scrollspy-example">
        <!-- Hero Search: Start -->
        <section id="hero-search" class="section-py landing-hero position-relative">
            <img src="{{ asset_admin_url('assets/img/front-pages/backgrounds/hero-bg.png') }}" alt="hero background"
                class="position-absolute top-0 start-50 translate-middle-x object-fit-contain w-100 h-100" data-speed="1" />
            <div class="container">
                <div class="hero-text-box text-center">
                    <h1 class="text-primary hero-title display-4 fw-bold mb-3">
                        Tìm kiếm bài viết
                    </h1>
                    <h2 class="hero-sub-title h5 mb-4 pb-1 text-muted">
                        Khám phá hàng ngàn bài viết chất lượng về công nghệ, lập trình<br class="d-none d-lg-block" />
                        và nhiều chủ đề thú vị khác
                    </h2>

                    <!-- Search Form -->
                    <div class="hero-search-box d-flex justify-content-center mb-4">
                        <form action="{{ route('client.search') }}" method="GET" class="w-100" style="max-width: 700px;">
                            <div class="input-group input-group-lg shadow-lg">
                                <input type="text" name="q" class="form-control form-control-lg border-0"
                                    placeholder="Nhập từ khóa tìm kiếm..." value="{{ request('q') }}"
                                    aria-label="Tìm kiếm bài viết" autofocus>
                                <button class="btn btn-primary btn-lg px-4" type="submit">
                                    <i class="bx bx-search bx-sm me-1"></i> Tìm kiếm
                                </button>
                            </div>
                        </form>
                    </div>

                    <p class="text-muted small mb-0">
                        <i class="bx bx-info-circle me-1"></i>
                        Ví dụ: JavaScript, Laravel, React, PHP, Vue.js...
                    </p>
                </div>
            </div>
        </section>
        <!-- Hero Search: End -->

        {{-- 6 Bài viết mới nhất --}}
        @if (isset($latestPosts) && $latestPosts->count() > 0)
            <section class="section-py bg-body">
                <div class="container">
                    <div class="text-center mb-4">
                        <h2 class="mb-2">Bài viết mới nhất</h2>
                    </div>
                    <div class="row g-4">
                        @foreach ($latestPosts->take(6) as $post)
                            <div class="col-md-6 col-lg-4">
                                @include('client.components.post.post-card', [
                                    'post' => $post,
                                    'showButton' => true,
                                    'buttonText' => 'Đọc thêm',
                                    'buttonClass' => 'text-primary',
                                    'descriptionLimit' => 120,
                                ])
                            </div>
                        @endforeach
                    </div>
                    <div class="text-center mt-4">
                        <a href="{{ route('client.posts') }}" class="btn btn-outline-primary">Xem thêm</a>
                    </div>
                </div>
            </section>
        @endif

        {{-- Featured Post Banner ở giữa --}}
        @if (isset($featuredPost) && $featuredPost)
            <section class="section-py">
                <div class="container">
                    <div class="card border-0 shadow-lg position-relative overflow-hidden">
                        @if ($featuredPost->thumbnail)
                            <div class="position-relative" style="height: 500px; overflow: hidden;">
                                <img src="{{ $featuredPost->thumbnail }}" alt="{{ $featuredPost->title }}"
                                    class="w-100 h-100" style="object-fit: cover;">
                                <div class="position-absolute top-0 start-0 end-0 bottom-0 bg-dark bg-opacity-50"></div>
                                <div class="position-absolute bottom-0 start-0 end-0 p-4 p-md-5 text-white">
                                    @if ($featuredPost->category_name)
                                        <span class="badge bg-primary mb-2">{{ $featuredPost->category_name }}</span>
                                    @endif
                                    <h2 class="display-5 fw-bold mb-3 text-white">
                                        <a href="{{ route('client.post', $featuredPost->slug) }}"
                                            class="text-white text-decoration-none">
                                            {{ $featuredPost->title }}
                                        </a>
                                    </h2>
                                    @if ($featuredPost->meta_description)
                                        <p class="lead mb-4">
                                            {{ \Illuminate\Support\Str::limit($featuredPost->meta_description, 200) }}
                                        </p>
                                    @else
                                        <p class="lead mb-4">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($featuredPost->content ?? ''), 200) }}
                                        </p>
                                    @endif
                                    <a href="{{ route('client.post', $featuredPost->slug) }}"
                                        class="btn btn-primary btn-lg">Bắt đầu lập kế hoạch</a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        {{-- Bài viết theo danh mục (Tabs) --}}
        <section class="section-py bg-body">
            <div class="container">
                <div class="row">
                    {{-- Main Content Column --}}
                    <div class="col-lg-8">
                        {{-- Category Navigation Tabs --}}
                        @if (isset($categories) && $categories->count() > 0)
                            <div class="mb-4 border-bottom">
                                <ul class="nav nav-tabs border-0" id="categoryTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active border-0 pb-3" id="tab-all" data-bs-toggle="tab"
                                            data-bs-target="#content-all" type="button" role="tab" data-category-id="">
                                            Tất cả
                                        </button>
                                    </li>
                                    @foreach ($categories as $category)
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link border-0 pb-3" id="tab-{{ $category->id }}"
                                                data-bs-toggle="tab" data-bs-target="#content-{{ $category->id }}"
                                                type="button" role="tab" data-category-id="{{ $category->id }}">
                                                {{ $category->name }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Tab Content --}}
                        <div class="tab-content" id="categoryTabContent">
                            {{-- All Posts Tab --}}
                            <div class="tab-pane fade show active" id="content-all" role="tabpanel"
                                aria-labelledby="tab-all">
                                {{-- Posts Grid --}}
                                @if (isset($recentPosts) && $recentPosts->count() > 0)
                                    <div class="row g-4" id="posts-grid-all">
                                        @foreach ($recentPosts as $post)
                                            <div class="col-md-6">
                                                @include('client.components.post.post-card', [
                                                    'post' => $post,
                                                    'showButton' => true,
                                                    'buttonText' => 'Đọc thêm',
                                                    'buttonClass' => 'text-primary',
                                                    'descriptionLimit' => 120,
                                                ])
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            {{-- Category Tabs Content --}}
                            @if (isset($categories) && $categories->count() > 0)
                                @foreach ($categories as $category)
                                    <div class="tab-pane fade" id="content-{{ $category->id }}" role="tabpanel"
                                        aria-labelledby="tab-{{ $category->id }}">
                                        <div id="posts-grid-{{ $category->id }}" class="posts-grid-loading">
                                            <div class="text-center py-5">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Đang tải...</span>
                                                </div>
                                                <p class="mt-3 text-muted">Đang tải bài viết...</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>


                    </div>
                    {{-- Sidebar --}}
                    <div class="col-lg-4">
                        @if (isset($sidebarPosts) && $sidebarPosts->count() > 0)
                            <div class="sticky-top" style="top: 20px; z-index: 998;">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header border-bottom">
                                        <h5 class="mb-0 fw-bold">Bài viết gợi ý</h5>
                                    </div>
                                    <div class="card-body p-0">
                                        @foreach ($sidebarPosts as $post)
                                            <div class="p-3 border-bottom">
                                                <div class="row g-3 align-items-center">
                                                    <div class="col-4">
                                                        @if ($post->thumbnail)
                                                            <a href="{{ route('client.post', $post->slug) }}">
                                                                <img src="{{ $post->thumbnail }}"
                                                                    alt="{{ $post->title }}" class="img-fluid rounded"
                                                                    style="height: 80px; width: 100%; object-fit: cover;">
                                                            </a>
                                                        @endif
                                                    </div>
                                                    <div class="col-8">
                                                        <div class="text-muted small mb-1">
                                                            @if (isset($post->created_at))
                                                                {{ $post->created_at->format('d F Y') }}
                                                            @endif
                                                        </div>
                                                        <h6 class="mb-0">
                                                            <a href="{{ route('client.post', $post->slug) }}"
                                                                class="text-heading text-decoration-none small">
                                                                {{ \Illuminate\Support\Str::limit($post->title, 60) }}
                                                            </a>
                                                        </h6>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
        </section>

    </div>

@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const categoryTabs = document.querySelectorAll('#categoryTabs button[data-category-id]');
            const loadedCategories = new Set(['']); // Track loaded categories, '' means all

            categoryTabs.forEach(function(tab) {
                tab.addEventListener('shown.bs.tab', function(event) {
                    const categoryId = event.target.getAttribute('data-category-id');
                    const targetPaneId = event.target.getAttribute('data-bs-target').replace('#',
                        '');
                    const postsGrid = document.getElementById('posts-grid-' + categoryId);

                    // If not loaded yet, load posts
                    if (!loadedCategories.has(categoryId) && postsGrid) {
                        loadCategoryPosts(categoryId, postsGrid);
                        loadedCategories.add(categoryId);
                    }
                });
            });

            function loadCategoryPosts(categoryId, container) {
                const url = '{{ route('client.api.posts-by-category') }}?category_id=' + categoryId;

                fetch(url, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            renderCategoryPosts(null, data.posts, container);
                        } else {
                            container.innerHTML =
                                '<div class="text-center py-5"><p class="text-muted">Không có bài viết nào trong danh mục này.</p></div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading posts:', error);
                        container.innerHTML =
                            '<div class="text-center py-5"><p class="text-danger">Có lỗi xảy ra khi tải bài viết. Vui lòng thử lại.</p></div>';
                    });
            }

            function renderCategoryPosts(featured, posts, container) {
                let html = '';

                // Render posts grid (không hiển thị featured post trong tab)
                if (posts && posts.length > 0) {
                    html += '<div class="row g-4">';

                    posts.forEach(function(post) {
                        const thumbnail = post.thumbnail || '';
                        const description = post.meta_description || post.content || '';
                        const shortDescription = description.length > 120 ? description.substring(0, 120) +
                            '...' : description;

                        html += `
                            <div class="col-md-6">
                                <div class="card h-100">
                                    ${thumbnail ? `
                                                                    <a href="/bai-viet/${post.slug}">
                                                                        <img src="${thumbnail}" alt="${post.title}" class="card-img-top" style="height: 250px; object-fit: cover;">
                                                                    </a>
                                                                ` : ''}
                                    <div class="card-body d-flex flex-column">
                                        ${post.category_name ? `<div class="mb-2"><span class="badge bg-label-primary">${post.category_name}</span></div>` : ''}
                                        <h5 class="card-title mb-2">
                                            <a href="/bai-viet/${post.slug}" class="text-heading text-decoration-none">${post.title}</a>
                                        </h5>
                                        <p class="card-text text-muted flex-grow-1">${shortDescription.replace(/<[^>]*>/g, '')}</p>
                                        <a href="/bai-viet/${post.slug}" class="text-primary mt-auto">
                                            <i class="bx bx-chevron-right"></i> Đọc thêm
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    html += '</div>';
                } else {
                    html =
                        '<div class="text-center py-5"><p class="text-muted">Không có bài viết nào trong danh mục này.</p></div>';
                }

                container.innerHTML = html;
            }
        });
    </script>
@endpush
