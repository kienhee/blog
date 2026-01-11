@extends('admin.layouts.master')
@section('title', 'Chi tiết ' . $monthNames[$financeMonth->month] . '/' . $year->year)

@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <style>
        /* Responsive buttons cho mobile */
        @media (max-width: 768px) {
            /* Container của buttons */
            .d-flex.gap-2 {
                flex-direction: column;
                width: 100%;
                gap: 0.75rem !important;
            }
            
            /* Buttons full width trên mobile */
            .d-flex.gap-2 > .btn,
            .d-flex.gap-2 > a.btn {
                width: 100%;
                justify-content: center;
                padding: 0.625rem 1rem;
                font-size: 0.875rem;
                white-space: nowrap;
            }
            
            /* Icon trong button */
            .d-flex.gap-2 .btn i,
            .d-flex.gap-2 a.btn i {
                font-size: 1rem;
                margin-right: 0.5rem;
            }
            
            /* Heading section responsive */
            .d-flex.flex-wrap.justify-content-between {
                flex-direction: column;
                align-items: flex-start !important;
            }
            
            .d-flex.flex-wrap.justify-content-between > div:first-child {
                width: 100%;
                margin-bottom: 1rem;
            }
            
            .d-flex.flex-wrap.justify-content-between > div:last-child {
                width: 100%;
            }
        }
        
        /* Tablet và desktop - giữ nguyên */
        @media (min-width: 769px) {
            .d-flex.gap-2 {
                flex-wrap: wrap;
            }
        }
    </style>
@endpush

