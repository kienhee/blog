package com.kienhee.blog.service.impl;

import com.kienhee.blog.entity.SiteSetting;
import com.kienhee.blog.repository.SiteSettingRepository;
import com.kienhee.blog.service.SettingService;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.LinkedHashMap;
import java.util.Map;
import java.util.Optional;
import java.util.TreeMap;
import java.util.regex.Pattern;

@Service
@RequiredArgsConstructor
public class SettingServiceImpl implements SettingService {

    /** Lowercase segments joined by . _ or - (site.title, blog.posts_per_page, social.x-handle). */
    public static final Pattern KEY_PATTERN = Pattern.compile("^[a-z0-9]+(?:[._-][a-z0-9]+)*$");
    public static final int MAX_KEY_LENGTH = 100;
    /** setting_value is a MySQL TEXT column. */
    public static final int MAX_VALUE_LENGTH = 65_535;

    private final SiteSettingRepository repository;

    @Override
    @Transactional(readOnly = true)
    public Map<String, String> getAll() {
        Map<String, String> all = new TreeMap<>();
        for (SiteSetting setting : repository.findAll()) {
            all.put(setting.getKey(), setting.getValue() != null ? setting.getValue() : "");
        }
        return all;
    }

    @Override
    @Transactional(readOnly = true)
    public Optional<String> get(String key) {
        return key == null ? Optional.empty() : repository.findById(key).map(SiteSetting::getValue);
    }

    @Override
    @Transactional(readOnly = true)
    public String get(String key, String defaultValue) {
        return get(key).filter(v -> !v.isBlank()).orElse(defaultValue);
    }

    @Override
    @Transactional(readOnly = true)
    public int getInt(String key, int defaultValue) {
        try {
            return get(key).map(String::trim).filter(v -> !v.isEmpty()).map(Integer::parseInt).orElse(defaultValue);
        } catch (NumberFormatException e) {
            return defaultValue;
        }
    }

    @Override
    @Transactional
    public int saveAll(Map<String, String> values) {
        if (values == null || values.isEmpty()) {
            throw new IllegalArgumentException("Nothing to save.");
        }

        // Validate everything before writing anything, so one bad field never leaves a half-saved form.
        Map<String, String> clean = new LinkedHashMap<>();
        for (Map.Entry<String, String> entry : values.entrySet()) {
            String key = entry.getKey() != null ? entry.getKey().trim() : "";
            if (key.isEmpty() || key.length() > MAX_KEY_LENGTH || !KEY_PATTERN.matcher(key).matches()) {
                throw new IllegalArgumentException("Invalid setting key \"" + key
                        + "\". Use lowercase letters, digits and . _ - (for example site.title).");
            }
            String value = entry.getValue() != null ? entry.getValue().trim() : "";
            if (value.length() > MAX_VALUE_LENGTH) {
                throw new IllegalArgumentException("The value for \"" + key + "\" is too long.");
            }
            clean.put(key, value);
        }

        clean.forEach((key, value) -> {
            SiteSetting setting = repository.findById(key).orElseGet(() -> SiteSetting.builder().key(key).build());
            setting.setValue(value);
            repository.save(setting);
        });
        return clean.size();
    }
}
