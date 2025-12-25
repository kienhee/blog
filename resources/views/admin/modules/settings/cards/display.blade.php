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

    <div class="col-md-6 mb-3">
        <label class="form-label">Danh mục hiển thị ở trang chủ</label>
        <select name="home_categories[]" class="form-select select2" multiple>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}"
                    {{ in_array($cat->id, old('home_categories', $settings['home_categories'] ?? [])) ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
        <small class="text-muted d-block mt-2">
            Chọn các danh mục bạn muốn hiển thị ở trang chủ. Có thể chọn nhiều danh mục.
        </small>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-3">
        <label class="form-label">Kiểm tra kết nối gửi mail</label><br>
        <button id="testEmailBtn" class="btn btn-primary btn-sm" type="button" onclick="testEmailSetup()">
            <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
            <i class="bx bx-envelope me-2"></i> <span class="btn-label">Kiểm tra kết nối gửi mail</span>
        </button>
    </div>
</div>
@push('scripts')
<script>
    function testEmailSetup() {
        const $btn = $('#testEmailBtn');
        const $spinner = $btn.find('.spinner-border');
        const $label = $btn.find('.btn-label');
        $btn.prop('disabled', true);
        $spinner.removeClass('d-none');
        $label.text('Đang kiểm tra...');

        $.ajax({
            url: '{{ route('admin.settings.testEmailSetup') }}',
            type: 'GET',
            success: function(response) {
                toastr.success(response.message);
            },
            error: function () {
                toastr.error('Không thể kiểm tra kết nối gửi mail.');
            },
            complete: function () {
                $btn.prop('disabled', false);
                $spinner.addClass('d-none');
                $label.text('Kiểm tra');
            }
        });
    }
</script>
@endpush
