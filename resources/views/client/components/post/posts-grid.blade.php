@props(['posts'])

@if ($posts->count() > 0)
    <div class="row g-5 mb-5">
        @foreach ($posts as $post)
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

    {{-- Pagination --}}
    <div class="d-flex justify-content-center">
        {{ $posts->links('vendor.pagination.bootstrap-5-custom') }}
    </div>
@else
    {{-- Empty State --}}
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
