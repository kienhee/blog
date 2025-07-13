$(function () {
    const $form = $('#contact_form');
    const $successMsgClass = '.contact-success-message';

    $form.validate({
        rules: {
            name: { required: true, minlength: 2 },
            email: { required: true, email: true },
            subject: { required: true, minlength: 2 },
            message: { required: true, minlength: 5 }
        },
        messages: {
            name: 'Vui lòng nhập tên.',
            email: {
                required: 'Vui lòng nhập email.',
                email: 'Email không hợp lệ.'
            },
            subject: 'Vui lòng nhập tiêu đề.',
            message: 'Vui lòng nhập nội dung liên hệ.'
        },
        errorElement: 'div',
        errorClass: 'invalid-feedback',
        highlight(element) {
            $(element).addClass('is-invalid');
        },
        unhighlight(element) {
            $(element).removeClass('is-invalid');
        },
        errorPlacement(error, element) {
            element.parent('.input-group').length
                ? error.insertAfter(element.parent())
                : error.insertAfter(element);
        },
        submitHandler(form) {
            const $btn = $form.find('button[type=submit]');
            $btn.prop('disabled', true);

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: { 'X-CSRF-TOKEN': $('input[name="_token"]').val() },
                success(res) {
                    $form[0].reset();
                    $($successMsgClass).remove();

                    const msg = res?.message || 'Cảm ơn bạn đã liên hệ!';
                    const $msg = $(`<div class="contact-success-message text-success mt-2" style="font-size: 1rem;">${msg}</div>`);
                    $form.find('textarea[name="message"]').after($msg);
                },
                error(xhr) {
                    // Có thể xử lý lỗi chi tiết ở đây nếu muốn
                },
                complete() {
                    $btn.prop('disabled', false);
                }
            });

            return false;
        }
    });

    // Ẩn thông báo khi có thay đổi trong form
    $form.on('input change', 'input, textarea, select', function () {
        $($successMsgClass).fadeOut(200, function () { $(this).remove(); });
    });
});
