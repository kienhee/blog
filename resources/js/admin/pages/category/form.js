"use strict";

$(function () {
    // ======================================
    // 📝 FORM VALIDATION
    // ======================================
    const form = document.getElementById("form_category");

    if (form) {
        const fv = FormValidation.formValidation(form, {
            fields: {
                name: {
                    validators: {
                        notEmpty: {
                            message: "Tên danh mục là bắt buộc",
                        },
                        stringLength: {
                            min: 3,
                            max: 255,
                            message: "Tên danh mục phải từ 3 đến 255 ký tự",
                        },
                    },
                },
                slug: {
                    validators: {
                        notEmpty: {
                            message: "Slug không được để trống",
                        },
                        regexp: {
                            regexp: /^[a-z0-9-]+$/,
                            message:
                                "Slug chỉ được chứa chữ thường, số và dấu gạch ngang",
                        },
                        stringLength: {
                            min: 3,
                            max: 255,
                            message: "Slug phải từ 3 đến 255 ký tự",
                        },
                    },
                },
                description: {
                    validators: {
                        stringLength: {
                            max: 255,
                            message: "Mô tả không được vượt quá 255 ký tự",
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
                autoFocus: new FormValidation.plugins.AutoFocus(),
                submitButton: new FormValidation.plugins.SubmitButton(),
            },
        });

        fv.on("core.form.valid", function () {
            const btn = $("#submit_btn");
            btn.prop("disabled", true);
            btn.find(".spinner-border").removeClass("d-none");
            form.submit();
        });
    }

    // ======================================
    // 🌳 PARENT TREE SELECTOR (jstree)
    // ======================================
    const $parentTree = $("#jstree-parent");
    const $parentId = $("#parent_id");

    if ($parentTree.length) {
        const theme = $("html").hasClass("light-style")
            ? "default"
            : "default-dark";

        const currentCategoryId = $parentTree.data("exclude-id") || null;

        const loadParentTree = (selectedId = null) => {
            const url = $parentTree.data("url");

            $parentTree.jstree("destroy").empty();
            if (!url) return;

            const requestData = {};

            if (currentCategoryId) {
                requestData.exclude_id = currentCategoryId;
            }

            $.ajax({
                url: url,
                data: requestData,
                success: function (response) {
                    let categories = [];
                    let disabledIds = [];

                    if (Array.isArray(response)) {
                        categories = response;
                        disabledIds = [];
                    } else if (response.categories && response.disabled_ids) {
                        categories = response.categories;
                        disabledIds = response.disabled_ids.map((id) =>
                            parseInt(id)
                        );
                    } else {
                        categories = response;
                        disabledIds = [];
                    }

                    const jstreeData = categories.map((item) => {
                        const nodeId = parseInt(item.id);
                        const isDisabled = disabledIds.includes(nodeId);

                        return {
                            id: item.id,
                            parent: item.parent_id ? item.parent_id : "#",
                            text: item.name,
                            icon: "bx bx-folder",
                            state: {
                                disabled: isDisabled,
                            },
                        };
                    });

                    $parentTree.data("disabled-ids", disabledIds);

                    $parentTree
                        .jstree({
                            core: {
                                data: jstreeData,
                                themes: {
                                    name: theme,
                                    responsive: true,
                                    dots: true,
                                },
                                check_callback: function (
                                    operation,
                                    node,
                                    node_parent,
                                    node_position,
                                    more
                                ) {
                                    if (operation === "select_node") {
                                        const disabledIdsList =
                                            $parentTree.data("disabled-ids") ||
                                            [];

                                        if (node.state && node.state.disabled) {
                                            return false;
                                        }

                                        const nodeId =
                                            typeof node === "string"
                                                ? parseInt(node)
                                                : parseInt(node.id || node);
                                        if (disabledIdsList.includes(nodeId)) {
                                            return false;
                                        }
                                    }
                                    return true;
                                },
                            },
                            plugins: ["types"],
                            types: {
                                default: { icon: "bx bx-folder" },
                            },
                        })
                        .on("ready.jstree", function () {
                            const instance = $parentTree.jstree(true);
                            const disabledIdsList =
                                $parentTree.data("disabled-ids") || [];

                            if (disabledIdsList.length > 0) {
                                disabledIdsList.forEach(function (nodeId) {
                                    try {
                                        instance.disable_node(nodeId);
                                    } catch (e) {
                                        // Node có thể đã được disable trong state
                                    }
                                });
                            }

                            instance.open_all();

                            if (selectedId) {
                                const selectedIdInt = parseInt(selectedId);
                                if (!disabledIdsList.includes(selectedIdInt)) {
                                    instance.select_node(selectedId);
                                    instance
                                        .get_node(selectedId, true)
                                        .scrollIntoView({
                                            behavior: "smooth",
                                            block: "center",
                                        });
                                }
                            }
                        });

                    $parentTree
                        .off("select_node.jstree")
                        .on("select_node.jstree", function (e, data) {
                            const disabledIdsList =
                                $parentTree.data("disabled-ids") || [];
                            const nodeId = parseInt(data.node.id);

                            if (!disabledIdsList.includes(nodeId)) {
                                $parentId.val(data.node.id);
                            } else {
                                const instance = $parentTree.jstree(true);
                                instance.deselect_node(data.node);
                            }
                        });
                },
                error: function (xhr) {
                    // Silent fail
                },
            });
        };

        const initialParentId = $parentId.val();
        loadParentTree(initialParentId);
    }

    // ======================================
    // 📊 CHARACTER COUNTER & SEO VALIDATION
    // ======================================
    const CATEGORY_SEO_CONFIG = {
        title: {
            selector: "#inputSlug",
            recommendedLength: 60,
            warningThreshold: 50,
            counterClass: "title-counter",
            warningClass: "title-warning",
            warningMessage:
                "Tên danh mục đã vượt quá {length} ký tự. Nên rút gọn để tối ưu SEO.",
            counterBadgeClass: "badge bg-label-secondary ms-2",
            showMaxLength: false,
        },
        description: {
            selector: "#description",
            recommendedLength: 150,
            warningThreshold: 130,
            counterClass: "description-counter",
            warningClass: "description-warning",
            warningMessage:
                "Mô tả đã vượt quá {length} ký tự. Nên rút gọn để tối ưu SEO.",
            counterBadgeClass: "badge bg-label-secondary ms-2",
            showMaxLength: false,
        },
    };

    function initCategoryCharacterCounter(fieldConfig) {
        const $input = $(fieldConfig.selector);

        if ($input.length === 0) {
            return;
        }

        const $field = $input.closest(".mb-3");
        const maxLength =
            fieldConfig.maxLength || fieldConfig.recommendedLength;
        const warningThreshold = fieldConfig.warningThreshold;
        const showMaxLength = fieldConfig.showMaxLength === true;

        if (!$input.parent().hasClass("position-relative")) {
            $input.wrap('<div class="position-relative"></div>');
        }
        const $wrapper = $input.parent();

        const isTextarea = $input.is("textarea");
        const paddingRight = isTextarea ? "60px" : "80px";
        $input.css("padding-right", paddingRight);

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
        initCategoryCharacterCounter(CATEGORY_SEO_CONFIG.title);
    }

    if ($("#description").length) {
        initCategoryCharacterCounter(CATEGORY_SEO_CONFIG.description);
    }
});
