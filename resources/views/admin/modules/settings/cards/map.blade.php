<h5 class="mb-3">
    <i class="bx bx-map me-2"></i>Bản đồ Google Maps
</h5>
<p class="text-muted mb-3">Thêm bản đồ Google Maps để hiển thị vị trí trên website</p>

<div class="mb-3">
    <label class="form-label">Iframe hoặc Embed Code</label>
    <textarea name="map" class="form-control" rows="6" 
              placeholder="Dán iframe code từ Google Maps hoặc URL embed...">{{ old('map', $settings['map']) }}</textarea>
    <small class="text-muted d-block mt-2">
        Hướng dẫn: Vào Google Maps → Chia sẻ → Nhúng bản đồ → Sao chép iframe code và dán vào đây
    </small>
</div>

@if(!empty($settings['map']))
<div class="alert alert-info d-flex align-items-center mb-3">
    <i class="bx bx-check-circle me-2"></i>
    <span>Bản đồ đã được cấu hình. Bạn có thể xem trước bên dưới:</span>
</div>
<div class="border rounded p-2 bg-light">
    {!! $settings['map'] !!}
</div>
@endif
