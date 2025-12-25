/**
 * Thêm ID #inputSlug để lấy giá trị từ trường nhập slug
 * và tự động tạo slug từ tiêu đề bài viết.
 */

$("#inputSlug").on('input', function() {
    let input = $(this).val();
    // Chuyển sang chữ thường
    let slug = input.toLowerCase();

    // Loại bỏ dấu tiếng Việt
    slug = slug.normalize('NFD').replace(/[\u0300-\u036f]/g, '');

    // Thay thế các ký tự không phải a-z, 0-9 bằng dấu gạch ngang
    slug = slug.replace(/[^a-z0-9]+/g, '-');

    // Loại bỏ các dấu gạch ngang ở đầu và cuối
    slug = slug.replace(/^-+|-+$/g, '');

    // Loại bỏ các dấu gạch ngang liên tiếp
    slug = slug.replace(/-+/g, '-');

    $(`#outputSlug`).val(slug);
});


