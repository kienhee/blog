<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $post->title ?? 'Article' }}</title>

    <!--
        Unified CSS for Article Content
        This is the SAME CSS file used in TinyMCE editor
    -->
    <link rel="stylesheet" href="{{ asset_shared_url('css/article-content.css') }}">

    <!--
        Highlight.js Theme CSS
        This is the SAME theme used in TinyMCE editor
        Version: 11.9.0 (or latest)
    -->
    <link rel="stylesheet" href="{{ asset_shared_url('vendor/highlight/styles/atom-one-dark.min.css') }}">

    <!-- Boxicons for icons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

    <!-- Optional: Add your site's base styles here -->
    <style>
        body {
            margin: 0;
            padding: 20px;
            background-color: #fff;
        }

        .article-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2em;
        }

        .article-header {
            margin-bottom: 2em;
            padding-bottom: 1em;
            border-bottom: 2px solid #eee;
        }

        .article-title {
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 0.5em;
            color: #1a1a1a;
        }

        .article-meta {
            color: #666;
            font-size: 0.9em;
            display: flex;
            flex-wrap: wrap;
            gap: 1.5em;
            align-items: center;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5em;
        }

        .meta-item i {
            font-size: 1.1em;
        }

        .meta-item.author-meta {
            display: flex;
            align-items: center;
            gap: 0.75em;
        }

        .author-avatar-small {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e0e0e0;
        }

        .category-link {
            color: #666;
            text-decoration: none;
            transition: color 0.2s;
        }

        .category-link:hover {
            color: #007bff;
        }

        .article-thumbnail {
            margin-bottom: 2em;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .article-thumbnail img {
            width: 100%;
            height: auto;
            display: block;
        }

        .article-footer {
            margin-top: 3em;
            padding-top: 2em;
            border-top: 2px solid #eee;
        }

        .author-box {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 2em;
            margin-bottom: 2em;
            display: flex;
            gap: 1.5em;
            align-items: flex-start;
        }

        .author-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #f0f0f0;
            flex-shrink: 0;
        }

        .author-info {
            flex: 1;
        }

        .author-label {
            font-size: 0.85em;
            font-weight: 600;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.75em;
        }

        .author-name {
            font-size: 1.25em;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 0.75em;
        }

        .author-description {
            color: #666;
            font-size: 0.95em;
            line-height: 1.6;
            font-style: italic;
        }

        .hashtags-section {
            margin-top: 1em;
        }

        .hashtags-title {
            font-size: 1.1em;
            font-weight: 600;
            margin-bottom: 1em;
            color: #1a1a1a;
        }

        .hashtags-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75em;
        }

        .hashtag-tag {
            display: inline-block;
            padding: 0.5em 1em;
            background-color: #f0f0f0;
            color: #333;
            text-decoration: none;
            border-radius: 20px;
            font-size: 0.9em;
            transition: all 0.2s;
        }

        .hashtag-tag:hover {
            background-color: #007bff;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3);
        }
    </style>
</head>

<body>
    <div class="article-container">
        <!-- Article Header -->
        <header class="article-header">
            <h1 class="article-title">{{ $post->title ?? 'Article Title' }}</h1>
            <div class="article-meta">
                @if (isset($post->user))
                    <span class="meta-item author-meta">
                        @if ($post->user->avatar)
                            <img src="{{ $post->user->avatar }}" alt="{{ $post->user->full_name ?? 'Author' }}"
                                class="author-avatar-small" />
                        @else
                            <i class="bx bx-user"></i>
                        @endif
                        <span>{{ $post->user->full_name ?? ($post->user->name ?? 'Unknown') }}</span>
                    </span>
                @endif
                @if (isset($post->created_at))
                    <span class="meta-item">
                        <i class="bx bx-calendar"></i>
                        {{ $post->created_at->format('F j, Y') }}
                    </span>
                @endif
                @if (isset($viewCount))
                    <span class="meta-item">
                        <i class="bx bx-show"></i>
                        {{ number_format($viewCount) }} lượt đọc
                    </span>
                @endif
                @if (isset($post->category))
                    <span class="meta-item">
                        <i class="bx bx-folder"></i>
                        <a href="#" class="category-link">{{ $post->category->name }}</a>
                    </span>
                @endif
            </div>
        </header>

        <!-- Article Thumbnail -->
        @if (isset($post->thumbnail) && $post->thumbnail)
            <div class="article-thumbnail">
                <img src="{{ $post->thumbnail }}" alt="{{ $post->title ?? 'Article thumbnail' }}" />
            </div>
        @endif

        <!--
            Article Content Wrapper
            CRITICAL: Must use the same wrapper class as TinyMCE editor
            This ensures 100% visual consistency
        -->
        <div class="article-content">
            {!! $post->content ?? '<p>No content available.</p>' !!}
        </div>
        <!-- Hashtags Section -->
        @if (isset($post->hashtags) && $post->hashtags->count() > 0)
            <div class="hashtags-section">
                <h3 class="hashtags-title">Tags:</h3>
                <div class="hashtags-list">
                    @foreach ($post->hashtags as $hashtag)
                        <a href="#" class="hashtag-tag">#{{ $hashtag->name }}</a>
                    @endforeach
                </div>
            </div>
        @endif
        <!-- Article Footer: Author Box and Hashtags -->
        <footer class="article-footer">
            @if (isset($post->user))
                <div class="author-box">
                    @if ($post->user->avatar)
                        <img src="{{ $post->user->avatar }}" alt="{{ $post->user->full_name ?? 'Author' }}"
                            class="author-avatar" />
                    @else
                        <div class="author-avatar"
                            style="background: #e0e0e0; display: flex; align-items: center; justify-content: center; color: #999;">
                            <i class="bx bx-user" style="font-size: 2em;"></i>
                        </div>
                    @endif
                    <div class="author-info">
                        <div class="author-label">TÁC GIẢ</div>
                        <div class="author-name">{{ $post->user->full_name ?? ($post->user->name ?? 'Unknown') }}</div>
                        @if ($post->user->description)
                            <hr>
                            <div class="author-description">"{{ $post->user->description }}"</div>
                        @endif
                    </div>
                </div>
            @endif


        </footer>
    </div>
    <script src="{{ asset_shared_url('vendor/highlight/highlight.min.js') }}"></script>
</body>

</html>
