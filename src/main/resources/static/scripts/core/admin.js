/* Kienhee admin — core layout & shared UI components (offcanvas, dialog, toast, theme) */
$(function () {
  'use strict';

  /* ---- toast notifications ---- */
  function toast(msg) {
    var $t = $('.toast');
    if (!$t.length) return;
    $t.find('.msg').text(msg);
    $t.addClass('show');
    clearTimeout($t.data('toastTimer'));
    var timer = setTimeout(function () {
      $t.removeClass('show');
    }, 2200);
    $t.data('toastTimer', timer);
  }
  window.khToast = toast;

  $(document).on('click', '[data-toast]', function () {
    toast($(this).attr('data-toast'));
  });

  /* ---- sidebar drawer & scrim ---- */
  $('[data-side-toggle]').on('click', function () {
    var open = $('body').toggleClass('side-open').hasClass('side-open');
    $(this).attr('aria-expanded', String(open));
  });

  $('.scrim').on('click', function () {
    $('body').removeClass('side-open');
  });

  /* ---- theme toggle ---- */
  var savedTheme = null;
  try {
    savedTheme = localStorage.getItem('kienhee-theme');
  } catch (e) {}
  if (savedTheme) {
    $('html').attr('data-theme', savedTheme);
  }

  $('[data-theme-toggle]').on('click', function () {
    var isLight = $('html').attr('data-theme') === 'light';
    var next = isLight ? 'dark' : 'light';
    $('html').attr('data-theme', next);
    try {
      localStorage.setItem('kienhee-theme', next);
    } catch (e) {}
  });

  /* ---- helper: clear validation errors ---- */
  function clearErrors($scope) {
    if (!$scope || !$scope.length) return;
    $scope.find('.is-invalid, .error').removeClass('is-invalid error');
    $scope.find('.has-error').removeClass('has-error');
    $scope.find('.err.show').removeClass('show').hide();
  }
  window.khClearErrors = clearErrors;

  /* ---- offcanvas drawer component ---- */
  $(document).on('click', '[data-oc-open]', function () {
    $('.offcanvas').addClass('open');
  });

  $(document).on('click', '[data-oc-close]', function () {
    var $oc = $('.offcanvas').removeClass('open');
    clearErrors($oc);
  });

  $(document).on('click', '.offcanvas', function (e) {
    if ($(e.target).is('.offcanvas')) {
      var $oc = $(this).removeClass('open');
      clearErrors($oc);
    }
  });

  /* ---- delete confirmation dialog ---- */
  function defaultDialogDesc() { return khT('js.dialog.irreversible'); }

  /**
   * @param what         what is being deleted, e.g. 'post "Hello"'
   * @param formSelector form to submit, or a callback to run, once the user confirms
   * @param description  what this delete actually does; defaults to js.dialog.irreversible
   */
  function openDialog(what, formSelector, description) {
    var $dlg = $('.dialog');
    if (!$dlg.length) return;
    $dlg.find('.t').text(khT('js.dialog.title', what || khT('js.dialog.this_record')));
    $dlg.find('[data-dialog-desc]').text(description || defaultDialogDesc());
    // Either a form selector to submit or a callback to run once the user confirms.
    $dlg.data('targetForm', formSelector || null);
    $dlg.addClass('open');
  }
  window.khDialog = openDialog;

  $(document).on('click', '[data-del]', function () {
    openDialog($(this).attr('data-del') || khT('js.dialog.this_record'));
  });

  $('.dialog').on('click', '[data-dialog-close]', function () {
    $('.dialog').removeClass('open');
  });

  $('.dialog').on('click', '[data-dialog-confirm]', function () {
    var $dlg = $('.dialog');
    var formSelector = $dlg.data('targetForm');
    $dlg.removeClass('open');
    // A function target lets a page run its own action (e.g. an AJAX delete) on confirm.
    if (typeof formSelector === 'function') {
      formSelector();
      return;
    }
    if (formSelector) {
      // Callers pass the exact form to submit, so submit it. The old "action must contain
      // /delete" check silently skipped forms like ".../bulk-delete" and only showed a toast.
      var $form = $(formSelector);
      if ($form.length && $form.attr('action')) {
        $form.submit();
        return;
      }
    }
    // No target form: the static demo pages (data-del buttons) have nothing to submit.
    toast(khT('js.toast.deleted'));
  });

  /* ---- keyboard shortcuts ---- */
  $(document).on('keydown', function (e) {
    if (e.key !== 'Escape') return;
    $('body').removeClass('side-open');
    $('.offcanvas').removeClass('open');
    $('.dialog').removeClass('open');
  });

  /* ---- generic data-validate helper ---- */
  $('[data-validate]').on('click', function (e) {
    var $btn = $(this);
    var selector = $btn.attr('data-validate');
    var $scope = $(selector);
    if (!$scope.length) return;

    clearErrors($scope);
    var bad = 0;

    $scope.find('[data-required]').each(function () {
      var $f = $(this);
      var val = $.trim($f.val() || '');
      var ok = $f.is(':checkbox') ? $f.is(':checked') : val.length > 0;

      if ($f.attr('data-min')) {
        ok = ok && val.length >= parseInt($f.attr('data-min'), 10);
      }
      if ($f.attr('data-match')) {
        var otherVal = $.trim($scope.find($f.attr('data-match')).val() || '');
        ok = ok && val === otherVal;
      }

      if (!ok) {
        bad++;
        $f.addClass('is-invalid');
        var $msg = $f.closest('.field').find('.err');
        if ($msg.length) $msg.addClass('show');
      }
    });

    if (bad === 0) {
      var done = $btn.attr('data-success') || 'Saved';
      $('.offcanvas').removeClass('open');
      var go = $btn.attr('data-goto');
      toast(done);
      if (go) setTimeout(function () { window.location.href = go; }, 600);
    } else {
      e.preventDefault();
    }
  });

  /* ---- slug auto-fill ---- */
  // Titles are usually Vietnamese, so accents are transliterated before the slug is built
  // ("Bai viet so 1" from "Bài viết số 1"); a naive [^a-z0-9] strip turns those letters into
  // separators and leaves "b-i-vi-t-s". Slugs stay ASCII on purpose: they go straight into URLs.
  function slugify(value) {
    return String(value == null ? '' : value)
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')   // combining accents NFD splits off (a-grave, e-acute, o-dot, ...)
      .replace(/[\u0110\u0111]/g, 'd')   // D-bar and d-bar, which NFD leaves alone
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  }
  window.khSlugify = slugify;

  $('[data-slug-source]').each(function () {
    var $src = $(this);
    var $target = $($src.attr('data-slug-source'));
    $src.on('input', function () {
      if (!$target.length || $target.data('touched')) return;
      $target.val(slugify($.trim($src.val())));
    });
    if ($target.length) {
      $target.on('input', function () { $(this).data('touched', true); });
      // Typing a Vietnamese title straight into the slug field should still produce a valid slug.
      $target.on('blur', function () {
        var $t = $(this);
        var cleaned = slugify($t.val());
        if (cleaned !== $.trim($t.val())) $t.val(cleaned);
      });
    }
  });

  /* ---- tag toggles, tabs, role list ---- */
  $('.tag-toggle').on('click', function () {
    var $t = $(this);
    var isPressed = $t.attr('aria-pressed') === 'true';
    $t.attr('aria-pressed', String(!isPressed));
  });

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

  /* ---- roles preview selector ---- */
  $('[data-role]').on('click', function () {
    var $r = $(this);
    $r.parent().find('[data-role]').each(function () {
      $(this).attr('aria-pressed', String(this === $r[0]));
    });
    var $t = $('[data-role-title]');
    if ($t.length) $t.text($r.attr('data-role') + ' permissions');
  });
});
