<h5 class="mb-3">
    <i class="bx bx-slider me-2"></i>Cấu hình hiển thị
</h5>
<p class="text-muted mb-3">Tùy chỉnh cách hiển thị nội dung trên website</p>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Số lượng bài viết mỗi trang</label>
        <input type="number" name="posts_per_page" class="form-control"
               value="{{ old('posts_per_page', $settings['posts_per_page']) }}"
               min="1" max="100"
               placeholder="15">
        <small class="text-muted">Số lượng bài viết hiển thị trên mỗi trang danh sách (mặc định: 15)</small>
    </div>
</div>