@section('content')
    <section>
        @include('admin.components.headingPage', [
            'description' => 'Chi tiết ' . $monthNames[$financeMonth->month] . ' / ' . $year->year,
            'button' => 'back',
            'buttonLink' => route('admin.finance.years.months.show', ['yearId' => $year->id, 'month' => $financeMonth->month]),
            'extraButtons' => !$financeMonth->isLocked() ? [
                [
                    'type' => 'button',
                    'text' => 'Thêm chi phí',
                    'icon' => 'bx-plus',
                    'class' => 'btn-primary',
                    'id' => 'btnAddExpense'
                ],
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
                                    <button class="accordion-button collapsed {{ $bgClass }}" type="button" 
                                            data-bs-toggle="collapse" data-bs-target="#{{ $accordionId }}" 
                                            aria-expanded="false" 
                                            aria-controls="{{ $accordionId }}">
                                        <span class="badge badge-dot {{ $dotColor }} me-2"></span>
                                        <strong>{{ $dayData['date']->format('d/m/Y') }}</strong>
                                        <span class="ms-auto me-3">
                                            Tổng: {{ number_format($dayData['total'], 0, ',', '.') }}đ
                                        </span>
                                    </button>
                                </h2>
                                <div id="{{ $accordionId }}" class="accordion-collapse collapse" 
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
                    
                    <!-- Tổng tiền đã chi tiêu trong tháng -->
                    <div class="mt-4 pt-3 border-top">
                        <p class="mb-0">
                            <strong>Số tiền đã chi tiêu đến thời điểm hiện tại</strong> 
                            <span class="text-primary fw-bold">({{ number_format($totalExpenses, 0, ',', '.') }}đ):</span>
                        </p>
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        Chưa có chi tiêu nào trong tháng này.
                    </div>
                @endif
            </div>
        </div>

        <!-- Offcanvas để thêm chi phí -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="addExpenseSidebar"
            aria-labelledby="addExpenseSidebarLabel">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title mb-2" id="addExpenseSidebarLabel">Thêm chi tiêu</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                    aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <form id="expenseForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="expenseDate">Ngày <span class="text-danger">*</span></label>
                        <input type="text" class="form-control date-picker" id="expenseDate" name="date" 
                            placeholder="dd/mm/yyyy" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="expenseType">Loại chi tiêu <span class="text-danger">*</span></label>
                        <select class="form-select" id="expenseType" name="finance_type_id" required>
                            <option value="">Chọn loại chi tiêu</option>
                            @foreach($financeTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="expenseMoney">Số tiền chi tiêu <span class="text-danger">*</span></label>
                        <input type="text" class="form-control format-money" id="expenseMoney" name="money" 
                            placeholder="Nhập số tiền" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="expenseNote">Ghi chú</label>
                        <textarea class="form-control" id="expenseNote" name="note" rows="3" 
                            placeholder="Nhập ghi chú (nếu có)"></textarea>
                    </div>
                    <div class="d-flex justify-content-between my-4">
                        <div>
                            <button type="submit" class="btn btn-primary" id="btnSaveExpense">
                                <span class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                                Lưu
                            </button>
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">
                                Hủy
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/moment/moment.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script>
        // Helper function để unformat số (bỏ dấu chấm)
        function unformatNumber(value) {
            if (!value) return '';
            return value.toString().replace(/\./g, '');
        }

        $(document).ready(function() {
            const monthId = {{ $financeMonth->id }};
            const isLocked = {{ $financeMonth->isLocked() ? 'true' : 'false' }};
            const lockUrl = '{{ route("admin.finance.months.lock", $financeMonth->id) }}';
            const expenseStoreUrl = '{{ route("admin.finance.months.expenses.store", $financeMonth->id) }}';
            
            // Khởi tạo flatpickr cho date picker
            const $expenseDate = $('#expenseDate');
            if ($expenseDate.length && typeof flatpickr !== 'undefined') {
                $expenseDate.flatpickr({
                    dateFormat: 'd/m/Y',
                    allowInput: true,
                    locale: {
                        firstDayOfWeek: 1
                    }
                });
            }
            
            // Format money khi nhập
            $('.format-money').on('input', function() {
                let val = this.value;
                val = val.replace(/\D/g, '');
                val = val.replace(/^0+(?!$)/, '');
                val = val.substring(0, 15);
                val = val.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                this.value = val;
            });

            // Khởi tạo offcanvas
            let expenseSidebar = new bootstrap.Offcanvas(document.getElementById('addExpenseSidebar'));
            
            // Button thêm chi tiêu
            $('#btnAddExpense').on('click', function() {
                if (isLocked) {
                    toastr.warning('Tháng này đã bị khóa, không thể thêm chi tiêu', "Thông báo");
                    return;
                }
                const today = moment().format('DD/MM/YYYY');
                $('#expenseDate').val(today);
                $('#expenseType').val('').trigger('change');
                $('#expenseMoney').val('');
                $('#expenseNote').val('');
                $('#addExpenseSidebarLabel').text('Thêm chi tiêu');
                expenseSidebar.show();
            });
            
            // Submit form
            $('#expenseForm').on('submit', function(e) {
                e.preventDefault();
                
                const $btn = $('#btnSaveExpense');
                const originalHtml = $btn.html();
                $btn.prop('disabled', true).find('.spinner-border').removeClass('d-none');
                
                // Convert date từ d/m/Y sang Y-m-d
                const dateValue = $('#expenseDate').val();
                let dateFormatted = dateValue;
                if (dateValue) {
                    const dateParts = dateValue.split('/');
                    if (dateParts.length === 3) {
                        dateFormatted = dateParts[2] + '-' + dateParts[1] + '-' + dateParts[0];
                    }
                }
                
                const formData = {
                    date: dateFormatted,
                    finance_type_id: $('#expenseType').val(),
                    money: unformatNumber($('#expenseMoney').val()),
                    note: $('#expenseNote').val()
                };
                
                $.ajax({
                    url: expenseStoreUrl,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    data: formData,
                    success: function(response) {
                        if (response.status) {
                            toastr.success(response.message, "Thông báo");
                            expenseSidebar.hide();
                            // Reload page sau 1 giây để cập nhật dữ liệu
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.message || 'Có lỗi xảy ra', "Đã có lỗi xảy ra");
                            $btn.prop('disabled', false).html(originalHtml);
                        }
                    },
                    error: function(xhr) {
                        let message = 'Có lỗi xảy ra khi thêm chi tiêu.';
                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            } else if (xhr.responseJSON.errors) {
                                const errors = Object.values(xhr.responseJSON.errors).flat();
                                message = errors.join('<br>');
                            }
                        }
                        toastr.error(message, "Đã có lỗi xảy ra");
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });
            
            // Lock month
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

