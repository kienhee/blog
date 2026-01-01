/**
 * Client Contact Page - Form Validation
 */
"use strict";

$(function () {
    // ================================
    // Constants & Selectors
    // ================================
    const $form = $("#contactForm");
    const $submitBtn = $("#contactSubmitBtn");
    const $submitBtnText = $submitBtn.find(".btn-text");
    const $spinner = $submitBtn.find(".spinner-border");
    const $phoneInput = $("#contact-form-phone");

    // ================================
    // Helper Functions
    // ================================
    /**
     * Lọc chỉ giữ lại số từ chuỗi
     */
    const filterNumericOnly = (value) => value.replace(/[^0-9]/g, "");

    /**
     * Revalidate phone field nếu FormValidation đã khởi tạo
     */
    const revalidatePhone = () => {
        if (window.fvContact) {
            window.fvContact.revalidateField("phone");
        }
    };

    // ================================
    // Phone number input - chỉ cho phép nhập số
    // ================================
    if ($phoneInput.length) {
        const $input = $phoneInput;

        // Xử lý input và paste với logic chung
        const handleNumericInput = (value) => {
            const numericOnly = filterNumericOnly(value).substring(0, 10);
            $input.val(numericOnly);
            revalidatePhone();
        };

        $input.on("input", function () {
            handleNumericInput($(this).val());
        });

        $input.on("paste", function (e) {
            e.preventDefault();
            const pastedText = (e.originalEvent || e).clipboardData.getData("text");
            handleNumericInput(pastedText);
        });

        $input.on("keypress", function (e) {
            if (!/[0-9]/.test(String.fromCharCode(e.which))) {
                e.preventDefault();
            }
        });

        $input.on("blur", revalidatePhone);
    }

    // ================================
    // Form Validation Setup
    // ================================
    if ($form.length && typeof FormValidation !== "undefined") {
        // Validation rules
        const validationRules = {
            fullname: {
                validators: {
                    notEmpty: {
                        message: "Vui lòng nhập họ và tên.",
                    },
                    stringLength: {
                        min: 2,
                        max: 255,
                        message: "Họ và tên phải từ 2 đến 255 ký tự.",
                    },
                },
            },
            email: {
                validators: {
                    notEmpty: {
                        message: "Vui lòng nhập email.",
                    },
                    emailAddress: {
                        message: "Email không hợp lệ. Vui lòng nhập đúng định dạng email.",
                    },
                    stringLength: {
                        max: 255,
                        message: "Email không được vượt quá 255 ký tự.",
                    },
                },
            },
            phone: {
                validators: {
                    notEmpty: {
                        message: "Vui lòng nhập số điện thoại.",
                    },
                    stringLength: {
                        min: 10,
                        max: 10,
                        message: "Số điện thoại phải có đúng 10 số.",
                    },
                    regexp: {
                        regexp: /^(032|033|034|035|036|037|038|039|086|096|097|098|081|082|083|084|085|088|091|094|056|058|092|070|076|077|078|079|089|090|093|099|059)[0-9]{7}$/,
                        message: "Số điện thoại không hợp lệ.",
                    },
                },
            },
            subject: {
                validators: {
                    notEmpty: {
                        message: "Vui lòng nhập tiêu đề.",
                    },
                    stringLength: {
                        min: 3,
                        max: 255,
                        message: "Tiêu đề phải từ 3 đến 255 ký tự.",
                    },
                },
            },
            message: {
                validators: {
                    notEmpty: {
                        message: "Vui lòng nhập tin nhắn.",
                    },
                    stringLength: {
                        min: 10,
                        max: 2000,
                        message: "Tin nhắn phải từ 10 đến 2000 ký tự.",
                    },
                },
            },
        };

        const fvContact = FormValidation.formValidation($form[0], {
            fields: validationRules,
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    rowSelector: ".col-md-6, .col-md-12, .col-12",
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
        fvContact.on("core.form.valid", () => {
            // Disable submit button and show spinner
            $submitBtn.prop("disabled", true);
            $submitBtnText.text("Đang gửi...");
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
                    if (res?.status || res?.success) {
                        // Hiển thị thông báo thành công
                        if (typeof toastr !== "undefined") {
                            toastr.success(
                                res.message || "Gửi tin nhắn thành công! Chúng tôi sẽ phản hồi sớm nhất có thể.",
                                "Thành công"
                            );
                        }

                        // Reset form
                        $form[0].reset();
                        fvContact.resetForm();
                    } else {
                        // Hiển thị thông báo lỗi
                        if (typeof toastr !== "undefined") {
                            toastr.error(
                                res?.message || "Không thể gửi tin nhắn. Vui lòng thử lại.",
                                "Lỗi"
                            );
                        }
                    }
                },
                error: (xhr) => {
                    let errorMessage = "Có lỗi xảy ra khi gửi tin nhắn. Vui lòng thử lại.";

                    // Xử lý lỗi validation từ server
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        const errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach((field) => {
                            const $field = $form.find(`[name="${field}"]`);
                            if ($field.length && errors[field][0]) {
                                $field.addClass("is-invalid");
                                $field.removeClass("is-valid");
                                // Remove existing feedback
                                $field.next(".invalid-feedback").remove();
                                $field.after(
                                    $(`<div class="invalid-feedback d-block"></div>`).text(errors[field][0])
                                );
                                // Revalidate field to update FormValidation state
                                fvContact.revalidateField(field);
                            }
                        });
                        errorMessage = xhr.responseJSON?.message || "Vui lòng kiểm tra lại thông tin đã nhập.";
                    } else if (xhr.responseJSON?.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    // Hiển thị thông báo lỗi
                    if (typeof toastr !== "undefined") {
                        toastr.error(errorMessage, "Lỗi");
                    }
                },
                complete: () => {
                    // Re-enable submit button
                    $submitBtn.prop("disabled", false);
                    $submitBtnText.text("Gửi tin nhắn");
                    $spinner.addClass("d-none");
                },
            });
        });

        // ================================
        // Clear server errors on input
        // ================================
        $form.find("input, textarea").on("input", function () {
            const $input = $(this);
            // Xóa server validation errors khi user bắt đầu nhập
            if ($input.hasClass("is-invalid")) {
                const $feedback = $input.next(".invalid-feedback");
                if ($feedback.length && $feedback.hasClass("d-block")) {
                    // Chỉ xóa nếu là server error (có class d-block)
                    $feedback.remove();
                    $input.removeClass("is-invalid");
                    // Revalidate field để cập nhật state
                    fvContact.revalidateField($input[0]);
                }
            }
        });

        // Store instance globally for reuse
        window.fvContact = fvContact;
    }
});
