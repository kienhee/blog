package com.kienhee.blog;

import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.springframework.core.io.Resource;
import org.springframework.core.io.support.PathMatchingResourcePatternResolver;

import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.Reader;
import java.nio.charset.StandardCharsets;
import java.util.Properties;
import java.util.TreeSet;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import static org.junit.jupiter.api.Assertions.assertNotNull;
import static org.junit.jupiter.api.Assertions.assertTrue;

/**
 * Every message key a template or a DTO names must exist in the catalogue.
 *
 * <p>No Spring context: this reads the files, like {@code DemoDataSeparationTests}. It catches the
 * typo case that {@code MessageCatalogTests} cannot — a key that is used but was never added.</p>
 *
 * <p>Keys built at runtime ({@code #{'enum.post.status.' + ${p.status.name()}}}) can't be checked
 * this way, so they are skipped here; the pages that use them are exercised by the MVC tests.</p>
 */
@DisplayName("Message keys used by templates and DTOs")
class MessageUsageTests {

    /** #{some.key} or #{some.key(args)} — a literal key only, no leading quote. */
    private static final Pattern TEMPLATE_KEY = Pattern.compile("#\\{([a-z][a-zA-Z0-9_.]*)[(}]");

    /** message = "{validation.x.y}" in a DTO. */
    private static final Pattern DTO_KEY = Pattern.compile("message\\s*=\\s*\"\\{([^}\"]+)}\"");

    private static Properties catalogue() {
        Properties properties = new Properties();
        try (InputStream in = MessageUsageTests.class.getResourceAsStream("/messages.properties")) {
            assertNotNull(in, "messages.properties must exist");
            try (Reader reader = new InputStreamReader(in, StandardCharsets.UTF_8)) {
                properties.load(reader);
            }
        } catch (IOException e) {
            throw new IllegalStateException(e);
        }
        return properties;
    }

    private static TreeSet<String> keysIn(String pattern, Pattern regex) {
        TreeSet<String> found = new TreeSet<>();
        try {
            for (Resource resource : new PathMatchingResourcePatternResolver().getResources(pattern)) {
                String body = new String(resource.getInputStream().readAllBytes(), StandardCharsets.UTF_8);
                Matcher matcher = regex.matcher(body);
                while (matcher.find()) {
                    found.add(matcher.group(1));
                }
            }
        } catch (IOException e) {
            throw new IllegalStateException(e);
        }
        return found;
    }

    @Test
    @DisplayName("every #{key} in a template is in the catalogue")
    void templateKeysExist() {
        Properties catalogue = catalogue();
        TreeSet<String> used = keysIn("file:src/main/resources/templates/**/*.html", TEMPLATE_KEY);
        assertTrue(used.size() > 50, "expected the templates to use many keys, found " + used.size());

        TreeSet<String> missing = new TreeSet<>();
        for (String key : used) {
            if (!catalogue.containsKey(key)) {
                missing.add(key);
            }
        }
        assertTrue(missing.isEmpty(), "used in a template but not in messages.properties: " + missing);
    }

    @Test
    @DisplayName("every constraint message code in a DTO is in the catalogue")
    void dtoKeysExist() {
        Properties catalogue = catalogue();
        TreeSet<String> used = keysIn("file:src/main/java/com/kienhee/blog/dto/*.java", DTO_KEY);
        assertTrue(used.size() > 30, "expected the DTOs to use many codes, found " + used.size());

        TreeSet<String> missing = new TreeSet<>();
        for (String key : used) {
            if (!catalogue.containsKey(key)) {
                missing.add(key);
            }
        }
        assertTrue(missing.isEmpty(), "used in a DTO but not in messages.properties: " + missing);
    }

    @Test
    @DisplayName("no DTO carries a hard-coded English constraint message any more")
    void dtoMessagesAreCodes() {
        TreeSet<String> plain = keysIn("file:src/main/java/com/kienhee/blog/dto/*.java",
                Pattern.compile("message\\s*=\\s*\"([^\"{}]+)\""));
        assertTrue(plain.isEmpty(), "these constraint messages are still literal text: " + plain);
    }
}
