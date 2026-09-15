package com.kienhee.blog.config;

import lombok.Getter;
import lombok.Setter;
import org.springframework.boot.context.properties.ConfigurationProperties;
import org.springframework.stereotype.Component;

/**
 * {@code app.mail.*} — see application.yaml. SMTP host and credentials are Spring's own
 * {@code spring.mail.*}, filled from the git-ignored {@code config/secrets.yaml} or environment variables.
 */
@Getter
@Setter
@Component
@ConfigurationProperties(prefix = "app.mail")
public class AppMailProperties {

    /** When false, emails are only logged (never sent). Tests always run with false. */
    private boolean enabled = false;

    private String from = "no-reply@kienhee.com";

    private String fromName = "Kienhee";

    /** Base for absolute links inside emails, e.g. https://kienhee.com. */
    private String baseUrl = "http://localhost:8080";
}
