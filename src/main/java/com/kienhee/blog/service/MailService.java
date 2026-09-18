package com.kienhee.blog.service;

import java.util.Locale;
import java.util.Map;

/** Transactional email. Rendering uses Thymeleaf templates under {@code templates/mail/}. */
public interface MailService {

    /**
     * Renders {@code templates/mail/<template>.html} for {@code locale} and sends it asynchronously,
     * so the caller never waits on (or leaks timing through) the SMTP server. Failures are logged,
     * never thrown. When {@code app.mail.enabled} is false nothing is sent.
     *
     * <p>Sending is {@code @Async}, so {@code LocaleContextHolder} is gone by the time the mail is
     * built: the language is passed in. A caller on a request thread hands over
     * {@code LocaleContextHolder.getLocale()}; a caller with no request (a job, or mail to someone
     * whose language we don't store) hands over {@code I18n.DEFAULT}.</p>
     *
     * @param subjectCode message key for the subject, resolved for {@code locale}
     * @param subjectArgs parameters for that subject, if it takes any
     */
    void send(String to, Locale locale, String template, String subjectCode,
              Map<String, Object> variables, Object... subjectArgs);

    /**
     * Same, for a subject a person typed — a newsletter issue — which is not in the catalogue and
     * is sent exactly as written.
     */
    void sendWritten(String to, Locale locale, String template, String subject, Map<String, Object> variables);
}
