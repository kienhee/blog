/* Kienhee admin — post editor: rich text sync, hashtag picker, validation */
$(function () {
  'use strict';

  if ($.validator && !$.validator.methods.pattern) {
    $.validator.addMethod('pattern', function (value, element, param) {
      if (this.optional(element)) return true;
      if (typeof param === 'string') param = new RegExp('^(?:' + param + ')$');
      return param.test(value);
    }, 'Invalid format.');
  }

  var $area = $('#post-content-area');
  var $contentField = $('#post-content');

  function updateWordCount() {
    var text = $.trim($area.text());
    var words = text.length ? text.split(/\s+/).length : 0;
    $('#post-word-count').text(words + (words === 1 ? ' word' : ' words'));
  }

  function syncContent() {
    if (!$area.length || !$contentField.length) return;
    $contentField.val($area.html());
  }

  if ($area.length) {
    $area.on('input blur', function () {
      syncContent();
      updateWordCount();
    });
    updateWordCount();
  }

  /* ---- Cover image: the shared MediaExplorer in pick mode ---- */
  // Same component as the Media library page (scripts/media/media-explorer.js), so the picker always
  // matches the library. Mounted the first time the modal opens, reloaded on every later open so it
  // reflects uploads and changes made elsewhere.
  var $picker = $('#media-picker');
  var pickerExplorer = null;

  function closeMediaPicker() {
    $picker.removeClass('open');
  }

  $('#btn-open-media-picker').on('click', function () {
    if (!window.MediaExplorer) return;
    if (!pickerExplorer) {
      pickerExplorer = window.MediaExplorer.mount(document.getElementById('media-picker-explorer'), {
        mode: 'pick',
        accept: 'image',
        multiple: false,
        onSelect: function (files) {
          if (files.length) $('#post-coverImage').val(files[0].url).trigger('input');
          closeMediaPicker();
        },
        onCancel: closeMediaPicker
      });
    } else {
      pickerExplorer.reload();
    }
    $picker.addClass('open');
  });

  $('#btn-close-media-picker').on('click', closeMediaPicker);

  $picker.on('mousedown', function (e) {
    if (e.target === this) closeMediaPicker();
  });

  $(document).on('keydown', function (e) {
    // Escape closes the picker, unless it is closing something inside it first (menu, new-folder modal).
    if (e.key === 'Escape' && $picker.hasClass('open')
        && !$picker.find('.context-menu.open, .rename-popover.open, .folder-modal.open').length) {
      closeMediaPicker();
    }
  });

  $('#post-coverImage').on('input', function () {
    var url = $.trim($(this).val());
    var $preview = $('#post-coverImage-preview');
    if (url) {
      $preview.attr('src', url).prop('hidden', false);
    } else {
      $preview.prop('hidden', true);
    }
  });

  /* ---- jQuery validation for Post form ---- */
  if ($.fn.validate && $('#post-form').length) {
    $('#post-form').validate({
      ignore: [],
      rules: {
        title: {
          required: true,
          minlength: 3
        },
        slug: {
          required: true,
          minlength: 3,
          pattern: /^[a-z0-9]+(-[a-z0-9]+)*$/
        },
        content: {
          required: true
        },
        categoryId: {
          required: true
        },
        status: {
          required: true
        }
      },
      messages: {
        title: {
          required: 'Title is required.',
          minlength: 'Title must have at least 3 characters.'
        },
        slug: {
          required: 'Slug is required.',
          minlength: 'Slug must have at least 3 characters.',
          pattern: 'Slug may only contain lowercase letters, numbers and hyphens.'
        },
        content: {
          required: 'Content is required.'
        },
        categoryId: {
          required: 'Category is required.'
        },
        status: {
          required: 'Status is required.'
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
        if (!$field.length) {
          $field = $(element).closest('.meta-block');
        }
        $field.find('span.err:not([id])').remove();
        error.appendTo($field);
      },
      submitHandler: function (form) {
        syncContent();

        var $form = $(form);
        $form.find('input[name="hashtagIds"]').remove();
        $form.find('#post-hashtag-picker .tag-toggle[aria-pressed="true"]').each(function () {
          var id = $(this).data('id');
          $('<input>').attr({ type: 'hidden', name: 'hashtagIds', value: id }).appendTo($form);
        });

        form.submit();
      }
    });
  }
});
