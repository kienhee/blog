/**
 * Admin Profile Change Password Page - Form Validation
 */
"use strict";

$(function () {
    // ================================
    // Constants & Selectors
    // ================================
    const $form = $("#formChangePassword");
    const $submitBtn = $("#submitBtn");
    const $resetBtn = $("#resetBtn");
    const $spinner = $submitBtn.find(".spinner-border");

    // ================================
    // Form Validation Setup
    // ================================
    if ($form.length && typeof FormValidation !== "undefined") {
        const validationRules = {
            currentPassword: {
                validators: {
                    notEmpty: {
                        message: "Vui lòng nhập mật khẩu hiện tại.",
                    },
                    stringLength: {
                        min: 6,
                        max: 255,
                        message: "Mật khẩu hiện tại phải từ 6 đến 255 ký tự.",
                    },
                },
            },
            newPassword: {
                validators: {
                    notEmpty: {
                        message: "Vui lòng nhập mật khẩu mới.",
                    },
                    stringLength: {
                        min: 6,
                        max: 255,
                        message: "Mật khẩu mới phải từ 6 đến 255 ký tự.",
                    },
                },
            },
            newPassword_confirmation: {
                validators: {
                    notEmpty: {
                        message: "Vui lòng xác nhận mật khẩu mới.",
                    },
                    stringLength: {
                        max: 255,
                        message: "Mật khẩu xác nhận không được vượt quá 255 ký tự.",
                    },
                    identical: {
                        compare: () => $form.find('[name="newPassword"]').val(),
                        message: "Mật khẩu xác nhận không khớp.",
                    },
                },
            },
        };

        const fvPassword = FormValidation.formValidation($form[0], {
            fields: validationRules,
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    rowSelector: ".mb-3",
                    eleInvalidClass: "is-invalid",
                    eleValidClass: "is-valid",
                }),
                autoFocus: new FormValidation.plugins.AutoFocus(),
                submitButton: new FormValidation.plugins.SubmitButton(),
            },
        });

        // ================================
        // Handle valid form submission
        // ================================
        fvPassword.on("core.form.valid", () => {
            // Disable submit button and show spinner
            $submitBtn.prop("disabled", true);
            $spinner.removeClass("d-none");

            $.ajax({
                url: $form.attr("action"),
                method: "POST",
                data: $form.serialize(),
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
                success: (res) => {
                    if (res?.status) {
                        // Hiển thị thông báo thành công ở field currentPassword
                        const $currentPasswordInput = $form.find('[name="currentPassword"]');
                        if ($currentPasswordInput.length) {
                            $currentPasswordInput.removeClass("is-invalid");
                            $currentPasswordInput.next(".invalid-feedback").remove();
                            $currentPasswordInput.addClass("is-valid");
                            $currentPasswordInput.after(
                                $(`<div class="valid-feedback d-block"></div>`).text(
                                    res.message || "Đổi mật khẩu thành công!"
                                )
                            );
                        }

                        toastr.success(
                            res.message || "Đổi mật khẩu thành công!",
                            "Thông báo"
                        );
                        $form[0].reset();
                        fvPassword.resetForm();
                        setTimeout(() => {
                            // Xóa valid feedback
                            $form.find(".valid-feedback").remove();
                            $form.find(".is-valid").removeClass("is-valid");
                        }, 3000);
                    } else {
                        toastr.error(
                            res?.message || "Không thể đổi mật khẩu",
                            "Thông báo"
                        );
                    }
                },
                error: (xhr) => {
                    // Display server validation errors
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        Object.entries(xhr.responseJSON.errors).forEach(([field, messages]) => {
                            const $input = $form.find(`[name="${field}"]`);
                            if ($input.length && messages[0]) {
                                $input.addClass("is-invalid");
                                // Remove existing feedback
                                $input.next(".invalid-feedback").remove();
                                $input.after(
                                    $(`<div class="invalid-feedback d-block"></div>`).text(messages[0])
                                );
                                // Revalidate field to update FormValidation state
                                fvPassword.revalidateField(field);
                            }
                        });
                    }
                    toastr.error(
                        xhr.responseJSON?.message || "Có lỗi xảy ra khi đổi mật khẩu",
                        "Thông báo"
                    );
                },
                complete: () => {
                    $submitBtn.prop("disabled", false);
                    $spinner.addClass("d-none");
                },
            });
        });

        // ================================
        // Clear server errors on input
        // ================================
        $form.find("input").on("input", function () {
            const $input = $(this);
            // Xóa server validation errors khi user bắt đầu nhập
            if ($input.hasClass("is-invalid")) {
                const $feedback = $input.next(".invalid-feedback");
                if ($feedback.length && $feedback.hasClass("d-block")) {
                    // Chỉ xóa nếu là server error (có class d-block)
                    $feedback.remove();
                    $input.removeClass("is-invalid");
                    // Revalidate field để cập nhật state
                    fvPassword.revalidateField($input[0]);
                }
            }
        });

        // ================================
        // Reset button handler
        // ================================
        if ($resetBtn.length) {
            $resetBtn.on("click", function (e) {
                e.preventDefault();
                $form[0].reset();
                fvPassword.resetForm();
            });
        }

        // Store instance globally for reuse
        window.fvPassword = fvPassword;
    }
});
