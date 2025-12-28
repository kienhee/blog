/**
 * Newsletter Subscription Form Validation
 */
"use strict";

$(function () {
    // ================================
    // Constants & Selectors
    // ================================
    const $form = $("#newsletter-form");
    const $emailInput = $("#newsletter-email");
    const $submitBtn = $form.find('button[type="submit"]');
    const $messageDiv = $("#newsletter-message");

    // ================================
    // Form Validation Setup
    // ================================
    if ($form.length && typeof FormValidation !== "undefined") {
        // Validation rules
        const validationRules = {
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
        };

        const fvNewsletter = FormValidation.formValidation($form[0], {
            fields: validationRules,
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    eleInvalidClass: "is-invalid",
                    eleValidClass: "is-valid",
                    rowSelector: ".input-group",
                }),
                submitButton: new FormValidation.plugins.SubmitButton(),
            },
        });

        // Store instance globally for potential reuse
        window.fvNewsletter = fvNewsletter;

        // ================================
        // Handle Form Submission
        // ================================
        fvNewsletter.on("core.form.valid", function () {
            // Disable submit button
            $submitBtn.prop("disabled", true);
            const originalText = $submitBtn.html();
            $submitBtn.html('<span class="spinner-border spinner-border-sm me-1"></span>Đang gửi...');

            // Clear previous messages
            $messageDiv.removeClass("alert alert-success alert-danger").html("");

            // Get form data
            const formData = {
                email: $emailInput.val().trim(),
                _token: $('meta[name="csrf-token"]').attr("content"),
            };

            // Submit via AJAX
            $.ajax({
                url: $form.attr("action"),
                method: "POST",
                data: formData,
                success: function (response) {
                    if (response.success) {
                        // Show success message
                        $messageDiv
                            .addClass("alert alert-success")
                            .html('<i class="bx bx-check-circle me-2"></i>' + response.message);

                        // Reset form
                        $form[0].reset();
                        fvNewsletter.resetForm(true);
                        // Clear error state
                        $emailInput.removeClass("is-invalid");
                    } else {
                        // Show error message
                        $messageDiv
                            .addClass("alert alert-danger")
                            .html('<i class="bx bx-error-circle me-2"></i>' + (response.message || "Đã có lỗi xảy ra."));
                    }
                },
                error: function (xhr) {
                    let errorMessage = "Đã có lỗi xảy ra. Vui lòng thử lại sau.";

                    if (xhr.status === 422) {
                        // Validation errors from server
                        const errors = xhr.responseJSON?.errors || {};
                        if (errors.email && errors.email.length > 0) {
                            errorMessage = errors.email[0];
                        } else if (xhr.responseJSON?.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        // Mark field as invalid
                        $emailInput.addClass("is-invalid");
                        $emailInput.removeClass("is-valid");
                        // Show error message in message div
                        $messageDiv
                            .addClass("alert alert-danger")
                            .html('<i class="bx bx-error-circle me-2"></i>' + errorMessage);
                    } else if (xhr.responseJSON?.message) {
                        errorMessage = xhr.responseJSON.message;
                        // Show error message in message div
                        $messageDiv
                            .addClass("alert alert-danger")
                            .html('<i class="bx bx-error-circle me-2"></i>' + errorMessage);
                    } else {
                        // Show generic error message
                        $messageDiv
                            .addClass("alert alert-danger")
                            .html('<i class="bx bx-error-circle me-2"></i>' + errorMessage);
                    }
                },
                complete: function () {
                    // Re-enable submit button
                    $submitBtn.prop("disabled", false);
                    $submitBtn.html(originalText);

                    // Scroll to message if it exists
                    if ($messageDiv.html()) {
                        $messageDiv[0].scrollIntoView({ behavior: "smooth", block: "nearest" });
                    }
                },
            });
        });
    } else {
        // Fallback: Simple HTML5 validation with AJAX if FormValidation not available
        $form.on("submit", function (e) {
            e.preventDefault();

            // Basic validation
            const email = $emailInput.val().trim();
            if (!email) {
                $messageDiv
                    .addClass("alert alert-danger")
                    .html('<i class="bx bx-error-circle me-2"></i>Vui lòng nhập email.');
                return;
            }

            // Simple email regex check
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                $messageDiv
                    .addClass("alert alert-danger")
                    .html('<i class="bx bx-error-circle me-2"></i>Email không hợp lệ.');
                return;
            }

            // Disable submit button
            $submitBtn.prop("disabled", true);
            const originalText = $submitBtn.html();
            $submitBtn.html('<span class="spinner-border spinner-border-sm me-1"></span>Đang gửi...');

            // Clear previous messages
            $messageDiv.removeClass("alert alert-success alert-danger").html("");

            // Submit via AJAX
            $.ajax({
                url: $form.attr("action"),
                method: "POST",
                data: {
                    email: email,
                    _token: $('meta[name="csrf-token"]').attr("content"),
                },
                success: function (response) {
                    if (response.success) {
                        $messageDiv
                            .addClass("alert alert-success")
                            .html('<i class="bx bx-check-circle me-2"></i>' + response.message);
                        $form[0].reset();
                    } else {
                        $messageDiv
                            .addClass("alert alert-danger")
                            .html('<i class="bx bx-error-circle me-2"></i>' + (response.message || "Đã có lỗi xảy ra."));
                    }
                },
                error: function (xhr) {
                    let errorMessage = "Đã có lỗi xảy ra. Vui lòng thử lại sau.";
                    if (xhr.responseJSON?.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    $messageDiv
                        .addClass("alert alert-danger")
                        .html('<i class="bx bx-error-circle me-2"></i>' + errorMessage);
                },
                complete: function () {
                    $submitBtn.prop("disabled", false);
                    $submitBtn.html(originalText);
                },
            });
        });
    }

    // Clear message when user starts typing
    $emailInput.on("input", function () {
        // Clear error message
        if ($messageDiv.hasClass("alert-danger")) {
            $messageDiv.removeClass("alert alert-danger").html("");
        }
        // Clear invalid state
        $emailInput.removeClass("is-invalid");
        // Revalidate if FormValidation is available
        if (window.fvNewsletter) {
            window.fvNewsletter.revalidateField("email");
        }
    });
});

