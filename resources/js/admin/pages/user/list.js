"use strict";

$(function () {
    // Khởi tạo date picker cho filter ngày tạo
    const $datePicker = $(".date-picker");
    if ($datePicker.length && typeof flatpickr !== "undefined") {
        $datePicker.flatpickr({
            dateFormat: "d/m/Y",
        });
    }

    const userTableEl = $("#user_datatable");
    const userTrashTableEl = $("#user_datatable_trash");

    const userTable = userTableEl.DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: userTableEl.data("url"),
            data: function (d) {
                d.created_at = $("#created_at").val();
                d.verified_status = $("#email_verified_filter").val();
            },
        },
        order: [[5, "desc"]],
        language: {
            url:
                $('input[name="datatables_vi"]').val() ||
                window.datatablesViUrl,
            searchPlaceholder: "Tìm kiếm theo tên, email hoặc số điện thoại...",
        },
        columns: [
            { data: "checkbox_html", orderable: false, searchable: false },
            { data: "DT_RowIndex", orderable: false, searchable: false },
            { data: "user_html", name: "full_name" },
            {
                data: "email_html",
                name: "email",
            },
            { data: "phone_html", name: "phone" },
            {
                data: "created_at_html",
                name: "created_at",
                searchable: false,
            },
            {
                data: "email_verified_html",
                orderable: false,
                searchable: false,
            },
            {
                data: "roles_html",
                orderable: false,
                searchable: false,
            },
            { data: "action_html", orderable: false, searchable: false },
        ],
        drawCallback: function () {
            initRowCheckboxHandlers(
                "#user_datatable",
                "#selectAllUsers",
                "#bulkActionsContainerUsers",
                "#selectedCountUsers"
            );
            initDeleteButtons();
        },
    });

    const userTrashTable = userTrashTableEl.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: userTrashTableEl.data("url"),
            data: function (d) {
                d.created_at = $("#created_at").val();
                d.verified_status = $("#email_verified_filter").val();
            },
        },
        order: [[1, "desc"]],
        language: {
            url:
                $('input[name="datatables_vi"]').val() ||
                window.datatablesViUrl,
            searchPlaceholder: "Tìm kiếm theo tên hoặc email...",
        },
        columns: [
            { data: "checkbox_html", orderable: false, searchable: false },
            { data: "DT_RowIndex", orderable: false, searchable: false },
            { data: "user_html", orderable: false, searchable: false },
            { data: "email_html", orderable: false, searchable: false },
            { data: "deleted_at_html", orderable: true, searchable: false },
            { data: "action_html", orderable: false, searchable: false },
        ],
        drawCallback: function () {
            initRowCheckboxHandlers(
                "#user_datatable_trash",
                "#selectAllUsersTrash",
                "#bulkActionsContainerUsersTrash",
                "#selectedCountUsersTrash"
            );
            initRestoreForceDeleteButtons();
        },
    });

    // Filter theo ngày tạo
    $("#created_at, #email_verified_filter").on("change", function () {
        userTable.draw();
        userTrashTable.draw();
    });

    $("#clearFilter").on("click", function () {
        $("#created_at").val("");
        $("#email_verified_filter").val("");
        if ($datePicker.length && $datePicker.data("flatpickr")) {
            $datePicker[0]._flatpickr.clear();
        }
        userTable.draw();
        userTrashTable.draw();
    });

    function getSelectedIds(tableSelector) {
        const ids = [];
        document
            .querySelectorAll(`${tableSelector} .row-checkbox:checked`)
            .forEach((el) => {
                ids.push(el.value);
            });
        return ids;
    }

    // Helper function để đóng modal và cleanup backdrop
    function closeModalAndCleanup(modal) {
        const modalInstance = window.bootstrap.Modal.getInstance(modal);
        if (modalInstance) {
            modalInstance.hide();
        }

        // Đảm bảo xóa backdrop và reset body
        setTimeout(() => {
            const backdrops = document.querySelectorAll(".modal-backdrop");
            backdrops.forEach((backdrop) => backdrop.remove());
            document.body.classList.remove("modal-open");
            document.body.style.overflow = "";
            document.body.style.paddingRight = "";
        }, 300);
    }

    function initRowCheckboxHandlers(
        tableSelector,
        selectAllSelector,
        bulkContainerSelector,
        counterSelector
    ) {
        const selectAll = document.querySelector(selectAllSelector);
        const bulkContainer = document.querySelector(bulkContainerSelector);
        const counter = document.querySelector(counterSelector);

        if (!selectAll || !bulkContainer || !counter) return;

        selectAll.addEventListener("change", () => {
            document
                .querySelectorAll(`${tableSelector} .row-checkbox`)
                .forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });
            updateBulkUI();
        });

        document
            .querySelectorAll(`${tableSelector} .row-checkbox`)
            .forEach((checkbox) => {
                checkbox.addEventListener("change", () => {
                    if (!checkbox.checked) {
                        selectAll.checked = false;
                    }
                    updateBulkUI();
                });
            });

        function updateBulkUI() {
            const selected = document.querySelectorAll(
                `${tableSelector} .row-checkbox:checked`
            ).length;
            if (selected > 0) {
                bulkContainer.style.display = "block";
            } else {
                bulkContainer.style.display = "none";
            }
            const strong = counter.querySelector("strong");
            if (strong) {
                strong.textContent = selected.toString();
            }
        }
    }

    function initDeleteButtons() {
        const modal = document.querySelector("#confirmDeleteUserModal");
        if (!modal) return;

        const nameSpan = modal.querySelector("#deleteUserName");
        const confirmBtn = modal.querySelector("#confirmDeleteUserBtn");
        let currentUrl = null;

        document
            .querySelectorAll("#user_datatable .btn-delete")
            .forEach((btn) => {
                btn.addEventListener("click", () => {
                    currentUrl = btn.getAttribute("data-url");
                    const title = btn.getAttribute("data-title") || "";
                    if (nameSpan) nameSpan.textContent = title;
                    const bsModal = new window.bootstrap.Modal(modal);
                    bsModal.show();
                });
            });

        if (confirmBtn) {
            confirmBtn.addEventListener("click", () => {
                if (!currentUrl) return;
                confirmBtn.disabled = true;
                confirmBtn
                    .querySelector(".spinner-border")
                    ?.classList.remove("d-none");

                fetch(currentUrl, {
                    method: "DELETE",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                })
                    .then((res) => res.json())
                    .then((data) => {
                        if (data.status) {
                            toastr.success(
                                data.message || "Tài khoản đã ngừng hoạt động"
                            );
                            userTable.ajax.reload();
                            userTrashTable.ajax.reload();
                        } else {
                            toastr.error(data.message || "Có lỗi xảy ra", "Đã có lỗi xảy ra");
                        }
                    })
                    .catch(() => {
                        toastr.error("Có lỗi xảy ra, vui lòng thử lại", "Đã có lỗi xảy ra");
                    })
                    .finally(() => {
                        confirmBtn.disabled = false;
                        confirmBtn
                            .querySelector(".spinner-border")
                            ?.classList.add("d-none");
                        window.bootstrap.Modal.getInstance(modal)?.hide();
                    });
            });
        }

        const bulkDeleteBtn = document.querySelector("#bulkDeleteBtnUsers");
        const bulkDeleteModal = document.querySelector("#bulkDeleteUsersModal");
        const bulkDeleteCount = document.querySelector("#bulkDeleteUsersCount");
        const confirmBulkDeleteBtn = document.querySelector(
            "#confirmBulkDeleteUsersBtn"
        );

        if (
            bulkDeleteBtn &&
            bulkDeleteModal &&
            bulkDeleteCount &&
            confirmBulkDeleteBtn
        ) {
            bulkDeleteBtn.addEventListener("click", () => {
                const ids = getSelectedIds("#user_datatable");
                if (ids.length === 0) {
                    toastr.warning("Vui lòng chọn ít nhất một người dùng");
                    return;
                }
                bulkDeleteCount.textContent = ids.length.toString();
                new window.bootstrap.Modal(bulkDeleteModal).show();
            });

            confirmBulkDeleteBtn.addEventListener("click", () => {
                const ids = getSelectedIds("#user_datatable");
                if (ids.length === 0) return;

                confirmBulkDeleteBtn.disabled = true;
                confirmBulkDeleteBtn
                    .querySelector(".spinner-border")
                    ?.classList.remove("d-none");

                fetch(window.userBulkDeleteUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                    body: JSON.stringify({ ids }),
                })
                    .then((res) => res.json())
                    .then((data) => {
                        if (data.status) {
                            toastr.success(
                                data.message ||
                                    "Các tài khoản đã ngừng hoạt động"
                            );
                            userTable.ajax.reload();
                            userTrashTable.ajax.reload();
                        } else {
                            toastr.error(data.message || "Có lỗi xảy ra", "Đã có lỗi xảy ra");
                        }
                    })
                    .catch(() => {
                        toastr.error("Có lỗi xảy ra, vui lòng thử lại", "Đã có lỗi xảy ra");
                    })
                    .finally(() => {
                        confirmBulkDeleteBtn.disabled = false;
                        confirmBulkDeleteBtn
                            .querySelector(".spinner-border")
                            ?.classList.add("d-none");
                        closeModalAndCleanup(bulkDeleteModal);
                    });
            });
        }
    }

    function initRestoreForceDeleteButtons() {
        const restoreModal = document.querySelector("#confirmRestoreUserModal");
        const forceDeleteModal = document.querySelector(
            "#confirmForceDeleteUserModal"
        );

        let currentRestoreUrl = null;
        let currentForceDeleteUrl = null;

        if (restoreModal) {
            const restoreNameSpan =
                restoreModal.querySelector("#restoreUserName");
            const restoreBtn = restoreModal.querySelector(
                "#confirmRestoreUserBtn"
            );

            document
                .querySelectorAll("#user_datatable_trash .btn-restore")
                .forEach((btn) => {
                    btn.addEventListener("click", () => {
                        currentRestoreUrl = btn.getAttribute("data-url");
                        const title = btn.getAttribute("data-title") || "";
                        if (restoreNameSpan)
                            restoreNameSpan.textContent = title;
                        new window.bootstrap.Modal(restoreModal).show();
                    });
                });

            if (restoreBtn) {
                restoreBtn.addEventListener("click", () => {
                    if (!currentRestoreUrl) return;

                    restoreBtn.disabled = true;
                    restoreBtn
                        .querySelector(".spinner-border")
                        ?.classList.remove("d-none");

                    fetch(currentRestoreUrl, {
                        method: "POST",
                        headers: {
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                        },
                    })
                        .then((res) => res.json())
                        .then((data) => {
                            if (data.status) {
                                toastr.success(
                                    data.message ||
                                        "Đã kích hoạt lại tài khoản thành công"
                                );
                                userTable.ajax.reload();
                                userTrashTable.ajax.reload();
                            } else {
                                toastr.error(data.message || "Có lỗi xảy ra", "Đã có lỗi xảy ra");
                            }
                        })
                        .catch(() => {
                            toastr.error("Có lỗi xảy ra, vui lòng thử lại", "Đã có lỗi xảy ra");
                        })
                        .finally(() => {
                            restoreBtn.disabled = false;
                            restoreBtn
                                .querySelector(".spinner-border")
                                ?.classList.add("d-none");
                            window.bootstrap.Modal.getInstance(
                                restoreModal
                            )?.hide();
                        });
                });
            }
        }

        if (forceDeleteModal) {
            const forceDeleteNameSpan = forceDeleteModal.querySelector(
                "#forceDeleteUserName"
            );
            const forceDeleteBtn = forceDeleteModal.querySelector(
                "#confirmForceDeleteUserBtn"
            );

            document
                .querySelectorAll("#user_datatable_trash .btn-force-delete")
                .forEach((btn) => {
                    btn.addEventListener("click", () => {
                        currentForceDeleteUrl = btn.getAttribute("data-url");
                        const title = btn.getAttribute("data-title") || "";
                        if (forceDeleteNameSpan)
                            forceDeleteNameSpan.textContent = title;
                        new window.bootstrap.Modal(forceDeleteModal).show();
                    });
                });

            if (forceDeleteBtn) {
                forceDeleteBtn.addEventListener("click", () => {
                    if (!currentForceDeleteUrl) return;

                    forceDeleteBtn.disabled = true;
                    forceDeleteBtn
                        .querySelector(".spinner-border")
                        ?.classList.remove("d-none");

                    fetch(currentForceDeleteUrl, {
                        method: "DELETE",
                        headers: {
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                        },
                    })
                        .then((res) => res.json())
                        .then((data) => {
                            if (data.status) {
                                toastr.success(
                                    data.message ||
                                        "Xóa vĩnh viễn người dùng thành công"
                                );
                                userTable.ajax.reload();
                                userTrashTable.ajax.reload();
                            } else {
                                toastr.error(data.message || "Có lỗi xảy ra", "Đã có lỗi xảy ra");
                            }
                        })
                        .catch(() => {
                            toastr.error("Có lỗi xảy ra, vui lòng thử lại", "Đã có lỗi xảy ra");
                        })
                        .finally(() => {
                            forceDeleteBtn.disabled = false;
                            forceDeleteBtn
                                .querySelector(".spinner-border")
                                ?.classList.add("d-none");
                            window.bootstrap.Modal.getInstance(
                                forceDeleteModal
                            )?.hide();
                        });
                });
            }
        }

        const bulkRestoreBtn = document.querySelector("#bulkRestoreBtnUsers");
        const bulkForceDeleteBtn = document.querySelector(
            "#bulkForceDeleteBtnUsers"
        );
        const bulkRestoreModal = document.querySelector(
            "#bulkRestoreUsersModal"
        );
        const bulkForceDeleteModal = document.querySelector(
            "#bulkForceDeleteUsersModal"
        );
        const bulkRestoreCount = document.querySelector(
            "#bulkRestoreUsersCount"
        );
        const bulkForceDeleteCount = document.querySelector(
            "#bulkForceDeleteUsersCount"
        );
        const confirmBulkRestoreBtn = document.querySelector(
            "#confirmBulkRestoreUsersBtn"
        );
        const confirmBulkForceDeleteBtn = document.querySelector(
            "#confirmBulkForceDeleteUsersBtn"
        );

        if (
            bulkRestoreBtn &&
            bulkRestoreModal &&
            bulkRestoreCount &&
            confirmBulkRestoreBtn
        ) {
            bulkRestoreBtn.addEventListener("click", () => {
                const ids = getSelectedIds("#user_datatable_trash");
                if (ids.length === 0) {
                    toastr.warning("Vui lòng chọn ít nhất một người dùng");
                    return;
                }
                bulkRestoreCount.textContent = ids.length.toString();
                new window.bootstrap.Modal(bulkRestoreModal).show();
            });

            confirmBulkRestoreBtn.addEventListener("click", () => {
                const ids = getSelectedIds("#user_datatable_trash");
                if (ids.length === 0) return;

                confirmBulkRestoreBtn.disabled = true;
                confirmBulkRestoreBtn
                    .querySelector(".spinner-border")
                    ?.classList.remove("d-none");

                fetch(window.userBulkRestoreUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                    body: JSON.stringify({ ids }),
                })
                    .then((res) => res.json())
                    .then((data) => {
                        if (data.status) {
                            toastr.success(
                                data.message ||
                                    "Đã kích hoạt lại các tài khoản thành công"
                            );
                            userTable.ajax.reload();
                            userTrashTable.ajax.reload();
                        } else {
                            toastr.error(data.message || "Có lỗi xảy ra", "Đã có lỗi xảy ra");
                        }
                    })
                    .catch(() => {
                        toastr.error("Có lỗi xảy ra, vui lòng thử lại", "Đã có lỗi xảy ra");
                    })
                    .finally(() => {
                        confirmBulkRestoreBtn.disabled = false;
                        confirmBulkRestoreBtn
                            .querySelector(".spinner-border")
                            ?.classList.add("d-none");
                        closeModalAndCleanup(bulkRestoreModal);
                    });
            });
        }

        if (
            bulkForceDeleteBtn &&
            bulkForceDeleteModal &&
            bulkForceDeleteCount &&
            confirmBulkForceDeleteBtn
        ) {
            bulkForceDeleteBtn.addEventListener("click", () => {
                const ids = getSelectedIds("#user_datatable_trash");
                if (ids.length === 0) {
                    toastr.warning("Vui lòng chọn ít nhất một người dùng");
                    return;
                }
                bulkForceDeleteCount.textContent = ids.length.toString();
                new window.bootstrap.Modal(bulkForceDeleteModal).show();
            });

            confirmBulkForceDeleteBtn.addEventListener("click", () => {
                const ids = getSelectedIds("#user_datatable_trash");
                if (ids.length === 0) return;

                confirmBulkForceDeleteBtn.disabled = true;
                confirmBulkForceDeleteBtn
                    .querySelector(".spinner-border")
                    ?.classList.remove("d-none");

                fetch(window.userBulkForceDeleteUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                    body: JSON.stringify({ ids }),
                })
                    .then((res) => res.json())
                    .then((data) => {
                        if (data.status) {
                            toastr.success(
                                data.message ||
                                    "Đã xóa vĩnh viễn người dùng thành công"
                            );
                            userTable.ajax.reload();
                            userTrashTable.ajax.reload();
                        } else {
                            toastr.error(data.message || "Có lỗi xảy ra", "Đã có lỗi xảy ra");
                        }
                    })
                    .catch(() => {
                        toastr.error("Có lỗi xảy ra, vui lòng thử lại", "Đã có lỗi xảy ra");
                    })
                    .finally(() => {
                        confirmBulkForceDeleteBtn.disabled = false;
                        confirmBulkForceDeleteBtn
                            .querySelector(".spinner-border")
                            ?.classList.add("d-none");
                        closeModalAndCleanup(bulkForceDeleteModal);
                    });
            });
        }
    }
});
