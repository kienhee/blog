"use strict";

$(function () {
    // ======================================
    // 👁️ MODAL XEM CHI TIẾT COMMENT
    // ======================================
    const detailModal = $("#commentDetailModal");
    const detailModalBody = $("#commentDetailModalBody");

    // Escape HTML để tránh XSS
    const escapeHtml = function (text) {
        if (!text) return "N/A";
        const div = document.createElement("div");
        div.textContent = text;
        return div.innerHTML;
    };

    // Format user avatar
    function formatUserAvatar(user) {
        if (!user) return '<div class="avatar-initial rounded-circle bg-label-secondary" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"><span class="text-sm">?</span></div>';
        
        if (user.avatar) {
            return `<img src="${escapeHtml(user.avatar)}" alt="${escapeHtml(user.full_name || user.email)}" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;" />`;
        }
        
        const initial = (user.full_name || user.email || '?').charAt(0).toUpperCase();
        return `<div class="avatar-initial rounded-circle bg-label-primary" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"><span class="text-sm">${initial}</span></div>`;
    }

    // Render lịch sử replies
    function renderReplies(replies) {
        if (!replies || replies.length === 0) {
            return `
                <div class="alert alert-info mb-0">
                    <i class="bx bx-info-circle me-2"></i>Chưa có phản hồi nào cho bình luận này.
                </div>
            `;
        }

        return `
            <div class="timeline">
                ${replies
                    .map(
                        (reply, index) => `
                    <div class="timeline-item mb-3 ${
                        index === 0
                            ? "border-start border-primary border-2 ps-3"
                            : "border-start border-2 ps-3 border-secondary"
                    }">
                        <div class="d-flex align-items-start gap-2 mb-2">
                            ${formatUserAvatar({ avatar: reply.user_avatar, full_name: reply.user_name })}
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong class="d-block">${escapeHtml(reply.user_name)}</strong>
                                        <small class="text-muted">${escapeHtml(reply.created_at_human || reply.created_at)}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-light rounded p-3 ms-5" style="white-space: pre-wrap; word-wrap: break-word;">${escapeHtml(reply.content)}</div>
                    </div>
                `
                    )
                    .join("")}
            </div>
        `;
    }

    // Render form trả lời
    function renderReplyForm(comment) {
        return `
            <form id="commentReplyForm" data-comment-id="${comment.id}">
                <div class="mb-3">
                    <label for="reply_content" class="form-label">Nội dung trả lời <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="reply_content" name="content" rows="4"
                        placeholder="Nhập nội dung trả lời..." required></textarea>
                    <small class="text-muted">Tối thiểu 3 ký tự, tối đa 5000 ký tự</small>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-label-secondary" id="cancelReplyBtn">Hủy</button>
                    <button type="submit" class="btn btn-primary" id="submitReplyBtn">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                        <i class="bx bx-send me-1"></i> Gửi trả lời
                    </button>
                </div>
            </form>
        `;
    }

    // Render nội dung modal với tabs
    function renderModalContent(comment) {
        const statusLabels = {
            pending: "Chờ duyệt",
            approved: "Đã duyệt",
            spam: "Spam",
            trash: "Thùng rác",
        };
        const statusClasses = {
            pending: "bg-label-warning",
            approved: "bg-label-success",
            spam: "bg-label-danger",
            trash: "bg-label-secondary",
        };
        const statusLabel = statusLabels[comment.status] || "Không xác định";
        const statusClass = statusClasses[comment.status] || "bg-label-secondary";

        const postLink = comment.post ? `<a href="/bai-viet/${escapeHtml(comment.post.slug)}" target="_blank" class="text-primary">${escapeHtml(comment.post.title)}</a>` : '<span class="text-muted">-</span>';

        return `
            <!-- Nav tabs -->
            <ul class="nav nav-tabs nav-fill mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-pane"
                        type="button" role="tab" aria-controls="info-pane" aria-selected="true">
                        <i class="bx bx-info-circle me-1"></i> Thông tin
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="replies-tab" data-bs-toggle="tab" data-bs-target="#replies-pane"
                        type="button" role="tab" aria-controls="replies-pane" aria-selected="false">
                        <i class="bx bx-history me-1"></i> Phản hồi
                        ${
                            comment.replies && comment.replies.length > 0
                                ? `<span class="badge rounded-pill bg-label-primary d-inline-flex align-items-center lh-1 ms-1"><span class="badge badge-dot text-bg-primary me-1"></span>${comment.replies.length}</span>`
                                : ""
                        }
                    </button>
                </li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
                <!-- Tab 1: Thông tin bình luận -->
                <div class="tab-pane fade show active" id="info-pane" role="tabpanel" aria-labelledby="info-tab">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <div class="d-flex align-items-start gap-3">
                                ${formatUserAvatar(comment.user)}
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong class="d-block">${escapeHtml(comment.user ? (comment.user.full_name || comment.user.email) : 'Người dùng')}</strong>
                                            <small class="text-muted">${escapeHtml(comment.user ? comment.user.email : '')}</small>
                                        </div>
                                        <span class="badge ${statusClass}">${statusLabel}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">Bài viết:</label>
                            <p class="mb-0">${postLink}</p>
                        </div>
                        ${comment.parent ? `
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">Trả lời cho:</label>
                            <div class="bg-light rounded p-2 border-start border-primary border-3">
                                <small class="text-muted d-block mb-1">${escapeHtml(comment.parent.user_name)}</small>
                                <p class="mb-0" style="white-space: pre-wrap; word-wrap: break-word;">${escapeHtml(comment.parent.content)}</p>
                            </div>
                        </div>
                        ` : ''}
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">Nội dung bình luận:</label>
                            <div class="border rounded p-3 bg-light" style="max-height: 300px; overflow-y: auto;">
                                <p class="mb-0" style="white-space: pre-wrap; word-wrap: break-word;">${escapeHtml(comment.content)}</p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Ngày tạo:</label>
                            <p class="mb-0 text-muted">${escapeHtml(comment.created_at)}</p>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Phản hồi -->
                <div class="tab-pane fade" id="replies-pane" role="tabpanel" aria-labelledby="replies-tab">
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">
                            <i class="bx bx-history me-2"></i>Lịch sử phản hồi
                            ${
                                comment.replies && comment.replies.length > 0
                                    ? `(${comment.replies.length})`
                                    : ""
                            }
                        </h6>
                        ${renderReplies(comment.replies)}
                    </div>
                    <hr class="my-4">
                    <div class="mt-4">
                        <h6 class="fw-bold mb-3">
                            <i class="bx bx-reply me-2"></i>Trả lời nhanh
                        </h6>
                        ${renderReplyForm(comment)}
                    </div>
                </div>
            </div>
        `;
    }

    // Event click vào button "xem chi tiết"
    $(document).on("click", ".btn-show-comment", function (e) {
        e.preventDefault();
        const commentId = $(this).data("url").split('/').pop();
        const viewUrl = window.commentShowUrl.replace(":id", commentId);

        // Hiển thị loading
        detailModalBody.html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Đang tải thông tin...</p>
            </div>
        `);

        // Mở modal
        const modal = new bootstrap.Modal(detailModal[0]);
        modal.show();

        // Gọi AJAX để lấy chi tiết
        $.ajax({
            url: viewUrl,
            type: "GET",
            success: function (response) {
                if (response.status && response.data) {
                    const comment = response.data;
                    detailModalBody.html(renderModalContent(comment));
                    detailModalBody.data("comment-id", comment.id);
                } else {
                    let errorMessage = "Không thể tải thông tin bình luận. Vui lòng thử lại.";
                    if (response.message) {
                        errorMessage = response.message;
                    }
                    detailModalBody.html(`<div class="alert alert-danger">${errorMessage}</div>`);
                }
            },
            error: function (xhr) {
                let errorMessage = "Có lỗi xảy ra khi tải thông tin bình luận. Vui lòng thử lại.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 404) {
                    errorMessage = "Bình luận không tồn tại.";
                }
                detailModalBody.html(`<div class="alert alert-danger">${errorMessage}</div>`);
            },
        });
    });

    // ======================================
    // 📧 XỬ LÝ TRẢ LỜI NHANH
    // ======================================
    // Event submit form trả lời
    $(document).on("submit", "#commentReplyForm", function (e) {
        e.preventDefault();

        const form = $(this);
        const commentId = form.data("comment-id");
        const submitBtn = $("#submitReplyBtn");
        const spinner = submitBtn.find(".spinner-border");
        const replyUrl = window.commentReplyUrl.replace(":id", commentId);

        const content = $("#reply_content").val().trim();

        if (!content || content.length < 3) {
            toastr.error("Nội dung phải có ít nhất 3 ký tự", "Lỗi");
            $("#reply_content").focus();
            return;
        }

        if (content.length > 5000) {
            toastr.error("Nội dung không được vượt quá 5000 ký tự", "Lỗi");
            $("#reply_content").focus();
            return;
        }

        submitBtn.prop("disabled", true);
        spinner.removeClass("d-none");

        $.ajax({
            url: replyUrl,
            type: "POST",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            data: {
                content: content,
            },
            success: function (response) {
                if (response.status) {
                    toastr.success(response.message || "Gửi trả lời thành công", "Thành công");

                    // Reload lại chi tiết comment để hiển thị reply mới
                    const viewUrl = window.commentShowUrl.replace(":id", commentId);
                    $.ajax({
                        url: viewUrl,
                        type: "GET",
                        success: function (response) {
                            if (response.status && response.data) {
                                const comment = response.data;
                                detailModalBody.html(renderModalContent(comment));
                                detailModalBody.data("comment-id", comment.id);

                                // Chuyển sang tab replies sau khi gửi thành công
                                const repliesTabElement = document.getElementById("replies-tab");
                                if (repliesTabElement) {
                                    const repliesTab = new bootstrap.Tab(repliesTabElement);
                                    repliesTab.show();
                                }

                                // Refresh table để cập nhật
                                if (typeof window.commentTable !== "undefined" && window.commentTable) {
                                    window.commentTable.draw();
                                }
                            }
                        },
                    });

                    // Reset form
                    $("#reply_content").val("");
                } else {
                    toastr.error(response.message || "Không thể gửi trả lời", "Lỗi");
                }
            },
            error: function (xhr) {
                let errorMessage = "Có lỗi xảy ra khi gửi trả lời";
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.errors) {
                        const errors = Object.values(xhr.responseJSON.errors).flat();
                        errorMessage = errors.join(", ");
                    }
                }
                toastr.error(errorMessage, "Lỗi");
            },
            complete: function () {
                submitBtn.prop("disabled", false);
                spinner.addClass("d-none");
            },
        });
    });

    // Event cancel reply
    $(document).on("click", "#cancelReplyBtn", function () {
        $("#reply_content").val("");
    });
});

