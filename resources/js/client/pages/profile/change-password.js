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
            $form
                .find('input[type="text"][id*="Password"]')
                .attr("type", "password");
            $form
                .find(".password-toggle-icon")
                .removeClass("bx-show")
                .addClass("bx-hide");

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
                            max: 255,
                            message:
                                "Mật khẩu hiện tại không được vượt quá 255 ký tự.",
                        },
                    },
                },
                newPassword: {
                    validators: {
                        notEmpty: { 
                            message: "Vui lòng nhập mật khẩu mới." 
                        },
                        stringLength: {
                            min: 6,
                            max: 255,
                            message: "Mật khẩu mới phải từ 6 đến 255 ký tự.",
                        },
                        callback: {
                            message: "Mật khẩu mới phải khác mật khẩu hiện tại.",
                            callback: function (value, validator, $field) {
                                const currentPassword = $form.find('[name="currentPassword"]').val();
                                if (currentPassword && value === currentPassword) {
                                    return false;
                                }
                                return true;
                            },
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
                            compare: () =>
                                $form.find('[name="newPassword"]').val(),
                            message: "Mật khẩu xác nhận không khớp.",
                        },
                    },
                },
            },
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    rowSelector: ".mb-3",
                    eleInvalidClass: "",
                    eleValidClass: "",
                }),
                autoFocus: new FormValidation.plugins.AutoFocus(),
                submitButton: new FormValidation.plugins.SubmitButton(),
            },
            init: (instance) => {
                instance.on("plugins.message.placed", (e) => {
                    if (
                        e.element.parentElement?.classList.contains(
                            "input-group"
                        )
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
                success: function (res) {
                    if (res?.status) {
                        toastr.success(
                            res.message || "Đổi mật khẩu thành công",
                            "Thông báo"
                        );
                        $form[0].reset();
                        if (window.fvPassword) window.fvPassword.resetForm();
                    } else {
                        toastr.error(
                            res?.message || "Không thể đổi mật khẩu",
                            "Thông báo"
                        );
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        const errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach((field) => {
                            const messages = errors[field];
                            const $input = $form.find(`[name="${field}"]`);
                            if ($input.length) {
                                $input.addClass("is-invalid");
                                const $feedback = $(
                                    '<div class="invalid-feedback d-block"></div>'
                                ).text(messages[0]);
                                $input.after($feedback);
                            }
                        });
                    }
                    toastr.error(
                        xhr.responseJSON?.message ||
                            "Có lỗi xảy ra khi đổi mật khẩu",
                        "Thông báo"
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

