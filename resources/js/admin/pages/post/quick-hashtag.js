"use strict";

$(function () {
    // ======================================
    // #️⃣ QUICK ADD HASHTAG
    // ======================================
    $("#saveHashtag").on("click", function () {
        const form = $("#addHashtagForm");
        const name = $("#hashtag_name").val();

        if (!name) {
            toastr.error("Vui lòng nhập tên hashtag", "Thông báo");
            return;
        }

        $.ajax({
            url: window.hashtagQuickStoreUrl,
            type: "POST",
            data: {
                name: name,
                _token: $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                if (response.status) {
                    // Thêm hashtag mới vào select2
                    const newOption = new Option(
                        response.data.name,
                        response.data.id,
                        true,
                        true
                    );
                    $("#hashtag").append(newOption).trigger("change");

                    // Reset form và đóng modal
                    form[0].reset();
                    $("#addHashtagModal").modal("hide");
                    toastr.success("Thêm hashtag thành công", "Thông báo");
                }
            },
            error: function (xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    Object.keys(errors).forEach((key) => {
                        toastr.error(errors[key][0], "Thông báo");
                    });
                } else {
                    const message =
                        xhr.responseJSON?.message ||
                        "Đã có lỗi xảy ra. Vui lòng thử lại!";
                    toastr.error(message, "Thông báo");
                }
            },
        });
    });

    // ======================================
    // 🔍 HASHTAG SELECT2 WITH AJAX
    // ======================================
    const $hashtagSelect = $("#hashtag");

    if ($hashtagSelect.length > 0) {
        // Format hiển thị hashtag
        function formatHashtag(hashtag) {
            if (hashtag.loading) {
                return hashtag.text;
            }
            return $(
                '<span><i class="bx bx-hash"></i> ' + hashtag.text + "</span>"
            );
        }

        $hashtagSelect.select2({
            ajax: {
                url: window.hashtagSearchUrl,
                dataType: "json",
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term,
                        page: params.page,
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.data.map((item) => ({
                            id: item.id,
                            text: item.name,
                        })),
                        pagination: {
                            more: params.page * 20 < data.total,
                        },
                    };
                },
                cache: true,
            },
            placeholder: "---Chọn hashtag---",
            minimumInputLength: 0,
            templateResult: formatHashtag,
            templateSelection: formatHashtag,
        });
    }
});
