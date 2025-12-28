/**
 * Xác thực Trang
 */

"use strict";

import { initFormLoading } from "../../common/forms/form-loading.js";

const formAuthentication = document.querySelector("#formAuthentication");

document.addEventListener("DOMContentLoaded", function (e) {
    (function () {
        // Validation form cho việc thêm bản ghi mới
        if (formAuthentication) {
            // Xác định loại form dựa trên URL hoặc các field có trong form
            const url = window.location.pathname;
            const isForgotPassword = url.includes("forgot-password");
            const isResetPassword = url.includes("reset-password");

            // Cấu hình fields dựa trên loại form
            let fieldsConfig = {};

            if (isForgotPassword) {
                // Form quên mật khẩu - chỉ cần email
                fieldsConfig = {
                    email: {
                        validators: {
                            notEmpty: {
                                message: "Vui lòng nhập email của bạn",
                            },
                            emailAddress: {
                                message: "Vui lòng nhập địa chỉ email hợp lệ",
                            },
                        },
                    },
                };
            } else if (isResetPassword) {
                // Form đặt lại mật khẩu - cần password và password_confirmation
                fieldsConfig = {
                    password: {
                        validators: {
                            notEmpty: {
                                message: "Vui lòng nhập mật khẩu mới",
                            },
                            stringLength: {
                                min: 6,
                                message: "Mật khẩu phải có ít nhất 6 ký tự",
                            },
                        },
                    },
                    password_confirmation: {
                        validators: {
                            notEmpty: {
                                message: "Vui lòng xác nhận mật khẩu",
                            },
                            identical: {
                                compare: function () {
                                    return formAuthentication.querySelector(
                                        '[name="password"]'
                                    ).value;
                                },
                                message:
                                    "Mật khẩu và xác nhận không giống nhau",
                            },
                            stringLength: {
                                min: 6,
                                message: "Mật khẩu phải có ít nhất 6 ký tự",
                            },
                        },
                    },
                };
            } else {
                // Form đăng nhập
                fieldsConfig = {
                    login: {
                        validators: {
                            notEmpty: {
                                message: "Vui lòng nhập email",
                            },
                        },
                    },
                    password: {
                        validators: {
                            notEmpty: {
                                message: "Vui lòng nhập mật khẩu của bạn",
                            },
                            stringLength: {
                                min: 6,
                                message: "Mật khẩu phải có nhiều hơn 6 ký tự",
                            },
                        },
                    },
                    password_confirmation: {
                        validators: {
                            notEmpty: {
                                message: "Vui lòng xác nhận mật khẩu",
                            },
                            identical: {
                                compare: function () {
                                    return formAuthentication.querySelector(
                                        '[name="password"]'
                                    ).value;
                                },
                                message:
                                    "Mật khẩu và xác nhận không giống nhau",
                            },
                            stringLength: {
                                min: 6,
                                message: "Mật khẩu phải có nhiều hơn 6 ký tự",
                            },
                        },
                    },
                    terms: {
                        validators: {
                            notEmpty: {
                                message:
                                    "Vui lòng đồng ý các điều khoản & điều kiện",
                            },
                        },
                    },
                };
            }

            const fv = FormValidation.formValidation(formAuthentication, {
                fields: fieldsConfig,
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    bootstrap5: new FormValidation.plugins.Bootstrap5({
                        rowSelector: ".mb-3",
                        eleInvalidClass: "is-invalid",
                        eleValidClass: "is-valid",
                    }),
                    submitButton: new FormValidation.plugins.SubmitButton(),

                    defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
                    autoFocus: new FormValidation.plugins.AutoFocus(),
                },
                init: (instance) => {
                    instance.on("plugins.message.placed", function (e) {
                        if (
                            e.element.parentElement.classList.contains(
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

            // Xử lý loading state cho tất cả auth forms (login, forgot-password, reset-password)
            // Sử dụng hàm initFormLoading có sẵn
            initFormLoading(formAuthentication, {
                buttonId: "btnSubmit",
                useFormValidation: true,
                formValidationInstance: fv,
            });
        }

        // Xác nhận Hai Bước
        const numeralMask = document.querySelectorAll(".numeral-mask");

        // Masquerade Verification
        if (numeralMask.length) {
            numeralMask.forEach((e) => {
                new Cleave(e, {
                    numeral: true,
                });
            });
        }
    })();
});
