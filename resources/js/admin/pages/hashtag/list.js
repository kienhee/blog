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
    // 📋 KHỞI TẠO DATATABLE CHO DANH SÁCH
    // ======================================
    let datatable = $("#hashtag_datatable");

    if (datatable.length) {
        let urlGetData = datatable.data("url");
        window.hashtagTable = datatable.DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: urlGetData,
                data: function (d) {
                    d.created_at = $("#created_at").val();
                },
            },
            order: [[4, "desc"]],
            drawCallback: function (settings) {
                // Reset select all checkbox khi table redraw
                $("#selectAllHashtags").prop("checked", false);
                if (typeof window.selectedHashtagIdsHashtags !== "undefined") {
                    window.selectedHashtagIdsHashtags = [];
                }
                $("#bulkActionsContainerHashtags").hide();
            },
            language: {
                url:
                    $("input[name='datatables_vi']").val() ||
                    window.datatablesViUrl,
                searchPlaceholder: "Tìm kiếm theo tên...",
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
                { data: "name_html", name: "name" },
                { data: "slug_html", name: "slug" },
                {
                    data: "created_at_html",
                    name: "created_at",
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

        // Filter
        $("#created_at").on("change", function () {
            window.hashtagTable.draw();
        });

        // Reset filter
        $("#clearFilter").on("click", function () {
            $("#created_at").val("");
            if ($datePicker.length && $datePicker.data("flatpickr")) {
                $datePicker[0]._flatpickr.clear();
            }
            window.hashtagTable.draw();
        });
    }

    // ======================================
    // 📦 XỬ LÝ BULK ACTIONS (CHỌN NHIỀU) - MAIN LIST
    // ======================================
    if (typeof window.selectedHashtagIdsHashtags === "undefined") {
        window.selectedHashtagIdsHashtags = [];
    }

    // Chọn tất cả
    $(document).on("change", "#selectAllHashtags", function () {
        const isChecked = $(this).is(":checked");
        $("#hashtag_datatable tbody .row-checkbox").prop("checked", isChecked);
        updateSelectedHashtagIdsHashtags();
    });

    // Chọn từng item
    $(document).on(
        "change",
        "#hashtag_datatable tbody .row-checkbox",
        function () {
            updateSelectedHashtagIdsHashtags();
            const totalCheckboxes = $(
                "#hashtag_datatable tbody .row-checkbox"
            ).length;
            const checkedCheckboxes = $(
                "#hashtag_datatable tbody .row-checkbox:checked"
            ).length;
            $("#selectAllHashtags").prop(
                "checked",
                totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes
            );
        }
    );

    // Update selected IDs và hiển thị bulk actions
    function updateSelectedHashtagIdsHashtags() {
        window.selectedHashtagIdsHashtags = [];
        $("#hashtag_datatable tbody .row-checkbox:checked").each(function () {
            window.selectedHashtagIdsHashtags.push($(this).val());
        });

        const count = window.selectedHashtagIdsHashtags.length;
        $("#selectedCountHashtags strong").text(count);

        if (count > 0) {
            $("#bulkActionsContainerHashtags").slideDown();
        } else {
            $("#bulkActionsContainerHashtags").slideUp();
        }
    }

    // Bulk delete
    $(document).on("click", "#bulkDeleteBtnHashtags", function () {
        if (window.selectedHashtagIdsHashtags.length === 0) {
            toastr.warning("Vui lòng chọn ít nhất một hashtag", "Thông báo");
            return;
        }

        $("#bulkDeleteCountHashtags").text(
            window.selectedHashtagIdsHashtags.length
        );
        const modal = new bootstrap.Modal($("#bulkDeleteModalHashtags"));
        modal.show();
    });

    // Confirm bulk delete
    $(document).on("click", "#confirmBulkDeleteBtnHashtags", function () {
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
            url: window.hashtagBulkDeleteUrl,
            type: "DELETE",
            data: { ids: window.selectedHashtagIdsHashtags },
            success: function (res) {
                $("#bulkDeleteModalHashtags").modal("hide");
                if (res.status) {
                    toastr.success(res.message, "Thông báo");
                    if (typeof window.hashtagTable !== "undefined") {
                        window.hashtagTable.draw();
                    }
                    window.selectedHashtagIdsHashtags = [];
                    $("#selectAllHashtags").prop("checked", false);
                    $("#bulkActionsContainerHashtags").slideUp();
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

    // ======================================
    // 🗑️ KHỞI TẠO DATATABLE CHO THÙNG RÁC
    // ======================================
    let trashDatatable = $("#hashtag_datatable_trash");
    if (trashDatatable.length) {
        let urlGetTrashedData = trashDatatable.data("url");
        window.hashtagTrashTable = trashDatatable.DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: urlGetTrashedData,
                data: function (d) {
                    d.created_at = $("#created_at").val();
                },
            },
            order: [[4, "desc"]],
            language: {
                url: $("input[name='datatables_vi']").val(),
                searchPlaceholder: "Tìm kiếm theo tên...",
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
                { data: "name_html", name: "name" },
                { data: "slug_html", name: "slug" },
                {
                    data: "deleted_at_html",
                    name: "deleted_at",
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
                const $selectAll = $(
                    "#hashtag_datatable_trash #selectAllTrash"
                );
                if ($selectAll.length) {
                    $selectAll.prop("checked", false);
                }
                if (typeof window.selectedHashtagIds !== "undefined") {
                    window.selectedHashtagIds = [];
                }
                const $bulkContainer = $("#bulkActionsContainer");
                if ($bulkContainer.length) {
                    $bulkContainer.hide();
                }
            },
        });

        // Reload trash table when tab is shown
        $('button[data-bs-target="#trash_tab"]').on(
            "shown.bs.tab",
            function () {
                window.hashtagTrashTable.draw();
            }
        );
    }

    // ======================================
    // 📦 XỬ LÝ BULK ACTIONS (CHỌN NHIỀU) - TRASH TAB
    // ======================================
    if (typeof window.selectedHashtagIds === "undefined") {
        window.selectedHashtagIds = [];
    }

    // Chọn tất cả
    $(document).on(
        "change",
        "#hashtag_datatable_trash #selectAllTrash",
        function () {
            const isChecked = $(this).is(":checked");
            $("#hashtag_datatable_trash tbody .row-checkbox").prop(
                "checked",
                isChecked
            );
            updateSelectedHashtagIds();
        }
    );

    // Chọn từng item
    $(document).on(
        "change",
        "#hashtag_datatable_trash tbody .row-checkbox",
        function () {
            updateSelectedHashtagIds();
            const totalCheckboxes = $(
                "#hashtag_datatable_trash tbody .row-checkbox"
            ).length;
            const checkedCheckboxes = $(
                "#hashtag_datatable_trash tbody .row-checkbox:checked"
            ).length;
            $("#hashtag_datatable_trash #selectAllTrash").prop(
                "checked",
                totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes
            );
        }
    );

    // Update selected IDs và hiển thị bulk actions
    function updateSelectedHashtagIds() {
        window.selectedHashtagIds = [];
        $("#hashtag_datatable_trash tbody .row-checkbox:checked").each(
            function () {
                window.selectedHashtagIds.push($(this).val());
            }
        );

        const count = window.selectedHashtagIds.length;
        $("#selectedCount strong").text(count);

        if (count > 0) {
            $("#bulkActionsContainer").slideDown();
        } else {
            $("#bulkActionsContainer").slideUp();
        }
    }

    // Reload table sau khi thao tác
    function reloadHashtagTrashTable() {
        if (typeof window.hashtagTrashTable !== "undefined") {
            window.hashtagTrashTable.draw();
            window.selectedHashtagIds = [];
            $("#hashtag_datatable_trash #selectAllTrash").prop(
                "checked",
                false
            );
            $("#bulkActionsContainer").slideUp();
        }
    }

    // Bulk restore
    $(document).on("click", "#bulkRestoreBtn", function () {
        if (window.selectedHashtagIds.length === 0) {
            toastr.warning("Vui lòng chọn ít nhất một hashtag", "Thông báo");
            return;
        }

        $("#bulkRestoreCount").text(window.selectedHashtagIds.length);
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
            url: window.hashtagBulkRestoreUrl,
            type: "POST",
            data: { ids: window.selectedHashtagIds },
            success: function (res) {
                $("#bulkRestoreModal").modal("hide");
                if (res.status) {
                    toastr.success(res.message, "Thông báo");
                    if (typeof window.hashtagTable !== "undefined") {
                        window.hashtagTable.draw();
                    }
                    reloadHashtagTrashTable();
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
        if (window.selectedHashtagIds.length === 0) {
            toastr.warning("Vui lòng chọn ít nhất một hashtag", "Thông báo");
            return;
        }

        $("#bulkForceDeleteCount").text(window.selectedHashtagIds.length);
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
            url: window.hashtagBulkForceDeleteUrl,
            type: "DELETE",
            data: { ids: window.selectedHashtagIds },
            success: function (res) {
                $("#bulkForceDeleteModal").modal("hide");
                if (res.status) {
                    toastr.success(res.message, "Thông báo");
                    reloadHashtagTrashTable();
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
            window.selectedHashtagIds = [];
            $("#hashtag_datatable_trash #selectAllTrash").prop(
                "checked",
                false
            );
            $("#bulkActionsContainer").hide();
        }
    );

    // ======================================
    // 🗑️ XỬ LÝ XÓA VỚI BOOTSTRAP MODAL
    // ======================================
    let deleteUrl = null;
    let currentRow = null;

    // Khi click nút xóa
    $(document).on("click", ".btn-delete", function () {
        deleteUrl = $(this).data("url");
        const title = $(this).data("title");
        currentRow = $(this).closest("tr");

        $("#deleteTitle").text(title || "hashtag này");
        const modal = new bootstrap.Modal($("#confirmDeleteModal"));
        modal.show();
    });

    // Khi nhấn nút "Xóa"
    $("#confirmDeleteBtn").on("click", function () {
        if (!deleteUrl) {
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
            url: deleteUrl,
            type: "DELETE",
            success: function (res) {
                if (res.status) {
                    $("#confirmDeleteModal").modal("hide");
                    if (
                        typeof window.hashtagTable !== "undefined" &&
                        currentRow &&
                        currentRow.length
                    ) {
                        window.hashtagTable
                            .row(currentRow)
                            .remove()
                            .draw(false);
                    } else {
                        location.reload();
                    }
                    toastr.success(
                        res.message || "Xóa thành công",
                        "Thông báo"
                    );
                } else {
                    toastr.error(
                        res.message || "Không thể xóa hashtag",
                        "Thông báo"
                    );
                }
            },
            error: function (xhr) {
                let message = "Lỗi khi xóa hashtag";
                if (xhr.responseJSON) {
                    message = xhr.responseJSON.message || message;
                } else if (xhr.status === 404) {
                    message = "Hashtag không tồn tại";
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

        $("#restoreTitle").text(title || "hashtag này");
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
                    if (typeof window.hashtagTable !== "undefined") {
                        window.hashtagTable.draw();
                    }
                    if (typeof window.hashtagTrashTable !== "undefined") {
                        window.hashtagTrashTable.draw();
                    }
                    toastr.success(
                        res.message || "Khôi phục thành công",
                        "Thông báo"
                    );
                } else {
                    toastr.error(
                        res.message || "Không thể khôi phục hashtag",
                        "Thông báo"
                    );
                }
            },
            error: function (xhr) {
                let message = "Lỗi khi khôi phục hashtag";
                if (xhr.responseJSON) {
                    message = xhr.responseJSON.message || message;
                } else if (xhr.status === 404) {
                    message = "Hashtag không tồn tại";
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

        $("#forceDeleteTitle").text(title || "hashtag này");
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
                        typeof window.hashtagTrashTable !== "undefined" &&
                        currentTrashRow &&
                        currentTrashRow.length
                    ) {
                        window.hashtagTrashTable
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
                        res.message || "Không thể xóa vĩnh viễn hashtag",
                        "Thông báo"
                    );
                }
            },
            error: function (xhr) {
                let message = "Lỗi khi xóa vĩnh viễn hashtag";
                if (xhr.responseJSON) {
                    message = xhr.responseJSON.message || message;
                } else if (xhr.status === 404) {
                    message = "Hashtag không tồn tại";
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
});
