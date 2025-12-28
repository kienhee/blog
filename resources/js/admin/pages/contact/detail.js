"use strict";

$(function () {
    // ======================================
    // 👁️ MODAL XEM CHI TIẾT CONTACT
    // ======================================
    const detailModal = $("#contactDetailModal");
    const detailModalBody = $("#contactDetailModalBody");

    // Escape HTML để tránh XSS
    const escapeHtml = function (text) {
        if (!text) return "N/A";
        const div = document.createElement("div");
        div.textContent = text;
        return div.innerHTML;
    };

    // Render lịch sử replies
    function renderReplies(replies) {
        if (!replies || replies.length === 0) {
            return `
                <div class="alert alert-info mb-0">
                    <i class="bx bx-info-circle me-2"></i>Chưa có phản hồi nào cho liên hệ này.
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
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong class="d-block">${escapeHtml(
                                    reply.subject
                                )}</strong>
                                <small class="text-muted">Bởi: ${escapeHtml(
                                    reply.user_name
                                )}</small>
                            </div>
                            <small class="text-muted">${escapeHtml(
                                reply.created_at
                            )}</small>
                        </div>
                        <div class="bg-light rounded p-2" style="white-space: pre-wrap; word-wrap: break-word;">${escapeHtml(
                            reply.message
                        )}</div>
                    </div>
                `
                    )
                    .join("")}
            </div>
        `;
    }

    // Render form trả lời
    function renderReplyForm(contact) {
        return `
            <form id="contactReplyForm" data-contact-id="${contact.id}">
                <div class="mb-3">
                    <label for="reply_subject" class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="reply_subject" name="subject"
                        value="Re: ${escapeHtml(contact.subject)}" required>
                </div>
                <div class="mb-3">
                    <label for="reply_message" class="form-label">Nội dung trả lời <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="reply_message" name="message" rows="6"
                        placeholder="Nhập nội dung trả lời..." required></textarea>
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
    function renderModalContent(contact) {
        const statusLabels = {
            0: "Chưa xử lý",
            1: "Đã liên hệ",
            2: "Đã trả lời email",
            3: "Spam",
        };
        const statusClasses = {
            0: "bg-label-warning",
            1: "bg-label-info",
            2: "bg-label-success",
            3: "bg-label-danger",
        };
        const statusLabel = statusLabels[contact.status] || "Không xác định";
        const statusClass =
            statusClasses[contact.status] || "bg-label-secondary";

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
                        <i class="bx bx-history me-1"></i> Lịch sử trả lời
                        ${
                            contact.replies && contact.replies.length > 0
                                ? `<span class="badge bg-label-primary ms-1">${contact.replies.length}</span>`
                                : ""
                        }
                    </button>
                </li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
                <!-- Tab 1: Thông tin liên hệ -->
                <div class="tab-pane fade show active" id="info-pane" role="tabpanel" aria-labelledby="info-tab">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Họ tên:</label>
                            <p class="mb-0">${escapeHtml(contact.full_name)}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Email:</label>
                            <p class="mb-0">
                                <a href="mailto:${escapeHtml(
                                    contact.email
                                )}">${escapeHtml(contact.email)}</a>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Số điện thoại:</label>
                            <p class="mb-0">
                                ${
                                    contact.phone
                                        ? `<a href="tel:${escapeHtml(contact.phone)}">${escapeHtml(contact.phone)}</a>`
                                        : '<span class="text-muted">-</span>'
                                }
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Chủ đề:</label>
                            <p class="mb-0">${escapeHtml(contact.subject)}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Trạng thái:</label>
                            <p class="mb-0">
                                <span class="badge ${statusClass}">${statusLabel}</span>
                            </p>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">Tin nhắn:</label>
                            <div class="border rounded p-3 bg-light" style="max-height: 300px; overflow-y: auto;">
                                <p class="mb-0" style="white-space: pre-wrap; word-wrap: break-word;">${escapeHtml(
                                    contact.message
                                )}</p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Ngày tạo:</label>
                            <p class="mb-0 text-muted">${escapeHtml(
                                contact.created_at
                            )}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Cập nhật lần cuối:</label>
                            <p class="mb-0 text-muted">${escapeHtml(
                                contact.updated_at
                            )}</p>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Lịch sử trả lời -->
                <div class="tab-pane fade" id="replies-pane" role="tabpanel" aria-labelledby="replies-tab">
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">
                            <i class="bx bx-history me-2"></i>Lịch sử trả lời
                            ${
                                contact.replies && contact.replies.length > 0
                                    ? `(${contact.replies.length})`
                                    : ""
                            }
                        </h6>
                        ${renderReplies(contact.replies)}
                    </div>
                    <hr class="my-4">
                    <div class="mt-4">
                        <h6 class="fw-bold mb-3">
                            <i class="bx bx-reply me-2"></i>Trả lời nhanh
                        </h6>
                        ${renderReplyForm(contact)}
                    </div>
                </div>
            </div>
        `;
    }

    // Event click vào button "xem thêm"
    $(document).on("click", ".btn-view-contact", function (e) {
        e.preventDefault();
        const contactId = $(this).data("id");
        const viewUrl =
            $(this).data("url") ||
            window.contactShowUrl.replace(":id", contactId);

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
                    const contact = response.data;
                    detailModalBody.html(renderModalContent(contact));
                    detailModalBody.data("contact-id", contact.id);
                    // Khởi tạo validation cho reply form
                    initReplyFormValidation();
                } else {
                    let errorMessage =
                        "Không thể tải thông tin liên hệ. Vui lòng thử lại.";
                    if (response.message) {
                        errorMessage = response.message;
                    }
                    detailModalBody.html(
                        `<div class="alert alert-danger">${errorMessage}</div>`
                    );
                }
            },
            error: function (xhr) {
                let errorMessage =
                    "Có lỗi xảy ra khi tải thông tin liên hệ. Vui lòng thử lại.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 404) {
                    errorMessage = "Liên hệ không tồn tại.";
                }

                detailModalBody.html(
                    `<div class="alert alert-danger">${errorMessage}</div>`
                );
            },
        });
    });

    // ======================================
    // 📧 XỬ LÝ TRẢ LỜI NHANH
    // ======================================
    // Khởi tạo FormValidation cho reply form khi modal được render
    let replyFormValidator = null;

    function initReplyFormValidation() {
        const $replyForm = $("#contactReplyForm");
        if ($replyForm.length && typeof FormValidation !== "undefined") {
            // Destroy instance cũ nếu có
            if (replyFormValidator) {
                replyFormValidator.destroy();
            }

            replyFormValidator = FormValidation.formValidation($replyForm[0], {
                fields: {
                    subject: {
                        validators: {
                            notEmpty: {
                                message: "Vui lòng nhập tiêu đề trả lời",
                            },
                            stringLength: {
                                min: 3,
                                max: 255,
                                message: "Tiêu đề phải từ 3 đến 255 ký tự",
                            },
                        },
                    },
                    message: {
                        validators: {
                            notEmpty: {
                                message: "Vui lòng nhập nội dung trả lời",
                            },
                            stringLength: {
                                min: 10,
                                message: "Nội dung phải có ít nhất 10 ký tự",
                            },
                        },
                    },
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    bootstrap5: new FormValidation.plugins.Bootstrap5({
                        rowSelector: ".mb-3",
                        eleInvalidClass: "is-invalid",
                        eleValidClass: "is-valid",
                    }),
                    submitButton: new FormValidation.plugins.SubmitButton(),
                    autoFocus: new FormValidation.plugins.AutoFocus(),
                },
            });
        }
    }

    // Event submit form trả lời
    $(document).on("submit", "#contactReplyForm", function (e) {
        e.preventDefault();

        const form = $(this);
        const contactId = form.data("contact-id");
        const submitBtn = $("#submitReplyBtn");
        const spinner = submitBtn.find(".spinner-border");
        const replyUrl = window.contactReplyUrl.replace(":id", contactId);

        // Nếu có FormValidation, validate trước
        if (replyFormValidator) {
            replyFormValidator.validate().then(function (status) {
                if (status !== "Valid") {
                    return;
                }
                submitReplyForm();
            });
        } else {
            // Fallback: Manual validation
            const subject = $("#reply_subject").val().trim();
            const message = $("#reply_message").val().trim();

            if (!subject || subject.length < 3) {
                toastr.error("Tiêu đề phải có ít nhất 3 ký tự", "Lỗi");
                $("#reply_subject").focus();
                return;
            }

            if (!message || message.length < 10) {
                toastr.error("Nội dung phải có ít nhất 10 ký tự", "Lỗi");
                $("#reply_message").focus();
                return;
            }

            submitReplyForm();
        }

        function submitReplyForm() {
            const subject = $("#reply_subject").val().trim();
            const message = $("#reply_message").val().trim();

            submitBtn.prop("disabled", true);
            spinner.removeClass("d-none");

            $.ajax({
                url: replyUrl,
                type: "POST",
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                        "content"
                    ),
                },
                data: {
                    subject: subject,
                    message: message,
                },
                success: function (response) {
                    if (response.status) {
                        toastr.success(
                            response.message || "Gửi trả lời thành công",
                            "Thành công"
                        );

                        // Reload lại chi tiết contact để hiển thị reply mới
                        const viewUrl = window.contactShowUrl.replace(
                            ":id",
                            contactId
                        );
                        $.ajax({
                            url: viewUrl,
                            type: "GET",
                            success: function (response) {
                                if (response.status && response.data) {
                                    const contact = response.data;
                                    detailModalBody.html(
                                        renderModalContent(contact)
                                    );
                                    detailModalBody.data(
                                        "contact-id",
                                        contact.id
                                    );

                                    // Chuyển sang tab replies sau khi gửi thành công
                                    const repliesTabElement =
                                        document.getElementById("replies-tab");
                                    if (repliesTabElement) {
                                        const repliesTab = new bootstrap.Tab(
                                            repliesTabElement
                                        );
                                        repliesTab.show();
                                    }

                                    // Refresh table để cập nhật status
                                    if (
                                        typeof window.contactTable !==
                                            "undefined" &&
                                        window.contactTable
                                    ) {
                                        window.contactTable.draw();
                                    }
                                }
                            },
                        });

                        // Reset form - giữ lại subject với "Re:"
                        const originalSubject = $("#reply_subject")
                            .val()
                            .replace(/^Re:\s*/i, "");
                        $("#reply_subject").val("Re: " + originalSubject);
                        $("#reply_message").val("");
                    } else {
                        toastr.error(
                            response.message || "Không thể gửi trả lời",
                            "Lỗi"
                        );
                    }
                },
                error: function (xhr) {
                    let errorMessage = "Có lỗi xảy ra khi gửi trả lời";
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON.errors) {
                            const errors = Object.values(
                                xhr.responseJSON.errors
                            ).flat();
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
        }
    });

    // Event cancel reply
    $(document).on("click", "#cancelReplyBtn", function () {
        $("#reply_subject").val("");
        $("#reply_message").val("");
    });
});
