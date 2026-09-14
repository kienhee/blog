/* Kienhee admin — DataTables.net integration for admin list tables */
$(function () {
  'use strict';

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

    var dt = $table.DataTable({
      pageLength: perPage,
      lengthChange: false,
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
        searchPlaceholder: 'Search records…',
        lengthMenu: 'Show _MENU_ entries',
        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
        infoEmpty: 'Showing 0 of 0 entries',
        infoFiltered: '(filtered from _MAX_ total)',
        zeroRecords: 'No matching records found',
        emptyTable: 'No data available in table',
        paginate: {
          first: '«',
          previous: 'Prev',
          next: 'Next',
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
      $bulk.find('[data-bulk]').on('click', function () {
        var act = $(this).attr('data-bulk');
        if (act === 'Delete') {
          if (window.khDialog) window.khDialog('the selected records');
          return;
        }
        if (window.khToast) window.khToast(act + ' · applied to selection');
      });

      $bulk.find('[data-bulk-clear]').on('click', function () {
        $table.find('.rowcheck, .allcheck').prop('checked', false);
        syncBulk();
      });
    }
  });
});
