(function ($) {
    "use strict";

    // Đợi upload-image-alone.js load xong trước
    $(function () {
        // Chỉ chạy nếu có avatar input (user form hoặc profile)
        if ($("#avatar").length) {
            // Lưu SetUrl gốc từ upload-image-alone.js
            let originalSetUrl = null;

            // Lưu tham chiếu đến nút upload
            const $uploadBtn = $("button.upload_btn");

            // Hook vào sự kiện click để intercept SetUrl
            $uploadBtn.on("click", function () {
                // Đợi upload-image-alone.js setup xong SetUrl
                setTimeout(function () {
                    originalSetUrl = window.SetUrl;

                    // Override SetUrl để xử lý đúng cho avatar
                    window.SetUrl = function (items) {
                        if (!items || !items.length) return;

                        const item = items[0];
                        const fileUrl = item.url || "";

                        // CẬP NHẬT INPUT #avatar (quan trọng!)
                        const $avatarInput = $("#avatar");
                        if ($avatarInput.length) {
                            $avatarInput
                                .val(fileUrl)
                                .trigger("input")
                                .trigger("change");
                        }

                        // Cập nhật preview
                        const $preview = $("#upload_box");
                        if ($preview.length) {
                            $preview.empty();
                            const $img = $("<img>", {
                                src: fileUrl,
                                alt: "Avatar preview",
                            }).css({
                                width: "100%",
                                height: "100%",
                                objectFit: "cover",
                            });
                            $preview.append($img);
                        }

                        // KHÔNG ẩn nút upload button
                        // (không gọi .hide() như upload-image-alone.js)
                    };
                }, 50);
            });

            // MutationObserver để đảm bảo nút không bị ẩn (backup plan)
            if ($uploadBtn.length) {
                const observer = new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        if (
                            mutation.type === "attributes" &&
                            mutation.attributeName === "style"
                        ) {
                            const $target = $(mutation.target);
                            if (
                                $target.is(":hidden") ||
                                $target.css("display") === "none"
                            ) {
                                $target.show();
                            }
                        }
                    });
                });

                $uploadBtn.each(function () {
                    observer.observe(this, {
                        attributes: true,
                        attributeFilter: ["style"],
                    });
                });
            }

            // Hiển thị preview nếu đã có ảnh sẵn
            const $avatarInput = $("#avatar");
            const $preview = $("#upload_box");
            if ($avatarInput.length && $avatarInput.val() && $preview.length) {
                const url = $avatarInput.val();
                $preview.empty();
                const $img = $("<img>", {
                    src: url,
                    alt: "Avatar preview",
                }).css({
                    width: "100%",
                    height: "100%",
                    objectFit: "cover",
                });
                $preview.append($img);
            }
        }
    });
})(jQuery);


