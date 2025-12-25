@extends('admin.layouts.master')
@section('title', 'Danh sách hashtag')
@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet"
        href="{{ asset_admin_url('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/animate-css/animate.css') }}" />
@endpush
@section('content')
    <section>
        @include('admin.components.headingPage', [
            'description' => 'Quản lý hashtag cho bài viết',
            'button' => 'add',
            'buttonLink' => 'admin.hashtags.create',
        ])

        <div class="card">
            <div class="card-header border-bottom">
                <div class="d-flex align-items-end row gap-6 gap-md-0 g-md-6">
                    <div class="col-md-3 mb-2">
                        <label for="created_at" class="form-label mb-1">Ngày tạo</label>
                        <input type="text" id="created_at" class="form-control date-picker" placeholder="DD/MM/YYYY" />
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-primary" id="clearFilter">Đặt lại</button>
                    </div>
                </div>
            </div>
            {{-- tabs --}}
            <div class="nav-align-top">
                <ul class="nav nav-tabs nav-fill" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link rounded-0 border-0 active" role="tab"
                            data-bs-toggle="tab" data-bs-target="#hashtags_tab" aria-controls="hashtags_tab"
                            aria-selected="true">
                            <i class="bx bx-list-ul me-1"></i> Danh sách
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link rounded-0 border-0" role="tab" data-bs-toggle="tab"
                            data-bs-target="#trash_tab" aria-controls="trash_tab" aria-selected="false">
                            <i class="bx bx-trash me-1"></i> Thùng rác
                        </button>
                    </li>
                </ul>
                <div class="tab-content border-0">
                    <div class="tab-pane fade show active" id="hashtags_tab" role="tabpanel">
                        <div class="card-header border-bottom mb-3" id="bulkActionsContainerHashtags"
                            style="display: none;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted" id="selectedCountHashtags">Đã chọn: <strong>0</strong>
                                        mục</span>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtnHashtags">
                                        <i class="bx bx-trash me-1"></i> Xóa đã chọn
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-datatable table-responsive">
                            <table class="table border-top" id="hashtag_datatable"
                                data-url="{{ route('admin.hashtags.ajaxGetData') }}">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" class="form-check-input" id="selectAllHashtags" />
                                        </th>
                                        <th>STT</th>
                                        <th>Tên</th>
                                        <th>Slug</th>
                                        <th>Ngày tạo</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="trash_tab" role="tabpanel">
                        <div class="card-header border-bottom mb-3" id="bulkActionsContainer" style="display: none;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted" id="selectedCount">Đã chọn: <strong>0</strong> mục</span>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-success btn-sm me-2" id="bulkRestoreBtn">
                                        <i class="bx bx-undo me-1"></i> Khôi phục đã chọn
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" id="bulkForceDeleteBtn">
                                        <i class="bx bx-trash me-1"></i> Xóa vĩnh viễn đã chọn
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-datatable table-responsive">
                            <table class="table border-top" id="hashtag_datatable_trash"
                                data-url="{{ route('admin.hashtags.ajaxGetTrashedData') }}">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" class="form-check-input" id="selectAllTrash" />
                                        </th>
                                        <th>STT</th>
                                        <th>Tên</th>
                                        <th>Slug</th>
                                        <th>Ngày xóa</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmDeleteLabel">Xác nhận xóa hashtag</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning mb-0">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Thông báo:</strong> Bạn sắp xóa hashtag "<span id="deleteTitle"
                                class="fw-bold"></span>".<br>
                            Hashtag sẽ được chuyển vào <strong>Thùng rác</strong> và bạn có thể khôi phục lại sau.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            Xóa
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Delete Confirmation Modal (Main List) -->
        <div class="modal fade" id="bulkDeleteModalHashtags" tabindex="-1" aria-labelledby="bulkDeleteLabelHashtags"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkDeleteLabelHashtags">Xác nhận xóa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning mb-0">
                            <i class="bx bx-info-circle me-2"></i>
                            Bạn có chắc chắn muốn xóa <strong id="bulkDeleteCountHashtags">0</strong> hashtag đã chọn?<br>
                            <small class="d-block mt-2">Các hashtag sẽ được chuyển vào <strong>Thùng rác</strong> và bạn
                                có thể khôi phục lại sau.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-danger" id="confirmBulkDeleteBtnHashtags">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            Xóa
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Restore Confirmation Modal -->
        <div class="modal fade" id="confirmRestoreModal" tabindex="-1" aria-labelledby="confirmRestoreLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmRestoreLabel">Xác nhận khôi phục hashtag</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Bạn có chắc chắn muốn khôi phục hashtag "<strong id="restoreTitle" class="text-limit-1"
                            style="word-break: break-word; white-space: normal; display: inline;"></strong>"?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-success" id="confirmRestoreBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            Khôi phục
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Force Delete Confirmation Modal -->
        <div class="modal fade" id="confirmForceDeleteModal" tabindex="-1" aria-labelledby="confirmForceDeleteLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmForceDeleteLabel">Xác nhận xóa vĩnh viễn hashtag</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger mb-0">
                            <i class="bx bx-error-circle me-2"></i>
                            <strong>Cảnh báo:</strong> Bạn sắp xóa vĩnh viễn hashtag "<strong id="forceDeleteTitle"
                                class="text-limit-1"
                                style="word-break: break-word; white-space: normal; display: inline;"></strong>".<br>
                            Hành động này không thể hoàn tác và sẽ xóa vĩnh viễn khỏi hệ thống.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-danger" id="confirmForceDeleteBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            Xóa vĩnh viễn
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Bulk Restore Confirmation Modal -->
        <div class="modal fade" id="bulkRestoreModal" tabindex="-1" aria-labelledby="bulkRestoreLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkRestoreLabel">Xác nhận khôi phục</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info mb-0">
                            <i class="bx bx-info-circle me-2"></i>
                            Bạn có chắc chắn muốn khôi phục <strong id="bulkRestoreCount">0</strong> hashtag đã chọn?
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-success" id="confirmBulkRestoreBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            Khôi phục
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Force Delete Confirmation Modal -->
        <div class="modal fade" id="bulkForceDeleteModal" tabindex="-1" aria-labelledby="bulkForceDeleteLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkForceDeleteLabel">Xác nhận xóa vĩnh viễn</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger mb-0">
                            <i class="bx bx-error-circle me-2"></i>
                            <strong>Cảnh báo:</strong> Bạn có chắc chắn muốn xóa vĩnh viễn <strong
                                id="bulkForceDeleteCount">0</strong> hashtag đã chọn?<br>
                            <small class="d-block mt-2">Hành động này không thể hoàn tác và sẽ xóa vĩnh viễn khỏi hệ
                                thống.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-danger" id="confirmBulkForceDeleteBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            Xóa vĩnh viễn
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
    @vite(['resources/js/admin/pages/hashtag/list.js'])
    <script>
        // Define routes for bulk actions
        window.hashtagBulkDeleteUrl = "{{ route('admin.hashtags.bulkDelete') }}";
        window.hashtagBulkRestoreUrl = "{{ route('admin.hashtags.bulkRestore') }}";
        window.hashtagBulkForceDeleteUrl = "{{ route('admin.hashtags.bulkForceDelete') }}";
    </script>
@endpush
