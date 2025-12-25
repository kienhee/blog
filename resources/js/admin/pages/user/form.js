"use strict";

document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("form_user");

    // Khởi tạo date picker cho ngày sinh (dùng chung cho create & edit)
    const $birthday = $("#birthday");
    if ($birthday.length && typeof flatpickr !== "undefined") {
        flatpickr($birthday[0], {
            dateFormat: "Y-m-d",
            allowInput: true,
            altInput: true,
            altFormat: "d/m/Y",
        });
    }

    if (!form || typeof FormValidation === "undefined") {
        return;
    }

    // Phân biệt create / edit dựa vào _method=PUT
    const isEdit =
        form.querySelector('input[name="_method"][value="PUT"]') !== null;

    const fieldsConfig = {
        email: {
            validators: {
                notEmpty: {
                    message: "Vui lòng nhập email",
                },
                emailAddress: {
                    message: "Email không hợp lệ",
                },
                stringLength: {
                    max: 254,
                    message: "Email không được vượt quá 254 ký tự",
                },
            },
        },
        full_name: {
            validators: {
                notEmpty: {
                    message: "Vui lòng nhập họ và tên",
                },
                stringLength: {
                    min: 2,
                    max: 150,
                    message: "Họ và tên phải từ 2 đến 150 ký tự",
                },
            },
        },
        phone: {
            validators: {
                stringLength: {
                    max: 20,
                    message: "Số điện thoại không được vượt quá 20 ký tự",
                },
                regexp: {
                    regexp: /^[0-9]*$/,
                    message: "Số điện thoại chỉ được chứa số",
                },
            },
        },
        description: {
            validators: {
                stringLength: {
                    max: 255,
                    message: "Giới thiệu không được vượt quá 255 ký tự",
                },
            },
        },
        twitter_url: {
            validators: {
                uri: {
                    message: "URL Twitter không hợp lệ",
                },
                stringLength: {
                    max: 255,
                    message: "URL không được vượt quá 255 ký tự",
                },
                callback: {
                    message:
                        "URL Twitter phải bắt đầu với https://twitter.com/ hoặc https://x.com/",
                    callback: function (input) {
                        const value = input.value;
                        if (!value) return true;
                        return (
                            value.startsWith("https://twitter.com/") ||
                            value.startsWith("https://x.com/")
                        );
                    },
                },
            },
        },
        facebook_url: {
            validators: {
                uri: {
                    message: "URL Facebook không hợp lệ",
                },
                stringLength: {
                    max: 255,
                    message: "URL không được vượt quá 255 ký tự",
                },
                callback: {
                    message:
                        "URL Facebook phải bắt đầu với https://facebook.com/ hoặc https://fb.com/",
                    callback: function (input) {
                        const value = input.value;
                        if (!value) return true;
                        return (
                            value.startsWith("https://facebook.com/") ||
                            value.startsWith("https://fb.com/") ||
                            value.startsWith("https://www.facebook.com/")
                        );
                    },
                },
            },
        },
        instagram_url: {
            validators: {
                uri: {
                    message: "URL Instagram không hợp lệ",
                },
                stringLength: {
                    max: 255,
                    message: "URL không được vượt quá 255 ký tự",
                },
                callback: {
                    message:
                        "URL Instagram phải bắt đầu với https://instagram.com/",
                    callback: function (input) {
                        const value = input.value;
                        if (!value) return true;
                        return (
                            value.startsWith("https://instagram.com/") ||
                            value.startsWith("https://www.instagram.com/")
                        );
                    },
                },
            },
        },
        linkedin_url: {
            validators: {
                uri: {
                    message: "URL LinkedIn không hợp lệ",
                },
                stringLength: {
                    max: 255,
                    message: "URL không được vượt quá 255 ký tự",
                },
                callback: {
                    message:
                        "URL LinkedIn phải bắt đầu với https://linkedin.com/in/",
                    callback: function (input) {
                        const value = input.value;
                        if (!value) return true;
                        return (
                            value.startsWith("https://linkedin.com/in/") ||
                            value.startsWith("https://www.linkedin.com/in/") ||
                            value.startsWith("https://linkedin.com/company/") ||
                            value.startsWith(
                                "https://www.linkedin.com/company/"
                            )
                        );
                    },
                },
            },
        },
    };

    // Thêm rule cho password nếu có field (thường chỉ ở trang create)
    const passwordInput = form.querySelector('[name="password"]');
    const passwordConfirmationInput = form.querySelector(
        '[name="password_confirmation"]'
    );

    if (passwordInput && !isEdit) {
        // CREATE: password bắt buộc
        fieldsConfig.password = {
            validators: {
                notEmpty: {
                    message: "Vui lòng nhập mật khẩu",
                },
                stringLength: {
                    min: 6,
                    max: 255,
                    message: "Mật khẩu phải từ 6 đến 255 ký tự",
                },
            },
        };
        if (passwordConfirmationInput) {
            fieldsConfig.password_confirmation = {
                validators: {
                    notEmpty: {
                        message: "Vui lòng xác nhận mật khẩu",
                    },
                    stringLength: {
                        max: 255,
                        message:
                            "Mật khẩu xác nhận không được vượt quá 255 ký tự",
                    },
                    identical: {
                        compare: function () {
                            return passwordInput.value;
                        },
                        message: "Mật khẩu xác nhận không khớp",
                    },
                },
            };
        }
    } else if (passwordInput && isEdit) {
        // EDIT (nếu sau này có field password): cho phép bỏ trống, nhưng nếu nhập thì phải hợp lệ
        fieldsConfig.password = {
            validators: {
                stringLength: {
                    min: 6,
                    max: 255,
                    message: "Mật khẩu phải từ 6 đến 255 ký tự",
                },
                callback: {
                    message: "Mật khẩu phải có ít nhất 6 ký tự",
                    callback: function (input) {
                        const value = input.value;
                        return (
                            !value || (value.length >= 6 && value.length <= 255)
                        );
                    },
                },
            },
        };
        if (passwordConfirmationInput) {
            fieldsConfig.password_confirmation = {
                validators: {
                    stringLength: {
                        max: 255,
                        message:
                            "Mật khẩu xác nhận không được vượt quá 255 ký tự",
                    },
                    identical: {
                        compare: function () {
                            return passwordInput.value;
                        },
                        message: "Mật khẩu xác nhận không khớp",
                    },
                },
            };
        }
    }

    const fv = FormValidation.formValidation(form, {
        fields: fieldsConfig,
        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            bootstrap5: new FormValidation.plugins.Bootstrap5({
                rowSelector: ".mb-3, .col-md-6, .col-12",
                eleValidClass: "",
                eleInvalidClass: "",
            }),
            autoFocus: new FormValidation.plugins.AutoFocus(),
            submitButton: new FormValidation.plugins.SubmitButton(),
        },
        init: (instance) => {
            instance.on("plugins.message.placed", function (e) {
                // Nếu input nằm trong input-group thì render message ra ngoài
                if (
                    e.element.parentElement &&
                    e.element.parentElement.classList.contains("input-group")
                ) {
                    e.element.parentElement.insertAdjacentElement(
                        "afterend",
                        e.messageElement
                    );
                }
            });
        },
    });

    // Xử lý loading khi form validation thành công
    fv.on("core.form.valid", function () {
        const btn = $("#submit_btn");
        btn.prop("disabled", true);
        btn.find(".spinner-border").removeClass("d-none");
        form.submit();
    });

    window.fvUserForm = fv;

    // Only allow numbers in phone input
    const phoneInput = document.getElementById("phone");
    if (phoneInput) {
        phoneInput.addEventListener("input", function (e) {
            // Remove any non-numeric characters
            this.value = this.value.replace(/[^0-9]/g, "");
        });

        // Prevent typing non-numeric characters
        phoneInput.addEventListener("keypress", function (e) {
            // Allow: backspace, delete, tab, escape, enter
            if (
                [46, 8, 9, 27, 13].indexOf(e.keyCode) !== -1 ||
                // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                (e.keyCode === 65 && e.ctrlKey === true) ||
                (e.keyCode === 67 && e.ctrlKey === true) ||
                (e.keyCode === 86 && e.ctrlKey === true) ||
                (e.keyCode === 88 && e.ctrlKey === true)
            ) {
                return;
            }
            // Ensure that it is a number and stop the keypress
            if (
                (e.shiftKey || e.keyCode < 48 || e.keyCode > 57) &&
                (e.keyCode < 96 || e.keyCode > 105)
            ) {
                e.preventDefault();
            }
        });
    }
});
