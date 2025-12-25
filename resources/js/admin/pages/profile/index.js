/**
 * Profile Page - Change Password (Optimized)
 */
"use strict";

$(function () {
    const $form = $("#formChangePassword");
    const $reset = $("#resetBtn");
    const $submit = $("#submitBtn");
    const $tabPassword = $("#password-tab");
    const hasErrors = window.hasPasswordErrors || false;
    const hasProfileErrors = window.hasProfileErrors || false;

    /**
     * Reset form
     */
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

    /**
     * Form Validation
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
                        notEmpty: { message: "Vui lòng nhập mật khẩu mới." },
                        stringLength: {
                            min: 6,
                            max: 255,
                            message: "Mật khẩu mới phải từ 6 đến 255 ký tự.",
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

        // On valid submit
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
    }

    /**
     * Tab navigation (auto-open password tab if needed)
     */
    const openPasswordTab = () => {
        const $btn = $('[data-bs-target="#password-tab"]');
        if ($btn.length) {
            new bootstrap.Tab($btn[0]).show();

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
    };

    if (window.location.hash === "#password-tab" || hasErrors)
        openPasswordTab();

    $('[data-bs-target="#password-tab"]').on("click", () => {
        history.pushState(null, "", "#password-tab");
    });

    $('[data-bs-target="#profile-tab"]').on("click", () => {
        history.pushState(null, "", window.location.pathname);
    });

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
    // AJAX submit for profile update
    // ================================
    const $profileForm = $("#profileForm");
    const $profileSubmit = $("#profileSubmitBtn");
    const $profileSpinner = $profileSubmit.find(".spinner-border");
    const $birthday = $("#birthday");

    if ($profileForm.length) {
        $profileForm.on("submit", function (e) {
            e.preventDefault();

            // clear errors
            $profileForm.find(".is-invalid").removeClass("is-invalid");
            $profileForm.find(".invalid-feedback").remove();

            // Chuẩn hóa ngày sinh về Y-m-d trước khi submit
            const birthdayVal = $birthday.val();
            if (birthdayVal) {
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
