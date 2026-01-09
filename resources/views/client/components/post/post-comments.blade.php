{{-- Comments Section --}}
<div class="post-comments mt-5 pt-4 border-top">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between mb-4 gap-2">
        <h3 class="comments-title mb-0">
            <i class="bx bx-comment-dots me-2"></i>Bình luận
        </h3>
        @if(isset($post->allow_comment) && $post->allow_comment)
            @php
                $commentsCount = isset($post->comments_count) ? $post->comments_count : 0;
            @endphp
            <span class="badge bg-label-primary">{{ $commentsCount }} bình luận</span>
        @endif
    </div>

    @if(isset($post->allow_comment) && $post->allow_comment)
        {{-- Comments are enabled --}}
        <div id="comments-container">
            {{-- Comment Form --}}
            @auth
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-start mb-3">
                            @if(auth()->user()->avatar)
                                <img src="{{ thumb_path(auth()->user()->avatar) }}" 
                                    alt="{{ auth()->user()->full_name ?? auth()->user()->email }}" 
                                    class="rounded-circle me-2 me-md-3 comment-avatar comment-avatar-main" 
                                    style="width: 40px; height: 40px; min-width: 40px; object-fit: cover;"
                                    loading="lazy"
                                    decoding="async" />
                            @else
                                <div class="rounded-circle bg-label-secondary d-flex align-items-center justify-content-center me-2 me-md-3 comment-avatar comment-avatar-main" 
                                    style="width: 40px; height: 40px; min-width: 40px;">
                                    <i class="bx bx-user"></i>
                                </div>
                            @endif
                            <div class="flex-grow-1">
                                <div class="mb-2">
                                    <strong class="small d-block d-sm-inline">{{ auth()->user()->full_name ?? auth()->user()->email }}</strong>
                                </div>
                                <form id="comment-form" class="comment-form">
                                    @csrf
                                    <input type="hidden" name="post_id" value="{{ $post->id ?? '' }}">
                                    <input type="hidden" name="parent_id" value="">
                                    <div class="mb-3">
                                        <textarea name="content" id="comment-content" 
                                            class="form-control" 
                                            rows="3" 
                                            placeholder="Viết bình luận của bạn..." 
                                            required></textarea>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary btn-sm w-100 w-sm-auto">
                                            <i class="bx bx-send me-1 me-md-2"></i><span class="d-none d-sm-inline">Gửi bình luận</span> 
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-info d-flex flex-column flex-sm-row align-items-start align-items-sm-center mb-4">
                    <i class="bx bx-info-circle me-0 me-sm-2 mb-2 mb-sm-0 fs-4"></i>
                    <div class="flex-grow-1">
                        <strong>Đăng nhập để bình luận</strong>
                        <p class="mb-0 small">Vui lòng <a href="{{ route('client.auth.login') }}" class="alert-link">đăng nhập</a> hoặc <a href="{{ route('client.auth.register') }}" class="alert-link">đăng ký</a> để tham gia thảo luận.</p>
                    </div>
                </div>
            @endauth

            {{-- Comments List --}}
            <div id="comments-list" class="comments-list">
                @if(isset($post->comments) && $post->comments->count() > 0)
                    @foreach($post->comments as $comment)
                        @include('client.components.post.comment-item', ['comment' => $comment, 'level' => 0])
                    @endforeach
                @else
                    <div class="text-center py-5">
                        <i class="bx bx-comment-x fs-1 text-muted mb-3 d-block"></i>
                        <p class="text-muted mb-0">Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>
                    </div>
                @endif
            </div>
        </div>
    @else
        {{-- Comments are disabled --}}
        <div class="comments-disabled text-center py-5">
            <div class="card border-0 bg-label-secondary">
                <div class="card-body py-5">
                    <i class="bx bx-lock-alt fs-1 text-muted mb-3 d-block"></i>
                    <h5 class="mb-2">Bình luận đã được đóng</h5>
                    <p class="text-muted mb-0">
                        Tác giả đã tắt tính năng bình luận cho bài viết này.
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
