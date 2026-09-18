package com.kienhee.blog.controller;

import com.kienhee.blog.config.I18n;
import com.kienhee.blog.entity.Post;
import lombok.RequiredArgsConstructor;
import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.jsoup.safety.Safelist;
import org.springframework.context.MessageSource;
import org.springframework.context.i18n.LocaleContextHolder;
import org.springframework.stereotype.Component;

import java.time.Duration;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.Locale;

/**
 * Formatting helpers for public templates, used as {@code ${@publicView.readMinutes(post)}}.
 */
@Component("publicView")
@RequiredArgsConstructor
public class PublicViewHelper {

    private final MessageSource messages;

    private static final int WORDS_PER_MINUTE = 220;
    private static final int DEK_LENGTH = 180;

    /**
     * Post content is HTML from the admin editor. It is rendered with th:utext, so it is cleaned to a
     * safe subset first: no scripts, event handlers, iframes or javascript: URLs. Relative links
     * (/media/...) are kept.
     */
    private static final Safelist SAFELIST = Safelist.relaxed()
            .addTags("figure", "figcaption", "hr", "mark", "s")
            // TinyMCE marks up figures, code samples and alignment with classes/inline styles.
            .addAttributes(":all", "id", "title", "class")
            .addAttributes("p", "style").addAttributes("h1", "style").addAttributes("h2", "style")
            .addAttributes("h3", "style").addAttributes("h4", "style").addAttributes("h5", "style")
            .addAttributes("h6", "style").addAttributes("figure", "style").addAttributes("img", "style")
            .addAttributes("table", "style").addAttributes("td", "style").addAttributes("th", "style")
            .addAttributes("a", "target", "rel")
            .preserveRelativeLinks(true);

    private static final String BASE_URI = "http://localhost/";

    public String safeHtml(String html) {
        if (html == null || html.isBlank()) return "";
        Document.OutputSettings output = new Document.OutputSettings().prettyPrint(false);
        return Jsoup.clean(html, BASE_URI, SAFELIST, output);
    }

    public String plainText(String html) {
        return html == null ? "" : Jsoup.parse(html).text();
    }

    public int readMinutes(Post post) {
        String text = plainText(post.getContent()).trim();
        if (text.isEmpty()) return 1;
        int words = text.split("\\s+").length;
        return Math.max(1, (int) Math.ceil(words / (double) WORDS_PER_MINUTE));
    }

    /** Short summary: excerpt, else SEO description, else the start of the content. */
    public String dek(Post post) {
        if (post.getExcerpt() != null && !post.getExcerpt().isBlank()) return post.getExcerpt();
        if (post.getSeoDescription() != null && !post.getSeoDescription().isBlank()) return post.getSeoDescription();
        String text = plainText(post.getContent()).trim();
        return text.length() > DEK_LENGTH ? text.substring(0, DEK_LENGTH).trim() + "…" : text;
    }

    /**
     * Date pattern per language: "11 thang 9, 2026" in Vietnamese, "Sep 11, 2026" in English.
     * Templates can't reach a Locale constant, so the language comes from the request here.
     */
    private static DateTimeFormatter dateFormatter(Locale locale) {
        return I18n.EN.getLanguage().equals(locale.getLanguage())
                ? DateTimeFormatter.ofPattern("MMM d, yyyy", I18n.EN)
                : DateTimeFormatter.ofPattern("d MMMM, yyyy", I18n.VI);
    }

    public String date(LocalDateTime value) {
        if (value == null) return "";
        return dateFormatter(LocaleContextHolder.getLocale()).format(value);
    }

    /** "just now", "5 minutes ago", "2 days ago"; older than 30 days falls back to the date. */
    public String ago(LocalDateTime value) {
        if (value == null) return "";
        Duration d = Duration.between(value, LocalDateTime.now());
        long minutes = d.toMinutes();
        if (minutes < 1) return say("common.ago.now");
        if (minutes < 60) return plural("common.ago.minute", minutes);
        long hours = d.toHours();
        if (hours < 24) return plural("common.ago.hour", hours);
        long days = d.toDays();
        if (days < 30) return plural("common.ago.day", days);
        return date(value);
    }

    private String say(String code, Object... args) {
        return messages.getMessage(code, args, LocaleContextHolder.getLocale());
    }

    /** English needs "1 minute" vs "2 minutes"; Vietnamese uses the same word either way. */
    private String plural(String prefix, long count) {
        return say(prefix + (count == 1 ? ".one" : ".other"), count);
    }

    public boolean hasCover(Post post) {
        return post.getCoverImage() != null && !post.getCoverImage().isBlank();
    }
}
