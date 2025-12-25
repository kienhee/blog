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
    let datatable = $("#category_datatable");
    let table = null;

    if (datatable.length) {
        let urlGetData = datatable.data("url");
        table = datatable.DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: urlGetData,
                data: function (d) {
                    d.created_at = $("#created_at").val();
                },
            },
            order: [[5, "desc"]],
            drawCallback: function (settings) {
                // Reset select all checkbox khi table redraw
                $("#selectAllCategories").prop("checked", false);
                if (
                    typeof window.selectedCategoryIdsCategories !== "undefined"
                ) {
                    window.selectedCategoryIdsCategories = [];
                }
                $("#bulkActionsContainerCategories").hide();
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
                { data: "name_html", name: "name" },
                { data: "description", name: "description" },
                {
                    data: "post_count_html",
                    name: "post_count",
                    orderable: true,
                    searchable: false,
                },
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
            table.draw();
        });

        // Reset filter
        $("#clearFilter").on("click", function () {
            $("#created_at").val("");
            if ($datePicker.length && $datePicker.data("flatpickr")) {
                $datePicker[0]._flatpickr.clear();
            }
            table.draw();
        });
    }

    // ======================================
    // 📦 XỬ LÝ BULK ACTIONS (CHỌN NHIỀU) - MAIN LIST
    // ======================================
    if (typeof window.selectedCategoryIdsCategories === "undefined") {
        window.selectedCategoryIdsCategories = [];
    }

    // Chọn tất cả
    $(document).on("change", "#selectAllCategories", function () {
        const isChecked = $(this).is(":checked");
        $("#category_datatable tbody .row-checkbox").prop("checked", isChecked);
        updateSelectedCategoryIdsCategories();
    });

    // Chọn từng item
    $(document).on(
        "change",
        "#category_datatable tbody .row-checkbox",
        function () {
            updateSelectedCategoryIdsCategories();
            const totalCheckboxes = $(
                "#category_datatable tbody .row-checkbox"
            ).length;
            const checkedCheckboxes = $(
                "#category_datatable tbody .row-checkbox:checked"
            ).length;
            $("#selectAllCategories").prop(
                "checked",
                totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes
            );
        }
    );

    // Update selected IDs và hiển thị bulk actions
    function updateSelectedCategoryIdsCategories() {
        window.selectedCategoryIdsCategories = [];
        $("#category_datatable tbody .row-checkbox:checked").each(function () {
            window.selectedCategoryIdsCategories.push($(this).val());
        });

        const count = window.selectedCategoryIdsCategories.length;
        $("#selectedCountCategories strong").text(count);

        if (count > 0) {
            $("#bulkActionsContainerCategories").slideDown();
        } else {
            $("#bulkActionsContainerCategories").slideUp();
        }
    }

    // Bulk delete
    $(document).on("click", "#bulkDeleteBtnCategories", function () {
        if (window.selectedCategoryIdsCategories.length === 0) {
            toastr.warning("Vui lòng chọn ít nhất một danh mục", "Thông báo");
            return;
        }

        $("#bulkDeleteCountCategories").text(
            window.selectedCategoryIdsCategories.length
        );
        const modal = new bootstrap.Modal($("#bulkDeleteModalCategories"));
        modal.show();
    });

    // Confirm bulk delete
    $(document).on("click", "#confirmBulkDeleteBtnCategories", function () {
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
            url: window.categoryBulkDeleteUrl,
            type: "DELETE",
            data: { ids: window.selectedCategoryIdsCategories },
            success: function (res) {
                $("#bulkDeleteModalCategories").modal("hide");
                if (res.status) {
                    toastr.success(res.message, "Thông báo");
                    if (table) {
                        table.draw();
                    }
                    window.selectedCategoryIdsCategories = [];
                    $("#selectAllCategories").prop("checked", false);
                    $("#bulkActionsContainerCategories").slideUp();
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
    let trashDatatable = $("#category_datatable_trash");
    if (trashDatatable.length) {
        let urlGetTrashedData = trashDatatable.data("url");
        window.categoryTrashTable = trashDatatable.DataTable({
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
                {
                    data: "post_count_html",
                    name: "post_count",
                    orderable: true,
                    searchable: false,
                },
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
                // Reset select all checkbox khi table redraw
                $("#selectAllTrash").prop("checked", false);
                if (typeof window.selectedCategoryIds !== "undefined") {
                    window.selectedCategoryIds = [];
                }
                $("#bulkActionsContainer").hide();
            },
        });

        // Reload trash table when tab is shown
        $('button[data-bs-target="#trash_tab"]').on(
            "shown.bs.tab",
            function () {
                window.categoryTrashTable.draw();
            }
        );

        // Update columns order
        window.categoryTrashTable.columns.adjust();
    }

    // ======================================
    // 📦 XỬ LÝ BULK ACTIONS (CHỌN NHIỀU) - TRASH TAB
    // ======================================
    if (typeof window.selectedCategoryIds === "undefined") {
        window.selectedCategoryIds = [];
    }

    // Chọn tất cả
    $(document).on("change", "#selectAllTrash", function () {
        const isChecked = $(this).is(":checked");
        $("#category_datatable_trash tbody .row-checkbox").prop(
            "checked",
            isChecked
        );
        updateSelectedCategoryIds();
    });

    // Chọn từng item
    $(document).on(
        "change",
        "#category_datatable_trash tbody .row-checkbox",
        function () {
            updateSelectedCategoryIds();
            const totalCheckboxes = $(
                "#category_datatable_trash tbody .row-checkbox"
            ).length;
            const checkedCheckboxes = $(
                "#category_datatable_trash tbody .row-checkbox:checked"
            ).length;
            $("#selectAllTrash").prop(
                "checked",
                totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes
            );
        }
    );

    // Update selected IDs và hiển thị bulk actions
    function updateSelectedCategoryIds() {
        window.selectedCategoryIds = [];
        $("#category_datatable_trash tbody .row-checkbox:checked").each(
            function () {
                window.selectedCategoryIds.push($(this).val());
            }
        );

        const count = window.selectedCategoryIds.length;
        $("#selectedCount strong").text(count);

        if (count > 0) {
            $("#bulkActionsContainer").slideDown();
        } else {
            $("#bulkActionsContainer").slideUp();
        }
    }

    // Reload table sau khi thao tác
    function reloadCategoryTrashTable() {
        if (typeof window.categoryTrashTable !== "undefined") {
            window.categoryTrashTable.draw();
            window.selectedCategoryIds = [];
            $("#selectAllTrash").prop("checked", false);
            $("#bulkActionsContainer").slideUp();
        }
    }

    // Bulk restore
    $(document).on("click", "#bulkRestoreBtn", function () {
        if (window.selectedCategoryIds.length === 0) {
            toastr.warning("Vui lòng chọn ít nhất một danh mục", "Thông báo");
            return;
        }

        $("#bulkRestoreCount").text(window.selectedCategoryIds.length);
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
            url: window.categoryBulkRestoreUrl,
            type: "POST",
            data: { ids: window.selectedCategoryIds },
            success: function (res) {
                $("#bulkRestoreModal").modal("hide");
                if (res.status) {
                    toastr.success(res.message, "Thông báo");
                    if (table) {
                        table.draw();
                    }
                    reloadCategoryTrashTable();
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
        if (window.selectedCategoryIds.length === 0) {
            toastr.warning("Vui lòng chọn ít nhất một danh mục", "Thông báo");
            return;
        }

        $("#bulkForceDeleteCount").text(window.selectedCategoryIds.length);
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
            url: window.categoryBulkForceDeleteUrl,
            type: "DELETE",
            data: { ids: window.selectedCategoryIds },
            success: function (res) {
                $("#bulkForceDeleteModal").modal("hide");
                if (res.status) {
                    toastr.success(res.message, "Thông báo");
                    reloadCategoryTrashTable();
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
            window.selectedCategoryIds = [];
            $("#selectAllTrash").prop("checked", false);
            $("#bulkActionsContainer").hide();
        }
    );

    // ======================================
    // 🗑️ XỬ LÝ XÓA VỚI BOOTSTRAP MODAL
    // ======================================
    let deleteUrl = null;
    let currentRow = null;
    let deleteCategoryId = null;

    // Khi click nút xóa
    $(document).on("click", ".btn-delete", function () {
        deleteUrl = $(this).data("url");
        const title = $(this).data("title");

        if (!deleteUrl || !deleteUrl.includes("/destroy/")) {
            console.error("Invalid delete URL:", deleteUrl);
            toastr.error("URL xóa không hợp lệ", "Thông báo");
            return;
        }

        deleteCategoryId = deleteUrl.split("/").pop();
        currentRow = $(this).closest("tr");

        // Gọi API để lấy thông tin chi tiết
        $.ajax({
            url: window.categoryDeleteInfoUrl.replace(":id", deleteCategoryId),
            type: "GET",
            success: function (res) {
                if (res.status && res.data) {
                    showDeleteModal(title, res.data);
                } else {
                    toastr.error(
                        "Không thể lấy thông tin danh mục",
                        "Thông báo"
                    );
                }
            },
            error: function (xhr) {
                console.error("Error getting delete info:", xhr);
                toastr.error("Không thể lấy thông tin danh mục", "Thông báo");
            },
        });
    });

    // Hiển thị modal xóa với thông tin chi tiết
    function showDeleteModal(title, data) {
        $("#deleteCategoryName").text(escapeHtml(title));
        $("#deleteChildrenCount").text(data.total_children_count || 0);
        $("#deletePostCount").text(data.direct_post_count || 0);

        const treeContainer = $("#deleteCategoryTreeContainer");
        const treeList = $("#deleteCategoryTree");
        treeList.empty();

        if (
            data.tree &&
            data.tree.length > 0 &&
            data.total_children_count > 0
        ) {
            renderCategoryTree(data.tree[0], treeList, 0);
            treeContainer.show();
            $("#deleteWarningChildren").show();
        } else {
            treeContainer.hide();
            $("#deleteWarningChildren").hide();
        }

        const modal = new bootstrap.Modal($("#confirmDeleteModal"));
        modal.show();
    }

    // Render category tree recursively
    function renderCategoryTree(node, container, level) {
        const indent = level * 20;
        const isRoot = level === 0;
        const iconClass = isRoot
            ? "bx bx-folder text-danger"
            : "bx bx-folder text-warning";
        const fontWeight = isRoot ? "fw-bold" : "";

        const li = $("<li>").css({
            "padding-left": indent + "px",
            "padding-top": "4px",
            "padding-bottom": "4px",
            "margin-bottom": "2px",
        });

        const icon = $("<i>").addClass(iconClass + " me-2");
        const name = $("<span>")
            .addClass(fontWeight)
            .text(escapeHtml(node.name));

        li.append(icon).append(name);
        container.append(li);

        if (node.children && node.children.length > 0) {
            node.children.forEach(function (child) {
                renderCategoryTree(child, container, level + 1);
            });
        }
    }

    // Helper function để escape HTML
    function escapeHtml(text) {
        if (!text) return "";
        const div = document.createElement("div");
        div.textContent = text;
        return div.innerHTML;
    }

    // Xử lý xóa
    $("#confirmDeleteBtn").on("click", function () {
        performDelete();
    });

    // Thực hiện xóa
    function performDelete() {
        const btn = $("#confirmDeleteBtn");
        const spinner = btn.find(".spinner-border");

        btn.prop("disabled", true);
        spinner.removeClass("d-none");

        if (!deleteUrl) {
            toastr.error("URL xóa không hợp lệ", "Thông báo");
            btn.prop("disabled", false);
            spinner.addClass("d-none");
            return;
        }

        $.ajax({
            url: deleteUrl,
            type: "DELETE",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                "X-Requested-With": "XMLHttpRequest",
            },
            success: function (res) {
                if (res.status) {
                    $("#confirmDeleteModal").modal("hide");
                    if (currentRow && table) {
                        table.row(currentRow).remove().draw(false);
                    }
                    toastr.success(res.message, "Thông báo");
                } else {
                    toastr.error(res.message, "Thông báo");
                }
            },
            error: function (xhr) {
                console.error("Delete error:", xhr);
                let errorMessage = "Lỗi khi xóa danh mục.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 405) {
                    errorMessage =
                        "Phương thức DELETE không được hỗ trợ. Vui lòng kiểm tra lại route.";
                } else if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMessage = response.message;
                        }
                    } catch (e) {
                        // Không phải JSON
                    }
                }
                toastr.error(errorMessage, "Thông báo");
            },
            complete: function () {
                btn.prop("disabled", false);
                spinner.addClass("d-none");
                deleteUrl = null;
                currentRow = null;
                deleteCategoryId = null;
            },
        });
    }

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

        if (!restoreUrl) {
            toastr.error("Không tìm thấy URL khôi phục.", "Thông báo");
            return;
        }

        $("#restoreTitle").text(title || "danh mục này");
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
                    if (table) {
                        table.draw();
                    }
                    if (typeof window.categoryTrashTable !== "undefined") {
                        window.categoryTrashTable.draw();
                    }
                    toastr.success(
                        res.message || "Khôi phục thành công",
                        "Thông báo"
                    );
                } else {
                    toastr.error(
                        res.message || "Không thể khôi phục danh mục",
                        "Thông báo"
                    );
                }
            },
            error: function (xhr) {
                let message = "Lỗi khi khôi phục danh mục";
                if (xhr.responseJSON) {
                    message = xhr.responseJSON.message || message;
                } else if (xhr.status === 404) {
                    message = "Danh mục không tồn tại";
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

        if (!forceDeleteUrl) {
            toastr.error("Không tìm thấy URL xóa.", "Thông báo");
            return;
        }

        $("#forceDeleteTitle").text(title || "danh mục này");
        // Kiểm tra số bài viết để hiển thị cảnh báo bổ sung
        let postCount = 0;
        if (currentTrashRow && currentTrashRow.length) {
            const postCountText = currentTrashRow
                .find("td")
                .eq(3)
                .text()
                .trim();
            const parsed = parseInt(postCountText.replace(/[^0-9]/g, ""), 10);
            postCount = isNaN(parsed) ? 0 : parsed;
        }
        const $forceDeletePostAlert = $("#forceDeletePostAlert");
        if ($forceDeletePostAlert.length) {
            if (postCount > 0) {
                $forceDeletePostAlert.removeClass("d-none");
            } else {
                $forceDeletePostAlert.addClass("d-none");
            }
        }
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
                        typeof window.categoryTrashTable !== "undefined" &&
                        currentTrashRow &&
                        currentTrashRow.length
                    ) {
                        window.categoryTrashTable
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
                        res.message || "Không thể xóa vĩnh viễn danh mục",
                        "Thông báo"
                    );
                }
            },
            error: function (xhr) {
                let message = "Lỗi khi xóa vĩnh viễn danh mục";
                if (xhr.responseJSON) {
                    message = xhr.responseJSON.message || message;
                } else if (xhr.status === 404) {
                    message = "Danh mục không tồn tại";
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
