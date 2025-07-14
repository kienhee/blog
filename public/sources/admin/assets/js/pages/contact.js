$(document).ready(function () {
    let url = $("#contactTable").data('url-get-data');
    let table = $('#contactTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: url,
            data: function (d) {
                d.is_confirmed = $('#filterStatus').val(); // gửi filter lên server
            }
        },
        columns: [
            {data: 'name'},
            {data: 'email'},
            {data: 'subject'},
            {data: 'message'},
            {
                data: 'created_at',
                render: function (data) {
                    return moment(data).format('DD/MM/YYYY HH:mm');
                }
            },
            {
                data: 'is_confirmed',
                render: function (data) {
                    if (data == 1) {
                        return '<span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Đã trả lời</span>';

                    } else {
                        return '<span class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i>Chưa trả lời</span>';

                    }
                }
            },
            {
                data: 'id',
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    let disabled = row.is_confirmed == 1 ? 'disabled' : '';
                    return `<button class="btn btn-sm btn-success confirm-btn w-100" data-id="${data}" ${disabled}>Xác nhận</button>`;
                }
            }
        ],
        language: {
            url: "/sources/admin/assets/lang/vi-datatables.json"
        }
    });

    // Trigger reload khi chọn lọc
    $('#filterStatus').on('change', function () {
        table.ajax.reload();
    });


    // ------------------------Handle confirm ------------------
    let selectedId = null;

    $(document).on('click', '.confirm-btn', function () {
        selectedId = $(this).data('id');
        $('#confirmModal').modal('show');
    });
    let url_confirm = $("#contactTable").data('url-confirm');
    $('#confirmActionBtn').on('click', function () {
        // Gửi AJAX xử lý xác nhận
        $.ajax({
            url: `${url_confirm}/${selectedId}`,
            type: 'POST',
            success: function (response) {
                $('#confirmModal').modal('hide');
                $('#contactTable').DataTable().ajax.reload();
            },
            error: function (xhr) {
                alert('Có lỗi xảy ra!');
            }
        });
    });
});
