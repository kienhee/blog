@extends('admin.layouts.master')
@section('title', 'Danh sách chi tiêu')

@push('styles')
<link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
@endpush

@section('content')
<section>
    @include('admin.components.headingPage', [
        'description' => 'Quản lý chi tiêu theo năm',
        'button' => 'offcanvas',
        'buttonId' => 'createYearOffcanvas',
        'buttonText' => 'Tạo năm mới',
    ])

    @if($years->count() > 0)
        <div class="row g-3">
            @foreach($years as $year)
                @php
                    $targets = $year->target ?? [];
                    $totalTargets = count($targets);
                    $completedTargets = count(array_filter($targets, function($item) {
                        return isset($item['completed']) && $item['completed'];
                    }));
                @endphp
                <div class="col-md-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h3 class="card-title mb-3">{{ $year->year }}</h5>
                            <div class="mb-2">
                                <small class="text-muted">Số mục tiêu: <strong>{{ $totalTargets }}</strong></small>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">Hoàn thành: <strong>{{ $completedTargets }}/{{ $totalTargets }}</strong></small>
                            </div>
                            <a href="{{ route('admin.finance.years.show', $year->id) }}" class="btn btn-primary btn-sm w-100">
                                <i class="bx bx-show me-1"></i> Xem chi tiết
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card">
            <div class="card-body">
                <div class="alert alert-info mb-0">
                    <i class="bx bx-info-circle me-2"></i>
                    Chưa có dữ liệu chi tiêu. Hãy tạo năm mới để bắt đầu quản lý chi tiêu.
                </div>
            </div>
        </div>
    @endif

    <!-- Offcanvas tạo năm -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="createYearOffcanvas" aria-labelledby="createYearOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="createYearOffcanvasLabel">Tạo năm mới</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form id="createYearForm">
                @csrf
                <div class="mb-3">
                    <label for="year" class="form-label">Năm <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="year" name="year" 
                        min="2026" max="2100" 
                        value="{{ date('Y') >= 2026 ? date('Y') : 2026 }}" 
                        placeholder="Nhập năm" required>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="d-flex gap-2 justify-content-end">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">Hủy</button>
                    <button type="submit" class="btn btn-primary" id="submitCreateYearBtn">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                        <i class="bx bx-save me-1"></i> Tạo năm
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.js') }}"></script>
<script>
$(document).ready(function() {
    // Submit form tạo năm
    $('#createYearForm').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $btn = $('#submitCreateYearBtn');
        const originalHtml = $btn.html();
        const $yearInput = $('#year');
        
        // Reset validation
        $yearInput.removeClass('is-invalid');
        $yearInput.next('.invalid-feedback').text('');
        
        $btn.prop('disabled', true).find('.spinner-border').removeClass('d-none');
        
        const formData = {
            year: $yearInput.val()
        };
        
        $.ajax({
            url: '{{ route("admin.finance.years.store") }}',
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.status) {
                    toastr.success(response.message, "Thông báo");
                    
                    // Đóng offcanvas
                    const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('createYearOffcanvas'));
                    offcanvas.hide();
                    
                    // Reset form
                    $form[0].reset();
                    
                    // Reload trang sau 1 giây
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.message || 'Có lỗi xảy ra', "Đã có lỗi xảy ra");
                }
                $btn.prop('disabled', false).html(originalHtml);
            },
            error: function(xhr) {
                let message = 'Có lỗi xảy ra khi tạo năm.';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    if (errors.year) {
                        $yearInput.addClass('is-invalid');
                        $yearInput.next('.invalid-feedback').text(errors.year[0]);
                        message = errors.year[0];
                    } else {
                        const firstError = Object.values(errors)[0];
                        message = Array.isArray(firstError) ? firstError[0] : firstError;
                    }
                }
                
                toastr.error(message, "Đã có lỗi xảy ra");
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
    
    // Reset form khi offcanvas được đóng
    $('#createYearOffcanvas').on('hidden.bs.offcanvas', function() {
        $('#createYearForm')[0].reset();
        $('#year').removeClass('is-invalid');
        $('#year').next('.invalid-feedback').text('');
        $('#year').val('{{ date("Y") }}');
    });
});
</script>
@endpush

