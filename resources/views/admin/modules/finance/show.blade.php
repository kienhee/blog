@extends('admin.layouts.master')
@section('title', 'Chi tiết năm ' . $year->year)

@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
    <style>
        .target-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .target-item input[type="text"] {
            flex: 1;
        }
        .target-item.disabled {
            background-color: #f5f5f5;
        }
        .target-item input[type="text"]:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }
        .target-actions {
            display: flex;
            gap: 5px;
        }
        .card-hover {
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: #696cff;
        }
        .card-hover .card-body {
            color: inherit;
        }
        .card-disabled {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }
        .card-disabled:hover {
            transform: none;
            box-shadow: none;
            border-color: #e0e0e0;
        }
        .card-disabled .card-body {
            color: #999;
        }
    </style>
@endpush

@section('content')
<section>
    @include('admin.components.headingPage', [
        'description' => 'Chi tiết năm ' . $year->year,
        'button' => 'back',
        'buttonLink' => route('admin.finance.years.list'),
    ])

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                Năm: <strong>{{ $year->year }}</strong>
            </h5>
            <button type="button" class="btn btn-sm btn-primary" id="editBtn">
                <i class="bx bx-edit me-1"></i> Chỉnh sửa
            </button>
        </div>
        <hr/>
        <div class="card-body">
            <div class="row">
                <!-- Ghi chú -->
                <div class="col-md-6 mb-4">
                    <h6 class="mb-3">Ghi chú</h6>
                    <div id="noteDisplay" class="mb-3">
                        <p class="text-muted mb-0" id="noteContent">
                            @if ($year->note)
                                {{ $year->note }}
                            @else
                                <em>Chưa có ghi chú</em>
                            @endif
                        </p>
                    </div>
                    <div id="noteEdit" style="display: none;">
                        <textarea class="form-control" id="noteTextarea" rows="5" placeholder="Nhập ghi chú...">{{ $year->note ?? '' }}</textarea>
                    </div>
                </div>

                <!-- Mục tiêu -->
                <div class="col-md-6 mb-4">
                    <h6 class="mb-3">Mục tiêu năm</h6>
                    <form id="targetForm">
                        <div id="targetList">
                            @if (count($year->target) > 0)
                                @foreach ($year->target as $index => $item)
                                    <div class="target-item disabled" data-index="{{ $index }}">
                                        <input type="checkbox" class="form-check-input target-checkbox"
                                            {{ isset($item['completed']) && $item['completed'] ? 'checked' : '' }}
                                            disabled>
                                        <input type="text" class="form-control form-control-sm target-name"
                                            value="{{ $item['name'] ?? '' }}" placeholder="Nhập mục tiêu..." disabled>
                                        <button type="button" class="btn btn-sm btn-danger remove-target-btn"
                                            style="display: none;">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </div>
                                @endforeach
                            @else
                                <!-- Mặc định 2 trường -->
                                <div class="target-item disabled" data-index="0">
                                    <input type="checkbox" class="form-check-input target-checkbox" disabled>
                                    <input type="text" class="form-control form-control-sm target-name"
                                           placeholder="Nhập mục tiêu..." disabled>
                                    <button type="button" class="btn btn-sm btn-danger remove-target-btn"
                                            style="display: none;">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                                <div class="target-item disabled" data-index="1">
                                    <input type="checkbox" class="form-check-input target-checkbox" disabled>
                                    <input type="text" class="form-control form-control-sm target-name"
                                           placeholder="Nhập mục tiêu..." disabled>
                                    <button type="button" class="btn btn-sm btn-danger remove-target-btn"
                                            style="display: none;">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                        <div id="addTargetBtn" style="display: none;" class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-primary">
                                <i class="bx bx-plus me-1"></i> Thêm mục tiêu
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Actions buttons chung -->
            <div class="mt-3" id="editActions" style="display: none;">
                <button type="button" class="btn btn-success" id="saveAllBtn">
                    <i class="bx bx-save me-1"></i> Lưu tất cả
                </button>
                <button type="button" class="btn btn-secondary" id="cancelEditBtn">
                    <i class="bx bx-x me-1"></i> Hủy
                </button>
            </div>
            <hr/>
            <div class="card-header">
                <h5 class="card-title mb-0">Danh sách tháng năm {{ $year->year }}</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @php
                        $monthNames = [
                            1 => 'Tháng 1',
                            2 => 'Tháng 2',
                            3 => 'Tháng 3',
                            4 => 'Tháng 4',
                            5 => 'Tháng 5',
                            6 => 'Tháng 6',
                            7 => 'Tháng 7',
                            8 => 'Tháng 8',
                            9 => 'Tháng 9',
                            10 => 'Tháng 10',
                            11 => 'Tháng 11',
                            12 => 'Tháng 12',
                        ];
                    @endphp
                    @for ($month = 1; $month <= 12; $month++)
                        @php
                            $isExisting = in_array($month, $existingMonths ?? []);
                            $canCreate = in_array($month, $creatableMonths ?? []);
                            $isDisabled = !$isExisting && !$canCreate;
                        @endphp
                        <div class="col-md-3 col-sm-4 col-6">
                            @if ($isDisabled)
                                <div class="card card-disabled h-100">
                                    <div class="card-body text-center">
                                        <h6 class="card-title mb-0">{{ $monthNames[$month] }}/{{ $year->year }}</h6>
                                        <span class="badge bg-label-warning mt-2">Chưa thể tạo</span>
                                    </div>
                                </div>
                            @else
                                <a href="{{ route('admin.finance.years.months.show', ['yearId' => $year->id, 'month' => $month]) }}"
                                   class="card card-hover h-100 text-decoration-none">
                                    <div class="card-body text-center">
                                        <h6 class="card-title mb-0">{{ $monthNames[$month] }}/{{ $year->year }}</h6>
                                        @if ($isExisting)
                                            <span class="badge bg-label-success mt-2">Đã có dữ liệu</span>
                                        @else
                                            <span class="badge bg-label-secondary mt-2">Chưa có dữ liệu</span>
                                        @endif
                                    </div>
                                </a>
                            @endif
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        <!-- Danh sách tháng -->
        <div class="card mt-4">
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.js') }}"></script>
<script>
$(document).ready(function() {
    let originalNote = $('#noteTextarea').val();

    // Thêm mục tiêu mới
    function addTargetItem() {
        const index = $('#targetList .target-item').length;
        const itemHtml = `
            <div class="target-item" data-index="${index}">
                <input type="checkbox" class="form-check-input target-checkbox">
                <input type="text" class="form-control target-name" placeholder="Nhập mục tiêu...">
                <button type="button" class="btn btn-sm btn-danger remove-target-btn">
                    <i class="bx bx-trash"></i>
                </button>
            </div>
        `;
        $('#targetList').append(itemHtml);
    }

    // Xóa mục tiêu
    $(document).on('click', '.remove-target-btn', function() {
        $(this).closest('.target-item').remove();
    });

    // Bật chế độ chỉnh sửa (chung cho cả 2 phần)
    $('#editBtn').on('click', function() {
        originalNote = $('#noteTextarea').val();
        $('#targetList .target-item').removeClass('disabled');
        $('#targetList input').prop('disabled', false);
        $('#targetList .remove-target-btn').show();
        $('#addTargetBtn').show();
        $('#noteDisplay').hide();
        $('#noteEdit').show();
        $('#editBtn').hide();
        $('#editActions').show();
    });

    // Xử lý thêm mục tiêu
    $('#addTargetBtn button').on('click', function() {
        addTargetItem();
    });

    // Hủy chỉnh sửa
    $('#cancelEditBtn').on('click', function() {
        window.location.reload();
    });

    // Lưu tất cả (cả mục tiêu và ghi chú)
    $('#saveAllBtn').on('click', function() {
        const $btn = $(this);
        const originalHtml = $btn.html();

        $btn.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-2"></span>Đang lưu...'
        );

        // Lấy dữ liệu mục tiêu
        const targets = [];
        $('#targetList .target-item').each(function() {
            const name = $(this).find('.target-name').val().trim();
            const completed = $(this).find('.target-checkbox').is(':checked');
            if (name) {
                targets.push({
                    name: name,
                    completed: completed ? 1 : 0
                });
            }
        });

        // Lấy dữ liệu ghi chú
        const noteContent = $('#noteTextarea').val();

        // Gửi 2 request AJAX song song
        const targetRequest = $.ajax({
            url: '{{ route('admin.finance.years.updateTarget', $year->id) }}',
            method: 'PUT',
            data: {
                target: targets,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const noteRequest = $.ajax({
            url: '{{ route('admin.finance.years.updateNote', $year->id) }}',
            method: 'PUT',
            data: {
                note: noteContent,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        // Đợi cả 2 request hoàn thành
        $.when(targetRequest, noteRequest).done(function(targetResponse, noteResponse) {
            const targetSuccess = targetResponse[0].status;
            const noteSuccess = noteResponse[0].status;

            if (targetSuccess && noteSuccess) {
                toastr.success('Cập nhật thành công', "Thông báo");

                $('#targetList .target-item').addClass('disabled');
                $('#targetList input').prop('disabled', true);
                $('#targetList .remove-target-btn').hide();
                $('#addTargetBtn').hide();
                $('#noteEdit').hide();
                $('#noteDisplay').show();

                if (noteContent.trim()) {
                    $('#noteContent').text(noteContent);
                } else {
                    $('#noteContent').html('<em>Chưa có ghi chú</em>');
                }

                $('#editBtn').show();
                $('#editActions').hide();

                setTimeout(function() {
                    window.location.reload();
                }, 500);
            } else {
                let message = 'Có lỗi xảy ra khi lưu.';
                if (!targetSuccess && targetResponse[0].message) {
                    message = targetResponse[0].message;
                } else if (!noteSuccess && noteResponse[0].message) {
                    message = noteResponse[0].message;
                }
                toastr.error(message, "Đã có lỗi xảy ra");
            }
            $btn.prop('disabled', false).html(originalHtml);
        }).fail(function(xhr) {
            let message = 'Có lỗi xảy ra khi lưu.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            toastr.error(message, "Đã có lỗi xảy ra");
            $btn.prop('disabled', false).html(originalHtml);
        });
    });
});
</script>
@endpush
