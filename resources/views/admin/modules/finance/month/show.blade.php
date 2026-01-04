@extends('admin.layouts.master')
@section('title', $monthNames[$financeMonth->month] . '/' . $year->year)

@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/fullcalendar/fullcalendar.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/quill/editor.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/css/pages/app-calendar.css') }}" />
    <style>
        /* Style cho checkbox với màu */
        .form-check-danger .form-check-input:checked {
            background-color: #ff3e1d;
            border-color: #ff3e1d;
        }
        .form-check-warning .form-check-input:checked {
            background-color: #ffab00;
            border-color: #ffab00;
        }
        .form-check-success .form-check-input:checked {
            background-color: #71dd37;
            border-color: #71dd37;
        }
        .form-check-info .form-check-input:checked {
            background-color: #03c3ec;
            border-color: #03c3ec;
        }
        .form-check-primary .form-check-input:checked {
            background-color: #696cff;
            border-color: #696cff;
        }
        .form-check-secondary .form-check-input:checked {
            background-color: #8592a3;
            border-color: #8592a3;
        }
    </style>
@endpush

@section('content')
    <section>
        @include('admin.components.headingPage', [
            'description' => 'Chi tiết ' . $monthNames[$financeMonth->month] . ' / ' . $year->year,
            'button' => 'back',
            'buttonLink' => route('admin.finance.years.show', $year->id),
            'extraButtons' => $financeDays->count() >= 1 ? [
                [
                    'url' => route('admin.finance.months.dayDetails', $financeMonth->id),
                    'text' => 'Xem chi tiết',
                    'icon' => 'bx-list-ul',
                    'class' => 'btn-primary'
                ]
            ] : [],
        ])
        <div class="card app-calendar-wrapper">
            <div class="row g-0">
                <!-- Calendar Sidebar -->
                <div class="col app-calendar-sidebar" id="app-calendar-sidebar">
                    <div class="border-bottom p-4 my-sm-0 mb-3">
                        <div class="d-grid">
                            <button class="btn btn-primary" type="button" id="btnAddExpense" @if($financeMonth->isLocked()) disabled @endif>
                                <i class="bx bx-plus me-1"></i>
                                <span class="align-middle">Thêm chi tiêu</span>
                            </button>
                        </div>
                    </div>
                    <div class="p-4">
                        <!-- Thông tin tháng -->
                        <div class="mb-4">
                            <h6 class="mb-2 d-flex align-items-center justify-content-between">
                                <span>Thông tin tháng</span>
                                @if($financeMonth->isLocked())
                                    <span class="badge bg-label-warning">
                                        <i class="bx bx-lock me-1"></i>Đã khóa
                                    </span>
                                @endif
                            </h6>
                            <dl class="row mb-2 small">
                                <dt class="col-6 text-muted">Tháng:</dt>
                                <dd class="col-6">{{ $monthNames[$financeMonth->month] }}/{{ $year->year }}</dd>
                            </dl>
                            @if($financeMonth->isLocked())
                                <div class="alert alert-warning alert-dismissible mb-3" role="alert">
                                    <small>
                                        <i class="bx bx-info-circle me-1"></i>
                                        Tháng này đã bị khóa, không thể chỉnh sửa dữ liệu.
                                        @if($financeMonth->locked_time)
                                            Đã khóa vào: {{ $financeMonth->locked_time->format('d/m/Y H:i') }}
                                        @endif
                                    </small>
                                </div>
                            @endif
                            
                            @php
                                $totalExpenses = $financeDays->sum('money');
                            @endphp
                            
                            <div class="mb-2">
                                <label class="form-label small mb-1">Tổng tiền trong tháng</label>
                                <input type="text" class="form-control form-control-sm format-money" id="totalMoney" 
                                    value="{{ number_format($financeMonth->total_money, 0, ',', '.') }}" 
                                    data-field="total_money" @if($financeMonth->isLocked()) readonly style="background-color: #f5f5f5;" @endif>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label small mb-1">Tiền còn lại</label>
                                <input type="text" class="form-control form-control-sm" id="remainingMoney" 
                                    value="{{ number_format($financeMonth->remaining_money, 0, ',', '.') }}" readonly 
                                    style="background-color: #f5f5f5;">
                                <small class="text-muted d-block mt-1">Tự động tính: Tổng tiền - Tổng chi phí ({{ number_format($totalExpenses, 0, ',', '.') }}đ)</small>
                            </div>
                        </div>

                        <hr class="container-m-nx my-4" />

                        <!-- Event Filters -->
                        <div class="mb-4">
                            <small class="text-small text-muted text-uppercase align-middle">Lọc theo loại chi phí</small>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input select-all-filter" type="checkbox" id="selectAllFilters" data-value="all" checked />
                            <label class="form-check-label" for="selectAllFilters">Xem tất cả</label>
                        </div>

                        <div class="app-calendar-events-filter">
                            @php
                                $colorClasses = [
                                    'primary' => 'primary',
                                    'danger' => 'danger', 
                                    'warning' => 'warning',
                                    'success' => 'success',
                                    'info' => 'info',
                                    'secondary' => 'secondary'
                                ];
                                $colorIndex = 0;
                                $colorKeys = array_keys($colorClasses);
                            @endphp
                            @foreach($financeTypes as $type)
                                @php
                                    $colorKey = $colorKeys[$colorIndex % count($colorKeys)];
                                    $colorClass = $colorClasses[$colorKey];
                                    $formCheckClass = '';
                                    if ($colorClass === 'danger') {
                                        $formCheckClass = 'form-check-danger';
                                    } elseif ($colorClass === 'warning') {
                                        $formCheckClass = 'form-check-warning';
                                    } elseif ($colorClass === 'success') {
                                        $formCheckClass = 'form-check-success';
                                    } elseif ($colorClass === 'info') {
                                        $formCheckClass = 'form-check-info';
                                    }
                                    $colorIndex++;
                                @endphp
                                <div class="form-check mb-2 {{ $formCheckClass }}">
                                    <input class="form-check-input input-filter" type="checkbox" 
                                        id="select-type-{{ $type->id }}" 
                                        data-value="{{ $type->id }}" 
                                        data-color="{{ $colorClass }}"
                                        checked />
                                    <label class="form-check-label" for="select-type-{{ $type->id }}">
                                        {{ $type->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <!-- /Calendar Sidebar -->

                <!-- Calendar & Modal -->
                <div class="col app-calendar-content">
                    <div class="card shadow-none border-0">
                        <div class="card-body pb-0">
                            <!-- FullCalendar -->
                            <div id="calendar"></div>
                        </div>
                    </div>
                    <div class="app-overlay"></div>
                </div>
                <!-- /Calendar & Modal -->
            </div>
        </div>
        
        <!-- FullCalendar Offcanvas -->
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
                        <div>
                            <button type="button" class="btn btn-label-danger d-none" id="btnDeleteExpense">Xóa</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/fullcalendar/fullcalendar.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/moment/moment.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script>
    // Hàm format số với dấu chấm phân cách hàng nghìn
    function formatNumber(num) {
        if (!num && num !== 0) return '';
        const numStr = num.toString().replace(/\./g, ''); // Loại bỏ dấu chấm cũ
        return numStr.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    
    // Hàm unformat số (loại bỏ dấu chấm)
    function unformatNumber(str) {
        if (!str) return '';
        return str.toString().replace(/\./g, '');
    }
    
    $(document).ready(function() {
        const monthId = {{ $financeMonth->id }};
        const year = {{ $year->year }};
        const month = {{ $financeMonth->month }};
        const isLocked = {{ $financeMonth->isLocked() ? 'true' : 'false' }};
        const expenseIndexUrl = '{{ route("admin.finance.months.expenses.index", $financeMonth->id) }}';
        const expenseStoreUrl = '{{ route("admin.finance.months.expenses.store", $financeMonth->id) }}';
        const expenseUpdateUrl = '{{ route("admin.finance.months.expenses.update", ["monthId" => $financeMonth->id, "id" => ":id"]) }}';
        const expenseDeleteUrl = '{{ route("admin.finance.months.expenses.destroy", ["monthId" => $financeMonth->id, "id" => ":id"]) }}';
        const monthUpdateUrl = '{{ route("admin.finance.months.update", $financeMonth->id) }}';
        
        // Khởi tạo flatpickr cho date picker với format d/m/y
        $('#expenseDate').flatpickr({
            dateFormat: 'd/m/Y',
            allowInput: true,
            locale: {
                firstDayOfWeek: 1
            }
        });
        
        // Format money khi nhập (cho các input có class format-money)
        $('.format-money').on('input', function() {
            let val = this.value;
            
            // 1. Xoá tất cả ký tự không phải số
            val = val.replace(/\D/g, '');
            
            // 2. Bỏ số 0 ở đầu (chỉ cho phép "0")
            val = val.replace(/^0+(?!$)/, '');
            
            // 3. Giới hạn tối đa 15 số (để hỗ trợ số lớn)
            val = val.substring(0, 15);
            
            // 4. Thêm dấu phân tách hàng nghìn (quan trọng: phải xoá chấm cũ trước khi thêm lại)
            val = val.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            this.value = val;
        });
        
        let calendarEl = document.getElementById('calendar');
        let calendar;
        let expenseSidebar = new bootstrap.Offcanvas(document.getElementById('addExpenseSidebar'));
        let currentExpenseId = null;
        let isInitialLoad = true; // Flag để tránh redirect khi load lần đầu
        let saveTimeout = null; // Timeout cho auto save
        
        // Màu cho từng loại chi phí
        const financeTypeColors = {
            @foreach($financeTypes as $index => $type)
                @php
                    $colorClasses = ['primary', 'danger', 'warning', 'success', 'info', 'secondary'];
                    $colorClass = $colorClasses[$index % count($colorClasses)];
                @endphp
                {{ $type->id }}: '{{ $colorClass }}',
            @endforeach
        };
        
        // Dữ liệu events từ finance_days (allEvents chứa tất cả, events sẽ được filter)
        let allEvents = [
            @foreach($financeDays as $day)
            {
                id: '{{ $day->id }}',
                title: '{{ addslashes($day->financeType->name ?? "Chưa phân loại") }}: {{ number_format($day->money) }}đ',
                start: '{{ $day->date->format("Y-m-d") }}',
                extendedProps: {
                    finance_type_id: {{ $day->finance_type_id }},
                    finance_type_name: '{{ addslashes($day->financeType->name ?? "Chưa phân loại") }}',
                    money: {{ $day->money }},
                    note: {!! json_encode($day->note ?? '') !!}
                }
            },
            @endforeach
        ];
        
        // Khởi tạo calendar
        calendar = new Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            initialDate: year + '-' + String(month).padStart(2, '0') + '-01',
            plugins: [dayGridPlugin, interactionPlugin],
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: ''
            },
            validRange: {
                start: moment('2026-01-01').startOf('month').toDate(), // Bắt đầu từ tháng 1/2026
                end: moment().endOf('month').toDate() // Chỉ cho phép xem đến cuối tháng hiện tại
            },
            events: function(fetchInfo, successCallback) {
                // Filter events theo finance_type_id đã chọn
                const selectedTypes = getSelectedFinanceTypes();
                let filteredEvents;
                if (selectedTypes === 'all') {
                    filteredEvents = allEvents;
                } else {
                    filteredEvents = allEvents.filter(function(event) {
                        return selectedTypes.includes(event.extendedProps.finance_type_id.toString());
                    });
                }
                successCallback(filteredEvents);
            },
            locale: 'vi',
            fixedWeekCount: false, // Hiển thị tất cả các tuần trong tháng
            showNonCurrentDates: true, // Hiển thị các ngày của tháng khác
            navLinks: true, // Cho phép click vào ngày để chuyển view
            datesSet: function(dateInfo) {
                // Bỏ qua lần load đầu tiên
                if (isInitialLoad) {
                    isInitialLoad = false;
                    return;
                }
                
                // Khi user chuyển tháng trên calendar, chuyển hướng đến tháng đó
                const currentMonth = dateInfo.view.currentStart.getMonth() + 1; // getMonth() trả về 0-11
                const currentYear = dateInfo.view.currentStart.getFullYear();
                
                // Chỉ chuyển hướng nếu tháng/năm khác với tháng hiện tại
                if (currentMonth !== month || currentYear !== year) {
                    if (currentYear !== year) {
                        // Nếu khác năm, tìm hoặc tạo year mới
                        $.ajax({
                            url: '{{ route("admin.finance.years.getByYear") }}',
                            method: 'GET',
                            data: { year: currentYear },
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            success: function(response) {
                                if (response.status && response.data) {
                                    const yearId = response.data.id;
                                    const monthUrl = '{{ route("admin.finance.years.months.show", ["yearId" => ":yearId", "month" => ":month"]) }}'
                                        .replace(':yearId', yearId)
                                        .replace(':month', currentMonth);
                                    window.location.href = monthUrl;
                                }
                            },
                            error: function() {
                                toastr.error('Không thể tải năm ' + currentYear, "Đã có lỗi xảy ra");
                            }
                        });
                    } else {
                        // Cùng năm, chỉ cần chuyển tháng
                        const monthUrl = '{{ route("admin.finance.years.months.show", ["yearId" => $year->id, "month" => ":month"]) }}'
                            .replace(':month', currentMonth);
                        window.location.href = monthUrl;
                    }
                }
            },
            eventClassNames: function({ event }) {
                const typeId = event.extendedProps.finance_type_id;
                const colorName = financeTypeColors[typeId] || 'secondary';
                return ['fc-event-' + colorName, 'text-white']; // Thêm text-white để chữ trắng, dễ đọc
            },
            dateClick: function(info) {
                // Kiểm tra nếu tháng đã bị lock
                if (isLocked) {
                    toastr.warning('Tháng này đã bị khóa, không thể thêm chi tiêu', "Thông báo");
                    return;
                }
                // Khi click vào ngày, mở offcanvas và fill ngày
                const clickedDate = moment(info.dateStr).format('DD/MM/YYYY');
                document.getElementById('expenseDate').value = clickedDate;
                document.getElementById('expenseType').value = '';
                document.getElementById('expenseMoney').value = '';
                document.getElementById('expenseNote').value = '';
                currentExpenseId = null;
                document.getElementById('addExpenseSidebarLabel').textContent = 'Thêm chi tiêu';
                document.getElementById('btnDeleteExpense').classList.add('d-none');
                expenseSidebar.show();
            },
            eventClick: function(info) {
                // Kiểm tra nếu tháng đã bị lock
                if (isLocked) {
                    toastr.warning('Tháng này đã bị khóa, không thể chỉnh sửa chi tiêu', "Thông báo");
                    return;
                }
                // Khi click vào event, mở offcanvas để chỉnh sửa
                const event = info.event;
                currentExpenseId = event.id;
                const eventDate = moment(event.start).format('DD/MM/YYYY');
                
                document.getElementById('expenseDate').value = eventDate;
                document.getElementById('expenseType').value = event.extendedProps.finance_type_id;
                document.getElementById('expenseMoney').value = formatNumber(event.extendedProps.money);
                document.getElementById('expenseNote').value = event.extendedProps.note || '';
                document.getElementById('addExpenseSidebarLabel').textContent = 'Chỉnh sửa chi tiêu';
                document.getElementById('btnDeleteExpense').classList.remove('d-none');
                expenseSidebar.show();
            }
        });
        
        calendar.render();
        
        // Hàm lấy danh sách finance_type_id đã chọn
        function getSelectedFinanceTypes() {
            const selectAll = document.querySelector('.select-all-filter');
            if (selectAll && selectAll.checked) {
                return 'all';
            }
            
            const filterInputs = document.querySelectorAll('.input-filter:checked');
            const selected = [];
            filterInputs.forEach(function(item) {
                selected.push(item.getAttribute('data-value'));
            });
            return selected.length > 0 ? selected : 'all';
        }
        
        // Hàm load lại events từ server
        function reloadEvents() {
            $.ajax({
                url: expenseIndexUrl,
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.status && response.data) {
                        // Cập nhật allEvents với dữ liệu mới
                        allEvents = response.data.map(function(event) {
                            return {
                                id: event.id.toString(),
                                title: event.title,
                                start: event.start,
                                extendedProps: event.extendedProps
                            };
                        });
                        // Refresh calendar
                        calendar.refetchEvents();
                        // Cập nhật remaining_money sau khi reload events
                        updateRemainingMoney();
                    }
                },
                error: function(xhr) {
                    console.error('Error loading events:', xhr);
                    toastr.error('Có lỗi xảy ra khi tải lại dữ liệu', "Đã có lỗi xảy ra");
                }
            });
        }
        
        // Cập nhật remaining_money dựa trên allEvents và totalMoney
        function updateRemainingMoney() {
            // Tính tổng chi phí từ allEvents
            let totalExpenses = 0;
            allEvents.forEach(function(event) {
                totalExpenses += parseFloat(event.extendedProps.money) || 0;
            });
            
            // Tính remaining_money (unformat giá trị input trước)
            const totalMoney = parseFloat(unformatNumber($('#totalMoney').val())) || 0;
            const remainingMoney = totalMoney - totalExpenses;
            $('#remainingMoney').val(formatNumber(remainingMoney));
            
            // Cập nhật lại remaining_money trên server
            $.ajax({
                url: monthUpdateUrl,
                method: 'PUT',
                data: {
                    total_money: totalMoney
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(updateResponse) {
                    if (updateResponse.status && updateResponse.data) {
                        $('#remainingMoney').val(formatNumber(updateResponse.data.remaining_money));
                    }
                }
            });
        }
        
        // Xử lý filter events
        function filterEvents() {
            calendar.refetchEvents();
        }
        
        // Select all filter
        $('.select-all-filter').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.input-filter').prop('checked', isChecked);
            filterEvents();
        });
        
        // Individual filter
        $('.input-filter').on('change', function() {
            const allChecked = $('.input-filter:checked').length === $('.input-filter').length;
            $('.select-all-filter').prop('checked', allChecked);
            filterEvents();
        });
        
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
            currentExpenseId = null;
            $('#addExpenseSidebarLabel').text('Thêm chi tiêu');
            $('#btnDeleteExpense').addClass('d-none');
            expenseSidebar.show();
        });
        
        // Submit form
        $('#expenseForm').on('submit', function(e) {
            e.preventDefault();
            
            const $btn = $('#btnSaveExpense');
            const originalHtml = $btn.html();
            $btn.prop('disabled', true).find('.spinner-border').removeClass('d-none');
            
            // Convert date từ d/m/Y sang Y-m-d để gửi lên server
            const dateValue = $('#expenseDate').val();
            let dateFormatted = dateValue;
            if (dateValue) {
                // Nếu format là d/m/Y, convert sang Y-m-d
                const dateParts = dateValue.split('/');
                if (dateParts.length === 3) {
                    dateFormatted = dateParts[2] + '-' + dateParts[1] + '-' + dateParts[0];
                }
            }
            
            const formData = {
                date: dateFormatted,
                finance_type_id: $('#expenseType').val(),
                money: unformatNumber($('#expenseMoney').val()), // Unformat trước khi gửi
                note: $('#expenseNote').val()
            };
            
            const url = currentExpenseId 
                ? expenseUpdateUrl.replace(':id', currentExpenseId)
                : expenseStoreUrl;
            const method = currentExpenseId ? 'PUT' : 'POST';
            
            $.ajax({
                url: url,
                method: method,
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.status) {
                        toastr.success(response.message, "Thông báo");
                        expenseSidebar.hide();
                        // Load lại events từ server
                        reloadEvents();
                        // reloadEvents() sẽ tự động cập nhật remaining_money
                    } else {
                        toastr.error(response.message || 'Có lỗi xảy ra', "Đã có lỗi xảy ra");
                    }
                    $btn.prop('disabled', false).html(originalHtml);
                },
                error: function(xhr) {
                    let message = 'Có lỗi xảy ra khi lưu chi tiêu.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    toastr.error(message, "Đã có lỗi xảy ra");
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        });
        
        // Delete expense
        $('#btnDeleteExpense').on('click', function() {
            if (!currentExpenseId) return;
            
            if (!confirm('Bạn có chắc chắn muốn xóa chi tiêu này?')) {
                return;
            }
            
            const $btn = $(this);
            const originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Đang xóa...');
            
            $.ajax({
                url: expenseDeleteUrl.replace(':id', currentExpenseId),
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.status) {
                        toastr.success(response.message, "Thông báo");
                        expenseSidebar.hide();
                        // Load lại events từ server
                        reloadEvents();
                        // reloadEvents() sẽ tự động cập nhật remaining_money
                    } else {
                        toastr.error(response.message || 'Có lỗi xảy ra', "Đã có lỗi xảy ra");
                    }
                    $btn.prop('disabled', false).html(originalHtml);
                },
                error: function(xhr) {
                    let message = 'Có lỗi xảy ra khi xóa chi tiêu.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    toastr.error(message, "Đã có lỗi xảy ra");
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        });
        
        // Auto save thông tin tháng khi người dùng nhập xong
        function calculateRemainingMoney() {
            // Unformat giá trị input trước khi tính
            const totalMoney = parseFloat(unformatNumber($('#totalMoney').val())) || 0;
            // Lấy tổng chi phí từ allEvents
            let totalExpenses = 0;
            allEvents.forEach(function(event) {
                totalExpenses += parseFloat(event.extendedProps.money) || 0;
            });
            const remainingMoney = totalMoney - totalExpenses;
            $('#remainingMoney').val(formatNumber(remainingMoney));
        }
        
        function saveMonthInfo(field, value) {
            // Clear timeout trước đó
            if (saveTimeout) {
                clearTimeout(saveTimeout);
            }
            
            // Tính lại remaining_money
            calculateRemainingMoney();
            
            // Tạo data object (unformat giá trị trước khi gửi)
            const data = {};
            data[field] = parseFloat(unformatNumber(value)) || 0;
            
            // Debounce: Đợi 1 giây sau khi người dùng ngừng gõ
            saveTimeout = setTimeout(function() {
                $.ajax({
                    url: monthUpdateUrl,
                    method: 'PUT',
                    data: data,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        if (response.status && response.data) {
                            // Cập nhật remaining_money từ server (format lại)
                            $('#remainingMoney').val(formatNumber(response.data.remaining_money));
                        }
                    },
                    error: function(xhr) {
                        console.error('Error saving month info:', xhr);
                    }
                });
            }, 1000); // Đợi 1 giây
        }
        
        // Xử lý khi người dùng nhập vào các input
        $('#totalMoney').on('input blur', function() {
            if (isLocked) {
                return; // Không cho phép chỉnh sửa nếu đã lock
            }
            const field = $(this).data('field');
            const value = $(this).val();
            
            if (field === 'total_money') {
                calculateRemainingMoney();
            }
            
            if ($(this).is(':focus') === false || $(event.originalEvent.type) === 'blur') {
                saveMonthInfo(field, value);
            }
        });
        
        // Tính toán remaining_money khi load trang
        calculateRemainingMoney();
    });
    </script>
@endpush
