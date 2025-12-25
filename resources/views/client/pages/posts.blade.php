@extends('client.layouts.master')
@section('title', 'Danh sách bài viết')
@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/css/pages/front-page-help-center.css') }}" />
    <style>
        /* Limit card title to 2 lines */
        .card-title {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.5;
            max-height: 3em;
            /* 2 lines * 1.5 line-height */
        }

        .card-title a {
            display: block;
        }
    </style>
@endpush
@section('content')
    <section class="section-py first-section-pt help-center-header position-relative overflow-hidden">
        <img class="banner-bg-img" src="{{ asset_admin_url('assets/img/pages/header.png') }}" alt="Help center header" />
        <h2 class="text-center">Danh sách bài viết</h2>
        <form method="GET" action="{{ route('client.posts') }}"
            class="input-wrapper my-3 input-group input-group-lg input-group-merge position-relative mx-auto">
            <span class="input-group-text" id="basic-addon1"><i class="bx bx-search bx-sm"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Tìm kiếm bài viết..." aria-label="Search"
                aria-describedby="basic-addon1" value="{{ request('search') }}" />
            @if (request('category'))
                <input type="hidden" name="category" value="{{ request('category') }}" />
            @endif
        </form>
        <p class="text-center px-3 mb-0">Common troubleshooting topics: eCommerce, Blogging to payment</p>
    </section>
    <!-- Blog List: Start -->
    <section class="section-py custom-pagination">
        <div class="container">
            <!-- Posts Grid -->
            @if ($posts->count() > 0)
                <div class="row g-5 mb-5">
                    @foreach ($posts as $post)
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100">
                                <!-- Post Thumbnail -->
                                @if ($post->thumbnail)
                                    <a href="{{ route('client.post', $post->slug) }}">
                                        <img class="card-img-top" src="{{ $post->thumbnail }}" alt="{{ $post->title }}"
                                            style="height: 250px; object-fit: cover;">
                                    </a>
                                @endif

                                <div class="card-body d-flex flex-column">
                                    <!-- Category Badge -->
                                    @if ($post->category_name)
                                        <div class="mb-2">
                                            <span class="badge bg-label-primary">{{ $post->category_name }}</span>
                                        </div>
                                    @endif

                                    <!-- Post Title -->
                                    <h5 class="card-title">
                                        <a href="{{ route('client.post', $post->slug) }}" class="text-heading">
                                            {{ $post->title }}
                                        </a>
                                    </h5>

                                    <!-- Post Description -->
                                    @if ($post->meta_description)
                                        <p class="card-text text-muted flex-grow-1">
                                            {{ \Illuminate\Support\Str::limit($post->meta_description, 120) }}
                                        </p>
                                    @else
                                        <p class="card-text text-muted flex-grow-1">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($post->content ?? ''), 120) }}
                                        </p>
                                    @endif
                                    <a href="{{ route('client.post', $post->slug) }}" class="text-primary">
                                        <i class="bx bx-chevron-right"></i> Đọc thêm
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $posts->links('vendor.pagination.bootstrap-5-custom') }}
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="bx bx-file bx-lg text-muted"></i>
                    </div>
                    <h5 class="text-muted">Không tìm thấy bài viết nào</h5>
                    <p class="text-muted">Vui lòng thử lại với từ khóa khác hoặc quay lại sau.</p>
                    <a href="{{ route('client.posts') }}" class="btn btn-primary mt-3">
                        Xem tất cả bài viết
                    </a>
                </div>
            @endif
        </div>
    </section>
    <!-- Blog List: End -->
@endsection
