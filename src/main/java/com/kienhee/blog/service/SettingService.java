package com.kienhee.blog.service;

import java.util.Map;
import java.util.Optional;

/**
 * Generic key/value site settings. Keys are free-form (lowercase letters, digits and {@code . _ -},
 * e.g. {@code site.title}), so a new setting only needs a field in the UI — no schema change.
 */
public interface SettingService {

    /** Every stored setting, sorted by key. */
    Map<String, String> getAll();

    Optional<String> get(String key);

    /** The stored value, or {@code defaultValue} when the key is missing or blank. */
    String get(String key, String defaultValue);

    /** The stored value as an int, or {@code defaultValue} when missing, blank or not a number. */
    int getInt(String key, int defaultValue);

    /**
     * Creates or updates every given key. Keys not in the map are left untouched. All keys and
     * values are validated first, so an invalid entry saves nothing.
     *
     * @return how many settings were written
     * @throws IllegalArgumentException for an empty map, an invalid key or a value that is too long
     */
    int saveAll(Map<String, String> values);
}
