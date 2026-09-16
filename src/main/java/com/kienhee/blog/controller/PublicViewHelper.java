package com.kienhee.blog.controller;

import com.kienhee.blog.entity.Post;
import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.jsoup.safety.Safelist;
import org.springframework.stereotype.Component;

import java.time.Duration;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.Locale;

/**
 * Formatting helpers for public templates, used as {@code ${@publicView.readMinutes(post)}}.
 */
@Component("publicView")
public class PublicViewHelper {

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

    private static final DateTimeFormatter DATE = DateTimeFormatter.ofPattern("MMM d, yyyy", Locale.ENGLISH);

    /** "Sep 11, 2026" regardless of the visitor's locale (templates can't reach Locale.ENGLISH). */
    public String date(LocalDateTime value) {
        return value == null ? "" : DATE.format(value);
    }

    /** "just now", "5 minutes ago", "2 days ago"; older than 30 days falls back to the date. */
    public String ago(LocalDateTime value) {
        if (value == null) return "";
        Duration d = Duration.between(value, LocalDateTime.now());
        long minutes = d.toMinutes();
        if (minutes < 1) return "just now";
        if (minutes < 60) return minutes + (minutes == 1 ? " minute ago" : " minutes ago");
        long hours = d.toHours();
        if (hours < 24) return hours + (hours == 1 ? " hour ago" : " hours ago");
        long days = d.toDays();
        if (days < 30) return days + (days == 1 ? " day ago" : " days ago");
        return date(value);
    }

    public boolean hasCover(Post post) {
        return post.getCoverImage() != null && !post.getCoverImage().isBlank();
    }
}
