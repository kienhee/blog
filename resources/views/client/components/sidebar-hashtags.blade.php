{{-- Hashtags Widget --}}
@if (isset($allHashtags) && $allHashtags->count() > 0)
    <div class="sidebar-widget ">
        <h3 class="sidebar-widget-title mb-3">
            <i class="bx bx-hash"></i>
            Khám phá
        </h3>
        <ul class="list-unstyled d-flex flex-wrap gap-2">
            @foreach ($allHashtags as $hashtag)
                <li>
                    @if ($hashtag->slug)
                        <a href="{{ route('client.hashtag', ['slug' => $hashtag->slug]) }}"
                            class="badge rounded-pill bg-label-secondary">
                            <span>{{ $hashtag->name }}</span>
                        </a>
                    @else
                        <span class="badge rounded-pill bg-label-secondary">
                            <span>{{ $hashtag->name }}</span>
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif
