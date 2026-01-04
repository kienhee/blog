@extends('admin.layouts.master')
@section('title', 'Chi tiết ' . $monthNames[$financeMonth->month] . '/' . $year->year)

@section('content')
    <section>
        @include('admin.components.headingPage', [
            'description' => 'Chi tiết ' . $monthNames[$financeMonth->month] . ' / ' . $year->year,
            'button' => 'back',
            'buttonLink' => route('admin.finance.years.months.show', ['yearId' => $year->id, 'month' => $financeMonth->month]),
            'extraButtons' => !$financeMonth->isLocked() ? [
                [
                    'type' => 'button',
                    'text' => 'Khóa tháng',
                    'icon' => 'bx-lock',
                    'class' => 'btn-warning',
                    'id' => 'btnLockMonth'
                ]
            ] : [],
        ])

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Danh sách chi phí theo ngày</h5>
            </div>
            <div class="card-body">
                <!-- Ghi chú về ý nghĩa màu sắc -->
                <div class="alert alert-info mb-3" role="alert">
                    <h6 class="alert-heading mb-2">
                        <i class="bx bx-info-circle me-1"></i> Ghi chú về màu sắc
                    </h6>
                    <p class="mb-2 small">Màu sắc của dot badge và nền thể hiện mức chi phí trong ngày:</p>
                    <ul class="mb-0 small">
                        <li><span class="badge badge-dot text-bg-secondary me-2"></span><span class="badge bg-label-secondary me-2">Xám</span>: Tổng chi phí &lt; 200.000đ</li>
                        <li><span class="badge badge-dot text-bg-warning me-2"></span><span class="badge bg-label-warning me-2">Vàng</span>: Tổng chi phí từ 200.000đ đến dưới 500.000đ</li>
                        <li><span class="badge badge-dot text-bg-danger me-2"></span><span class="badge bg-label-danger me-2">Đỏ</span>: Tổng chi phí ≥ 500.000đ</li>
                    </ul>
                </div>
                
                @if($daysGrouped->count() > 0)
                    <div class="accordion" id="accordionDays">
                        @foreach($daysGrouped as $index => $dayData)
                            @php
                                // Tính toán màu sắc badge và nền dựa trên tổng tiền trong ngày (x)
                                $totalInDay = $dayData['total'];
                                $dotColor = 'text-bg-secondary'; // Mặc định: x < 200.000
                                $bgClass = 'bg-label-secondary'; // Màu nền mặc định: x < 200.000
                                
                                if ($totalInDay >= 500000) {
                                    $dotColor = 'text-bg-danger'; // x >= 500.000
                                    $bgClass = 'bg-label-danger';
                                } elseif ($totalInDay >= 200000) {
                                    $dotColor = 'text-bg-warning'; // x >= 200.000 và x < 500.000
                                    $bgClass = 'bg-label-warning';
                                }
                                
                                $accordionId = 'day-' . $index;
                            @endphp
                            <div class="accordion-item">
                                <h2 class="accordion-header {{ $bgClass }}" id="heading{{ $index }}">
                                    <button class="accordion-button {{ $index !== 0 ? 'collapsed' : '' }} {{ $bgClass }}" type="button" 
                                            data-bs-toggle="collapse" data-bs-target="#{{ $accordionId }}" 
                                            aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" 
                                            aria-controls="{{ $accordionId }}">
                                        <span class="badge badge-dot {{ $dotColor }} me-2"></span>
                                        <strong>{{ $dayData['date']->format('d/m/Y') }}</strong>
                                        <span class="ms-auto me-3">
                                            Tổng: {{ number_format($dayData['total'], 0, ',', '.') }}đ
                                        </span>
                                    </button>
                                </h2>
                                <div id="{{ $accordionId }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" 
                                     aria-labelledby="heading{{ $index }}" data-bs-parent="#accordionDays">
                                    <div class="accordion-body {{ $bgClass }}">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Loại chi tiêu</th>
                                                        <th>Số tiền</th>
                                                        <th>Ghi chú</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($dayData['expenses'] as $expense)
                                                        <tr>
                                                            <td>{{ $expense['finance_type_name'] }}</td>
                                                            <td><strong>{{ number_format($expense['money'], 0, ',', '.') }}đ</strong></td>
                                                            <td>{{ $expense['note'] ?: '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        Chưa có chi tiêu nào trong tháng này.
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.js') }}"></script>
    <script>
        $(document).ready(function() {
            const lockUrl = '{{ route("admin.finance.months.lock", $financeMonth->id) }}';
            
            $('#btnLockMonth').on('click', function() {
                if (!confirm('Bạn có chắc chắn muốn khóa tháng này? Sau khi khóa, bạn sẽ không thể chỉnh sửa dữ liệu nữa.')) {
                    return;
                }
                
                const $btn = $(this);
                const originalHtml = $btn.html();
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Đang khóa...');
                
                $.ajax({
                    url: lockUrl,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        if (response.status) {
                            toastr.success(response.message, "Thông báo");
                            // Reload page sau 1 giây để cập nhật UI
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.message || 'Có lỗi xảy ra', "Đã có lỗi xảy ra");
                            $btn.prop('disabled', false).html(originalHtml);
                        }
                    },
                    error: function(xhr) {
                        let message = 'Có lỗi xảy ra khi khóa tháng.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        toastr.error(message, "Đã có lỗi xảy ra");
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });
        });
    </script>
@endpush

