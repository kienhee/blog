(function ($) {
    "use strict";
    function openFileManager(url) {
        let w = window.screen.width;
        let h = window.screen.height;
        window.open(
            url,
            "FileManager",
            `width=${w},height=${h},resizable=yes,scrollbars=yes`
        );
    }

    $.fn.filemanager = function (type, options) {
        type = type || "file";

        return this.off("click.fm").on("click.fm", function (e) {
            e.preventDefault();

            let route_prefix =
                options && options.prefix
                    ? options.prefix.replace(/\/$/, "")
                    : "/filemanager";
            let $input = $("#images");
            let $preview = $("#upload_box");

            openFileManager(`${route_prefix}?type=${encodeURIComponent(type)}`);

            window.SetUrl = function (items) {
                if (!items || !items.length) return;

                // reset danh sách cũ
                let urls = [];

                // thêm ảnh mới (tối đa 10)
                items.forEach((item, i) => {
                    if (i < 10) {
                        urls.push(item.url);
                    }
                });

                // lưu input (CSV)
                $input.val(urls.join(",")).trigger("input").trigger("change");

                // render preview
                renderPreview($preview, $input, urls, route_prefix);
            };

            return false;
        });
    };

    // Hàm render preview + gắn nút xóa
    function renderPreview($preview, $input, urls, route_prefix) {
        $preview.empty().addClass("preview-flex").css("cursor", "pointer");

        urls.forEach((url, index) => {
            let $imgWrapper = $("<div>", { class: "preview-item" });

            let $img = $("<img>", { src: url, alt: "images preview" });

            let $removeBtn = $("<button>", {
                type: "button",
                class: "btn btn-sm btn-danger position-absolute top-0 end-0 m-1",
                html: "&times;",
            }).on("click", function (e) {
                e.stopPropagation(); // tránh click mở filemanager
                urls.splice(index, 1);
                $input.val(urls.join(",")).trigger("change");
                $imgWrapper.remove();
            });

            $imgWrapper.append($img).append($removeBtn);
            $preview.append($imgWrapper);
            // Gọi lại để kích hoạt drag-drop
            initSortable();
        });

        // click vào preview mở lại filemanager
        $preview.off("click").on("click", function () {
            openFileManager(`${route_prefix}?type=image`);
            window.SetUrl = function (items) {
                if (!items || !items.length) return;
                let urls = [];
                items.forEach((item, i) => {
                    if (i < 10) urls.push(item.url);
                });
                $input.val(urls.join(",")).trigger("change");
                renderPreview($preview, $input, urls, route_prefix);
            };
        });
    }

    $(function () {
        // gắn cho nút ban đầu
        $(".upload_btn").filemanager("image", { prefix: "/filemanager" });

        let $input = $("#images");
        let $preview = $("#upload_box");

        // render nếu có dữ liệu cũ
        if ($input.length && $input.val()) {
            let urls = $input.val().split(",");
            renderPreview($preview, $input, urls, "/filemanager");
        }
    });

    // Khởi tạo sortable sau khi render preview
    function initSortable() {
        let el = document.getElementById("upload_box");
        Sortable.create(el, {
            animation: 150,
            handle: "img", // chỉ kéo bằng ảnh
            onEnd: function (evt) {
                // Cập nhật lại input hidden theo thứ tự mới
                let urls = [];
                $("#upload_box .preview-item img").each(function () {
                    urls.push($(this).attr("src"));
                });
                $("#images").val(urls.join(",")).trigger("change");
            },
        });
    }
})(jQuery);


