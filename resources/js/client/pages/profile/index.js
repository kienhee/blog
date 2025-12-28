/**
 * Client Profile Page - Profile Update Form Validation
 * Based on Admin Profile
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
    if ($profileForm.length && typeof FormValidation !== "undefined") {
        // Validation rules
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
                        callback: () => true, // Email disabled, always valid
                    },
                },
            },
            phone: {
                validators: {
                    notEmpty: { message: "Vui lòng nhập số điện thoại." },
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
            gender: {
                validators: {
                    notEmpty: { message: "Vui lòng chọn giới tính." },
                    callback: {
                        message: "Giới tính không hợp lệ.",
                        callback: (item) => {
                            if (item == null || item === "") return false;
                            return ["0", "1", "2"].includes(String(item.value));
                        },
                    },
                },
            },
            birthday: {
                validators: {
                    notEmpty: { message: "Vui lòng nhập ngày sinh." },
                    date: {
                        format: "DD/MM/YYYY",
                        message: "Vui lòng nhập định dạng dd/mm/yyyy.",
                    },
                    callback: {
                        message: "Ngày sinh không hợp lệ.",
                        callback: (item) => {
                            if (item == null || item === "") return false;
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
        };

        const fvProfile = FormValidation.formValidation($profileForm[0], {
            fields: validationRules,
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    rowSelector: ".col-md-6, .col-12, .col-lg-4, .col-lg-8",
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
        fvProfile.on("core.form.valid", () => {
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
                        // Update profile display if selectors exist
                        const user = res.user || {};
                        if (profileSelectors.displayName.length && (user.full_name || user.email)) {
                            profileSelectors.displayName.text(user.full_name || user.email);
                        }
                        if (profileSelectors.email.length && user.email) {
                            profileSelectors.email.text(user.email);
                        }
                        if (profileSelectors.phone.length && user.phone !== undefined) {
                            profileSelectors.phone.text(user.phone || "");
                        }
                        if (profileSelectors.avatar.length && user.avatar) {
                            profileSelectors.avatar.attr("src", user.avatar);
                        }

                        // Close modal and show success
                        $(modalEl).modal("hide");
                        toastr.success(
                            res.message || "Cập nhật thông tin thành công",
                            "Thông báo"
                        );

                        // Reload page after 1 second to update all displayed info
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

        // ================================
        // Reset validation when modal hidden
        // ================================
        if (modalEl) {
            modalEl.addEventListener("hidden.bs.modal", () => {
                fvProfile.resetForm();
            });
        }

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
