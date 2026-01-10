{{-- Mobile Share Buttons (Horizontal, below tags) --}}
<div class="post-share-mobile">
    <div class="post-share-mobile-title">
        <i class="bx bx-share-alt me-2"></i>
        Chia sẻ:
    </div>
    <div class="post-share-mobile-buttons">
        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->fullUrl()) }}" target="_blank"
            class="post-share-mobile-btn facebook" title="Chia sẻ trên Facebook">
            <i class="bx bxl-facebook"></i>
            <span>Facebook</span>
        </a>
        <a href="https://twitter.com/intent/tweet?url={{ urlencode(request()->fullUrl()) }}&text={{ urlencode($post->title ?? '') }}"
            target="_blank" class="post-share-mobile-btn twitter" title="Chia sẻ trên Twitter">
            <i class="bx bxl-twitter"></i>
            <span>Twitter</span>
        </a>
        <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(request()->fullUrl()) }}&title={{ urlencode($post->title ?? '') }}"
            target="_blank" class="post-share-mobile-btn linkedin" title="Chia sẻ trên LinkedIn">
            <i class="bx bxl-linkedin"></i>
            <span>LinkedIn</span>
        </a>
        <a href="https://api.whatsapp.com/send?text={{ urlencode(($post->title ?? '') . ' ' . request()->fullUrl()) }}"
            target="_blank" class="post-share-mobile-btn whatsapp" title="Chia sẻ trên WhatsApp">
            <i class="bx bxl-whatsapp"></i>
            <span>WhatsApp</span>
        </a>
        <button
            onclick="navigator.clipboard.writeText('{{ request()->fullUrl() }}'); toastr.success('Đã sao chép link!', 'Thông báo');"
            class="post-share-mobile-btn" title="Sao chép link" title="Sao chép link">
            <i class="bx bx-link"></i>
            <span>Sao chép</span>
        </button>
        @auth
            <button id="savePostBtnMobile" class="post-share-mobile-btn save-post-btn {{ $isSaved ?? false ? 'saved' : '' }}"
                data-post-id="{{ $post->id ?? '' }}" title="{{ $isSaved ?? false ? 'Bỏ lưu bài viết' : 'Lưu bài viết' }}">
                <i class="bx {{ $isSaved ?? false ? 'bxs-bookmark' : 'bx-bookmark' }}"></i>
                <span>{{ $isSaved ?? false ? 'Đã lưu' : 'Lưu' }}</span>
            </button>
        @else
            <a href="{{ route('client.auth.login', ['redirect' => request()->fullUrl()]) }}" id="loginToSavePostBtnMobile" class="post-share-mobile-btn"
                title="Đăng nhập để lưu bài viết">
                <i class="bx bxs-bookmark"></i>
                <span>Lưu</span>
            </a>
        @endauth
    </div>
</div>

<style>
    /* Mobile Share Buttons - Only visible on mobile */
    .post-share-mobile {
        display: none;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e9ecef;
    }

    .post-share-mobile-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #212529;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
    }

    .post-share-mobile-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .post-share-mobile-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 1rem;
        border-radius: 0.5rem;
        border: 1px solid #e9ecef;
        background: #fff;
        color: #6c757d;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.2s ease;
        cursor: pointer;
        flex: 1;
        min-width: calc(50% - 0.375rem);
        justify-content: center;
    }

    .post-share-mobile-btn:hover {
        background: #f8f9fa;
        border-color: #667eea;
        color: #667eea;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(102, 126, 234, 0.2);
    }

    .post-share-mobile-btn.facebook:hover {
        background: #1877f2;
        border-color: #1877f2;
        color: #fff;
    }

    .post-share-mobile-btn.twitter:hover {
        background: #1da1f2;
        border-color: #1da1f2;
        color: #fff;
    }

    .post-share-mobile-btn.linkedin:hover {
        background: #0077b5;
        border-color: #0077b5;
        color: #fff;
    }

    .post-share-mobile-btn.whatsapp:hover {
        background: #25d366;
        border-color: #25d366;
        color: #fff;
    }

    .post-share-mobile-btn.save-post-btn.saved {
        background: #667eea;
        border-color: #667eea;
        color: #fff;
    }

    .post-share-mobile-btn.save-post-btn.saved:hover {
        background: #5568d3;
        border-color: #5568d3;
    }

    .post-share-mobile-btn i {
        font-size: 1.125rem;
    }

    /* Show on mobile only */
    @media (max-width: 991.98px) {
        .post-share-mobile {
            display: block;
        }
    }

    /* Dark mode styles */
    .dark-style .post-share-mobile,
    html.dark-style .post-share-mobile,
    body.dark-style .post-share-mobile,
    html[data-theme="dark"] .post-share-mobile,
    [data-theme="dark"] .post-share-mobile {
        border-top-color: rgba(255, 255, 255, 0.1);
    }

    .dark-style .post-share-mobile-title,
    html.dark-style .post-share-mobile-title,
    body.dark-style .post-share-mobile-title,
    html[data-theme="dark"] .post-share-mobile-title,
    [data-theme="dark"] .post-share-mobile-title {
        color: #cbcbe2;
    }

    .dark-style .post-share-mobile-btn,
    html.dark-style .post-share-mobile-btn,
    body.dark-style .post-share-mobile-btn,
    html[data-theme="dark"] .post-share-mobile-btn,
    [data-theme="dark"] .post-share-mobile-btn {
        background: #2b2c40;
        border-color: rgba(255, 255, 255, 0.2);
        color: #a3a4cc;
    }

    .dark-style .post-share-mobile-btn:hover,
    html.dark-style .post-share-mobile-btn:hover,
    body.dark-style .post-share-mobile-btn:hover,
    html[data-theme="dark"] .post-share-mobile-btn:hover,
    [data-theme="dark"] .post-share-mobile-btn:hover {
        background: #3a3b5c;
        border-color: #667eea;
        color: #8b9aff;
    }
</style>
