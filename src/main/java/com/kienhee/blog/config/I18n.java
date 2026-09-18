package com.kienhee.blog.config;

import java.util.List;
import java.util.Locale;
import java.util.Map;

/**
 * The two languages this site speaks, in one place.
 *
 * <p>Vietnamese is the default: {@code messages.properties} holds the Vietnamese texts (so a
 * missing English key falls back to Vietnamese rather than to a raw key) and
 * {@code messages_en.properties} holds the English ones.</p>
 *
 * <p>The language is remembered in the {@link #COOKIE} cookie only — there is no {@code /vi/},
 * {@code /en/} URL prefix, so public URLs never change. See {@code docs/plan-i18n.md}.</p>
 */
public final class I18n {

    public static final Locale VI = Locale.forLanguageTag("vi");
    public static final Locale EN = Locale.ENGLISH;

    /** Used for requests with no cookie, and for background jobs that have no request at all. */
    public static final Locale DEFAULT = VI;

    public static final String COOKIE = "kh_lang";

    private static final Map<String, Locale> SUPPORTED = Map.of("vi", VI, "en", EN);

    /** Language codes in display order (the order of the flags in the switcher). */
    public static final List<String> CODES = List.of("vi", "en");

    private I18n() {
    }

    /**
     * The locale for a language code, or {@code null} when the code is not one we support.
     * A cookie can be edited by hand, so every incoming value goes through here.
     */
    public static Locale parse(String code) {
        if (code == null) {
            return null;
        }
        return SUPPORTED.get(code.trim().toLowerCase(Locale.ROOT));
    }

    /** Like {@link #parse(String)} but falls back to {@link #DEFAULT}. */
    public static Locale parseOrDefault(String code) {
        Locale locale = parse(code);
        return locale != null ? locale : DEFAULT;
    }

    /** {@code "vi"} / {@code "en"} for a resolved locale; unknown locales report the default. */
    public static String codeOf(Locale locale) {
        if (locale == null) {
            return DEFAULT.getLanguage();
        }
        String language = locale.getLanguage();
        return SUPPORTED.containsKey(language) ? language : DEFAULT.getLanguage();
    }
}
