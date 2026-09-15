/* Kienhee admin — hashtag CRUD & form validation module */
$(function () {
  'use strict';

  if ($.validator && !$.validator.methods.pattern) {
    $.validator.addMethod('pattern', function (value, element, param) {
      if (this.optional(element)) return true;
      if (typeof param === 'string') param = new RegExp('^(?:' + param + ')$');
      return param.test(value);
    }, 'Invalid format.');
  }

  /* ---- Create Hashtag button ---- */
  $('#btn-create-hashtag').on('click', function () {
    $('#hashtag-form-mode').val('create');
    $('#hashtag-id').val('');
    $('#hashtag-name').val('');
    $('#hashtag-slug').val('').prop('readonly', false).css({ opacity: '', cursor: '' });
    $('#hashtag-description').val('');
    $('#hashtag-active').prop('checked', true);
    $('#hashtag-form').attr('action', '/admin/hashtags');
    $('#oc-kicker').text('Create');
    $('#oc-title').text('New Hashtag');
    $('#hashtag-submit-btn').text('Create hashtag');

    if ($.fn.validate) {
      var validator = $('#hashtag-form').validate();
      validator.resetForm();
      $('#hashtag-form').find('.is-invalid, .error').removeClass('is-invalid error');
      $('#hashtag-form').find('.has-error').removeClass('has-error');
      $('#hashtag-form').find('span.err').removeClass('show').hide();
    }
  });

  /* ---- Edit Hashtag button ---- */
  $(document).on('click', '.btn-edit-hashtag', function () {
    var $btn = $(this);
    var id = $btn.data('id');
    var name = $btn.data('name') || '';
    var slug = $btn.data('slug') || '';
    var description = $btn.data('description') || '';
    var active = $btn.data('active') === true || $btn.data('active') === 'true';

    $('#hashtag-form-mode').val('edit');
    $('#hashtag-id').val(id);
    $('#hashtag-name').val(name);
    $('#hashtag-slug').val(slug).prop('readonly', true).css({ opacity: '0.75', cursor: 'not-allowed' });
    $('#hashtag-description').val(description);
    $('#hashtag-active').prop('checked', active);
    $('#hashtag-form').attr('action', '/admin/hashtags/' + id + '/edit');
    $('#oc-kicker').text('Update');
    $('#oc-title').text('Edit Hashtag');
    $('#hashtag-submit-btn').text('Save changes');

    if ($.fn.validate) {
      var validator = $('#hashtag-form').validate();
      validator.resetForm();
      $('#hashtag-form').find('.is-invalid, .error').removeClass('is-invalid error');
      $('#hashtag-form').find('.has-error').removeClass('has-error');
      $('#hashtag-form').find('span.err').removeClass('show').hide();
    }

    $('.offcanvas').addClass('open');
  });

  /* ---- Delete Hashtag button ---- */
  $(document).on('click', '.btn-del-hashtag', function () {
    var id = $(this).data('id');
    var name = $(this).data('name') || '';
    $('#delete-hashtag-form').attr('action', '/admin/hashtags/' + id + '/delete');
    if (window.khDialog) {
      window.khDialog(name ? 'hashtag "' + name + '"' : 'this hashtag', '#delete-hashtag-form',
        'It moves to the trash, where you can restore it.');
    }
  });

  /* ---- jQuery validation for Hashtag form ---- */
  if ($.fn.validate && $('#hashtag-form').length) {
    $('#hashtag-form').validate({
      rules: {
        name: {
          required: true,
          minlength: 2
        },
        slug: {
          required: true,
          minlength: 2,
          pattern: /^[a-z0-9]+(-[a-z0-9]+)*$/
        }
      },
      messages: {
        name: {
          required: 'Name is required.',
          minlength: 'Name must have at least 2 characters.'
        },
        slug: {
          required: 'Slug is required.',
          minlength: 'Slug must have at least 2 characters.',
          pattern: 'Slug may only contain lowercase letters, numbers and hyphens.'
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
