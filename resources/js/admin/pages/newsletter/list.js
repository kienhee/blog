"use strict";

$(function () {
    // ======================================
    // 📋 KHỞI TẠO DATATABLE CHO DANH SÁCH
    // ======================================
    let datatable = $("#datatable_newsletter");

    if (datatable.length) {
        let urlGetData = window.newsletterListUrl || datatable.data("url");
        var table = datatable.DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            searching: false,
            ajax: {
                url: urlGetData,
                data: function (d) {
                    d.email = $("#email").val();
                    d.status = $("#status").val();
                },
            },
            order: [[4, "desc"]],
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
                { data: "email", name: "newsletters.email" },
                { data: "status", name: "newsletters.status", searchable: false },
                {
                    data: "subscribed_at",
                    name: "newsletters.subscribed_at",
                    searchable: false,
                },
                {
                    data: "created_at",
                    name: "newsletters.created_at",
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
        $("#email, #status").on("change input", function () {
            table.draw();
        });

        // Reset filter
        $("#clearFilter").on("click", function () {
            $("#email").val("");
            $("#status").val("");
            table.draw();
        });
    }

    // ======================================
    // 🗑️ XỬ LÝ XÓA
    // ======================================
    let deleteUrl = null;
    let currentRow = null;

    // Khi click nút xóa
    $(document).on("click", ".delete-item", function () {
        deleteUrl = $(this).data("url");
        currentRow = $(this).closest("tr");

        // Mở modal
        const modal = new bootstrap.Modal($("#confirmDeleteModal"));
        modal.show();
    });

    // Khi nhấn nút "Xóa" trong modal
    $("#confirmDeleteBtn").on("click", function () {
        if (!deleteUrl) return;

        const $btn = $(this);
        $btn.prop("disabled", true);
        $btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Đang xóa...');

        $.ajax({
            url: deleteUrl,
            method: "DELETE",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                if (response.status) {
                    if (typeof toastr !== "undefined") {
                        toastr.success(response.message || "Xóa email đăng ký thành công", "Thành công");
                    }
                    table.ajax.reload();
                } else {
                    if (typeof toastr !== "undefined") {
                        toastr.error(response.message || "Có lỗi xảy ra", "Lỗi");
                    }
                }
            },
            error: function (xhr) {
                let errorMessage = "Có lỗi xảy ra khi xóa email đăng ký";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                if (typeof toastr !== "undefined") {
                    toastr.error(errorMessage, "Lỗi");
                }
            },
            complete: function () {
                $btn.prop("disabled", false);
                $btn.text("Xóa");
                $("#confirmDeleteModal").modal("hide");
                deleteUrl = null;
            },
        });
    });

    // Reset modal khi đóng
    $("#confirmDeleteModal").on("hidden.bs.modal", function () {
        deleteUrl = null;
        currentRow = null;
    });

    // Store table instance globally for reuse
    if (typeof table !== "undefined") {
        window.newsletterTable = table;
    }
});

