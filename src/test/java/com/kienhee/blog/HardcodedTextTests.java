package com.kienhee.blog;

import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.springframework.core.io.Resource;
import org.springframework.core.io.support.PathMatchingResourcePatternResolver;

import java.io.IOException;
import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.List;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import static org.junit.jupiter.api.Assertions.assertTrue;

/**
 * Regression guard for the i18n work: no new English sentence may be typed straight into a
 * template or a script. Everything a reader sees goes through the catalogue
 * ({@code #{...}} in Thymeleaf, {@code khT('js...')} in scripts).
 *
 * <p>This is the test that keeps a new page from quietly arriving in one language only. When it
 * fails, the fix is a message key — not an entry in the allow-lists below.</p>
 */
@DisplayName("No hard-coded user-facing text")
class HardcodedTextTests {

    /** A word-y text node between tags: the kind of thing a reader reads. */
    private static final Pattern TEXT_NODE = Pattern.compile(">([A-Z][a-z]+(?:[ ,'’]+[A-Za-z]+){2,}[.?!]?)<");

    /** A string literal in a script: starts with a capital, three words or more. */
    private static final Pattern JS_STRING =
            Pattern.compile("(?<![\\w.])'([A-Z][a-z]+(?:[ ,']+[A-Za-z]+){2,}[.?!]?)'");

    /**
     * Prototype text (Thymeleaf natural templating) sits inside a tag that also carries th:text, so
     * it never reaches a reader. Everything listed here was checked by hand once.
     */
    private static final List<String> ALLOWED_TEMPLATE_LINES = List.of(
            "th:text", "th:utext", "th:errors", "th:each", "th:replace", "th:include", "th:insert",
            "<!--", "th:placeholder", "th:title", "th:aria-label", "th:data-tip", "th:value");

    private static final List<String> ALLOWED_JS_LINES = List.of("khT(", "//", "* ", "console.");

    private static List<String> offenders(String pattern, Pattern regex, List<String> allowedLines) {
        List<String> found = new ArrayList<>();
        try {
            for (Resource resource : new PathMatchingResourcePatternResolver().getResources(pattern)) {
                String name = resource.getFilename();
                if (name == null) {
                    continue;
                }
                String[] lines = new String(resource.getInputStream().readAllBytes(), StandardCharsets.UTF_8)
                        .split("\r?\n");
                for (int i = 0; i < lines.length; i++) {
                    String line = lines[i];
                    if (allowedLines.stream().anyMatch(line::contains)) {
                        continue;
                    }
                    Matcher matcher = regex.matcher(line);
                    while (matcher.find()) {
                        found.add(name + ":" + (i + 1) + "  " + matcher.group(1));
                    }
                }
            }
        } catch (IOException e) {
            throw new IllegalStateException(e);
        }
        return found;
    }

    @Test
    @DisplayName("no English sentence typed directly into a template")
    void templatesUseKeys() {
        List<String> found = offenders("file:src/main/resources/templates/**/*.html", TEXT_NODE, ALLOWED_TEMPLATE_LINES);
        assertTrue(found.isEmpty(), "text a reader sees must come from messages*.properties:\n  " + String.join("\n  ", found));
    }

    @Test
    @DisplayName("no English sentence typed directly into a script")
    void scriptsUseKeys() {
        List<String> found = offenders("file:src/main/resources/static/scripts/{core,auth,user,category,hashtag,post,media,public,comment,role,setting,subscriber,trash}/*.js",
                JS_STRING, ALLOWED_JS_LINES);
        assertTrue(found.isEmpty(), "text a reader sees must come from the js.* keys:\n  " + String.join("\n  ", found));
    }
}
