/* Kienhee admin — profile & password management module */
$(function () {
  'use strict';

  /* ---- Replace photo (avatar upload) ---- */
  $('#btn-replace-photo').on('click', function () {
    $('#avatar-file-input').trigger('click');
  });

  $('#avatar-file-input').on('change', function () {
    if (this.files && this.files.length) {
      $('#avatar-form').trigger('submit');
    }
  });

  /* ---- Profile tab switching ---- */
  $('[data-tab]').on('click', function () {
    var $t = $(this);
    var name = $t.attr('data-tab');
    $t.parent().find('[data-tab]').each(function () {
      $(this).attr('aria-pressed', String(this === $t[0]));
    });
    $('[data-panel]').each(function () {
      var $p = $(this);
      $p.toggle($p.attr('data-panel') === name);
    });
  });

  /* ---- jQuery validation for Profile Info form ---- */
  if ($.fn.validate && $('#profile-info-form').length) {
    $('#profile-info-form').validate({
      rules: {
        fullName: {
          required: true,
          minlength: 2
        }
      },
      messages: {
        fullName: {
          required: 'Display name is required.',
          minlength: 'Display name must have at least 2 characters.'
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

  /* ---- jQuery validation for Change Password form ---- */
  if ($.fn.validate && $('#profile-password-form').length) {
    $('#profile-password-form').validate({
      rules: {
        currentPassword: {
          required: true
        },
        newPassword: {
          required: true,
          minlength: 6
        },
        confirmPassword: {
          required: true,
          equalTo: '#pw-next'
        }
      },
      messages: {
        currentPassword: {
          required: 'Current password is required.'
        },
        newPassword: {
          required: 'New password is required.',
          minlength: 'Use at least 6 characters.'
        },
        confirmPassword: {
          required: 'Confirm password is required.',
          equalTo: 'Passwords do not match.'
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

