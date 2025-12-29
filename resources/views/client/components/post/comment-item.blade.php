{{-- Comment Item Component (Recursive for nested replies) --}}
@php
    $level = $level ?? 0;
    $maxLevel = 3; // Giới hạn độ sâu của nested comments
@endphp

<div class="comment-item card mb-3" data-comment-id="{{ $comment->id }}">
    <div class="card-body">
        <div class="d-flex align-items-start">
            @if(isset($comment->user) && $comment->user->avatar)
                <img src="{{ thumb_path($comment->user->avatar) }}" 
                    alt="{{ $comment->user->full_name ?? $comment->user->email }}" 
                    class="rounded-circle me-2 me-md-3 comment-avatar" 
                    style="width: 36px; height: 36px; min-width: 36px; object-fit: cover;">
            @else
                <div class="rounded-circle bg-label-secondary d-flex align-items-center justify-content-center me-2 me-md-3 comment-avatar" 
                    style="width: 36px; height: 36px; min-width: 36px;">
                    <i class="bx bx-user" style="font-size: 18px;"></i>
                </div>
            @endif
            <div class="flex-grow-1" style="min-width: 0;">
                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between mb-2 gap-1">
                    <div class="d-flex align-items-center flex-wrap gap-1">
                        <strong class="comment-author-name">
                            {{ isset($comment->user) ? ($comment->user->full_name ?? $comment->user->email) : 'Khách' }}
                        </strong>
                        @if(isset($comment->created_at))
                            <span class="text-muted small comment-time">
                                <i class="bx bx-time me-1"></i>{{ $comment->created_at->diffForHumans() }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="comment-content mb-2">
                    {{ $comment->content ?? '' }}
                </div>
                
                {{-- Reply Button --}}
                @auth
                    @if($level < $maxLevel)
                        <button type="button" class="btn btn-sm btn-outline-secondary reply-btn" 
                            data-comment-id="{{ $comment->id }}"
                            data-comment-author="{{ isset($comment->user) ? ($comment->user->full_name ?? $comment->user->email) : 'Khách' }}">
                            <i class="bx bx-reply me-1"></i><span class="d-none d-sm-inline">Trả lời</span>
                        </button>
                    @endif
                @endauth
            </div>
        </div>
        
        {{-- Reply Form (Hidden by default) --}}
        @auth
            @if($level < $maxLevel)
                <div class="reply-form-container mt-3 ms-0 ms-md-5" id="reply-form-{{ $comment->id }}" style="display: none;">
                    <div class="card bg-label-secondary">
                        <div class="card-body p-2 p-sm-3">
                            <div class="d-flex align-items-start">
                                @if(auth()->user()->avatar)
                                    <img src="{{ thumb_path(auth()->user()->avatar) }}" 
                                        alt="{{ auth()->user()->full_name ?? auth()->user()->email }}" 
                                        class="rounded-circle me-2 me-md-3 comment-avatar" 
                                        style="width: 28px; height: 28px; min-width: 28px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-label-secondary d-flex align-items-center justify-content-center me-2 me-md-3 comment-avatar" 
                                        style="width: 28px; height: 28px; min-width: 28px;">
                                        <i class="bx bx-user" style="font-size: 14px;"></i>
                                    </div>
                                @endif
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <div class="mb-2 small">
                                        <strong>Trả lời <span class="reply-to-author"></span></strong>
                                    </div>
                                    <form class="reply-form" data-parent-id="{{ $comment->id }}">
                                        @csrf
                                        <input type="hidden" name="post_id" value="{{ $post->id ?? '' }}">
                                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                        <div class="mb-2">
                                            <textarea name="content" 
                                                class="form-control form-control-sm" 
                                                rows="2" 
                                                placeholder="Viết phản hồi của bạn..." 
                                                required></textarea>
                                        </div>
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="button" class="btn btn-sm btn-secondary cancel-reply-btn">Hủy</button>
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="bx bx-send me-1"></i>Gửi
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endauth
        
        {{-- Replies List --}}
        @if(isset($comment->replies) && $comment->replies->count() > 0)
            <div class="replies-list mt-3 ms-0 ms-md-5">
                @foreach($comment->replies->sortBy('created_at') as $reply)
                    @include('client.components.post.comment-item', ['comment' => $reply, 'level' => $level + 1])
                @endforeach
            </div>
        @endif
    </div>
</div>
