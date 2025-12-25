"use strict";

$(function () {
    // Khởi tạo date picker cho filter
    const $datePicker = $(".date-picker");
    if ($datePicker.length) {
        $datePicker.flatpickr({
            dateFormat: "d/m/Y",
        });
    }

    // ======================================
    // 📋 KHỞI TẠO DATATABLE CHO DANH SÁCH BÀI VIẾT
    // ======================================
    const postsTableSelector = "#datatable_blog";
    const $postsTable = $(postsTableSelector);

    if ($postsTable.length) {
        window.postsTable = $postsTable.DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: window.postsListUrl,
                data: function (d) {
                    d.status = $("#status").val();
                    d.category_id = $("#category_id").val();
                    d.created_at = $("#created_at").val();
                },
            },
            order: [[7, "desc"]],
            drawCallback: function (settings) {
                // Reset select all checkbox khi table redraw
                $("#selectAllPosts").prop("checked", false);
                if (typeof window.selectedPostIdsPosts !== "undefined") {
                    window.selectedPostIdsPosts = [];
                }
                $("#bulkActionsContainerPosts").hide();
            },
            language: {
                url:
                    $("input[name='datatables_vi']").val() ||
                    window.datatablesViUrl,
                searchPlaceholder: "Tìm kiếm theo tiêu đề...",
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
                {
                    data: "title_html",
                    name: "posts.title",
                },
                {
                    data: "status_html",
                    name: "posts.status",
                    searchable: false,
                },
                {
                    data: "category_name",
                    name: "categories.name",
                    searchable: false,
                },
                {
                    data: "allow_comment_html",
                    name: "posts.allow_comment",
                    searchable: false,
                },
                {
                    data: "view_count_html",
                    name: "view_count",
                    searchable: false,
                },
                {
                    data: "created_at_html",
                    name: "posts.created_at",
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
    }

    // ======================================
    // 🗑️ KHỞI TẠO DATATABLE CHO THÙNG RÁC
    // ======================================
    const trashTableSelector = "#datatable_blog_trash";
    const $trashTable = $(trashTableSelector);

    if ($trashTable.length) {
        window.postsTrashTable = $trashTable.DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: window.postsTrashListUrl,
                data: function (d) {
                    d.status = $("#status").val();
                    d.category_id = $("#category_id").val();
                    d.created_at = $("#created_at").val();
                },
            },
            order: [[7, "desc"]],
            language: {
                url:
                    $("input[name='datatables_vi']").val() ||
                    window.datatablesViUrl,
                searchPlaceholder: "Tìm kiếm theo tiêu đề...",
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
                {
                    data: "title_html",
                    name: "posts.title",
                },
                {
                    data: "status_html",
                    name: "posts.status",
                    searchable: false,
                },
                {
                    data: "category_name",
                    name: "categories.name",
                    searchable: false,
                },
                {
                    data: "allow_comment_html",
                    name: "posts.allow_comment",
                    searchable: false,
                },
                {
                    data: "view_count_html",
                    name: "view_count",
                    searchable: false,
                },
                {
                    data: "deleted_at_html",
                    name: "posts.deleted_at",
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
                $("#selectAllTrash").prop("checked", false);
                if (typeof window.selectedPostIds !== "undefined") {
                    window.selectedPostIds = [];
                }
                $("#bulkActionsContainer").hide();
            },
        });
    }

    // ======================================
    // 🔍 XỬ LÝ FILTER
    // ======================================
    // Filter khi thay đổi status, category_id, created_at
    $("#status, #category_id, #created_at").on("change", function () {
        if (typeof window.postsTable !== "undefined") {
            window.postsTable.draw();
        }
        if (typeof window.postsTrashTable !== "undefined") {
            window.postsTrashTable.draw();
        }
    });

    // Reset filter
    $("#clearFilter").on("click", function () {
        $("#status").val("");
        $("#category_id").val("");
        $("#created_at").val("");

        // Clear date picker nếu có
        if ($datePicker.length && $datePicker.data("flatpickr")) {
            $datePicker[0]._flatpickr.clear();
        }

        // Reload cả 2 bảng
        if (typeof window.postsTable !== "undefined") {
            window.postsTable.draw();
        }
        if (typeof window.postsTrashTable !== "undefined") {
            window.postsTrashTable.draw();
        }
    });

    // ======================================
    // 🔄 RELOAD TABLE KHI CHUYỂN TAB
    // ======================================
    // Reload trash table khi tab thùng rác được hiển thị
    $('button[data-bs-target="#trash_tab"]').on("shown.bs.tab", function () {
        if (typeof window.postsTrashTable !== "undefined") {
            window.postsTrashTable.draw();
        }
    });

    // Reload posts table khi tab danh sách được hiển thị
    $('button[data-bs-target="#posts_tab"]').on("shown.bs.tab", function () {
        if (typeof window.postsTable !== "undefined") {
            window.postsTable.draw();
        }
    });

    // ======================================
    // 🗑️ XỬ LÝ XÓA BÀI VIẾT (SOFT DELETE)
    // ======================================
    let deleteUrl = null;
    let currentRow = null;

    // Khi click nút xóa
    $(document).on("click", ".btn-delete", function () {
        deleteUrl = $(this).data("url");
        const title = $(this).data("title");
        currentRow = $(this).closest("tr");

        // Gán thông tin vào modal
        $("#deleteTitle").text(title || "bài viết này");
        $("#deleteForm").attr("action", deleteUrl);

        // Mở modal
        const modal = new bootstrap.Modal($("#confirmDeleteModal"));
        modal.show();
    });

    // Khi nhấn nút "Xóa" trong modal
    $("#deleteForm").on("submit", function (e) {
        e.preventDefault();

        if (!deleteUrl) {
            toastr.error("Không tìm thấy URL xóa.", "Thông báo");
            return;
        }

        const btn = $("#confirmDeleteBtn");
        const spinner = btn.find(".spinner-border");

        btn.prop("disabled", true);
        spinner.removeClass("d-none");

        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        $.ajax({
            url: deleteUrl,
            type: "DELETE",
            success: function (res) {
                if (res.status) {
                    $("#confirmDeleteModal").modal("hide");

                    // Xóa row khỏi DataTable nếu có
                    if (
                        typeof window.postsTable !== "undefined" &&
                        currentRow &&
                        currentRow.length
                    ) {
                        window.postsTable.row(currentRow).remove().draw(false);
                    } else {
                        // Reload page nếu không có table reference
                        location.reload();
                    }

                    toastr.success(
                        res.message || "Xóa thành công",
                        "Thông báo"
                    );
                } else {
                    toastr.error(
                        res.message || "Không thể xóa bài viết",
                        "Thông báo"
                    );
                }
            },
            error: function (xhr) {
                let message = "Lỗi khi xóa bài viết";
                if (xhr.responseJSON) {
                    message = xhr.responseJSON.message || message;
                } else if (xhr.status === 404) {
                    message = "Bài viết không tồn tại";
                } else if (xhr.status === 500) {
                    message = "Lỗi server. Vui lòng thử lại sau";
                }
                toastr.error(message, "Thông báo");
            },
            complete: function () {
                btn.prop("disabled", false);
                spinner.addClass("d-none");
            },
        });
    });

    // ======================================
    // 🔄 XỬ LÝ RESTORE VÀ FORCE DELETE
    // ======================================
    let restoreUrl = null;
    let forceDeleteUrl = null;
    let currentTrashRow = null;

    // Khi click nút restore
    $(document).on("click", ".btn-restore", function () {
        restoreUrl = $(this).data("url");
        const title = $(this).data("title");
        currentTrashRow = $(this).closest("tr");

        $("#restoreTitle").text(title || "bài viết này");
        const modal = new bootstrap.Modal($("#confirmRestoreModal"));
        modal.show();
    });

    // Khi nhấn nút "Khôi phục"
    $("#confirmRestoreBtn").on("click", function () {
        if (!restoreUrl) {
            toastr.error("Không tìm thấy URL khôi phục.", "Thông báo");
            return;
        }

        const btn = $(this);
        const spinner = btn.find(".spinner-border");

        btn.prop("disabled", true);
        spinner.removeClass("d-none");

        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        $.ajax({
            url: restoreUrl,
            type: "POST",
            success: function (res) {
                if (res.status) {
                    $("#confirmRestoreModal").modal("hide");

                    // Reload cả bảng danh sách chính và bảng thùng rác
                    if (typeof window.postsTable !== "undefined") {
                        window.postsTable.draw();
                    }
                    if (typeof window.postsTrashTable !== "undefined") {
                        window.postsTrashTable.draw();
                    }

                    toastr.success(
                        res.message || "Khôi phục thành công",
                        "Thông báo"
                    );
                } else {
                    toastr.error(
                        res.message || "Không thể khôi phục bài viết",
                        "Thông báo"
                    );
                }
            },
            error: function (xhr) {
                let message = "Lỗi khi khôi phục bài viết";
                if (xhr.responseJSON) {
                    message = xhr.responseJSON.message || message;
                } else if (xhr.status === 404) {
                    message = "Bài viết không tồn tại";
                } else if (xhr.status === 500) {
                    message = "Lỗi server. Vui lòng thử lại sau";
                }
                toastr.error(message, "Thông báo");
            },
            complete: function () {
                btn.prop("disabled", false);
                spinner.addClass("d-none");
            },
        });
    });

    // Khi click nút force delete
    $(document).on("click", ".btn-force-delete", function () {
        forceDeleteUrl = $(this).data("url");
        const title = $(this).data("title");
        currentTrashRow = $(this).closest("tr");

        $("#forceDeleteTitle").text(title || "bài viết này");
        const modal = new bootstrap.Modal($("#confirmForceDeleteModal"));
        modal.show();
    });

    // Khi nhấn nút "Xóa vĩnh viễn"
    $("#confirmForceDeleteBtn").on("click", function () {
        if (!forceDeleteUrl) {
            toastr.error("Không tìm thấy URL xóa.", "Thông báo");
            return;
        }

        const btn = $(this);
        const spinner = btn.find(".spinner-border");

        btn.prop("disabled", true);
        spinner.removeClass("d-none");

        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        $.ajax({
            url: forceDeleteUrl,
            type: "DELETE",
            success: function (res) {
                if (res.status) {
                    $("#confirmForceDeleteModal").modal("hide");

                    if (
                        typeof window.postsTrashTable !== "undefined" &&
                        currentTrashRow &&
                        currentTrashRow.length
                    ) {
                        window.postsTrashTable
                            .row(currentTrashRow)
                            .remove()
                            .draw(false);
                    } else {
                        location.reload();
                    }

                    toastr.success(
                        res.message || "Xóa vĩnh viễn thành công",
                        "Thông báo"
                    );
                } else {
                    toastr.error(
                        res.message || "Không thể xóa vĩnh viễn bài viết",
                        "Thông báo"
                    );
                }
            },
            error: function (xhr) {
                let message = "Lỗi khi xóa vĩnh viễn bài viết";
                if (xhr.responseJSON) {
                    message = xhr.responseJSON.message || message;
                } else if (xhr.status === 404) {
                    message = "Bài viết không tồn tại";
                } else if (xhr.status === 500) {
                    message = "Lỗi server. Vui lòng thử lại sau";
                }
                toastr.error(message, "Thông báo");
            },
            complete: function () {
                btn.prop("disabled", false);
                spinner.addClass("d-none");
            },
        });
    });

    // ======================================
    // 📦 XỬ LÝ BULK ACTIONS (CHỌN NHIỀU) - TRASH TAB
    // ======================================
    if (typeof window.selectedPostIds === "undefined") {
        window.selectedPostIds = [];
    }

    // Chọn tất cả
    $(document).on("change", "#selectAllTrash", function () {
        const isChecked = $(this).is(":checked");
        $("#datatable_blog_trash tbody .row-checkbox").prop(
            "checked",
            isChecked
        );
        updateSelectedPostIds();
    });

    // Chọn từng item - sử dụng event delegation cho dynamic content
    $(document).on(
        "change",
        "#datatable_blog_trash tbody .row-checkbox",
        function () {
            updateSelectedPostIds();
            // Update select all checkbox
            const totalCheckboxes = $(
                "#datatable_blog_trash tbody .row-checkbox"
            ).length;
            const checkedCheckboxes = $(
                "#datatable_blog_trash tbody .row-checkbox:checked"
            ).length;
            $("#selectAllTrash").prop(
                "checked",
                totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes
            );
        }
    );

    // Update selected IDs và hiển thị bulk actions
    function updateSelectedPostIds() {
        window.selectedPostIds = [];
        $("#datatable_blog_trash tbody .row-checkbox:checked").each(
            function () {
                window.selectedPostIds.push($(this).val());
            }
        );

        const count = window.selectedPostIds.length;
        $("#selectedCount strong").text(count);

        if (count > 0) {
            $("#bulkActionsContainer").slideDown();
        } else {
            $("#bulkActionsContainer").slideUp();
        }
    }

    // Reload table sau khi thao tác
    function reloadPostTrashTable() {
        if (typeof window.postsTrashTable !== "undefined") {
            window.postsTrashTable.draw();
            window.selectedPostIds = [];
            $("#selectAllTrash").prop("checked", false);
            $("#bulkActionsContainer").slideUp();
        }
    }

    // Bulk restore
    $(document).on("click", "#bulkRestoreBtn", function () {
        if (window.selectedPostIds.length === 0) {
            toastr.warning("Vui lòng chọn ít nhất một bài viết", "Thông báo");
            return;
        }

        $("#bulkRestoreCount").text(window.selectedPostIds.length);
        const modal = new bootstrap.Modal($("#bulkRestoreModal"));
        modal.show();
    });

    // Confirm bulk restore
    $(document).on("click", "#confirmBulkRestoreBtn", function () {
        const btn = $(this);
        const spinner = btn.find(".spinner-border");

        btn.prop("disabled", true);
        spinner.removeClass("d-none");

        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        $.ajax({
            url: window.postBulkRestoreUrl,
            type: "POST",
            data: { ids: window.selectedPostIds },
            success: function (res) {
                $("#bulkRestoreModal").modal("hide");
                if (res.status) {
                    toastr.success(res.message, "Thông báo");
                    // Reload cả bảng danh sách chính và bảng thùng rác
                    if (typeof window.postsTable !== "undefined") {
                        window.postsTable.draw();
                    }
                    reloadPostTrashTable();
                } else {
                    toastr.error(res.message, "Thông báo");
                }
            },
            error: function (xhr) {
                let message = "Lỗi khi khôi phục";
                if (xhr.responseJSON) {
                    message = xhr.responseJSON.message || message;
                }
                toastr.error(message, "Thông báo");
            },
            complete: function () {
                btn.prop("disabled", false);
                spinner.addClass("d-none");
            },
        });
    });

    // Bulk force delete
    $(document).on("click", "#bulkForceDeleteBtn", function () {
        if (window.selectedPostIds.length === 0) {
            toastr.warning("Vui lòng chọn ít nhất một bài viết", "Thông báo");
            return;
        }

        $("#bulkForceDeleteCount").text(window.selectedPostIds.length);
        const modal = new bootstrap.Modal($("#bulkForceDeleteModal"));
        modal.show();
    });

    // Confirm bulk force delete
    $(document).on("click", "#confirmBulkForceDeleteBtn", function () {
        const btn = $(this);
        const spinner = btn.find(".spinner-border");

        btn.prop("disabled", true);
        spinner.removeClass("d-none");

        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        $.ajax({
            url: window.postBulkForceDeleteUrl,
            type: "DELETE",
            data: { ids: window.selectedPostIds },
            success: function (res) {
                $("#bulkForceDeleteModal").modal("hide");
                if (res.status) {
                    toastr.success(res.message, "Thông báo");
                    reloadPostTrashTable();
                } else {
                    toastr.error(res.message, "Thông báo");
                }
            },
            error: function (xhr) {
                let message = "Lỗi khi xóa vĩnh viễn";
                if (xhr.responseJSON) {
                    message = xhr.responseJSON.message || message;
                }
                toastr.error(message, "Thông báo");
            },
            complete: function () {
                btn.prop("disabled", false);
                spinner.addClass("d-none");
            },
        });
    });

    // Reset khi chuyển tab
    $(document).on(
        "shown.bs.tab",
        'button[data-bs-target="#trash_tab"]',
        function () {
            window.selectedPostIds = [];
            $("#selectAllTrash").prop("checked", false);
            $("#bulkActionsContainer").hide();
        }
    );

    // ======================================
    // 📦 XỬ LÝ BULK ACTIONS (CHỌN NHIỀU) - POSTS TAB
    // ======================================
    if (typeof window.selectedPostIdsPosts === "undefined") {
        window.selectedPostIdsPosts = [];
    }

    // Chọn tất cả - Posts tab
    $(document).on("change", "#selectAllPosts", function () {
        const isChecked = $(this).is(":checked");
        $("#datatable_blog tbody .row-checkbox").prop("checked", isChecked);
        updateSelectedPostIdsPosts();
    });

    // Chọn từng item - Posts tab
    $(document).on(
        "change",
        "#datatable_blog tbody .row-checkbox",
        function () {
            updateSelectedPostIdsPosts();
            // Update select all checkbox
            const totalCheckboxes = $(
                "#datatable_blog tbody .row-checkbox"
            ).length;
            const checkedCheckboxes = $(
                "#datatable_blog tbody .row-checkbox:checked"
            ).length;
            $("#selectAllPosts").prop(
                "checked",
                totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes
            );
        }
    );

    // Update selected IDs và hiển thị bulk actions - Posts tab
    function updateSelectedPostIdsPosts() {
        window.selectedPostIdsPosts = [];
        $("#datatable_blog tbody .row-checkbox:checked").each(function () {
            window.selectedPostIdsPosts.push($(this).val());
        });

        const count = window.selectedPostIdsPosts.length;
        $("#selectedCountPosts strong").text(count);

        if (count > 0) {
            $("#bulkActionsContainerPosts").slideDown();
        } else {
            $("#bulkActionsContainerPosts").slideUp();
        }
    }

    // Reload table sau khi thao tác - Posts tab
    function reloadPostsTable() {
        if (typeof window.postsTable !== "undefined") {
            window.postsTable.draw();
            window.selectedPostIdsPosts = [];
            $("#selectAllPosts").prop("checked", false);
            $("#bulkActionsContainerPosts").slideUp();
        }
    }

    // Bulk delete - Posts tab
    $(document).on("click", "#bulkDeleteBtn", function () {
        if (window.selectedPostIdsPosts.length === 0) {
            toastr.warning("Vui lòng chọn ít nhất một bài viết", "Thông báo");
            return;
        }

        $("#bulkDeleteCount").text(window.selectedPostIdsPosts.length);
        const modal = new bootstrap.Modal($("#bulkDeleteModal"));
        modal.show();
    });

    // Confirm bulk delete - Posts tab
    $(document).on("click", "#confirmBulkDeleteBtn", function () {
        const btn = $(this);
        const spinner = btn.find(".spinner-border");

        btn.prop("disabled", true);
        spinner.removeClass("d-none");

        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        $.ajax({
            url: window.postBulkDeleteUrl,
            type: "DELETE",
            data: { ids: window.selectedPostIdsPosts },
            success: function (res) {
                $("#bulkDeleteModal").modal("hide");
                if (res.status) {
                    toastr.success(res.message, "Thông báo");
                    // Reload cả bảng danh sách chính và bảng thùng rác
                    reloadPostsTable();
                    if (typeof window.postsTrashTable !== "undefined") {
                        window.postsTrashTable.draw();
                    }
                } else {
                    toastr.error(res.message, "Thông báo");
                }
            },
            error: function (xhr) {
                let message = "Lỗi khi xóa";
                if (xhr.responseJSON) {
                    message = xhr.responseJSON.message || message;
                }
                toastr.error(message, "Thông báo");
            },
            complete: function () {
                btn.prop("disabled", false);
                spinner.addClass("d-none");
            },
        });
    });

    // Bulk move category - open modal
    $(document).on("click", "#bulkMoveBtn", function () {
        if (window.selectedPostIdsPosts.length === 0) {
            toastr.warning("Vui lòng chọn ít nhất một bài viết", "Thông báo");
            return;
        }
        $("#bulkMoveCount").text(window.selectedPostIdsPosts.length);
        const modal = new bootstrap.Modal($("#bulkMoveModal"));
        modal.show();
    });

    // Confirm bulk move category
    $(document).on("click", "#confirmBulkMoveBtn", function () {
        const categoryId = $("#bulkMoveCategory").val();
        if (!categoryId) {
            toastr.warning("Vui lòng chọn danh mục đích", "Thông báo");
            return;
        }
        const btn = $(this);
        const spinner = btn.find(".spinner-border");

        btn.prop("disabled", true);
        spinner.removeClass("d-none");

        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        $.ajax({
            url: window.postBulkMoveUrl,
            type: "POST",
            data: { ids: window.selectedPostIdsPosts, category_id: categoryId },
            success: function (res) {
                $("#bulkMoveModal").modal("hide");
                if (res.status) {
                    toastr.success(res.message, "Thông báo");
                    reloadPostsTable();
                    if (typeof window.postsTrashTable !== "undefined") {
                        window.postsTrashTable.draw();
                    }
                } else {
                    toastr.error(res.message, "Thông báo");
                }
            },
            error: function (xhr) {
                let message = "Lỗi khi chuyển danh mục";
                if (xhr.responseJSON) {
                    message = xhr.responseJSON.message || message;
                }
                toastr.error(message, "Thông báo");
            },
            complete: function () {
                btn.prop("disabled", false);
                spinner.addClass("d-none");
            },
        });
    });

    // Reset khi chuyển tab - Posts tab
    $(document).on(
        "shown.bs.tab",
        'button[data-bs-target="#posts_tab"]',
        function () {
            window.selectedPostIdsPosts = [];
            $("#selectAllPosts").prop("checked", false);
            $("#bulkActionsContainerPosts").hide();
        }
    );
});
