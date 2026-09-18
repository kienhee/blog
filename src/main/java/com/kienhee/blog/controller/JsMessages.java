package com.kienhee.blog.controller;

import com.kienhee.blog.config.I18n;
import org.springframework.stereotype.Component;

import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.Reader;
import java.nio.charset.StandardCharsets;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.Properties;
import java.util.TreeSet;
import java.util.concurrent.ConcurrentHashMap;

/**
 * The {@code js.*} slice of the message catalogue, as JSON for the browser.
 *
 * <p>There is no bundler in this project, so scripts can't import translations. The layout renders
 * this JSON into {@code <script type="application/json" id="kh-i18n">} and {@code core/i18n.js}
 * turns it into {@code khT('js.some.key')}. Only the prefixes in {@code PREFIXES} are sent — the
 * rest of the catalogue stays on the server.</p>
 *
 * <p>{@code MessageSource} cannot enumerate keys, so the two bundles are read here directly; they
 * are the same files Spring uses, and {@code MessageCatalogTests} keeps them in step.</p>
 */
@Component
public class JsMessages {

    /**
     * Prefixes sent to the browser: {@code js.*} (script-only copy) and {@code validation.*}, so the
     * client-side (jQuery Validate) messages are the very same strings the server answers with.
     */
    private static final List<String> PREFIXES = List.of("js.", "validation.");

    private final Map<String, String> cache = new ConcurrentHashMap<>();

    /** JSON object of every js.* message for this language, ready to inline in a script tag. */
    public String asJson(Locale locale) {
        return cache.computeIfAbsent(I18n.codeOf(locale), this::build);
    }

    private String build(String language) {
        Properties fallback = load("/messages.properties");
        Properties preferred = I18n.EN.getLanguage().equals(language) ? load("/messages_en.properties") : fallback;

        Map<String, String> selected = new LinkedHashMap<>();
        for (String key : new TreeSet<>(fallback.stringPropertyNames())) {
            if (PREFIXES.stream().anyMatch(key::startsWith)) {
                selected.put(key, preferred.getProperty(key, fallback.getProperty(key)));
            }
        }

        StringBuilder out = new StringBuilder("{");
        boolean first = true;
        for (Map.Entry<String, String> entry : selected.entrySet()) {
            if (!first) {
                out.append(',');
            }
            first = false;
            quote(out, entry.getKey()).append(':');
            quote(out, entry.getValue());
        }
        return out.append('}').toString();
    }

    /**
     * Minimal JSON string writer — the project has no JSON dependency of its own here, and the
     * input is our own catalogue. {@code <} is escaped as well, so the JSON can never close the
     * script tag that carries it.
     */
    private static StringBuilder quote(StringBuilder out, String value) {
        out.append('"');
        for (int i = 0; i < value.length(); i++) {
            char c = value.charAt(i);
            switch (c) {
                case '"' -> out.append("\\\"");
                case '\\' -> out.append("\\\\");
                case '\n' -> out.append("\\n");
                case '\r' -> out.append("\\r");
                case '\t' -> out.append("\\t");
                case '<' -> out.append("\\u003C");
                default -> {
                    if (c < 0x20) {
                        out.append(String.format("\\u%04x", (int) c));
                    } else {
                        out.append(c);
                    }
                }
            }
        }
        return out.append('"');
    }

    private Properties load(String resource) {
        Properties properties = new Properties();
        try (InputStream in = getClass().getResourceAsStream(resource)) {
            if (in == null) {
                throw new IllegalStateException(resource + " is missing from the classpath");
            }
            try (Reader reader = new InputStreamReader(in, StandardCharsets.UTF_8)) {
                properties.load(reader);
            }
        } catch (IOException e) {
            throw new IllegalStateException("Cannot read " + resource, e);
        }
        return properties;
    }
}
