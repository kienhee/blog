/* Kienhee — translations for scripts.
 *
 * The layout inlines the js.* slice of the message catalogue as JSON (see JsMessages and
 * fragments/i18n.html); this exposes it as khT('js.key', arg0, arg1) with {0}-style parameters,
 * matching the server side. A missing key renders as the key itself, so it is visible rather
 * than silently blank (MessageUsageTests is what keeps that from happening).
 */
(function () {
  'use strict';

  var dict = {};
  try {
    var el = document.getElementById('kh-i18n');
    if (el) dict = JSON.parse(el.textContent || el.innerHTML || '{}');
  } catch (e) {
    dict = {};
  }

  window.khT = function (key) {
    var text = dict[key];
    if (text === undefined || text === null) return key;
    if (arguments.length < 2) return text;
    var args = Array.prototype.slice.call(arguments, 1);
    return text.replace(/\{(\d+)\}/g, function (whole, index) {
      var value = args[Number(index)];
      return value === undefined || value === null ? whole : String(value);
    });
  };

  /** The language of the page ("vi" / "en"), for library locale files. */
  window.khLang = document.documentElement.getAttribute('lang') || 'vi';
}());
