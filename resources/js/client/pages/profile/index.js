/**
 * Client Profile Page - Profile Update Form Validation
 * Based on Admin Profile with client-specific features
 */
"use strict";

$(function () {
    // ================================
    // Constants & Selectors
    // ================================
    const $profileForm = $("#profileForm");
    const $birthday = $("#birthday");
    const $phoneInput = $("#phone");
    const $avatarInput = $("#avatar");
    const $avatarPreview = $("#avatar_preview");
    const modalEl = document.getElementById("editProfileModal");
    
    // Cache selectors for profile update
    const profileSelectors = {
        submit: $("#profileSubmitBtn"),
        spinner: $("#profileSubmitBtn .spinner-border"),
        displayName: $("#profileDisplayName"),
        email: $("#profileEmail"),
        phone: $("#profilePhone"),
        avatar: $("#profileAvatarImg"),
    };

    // ================================
    // Helper Functions
    // ================================
    /**
     * Chuyển đổi định dạng ngày từ Y-m-d sang d/m/Y
     */
    const formatDateToDisplay = (dateStr) => {
        if (!dateStr || !dateStr.includes("-")) return dateStr;
        const [y, m, d] = dateStr.split("-");
        return `${d}/${m}/${y}`;
    };

    /**
     * Chuyển đổi định dạng ngày từ d/m/Y sang Y-m-d
     */
    const formatDateToSubmit = (dateStr) => {
        if (!dateStr || !dateStr.includes("/")) return dateStr;
        const parts = dateStr.split("/");
        if (parts.length !== 3) return dateStr;
        const [d, m, y] = parts;
        return `${y}-${m.padStart(2, "0")}-${d.padStart(2, "0")}`;
    };

    /**
     * Lọc chỉ giữ lại số từ chuỗi
     */
    const filterNumericOnly = (value) => value.replace(/[^0-9]/g, "");

    /**
     * Revalidate phone field nếu FormValidation đã khởi tạo
     */
    const revalidatePhone = () => {
        if (window.fvProfile) {
            window.fvProfile.revalidateField("phone");
        }
    };

    /**
     * Reset validation state của form
     */
    const resetFormValidation = () => {
        $profileForm.find(".is-invalid").removeClass("is-invalid");
        $profileForm.find(".is-valid").removeClass("is-valid");
        $profileForm.find(".invalid-feedback").remove();
    };

    // ================================
    // Auto open modal when form has errors
    // ================================
    if (window.hasProfileErrors && modalEl) {
        new bootstrap.Modal(modalEl).show();
    }

    // ================================
    // Phone number input - chỉ cho phép nhập số
    // ================================
    if ($phoneInput.length) {
        const $input = $phoneInput;
        
        // Xử lý input và paste với logic chung
        const handleNumericInput = (value) => {
            const numericOnly = filterNumericOnly(value);
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
    if ($profileForm.length && typeof FormValidation !== "undefined") {
        // Validation rules - Client version (phone, birthday, gender are optional)
        const validationRules = {
            full_name: {
                validators: {
                    notEmpty: { message: "Vui lòng nhập họ tên." },
                    stringLength: {
                        min: 2,
                        max: 150,
                        message: "Họ tên phải từ 2 đến 150 ký tự.",
                    },
                },
            },
            email: {
                validators: {
                    callback: {
                        message: "Vui lòng nhập email.",
                        callback: function (value, validator, $field) {
                            // Nếu email input bị disabled (email đã verified), skip validation
                            const $emailInput = $("#email");
                            if ($emailInput.length && $emailInput.is(":disabled")) {
                                return true;
                            }
                            if (!value) return false;
                            // Validate email format
                            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                            if (!emailRegex.test(value)) {
                                return {
                                    valid: false,
                                    message: "Email không hợp lệ.",
                                };
                            }
                            if (value.length > 254) {
                                return {
                                    valid: false,
                                    message: "Email không được vượt quá 254 ký tự.",
                                };
                            }
                            return true;
                        },
                    },
                },
            },
            phone: {
                validators: {
                    stringLength: {
                        max: 20,
                        message: "Số điện thoại không được vượt quá 20 ký tự.",
                    },
                    regexp: {
                        regexp: /^[0-9]*$/,
                        message: "Số điện thoại chỉ được chứa số.",
                    },
                },
            },
            gender: {
                validators: {
                    callback: {
                        message: "Giới tính không hợp lệ.",
                        callback: (item) => {
                            if (item == null || item === "") return true; // Optional for client
                            return ["0", "1", "2"].includes(String(item.value));
                        },
                    },
                },
            },
            birthday: {
                validators: {
                    date: {
                        format: "DD/MM/YYYY",
                        message: "Ngày sinh không hợp lệ. Vui lòng nhập định dạng dd/mm/yyyy.",
                    },
                    callback: {
                        message: "Ngày sinh không hợp lệ.",
                        callback: (item) => {
                            if (item == null || item === "" || !item.value) return true; // Optional
                            const dateRegex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
                            if (!dateRegex.test(item.value)) return false;
                            
                            const [, day, month, year] = item.value.match(dateRegex);
                            const date = new Date(year, month - 1, day);
                            return (
                                date.getFullYear() == year &&
                                date.getMonth() == month - 1 &&
                                date.getDate() == day
                            );
                        },
                    },
                },
            },
            description: {
                validators: {
                    stringLength: {
                        max: 255,
                        message: "Mô tả không được vượt quá 255 ký tự.",
                    },
                },
            },
            twitter_url: {
                validators: {
                    uri: {
                        allowLocal: true,
                        message: "URL Twitter không hợp lệ.",
                    },
                    stringLength: {
                        max: 255,
                        message: "URL Twitter không được vượt quá 255 ký tự.",
                    },
                    callback: {
                        message: "URL Twitter phải bắt đầu với https://twitter.com/ hoặc https://x.com/",
                        callback: (value) => {
                            if (!value) return true; // nullable
                            return /^https:\/\/(twitter\.com|x\.com)\/.+$/.test(value);
                        },
                    },
                },
            },
            facebook_url: {
                validators: {
                    uri: {
                        allowLocal: true,
                        message: "URL Facebook không hợp lệ.",
                    },
                    stringLength: {
                        max: 255,
                        message: "URL Facebook không được vượt quá 255 ký tự.",
                    },
                    callback: {
                        message: "URL Facebook phải bắt đầu với https://facebook.com/ hoặc https://fb.com/",
                        callback: (value) => {
                            if (!value) return true; // nullable
                            return /^https:\/\/(www\.)?facebook\.com\/.+$|^https:\/\/fb\.com\/.+$/.test(value);
                        },
                    },
                },
            },
            instagram_url: {
                validators: {
                    uri: {
                        allowLocal: true,
                        message: "URL Instagram không hợp lệ.",
                    },
                    stringLength: {
                        max: 255,
                        message: "URL Instagram không được vượt quá 255 ký tự.",
                    },
                    callback: {
                        message: "URL Instagram phải bắt đầu với https://instagram.com/",
                        callback: (value) => {
                            if (!value) return true; // nullable
                            return /^https:\/\/(www\.)?instagram\.com\/.+$/.test(value);
                        },
                    },
                },
            },
            linkedin_url: {
                validators: {
                    uri: {
                        allowLocal: true,
                        message: "URL LinkedIn không hợp lệ.",
                    },
                    stringLength: {
                        max: 255,
                        message: "URL LinkedIn không được vượt quá 255 ký tự.",
                    },
                    callback: {
                        message: "URL LinkedIn phải bắt đầu với https://linkedin.com/in/ hoặc https://linkedin.com/company/",
                        callback: (value) => {
                            if (!value) return true; // nullable
                            return /^https:\/\/(www\.)?linkedin\.com\/(in|company)\/.+$/.test(value);
                        },
                    },
                },
            },
        };

        const fvProfile = FormValidation.formValidation($profileForm[0], {
            fields: validationRules,
            plugins: {
                trigger: new FormValidation.plugins.Trigger({
                    // Trigger validation khi blur và khi submit
                    event: {
                        valid: "blur",
                        invalid: "blur",
                    },
                }),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    rowSelector: ".col-md-6, .col-12, .col-lg-4, .col-lg-8",
                    eleInvalidClass: "is-invalid",
                    eleValidClass: "is-valid",
                }),
                autoFocus: new FormValidation.plugins.AutoFocus(),
                submitButton: new FormValidation.plugins.SubmitButton(),
            },
            init: (instance) => {
                // Xử lý message placement
                instance.on("plugins.message.placed", (e) => {
                    const { element, messageElement } = e;
                    
                    // Đảm bảo message element có class đúng
                    if (messageElement) {
                        messageElement.classList.add("invalid-feedback", "d-block");
                    }
                    
                    // Nếu input nằm trong input-group thì render message ra ngoài
                    if (element.parentElement?.classList.contains("input-group")) {
                        element.parentElement.insertAdjacentElement(
                            "afterend",
                            messageElement
                        );
                    } else {
                        // Đảm bảo message được đặt sau input/select
                        const $element = $(element);
                        const $existingFeedback = $element.siblings(".invalid-feedback");
                        if ($existingFeedback.length) {
                            $existingFeedback.replaceWith(messageElement);
                        } else {
                            $element.after(messageElement);
                        }
                    }
                });

                // Xử lý khi field invalid - đảm bảo message hiển thị
                instance.on("core.field.invalid", (e) => {
                    const { element, validators } = e;
                    if (element) {
                        element.classList.add("is-invalid");
                        element.classList.remove("is-valid");
                        
                        // Đảm bảo message được hiển thị
                        setTimeout(() => {
                            const $element = $(element);
                            const $message = $element.siblings(".invalid-feedback");
                            if ($message.length) {
                                $message.addClass("d-block").show();
                            }
                        }, 0);
                    }
                });

                instance.on("core.field.valid", (e) => {
                    const { element } = e;
                    if (element) {
                        element.classList.remove("is-invalid");
                        element.classList.add("is-valid");
                    }
                });

                // Reset validation khi modal mở
                if (modalEl) {
                    modalEl.addEventListener("show.bs.modal", () => {
                        instance.resetForm();
                        resetFormValidation();
                    });
                }
            },
        });

        // ================================
        // Form Submit Handler
        // ================================
        $profileForm.on("submit", function (e) {
            e.preventDefault();
            // Validate tất cả fields và hiển thị lỗi
            fvProfile.validate().then((status) => {
                // Đảm bảo tất cả invalid fields đều hiển thị message
                if (status !== "Valid") {
                    // Force hiển thị messages cho các fields invalid
                    setTimeout(() => {
                        $profileForm.find(".is-invalid").each(function () {
                            const $input = $(this);
                            let $message = $input.next(".invalid-feedback");
                            
                            // Nếu không có message, tìm trong parent
                            if (!$message.length) {
                                $message = $input.parent().find(".invalid-feedback");
                            }
                            
                            // Đảm bảo message hiển thị
                            if ($message.length) {
                                $message.addClass("d-block").css("display", "block");
                            }
                        });
                    }, 100);
                }
            });
        });

        // Handle valid form submission
        fvProfile.on("core.form.valid", () => {
            resetFormValidation();

            // Format birthday to Y-m-d before submit
            const birthdayVal = $birthday.val();
            if (birthdayVal) {
                const formatted = formatDateToSubmit(birthdayVal);
                if (formatted !== birthdayVal) {
                    $birthday.val(formatted);
                }
            }

            // Disable submit button and show spinner
            profileSelectors.submit.prop("disabled", true);
            profileSelectors.spinner.removeClass("d-none");

            $.ajax({
                url: $profileForm.attr("action"),
                method: "POST",
                data: $profileForm.serialize(),
                success: (res) => {
                    if (res?.status) {
                        // Update profile display
                        const user = res.user || {};
                        if (user.full_name || user.email) {
                            profileSelectors.displayName.text(user.full_name || user.email);
                        }
                        if (user.email) profileSelectors.email.text(user.email);
                        if (user.phone !== undefined) {
                            profileSelectors.phone.text(user.phone || "");
                        }
                        if (user.avatar) {
                            profileSelectors.avatar.attr("src", user.avatar);
                        }

                        // Close modal and show success
                        $(modalEl).modal("hide");
                        toastr.success(
                            res.message || "Cập nhật thông tin thành công",
                            "Thông báo"
                        );

                        // Reload page after 1 second to update all displayed info (client-specific)
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        toastr.error(
                            res?.message || "Không thể cập nhật thông tin",
                            "Thông báo"
                        );
                    }
                },
                error: (xhr) => {
                    // Display server validation errors
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        Object.entries(xhr.responseJSON.errors).forEach(([field, messages]) => {
                            const $input = $profileForm.find(`[name="${field}"]`);
                            if ($input.length && messages[0]) {
                                $input.addClass("is-invalid");
                                $input.after(
                                    $(`<div class="invalid-feedback d-block"></div>`).text(messages[0])
                                );
                            }
                        });
                    }
                    toastr.error("Có lỗi xảy ra khi cập nhật thông tin", "Thông báo");
                },
                complete: () => {
                    profileSelectors.submit.prop("disabled", false);
                    profileSelectors.spinner.addClass("d-none");

                    // Restore birthday display format
                    const val = $birthday.val();
                    if (val) {
                        const restored = formatDateToDisplay(val);
                        if (restored !== val) {
                            $birthday.val(restored);
                        }
                    }
                },
            });
        });

        // Store instance globally for reuse
        window.fvProfile = fvProfile;
    }

    // ================================
    // Avatar Preview Handler
    // ================================
    if ($avatarInput.length && $avatarPreview.length) {
        const renderAvatarPreview = (url) => {
            $avatarPreview.empty();
            if (url) {
                $avatarPreview.append(
                    $("<img>", {
                        src: url,
                        alt: "Avatar preview",
                        class: "upload_btn w-100 h-100",
                    })
                        .css({ objectFit: "cover", borderRadius: "0.5rem" })
                        .data("targetInput", "#avatar")
                        .data("targetPreview", "#avatar_preview")
                        .filemanager("image", { prefix: "/filemanager" })
                );
            } else {
                $avatarPreview.append('<i class="bx bx-image-add fs-1 text-muted"></i>');
            }
        };

        // Initial render
        const initialAvatar = $avatarInput.val();
        if (initialAvatar) {
            renderAvatarPreview(initialAvatar);
        }

        // Update on change
        $avatarInput.on("input change", function () {
            renderAvatarPreview($(this).val());
        });
    }

    // ================================
    // Flatpickr for Birthday
    // ================================
    if ($birthday.length && typeof flatpickr !== "undefined") {
        // Convert existing Y-m-d format to d/m/Y for display
        const currentVal = $birthday.val();
        if (currentVal) {
            const formatted = formatDateToDisplay(currentVal);
            if (formatted !== currentVal) {
                $birthday.val(formatted);
            }
        }

        flatpickr($birthday[0], {
            dateFormat: "d/m/Y",
            allowInput: true,
            defaultDate: $birthday.val() || null,
            locale: { firstDayOfWeek: 1 },
        });
    }
});
