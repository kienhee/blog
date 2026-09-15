/* Kienhee admin — shared Trash (posts, categories, hashtags, comments) */
$(function () {
  'use strict';

  var $all = $('#trash-check-all');
  var $checks = $('.trash-check');
  var $form = $('#trash-action-form');

  function selectedIds() {
    return $checks.filter(':checked').map(function () { return this.value; }).get();
  }

  function sync() {
    var n = selectedIds().length;
    $('#trash-selected').text(n + ' selected');
    $('[data-trash-bulk]').prop('disabled', n === 0);
    $all.prop('checked', $checks.length > 0 && n === $checks.length);
  }

  function submit(action, ids, confirmWhat) {
    $form.attr('action', action).find('input[name="ids"]').remove();
    $.each(ids, function (_, id) { $('<input type="hidden" name="ids">').val(id).appendTo($form); });
    if (confirmWhat && window.khDialog) {
      window.khDialog(confirmWhat, function () { $form[0].submit(); },
        'This deletes it for good and cannot be undone.');
    } else {
      $form[0].submit();
    }
  }

  $all.on('change', function () {
    $checks.prop('checked', this.checked);
    sync();
  });
  $checks.on('change', sync);
  sync();

  $('[data-trash-bulk]').on('click', function () {
    var ids = selectedIds();
    if (!ids.length) return;
    var purge = $(this).attr('data-trash-bulk') === 'purge';
    submit($(this).attr('data-action'), ids, purge ? ids.length + ' selected item' + (ids.length === 1 ? '' : 's') : null);
  });

  $(document).on('click', '[data-trash-row]', function () {
    var $btn = $(this);
    var purge = $btn.attr('data-trash-row') === 'purge';
    submit($btn.attr('data-action'), [$btn.attr('data-id')], purge ? '"' + ($btn.attr('data-name') || 'this item') + '"' : null);
  });

  $('#btn-empty-trash').on('click', function () {
    var $empty = $('#trash-empty-form');
    if (window.khDialog) {
      window.khDialog('everything in this tab', function () { $empty[0].submit(); },
        'Every item in this tab is deleted for good. This cannot be undone.');
    } else {
      $empty[0].submit();
    }
  });
});
