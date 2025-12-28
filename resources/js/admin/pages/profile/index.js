/**
 * Admin Profile Page - Profile Update Form Validation
 * Note: Change Password form is handled by resources/js/common/change-password.js in separate page
 */
"use strict";

$(function () {
    const hasProfileErrors = window.hasProfileErrors || false;

    /**
     * Auto open edit profile modal when form has errors
     */
    if (hasProfileErrors) {
        const modalEl = document.getElementById("editProfileModal");
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    }

    // ================================
    // AJAX submit for profile update with FormValidation
    // ================================
    const $profileForm = $("#profileForm");
    const $profileSubmit = $("#profileSubmitBtn");
    const $profileSpinner = $profileSubmit.find(".spinner-border");
    const $birthday = $("#birthday");
    const $emailInput = $("#email");

    if ($profileForm.length && typeof FormValidation !== "undefined") {
        // Form Validation for Profile Update - Chỉ validate các field bắt buộc
        const fvProfile = FormValidation.formValidation($profileForm[0], {
            fields: {
                full_name: {
                    validators: {
                        notEmpty: {
                            message: "Vui lòng nhập họ tên.",
                        },
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
                                // Email luôn bị disabled, skip validation
                                if ($emailInput.length && $emailInput.is(":disabled")) {
                                    return true;
                                }
                                return true;
                            },
                        },
                    },
                },
                phone: {
                    validators: {
                        notEmpty: {
                            message: "Vui lòng nhập số điện thoại.",
                        },
                        stringLength: {
                            max: 20,
                            message: "Số điện thoại không được vượt quá 20 ký tự.",
                        },
                        regexp: {
                            regexp: /^[0-9]+$/,
                            message: "Số điện thoại chỉ được chứa số.",
                        },
                    },
                },
                gender: {
                    validators: {
                        notEmpty: {
                            message: "Vui lòng chọn giới tính.",
                        },
                        callback: {
                            message: "Giới tính không hợp lệ.",
                            callback: function (item) {
                                // Kiểm tra empty: null, undefined, hoặc chuỗi rỗng
                                if (item === null || item === undefined || item === "") {
                                    return false; // bắt buộc
                                }
                                // Convert to string để so sánh (xử lý cả số 0)
                                const strValue = String(item.value);
                                return ["0", "1", "2"].includes(strValue);
                            },
                        },
                    },
                },
                birthday: {
                    validators: {
                        notEmpty: {
                            message: "Vui lòng nhập ngày sinh.",
                        },
                        date: {
                            format: "DD/MM/YYYY",
                            message: "Ngày sinh không hợp lệ. Vui lòng nhập định dạng dd/mm/yyyy.",
                        },
                        callback: {
                            message: "Ngày sinh không hợp lệ.",
                            callback: function (item) {
                                // Kiểm tra empty: null, undefined, hoặc chuỗi rỗng
                                if (item === null || item === undefined || item === "") {
                                    return false; // bắt buộc
                                }
                                // Validate format dd/mm/yyyy
                                const dateRegex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
                                if (!dateRegex.test(item.value)) {
                                    return false;
                                }
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
            },
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    rowSelector:
                        ".row > .col-md-6, .row > .col-12, .row > .col-lg-4, .row > .col-lg-8, .col-md-6",
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

                // Reset validation state khi modal mở để tránh hiển thị lỗi từ lần trước
                const modal = document.getElementById("editProfileModal");
                if (modal) {
                    modal.addEventListener("show.bs.modal", () => {
                        // Reset tất cả validation state
                        instance.resetForm();
                        $profileForm.find(".is-invalid").removeClass("is-invalid");
                        $profileForm.find(".is-valid").removeClass("is-valid");
                        $profileForm.find(".invalid-feedback").remove();
                    });
                }
            },
        });

        // Ngăn chặn form submit trực tiếp, chỉ cho phép submit khi validation pass
        $profileForm.on("submit", function (e) {
            e.preventDefault();
            // Validation sẽ được trigger bởi SubmitButton plugin
            // Nếu validation pass, event "core.form.valid" sẽ được trigger
        });

        // On valid submit - chỉ được trigger khi validation pass
        fvProfile.on("core.form.valid", () => {
            // clear old errors
            $profileForm.find(".is-invalid").removeClass("is-invalid");
            $profileForm.find(".invalid-feedback").remove();

            // Chuẩn hóa ngày sinh về Y-m-d trước khi submit
            const birthdayVal = $birthday.val();
            if (birthdayVal && birthdayVal.includes("/")) {
                const parts = birthdayVal.split("/");
                if (parts.length === 3) {
                    const [d, m, y] = parts;
                    $birthday.val(
                        `${y}-${m.padStart(2, "0")}-${d.padStart(2, "0")}`
                    );
                }
            }

            $profileSubmit.prop("disabled", true);
            $profileSpinner.removeClass("d-none");

            $.ajax({
                url: $profileForm.attr("action"),
                method: "POST",
                data: $profileForm.serialize(),
                success: function (res) {
                    if (res?.status) {
                        // Update header info
                        const user = res.user || {};
                        const displayName = user.full_name || user.email || "";
                        if (displayName) $("#profileDisplayName").text(displayName);
                        if (user.email) $("#profileEmail").text(user.email);
                        if (user.phone !== undefined)
                            $("#profilePhone").text(user.phone || "");
                        if (user.avatar) {
                            $("#profileAvatarImg").attr("src", user.avatar);
                        }

                        // Close modal
                        $("#editProfileModal").modal("hide");
                        toastr.success(
                            res.message || "Cập nhật thông tin thành công",
                            "Thông báo"
                        );

                    } else {
                        toastr.error(
                            res?.message || "Không thể cập nhật thông tin",
                            "Thông báo"
                        );
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        const errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach((field) => {
                            const messages = errors[field];
                            const $input = $profileForm.find(`[name="${field}"]`);
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
                        "Có lỗi xảy ra khi cập nhật thông tin",
                        "Thông báo"
                    );
                },
                complete: function () {
                    $profileSubmit.prop("disabled", false);
                    $profileSpinner.addClass("d-none");

                    // Khôi phục hiển thị d/m/Y sau submit (nếu cần)
                    const val = $birthday.val();
                    if (val && val.includes("-")) {
                        const [y, m, d] = val.split("-");
                        $birthday.val(`${d}/${m}/${y}`);
                    }
                },
            });
        });

        // Store instance
        window.fvProfile = fvProfile;
    }

    // ================================
    // Avatar preview on input change
    // ================================
    const $avatarInput = $("#avatar");
    const $avatarPreview = $("#avatar_preview");

    function renderAvatarPreview(url) {
        if (!$avatarPreview.length) return;
        $avatarPreview.empty();
        if (url) {
            const $img = $("<img>", {
                src: url,
                alt: "Avatar preview",
                class: "upload_btn w-100 h-100",
            })
                .css({ objectFit: "cover", borderRadius: "0.5rem" })
                .data("targetInput", "#avatar")
                .data("targetPreview", "#avatar_preview")
                .filemanager("image", { prefix: "/filemanager" });
            $avatarPreview.append($img);
        } else {
            $avatarPreview.append(
                '<i class="bx bx-image-add fs-1 text-muted"></i>'
            );
        }
    }

    if ($avatarInput.length) {
        // initial render if value exists
        if ($avatarInput.val()) {
            renderAvatarPreview($avatarInput.val());
        }

        $avatarInput.on("input change", function () {
            renderAvatarPreview($(this).val());
        });
    }

    // ================================
    // Flatpickr for birthday
    // ================================
    if ($birthday.length && typeof flatpickr !== "undefined") {
        // Chuyển giá trị hiện có (nếu dạng Y-m-d) sang d/m/Y để hiển thị
        const currentVal = $birthday.val();
        if (currentVal && currentVal.includes("-")) {
            const [y, m, d] = currentVal.split("-");
            $birthday.val(`${d}/${m}/${y}`);
        }

        flatpickr($birthday[0], {
            dateFormat: "d/m/Y",
            allowInput: true,
            defaultDate: $birthday.val() || null,
            locale: { firstDayOfWeek: 1 },
        });
    }
});

