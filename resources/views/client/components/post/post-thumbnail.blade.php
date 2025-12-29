{{-- Post Thumbnail --}}
@if (isset($post->thumbnail) && $post->thumbnail)
    <div class="post-thumbnail">
        <img src="{{ $post->thumbnail }}" 
             alt="{{ $post->title ?? 'Thumbnail bài viết' }}" 
             title="{{ $post->title ?? '' }}"
             loading="lazy" />
    </div>
@endif

