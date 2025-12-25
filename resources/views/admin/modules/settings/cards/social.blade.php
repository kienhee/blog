<h5 class="mb-3">
    <i class="bx bx-share-alt me-2"></i>Liên kết mạng xã hội
</h5>
<p class="text-muted mb-3">Thêm các liên kết mạng xã hội của bạn để hiển thị trên website</p>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Facebook</label>
        <input type="url" name="facebook" class="form-control" 
               value="{{ old('facebook', $settings['facebook']) }}" 
               placeholder="https://facebook.com/yourpage">
    </div>
    
    <div class="col-md-6 mb-3">
        <label class="form-label">YouTube</label>
        <input type="url" name="youtube" class="form-control" 
               value="{{ old('youtube', $settings['youtube']) }}" 
               placeholder="https://youtube.com/@yourchannel">
    </div>
    
    <div class="col-md-6 mb-3">
        <label class="form-label">Twitter / X</label>
        <input type="url" name="twitter" class="form-control" 
               value="{{ old('twitter', $settings['twitter']) }}" 
               placeholder="https://twitter.com/yourhandle">
    </div>
    
    <div class="col-md-6 mb-3">
        <label class="form-label">Instagram</label>
        <input type="url" name="instagram" class="form-control" 
               value="{{ old('instagram', $settings['instagram']) }}" 
               placeholder="https://instagram.com/yourprofile">
    </div>
    
    <div class="col-md-6 mb-3">
        <label class="form-label">TikTok</label>
        <input type="url" name="tiktok" class="form-control" 
               value="{{ old('tiktok', $settings['tiktok'] ?? '') }}" 
               placeholder="https://tiktok.com/@yourusername">
    </div>
    
    <div class="col-md-6 mb-3">
        <label class="form-label">LinkedIn</label>
        <input type="url" name="linkedin" class="form-control" 
               value="{{ old('linkedin', $settings['linkedin'] ?? '') }}" 
               placeholder="https://linkedin.com/company/yourcompany">
    </div>
    
    <div class="col-md-6 mb-3">
        <label class="form-label">Telegram</label>
        <input type="url" name="telegram" class="form-control" 
               value="{{ old('telegram', $settings['telegram'] ?? '') }}" 
               placeholder="https://t.me/yourchannel">
    </div>
    
    <div class="col-md-6 mb-3">
        <label class="form-label">Pinterest</label>
        <input type="url" name="pinterest" class="form-control" 
               value="{{ old('pinterest', $settings['pinterest'] ?? '') }}" 
               placeholder="https://pinterest.com/yourprofile">
    </div>
</div>