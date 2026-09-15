package com.kienhee.blog.controller;

import com.kienhee.blog.entity.Post;
import com.kienhee.blog.repository.PostRepository;
import com.kienhee.blog.service.PublicBlogService;
import com.kienhee.blog.service.SettingService;
import lombok.RequiredArgsConstructor;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.PageRequest;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.servlet.support.ServletUriComponentsBuilder;

import java.time.LocalDateTime;
import java.time.ZoneId;
import java.time.format.DateTimeFormatter;
import java.util.List;

/**
 * Machine-readable site files: RSS feed, XML sitemap and robots.txt. Only published posts and visible
 * categories appear (same rules as the public pages). Absolute links use the request's own scheme and host,
 * so they are right in dev and — with forwarded headers honoured (prod profile) — behind a proxy.
 */
@RestController
@RequiredArgsConstructor
public class FeedController {

    private static final int RSS_ITEMS = 20;
    private static final int SITEMAP_PAGE = 500;

    private final PublicBlogService blog;
    private final PostRepository postRepository;
    private final PublicViewHelper view;
    private final SettingService settingService;

    @GetMapping(value = "/rss.xml", produces = "application/rss+xml;charset=UTF-8")
    public String rss() {
        String base = baseUrl();
        List<Post> posts = blog.latest(1, RSS_ITEMS).getContent();

        StringBuilder x = new StringBuilder("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n")
                .append("<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n<channel>\n");
        tag(x, "title", settingService.get("site.title", "Kienhee"));
        tag(x, "link", base + "/");
        tag(x, "description", settingService.get("site.meta_description", "AI guides and news"));
        tag(x, "language", "en");
        x.append("<atom:link href=\"").append(esc(base + "/rss.xml")).append("\" rel=\"self\" type=\"application/rss+xml\"/>\n");
        if (!posts.isEmpty() && posts.get(0).getPublishedAt() != null) {
            tag(x, "lastBuildDate", rfc1123(posts.get(0).getPublishedAt()));
        }
        for (Post post : posts) {
            String link = base + "/article/" + post.getSlug();
            x.append("<item>\n");
            tag(x, "title", post.getTitle());
            tag(x, "link", link);
            x.append("<guid isPermaLink=\"true\">").append(esc(link)).append("</guid>\n");
            if (post.getPublishedAt() != null) {
                tag(x, "pubDate", rfc1123(post.getPublishedAt()));
            }
            if (post.getCategory() != null) {
                tag(x, "category", post.getCategory().getName());
            }
            tag(x, "description", view.dek(post));
            x.append("</item>\n");
        }
        return x.append("</channel>\n</rss>\n").toString();
    }

    @GetMapping(value = "/sitemap.xml", produces = "application/xml;charset=UTF-8")
    public String sitemap() {
        String base = baseUrl();
        StringBuilder x = new StringBuilder("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n")
                .append("<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n");
        for (String path : List.of("/", "/news", "/categories", "/about")) {
            url(x, base + path, null);
        }
        int page = 1;
        Page<Post> batch;
        do {
            batch = blog.latest(page++, SITEMAP_PAGE);
            for (Post post : batch) {
                url(x, base + "/article/" + post.getSlug(), post.getUpdatedAt() != null ? post.getUpdatedAt() : post.getPublishedAt());
            }
        } while (batch.hasNext());
        for (PublicBlogService.CategoryCard card : blog.categories()) {
            url(x, base + "/category/" + card.category().getSlug(), null);
        }
        for (Long authorId : postRepository.findPublishingAuthorIds(PageRequest.of(0, 1000))) {
            url(x, base + "/author/" + authorId, null);
        }
        return x.append("</urlset>\n").toString();
    }

    @GetMapping(value = "/robots.txt", produces = "text/plain;charset=UTF-8")
    public String robots() {
        return "User-agent: *\n"
                + "Disallow: /admin/\n"
                + "Disallow: /auth/\n"
                + "\n"
                + "Sitemap: " + baseUrl() + "/sitemap.xml\n";
    }

    private static String baseUrl() {
        return ServletUriComponentsBuilder.fromCurrentContextPath().build().toUriString();
    }

    private static void tag(StringBuilder x, String name, String value) {
        x.append('<').append(name).append('>').append(esc(value)).append("</").append(name).append(">\n");
    }

    private static void url(StringBuilder x, String loc, LocalDateTime lastModified) {
        x.append("<url><loc>").append(esc(loc)).append("</loc>");
        if (lastModified != null) {
            x.append("<lastmod>").append(lastModified.toLocalDate()).append("</lastmod>");
        }
        x.append("</url>\n");
    }

    private static String rfc1123(LocalDateTime value) {
        return DateTimeFormatter.RFC_1123_DATE_TIME.format(value.atZone(ZoneId.systemDefault()));
    }

    /** XML text/attribute escaping. */
    static String esc(String value) {
        if (value == null) return "";
        StringBuilder out = new StringBuilder(value.length());
        for (char c : value.toCharArray()) {
            switch (c) {
                case '&' -> out.append("&amp;");
                case '<' -> out.append("&lt;");
                case '>' -> out.append("&gt;");
                case '"' -> out.append("&quot;");
                case '\'' -> out.append("&apos;");
                default -> {
                    // XML 1.0 forbids most control characters.
                    if (c >= 0x20 || c == '\n' || c == '\r' || c == '\t') out.append(c);
                }
            }
        }
        return out.toString();
    }
}
