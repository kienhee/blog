{{-- Post Thumbnail --}}
@if (isset($post->thumbnail) && $post->thumbnail)
    <div class="post-thumbnail">
        <a href="{{ $post->thumbnail }}" 
           data-fancybox="gallery" 
           data-caption="{{ $post->title ?? 'Thumbnail bài viết' }}">
            <img src="{{ $post->thumbnail }}" 
                 alt="{{ $post->title ?? 'Thumbnail bài viết' }}" 
                 title="{{ $post->title ?? '' }}"
                 loading="lazy" />
        </a>
    </div>
@endif

