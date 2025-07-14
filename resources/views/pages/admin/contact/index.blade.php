@extends('layouts.admin.master')
@section('content')
    <div class="mb-3">
        <label for="filterStatus" class="form-label">Lọc trạng thái:</label>
        <select id="filterStatus" class="form-select" style="width: 200px;">
            <option value="">Tất cả</option>
            <option value="1">Chưa trả lời</option>
            <option value="0">Đã trả lời</option>
        </select>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-striped" id="contactTable"
               data-url-get-data="{{ route('dashboard.contacts.ajaxGetDataContact') }}"
               data-url-confirm="{{ route('dashboard.contacts.confirm') }}">
            <thead>
                <tr>
                    <th>Họ và tên</th>
                    <th>Email</th>
                    <th>Chủ đề</th>
                    <th>Tin nhắn</th>
                    <th>Ngày nhận</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
        </table>
    </div>
    <!-- Modal xác nhận -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Xác nhận</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    Bạn xác nhận đã trả lời người này?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" id="confirmActionBtn">Xác nhận</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script src="{{asset("sources/admin/assets/js/pages/contact.js")}}"></script>
@endsection
