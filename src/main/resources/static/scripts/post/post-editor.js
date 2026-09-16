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

  /* ---- Content: TinyMCE (self-hosted GPL build in scripts/lib/tinymce) ---- */
  // Replaces the old contenteditable toolbar. The editor writes back into #post-content
  // (name="content"), so the form post and jQuery Validate keep working unchanged.
  var TINY_BASE = window.KH_TINYMCE_BASE || '/scripts/lib/tinymce';

  function isLightTheme() {
    return document.documentElement.getAttribute('data-theme') === 'light';
  }

  function syncContent() {
    var ed = window.tinymce && window.tinymce.get('post-content');
    if (ed) ed.save();
  }

  function initTinymce() {
    if (!window.tinymce || !document.getElementById('post-content')) return;
    var light = isLightTheme();
    window.tinymce.init({
      selector: '#post-content',
      base_url: TINY_BASE,
      license_key: 'gpl',
      promotion: false,
      branding: false,
      menubar: 'edit insert format table',
      height: 620,
      plugins: 'advlist anchor autolink charmap code codesample fullscreen image link lists preview searchreplace table visualblocks wordcount',
      toolbar: 'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist | blockquote link image table codesample | removeformat fullscreen code',
      // Chrome is restyled by styles/tinymce-theme.css; the content area follows the public
      // article styles (styles/tinymce-content.css), with .kh-light for the light admin theme.
      skin: light ? 'oxide' : 'oxide-dark',
      content_css: '/styles/tinymce-content.css',
      body_class: light ? 'kh-light' : '',
      relative_urls: false,
      remove_script_host: true,
      convert_urls: true,
      image_caption: true,
      // "Choose from library" inside the image/media dialogs: the same MediaExplorer picker as the cover image.
      // No media/embed plugin: PublicViewHelper.safeHtml strips iframes, so embeds would silently vanish.
      file_picker_types: 'image',
      file_picker_callback: function (callback, value, meta) {
        openMediaPicker('image', function (file) {
          callback(file.url, { title: file.name, alt: file.name });
        });
      },
      setup: function (editor) {
        // Keep the textarea (and therefore jQuery Validate) in step with the editor.
        editor.on('change keyup undo redo SetContent', function () { editor.save(); });
      }
    });
  }

  initTinymce();

  // The admin theme toggle swaps the skin: re-create the editor with the content it holds.
  var themeObserver = new MutationObserver(function () {
    var ed = window.tinymce && window.tinymce.get('post-content');
    if (!ed) return;
    var html = ed.getContent();
    ed.remove();
    $('#post-content').val(html);
    initTinymce();
  });
  themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

  /* ---- Cover image: the shared MediaExplorer in pick mode ---- */
  // Same component as the Media library page (scripts/media/media-explorer.js), so the picker always
  // matches the library. Mounted the first time the modal opens, reloaded on every later open so it
  // reflects uploads and changes made elsewhere.
  var $picker = $('#media-picker');
  var pickerExplorer = null;
  var pickerAccept = 'image';
  var pickerHandler = null;

  function closeMediaPicker() {
    $picker.removeClass('open');
    pickerHandler = null;
  }

  // accept: 'image' (or null for any file), onPick: receives the chosen file.
  function openMediaPicker(accept, onPick) {
    if (!window.MediaExplorer) return;
    pickerHandler = onPick;
    if (!pickerExplorer || pickerAccept !== accept) {
      if (pickerExplorer && pickerExplorer.destroy) pickerExplorer.destroy();
      pickerAccept = accept;
      pickerExplorer = window.MediaExplorer.mount(document.getElementById('media-picker-explorer'), {
        mode: 'pick',
        accept: accept || undefined,
        multiple: false,
        onSelect: function (files) {
          var handler = pickerHandler;
          closeMediaPicker();
          if (files.length && handler) handler(files[0]);
        },
        onCancel: closeMediaPicker
      });
    } else {
      pickerExplorer.reload();
    }
    $picker.addClass('open');
  }

  $('#btn-open-media-picker').on('click', function () {
    openMediaPicker('image', function (file) {
      $('#post-coverImage').val(file.url).trigger('input');
    });
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
    // Runs before jQuery Validate's own submit handler, so the "content" rule sees the editor's HTML.
    $('#post-form').on('submit', syncContent);
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

/* Scheduling: the "Publish on" picker follows the status select (rules also enforced in PostController). */
$(function () {
  'use strict';

  var $status = $('#post-status');
  var $box = $('#post-schedule');
  var $input = $('#post-scheduledAt');
  if (!$status.length || !$box.length || !$input.length) return;

  if (window.flatpickr && !$input.prop('disabled')) {
    window.flatpickr($input[0], {
      enableTime: true,
      time_24hr: true,
      dateFormat: 'Y-m-d H:i',
      minDate: 'today',
      minuteIncrement: 5,
      allowInput: true
    });
  }

  function sync() {
    $box.prop('hidden', $status.val() !== 'SCHEDULED');
  }
  $status.on('change', sync);
  sync();

  var validator = $('#post-form').data('validator');
  if (validator && !$input.prop('disabled')) {
    $input.rules('add', {
      required: function () { return $status.val() === 'SCHEDULED'; },
      messages: { required: 'Choose when the post should go live.' }
    });
  }
});
