@props([
    'post',
    'showAuthor' => false,
    'showDate' => true,
    'thumbnailHeight' => '250px',
    'descriptionLimit' => 120,
    'showButton' => true,
    'buttonText' => 'Xem bài viết',
    'buttonClass' => 'btn btn-sm btn-outline-primary',
    'cardClass' => 'card h-100',
    'titleTag' => 'h5',
    'badgeClass' => 'bg-label-primary',
])

<div class="{{ $cardClass }}">
    {{-- Post Thumbnail --}}
    @if ($post->thumbnail ?? null)
        <a href="{{ route('client.post', $post->slug) }}">
            <img class="card-img-top" 
                 src="{{ $post->thumbnail }}" 
                 alt="{{ $post->title ?? '' }}"
                 loading="lazy"
                 decoding="async"
                 style="height: {{ $thumbnailHeight }}; object-fit: cover; width: 100%;"
                 onerror="this.style.display='none';">
        </a>
    @endif

    <div class="card-body d-flex flex-column">
        {{-- Category Badge --}}
        @if (isset($post->category_name) && $post->category_name)
            <div class="mb-2">
                <span class="badge {{ $badgeClass }}">{{ $post->category_name }}</span>
            </div>
        @endif

        {{-- Post Title --}}
        <{{ $titleTag }} class="card-title mb-{{ $titleTag === 'h2' ? '3' : '2' }}">
            <a href="{{ route('client.post', $post->slug) }}" class="text-heading text-decoration-none">
                {{ $post->title ?? '' }}
            </a>
        </{{ $titleTag }}>

        {{-- Author and Date --}}
        @if ($showAuthor || $showDate)
            <div class="d-flex align-items-center text-muted small mb-2">
                @if ($showAuthor && isset($post->user_name) && $post->user_name)
                    <span class="me-3">{{ $post->user_name }}</span>
                @endif
                @if ($showDate && isset($post->created_at) && $post->created_at)
                    <span>{{ $post->created_at->format('d F Y') }}</span>
                @endif
            </div>
        @endif

        {{-- Post Description --}}
        @php
            $description = $post->meta_description ?? null;
            if (!$description && isset($post->content)) {
                $description = strip_tags($post->content);
            }
        @endphp
        @if ($description)
            <p class="card-text text-muted {{ $titleTag === 'h2' ? '' : 'small' }} flex-grow-1">
                {{ \Illuminate\Support\Str::limit($description, $descriptionLimit) }}
            </p>
        @endif

        {{-- Action Button/Link --}}
        @if ($showButton)
            @if ($buttonClass === 'text-primary')
                <a href="{{ route('client.post', $post->slug) }}" class="text-primary mt-auto">
                    <i class="bx bx-chevron-right"></i> {{ $buttonText }}
                </a>
            @else
                <a href="{{ route('client.post', $post->slug) }}" class="{{ $buttonClass }} mt-auto">
                    {{ $buttonText }}
                </a>
            @endif
        @endif
    </div>
</div>

