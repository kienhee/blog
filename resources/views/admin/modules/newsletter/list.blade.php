@extends('admin.layouts.master')
@section('title', 'Quản lý đăng ký newsletter')
@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet"
        href="{{ asset_admin_url('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
@endpush
@section('content')
    <section>
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
            <div>
                <h4 class="mb-1 mt-3">Quản lý đăng ký newsletter</h4>
                <p class="text-muted">Quản lý danh sách email đăng ký nhận tin tức</p>
            </div>
        </div>
        @include('admin.components.showMessage')
        <div class="card">
            <div class="card-header border-bottom">
                <h5 class="card-title">Bộ lọc</h5>
                <div class="d-flex align-items-center row pt-4 gap-6 gap-md-0 g-md-6">
                    <div class="col-md-3 mb-2">
                        <label for="email" class="form-label mb-1">Email</label>
                        <input type="text" id="email" class="form-control" placeholder="Nhập email" />
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="status" class="form-label mb-1">Trạng thái</label>
                        <select id="status" class="form-select text-capitalize">
                            <option value="">Tất cả</option>
                            @foreach ($statusLabels as $status => $label)
                                <option value="{{ $status }}">
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-primary" id="clearFilter">Đặt lại</button>
                    </div>
                </div>
            </div>
            <div class="card-datatable table-responsive">
                <table class="table border-top" id="datatable_newsletter" data-url="{{ route('admin.newsletters.ajaxGetData') }}">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Email</th>
                            <th>Trạng thái</th>
                            <th>Ngày đăng ký</th>
                            <th>Ngày tạo</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <!-- Modal xác nhận xóa -->
        <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmDeleteModalLabel">Xác nhận xóa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Bạn có chắc chắn muốn xóa email đăng ký này không?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Xóa</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@push('scripts')
    <script src="{{ asset_admin_url('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.js') }}"></script>
    @vite(['resources/js/admin/pages/newsletter/list.js'])
    <script>
        window.newsletterListUrl = "{{ route('admin.newsletters.ajaxGetData') }}";
    </script>
@endpush

