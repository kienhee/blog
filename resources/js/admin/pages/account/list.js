"use strict";

$(function () {
    // Khởi tạo date picker cho filter
    const $datePicker = $(".date-picker");
    if ($datePicker.length) {
        $datePicker.flatpickr({
            dateFormat: "d/m/Y",
        });
    }

    // ======================================
    // 📋 KHỞI TẠO DATATABLE CHO DANH SÁCH
    // ======================================
    let datatable = $("#account_datatable");

    if (datatable.length) {
        let urlGetData = datatable.data("url");
        window.accountTable = datatable.DataTable({
            processing: true,
            serverSide: false, // Client-side để hỗ trợ drag & drop
            responsive: true,
            ajax: {
                url: urlGetData,
                data: function (d) {
                    d.created_at = $("#created_at").val();
                },
            },
            order: [[1, "asc"]], // Sort by STT (order column)
            drawCallback: function (settings) {
                // Reset select all checkbox khi table redraw
                $("#selectAllAccounts").prop("checked", false);
                if (typeof window.selectedAccountIds !== "undefined") {
                    window.selectedAccountIds = [];
                }
                $("#bulkActionsContainer").hide();
                
                // Initialize sortable after table is drawn
                initSortable();
            },
            language: {
                url: $("input[name='datatables_vi']").val() || window.datatablesViUrl,
                searchPlaceholder: "Tìm kiếm theo tên...",
            },
            columns: [
                {
                    data: "checkbox_html",
                    name: "checkbox",
                    orderable: false,
                    searchable: false,
                    width: "50px",
                },
                {
                    data: "DT_RowIndex",
                    name: "DT_RowIndex",
                    orderable: false,
                    searchable: false,
                    width: "50px",
                },
                { data: "name_html", name: "name" },
                { 
                    data: "password_html", 
                    name: "password",
                    orderable: false,
                    searchable: false,
                },
                { 
                    data: "type_html", 
                    name: "type",
                    orderable: false,
                    searchable: false,
                },
                { data: "note_html", name: "note" },
                {
                    data: "created_at_html",
                    name: "created_at",
                    searchable: false,
                },
                {
                    data: "action_html",
                    name: "action",
                    orderable: false,
                    searchable: false,
                },
            ],
        });

        // Filter
        $("#created_at").on("change", function () {
            window.accountTable.draw();
        });

        // Reset filter
        $("#clearFilter").on("click", function () {
            $("#created_at").val("");
            if ($datePicker.length && $datePicker.data("flatpickr")) {
                $datePicker[0]._flatpickr.clear();
            }
            window.accountTable.draw();
        });
    }

    // ======================================
    // 🔄 INITIALIZE SORTABLE (DRAG & DROP)
    // ======================================
    function initSortable() {
        const tbody = document.querySelector('#account_datatable tbody');
        if (!tbody || typeof Sortable === 'undefined') return;

        // Destroy existing sortable instance if any
        if (window.accountSortable) {
            window.accountSortable.destroy();
        }

        window.accountSortable = Sortable.create(tbody, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function(evt) {
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const orders = rows.map((row, index) => {
                    const checkbox = row.querySelector('.row-checkbox');
                    return {
                        id: checkbox ? checkbox.value : null,
                        order: index + 1
                    };
                }).filter(item => item.id !== null);

                // Update order on server
                $.ajaxSetup({
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    },
                });

                $.ajax({
                    url: window.accountUpdateOrderUrl,
                    type: "POST",
                    data: { orders: orders },
                    success: function(res) {
                        if (res.status) {
                            toastr.success(res.message || 'Cập nhật thứ tự thành công', 'Thông báo');
                            // Không cần reload table vì DOM đã được SortableJS di chuyển và server đã cập nhật
                            // Item đã ở vị trí mới, chỉ cần giữ nguyên
                        } else {
                            toastr.error(res.message || 'Có lỗi xảy ra', 'Lỗi');
                            // Reload để reset về trạng thái cũ khi có lỗi
                            window.accountTable.ajax.reload(null, false);
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Có lỗi xảy ra khi cập nhật thứ tự', 'Lỗi');
                        // Reload để reset về trạng thái cũ khi có lỗi
                        window.accountTable.ajax.reload(null, false);
                    }
                });
            }
        });
    }

    // ======================================
    // 📦 XỬ LÝ BULK ACTIONS (CHỌN NHIỀU)
    // ======================================
    if (typeof window.selectedAccountIds === "undefined") {
        window.selectedAccountIds = [];
    }

    // Chọn tất cả
    $(document).on("change", "#selectAllAccounts", function () {
        const isChecked = $(this).is(":checked");
        $("#account_datatable tbody .row-checkbox").prop("checked", isChecked);
        updateSelectedAccountIds();
    });

    // Chọn từng item
    $(document).on("change", "#account_datatable tbody .row-checkbox", function () {
        updateSelectedAccountIds();
        const totalCheckboxes = $("#account_datatable tbody .row-checkbox").length;
        const checkedCheckboxes = $("#account_datatable tbody .row-checkbox:checked").length;
        $("#selectAllAccounts").prop("checked", totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
    });

    // Update selected IDs và hiển thị bulk actions
    function updateSelectedAccountIds() {
        window.selectedAccountIds = [];
        $("#account_datatable tbody .row-checkbox:checked").each(function () {
            window.selectedAccountIds.push($(this).val());
        });

        const count = window.selectedAccountIds.length;
        $("#selectedCount strong").text(count);

        if (count > 0) {
            $("#bulkActionsContainer").slideDown();
        } else {
            $("#bulkActionsContainer").slideUp();
        }
    }

    // Bulk delete
    $(document).on("click", "#bulkDeleteBtn", function () {
        if (window.selectedAccountIds.length === 0) {
            toastr.warning("Vui lòng chọn ít nhất một tài khoản", "Thông báo");
            return;
        }

        $("#bulkDeleteCount").text(window.selectedAccountIds.length);
        const modal = new bootstrap.Modal($("#bulkDeleteModal"));
        modal.show();
    });

    // Confirm bulk delete
    $(document).on("click", "#confirmBulkDeleteBtn", function () {
        const btn = $(this);
        const spinner = btn.find(".spinner-border");

        btn.prop("disabled", true);
        spinner.removeClass("d-none");

        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        $.ajax({
            url: window.accountBulkDeleteUrl,
            type: "POST",
            data: { ids: window.selectedAccountIds },
            success: function (res) {
                $("#bulkDeleteModal").modal("hide");
                if (res.status) {
                    toastr.success(res.message, "Thông báo");
                    if (typeof window.accountTable !== "undefined") {
                        window.accountTable.ajax.reload(null, false); // Reload data từ server, false = giữ nguyên trang hiện tại
                    }
                    window.selectedAccountIds = [];
                    $("#selectAllAccounts").prop("checked", false);
                    $("#bulkActionsContainer").slideUp();
                } else {
                    toastr.error(res.message, "Thông báo");
                }
            },
            error: function (xhr) {
                let message = "Lỗi khi xóa";
                if (xhr.responseJSON) {
                    message = xhr.responseJSON.message || message;
                }
                toastr.error(message, "Thông báo");
            },
            complete: function () {
                btn.prop("disabled", false);
                spinner.addClass("d-none");
            },
        });
    });

    // ======================================
    // 📝 XỬ LÝ OFFCANVAS FORM (CREATE/EDIT)
    // ======================================
    const accountOffcanvas = new bootstrap.Offcanvas(document.getElementById('accountOffcanvas'));
    const accountForm = $('#accountForm');
    let isEditMode = false;

    // Mở offcanvas để thêm mới
    $('#btnAddAccount').on('click', function() {
        resetForm();
        $('#accountOffcanvasLabel').text('Thêm tài khoản');
        $('#accountId').val('');
        accountOffcanvas.show();
    });

    // Mở offcanvas để chỉnh sửa
    $(document).on('click', '.btn-edit', function() {
        const accountId = $(this).data('account-id');
        loadAccountData(accountId);
    });

    // Load dữ liệu account để edit
    function loadAccountData(accountId) {
        $.ajax({
            url: window.accountEditUrl.replace(':id', accountId),
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            success: function(response) {
                if (response.status && response.data) {
                    const account = response.data;
                    $('#accountId').val(account.id);
                    $('#accountType').val(account.type || '');
                    $('#accountName').val(account.name);
                    $('#accountNote').val(account.note || '');
                    $('#accountPassword').val('');
                    $('#accountOffcanvasLabel').text('Chỉnh sửa tài khoản');
                    resetFormValidation();
                    accountOffcanvas.show();
                } else {
                    toastr.error('Không thể tải dữ liệu tài khoản', 'Lỗi');
                }
            },
            error: function(xhr) {
                toastr.error('Có lỗi xảy ra khi tải dữ liệu', 'Lỗi');
            }
        });
    }

    // Reset form
    function resetForm() {
        accountForm[0].reset();
        $('#accountId').val('');
        resetFormValidation();
    }

    // Reset validation
    function resetFormValidation() {
        accountForm.find('.is-invalid').removeClass('is-invalid');
        accountForm.find('.invalid-feedback').text('');
    }

    // Submit form
    accountForm.on('submit', function(e) {
        e.preventDefault();
        
        const accountId = $('#accountId').val();
        const isEdit = accountId !== '';
        const url = isEdit 
            ? window.accountUpdateUrl.replace(':id', accountId)
            : window.accountStoreUrl;
        const method = isEdit ? 'PUT' : 'POST';

        resetFormValidation();
        
        const $btn = $('#saveAccountBtn');
        const $spinner = $btn.find('.spinner-border');
        $btn.prop('disabled', true);
        $spinner.removeClass('d-none');

        const formData = {
            type: $('#accountType').val(),
            name: $('#accountName').val(),
            note: $('#accountNote').val(),
        };

        // Chỉ thêm password nếu có giá trị
        const password = $('#accountPassword').val();
        if (password) {
            formData.password = password;
        }

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        $.ajax({
            url: url,
            method: method,
            data: formData,
            success: function(response) {
                if (response.status) {
                    toastr.success(response.message || (isEdit ? 'Cập nhật thành công' : 'Thêm thành công'), 'Thông báo');
                    accountOffcanvas.hide();
                    resetForm();
                    if (typeof window.accountTable !== "undefined") {
                        window.accountTable.ajax.reload(null, false); // Reload data từ server, false = giữ nguyên trang hiện tại
                    }
                } else {
                    toastr.error(response.message || 'Có lỗi xảy ra', 'Lỗi');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    // Validation errors
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(function(field) {
                        const fieldName = field === 'password' ? 'accountPassword' : 'account' + field.charAt(0).toUpperCase() + field.slice(1);
                        const $field = $('#' + fieldName);
                        $field.addClass('is-invalid');
                        $field.siblings('.invalid-feedback').text(errors[field][0]);
                    });
                } else {
                    const message = xhr.responseJSON?.message || 'Có lỗi xảy ra';
                    toastr.error(message, 'Lỗi');
                }
            },
            complete: function() {
                $btn.prop('disabled', false);
                $spinner.addClass('d-none');
            }
        });
    });

    // Password toggle trong offcanvas
    // Dùng event delegation để hoạt động với offcanvas render động
    $(document).on('click', '#togglePassword', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const $input = $('#accountPassword');
        const $icon = $(this).find('i');
        
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $icon.removeClass('bx-hide').addClass('bx-show');
        } else {
            $input.attr('type', 'password');
            $icon.removeClass('bx-show').addClass('bx-hide');
        }
    });

    // Gọi lại Helpers.initPasswordToggle() khi offcanvas được mở (nếu có)
    $('#accountOffcanvas').on('shown.bs.offcanvas', function() {
        if (typeof window.Helpers !== 'undefined' && typeof window.Helpers.initPasswordToggle === 'function') {
            window.Helpers.initPasswordToggle();
        }
    });

    // Generate password button
    $('#generatePasswordBtn').on('click', function() {
        const $btn = $(this);
        const $passwordInput = $('#accountPassword');
        const originalHtml = $btn.html();
        
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        
        $.ajax({
            url: window.accountGeneratePasswordUrl,
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.status && response.password) {
                    $passwordInput.val(response.password);
                    $passwordInput.attr('type', 'text');
                    $('#togglePassword i').removeClass('bx-hide').addClass('bx-show');
                    toastr.success('Mật khẩu mạnh đã được tạo', 'Thông báo');
                } else {
                    toastr.error('Không thể tạo mật khẩu', 'Lỗi');
                }
            },
            error: function(xhr) {
                toastr.error('Có lỗi xảy ra khi tạo mật khẩu', 'Lỗi');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // Reset form khi offcanvas đóng
    $('#accountOffcanvas').on('hidden.bs.offcanvas', function() {
        resetForm();
    });

    // ======================================
    // 🗑️ XỬ LÝ XÓA VỚI BOOTSTRAP MODAL
    // ======================================
    let deleteUrl = null;
    let currentRow = null;

    // Khi click nút xóa
    $(document).on("click", ".btn-delete", function () {
        deleteUrl = $(this).data("url");
        const title = $(this).data("title");
        currentRow = $(this).closest("tr");

        $("#deleteTitle").text(title || "tài khoản này");
        const modal = new bootstrap.Modal($("#confirmDeleteModal"));
        modal.show();
    });

    // Khi nhấn nút "Xóa"
    $("#confirmDeleteBtn").on("click", function () {
        if (!deleteUrl) {
            toastr.error("Không tìm thấy URL xóa.", "Thông báo");
            return;
        }

        const btn = $(this);
        const spinner = btn.find(".spinner-border");

        btn.prop("disabled", true);
        spinner.removeClass("d-none");

        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        $.ajax({
            url: deleteUrl,
            type: "DELETE",
            success: function (res) {
                if (res.status) {
                    $("#confirmDeleteModal").modal("hide");
                    toastr.success(res.message || "Xóa thành công", "Thông báo");
                    if (typeof window.accountTable !== "undefined") {
                        window.accountTable.ajax.reload(null, false); // Reload data từ server, false = giữ nguyên trang hiện tại
                    }
                } else {
                    toastr.error(res.message || "Không thể xóa tài khoản", "Thông báo");
                }
            },
            error: function (xhr) {
                let message = "Lỗi khi xóa tài khoản";
                if (xhr.responseJSON) {
                    message = xhr.responseJSON.message || message;
                } else if (xhr.status === 404) {
                    message = "Tài khoản không tồn tại";
                } else if (xhr.status === 500) {
                    message = "Lỗi server. Vui lòng thử lại sau";
                }
                toastr.error(message, "Thông báo");
            },
            complete: function () {
                btn.prop("disabled", false);
                spinner.addClass("d-none");
            },
        });
    });

    // ======================================
    // 👁️ XỬ LÝ XEM MẬT KHẨU
    // ======================================
    const viewPasswordModal = new bootstrap.Modal(document.getElementById('viewPasswordModal'));
    let currentViewPasswordAccountId = null;

    // Mở modal xem password
    $(document).on('click', '.btn-view-password', function() {
        currentViewPasswordAccountId = $(this).data('account-id');
        $('#userPassword').val('');
        $('#passwordResult').hide();
        $('#viewPasswordForm')[0].reset();
        $('#userPassword').removeClass('is-invalid').prop('readonly', false);
        $('#verifyPasswordBtn').show();
        viewPasswordModal.show();
    });

    // Xác thực password và hiển thị password
    $('#verifyPasswordBtn').on('click', function() {
        const userPassword = $('#userPassword').val();
        if (!userPassword) {
            $('#userPassword').addClass('is-invalid');
            $('#userPassword').siblings('.invalid-feedback').text('Vui lòng nhập mật khẩu đăng nhập');
            return;
        }

        if (!currentViewPasswordAccountId) {
            toastr.error('Không tìm thấy ID tài khoản', 'Lỗi');
            return;
        }

        const $btn = $(this);
        const $spinner = $btn.find('.spinner-border');
        $btn.prop('disabled', true);
        $spinner.removeClass('d-none');

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        $.ajax({
            url: window.accountViewPasswordUrl.replace(':id', currentViewPasswordAccountId),
            method: 'POST',
            data: { user_password: userPassword },
            success: function(response) {
                if (response.status && response.password) {
                    $('#displayPassword').val(response.password);
                    $('#passwordResult').show();
                    $('#userPassword').prop('readonly', true);
                    $btn.hide();
                } else {
                    toastr.error(response.message || 'Không thể xem mật khẩu', 'Lỗi');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    if (errors.user_password) {
                        $('#userPassword').addClass('is-invalid');
                        $('#userPassword').siblings('.invalid-feedback').text(errors.user_password[0]);
                    }
                } else {
                    const message = xhr.responseJSON?.message || 'Mật khẩu đăng nhập không chính xác';
                    toastr.error(message, 'Lỗi');
                }
            },
            complete: function() {
                $btn.prop('disabled', false);
                $spinner.addClass('d-none');
            }
        });
    });

    // Copy password
    $('#copyPasswordBtn').on('click', function() {
        const passwordInput = document.getElementById('displayPassword');
        passwordInput.select();
        document.execCommand('copy');
        toastr.success('Đã sao chép mật khẩu', 'Thông báo');
    });

    // Reset modal khi đóng
    $('#viewPasswordModal').on('hidden.bs.modal', function() {
        $('#viewPasswordForm')[0].reset();
        $('#passwordResult').hide();
        $('#userPassword').removeClass('is-invalid').prop('readonly', false);
        $('#verifyPasswordBtn').show();
        currentViewPasswordAccountId = null;
    });
});
