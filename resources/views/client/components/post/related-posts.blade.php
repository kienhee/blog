{{-- Related Posts --}}
@if (isset($relatedPosts) && $relatedPosts->count() > 0)
    <div class="post-comments">
        <h3 class="comments-title">Bài viết liên quan</h3>
        <div class="row">
            @foreach ($relatedPosts as $relatedPost)
                <div class="col-md-6 mb-4">
                    <div class="related-post-item" style="border: none; padding: 0;">
                        @if (isset($relatedPost->thumbnail) && $relatedPost->thumbnail)
                            <img src="{{ thumb_path($relatedPost->thumbnail) }}"
                                alt="{{ $relatedPost->title }}" class="related-post-thumb" />
                        @else
                            <div class="related-post-thumb"
                                style="background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                <i class="bx bx-image" style="font-size: 2em; color: #999;"></i>
                            </div>
                        @endif
                        <div class="related-post-info">
                            <h4 class="related-post-title">
                                <a
                                    href="{{ route('client.post', $relatedPost->slug) }}">{{ $relatedPost->title }}</a>
                            </h4>
                            <div class="related-post-meta">
                                @if (isset($relatedPost->created_at))
                                    {{ $relatedPost->created_at->format('d/m/Y') }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

