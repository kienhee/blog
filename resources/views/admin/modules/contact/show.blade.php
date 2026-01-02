@extends('admin.layouts.master')
@section('title', 'Chi tiết liên hệ')
@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
@endpush
@section('content')
    <section>
        @include('admin.components.headingPage', [
            'description' => 'Xem và quản lý chi tiết liên hệ',
            'button' => 'list',
            'listLink' => 'admin.contacts.list',
        ])

        @include('admin.components.showMessage')

        <div class="row">
            <!-- Left Column - Contact Information -->
            <div class="col-lg-8">
                <!-- Contact Details Card -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bx bx-info-circle me-2"></i>Thông tin liên hệ
                        </h5>
                        @php
                            $statusColor = $statusLabels[$contact->status] == 'Chưa xử lý' ? 'warning' : ($statusLabels[$contact->status] == 'Đã trả lời email' ? 'success' : ($statusLabels[$contact->status] == 'Spam' ? 'danger' : 'info'));
                        @endphp
                        <span class="badge rounded-pill bg-label-{{ $statusColor }} d-inline-flex align-items-center lh-1">
                            <span class="badge badge-dot text-bg-{{ $statusColor }} me-1"></span>
                            {{ $statusLabels[$contact->status] }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">Họ và tên</label>
                                <p class="mb-0 fw-semibold">{{ $contact->full_name }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">Email</label>
                                <p class="mb-0">
                                    <a href="mailto:{{ $contact->email }}" class="text-primary">{{ $contact->email }}</a>
                                </p>
                            </div>
                        </div>
                        @if($contact->phone)
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">Số điện thoại</label>
                                <p class="mb-0">
                                    <a href="tel:{{ $contact->phone }}" class="text-primary">{{ $contact->phone }}</a>
                                </p>
                            </div>
                        </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label text-muted">Tiêu đề</label>
                            <p class="mb-0 fw-semibold">{{ $contact->subject }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted">Tin nhắn</label>
                            <div class="alert alert-light" style="white-space: pre-wrap;">{{ $contact->message }}</div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label text-muted">Ngày gửi</label>
                                <p class="mb-0">
                                    <i class="bx bx-calendar me-1"></i>{{ $contact->created_at->format('d/m/Y H:i:s') }}
                                </p>
                            </div>
                            @if($contact->updated_at != $contact->created_at)
                            <div class="col-md-6">
                                <label class="form-label text-muted">Cập nhật lần cuối</label>
                                <p class="mb-0">
                                    <i class="bx bx-time me-1"></i>{{ $contact->updated_at->format('d/m/Y H:i:s') }}
                                </p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Replies Card -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bx bx-message-dots me-2"></i>Lịch sử trả lời
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($contact->replies->count() > 0)
                            <div class="timeline">
                                @foreach($contact->replies as $reply)
                                    <div class="timeline-item mb-4">
                                        <div class="timeline-marker"></div>
                                        <div class="timeline-content">
                                            <div class="card">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div>
                                                            <h6 class="mb-1">{{ $reply->subject }}</h6>
                                                            <small class="text-muted">
                                                                <i class="bx bx-user me-1"></i>{{ $reply->user->full_name ?? $reply->user->email ?? 'Hệ thống' }}
                                                            </small>
                                                        </div>
                                                        <small class="text-muted">
                                                            <i class="bx bx-time me-1"></i>{{ $reply->created_at->format('d/m/Y H:i') }}
                                                        </small>
                                                    </div>
                                                    <div class="mt-2" style="white-space: pre-wrap;">{{ $reply->message }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="bx bx-message-x fs-1 text-muted mb-2"></i>
                                <p class="text-muted">Chưa có phản hồi nào</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column - Actions & Status -->
            <div class="col-lg-4">
                <!-- Quick Actions Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bx bx-cog me-2"></i>Thao tác nhanh
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Change Status -->
                        @can('contact.update')
                        <div class="mb-3">
                            <label class="form-label">Thay đổi trạng thái</label>
                            <select id="changeStatusSelect" class="form-select">
                                @foreach($statusLabels as $status => $label)
                                    <option value="{{ $status }}" {{ $contact->status == $status ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button" id="updateStatusBtn" class="btn btn-primary w-100 mb-3">
                            <i class="bx bx-check me-1"></i>Cập nhật trạng thái
                        </button>
                        @endcan

                        <!-- Reply Button -->
                        @can('contact.update')
                        <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#replyModal">
                            <i class="bx bx-reply me-1"></i>Trả lời
                        </button>
                        @endcan
                    </div>
                </div>

                <!-- Contact Info Card -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bx bx-detail me-2"></i>Thông tin bổ sung
                        </h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5 text-muted">ID:</dt>
                            <dd class="col-sm-7">#{{ $contact->id }}</dd>

                            <dt class="col-sm-5 text-muted">Trạng thái:</dt>
                            <dd class="col-sm-7">
                                @php
                                    $statusColor = $statusLabels[$contact->status] == 'Chưa xử lý' ? 'warning' : ($statusLabels[$contact->status] == 'Đã trả lời email' ? 'success' : ($statusLabels[$contact->status] == 'Spam' ? 'danger' : 'info'));
                                @endphp
                                <span class="badge rounded-pill bg-label-{{ $statusColor }} d-inline-flex align-items-center lh-1">
                                    <span class="badge badge-dot text-bg-{{ $statusColor }} me-1"></span>
                                    {{ $statusLabels[$contact->status] }}
                                </span>
                            </dd>

                            <dt class="col-sm-5 text-muted">Số phản hồi:</dt>
                            <dd class="col-sm-7">{{ $contact->replies->count() }}</dd>

                            <dt class="col-sm-5 text-muted">Ngày tạo:</dt>
                            <dd class="col-sm-7">{{ $contact->created_at->format('d/m/Y') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reply Modal -->
        @can('contact.update')
        <div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form id="replyForm">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="replyModalLabel">Trả lời liên hệ</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="reply_subject" class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="reply_subject" name="subject" 
                                    value="Re: {{ $contact->subject }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="reply_message" class="form-label">Nội dung phản hồi <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="reply_message" name="message" rows="8" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary" id="submitReplyBtn">
                                <span class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
                                Gửi phản hồi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endcan
    </section>
@endsection

@push('scripts')
    <script src="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.js') }}"></script>
    <script>
        const contactId = {{ $contact->id }};
        const changeStatusUrl = "{{ route('admin.contacts.changeStatus', [':id', ':status']) }}";
        const replyUrl = "{{ route('admin.contacts.reply', $contact->id) }}";

        // Change Status (chỉ chạy nếu button tồn tại - tức là có quyền update)
        @if(auth()->user()->can('contact.update'))
        $('#updateStatusBtn').on('click', function() {
            const newStatus = $('#changeStatusSelect').val();
            const url = changeStatusUrl.replace(':id', contactId).replace(':status', newStatus);

            $.ajax({
                url: url,
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status) {
                        toastr.success(response.message, 'Thành công');
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Có lỗi xảy ra', 'Lỗi');
                }
            });
        });
        @endif

        // Reply Form
        @if(auth()->user()->can('contact.update'))
        $('#replyForm').on('submit', function(e) {
            e.preventDefault();
            const $btn = $('#submitReplyBtn');
            const $spinner = $btn.find('.spinner-border');

            $btn.prop('disabled', true);
            $spinner.removeClass('d-none');

            $.ajax({
                url: replyUrl,
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.status) {
                        toastr.success(response.message, 'Thành công');
                        $('#replyModal').modal('hide');
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    }
                },
                error: function(xhr) {
                    const errors = xhr.responseJSON?.errors;
                    if (errors) {
                        Object.keys(errors).forEach(field => {
                            const $field = $(`[name="${field}"]`);
                            $field.addClass('is-invalid');
                            $field.after(`<div class="invalid-feedback">${errors[field][0]}</div>`);
                        });
                    }
                    toastr.error(xhr.responseJSON?.message || 'Có lỗi xảy ra', 'Lỗi');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $spinner.addClass('d-none');
                }
            });
        });

        // Clear invalid feedback on input
        $('#replyForm input, #replyForm textarea').on('input', function() {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        });
        @endif
    </script>
@endpush

<style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    .timeline-item {
        position: relative;
    }
    .timeline-marker {
        position: absolute;
        left: -25px;
        top: 8px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #3B82F6;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #3B82F6;
    }
    .timeline::before {
        content: '';
        position: absolute;
        left: -19px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #E5E7EB;
    }
</style>

