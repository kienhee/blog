"use strict";

$(function () {
    const $form = $("#form_blog");
    const $submitBtn = $("#submit_btn");
    const $thumbnail = $("#thumbnail");
    const $editor = $("#editor");

    // ======================================
    // 🔧 HELPERS
    // ======================================
    const setError = ($el, msg, border = true) => {
        clearErrorBelowInput($el);
        if (msg) {
            showErrorBelowInput($el, msg);
            if (border) $el.addClass("is-invalid");
        } else {
            $el.removeClass("is-invalid");
        }
    };

    const validateEditor = () => {
        const content = tinymce.get("editor")?.getContent().trim() || "";
        $editor.val(content);
        setError(
            $editor,
            !content || content === "<p><br></p>"
                ? "Vui lòng nhập nội dung bài viết"
                : "",
            false
        );
        $(".tox-tinymce").css(
            "border",
            content ? "" : "1px solid var(--bs-form-invalid-color, red)"
        );
        return !!content;
    };

    const validateThumbnail = () => {
        const val = $thumbnail.val();
        setError($thumbnail, val ? "" : "Vui lòng chọn ảnh đại diện", false);
        $("#upload_box, .upload_box").css(
            "border",
            val ? "" : "2px dashed var(--bs-form-invalid-color, red)"
        );
        return !!val;
    };

    // ======================================
    // 📝 FORM VALIDATION
    // ======================================
    let fv = null;
    if ($form.length) {
        fv = FormValidation.formValidation($form[0], {
            fields: {
                title: {
                    validators: {
                        notEmpty: {
                            message: "Vui lòng nhập tiêu đề bài viết",
                        },
                        stringLength: {
                            min: 6,
                            max: 255,
                            message: "Tiêu đề phải từ 6 đến 255 ký tự",
                        },
                    },
                },
                slug: {
                    validators: {
                        notEmpty: { message: "Vui lòng nhập slug" },
                        stringLength: {
                            min: 6,
                            max: 255,
                            message: "Slug phải từ 6 đến 255 ký tự",
                        },
                        regexp: {
                            regexp: /^[a-z0-9]+(?:-[a-z0-9]+)*$/,
                            message:
                                "Slug chỉ được chứa chữ thường, số và dấu gạch ngang",
                        },
                    },
                },
                category_id: {
                    validators: {
                        notEmpty: { message: "Vui lòng chọn danh mục" },
                    },
                },
                "hashtags[]": {
                    validators: {
                        notEmpty: {
                            message: "Vui lòng chọn ít nhất 1 hashtag",
                        },
                    },
                },
                description: {
                    validators: {
                        stringLength: {
                            max: 255,
                            message:
                                "Meta description không được vượt quá 255 ký tự",
                        },
                    },
                },
            },
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    rowSelector: ".mb-3",
                    eleValidClass: "",
                }),
                submitButton: new FormValidation.plugins.SubmitButton(),
                autoFocus: new FormValidation.plugins.AutoFocus(),
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

        // Realtime validate
        $thumbnail.on("change input", validateThumbnail);
        if (tinymce.get("editor")) {
            tinymce.get("editor").on("change keyup", validateEditor);
        }

        // Submit - PHẢI validate tất cả trước khi submit
        $submitBtn.on("click", function (e) {
            e.preventDefault();

            // Validate custom fields (thumbnail & editor)
            const thumbnailValid = validateThumbnail();
            const editorValid = validateEditor();

            if (!thumbnailValid || !editorValid) {
                return;
            }

            // Validate tất cả fields trong FormValidation
            fv.validate().then(function (status) {
                if (status !== "Valid") {
                    return;
                }

                // Tất cả validation đã pass, submit form
                $submitBtn
                    .prop("disabled", true)
                    .find(".spinner-border")
                    .removeClass("d-none");
                $form.submit();
            });
        });
    }

    // ======================================
    // 📊 CHARACTER COUNTER & SEO VALIDATION
    // ======================================
    const SEO_CONFIG = {
        title: {
            selector: "#inputSlug",
            recommendedLength: 60,
            warningThreshold: 50,
            counterClass: "title-counter",
            warningClass: "title-warning",
            warningMessage:
                "Tiêu đề đã vượt quá {length} ký tự. Nên rút gọn để tối ưu SEO.",
            counterBadgeClass: "badge bg-label-secondary ms-2",
            showMaxLength: false,
        },
        metaDescription: {
            selector: "#description",
            recommendedLength: 150,
            warningThreshold: 130,
            counterClass: "meta-description-counter",
            warningClass: "meta-description-warning",
            warningMessage:
                "Meta description đã vượt quá {length} ký tự. Nên rút gọn để tối ưu SEO.",
            counterBadgeClass: "badge bg-label-secondary ms-2",
            showMaxLength: false,
        },
    };

    function initCharacterCounter(fieldConfig) {
        const $input = $(fieldConfig.selector);

        if ($input.length === 0) {
            return;
        }

        const $field = $input.closest(".mb-3");
        const maxLength =
            fieldConfig.maxLength || fieldConfig.recommendedLength;
        const warningThreshold = fieldConfig.warningThreshold;
        const showMaxLength = fieldConfig.showMaxLength === true;

        // Wrap input in a container with position relative
        if (!$input.parent().hasClass("position-relative")) {
            $input.wrap('<div class="position-relative"></div>');
        }
        const $wrapper = $input.parent();

        // Add padding-right to input
        const isTextarea = $input.is("textarea");
        const paddingRight = isTextarea ? "60px" : "80px";
        $input.css("padding-right", paddingRight);

        // Create counter badge
        const $counter = $(
            `<span class="badge bg-label-secondary position-absolute ${fieldConfig.counterClass}" style="right: 8px; top: 50%; transform: translateY(-50%); z-index: 10; pointer-events: none;"></span>`
        );
        if (isTextarea) {
            $counter.css({
                top: "auto",
                bottom: "8px",
                transform: "none",
            });
        }
        $wrapper.append($counter);

        function updateCounter() {
            const length = $input.val().length;
            const $warning = $field.find(`.${fieldConfig.warningClass}`);

            // Update counter badge text
            if (showMaxLength) {
                $counter.text(length + " / " + maxLength + " ký tự");
            } else {
                $counter.text(length + " ký tự");
            }

            // Update badge color
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

            // Show/hide warning message
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
    initCharacterCounter(SEO_CONFIG.title);
    initCharacterCounter(SEO_CONFIG.metaDescription);

    // ======================================
    // 📅 SCHEDULED AT HANDLING
    // ======================================
    function initScheduledAt() {
        const $scheduledAtInput = $("#scheduled_at");
        const $statusSelect = $("#status");
        const $scheduledAtField = $scheduledAtInput.closest(".mb-3");

        if ($scheduledAtInput.length === 0) {
            return;
        }

        const isDisabled = $scheduledAtInput.prop("disabled");

        // Hàm để ẩn/hiện input scheduled_at
        function toggleScheduledAtField(show) {
            if (show) {
                $scheduledAtField.slideDown(300);
            } else {
                $scheduledAtField.slideUp(300);
            }
        }

        // Kiểm tra trạng thái ban đầu và ẩn/hiện field
        const initialStatus = $statusSelect.val();
        if (initialStatus === "scheduled") {
            $scheduledAtField.show();
        } else {
            $scheduledAtField.hide();
        }

        // Lưu reference của toggle function để dùng trong các event handler
        window.toggleScheduledAtField = toggleScheduledAtField;

        if (!isDisabled && typeof flatpickr !== "undefined") {
            const flatpickrInstance = flatpickr($scheduledAtInput[0], {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                time_24hr: true,
                minDate: "today",
                minuteIncrement: 1,
                locale: {
                    firstDayOfWeek: 1,
                },
                onChange: function (selectedDates, dateStr, instance) {
                    if (dateStr) {
                        // Khi chọn scheduled_at, đổi status thành "scheduled" và hiện field
                        if ($statusSelect.val() !== "scheduled") {
                            $statusSelect.val("scheduled").trigger("change");
                            toastr.info(
                                'Bài viết sẽ được đặt ở trạng thái "Lên lịch" và sẽ tự động đăng vào thời gian đã chọn.',
                                "Thông báo"
                            );
                        }
                        // Đảm bảo field được hiện
                        if (
                            typeof window.toggleScheduledAtField === "function"
                        ) {
                            window.toggleScheduledAtField(true);
                        }
                    } else {
                        $scheduledAtInput.val("");
                        // Nếu đang ở status "scheduled" và xóa scheduled_at, đổi về "draft" và ẩn field
                        if ($statusSelect.val() === "scheduled") {
                            $statusSelect.val("draft").trigger("change");
                        }
                    }
                },
            });

            // Handle existing value (edit form)
            if ($scheduledAtInput.val()) {
                const currentValue = $scheduledAtInput.val();
                if (currentValue) {
                    const formattedValue = currentValue.replace("T", " ");
                    flatpickrInstance.setDate(formattedValue, false);
                }
            }

            // Xử lý khi status thay đổi
            $statusSelect.on("change", function () {
                const selectedStatus = $(this).val();

                if (selectedStatus === "scheduled") {
                    // Khi chọn "scheduled", hiện input và tự động mở flatpickr
                    toggleScheduledAtField(true);

                    // Highlight input
                    $scheduledAtInput.addClass("border-warning");
                    setTimeout(() => {
                        $scheduledAtInput.removeClass("border-warning");
                    }, 2000);

                    // Tự động mở flatpickr nếu chưa có scheduled_at
                    if (flatpickrInstance.selectedDates.length === 0) {
                        setTimeout(() => {
                            flatpickrInstance.open();
                        }, 350);
                        toastr.info(
                            "Vui lòng chọn thời gian đăng bài.",
                            "Thông báo"
                        );
                    } else {
                        // Nếu đã có scheduled_at, chỉ focus vào input
                        $scheduledAtInput.focus();
                    }
                } else {
                    // Khi chọn status khác (published, draft), ẩn input và xóa scheduled_at
                    toggleScheduledAtField(false);

                    if (selectedStatus === "published") {
                        // Khi chọn "published", phải xóa scheduled_at
                        if (flatpickrInstance.selectedDates.length > 0) {
                            flatpickrInstance.clear();
                            $scheduledAtInput.val("");
                            toastr.info(
                                'Đã xóa lịch đăng bài vì bài viết được đặt ở trạng thái "Xuất bản".',
                                "Thông báo"
                            );
                        }
                    } else {
                        // Khi chọn "draft", xóa scheduled_at nếu có
                        if (flatpickrInstance.selectedDates.length > 0) {
                            flatpickrInstance.clear();
                            $scheduledAtInput.val("");
                        }
                    }
                }
            });

            $scheduledAtInput.data("flatpickr", flatpickrInstance);
        } else {
            // Fallback for disabled input or no flatpickr
            $scheduledAtInput.on("change", function () {
                if ($(this).val()) {
                    // Khi chọn scheduled_at, đổi status thành "scheduled"
                    if ($statusSelect.val() !== "scheduled") {
                        $statusSelect.val("scheduled").trigger("change");
                        toastr.info(
                            'Bài viết sẽ được đặt ở trạng thái "Lên lịch" và sẽ tự động đăng vào thời gian đã chọn.',
                            "Thông báo"
                        );
                    }
                } else {
                    // Nếu xóa scheduled_at và đang ở status "scheduled", đổi về "draft"
                    if ($statusSelect.val() === "scheduled") {
                        $statusSelect.val("draft").trigger("change");
                    }
                }
            });

            $statusSelect.on("change", function () {
                const selectedStatus = $(this).val();

                if (selectedStatus === "scheduled") {
                    // Khi chọn "scheduled", hiện input
                    toggleScheduledAtField(true);

                    // Highlight input và focus
                    $scheduledAtInput.addClass("border-warning");
                    setTimeout(() => {
                        $scheduledAtInput.removeClass("border-warning");
                    }, 2000);

                    if (!$scheduledAtInput.val()) {
                        toastr.info(
                            "Vui lòng chọn thời gian đăng bài.",
                            "Thông báo"
                        );
                    } else {
                        $scheduledAtInput.focus();
                    }
                } else {
                    // Khi chọn status khác, ẩn input và xóa scheduled_at
                    toggleScheduledAtField(false);

                    if (selectedStatus === "published") {
                        if ($scheduledAtInput.val()) {
                            $scheduledAtInput.val("");
                            toastr.info(
                                'Đã xóa lịch đăng bài vì bài viết được đặt ở trạng thái "Xuất bản".',
                                "Thông báo"
                            );
                        }
                    } else {
                        // Khi chọn "draft", xóa scheduled_at nếu có
                        if ($scheduledAtInput.val()) {
                            $scheduledAtInput.val("");
                        }
                    }
                }
            });
        }
    }

    initScheduledAt();
});
