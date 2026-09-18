/* Vietnamese locale for flatpickr, kept small and local (the upstream l10n bundle is not vendored).
 * Loaded by the pages that use the date picker; post-editor.js applies it via flatpickr.localize
 * when the page language is not English. */
window.khFlatpickrLocale = {
  weekdays: {
    shorthand: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
    longhand: ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy']
  },
  months: {
    shorthand: ['Th1', 'Th2', 'Th3', 'Th4', 'Th5', 'Th6', 'Th7', 'Th8', 'Th9', 'Th10', 'Th11', 'Th12'],
    longhand: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
               'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12']
  },
  firstDayOfWeek: 1,
  rangeSeparator: ' đến ',
  weekAbbreviation: 'Tuần',
  scrollTitle: 'Lăn chuột để tăng/giảm',
  toggleTitle: 'Nhấn để chuyển AM/PM',
  time_24hr: true
};
