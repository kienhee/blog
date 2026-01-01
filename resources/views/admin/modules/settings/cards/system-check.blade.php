<h5 class="mb-3">
    <i class="bx bx-check-circle me-2"></i>Kiểm tra hệ thống
</h5>
<p class="text-muted mb-3">Kiểm tra queue và schedule để đảm bảo hệ thống hoạt động bình thường</p>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card border">
            <div class="card-body">
                <label class="form-label">Email nhận thông báo</label>
                <select name="test_emails[]" id="testEmails" class="form-select select2" multiple>
                    @foreach($users as $user)
                        <option value="{{ $user->email }}"
                            {{ in_array($user->email, old('test_emails', $settings['test_emails'] ?? [])) ? 'selected' : '' }}>
                            {{ $user->email }}@if($user->full_name) ({{ $user->full_name }})@endif
                        </option>
                    @endforeach
                </select>
                <small class="text-muted d-block mt-2">
                    Bước 1: Chọn email của user trong hệ thống để nhận email thông báo kiểm tra.
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-12 mb-4">
        <div class="card border">
            <div class="card-body">
                <h6 class="mb-3">Kiểm tra Queue</h6>
                <p class="text-muted small mb-3">Kiểm tra kết nối queue bằng cách gửi email test qua queue system ngay lập tức</p>
                
                <button id="testQueueBtn" class="btn btn-primary" type="button" onclick="testQueue()">
                    <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                    <i class="bx bx-check me-2"></i> <span class="btn-label">Kiểm tra kết nối</span>
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-12 mb-4">
        <div class="card border">
            <div class="card-body">
                <h6 class="mb-3">Kiểm tra Schedule</h6>
                <p class="text-muted small mb-3">
                    Bước 2: Bật trạng thái và chọn thời gian kiểm tra. Hệ thống sẽ tự động gửi email test qua queue theo thời gian đã chọn.<br>
                    Bước 3: Chờ email. Email sẽ được gửi lặp lại cho đến khi bạn tắt trạng thái.
                </p>
                <div class="alert alert-info small mb-3" role="alert">
                    <i class="bx bx-info-circle me-2"></i>
                    <strong>Lưu ý:</strong> Để schedule hoạt động, bạn cần chạy <code>php artisan schedule:work</code> hoặc setup cron job chạy <code>php artisan schedule:run</code> mỗi phút.
                </div>
                
                <div class="row align-items-center">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Trạng thái</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="schedule_test_enabled" 
                                   id="scheduleTestEnabled" 
                                   value="1"
                                   {{ old('schedule_test_enabled', $settings['schedule_test_enabled'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="scheduleTestEnabled">
                                <span id="scheduleStatusText">{{ old('schedule_test_enabled', $settings['schedule_test_enabled'] ?? false) ? 'Đang bật' : 'Đang tắt' }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Thời gian kiểm tra</label>
                        <select name="schedule_test_interval" id="scheduleTestInterval" class="form-select">
                            <option value="1" {{ old('schedule_test_interval', $settings['schedule_test_interval'] ?? 5) == 1 ? 'selected' : '' }}>1 phút</option>
                            <option value="3" {{ old('schedule_test_interval', $settings['schedule_test_interval'] ?? 5) == 3 ? 'selected' : '' }}>3 phút</option>
                            <option value="5" {{ old('schedule_test_interval', $settings['schedule_test_interval'] ?? 5) == 5 ? 'selected' : '' }}>5 phút</option>
                            <option value="10" {{ old('schedule_test_interval', $settings['schedule_test_interval'] ?? 5) == 10 ? 'selected' : '' }}>10 phút</option>
                            <option value="15" {{ old('schedule_test_interval', $settings['schedule_test_interval'] ?? 5) == 15 ? 'selected' : '' }}>15 phút</option>
                            <option value="30" {{ old('schedule_test_interval', $settings['schedule_test_interval'] ?? 5) == 30 ? 'selected' : '' }}>30 phút</option>
                            <option value="60" {{ old('schedule_test_interval', $settings['schedule_test_interval'] ?? 5) == 60 ? 'selected' : '' }}>1 giờ</option>
                        </select>
                        <small class="text-muted d-block mt-2">Chọn khoảng thời gian giữa các lần kiểm tra schedule</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Select2 will be initialized automatically by forms-selects.js
    // No custom initialization needed

    // Update status text when switch changes
    $('#scheduleTestEnabled').on('change', function() {
        const isEnabled = $(this).is(':checked');
        $('#scheduleStatusText').text(isEnabled ? 'Đang bật' : 'Đang tắt');
    });

    function testQueue() {
        const $btn = $('#testQueueBtn');
        const $spinner = $btn.find('.spinner-border');
        const $label = $btn.find('.btn-label');
        $btn.prop('disabled', true);
        $spinner.removeClass('d-none');
        $label.text('Đang kiểm tra...');

        $.ajax({
            url: '{{ route('admin.settings.testQueue') }}',
            type: 'GET',
            success: function(response) {
                toastr.success(response.message, "Thông báo");
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Không thể kiểm tra kết nối queue.';
                toastr.error(message, "Đã có lỗi xảy ra");
            },
            complete: function () {
                $btn.prop('disabled', false);
                $spinner.addClass('d-none');
                $label.text('Kiểm tra kết nối');
            }
        });
    }
</script>
@endpush

