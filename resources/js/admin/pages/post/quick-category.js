"use strict";

$(function () {
    // ======================================
    // 🏷️ QUICK ADD CATEGORY
    // ======================================
    $("#saveCategory").on("click", function () {
        const form = $("#addCategoryForm");
        const name = $("#category_name").val();

        if (!name) {
            toastr.error("Vui lòng nhập tên danh mục", "Thông báo");
            return;
        }

        $.ajax({
            url: window.categoryQuickStoreUrl,
            type: "POST",
            data: {
                name: name,
                _token: $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                if (response.status) {
                    const $categorySelect = $("#category_id");

                    const newOption = new Option(
                        response.data.name,
                        response.data.id,
                        true,
                        true
                    );
                    $categorySelect.append(newOption).trigger("change");

                    form[0].reset();
                    $("#addCategoryModal").modal("hide");
                    toastr.success("Thêm danh mục thành công", "Thông báo");
                }
            },
            error: function (xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    Object.keys(errors).forEach((key) => {
                        toastr.error(errors[key][0], "Thông báo");
                    });
                } else {
                    const message =
                        xhr.responseJSON?.message ||
                        "Đã có lỗi xảy ra. Vui lòng thử lại!";
                    toastr.error(message, "Thông báo");
                }
            },
        });
    });
});
