package com.kienhee.blog.controller;

import com.kienhee.blog.config.I18n;
import com.kienhee.blog.entity.Post;
import com.kienhee.blog.service.PublicBlogService;
import com.kienhee.blog.service.SettingService;
import lombok.RequiredArgsConstructor;
import org.springframework.context.i18n.LocaleContextHolder;
import org.springframework.web.bind.annotation.ControllerAdvice;
import org.springframework.web.bind.annotation.ModelAttribute;

import java.util.List;
import java.util.Map;

/** Site-wide values for the public layout (header, footer, search overlay). */
@ControllerAdvice(assignableTypes = {PublicController.class, NewsletterController.class})
@RequiredArgsConstructor
public class PublicSiteAdvice {

    private final SettingService settingService;
    private final PublicBlogService publicBlogService;

    /** All settings by key, e.g. {@code ${site['site.domain']}}. */
    @ModelAttribute("site")
    public Map<String, String> site() {
        return settingService.getAll();
    }

    @ModelAttribute("siteTitle")
    public String siteTitle() {
        return perLanguage("site.title", "Kienhee");
    }

    @ModelAttribute("siteDescription")
    public String siteDescription() {
        return perLanguage("site.meta_description", "AI guides and news, written and tested by one person.");
    }

    /**
     * A setting the admin may fill in per language: {@code site.title.vi} / {@code site.title.en},
     * falling back to the plain key (what older installs hold) and then to the built-in default.
     * {@code SettingService} is a free key/value store, so this needs no migration.
     */
    private String perLanguage(String key, String fallback) {
        String language = I18n.codeOf(LocaleContextHolder.getLocale());
        String translated = settingService.get(key + "." + language, "");
        if (!translated.isBlank()) {
            return translated;
        }
        return settingService.get(key, fallback);
    }

    @ModelAttribute("mainAuthorId")
    public Long mainAuthorId() {
        return publicBlogService.mainAuthorId().orElse(null);
    }

    /** Newest posts shown in the search overlay before the reader types. */
    @ModelAttribute("suggestedPosts")
    public List<Post> suggestedPosts() {
        return publicBlogService.latest(1, 4).getContent();
    }
}
