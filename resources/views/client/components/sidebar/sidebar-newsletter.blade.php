{{-- Newsletter Subscription Widget --}}
<div class="sidebar-widget">
    <div class="d-flex align-items-center gap-3 mb-3">
        <div class="d-flex align-items-center justify-content-center rounded-3 text-white"
            style="width: 56px; height: 56px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <i class="bx bx-envelope fs-2"></i>
        </div>
        <h3 class="sidebar-widget-title mb-0">Nhận tin tức</h3>
    </div>
    <p class="text-muted small mb-3">
        Đăng ký để nhận thông báo về các bài viết mới nhất. Những bài viết thực tế và hữu ích về
        công
        nghệ, lập trình và phát triển sự nghiệp.
    </p>
    <form id="newsletter-form" action="{{ route('client.newsletter.subscribe') }}" method="POST" novalidate>
        @csrf
        <div class="mb-2">
            <div class="input-group">
                <input type="email" class="form-control" id="newsletter-email" name="email"
                    placeholder="Nhập email của bạn..." required />
                <button type="submit" class="btn btn-primary">
                    Đăng ký
                </button>
            </div>
        </div>
        <div id="newsletter-message"></div>
    </form>
</div>

