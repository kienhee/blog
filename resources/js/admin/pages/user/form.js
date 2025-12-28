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
        "roles[]": {
            validators: {
                notEmpty: {
                    message: "Vui lòng chọn ít nhất một vai trò",
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
                eleInvalidClass: "is-invalid",
                eleValidClass: "is-valid",
            }),
            autoFocus: new FormValidation.plugins.AutoFocus(),
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
                // Xử lý select2: đặt message sau container của select2
                if (
                    e.element.classList.contains("select2") ||
                    $(e.element).hasClass("select2-hidden-accessible")
                ) {
                    const $select = $(e.element);
                    const $container = $select
                        .parent()
                        .find(".select2-container");
                    if ($container.length) {
                        $container.after(e.messageElement);
                        // Thêm class invalid vào container
                        $container.addClass("is-invalid");
                    }
                }
            });
            instance.on("core.field.validated", function (e) {
                // Khi field hợp lệ, xóa class invalid khỏi select2 container
                const $select = $(e.element);
                if ($select.hasClass("select2-hidden-accessible")) {
                    const $container = $select
                        .parent()
                        .find(".select2-container");
                    if ($container.length && e.valid) {
                        $container.removeClass("is-invalid");
                    }
                }
            });
        },
    });

    // ======================================
    // 🔍 VALIDATE ROLES (SELECT2 MULTIPLE)
    // ======================================
    const $rolesSelect = $("#roles");
    if ($rolesSelect.length) {
        // Xóa lỗi khi user chọn role
        $rolesSelect.on("change", function () {
            const rolesValue = $(this).val();
            if (
                rolesValue &&
                Array.isArray(rolesValue) &&
                rolesValue.length > 0
            ) {
                // Xóa class invalid và message lỗi
                const rolesContainer = $(this)
                    .parent()
                    .find(".select2-container");
                rolesContainer.removeClass("is-invalid");
                $(this).removeClass("is-invalid");
                rolesContainer.siblings(".invalid-feedback").remove();
            }
            // Revalidate field
            fv.revalidateField("roles[]");
        });
    }

    // ======================================
    // 📤 FORM SUBMIT HANDLER
    // ======================================
    const $form = $(form);
    const $submitBtn = $("#submit_btn");

    // Handle submit button click - PHẢI validate tất cả (bao gồm roles) trước khi submit
    $submitBtn.on("click", function (e) {
        e.preventDefault();

        // Validate roles trước bằng cách kiểm tra trực tiếp
        const rolesValue = $rolesSelect.val();
        if (
            !rolesValue ||
            !Array.isArray(rolesValue) ||
            rolesValue.length === 0
        ) {
            // Hiển thị lỗi cho roles
            const rolesContainer = $rolesSelect
                .parent()
                .find(".select2-container");
            rolesContainer.addClass("is-invalid");
            $rolesSelect.addClass("is-invalid");

            // Hiển thị message lỗi
            let errorMsg = rolesContainer.siblings(".invalid-feedback");
            if (!errorMsg.length) {
                errorMsg = $(
                    '<div class="invalid-feedback d-block">Vui lòng chọn ít nhất một vai trò</div>'
                );
                const $small = $rolesSelect.siblings("small.text-muted");
                if ($small.length) {
                    $small.after(errorMsg);
                } else {
                    rolesContainer.after(errorMsg);
                }
            }

            // Scroll to roles field
            $("html, body").animate(
                {
                    scrollTop: rolesContainer.offset().top - 100,
                },
                300
            );

            // Mở select2 dropdown
            $rolesSelect.select2("open");

            return false;
        }

        // Validate tất cả fields trong FormValidation (bao gồm roles)
        fv.validate().then(function (status) {
            if (status !== "Valid") {
                // Validation failed - không cho phép submit
                console.log("Validation failed, không thể submit form");

                // Scroll to first error field
                const firstError = $form.find(".is-invalid").first();
                if (firstError.length) {
                    const errorOffset = firstError.offset();
                    if (errorOffset) {
                        $("html, body").animate(
                            {
                                scrollTop: errorOffset.top - 100,
                            },
                            300
                        );
                        firstError.focus();
                    }
                }

                // Nếu có lỗi ở roles, focus vào select2
                if ($rolesSelect.length) {
                    const rolesContainer = $rolesSelect
                        .parent()
                        .find(".select2-container");
                    if (rolesContainer.hasClass("is-invalid")) {
                        $rolesSelect.select2("open");
                    }
                }

                return false;
            }

            $submitBtn.prop("disabled", true);
            $submitBtn.find(".spinner-border").removeClass("d-none");

            // Submit form lên backend
            $form[0].submit();
        });
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
