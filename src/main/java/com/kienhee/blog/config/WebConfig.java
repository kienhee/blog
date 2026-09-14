package com.kienhee.blog.config;

import org.springframework.context.annotation.Configuration;
import org.springframework.web.servlet.config.annotation.WebMvcConfigurer;

/**
 * MVC customisation hook.
 *
 * <p>There is deliberately <b>no</b> resource handler for the upload directory any more: files are
 * stored under their real names in a folder tree, so a direct {@code /uploads/**} mapping would make
 * them trivially guessable and bypass every access rule. All media bytes are served by
 * {@code MediaFileController} at {@code /media/{id}/{filename}}.</p>
 */
@Configuration
public class WebConfig implements WebMvcConfigurer {
}
