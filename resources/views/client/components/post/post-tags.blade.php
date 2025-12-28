{{-- Tags --}}
@if (isset($hashtags) && count($hashtags) > 0)
    <div class="post-tags">
        <h3 class="post-tags-title">Tags:</h3>
        <div class="post-tags-list">
            @foreach ($hashtags as $hashtag)
                @if (isset($hashtag['slug']) && $hashtag['slug'])
                    <a href="{{ route('client.hashtag', ['slug' => $hashtag['slug']]) }}"
                        class="post-tag">#{{ $hashtag['name'] }}</a>
                @else
                    <span class="post-tag">#{{ $hashtag['name'] }}</span>
                @endif
            @endforeach
        </div>
    </div>
@endif
