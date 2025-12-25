@extends('client.layouts.master')
@section('title', 'Danh sách bài viết')
@push('styles')
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
    <!-- Blog List: Start -->
    <section class="section-py custom-pagination">
        <div class="main-container">
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
