/* Kienhee admin — user CRUD & form validation module */
$(function () {
  'use strict';

  /* ---- Create User button ---- */
  $('#btn-create-user').on('click', function () {
    $('#user-form-mode').val('create');
    $('#user-id').val('');
    $('#user-fullName').val('');
    $('#user-email').val('').prop('readonly', false).css({ opacity: '', cursor: '' });
    $('#user-password').val('').attr('placeholder', khT('js.user.password_placeholder'));
    $('#user-pw-req').show();
    $('#user-pw-hint').text(khT('js.user.password_hint'));
    $('#user-phone').val('');
    $('#user-address').val('');
    $('#user-bio').val('');
    $('#user-roleId').val('');
    $('#user-form').attr('action', '/admin/users');
    $('#oc-kicker').text(khT('js.form.create'));
    $('#oc-title').text(khT('js.user.new'));
    $('#user-submit-btn').text(khT('js.user.submit_create'));

    if ($.fn.validate) {
      var validator = $('#user-form').validate();
      validator.resetForm();
      $('#user-form').find('.is-invalid, .error').removeClass('is-invalid error');
      $('#user-form').find('.has-error').removeClass('has-error');
      $('#user-form').find('span.err').removeClass('show').hide();
    }
  });

  /* ---- Edit User button ---- */
  $(document).on('click', '.btn-edit-user', function () {
    var $btn = $(this);
    var id = $btn.data('id');
    var name = $btn.data('name') || '';
    var email = $btn.data('email') || '';
    var phone = $btn.data('phone') || '';
    var address = $btn.data('address') || '';
    var bio = $btn.data('bio') || '';
    var roleId = $btn.data('role');

    $('#user-form-mode').val('edit');
    $('#user-id').val(id);
    $('#user-fullName').val(name);
    $('#user-email').val(email).prop('readonly', true).css({ opacity: '0.75', cursor: 'not-allowed' });
    $('#user-password').val('').attr('placeholder', khT('js.user.password_keep'));
    $('#user-pw-req').hide();
    $('#user-pw-hint').text(khT('js.user.password_keep_hint'));
    $('#user-phone').val(phone);
    $('#user-address').val(address);
    $('#user-bio').val(bio);
    $('#user-roleId').val(roleId ? String(roleId) : '');
    $('#user-form').attr('action', '/admin/users/' + id + '/edit');
    $('#oc-kicker').text(khT('js.form.update'));
    $('#oc-title').text(khT('js.user.edit'));
    $('#user-submit-btn').text(khT('js.form.save_changes'));

    if ($.fn.validate) {
      var validator = $('#user-form').validate();
      validator.resetForm();
      $('#user-form').find('.is-invalid, .error').removeClass('is-invalid error');
      $('#user-form').find('.has-error').removeClass('has-error');
      $('#user-form').find('span.err').removeClass('show').hide();
    }

    $('.offcanvas').addClass('open');
  });

  /* ---- Delete User button ---- */
  $(document).on('click', '.btn-del-user', function () {
    var id = $(this).data('id');
    var name = $(this).data('name') || '';
    $('#delete-user-form').attr('action', '/admin/users/' + id + '/delete');
    if (window.khDialog) {
      window.khDialog(name ? khT('js.dialog.named.user', name) : khT('js.dialog.this.user'), '#delete-user-form',
        khT('js.user.delete_note'));
    }
  });

  /* ---- jQuery validation for User form ---- */
  if ($.fn.validate && $('#user-form').length) {
    $('#user-form').validate({
      rules: {
        fullName: {
          required: true,
          minlength: 2
        },
        email: {
          required: true,
          email: true
        },
        password: {
          required: function () {
            return $('#user-form-mode').val() === 'create';
          },
          minlength: 6
        }
      },
      messages: {
        fullName: {
          required: khT('validation.full_name.required'),
          minlength: khT('validation.full_name.min')
        },
        email: {
          required: khT('validation.email.required'),
          email: khT('validation.email.invalid')
        },
        password: {
          required: khT('validation.password.required'),
          minlength: khT('validation.password.min6')
        }
      },
      errorElement: 'span',
      errorClass: 'err show',
      highlight: function (element) {
        var $el = $(element);
        $el.addClass('error');
        $el.closest('.field').addClass('has-error');
      },
      unhighlight: function (element) {
        var $el = $(element);
        $el.removeClass('error');
        $el.closest('.field').removeClass('has-error');
      },
      errorPlacement: function (error, element) {
        var $field = $(element).closest('.field');
        $field.find('span.err:not([id])').remove();
        error.appendTo($field);
      }
    });
  }
});

