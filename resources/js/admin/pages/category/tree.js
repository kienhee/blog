"use strict";

$(function () {
    // ======================================
    // 🌳 TREE VIEW (jstree) - LIST PAGE
    // ======================================
    const theme = $("html").hasClass("light-style")
        ? "default"
        : "default-dark";

    // Hàm cấu hình chung cho jstree
    const getTreeConfig = (url, updateUrl) => ({
        core: {
            themes: { name: theme },
            data: {
                url: url,
                dataType: "json",
            },
            check_callback: function (
                operation,
                node,
                node_parent,
                node_position
            ) {
                if (operation === "move_node") {
                    // Không cho phép kéo danh mục "Chưa phân loại" (ID 9999)
                    const nodeId = typeof node === "object" ? node.id : node;
                    if (nodeId && String(nodeId) === "9999") {
                        return false;
                    }

                    const parentId =
                        typeof node_parent === "object"
                            ? node_parent.id
                            : node_parent;
                    // Không cho phép kéo danh mục khác vào làm con của "Chưa phân loại"
                    if (parentId && String(parentId) === "9999") {
                        return false;
                    }
                    if (parentId && parentId === node.id) {
                        return false;
                    }
                }
                return true;
            },
        },
        plugins: ["types", "state", "dnd"],
        types: {
            default: { icon: "bx bx-folder" },
        },
    });

    // Danh sách các tree cần khởi tạo
    const trees = [
        {
            selector: "#jstree-ajax-post",
            url: $("#jstree-ajax-post").data("url"),
            type: "post",
        },
    ];

    // Khởi tạo jstree cho từng phần tử
    trees.forEach((tree) => {
        const $element = $(tree.selector);
        if ($element.length) {
            const updateUrl = window.categoryUpdateOrderUrl;

            $element.jstree(getTreeConfig(tree.url, updateUrl));

            // Xử lý khi drag drop hoàn thành
            $element.on("move_node.jstree", function (e, data) {
                const nodeId = data.node.id;
                const newParentId = data.parent === "#" ? null : data.parent;
                const position =
                    data.position !== undefined ? data.position : null;

                const csrfToken =
                    $('meta[name="csrf-token"]').attr("content") ||
                    $('input[name="_token"]').val() ||
                    window.Laravel?.csrfToken;

                $.ajax({
                    url: updateUrl,
                    type: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                    },
                    data: {
                        id: nodeId,
                        parent_id: newParentId,
                        position: position,
                        _token: csrfToken,
                    },
                    success: function (response) {
                        if (response.status) {
                            toastr.success(
                                response.message || "Cập nhật thành công",
                                "Thông báo"
                            );
                        } else {
                            toastr.error(
                                response.message || "Có lỗi xảy ra",
                                "Thông báo"
                            );
                            $element.jstree("refresh");
                        }
                    },
                    error: function (xhr) {
                        let errorMessage = "Có lỗi xảy ra khi cập nhật";
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (
                            xhr.responseJSON &&
                            xhr.responseJSON.errors
                        ) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors)
                                .flat()
                                .join(", ");
                        }
                        toastr.error(errorMessage, "Thông báo");
                        $element.jstree("refresh");
                    },
                });
            });

            // Flag để theo dõi drag
            let clickTimer = null;
            let isDragging = false;
            let currentNode = null;

            // Reset flag khi drag kết thúc
            $element.on("move_node.jstree", function () {
                isDragging = false;
                if (clickTimer) {
                    clearTimeout(clickTimer);
                    clickTimer = null;
                }
            });

            // Click vào node để chuyển đến trang edit
            $element.on("click", ".jstree-anchor", function (e) {
                if (
                    $(e.target).hasClass("jstree-icon") ||
                    $(e.target).closest(".jstree-icon").length
                ) {
                    return;
                }

                const $anchor = $(this);
                const instance = $element.jstree(true);
                const node = instance.get_node($anchor);

                if (!node || !node.id || node.id === "#" || isNaN(node.id)) {
                    return;
                }

                // Chặn click vào danh mục "Chưa phân loại" (ID 9999)
                if (String(node.id) === "9999") {
                    toastr.warning(
                        "Đây là danh mục hệ thống 'Chưa phân loại', không thể chỉnh sửa.",
                        "Thông báo"
                    );
                    return;
                }

                currentNode = node.id;

                if (clickTimer) {
                    clearTimeout(clickTimer);
                }

                clickTimer = setTimeout(function () {
                    if (currentNode && !isDragging) {
                        const editUrl = window.categoryEditUrl.replace(
                            ":id",
                            currentNode
                        );
                        window.location.href = editUrl;
                    }
                    clickTimer = null;
                }, 300);
            });

            // Đánh dấu là đang drag
            $element.on("drag_start.jstree", function () {
                isDragging = true;
                if (clickTimer) {
                    clearTimeout(clickTimer);
                    clickTimer = null;
                }
            });
        }
    });

    // Reload jsTree khi chuyển sang tab "Tổng quan"
    $(document).on(
        "shown.bs.tab",
        'button[data-bs-target="#tree_view_tab"]',
        function () {
            const $tree = $("#jstree-ajax-post");
            if ($tree.length) {
                const instance = $tree.jstree(true);
                if (instance) {
                    instance.refresh();
                }
            }
        }
    );
});
