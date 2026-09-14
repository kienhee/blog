/* Kienhee admin — category CRUD & form validation module */
$(function () {
  'use strict';

  if ($.validator && !$.validator.methods.pattern) {
    $.validator.addMethod('pattern', function (value, element, param) {
      if (this.optional(element)) return true;
      if (typeof param === 'string') param = new RegExp('^(?:' + param + ')$');
      return param.test(value);
    }, 'Invalid format.');
  }

  /* ---- Create Category button ---- */
  $('#btn-create-category').on('click', function () {
    $('#category-form-mode').val('create');
    $('#category-id').val('');
    $('#category-name').val('');
    $('#category-slug').val('').prop('readonly', false).css({ opacity: '', cursor: '' });
    $('#category-description').val('');
    $('#category-parentId').val('');
    $('#category-visible').prop('checked', true);
    $('#category-form').attr('action', '/admin/categories');
    $('#oc-kicker').text('Create');
    $('#oc-title').text('New Category');
    $('#category-submit-btn').text('Create category');

    if ($.fn.validate) {
      var validator = $('#category-form').validate();
      validator.resetForm();
      $('#category-form').find('.is-invalid, .error').removeClass('is-invalid error');
      $('#category-form').find('.has-error').removeClass('has-error');
      $('#category-form').find('span.err').removeClass('show').hide();
    }
  });

  /* ---- Edit Category button ---- */
  $(document).on('click', '.btn-edit-category', function () {
    var $btn = $(this);
    var id = $btn.data('id');
    var name = $btn.data('name') || '';
    var slug = $btn.data('slug') || '';
    var description = $btn.data('description') || '';
    var parentId = $btn.data('parent-id') || '';
    var visible = $btn.data('visible') === true || $btn.data('visible') === 'true';

    $('#category-form-mode').val('edit');
    $('#category-id').val(id);
    $('#category-name').val(name);
    $('#category-slug').val(slug).prop('readonly', true).css({ opacity: '0.75', cursor: 'not-allowed' });
    $('#category-description').val(description);
    $('#category-parentId').val(parentId).find('option[value="' + id + '"]').prop('disabled', true);
    $('#category-visible').prop('checked', visible);
    $('#category-form').attr('action', '/admin/categories/' + id + '/edit');
    $('#oc-kicker').text('Update');
    $('#oc-title').text('Edit Category');
    $('#category-submit-btn').text('Save changes');

    if ($.fn.validate) {
      var validator = $('#category-form').validate();
      validator.resetForm();
      $('#category-form').find('.is-invalid, .error').removeClass('is-invalid error');
      $('#category-form').find('.has-error').removeClass('has-error');
      $('#category-form').find('span.err').removeClass('show').hide();
    }

    $('.offcanvas').addClass('open');
  });

  /* ---- Delete Category button ---- */
  $(document).on('click', '.btn-del-category', function () {
    var id = $(this).data('id');
    var name = $(this).data('name') || '';
    $('#delete-category-form').attr('action', '/admin/categories/' + id + '/delete');
    if (window.khDialog) {
      window.khDialog(name ? 'category "' + name + '"' : 'this category', '#delete-category-form',
        'This cannot be undone. A category that still has subcategories cannot be deleted.');
    }
  });

  /* ---- jQuery validation for Category form ---- */
  if ($.fn.validate && $('#category-form').length) {
    $('#category-form').validate({
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
