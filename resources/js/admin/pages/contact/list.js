"use strict";

$(function () {
    // ======================================
    // 📋 KHỞI TẠO DATATABLE CHO DANH SÁCH
    // ======================================
    let datatable = $("#datatable_contact");

    if (datatable.length) {
        let urlGetData = datatable.data("url") || window.contactListUrl;
        var table = datatable.DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            searching: false,
            ajax: {
                url: urlGetData,
                data: function (d) {
                    d.full_name = $("#full_name").val();
                    d.email = $("#email").val();
                    d.subject = $("#subject").val();
                    d.status = $("#status").val();
                    d.created_at = $("#created_at").val();
                },
            },
            order: [[6, "desc"]],
            language: {
                url:
                    $("input[name='datatables_vi']").val() ||
                    window.datatablesViUrl,
            },
            columns: [
                {
                    data: "DT_RowIndex",
                    name: "DT_RowIndex",
                    orderable: false,
                    searchable: false,
                },
                { data: "full_name", name: "contacts.full_name" },
                { data: "email", name: "contacts.email" },
                {
                    data: "subject",
                    name: "contacts.subject",
                    searchable: false,
                },
                {
                    data: "message",
                    name: "contacts.message",
                    orderable: false,
                    searchable: false,
                },
                { data: "status", name: "contacts.status", searchable: false },
                {
                    data: "created_at",
                    name: "contacts.created_at",
                    searchable: false,
                },
                {
                    data: "action",
                    name: "action",
                    orderable: false,
                    searchable: false,
                },
            ],
        });

        // Filter
        $("#full_name, #email, #subject, #status, #created_at").on(
            "change input",
            function () {
                table.draw();
            }
        );

        // Reset filter
        $("#clearFilter").on("click", function () {
            $("#full_name").val("");
            $("#email").val("");
            $("#subject").val("");
            $("#status").val("");
            $("#created_at").val("");
            table.draw();
        });

        // Khởi tạo tooltip sau khi table được render
        table.on("draw", function () {
            // Bootstrap tooltip tự động xử lý với title attribute
        });
    }

    // Lưu table instance để dùng ở các file khác
    if (typeof table !== "undefined") {
        window.contactTable = table;
    }

    // ======================================
    // 🔄 XỬ LÝ THAY ĐỔI TRẠNG THÁI
    // ======================================
    let currentContactId = null;
    let currentStatus = null;
    let currentStatusLabel = null;

    // Khi click vào icon trạng thái để thay đổi
    $(document).on("click", ".change-status-item", function (e) {
        e.preventDefault();

        currentContactId = $(this).data("id");
        const currentStatusValue = $(this).data("status");

        const statusLabels = {
            0: "Chưa xử lý",
            1: "Đã liên hệ",
            2: "Đã trả lời email",
            3: "Spam",
        };

        const statusIcons = {
            0: "bx-message-square-dots",
            1: "bx-phone-call",
            2: "bx-envelope-check",
            3: "bx-shield-x",
        };

        const statusClasses = {
            0: "text-warning",
            1: "text-info",
            2: "text-success",
            3: "text-danger",
        };

        // Render các tùy chọn trạng thái
        let statusOptionsHtml = "";
        [0, 1, 2, 3].forEach((status) => {
            const isActive = status === currentStatusValue;
            const label = statusLabels[status];
            const iconClass = statusIcons[status];
            const textClass = statusClasses[status];

            statusOptionsHtml += `
                <div class="form-check mb-2">
                    <input class="form-check-input status-option" type="radio"
                           name="newStatus" id="status_${status}"
                           value="${status}" ${isActive ? "checked" : ""}>
                    <label class="form-check-label ${textClass}" for="status_${status}">
                        <i class="bx ${iconClass} me-2"></i>${label}
                        ${
                            isActive
                                ? '<i class="bx bx-check float-end mt-1"></i>'
                                : ""
                        }
                    </label>
                </div>
            `;
        });

        // Cập nhật nội dung modal
        $("#confirmStatusMessage").html(`
            <p class="mb-3">Chọn trạng thái mới cho liên hệ #${currentContactId}:</p>
            ${statusOptionsHtml}
        `);

        // Mở modal xác nhận
        const modal = new bootstrap.Modal($("#confirmChangeStatusModal"));
        modal.show();
    });

    // Khi nhấn nút "Xác nhận" trong modal
    $("#confirmChangeStatusBtn").on("click", function () {
        const btn = $(this);
        const spinner = btn.find(".spinner-border");

        // Lấy trạng thái được chọn từ radio button
        const selectedStatus = $('input[name="newStatus"]:checked').val();

        if (!selectedStatus && selectedStatus !== 0) {
            toastr.error("Vui lòng chọn trạng thái", "Lỗi");
            return;
        }

        currentStatus = selectedStatus;
        const changeStatusUrl = window.contactChangeStatusUrl
            .replace(":id", currentContactId)
            .replace(":status", currentStatus);

        btn.prop("disabled", true);
        spinner.removeClass("d-none");

        $.ajax({
            url: changeStatusUrl,
            type: "PUT",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                if (response.status) {
                    $("#confirmChangeStatusModal").modal("hide");
                    table.draw();
                    toastr.success(
                        "Cập nhật trạng thái thành công",
                        "Thông báo"
                    );
                } else {
                    toastr.error(
                        response.message || "Không thể cập nhật trạng thái",
                        "Thông báo"
                    );
                }
            },
            error: function (xhr) {
                let errorMessage = "Có lỗi xảy ra khi cập nhật trạng thái";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage, "Thông báo");
            },
            complete: function () {
                btn.prop("disabled", false);
                spinner.addClass("d-none");
            },
        });
    });
});
