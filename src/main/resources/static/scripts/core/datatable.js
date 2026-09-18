/* Kienhee admin — DataTables.net integration for admin list tables */
$(function () {
  'use strict';

  // Lightbox2 (admin layout) inserts captions as HTML by default. Captions come from user data
  // (post titles, file names), so they are always inserted as text.
  if (window.lightbox) {
    window.lightbox.option({ sanitizeTitle: true });
  }

  if (!$.fn.DataTable) {
    return;
  }

  $('table.dt').each(function () {
    var $table = $(this);
    // Site-wide "Posts per page" setting (meta from GlobalControllerAdvice); data-per-page overrides per table.
    var perPage = parseInt($table.attr('data-per-page') || $('meta[name="kh-posts-per-page"]').attr('content'), 10);
    if (!(perPage > 0)) perPage = 10;
    var $wrapRoot = $table.closest('.dt-root');
    var $bulk = $wrapRoot.find('.bulk');

    // Prevent header clicks/typing inside the column filter row from triggering a sort
    $table.find('tr.filters').on('click mousedown keydown', 'input, select', function (e) {
      e.stopPropagation();
    });

    var lastColumnIndex = $table.find('thead tr:first-child th').length - 1;

    // Page-size choices; the table's own default size is always one of them.
    var lengthMenu = [10, 25, 50, 100];
    if (lengthMenu.indexOf(perPage) < 0) lengthMenu.push(perPage);
    lengthMenu.sort(function (a, b) { return a - b; });

    var dt = $table.DataTable({
      pageLength: perPage,
      lengthMenu: lengthMenu,
      // "Show N entries" sits next to the pagination instead of above the table.
      layout: {
        topStart: null,
        bottomEnd: ['pageLength', 'paging']
      },
      orderCellsTop: true,
      autoWidth: false,
      // Default sort: column 1 ascending; a table can override with data-order-col / data-order-dir.
      order: [[parseInt($table.attr('data-order-col') || '1', 10), $table.attr('data-order-dir') === 'desc' ? 'desc' : 'asc']],
      columnDefs: [
        { targets: 0, orderable: false, searchable: false },
        { targets: lastColumnIndex, orderable: false, searchable: false }
      ],
      language: {
        search: '',
        searchPlaceholder: khT('js.table.search'),
        lengthMenu: khT('js.table.length_menu'),
        info: khT('js.table.info'),
        infoEmpty: khT('js.table.info_empty'),
        infoFiltered: khT('js.table.info_filtered'),
        zeroRecords: khT('js.table.no_match'),
        emptyTable: khT('js.table.empty'),
        paginate: {
          first: '«',
          previous: khT('js.table.prev'),
          next: khT('js.table.next'),
          last: '»'
        }
      },
      drawCallback: function () {
        $table.find('.allcheck').prop('checked', false);
        syncBulk();

        // Responsive data-label attributes for mobile stacked view
        var heads = $table.find('thead tr:first-child th').map(function () {
          return $.trim($(this).text());
        }).get();
        $table.find('tbody tr').each(function () {
          $(this).children('td').each(function (i) {
            if (heads[i] && !$(this).attr('data-label')) {
              $(this).attr('data-label', heads[i]);
            }
          });
        });
      }
    });

    // Column-specific search inputs in the header filters row (text inputs and selects)
    $table.find('tr.filters [data-col]').on('keyup change clear', function () {
      var colIdx = parseInt($(this).data('col'), 10);
      var val = this.value;
      if (dt.column(colIdx).search() !== val) {
        dt.column(colIdx).search(val).draw();
      }
    });

    // Bulk selection handling (scoped to rows on the current page)
    function syncBulk() {
      var checked = $table.find('tbody .rowcheck:checked').length;
      if ($bulk.length) {
        $bulk.toggleClass('show', checked > 0);
        $bulk.find('.n').text(checked + ' selected');
      }
    }

    $table.on('change', '.allcheck', function () {
      var isChecked = $(this).prop('checked');
      dt.rows({ page: 'current' }).nodes().to$().find('.rowcheck').prop('checked', isChecked);
      syncBulk();
    });

    $table.on('change', '.rowcheck', function () {
      var $pageRows = dt.rows({ page: 'current' }).nodes().to$();
      var total = $pageRows.find('.rowcheck').length;
      var checked = $pageRows.find('.rowcheck:checked').length;
      $table.find('.allcheck').prop('checked', total > 0 && total === checked);
      syncBulk();
    });

    if ($bulk.length) {
      // Bulk delete posts the checked ids (from every page, not only the visible one) to the table's
      // data-bulk-delete-url through the shared confirm dialog. Tables without that attribute have no bulk delete.
      $bulk.find('[data-bulk="Delete"]').on('click', function () {
        var url = $table.attr('data-bulk-delete-url');
        if (!url) return;
        var ids = $(dt.rows().nodes()).find('.rowcheck:checked').map(function () { return this.value; }).get();
        if (!ids.length) {
          if (window.khToast) window.khToast(khT('js.table.select_row'));
          return;
        }
        var $form = $('#dt-bulk-delete-form');
        if (!$form.length) {
          $form = $('<form id="dt-bulk-delete-form" method="post" style="display:none"></form>').appendTo(document.body);
        }
        $form.empty().attr('action', url);
        $('<input type="hidden" name="_csrf">').val($('meta[name="_csrf"]').attr('content') || '').appendTo($form);
        $.each(ids, function (_, id) { $('<input type="hidden" name="ids">').val(id).appendTo($form); });
        var noun = $table.attr('data-bulk-noun') || khT('js.table.records');
        if (window.khDialog) {
          window.khDialog(khT('js.table.selected_count', ids.length, noun), '#dt-bulk-delete-form',
            $table.attr('data-bulk-delete-note') || khT('js.dialog.irreversible'));
        }
      });

      $bulk.find('[data-bulk-clear]').on('click', function () {
        $table.find('.rowcheck, .allcheck').prop('checked', false);
        syncBulk();
      });
    }
  });
});
