/**
 * Client Profile Page - Form Validation
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

    if ($profileForm.length) {
        // Form Validation for Profile Update
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
                            callback: function (value) {
                                if (!value) return true; // nullable
                                return ["0", "1", "2"].includes(value);
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
                avatar: {
                    validators: {
                        stringLength: {
                            max: 255,
                            message: "Link ảnh đại diện không được vượt quá 255 ký tự.",
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
                            callback: function (value) {
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
                            callback: function (value) {
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
                            callback: function (value) {
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
                            callback: function (value) {
                                if (!value) return true; // nullable
                                return /^https:\/\/(www\.)?linkedin\.com\/(in|company)\/.+$/.test(value);
                            },
                        },
                    },
                },
            },
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    rowSelector: ".row > .col-md-6, .row > .col-12, .row > .col-lg-4, .row > .col-lg-8",
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
                        if (displayName)
                            $("#profileDisplayName").text(displayName);
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
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        const errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach((field) => {
                            const messages = errors[field];
                            const $input = $profileForm.find(
                                `[name="${field}"]`
                            );
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

