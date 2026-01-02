@extends('admin.layouts.master')
@section('title', 'Quản lý bình luận')
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
                <h4 class="mb-1 mt-3">Quản lý bình luận</h4>
                <p class="text-muted">Quản lý các bình luận từ người dùng</p>
            </div>
        </div>
        @include('admin.components.showMessage')
        <div class="card">
            <div class="card-header border-bottom">
                <h5 class="card-title">Bộ lọc</h5>
                <div class="d-flex align-items-center row pt-4 gap-6 gap-md-0 g-md-6">
                    <div class="col-md-3 mb-2">
                        <label for="status" class="form-label mb-1">Trạng thái</label>
                        <select id="status" class="form-select text-capitalize">
                            <option value="" selected>Tất cả</option>
                            @foreach ($statusLabels as $status => $label)
                                <option value="{{ $status }}">
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
            {{-- tabs --}}
            <div class="nav-align-top">
                <ul class="nav nav-tabs nav-fill" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link rounded-0 border-0 active" role="tab"
                            data-bs-toggle="tab" data-bs-target="#comments_tab" aria-controls="comments_tab" aria-selected="true">
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
                    {{-- Tab Danh sách --}}
                    <div class="tab-pane fade show active" id="comments_tab" role="tabpanel">
                        @canany(['comment.update', 'comment.delete'])
                            <div class="card-header border-bottom mb-3" id="bulkActionsContainerComments" style="display: none;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="text-muted" id="selectedCountComments">Đã chọn: <strong>0</strong> mục</span>
                                    </div>
                                    <div>
                                        @can('comment.update')
                                            <button type="button" class="btn btn-success btn-sm me-2" id="bulkApproveBtn">
                                                <i class="bx bx-check me-1"></i> Duyệt đã chọn
                                            </button>
                                            <button type="button" class="btn btn-warning btn-sm me-2" id="bulkSpamBtn">
                                                <i class="bx bx-shield-x me-1"></i> Đánh dấu spam
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-sm" id="bulkTrashBtn">
                                                <i class="bx bx-trash me-1"></i> Chuyển vào thùng rác
                                            </button>
                                        @endcan
                                        {{-- Xóa nút "Xóa đã chọn" - để xóa vĩnh viễn, phải vào tab "Thùng rác" trước --}}
                                    </div>
                                </div>
                            </div>
                        @endcanany
                        <div class="card-datatable table-responsive">
                            <table class="table border-top" id="datatable_comment" data-url="{{ route('admin.comments.ajaxGetData') }}">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" class="form-check-input" id="selectAllComments" />
                                        </th>
                                        <th>STT</th>
                                        <th>Người dùng</th>
                                        <th>Bài viết</th>
                                        <th>Nội dung</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày tạo</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    {{-- Tab Thùng rác --}}
                    <div class="tab-pane fade" id="trash_tab" role="tabpanel">
                        @canany(['comment.update', 'comment.delete'])
                            <div class="card-header border-bottom mb-3" id="bulkActionsContainerTrash" style="display: none;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="text-muted" id="selectedCountTrash">Đã chọn: <strong>0</strong> mục</span>
                                    </div>
                                    <div>
                                        @can('comment.update')
                                            <button type="button" class="btn btn-success btn-sm me-2" id="bulkRestoreBtn">
                                                <i class="bx bx-undo me-1"></i> Khôi phục đã chọn
                                            </button>
                                        @endcan
                                        @can('comment.delete')
                                            <button type="button" class="btn btn-danger btn-sm" id="bulkForceDeleteBtn">
                                                <i class="bx bx-trash me-1"></i> Xóa vĩnh viễn đã chọn
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        @endcanany
                            <div class="card-datatable table-responsive">
                                <table class="table border-top comments-trash-table" id="datatable_comment_trash" data-url="{{ route('admin.comments.ajaxGetTrashedData') }}">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" class="form-check-input" id="selectAllTrash" />
                                        </th>
                                        <th>STT</th>
                                        <th>Người dùng</th>
                                        <th>Bài viết</th>
                                        <th>Nội dung</th>
                                        <th>Trạng thái</th>
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

            <!-- Modal xem chi tiết và trả lời comment -->
            <div class="modal fade" id="commentDetailModal" tabindex="-1" aria-labelledby="commentDetailModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="commentDetailModalLabel">
                                <i class="bx bx-message-dots me-2"></i>Chi tiết bình luận
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="commentDetailModalBody">
                            <!-- Nội dung sẽ được load qua AJAX -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Đóng</button>
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
                            <h5 class="modal-title" id="confirmDeleteLabel">Xác nhận xóa bình luận</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning mb-0">
                                <i class="bx bx-info-circle me-2"></i>
                                <strong>Thông báo:</strong> Bạn sắp xóa bình luận #<strong id="deleteCommentId"></strong>.<br>
                                Bình luận sẽ được chuyển vào <strong>Thùng rác</strong> và bạn có thể khôi phục lại sau.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                            <form id="deleteForm" method="POST" action="#">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" id="confirmDeleteBtn">
                                    <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                        aria-hidden="true"></span>
                                    Xóa
                                </button>
                            </form>
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
                            <h5 class="modal-title" id="confirmRestoreLabel">Xác nhận khôi phục bình luận</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info mb-0">
                                <i class="bx bx-info-circle me-2"></i>
                                Bạn có chắc chắn muốn khôi phục bình luận #<strong id="restoreCommentId"></strong>?
                            </div>
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
                            <h5 class="modal-title" id="confirmForceDeleteLabel">Xác nhận xóa vĩnh viễn bình luận</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger mb-0">
                                <i class="bx bx-error-circle me-2"></i>
                                <strong>Cảnh báo:</strong> Bạn sắp xóa vĩnh viễn bình luận #<strong id="forceDeleteCommentId"></strong>.<br>
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

            <!-- Change Status Confirmation Modal -->
            <div class="modal fade" id="confirmChangeStatusModal" tabindex="-1" aria-labelledby="confirmChangeStatusLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmChangeStatusLabel">Xác nhận thay đổi trạng thái</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info mb-0">
                                <i class="bx bx-info-circle me-2"></i>
                                Bạn có chắc chắn muốn thay đổi trạng thái bình luận #<strong id="changeStatusCommentId"></strong> thành <strong id="changeStatusLabel"></strong>?
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                            <button type="button" class="btn btn-primary" id="confirmChangeStatusBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                    aria-hidden="true"></span>
                                Xác nhận
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk Approve Confirmation Modal -->
            <div class="modal fade" id="bulkApproveModal" tabindex="-1" aria-labelledby="bulkApproveLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="bulkApproveLabel">Xác nhận duyệt bình luận</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-success mb-0">
                                <i class="bx bx-check-circle me-2"></i>
                                Bạn có chắc chắn muốn duyệt <strong id="bulkApproveCount">0</strong> bình luận đã chọn?
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                            <button type="button" class="btn btn-success" id="confirmBulkApproveBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                    aria-hidden="true"></span>
                                Duyệt
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk Spam Confirmation Modal -->
            <div class="modal fade" id="bulkSpamModal" tabindex="-1" aria-labelledby="bulkSpamLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="bulkSpamLabel">Xác nhận đánh dấu spam</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning mb-0">
                                <i class="bx bx-shield-x me-2"></i>
                                Bạn có chắc chắn muốn đánh dấu spam <strong id="bulkSpamCount">0</strong> bình luận đã chọn?
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                            <button type="button" class="btn btn-warning" id="confirmBulkSpamBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                    aria-hidden="true"></span>
                                Đánh dấu spam
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk Trash Confirmation Modal -->
            <div class="modal fade" id="bulkTrashModal" tabindex="-1" aria-labelledby="bulkTrashLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="bulkTrashLabel">Xác nhận chuyển vào thùng rác</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning mb-0">
                                <i class="bx bx-info-circle me-2"></i>
                                Bạn có chắc chắn muốn chuyển <strong id="bulkTrashCount">0</strong> bình luận đã chọn vào thùng rác?<br>
                                <small class="d-block mt-2">Các bình luận sẽ được chuyển vào <strong>Thùng rác</strong> và bạn có thể khôi phục lại sau.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                            <button type="button" class="btn btn-secondary" id="confirmBulkTrashBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                    aria-hidden="true"></span>
                                Chuyển vào thùng rác
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
                            <h5 class="modal-title" id="bulkRestoreLabel">Xác nhận khôi phục bình luận</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info mb-0">
                                <i class="bx bx-info-circle me-2"></i>
                                Bạn có chắc chắn muốn khôi phục <strong id="bulkRestoreCount">0</strong> bình luận đã chọn?
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
                            <h5 class="modal-title" id="bulkForceDeleteLabel">Xác nhận xóa vĩnh viễn bình luận</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger mb-0">
                                <i class="bx bx-error-circle me-2"></i>
                                <strong>Cảnh báo:</strong> Bạn có chắc chắn muốn xóa vĩnh viễn <strong id="bulkForceDeleteCount">0</strong> bình luận đã chọn?<br>
                                <small class="d-block mt-2">Hành động này không thể hoàn tác và sẽ xóa vĩnh viễn khỏi hệ thống.</small>
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
    @vite(['resources/js/admin/pages/comment/list.js', 'resources/js/admin/pages/comment/detail.js'])
    <script>
        // Define routes for comment
        window.commentListUrl = "{{ route('admin.comments.ajaxGetData') }}";
        window.commentTrashedListUrl = "{{ route('admin.comments.ajaxGetTrashedData') }}";
        window.commentShowUrl = "{{ route('admin.comments.show', ':id') }}";
        window.commentChangeStatusUrl = "{{ route('admin.comments.changeStatus', [':id', ':status']) }}";
        window.commentBulkChangeStatusUrl = "{{ route('admin.comments.bulkChangeStatus') }}";
        window.commentReplyUrl = "{{ route('admin.comments.reply', ':id') }}";
        window.commentDeleteUrl = "{{ route('admin.comments.destroy', ':id') }}";
        // window.commentBulkDeleteUrl đã bị xóa - chỉ dùng "Chuyển vào thùng rác"
        window.commentRestoreUrl = "{{ route('admin.comments.restore', ':id') }}";
        window.commentForceDeleteUrl = "{{ route('admin.comments.forceDelete', ':id') }}";
        window.commentBulkRestoreUrl = "{{ route('admin.comments.bulkRestore') }}";
        window.commentBulkForceDeleteUrl = "{{ route('admin.comments.bulkForceDelete') }}";
        window.commentCountPendingUrl = "{{ route('admin.comments.countPending') }}";
    </script>
@endpush

