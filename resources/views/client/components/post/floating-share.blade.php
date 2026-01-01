{{-- Floating Share Buttons (Left Side) --}}
<div class="floating-share">
    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->fullUrl()) }}" target="_blank"
        class="floating-share-btn facebook" title="Chia sẻ trên Facebook">
        <i class="bx bxl-facebook"></i>
    </a>
    <a href="https://twitter.com/intent/tweet?url={{ urlencode(request()->fullUrl()) }}&text={{ urlencode($post->title ?? '') }}"
        target="_blank" class="floating-share-btn twitter" title="Chia sẻ trên Twitter">
        <i class="bx bxl-twitter"></i>
    </a>
    <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(request()->fullUrl()) }}&title={{ urlencode($post->title ?? '') }}"
        target="_blank" class="floating-share-btn linkedin" title="Chia sẻ trên LinkedIn">
        <i class="bx bxl-linkedin"></i>
    </a>
    <a href="https://api.whatsapp.com/send?text={{ urlencode(($post->title ?? '') . ' ' . request()->fullUrl()) }}"
        target="_blank" class="floating-share-btn whatsapp" title="Chia sẻ trên WhatsApp">
        <i class="bx bxl-whatsapp"></i>
    </a>
    <button
        onclick="navigator.clipboard.writeText('{{ request()->fullUrl() }}'); toastr.success('Đã sao chép link!', 'Thông báo');"
        class="floating-share-btn" title="Sao chép link" style="border: none; cursor: pointer;">
        <i class="bx bx-link"></i>
    </button>
    @auth
        <button id="savePostBtn" class="floating-share-btn save-post-btn {{ $isSaved ?? false ? 'saved' : '' }}"
            data-post-id="{{ $post->id ?? '' }}" title="{{ $isSaved ?? false ? 'Bỏ lưu bài viết' : 'Lưu bài viết' }}">
            <i class="bx {{ $isSaved ?? false ? 'bxs-bookmark' : 'bx-bookmark' }}"></i>
        </button>
    @else
        <a href="{{ route('client.auth.login', ['redirect' => request()->fullUrl()]) }}" id="loginToSavePostBtn" class="floating-share-btn"
            title="Đăng nhập để lưu bài viết">
            <i class="bx bxs-bookmark"></i>
        </a>
    @endauth
</div>
