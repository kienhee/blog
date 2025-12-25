<h5 class="mb-3">
    <i class="bx bx-info-circle me-2"></i>Thông tin cơ bản của website
</h5>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">
            Tên website <span class="text-danger">*</span>
        </label>
        <input type="text" name="site_name" class="form-control"
               value="{{ old('site_name', $settings['site_name']) }}"
               placeholder="Nhập tên website" required>
        <small class="text-muted">Tên hiển thị của website trên trình duyệt và công cụ tìm kiếm</small>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">
            Email quản trị <span class="text-danger">*</span>
        </label>
        <input type="email" name="email" class="form-control"
               value="{{ old('email', $settings['email']) }}"
               placeholder="admin@example.com" required>
        <small class="text-muted">Email liên hệ chính của website</small>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Số điện thoại</label>
        <input type="text" name="phone" class="form-control"
               value="{{ old('phone', $settings['phone']) }}"
               placeholder="0123 456 789">
        <small class="text-muted">Số điện thoại liên hệ</small>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Địa chỉ</label>
        <input type="text" name="address" class="form-control"
               value="{{ old('address', $settings['address']) }}"
               placeholder="Nhập địa chỉ">
        <small class="text-muted">Địa chỉ trụ sở hoặc văn phòng</small>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Mô tả ngắn</label>
    <textarea name="site_description" class="form-control" rows="3"
              placeholder="Nhập mô tả ngắn về website...">{{ old('site_description', $settings['site_description']) }}</textarea>
    <small class="text-muted">Mô tả ngắn gọn về website, sẽ hiển thị trong meta description</small>
</div>
