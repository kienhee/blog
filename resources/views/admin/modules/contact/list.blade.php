@extends('admin.layouts.master')
@section('title', 'Quản lý liên hệ')
@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet"
        href="{{ asset_admin_url('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
    <style>
        .dropdown-toggle::after {
            display: none !important;
        }
    </style>
@endpush
@section('content')
    <section>
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
            <div>
                <h4 class="mb-1 mt-3">Quản lý liên hệ</h4>
                <p class="text-muted">Quản lý các liên hệ từ khách hàng</p>
            </div>
        </div>
        @include('admin.components.showMessage')
        <div class="card">
            <div class="card-header border-bottom">
                <h5 class="card-title">Bộ lọc</h5>
                <div class="d-flex align-items-center row pt-4 gap-6 gap-md-0 g-md-6">
                    <div class="col-md-3 mb-2">
                        <label for="full_name" class="form-label mb-1">Họ tên</label>
                        <input type="text" id="full_name" class="form-control" placeholder="Nhập họ tên" />
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="email" class="form-label mb-1">Email</label>
                        <input type="text" id="email" class="form-control" placeholder="Nhập email" />
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="subject" class="form-label mb-1">Chủ đề</label>
                        <input type="text" id="subject" class="form-control" placeholder="Nhập chủ đề" />
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="status" class="form-label mb-1">Trạng thái</label>
                        <select id="status" class="form-select text-capitalize">
                            <option value="">Tất cả</option>
                            @foreach ($statusLabels as $status => $label)
                                <option value="{{ $status }}" @if ($status == 0) selected @endif>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-2 d-flex align-items-end gap-2">
                        <div class="flex-grow-1">
                            <label for="created_at" class="form-label mb-1">Ngày tạo</label>
                            <input type="text" id="created_at" class="form-control date-picker"
                                placeholder="DD/MM/YYYY" />
                        </div>
                        <button class="btn btn-primary" id="clearFilter">Đặt lại</button>
                    </div>
                </div>
            </div>
            <div class="card-datatable table-responsive">
                <table class="table border-top" id="datatable_contact" data-url="{{ route('admin.contacts.ajaxGetData') }}">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>Số điện thoại</th>
                            <th>Chủ đề</th>
                            <th>Tin nhắn</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <!-- Modal xem chi tiết contact -->
        <div class="modal fade" id="contactDetailModal" tabindex="-1" aria-labelledby="contactDetailModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="contactDetailModalLabel">
                            <i class="bx bx-envelope me-2"></i>Chi tiết liên hệ
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="contactDetailModalBody">
                        <!-- Nội dung sẽ được load qua AJAX -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Đóng</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal xác nhận thay đổi trạng thái -->
        <div class="modal fade" id="confirmChangeStatusModal" tabindex="-1" aria-labelledby="confirmChangeStatusModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmChangeStatusModalLabel">
                            <i class="bx bx-edit me-2"></i>Thay đổi trạng thái
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="confirmStatusMessage">
                            <p class="mb-3">Chọn trạng thái mới cho liên hệ:</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-primary" id="confirmChangeStatusBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            <i class="bx bx-check me-1"></i> Xác nhận
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@push('scripts')
    <script src="{{ asset_admin_url('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.js') }}"></script>
    @vite(['resources/js/admin/pages/contact/list.js', 'resources/js/admin/pages/contact/detail.js'])
    <script>
        // Define routes for contact
        window.contactListUrl = "{{ route('admin.contacts.ajaxGetData') }}";
        window.contactShowUrl = "{{ route('admin.contacts.show', ':id') }}";
        window.contactChangeStatusUrl = "{{ route('admin.contacts.changeStatus', [':id', ':status']) }}";
        window.contactReplyUrl = "{{ route('admin.contacts.reply', ':id') }}";
    </script>
@endpush
