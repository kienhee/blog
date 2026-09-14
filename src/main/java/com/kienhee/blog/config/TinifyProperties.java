package com.kienhee.blog.config;

import lombok.Getter;
import lombok.Setter;
import org.springframework.boot.context.properties.ConfigurationProperties;
import org.springframework.stereotype.Component;

@Component
@ConfigurationProperties(prefix = "tinify")
@Getter
@Setter
public class TinifyProperties {

    /**
     * TinyPNG (tinify.com) API key. Leave blank to disable image optimization —
     * uploads then fall back to storing the original, unoptimized file.
     */
    private String apiKey = "";

    public boolean isEnabled() {
        return apiKey != null && !apiKey.isBlank();
    }
}
