@extends('client.layouts.master')
@section('title', 'Tìm kiếm' . ($searchQuery ? ': ' . $searchQuery : ''))

@section('content')
    <!-- Search Page: Start -->
    <section class="section-py">
        <div class="main-container">
            <!-- Search Header -->
            <div class="mb-5">
                <h1 class="mb-3">Tìm kiếm</h1>
                
                <!-- Search Form -->
                <form action="{{ route('client.search') }}" method="GET" class="mb-4">
                    <div class="input-group input-group-lg">
                        <input type="text" 
                               name="q" 
                               class="form-control" 
                               placeholder="Nhập từ khóa tìm kiếm..." 
                               value="{{ $searchQuery }}"
                               aria-label="Tìm kiếm bài viết"
                               autofocus>
                        <button class="btn btn-primary" type="submit">
                            <i class="bx bx-search bx-sm me-1"></i> Tìm kiếm
                        </button>
                    </div>
                </form>

                @if (!empty($searchQuery))
                    <p class="text-muted">
                        @if ($posts->count() > 0)
                            Tìm thấy <strong>{{ $posts->total() }}</strong> kết quả cho từ khóa "<strong>{{ $searchQuery }}</strong>"
                        @else
                            Không tìm thấy kết quả nào cho từ khóa "<strong>{{ $searchQuery }}</strong>"
                        @endif
                    </p>
                @else
                    <p class="text-muted">
                        Nhập từ khóa để tìm kiếm bài viết
                    </p>
                @endif
            </div>

            <!-- Search Results -->
            @if (!empty($searchQuery))
                @if ($posts->count() > 0)
                    <!-- Posts Grid -->
                    @include('client.components.post.posts-grid', ['posts' => $posts])
                @else
                    <!-- Empty State -->
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="bx bx-search-alt bx-lg text-muted" style="font-size: 5rem;"></i>
                        </div>
                        <h4 class="text-muted mb-3">Không tìm thấy kết quả</h4>
                        <p class="text-muted mb-4">
                            Không có bài viết nào khớp với từ khóa "<strong>{{ $searchQuery }}</strong>".<br>
                            Vui lòng thử lại với từ khóa khác.
                        </p>
                        <div class="d-flex gap-3 justify-content-center">
                            <a href="{{ route('client.posts') }}" class="btn btn-primary">
                                <i class="bx bx-list-ul me-1"></i> Xem tất cả bài viết
                            </a>
                            <a href="{{ route('client.home') }}" class="btn btn-outline-primary">
                                <i class="bx bx-home me-1"></i> Về trang chủ
                            </a>
                        </div>
                    </div>
                @endif
            @else
                <!-- No Search Query -->
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="bx bx-search bx-lg text-muted" style="font-size: 5rem;"></i>
                    </div>
                    <h4 class="text-muted mb-3">Bắt đầu tìm kiếm</h4>
                    <p class="text-muted mb-4">
                        Nhập từ khóa vào ô tìm kiếm phía trên để tìm các bài viết bạn quan tâm.
                    </p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <span class="badge bg-label-secondary px-3 py-2">Ví dụ:</span>
                        <a href="{{ route('client.search', ['q' => 'JavaScript']) }}" class="badge bg-label-primary px-3 py-2 text-decoration-none">JavaScript</a>
                        <a href="{{ route('client.search', ['q' => 'Laravel']) }}" class="badge bg-label-primary px-3 py-2 text-decoration-none">Laravel</a>
                        <a href="{{ route('client.search', ['q' => 'React']) }}" class="badge bg-label-primary px-3 py-2 text-decoration-none">React</a>
                        <a href="{{ route('client.search', ['q' => 'PHP']) }}" class="badge bg-label-primary px-3 py-2 text-decoration-none">PHP</a>
                        <a href="{{ route('client.search', ['q' => 'Vue.js']) }}" class="badge bg-label-primary px-3 py-2 text-decoration-none">Vue.js</a>
                    </div>
                </div>
            @endif
        </div>
    </section>
    <!-- Search Page: End -->
@endsection

