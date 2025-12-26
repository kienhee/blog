{{-- Author Section --}}
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

