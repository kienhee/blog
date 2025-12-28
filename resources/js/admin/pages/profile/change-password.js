"use strict";

document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("formChangePassword");
    const submitBtn = document.getElementById("submitBtn");

    if (!form) return;

    const fv = FormValidation.formValidation(form, {
        fields: {
            currentPassword: {
                validators: {
                    notEmpty: {
                        message: "Vui lòng nhập mật khẩu hiện tại"
                    },
                    stringLength: {
                        min: 6,
                        max: 255,
                        message: "Mật khẩu phải từ 6 đến 255 ký tự"
                    }
                }
            },

            newPassword: {
                validators: {
                    notEmpty: {
                        message: "Vui lòng nhập mật khẩu mới"
                    },
                    stringLength: {
                        min: 6,
                        max: 255,
                        message: "Mật khẩu phải từ 6 đến 255 ký tự"
                    }
                }
            },

            newPassword_confirmation: {
                validators: {
                    notEmpty: {
                        message: "Vui lòng xác nhận mật khẩu mới"
                    },
                    stringLength: {
                        min: 6,
                        max: 255,
                        message: "Mật khẩu xác nhận phải từ 6 đến 255 ký tự"
                    },
                    identical: {
                        compare: () =>
                            form.querySelector('[name="newPassword"]').value,
                        message: "Mật khẩu xác nhận không khớp"
                    }
                }
            }
        },

        plugins: {
            trigger: new FormValidation.plugins.Trigger(),

            bootstrap5: new FormValidation.plugins.Bootstrap5({
                rowSelector: ".mb-3",
                eleInvalidClass: "is-invalid",
                eleValidClass: "is-valid"
            }),

            submitButton: new FormValidation.plugins.SubmitButton(),
            autoFocus: new FormValidation.plugins.AutoFocus()
        }
    });

    // ✅ Chỉ submit khi validate OK
    fv.on("core.form.valid", function () {
        submitBtn.disabled = true;
        submitBtn.querySelector(".spinner-border")?.classList.remove("d-none");

        form.submit();
    });

    // Optional: mở lại nút khi invalid
    fv.on("core.form.invalid", function () {
        submitBtn.disabled = false;
    });
});
