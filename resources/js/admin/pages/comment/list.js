"use strict";

$(function () {
    // ======================================
    // 📋 KHỞI TẠO DATATABLE CHO DANH SÁCH
    // ======================================
    let datatable = $("#datatable_comment");

    if (datatable.length) {
        let urlGetData = datatable.data("url") || window.commentListUrl;
        var table = datatable.DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            searching: false,
            ajax: {
                url: urlGetData,
                data: function (d) {
                    d.status = $("#status").val();
                    d.created_at = $("#created_at").val();
                },
            },
            order: [[5, "desc"]],
            language: {
                url:
                    $("input[name='datatables_vi']").val() ||
                    window.datatablesViUrl,
            },
            columns: [
                {
                    data: "checkbox_html",
                    name: "checkbox",
                    orderable: false,
                    searchable: false,
                    width: "50px",
                },
                {
                    data: "DT_RowIndex",
                    name: "DT_RowIndex",
                    orderable: false,
                    searchable: false,
                },
                { data: "user_html", name: "user", orderable: false, searchable: false },
                { data: "post_html", name: "post", orderable: false, searchable: false },
                { data: "content_html", name: "comments.content", orderable: false },
                { data: "status_html", name: "comments.status", searchable: false },
                {
                    data: "created_at_html",
                    name: "comments.created_at",
                    searchable: false,
                },
                {
                    data: "action_html",
                    name: "action",
                    orderable: false,
                    searchable: false,
                },
            ],
            drawCallback: function (settings) {
                // Reset select all checkbox khi table redraw
                $("#selectAllComments").prop("checked", false);
                $("#bulkActionsContainerComments").hide();
            },
        });

        // Filter
        $("#status, #created_at").on("change input", function () {
            table.draw();
        });

            // Reset filter
            $("#clearFilter").on("click", function () {
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
        window.commentTable = table;
    }

    // ======================================
    // ☑️ XỬ LÝ CHECKBOX DANH SÁCH
    // ======================================
    // Select all checkbox trong danh sách
    $(document).on("change", "#selectAllComments", function () {
        const isChecked = $(this).is(":checked");
        $(".row-checkbox").prop("checked", isChecked);
        updateBulkActionsComments();
    });

    // Individual checkbox trong danh sách
    $(document).on("change", ".row-checkbox", function () {
        const totalCheckboxes = $(".row-checkbox").length;
        const checkedCheckboxes = $(".row-checkbox:checked").length;
        $("#selectAllComments").prop("checked", totalCheckboxes === checkedCheckboxes);
        updateBulkActionsComments();
    });

    // Cập nhật hiển thị bulk actions cho danh sách
    function updateBulkActionsComments() {
        const checkedBoxes = $(".row-checkbox:checked");
        const count = checkedBoxes.length;
        const bulkContainer = $("#bulkActionsContainerComments");
        const selectedCount = $("#selectedCountComments");

        if (count > 0) {
            bulkContainer.show();
            selectedCount.html(`Đã chọn: <strong>${count}</strong> mục`);
        } else {
            bulkContainer.hide();
        }
    }

    // Hàm hiển thị/ẩn bulk action container cho cả 2 tab
    function toggleBulkActions(tabId) {
        let selectedCount = 0;
        let containerId = "";
        let selectedCountId = "";

        if (tabId === "comments_tab") {
            selectedCount = $(".row-checkbox:checked").length;
            containerId = "#bulkActionsContainerComments";
            selectedCountId = "#selectedCountComments";
        } else if (tabId === "trash_tab") {
            // Tìm checkbox trong cả 2 selector để đảm bảo tìm được
            selectedCount = $("#datatable_comment_trash .row-checkbox:checked, .comments-trash-table .row-checkbox:checked").length;
            containerId = "#bulkActionsContainerTrash";
            selectedCountId = "#selectedCountTrash";
        }

        if (selectedCount > 0) {
            $(containerId).show();
            $(selectedCountId).html(`Đã chọn: <strong>${selectedCount}</strong> mục`);
        } else {
            $(containerId).hide();
        }
    }

    // ======================================
    // 🔄 XỬ LÝ BULK ACTIONS CHO DANH SÁCH
    // ======================================
    let bulkActionIds = [];

    // Bulk approve
    $(document).on("click", "#bulkApproveBtn", function (e) {
        e.preventDefault();
        const checkedBoxes = $(".row-checkbox:checked");
        bulkActionIds = checkedBoxes.map(function () {
            return $(this).val();
        }).get();

        if (bulkActionIds.length === 0) {
            toastr.warning("Vui lòng chọn ít nhất một bình luận", "Thông báo");
            return;
        }

        $("#bulkApproveCount").text(bulkActionIds.length);
        const modal = new bootstrap.Modal($("#bulkApproveModal"));
        modal.show();
    });

    // Confirm bulk approve
    $(document).on("click", "#confirmBulkApproveBtn", function () {
        const btn = $(this);
        const spinner = btn.find(".spinner-border");
        btn.prop("disabled", true);
        spinner.removeClass("d-none");

        bulkChangeStatus(bulkActionIds, "approved", function() {
            $("#bulkApproveModal").modal("hide");
            btn.prop("disabled", false);
            spinner.addClass("d-none");
        });
    });

    // Bulk spam
    $(document).on("click", "#bulkSpamBtn", function (e) {
        e.preventDefault();
        const checkedBoxes = $(".row-checkbox:checked");
        bulkActionIds = checkedBoxes.map(function () {
            return $(this).val();
        }).get();

        if (bulkActionIds.length === 0) {
            toastr.warning("Vui lòng chọn ít nhất một bình luận", "Thông báo");
            return;
        }

        $("#bulkSpamCount").text(bulkActionIds.length);
        const modal = new bootstrap.Modal($("#bulkSpamModal"));
        modal.show();
    });

    // Confirm bulk spam
    $(document).on("click", "#confirmBulkSpamBtn", function () {
        const btn = $(this);
        const spinner = btn.find(".spinner-border");
        btn.prop("disabled", true);
        spinner.removeClass("d-none");

        bulkChangeStatus(bulkActionIds, "spam", function() {
            $("#bulkSpamModal").modal("hide");
            btn.prop("disabled", false);
            spinner.addClass("d-none");
        });
    });

    // Bulk trash
    $(document).on("click", "#bulkTrashBtn", function (e) {
        e.preventDefault();
        const checkedBoxes = $(".row-checkbox:checked");
        bulkActionIds = checkedBoxes.map(function () {
            return $(this).val();
        }).get();

        if (bulkActionIds.length === 0) {
            toastr.warning("Vui lòng chọn ít nhất một bình luận", "Thông báo");
            return;
        }

        $("#bulkTrashCount").text(bulkActionIds.length);
        const modal = new bootstrap.Modal($("#bulkTrashModal"));
        modal.show();
    });

    // Confirm bulk trash
    $(document).on("click", "#confirmBulkTrashBtn", function () {
        const btn = $(this);
        const spinner = btn.find(".spinner-border");
        btn.prop("disabled", true);
        spinner.removeClass("d-none");

        bulkChangeStatus(bulkActionIds, "trash", function() {
            $("#bulkTrashModal").modal("hide");
            btn.prop("disabled", false);
            spinner.addClass("d-none");
        });
    });

    // Đã xóa bulk delete - chỉ sử dụng "Chuyển vào thùng rác"

    // Helper function để bulk change status
    function bulkChangeStatus(ids, status, callback) {
        $.ajax({
            url: window.commentBulkChangeStatusUrl,
            type: "POST",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            data: { ids: ids, status: status },
            success: function (response) {
                if (response.status) {
                    table.draw();
                    $("#selectAllComments").prop("checked", false);
                    updateBulkActionsComments();
                    toastr.success(response.message || "Cập nhật thành công", "Thông báo");
                    // Update pending count
                    if (typeof window.updateBadgeCount === 'function') {
                        window.updateBadgeCount('admin_comments_pending', window.commentCountPendingUrl);
                    }
                } else {
                    toastr.error(response.message || "Không thể cập nhật", "Thông báo");
                }
                if (callback) callback();
            },
            error: function (xhr) {
                let errorMessage = "Có lỗi xảy ra khi cập nhật";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage, "Thông báo");
                if (callback) callback();
            },
        });
    }

    // ======================================
    // 🔄 XỬ LÝ THAY ĐỔI TRẠNG THÁI (INDIVIDUAL)
    // ======================================
    let currentActionUrl = null;
    let currentActionType = null;
    let currentCommentId = null;

    // Approve comment
    $(document).on("click", ".btn-approve-comment", function (e) {
        e.preventDefault();
        const url = $(this).data("url");
        const commentId = url.split('/').slice(-2)[0]; // Extract comment ID from URL

        currentActionUrl = url;
        currentActionType = "approved";
        currentCommentId = commentId;
        $("#changeStatusCommentId").text(commentId);
        $("#changeStatusLabel").text("Đã duyệt");

        const modal = new bootstrap.Modal($("#confirmChangeStatusModal"));
        modal.show();
    });

    // Spam comment
    $(document).on("click", ".btn-spam-comment", function (e) {
        e.preventDefault();
        const url = $(this).data("url");
        const commentId = url.split('/').slice(-2)[0];

        currentActionUrl = url;
        currentActionType = "spam";
        currentCommentId = commentId;
        $("#changeStatusCommentId").text(commentId);
        $("#changeStatusLabel").text("Spam");

        const modal = new bootstrap.Modal($("#confirmChangeStatusModal"));
        modal.show();
    });

    // Trash comment
    $(document).on("click", ".btn-trash-comment", function (e) {
        e.preventDefault();
        const url = $(this).data("url");
        const commentId = url.split('/').slice(-2)[0];

        currentActionUrl = url;
        currentActionType = "trash";
        currentCommentId = commentId;
        $("#changeStatusCommentId").text(commentId);
        $("#changeStatusLabel").text("Thùng rác");

        const modal = new bootstrap.Modal($("#confirmChangeStatusModal"));
        modal.show();
    });

    // Confirm change status
    $(document).on("click", "#confirmChangeStatusBtn", function () {
        if (!currentActionUrl || !currentActionType) {
            toastr.error("Không tìm thấy thông tin hành động", "Lỗi");
            return;
        }

        const btn = $(this);
        const spinner = btn.find(".spinner-border");
        btn.prop("disabled", true);
        spinner.removeClass("d-none");

        $.ajax({
            url: currentActionUrl,
            type: "PUT",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                if (response.status) {
                    $("#confirmChangeStatusModal").modal("hide");
                    table.draw();
                    if (currentActionType === "trash") {
                        tableTrash.draw();
                    }
                    toastr.success(response.message || "Cập nhật thành công", "Thông báo");
                    // Update pending count
                    if (typeof window.updateBadgeCount === 'function') {
                        window.updateBadgeCount('admin_comments_pending', window.commentCountPendingUrl);
                    }
                } else {
                    toastr.error(response.message || "Không thể cập nhật", "Thông báo");
                }
            },
            error: function (xhr) {
                let errorMessage = "Có lỗi xảy ra khi cập nhật";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage, "Thông báo");
            },
            complete: function () {
                btn.prop("disabled", false);
                spinner.addClass("d-none");
                currentActionUrl = null;
                currentActionType = null;
                currentCommentId = null;
            },
        });
    });

    // Delete comment (soft delete from list)
    $(document).on("click", ".btn-delete", function (e) {
        e.preventDefault();
        const url = $(this).data("url");
        const commentId = url.split('/').slice(-1)[0];

        currentActionUrl = url;
        currentCommentId = commentId;
        $("#deleteCommentId").text(commentId);
        $("#deleteForm").attr("action", url);

        const modal = new bootstrap.Modal($("#confirmDeleteModal"));
        modal.show();
    });

    // Confirm delete (soft delete)
    $("#deleteForm").on("submit", function (e) {
        e.preventDefault();

        if (!currentActionUrl) {
            toastr.error("Không tìm thấy URL xóa.", "Thông báo");
            return;
        }

        const btn = $("#confirmDeleteBtn");
        const spinner = btn.find(".spinner-border");

        btn.prop("disabled", true);
        spinner.removeClass("d-none");

        $.ajax({
            url: currentActionUrl,
            type: "DELETE",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                if (response.status) {
                    $("#confirmDeleteModal").modal("hide");
                    table.draw();
                    toastr.success(response.message || "Xóa thành công", "Thông báo");
                    // Update pending count
                    if (typeof window.updateBadgeCount === 'function') {
                        window.updateBadgeCount('admin_comments_pending', window.commentCountPendingUrl);
                    }
                } else {
                    toastr.error(response.message || "Không thể xóa", "Thông báo");
                }
            },
            error: function (xhr) {
                let errorMessage = "Có lỗi xảy ra khi xóa";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage, "Thông báo");
            },
            complete: function () {
                btn.prop("disabled", false);
                spinner.addClass("d-none");
                currentActionUrl = null;
                currentCommentId = null;
            },
        });
    });

    // ======================================
    // 🔄 RELOAD TABLE KHI CHUYỂN TAB
    // ======================================
    // Reload trash table khi tab thùng rác được hiển thị
    $('button[data-bs-target="#trash_tab"]').on("shown.bs.tab", function () {
        if (typeof tableTrash !== "undefined" && tableTrash) {
            tableTrash.draw();
        }
        toggleBulkActions("trash_tab");
    });

    // Reload comments table khi tab danh sách được hiển thị
    $('button[data-bs-target="#comments_tab"]').on("shown.bs.tab", function () {
        if (typeof table !== "undefined" && table) {
            table.draw();
        }
        toggleBulkActions("comments_tab");
    });

    // ======================================
    // 🗑️ KHỞI TẠO DATATABLE CHO THÙNG RÁC
    // ======================================
    let datatableTrash = $("#datatable_comment_trash");
    let tableTrash = null;

    if (datatableTrash.length) {
        let urlGetTrashedData = datatableTrash.data("url") || window.commentTrashedListUrl;
        tableTrash = datatableTrash.DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            searching: false,
            ajax: {
                url: urlGetTrashedData,
                data: function (d) {
                    d.deleted_at = $("#deleted_at_trash").val();
                },
            },
            order: [[6, "desc"]],
            drawCallback: function (settings) {
                // Reset select all checkbox khi table redraw
                $("#selectAllTrash").prop("checked", false);
                // Đợi một chút để đảm bảo DOM đã được cập nhật
                setTimeout(function() {
                    toggleBulkActions("trash_tab");
                }, 100);
            },
            language: {
                url:
                    $("input[name='datatables_vi']").val() ||
                    window.datatablesViUrl,
            },
            columns: [
                {
                    data: "checkbox_html",
                    name: "checkbox",
                    orderable: false,
                    searchable: false,
                },
                {
                    data: "DT_RowIndex",
                    name: "DT_RowIndex",
                    orderable: false,
                    searchable: false,
                },
                { data: "user_html", name: "user", orderable: false, searchable: false },
                { data: "post_html", name: "post", orderable: false, searchable: false },
                { data: "content_html", name: "comments.content", orderable: false },
                { data: "status_html", name: "comments.status", searchable: false },
                {
                    data: "deleted_at_html",
                    name: "comments.deleted_at",
                    searchable: false,
                },
                {
                    data: "action_html",
                    name: "action",
                    orderable: false,
                    searchable: false,
                },
            ],
        });

        // Lưu table instance
        window.commentTableTrash = tableTrash;
    }

    // ======================================
    // 🔄 XỬ LÝ CHECKBOX THÙNG RÁC
    // ======================================
    // Select all checkbox trong thùng rác
    $(document).on("change", "#selectAllTrash", function () {
        const isChecked = $(this).is(":checked");
        $("#datatable_comment_trash .row-checkbox, .comments-trash-table .row-checkbox").prop("checked", isChecked);
        toggleBulkActions("trash_tab");
    });

    // Individual checkbox trong thùng rác - sử dụng event delegation
    $(document).on("change", "#datatable_comment_trash .row-checkbox, .comments-trash-table .row-checkbox", function () {
        const totalCheckboxes = $("#datatable_comment_trash .row-checkbox, .comments-trash-table .row-checkbox").length;
        const checkedCheckboxes = $("#datatable_comment_trash .row-checkbox:checked, .comments-trash-table .row-checkbox:checked").length;
        $("#selectAllTrash").prop("checked", totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0);
        toggleBulkActions("trash_tab");
    });

    // ======================================
    // 🔄 XỬ LÝ RESTORE VÀ FORCE DELETE
    // ======================================

    // Restore comment from trash (using .btn-restore class)
    $(document).on("click", ".btn-restore, .btn-restore-comment", function (e) {
        e.preventDefault();
        const url = $(this).data("url");
        const commentId = url.split('/').slice(-1)[0];

        currentActionUrl = url;
        currentCommentId = commentId;
        $("#restoreCommentId").text(commentId);

        const modal = new bootstrap.Modal($("#confirmRestoreModal"));
        modal.show();
    });

    // Confirm restore
    $(document).on("click", "#confirmRestoreBtn", function () {
        if (!currentActionUrl) {
            toastr.error("Không tìm thấy URL khôi phục.", "Thông báo");
            return;
        }

        const btn = $(this);
        const spinner = btn.find(".spinner-border");
        btn.prop("disabled", true);
        spinner.removeClass("d-none");

        $.ajax({
            url: currentActionUrl,
            type: "POST",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                if (response.status) {
                    $("#confirmRestoreModal").modal("hide");
                    if (tableTrash) tableTrash.draw();
                    if (table) table.draw();
                    toastr.success(response.message || "Khôi phục thành công", "Thông báo");
                    // Update pending count
                    if (typeof window.updateBadgeCount === 'function') {
                        window.updateBadgeCount('admin_comments_pending', window.commentCountPendingUrl);
                    }
                } else {
                    toastr.error(response.message || "Không thể khôi phục", "Thông báo");
                }
            },
            error: function (xhr) {
                let errorMessage = "Có lỗi xảy ra khi khôi phục";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage, "Thông báo");
            },
            complete: function () {
                btn.prop("disabled", false);
                spinner.addClass("d-none");
                currentActionUrl = null;
                currentCommentId = null;
            },
        });
    });

    // Force delete comment from trash (using .btn-force-delete class)
    $(document).on("click", ".btn-force-delete, .btn-force-delete-comment", function (e) {
        e.preventDefault();
        const url = $(this).data("url");
        const commentId = url.split('/').slice(-1)[0];

        currentActionUrl = url;
        currentCommentId = commentId;
        $("#forceDeleteCommentId").text(commentId);

        const modal = new bootstrap.Modal($("#confirmForceDeleteModal"));
        modal.show();
    });

    // Confirm force delete
    $(document).on("click", "#confirmForceDeleteBtn", function () {
        if (!currentActionUrl) {
            toastr.error("Không tìm thấy URL xóa vĩnh viễn.", "Thông báo");
            return;
        }

        const btn = $(this);
        const spinner = btn.find(".spinner-border");
        btn.prop("disabled", true);
        spinner.removeClass("d-none");

        $.ajax({
            url: currentActionUrl,
            type: "DELETE",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                if (response.status) {
                    $("#confirmForceDeleteModal").modal("hide");
                    if (tableTrash) tableTrash.draw();
                    toastr.success(response.message || "Xóa vĩnh viễn thành công", "Thông báo");
                } else {
                    toastr.error(response.message || "Không thể xóa vĩnh viễn", "Thông báo");
                }
            },
            error: function (xhr) {
                let errorMessage = "Có lỗi xảy ra khi xóa vĩnh viễn";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage, "Thông báo");
            },
            complete: function () {
                btn.prop("disabled", false);
                spinner.addClass("d-none");
                currentActionUrl = null;
                currentCommentId = null;
            },
        });
    });

    // Bulk restore
    $(document).on("click", "#bulkRestoreBtn", function (e) {
        e.preventDefault();
        const checkedBoxes = $("#datatable_comment_trash .row-checkbox:checked, .comments-trash-table .row-checkbox:checked");
        bulkActionIds = checkedBoxes.map(function () {
            return $(this).val();
        }).get();

        if (bulkActionIds.length === 0) {
            toastr.warning("Vui lòng chọn ít nhất một bình luận", "Thông báo");
            return;
        }

        $("#bulkRestoreCount").text(bulkActionIds.length);
        const modal = new bootstrap.Modal($("#bulkRestoreModal"));
        modal.show();
    });

    // Confirm bulk restore
    $(document).on("click", "#confirmBulkRestoreBtn", function () {
        const btn = $(this);
        const spinner = btn.find(".spinner-border");
        btn.prop("disabled", true);
        spinner.removeClass("d-none");

        $.ajax({
            url: window.commentBulkRestoreUrl,
            type: "POST",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            data: { ids: bulkActionIds },
            success: function (response) {
                if (response.status) {
                    $("#bulkRestoreModal").modal("hide");
                    if (tableTrash) tableTrash.draw();
                    if (table) table.draw();
                    $("#selectAllTrash").prop("checked", false);
                    toggleBulkActions("trash_tab");
                    toastr.success(response.message || "Khôi phục thành công", "Thông báo");
                    // Update pending count
                    if (typeof window.updateBadgeCount === 'function') {
                        window.updateBadgeCount('admin_comments_pending', window.commentCountPendingUrl);
                    }
                } else {
                    toastr.error(response.message || "Không thể khôi phục", "Thông báo");
                }
            },
            error: function (xhr) {
                let errorMessage = "Có lỗi xảy ra khi khôi phục";
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

    // Bulk force delete
    $(document).on("click", "#bulkForceDeleteBtn", function (e) {
        e.preventDefault();
        const checkedBoxes = $("#datatable_comment_trash .row-checkbox:checked, .comments-trash-table .row-checkbox:checked");
        bulkActionIds = checkedBoxes.map(function () {
            return $(this).val();
        }).get();

        if (bulkActionIds.length === 0) {
            toastr.warning("Vui lòng chọn ít nhất một bình luận", "Thông báo");
            return;
        }

        $("#bulkForceDeleteCount").text(bulkActionIds.length);
        const modal = new bootstrap.Modal($("#bulkForceDeleteModal"));
        modal.show();
    });

    // Confirm bulk force delete
    $(document).on("click", "#confirmBulkForceDeleteBtn", function () {
        const btn = $(this);
        const spinner = btn.find(".spinner-border");
        btn.prop("disabled", true);
        spinner.removeClass("d-none");

        $.ajax({
            url: window.commentBulkForceDeleteUrl,
            type: "DELETE",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            data: { ids: bulkActionIds },
            success: function (response) {
                if (response.status) {
                    $("#bulkForceDeleteModal").modal("hide");
                    if (tableTrash) tableTrash.draw();
                    $("#selectAllTrash").prop("checked", false);
                    toggleBulkActions("trash_tab");
                    toastr.success(response.message || "Xóa vĩnh viễn thành công", "Thông báo");
                } else {
                    toastr.error(response.message || "Không thể xóa vĩnh viễn", "Thông báo");
                }
            },
            error: function (xhr) {
                let errorMessage = "Có lỗi xảy ra khi xóa vĩnh viễn";
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

