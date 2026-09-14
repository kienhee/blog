/* Kienhee admin — media trash (restore / permanent delete / empty trash)
 *
 * Restore is reversible, so it is a plain form submit. Everything destructive goes
 * through the shared confirm dialog (khDialog in core/admin.js), which submits the
 * target form it is given once the user confirms.
 */
$(function () {
  'use strict';

  function pointForm(selector, url) {
    var $form = $(selector);
    if (!$form.length) return null;
    $form.attr('action', url);
    return $form;
  }

  function confirmDelete(label, formSelector, description) {
    if (window.khDialog) {
      window.khDialog(label, formSelector, description);
    } else if (window.confirm('Permanently delete ' + label + '? ' + (description || 'This cannot be undone.'))) {
      $(formSelector).submit();
    }
  }

  var base = $('#purge-media-form').attr('action') || '';
  // ".../admin/media/0/delete-permanent" -> ".../admin/media/"
  var mediaBase = base.replace(/0\/delete-permanent$/, '');
  var folderBase = ($('#purge-folder-form').attr('action') || '').replace(/0\/delete-permanent$/, '');

  $(document).on('click', '.btn-purge-media', function () {
    var id = $(this).data('purge-id');
    var name = $(this).attr('data-purge-name') || 'this file';
    if (!pointForm('#purge-media-form', mediaBase + id + '/delete-permanent')) return;
    confirmDelete('"' + name + '" permanently', '#purge-media-form',
      'This cannot be undone. The file is removed from storage and its space is freed.');
  });

  $(document).on('click', '.btn-purge-folder', function () {
    var id = $(this).data('purge-id');
    var name = $(this).attr('data-purge-name') || 'this folder';
    if (!pointForm('#purge-folder-form', folderBase + id + '/delete-permanent')) return;
    confirmDelete('folder "' + name + '" permanently', '#purge-folder-form',
      'This cannot be undone. The folder and its subfolders are removed; any files still inside stay in the trash at Home.');
  });

  $('#btn-empty-trash').on('click', function () {
    confirmDelete('everything in the trash permanently', '#empty-trash-form',
      'This cannot be undone. Every file and folder in the trash is removed and their storage is freed.');
  });

  /* ---- multi-select: restore / permanently delete several rows at once ---- */
  var $bar = $('#trash-selection-bar');

  function checkedRows() {
    return $('.trash-check:checked').closest('tr.trash-row');
  }

  function syncSelection() {
    var n = checkedRows().length;
    var total = $('.trash-check').length;
    $bar.toggleClass('show', n > 0);
    $('#trash-selection-count').text(n + (n === 1 ? ' item selected' : ' items selected'));
    $('#trash-select-all').prop('checked', total > 0 && n === total).prop('indeterminate', n > 0 && n < total);
    $('tr.trash-row').each(function () {
      $(this).toggleClass('is-selected', $(this).find('.trash-check').prop('checked') === true);
    });
  }

  $(document).on('change', '.trash-check', syncSelection);

  $('#trash-select-all').on('change', function () {
    $('.trash-check').prop('checked', this.checked);
    syncSelection();
  });

  $('#btn-trash-clear').on('click', function () {
    $('.trash-check, #trash-select-all').prop('checked', false);
    syncSelection();
  });

  // Clicking anywhere on a row toggles it, except on its own buttons, forms and the checkbox itself.
  $(document).on('click', 'tr.trash-row', function (e) {
    if ($(e.target).closest('button, a, form, input, label').length) return;
    var $check = $(this).find('.trash-check');
    if (!$check.length) return;
    $check.prop('checked', !$check.prop('checked'));
    syncSelection();
  });

  function selectedIds() {
    var ids = { mediaIds: [], folderIds: [] };
    checkedRows().each(function () {
      (String($(this).attr('data-kind')) === 'folder' ? ids.folderIds : ids.mediaIds).push($(this).attr('data-id'));
    });
    return ids;
  }

  function postJson(url, data) {
    return $.ajax({
      url: url,
      method: 'POST',
      data: $.extend({ _csrf: $('input[name="_csrf"]').first().val() || '' }, data),
      traditional: true, // mediaIds=1&mediaIds=2, the shape Spring binds to List<Long>
      dataType: 'json'
    });
  }

  function notify(message) {
    if (window.khToast && message) window.khToast(message);
  }

  /** Fades out every row the server reports as gone from the trash and refreshes the totals. */
  function applyResult(res) {
    var gone = {};
    (res.removedMediaIds || []).forEach(function (id) { gone['media:' + id] = true; });
    (res.removedFolderIds || []).forEach(function (id) { gone['folder:' + id] = true; });

    var $rows = $('tr.trash-row').filter(function () {
      return gone[$(this).attr('data-kind') + ':' + $(this).attr('data-id')];
    });
    $rows.removeClass('trash-row').find('.trash-check').prop('checked', false).removeClass('trash-check');
    $rows.fadeOut(200, function () {
      $(this).remove();
      if (!$('tr.trash-row').length) {
        $('#trash-empty').fadeIn(200);
        $('#btn-empty-trash').prop('disabled', true);
      }
    });
    syncSelection();

    if (typeof res.fileCount === 'number') {
      $('#trash-counts').text(res.fileCount + ' file(s), ' + res.folderCount + ' folder(s)');
      $('#trash-bytes').text('Trash is using ' + (res.totalBytes / 1048576).toFixed(2) + ' MB of storage.');
    }
  }

  function runBatch(url) {
    postJson(url, selectedIds())
      .done(function (res) { applyResult(res); notify(res.message); })
      .fail(function (xhr) {
        var res = xhr.responseJSON || {};
        applyResult(res);
        notify(res.message || 'That did not work. Please try again.');
      });
  }

  $('#btn-trash-restore-selected').on('click', function () {
    if (!checkedRows().length) return;
    runBatch('/admin/media/api/trash/restore'); // reversible, so no confirm
  });

  $('#btn-trash-purge-selected').on('click', function () {
    var n = checkedRows().length;
    if (!n) return;
    var label = n + (n === 1 ? ' selected item' : ' selected items') + ' permanently';
    var description = 'This cannot be undone. The selected ' + (n === 1 ? 'item is' : 'items are')
      + ' removed from storage and the space is freed.';
    if (window.khDialog) {
      window.khDialog(label, function () { runBatch('/admin/media/api/trash/delete-permanent'); }, description);
    } else if (window.confirm('Permanently delete ' + label + '? ' + description)) {
      runBatch('/admin/media/api/trash/delete-permanent');
    }
  });

  syncSelection();
});
