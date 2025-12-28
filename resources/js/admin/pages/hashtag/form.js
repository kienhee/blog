"use strict";

$(function () {
    // ======================================
    // 📝 FORM VALIDATION
    // ======================================
    const $form = $("#form_hashtag");
    const $submitBtn = $("#submit_btn");

    if ($form.length) {
        const fv = FormValidation.formValidation($form[0], {
            fields: {
                name: {
                    validators: {
                        notEmpty: { message: "Vui lòng nhập tên hashtag" },
                        stringLength: {
                            min: 2,
                            max: 20,
                            message: "Tên hashtag phải từ 2 đến 20 ký tự",
                        },
                    },
                },
                slug: {
                    validators: {
                        notEmpty: { message: "Vui lòng nhập slug" },
                        stringLength: {
                            min: 2,
                            max: 20,
                            message: "Slug phải từ 2 đến 20 ký tự",
                        },
                        regexp: {
                            regexp: /^[a-z0-9]+(?:-[a-z0-9]+)*$/,
                            message:
                                "Slug chỉ được chứa chữ thường, số và dấu gạch ngang",
                        },
                    },
                },
            },
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    rowSelector: ".mb-3",
                    eleInvalidClass: "is-invalid",
                    eleValidClass: "is-valid",
                }),
                submitButton: new FormValidation.plugins.SubmitButton(),
                autoFocus: new FormValidation.plugins.AutoFocus(),
                defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
            },
            init: (instance) => {
                instance.on("plugins.message.placed", (e) => {
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

        // Submit
        $submitBtn.on("click", function (e) {
            e.preventDefault();
            fv.validate().then(function (status) {
                if (status !== "Valid") {
                    return;
                }
                $submitBtn
                    .prop("disabled", true)
                    .find(".spinner-border")
                    .removeClass("d-none");
                $form.trigger("submit");
            });
        });
    }

    // ======================================
    // 📊 CHARACTER COUNTER & SEO VALIDATION
    // ======================================
    const HASHTAG_SEO_CONFIG = {
        title: {
            selector: "#inputSlug",
            recommendedLength: 20,
            warningThreshold: 18,
            counterClass: "title-counter",
            warningClass: "title-warning",
            warningMessage:
                "Tên hashtag đã vượt quá {length} ký tự. Tối đa 20 ký tự.",
            counterBadgeClass: "badge bg-label-secondary ms-2",
            showMaxLength: true,
        },
        slug: {
            selector: "#outputSlug",
            recommendedLength: 20,
            warningThreshold: 18,
            counterClass: "slug-counter",
            warningClass: "slug-warning",
            warningMessage: "Slug đã vượt quá {length} ký tự. Tối đa 20 ký tự.",
            counterBadgeClass: "badge bg-label-secondary ms-2",
            showMaxLength: true,
        },
    };

    function initHashtagCharacterCounter(fieldConfig) {
        const $input = $(fieldConfig.selector);

        if ($input.length === 0) {
            return;
        }

        const $field = $input.closest(".mb-3");
        const maxLength =
            fieldConfig.maxLength || fieldConfig.recommendedLength;
        const warningThreshold = fieldConfig.warningThreshold;
        const showMaxLength = fieldConfig.showMaxLength === true;

        // Đặt thuộc tính maxlength để chặn nhập ngay từ input
        if (maxLength && !$input.attr("maxlength")) {
            $input.attr("maxlength", maxLength);
        }

        if (!$input.parent().hasClass("position-relative")) {
            $input.wrap('<div class="position-relative"></div>');
        }
        const $wrapper = $input.parent();

        const paddingRight = "80px";
        $input.css("padding-right", paddingRight);

        const $counter = $(
            `<span class="badge bg-label-secondary position-absolute ${fieldConfig.counterClass}" style="right: 8px; top: 50%; transform: translateY(-50%); z-index: 10; pointer-events: none;"></span>`
        );
        $wrapper.append($counter);

        function updateCounter() {
            // Chặn vượt quá maxLength ở client
            if (maxLength && $input.val().length > maxLength) {
                $input.val($input.val().slice(0, maxLength));
            }

            const length = $input.val().length;
            const $warning = $field.find(`.${fieldConfig.warningClass}`);

            if (showMaxLength) {
                $counter.text(length + " / " + maxLength + " ký tự");
            } else {
                $counter.text(length + " ký tự");
            }

            if (length > maxLength) {
                $counter
                    .removeClass("bg-label-secondary bg-label-warning")
                    .addClass("bg-label-danger");
            } else if (length > warningThreshold) {
                $counter
                    .removeClass("bg-label-secondary bg-label-danger")
                    .addClass("bg-label-warning");
            } else {
                $counter
                    .removeClass("bg-label-warning bg-label-danger")
                    .addClass("bg-label-secondary");
            }

            if (length > maxLength && $warning.length === 0) {
                const warningMsg = fieldConfig.warningMessage.replace(
                    "{length}",
                    maxLength
                );
                $field.append(
                    `<small class="text-warning d-block mt-1 ${fieldConfig.warningClass}">
                        <i class="bx bx-info-circle"></i> ${warningMsg}
                    </small>`
                );
            } else if (length <= maxLength && $warning.length > 0) {
                $warning.remove();
            }
        }

        $input.on("input", updateCounter);
        updateCounter();
    }

    // Initialize character counters
    if ($("#inputSlug").length) {
        initHashtagCharacterCounter(HASHTAG_SEO_CONFIG.title);
    }

    if ($("#outputSlug").length) {
        initHashtagCharacterCounter(HASHTAG_SEO_CONFIG.slug);
    }
});
