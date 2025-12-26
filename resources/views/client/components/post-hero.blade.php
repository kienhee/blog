{{-- Post Hero Section --}}
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

