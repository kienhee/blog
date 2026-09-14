package com.kienhee.blog.config;

import lombok.Getter;
import lombok.Setter;
import org.springframework.boot.context.properties.ConfigurationProperties;
import org.springframework.stereotype.Component;

@Component
@ConfigurationProperties(prefix = "app.upload")
@Getter
@Setter
public class UploadProperties {

    /**
     * Directory (relative or absolute) where uploaded media files are stored on disk.
     * Kept outside src/main/resources/static so files survive rebuilds/repackaging.
     */
    private String dir = "./uploads";
}
