/**
 * Client Profile Change Password Page - Form Validation
 */
"use strict";

$(function () {
    const $form = $("#formChangePassword");
    const $reset = $("#resetBtn");
    const $submit = $("#submitBtn");
    const hasErrors = window.hasPasswordErrors || false;

    /**
     * Reset form
     */
    if ($reset.length) {
        $reset.on("click", function (e) {
            e.preventDefault();

            $form[0].reset();
            $form.find(".is-invalid").removeClass("is-invalid");
            $form.find(".invalid-feedback").remove();

            if (window.fvPassword) window.fvPassword.resetForm();
        });
    }

    /**
     * Form Validation for Change Password
     */
    if ($form.length) {
        const fv = FormValidation.formValidation($form[0], {
            fields: {
                currentPassword: {
                    validators: {
                        notEmpty: {
                            message: "Vui lòng nhập mật khẩu hiện tại.",
                        },
                        stringLength: {
                            min: 6,
                            max: 255,
                            message: "Mật khẩu mới phải từ 6 đến 255 ký tự.",
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
                            message:
                                "Mật khẩu xác nhận không được vượt quá 255 ký tự.",
                        },
                        identical: {
                            compare: () => $form.find('[name="newPassword"]').val(),
                            message: "Mật khẩu xác nhận không khớp.",
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
                autoFocus: new FormValidation.plugins.AutoFocus(),
                submitButton: new FormValidation.plugins.SubmitButton(),
            },
            init: (instance) => {
                instance.on("plugins.message.placed", (e) => {
                    if (
                        e.element.parentElement?.classList.contains("input-group")
                    ) {
                        e.element.parentElement.insertAdjacentElement(
                            "afterend",
                            e.messageElement
                        );
                    }
                });
            },
        });

        // Ngăn chặn form submit trực tiếp, chỉ cho phép submit khi validation pass
        $form.on("submit", function (e) {
            e.preventDefault();
            // Validation sẽ được trigger bởi SubmitButton plugin
            // Nếu validation pass, event "core.form.valid" sẽ được trigger
        });

        // On valid submit - chỉ được trigger khi validation pass
        fv.on("core.form.valid", () => {
            // clear old errors
            $form.find(".is-invalid").removeClass("is-invalid");
            $form.find(".invalid-feedback").remove();

            $submit.prop("disabled", true);
            $submit.find(".spinner-border").removeClass("d-none");

            $.ajax({
                url: $form.attr("action"),
                method: "POST",
                data: $form.serialize(),
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
                success: function (res) {
                    if (res?.status) {
                        toastr.success(
                            res.message || "Đổi mật khẩu thành công!",
                            "Thành công",
                            {
                                timeOut: 5000,
                                positionClass: "toast-top-right",
                            }
                        );
                        // Reset form after success
                        setTimeout(() => {
                            $form[0].reset();
                            if (window.fvPassword) window.fvPassword.resetForm();
                        }, 1000);
                    } else {
                        toastr.error(
                            res?.message || "Không thể đổi mật khẩu",
                            "Lỗi",
                            {
                                timeOut: 5000,
                                positionClass: "toast-top-right",
                            }
                        );
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        const errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach((field) => {
                            const messages = errors[field];
                            // Đảm bảo messages là array
                            const messageArray = Array.isArray(messages) ? messages : [messages];
                            const $input = $form.find(`[name="${field}"]`);
                            if ($input.length && messageArray.length > 0) {
                                // Xóa tất cả feedback cũ (cả từ FormValidation và manual)
                                $input.next('.invalid-feedback').remove();
                                $input.siblings('.invalid-feedback').remove();
                                $input.parent().next('.invalid-feedback').remove();
                                
                                // Xóa class invalid cũ
                                $input.removeClass("is-invalid");
                                
                                // Thêm class invalid
                                $input.addClass("is-invalid");
                                
                                // Thêm feedback mới
                                const $feedback = $(
                                    '<div class="invalid-feedback d-block"></div>'
                                ).text(messageArray[0]);
                                
                                // Tìm vị trí phù hợp để chèn feedback
                                if ($input.parent().hasClass('input-group')) {
                                    $input.parent().after($feedback);
                                } else {
                                    $input.after($feedback);
                                }
                                
                                // Trigger validation lại từ FormValidation plugin để cập nhật state
                                if (window.fvPassword && typeof window.fvPassword.validateField === 'function') {
                                    window.fvPassword.validateField($input[0]).catch(function(err) {
                                        console.error('Validation error:', err);
                                    });
                                }
                            }
                        });
                    }
                    toastr.error(
                        xhr.responseJSON?.message ||
                            "Có lỗi xảy ra khi đổi mật khẩu",
                        "Lỗi",
                        {
                            timeOut: 5000,
                            positionClass: "toast-top-right",
                        }
                    );
                },
                complete: function () {
                    $submit.prop("disabled", false);
                    $submit.find(".spinner-border").addClass("d-none");
                },
            });
        });

        // Store instance
        window.fvPassword = fv;

        // Trigger validation khi input thay đổi (để hiển thị lỗi required khi xóa data)
        $form.find('input').on('input blur', function() {
            const $input = $(this);
            if (window.fvPassword && typeof window.fvPassword.validateField === 'function') {
                // Validate field và cập nhật UI
                window.fvPassword.validateField(this).then(function(result) {
                    if (result && result.valid) {
                        $input.removeClass("is-invalid");
                        // Xóa feedback nếu field valid
                        $input.next('.invalid-feedback').remove();
                        $input.siblings('.invalid-feedback').remove();
                    } else if (result && !result.valid) {
                        $input.addClass("is-invalid");
                    }
                }).catch(function(err) {
                    console.error('Validation error:', err);
                });
            }
        });

        // Auto focus on first error field if there are errors
        if (hasErrors) {
            setTimeout(() => {
                $("html, body").animate(
                    { scrollTop: $form.offset().top - 100 },
                    300
                );
                $form.find(".is-invalid:first").focus();
            }, 300);
        }
    }
});
