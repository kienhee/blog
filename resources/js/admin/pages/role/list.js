"use strict";

$(function () {
    // Khởi tạo date picker cho filter ngày tạo
    const $datePicker = $(".date-picker");
    if ($datePicker.length && typeof flatpickr !== "undefined") {
        $datePicker.flatpickr({
            dateFormat: "d/m/Y",
        });
    }

    const roleTableEl = $("#role_datatable");

    const roleTable = roleTableEl.DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: roleTableEl.data("url"),
            data: function (d) {
                d.created_at = $("#created_at").val();
            },
        },
        order: [[5, "desc"]],
        language: {
            url:
                $('input[name="datatables_vi"]').val() ||
                window.datatablesViUrl,
            searchPlaceholder: "Tìm kiếm theo tên vai trò...",
        },
        columns: [
            { data: "checkbox_html", orderable: false, searchable: false },
            { data: "DT_RowIndex", orderable: false, searchable: false },
            { data: "name_html", name: "name" },
            {
                data: "permissions_count_html",
                orderable: false,
                searchable: false,
            },
            {
                data: "users_count_html",
                orderable: false,
                searchable: false,
            },
            {
                data: "created_at_html",
                name: "created_at",
                searchable: false,
            },
            { data: "action_html", orderable: false, searchable: false },
        ],
        drawCallback: function () {
            initDeleteButtons();
        },
    });

    // Filter theo ngày tạo
    $("#created_at").on("change", function () {
        roleTable.draw();
    });

    $("#clearFilter").on("click", function () {
        $("#created_at").val("");
        if ($datePicker.length && $datePicker.data("flatpickr")) {
            $datePicker[0]._flatpickr.clear();
        }
        roleTable.draw();
    });

    function initDeleteButtons() {
        const modal = document.querySelector("#confirmDeleteRoleModal");
        if (!modal) return;

        const nameSpan = modal.querySelector("#deleteRoleName");
        const confirmBtn = modal.querySelector("#confirmDeleteRoleBtn");
        let currentUrl = null;

        document
            .querySelectorAll("#role_datatable .btn-delete")
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
                            if (typeof toastr !== "undefined") {
                                toastr.success(
                                    data.message || "Xóa vai trò thành công"
                                );
                            }
                            roleTable.ajax.reload();
                        } else {
                            if (typeof toastr !== "undefined") {
                                toastr.error(data.message || "Có lỗi xảy ra", "Đã có lỗi xảy ra");
                            }
                        }
                    })
                    .catch(() => {
                        if (typeof toastr !== "undefined") {
                            toastr.error("Có lỗi xảy ra, vui lòng thử lại", "Đã có lỗi xảy ra");
                        }
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
    }
});

