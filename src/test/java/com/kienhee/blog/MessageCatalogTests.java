package com.kienhee.blog;

import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.context.MessageSource;

import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.Reader;
import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.Properties;
import java.util.TreeSet;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertNotNull;
import static org.junit.jupiter.api.Assertions.assertTrue;

/**
 * Guard over the two message bundles, in the spirit of {@code PermissionCatalogTests}: the
 * Vietnamese and English catalogues must stay in step. Without this, an English key quietly
 * disappears and the page renders Vietnamese in the middle of an English layout.
 *
 * <p>{@code messages.properties} is Vietnamese (the default and the fallback);
 * {@code messages_en.properties} is English.</p>
 */
@SpringBootTest
@DisplayName("Message catalogue (vi / en)")
class MessageCatalogTests {

    private static final String VI_FILE = "/messages.properties";
    private static final String EN_FILE = "/messages_en.properties";

    @Autowired private MessageSource messageSource;

    private static Properties load(String resource) {
        Properties properties = new Properties();
        try (InputStream in = MessageCatalogTests.class.getResourceAsStream(resource)) {
            assertNotNull(in, resource + " must exist on the classpath");
            try (Reader reader = new InputStreamReader(in, StandardCharsets.UTF_8)) {
                properties.load(reader);
            }
        } catch (IOException e) {
            throw new IllegalStateException("Cannot read " + resource, e);
        }
        return properties;
    }

    /** Raw key order as written in the file, so duplicates can be spotted. */
    private static List<String> keysInOrder(String resource) {
        List<String> keys = new ArrayList<>();
        try (InputStream in = MessageCatalogTests.class.getResourceAsStream(resource)) {
            assertNotNull(in, resource + " must exist on the classpath");
            new String(in.readAllBytes(), StandardCharsets.UTF_8).lines().forEach(line -> {
                String trimmed = line.trim();
                if (trimmed.isEmpty() || trimmed.startsWith("#") || trimmed.startsWith("!")) {
                    return;
                }
                int eq = trimmed.indexOf('=');
                if (eq > 0) {
                    keys.add(trimmed.substring(0, eq).trim());
                }
            });
        } catch (IOException e) {
            throw new IllegalStateException("Cannot read " + resource, e);
        }
        return keys;
    }

    @Test
    @DisplayName("both bundles hold exactly the same keys")
    void sameKeys() {
        TreeSet<String> vi = new TreeSet<>(load(VI_FILE).stringPropertyNames());
        TreeSet<String> en = new TreeSet<>(load(EN_FILE).stringPropertyNames());

        TreeSet<String> missingInEn = new TreeSet<>(vi);
        missingInEn.removeAll(en);
        TreeSet<String> missingInVi = new TreeSet<>(en);
        missingInVi.removeAll(vi);

        assertTrue(missingInEn.isEmpty(), "missing from messages_en.properties: " + missingInEn);
        assertTrue(missingInVi.isEmpty(), "missing from messages.properties: " + missingInVi);
        assertTrue(vi.size() > 0, "the catalogue must not be empty");
    }

    @Test
    @DisplayName("no duplicated key inside a bundle")
    void noDuplicates() {
        for (String resource : List.of(VI_FILE, EN_FILE)) {
            Map<String, Integer> seen = new LinkedHashMap<>();
            for (String key : keysInOrder(resource)) {
                seen.merge(key, 1, Integer::sum);
            }
            List<String> duplicated = seen.entrySet().stream()
                    .filter(e -> e.getValue() > 1)
                    .map(Map.Entry::getKey)
                    .toList();
            assertTrue(duplicated.isEmpty(), resource + " declares a key twice: " + duplicated);
        }
    }

    @Test
    @DisplayName("no empty value and no leftover TODO")
    void valuesAreTranslated() {
        for (String resource : List.of(VI_FILE, EN_FILE)) {
            Properties properties = load(resource);
            for (String key : new TreeSet<>(properties.stringPropertyNames())) {
                String value = properties.getProperty(key);
                assertTrue(value != null && !value.isBlank(), resource + " has an empty value for " + key);
                assertTrue(!value.contains("TODO"), resource + " still has a TODO in " + key);
            }
        }
    }

    @Test
    @DisplayName("both languages resolve through the MessageSource")
    void resolvesInBothLanguages() {
        for (String key : new TreeSet<>(load(VI_FILE).stringPropertyNames())) {
            for (Locale locale : List.of(Locale.forLanguageTag("vi"), Locale.ENGLISH)) {
                String text = messageSource.getMessage(key, null, locale);
                assertTrue(text != null && !text.isBlank(), key + " does not resolve for " + locale);
            }
        }
    }

    @Test
    @DisplayName("an unsupported locale falls back to Vietnamese, never to a raw key")
    void unknownLocaleFallsBack() {
        String key = "lang.label";
        assertEquals(load(VI_FILE).getProperty(key),
                messageSource.getMessage(key, null, Locale.FRENCH));
    }
}
