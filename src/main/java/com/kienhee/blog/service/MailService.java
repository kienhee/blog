package com.kienhee.blog.service;

import java.util.Map;

/** Transactional email. Rendering uses Thymeleaf templates under {@code templates/mail/}. */
public interface MailService {

    /**
     * Renders {@code templates/mail/<template>.html} with {@code variables} and sends it asynchronously,
     * so the caller never waits on (or leaks timing through) the SMTP server. Failures are logged,
     * never thrown. When {@code app.mail.enabled} is false nothing is sent.
     */
    void send(String to, String subject, String template, Map<String, Object> variables);
}
