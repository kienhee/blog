package com.kienhee.blog.service.impl;

import com.kienhee.blog.config.AppMailProperties;
import com.kienhee.blog.service.MailService;
import jakarta.mail.internet.MimeMessage;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.beans.factory.ObjectProvider;
import org.springframework.mail.javamail.JavaMailSender;
import org.springframework.mail.javamail.MimeMessageHelper;
import org.springframework.scheduling.annotation.Async;
import org.springframework.stereotype.Service;
import org.thymeleaf.ITemplateEngine;
import org.thymeleaf.context.Context;

import java.util.Locale;
import java.util.Map;

@Slf4j
@Service
@RequiredArgsConstructor
public class MailServiceImpl implements MailService {

    private final ObjectProvider<JavaMailSender> mailSender;
    private final ITemplateEngine templateEngine;
    private final AppMailProperties properties;

    @Async
    @Override
    public void send(String to, String subject, String template, Map<String, Object> variables) {
        // Never log the body: it can carry single-use links.
        if (!properties.isEnabled()) {
            log.info("Mail disabled (app.mail.enabled=false): not sending \"{}\" to {}", subject, mask(to));
            return;
        }
        JavaMailSender sender = mailSender.getIfAvailable();
        if (sender == null) {
            log.warn("Mail enabled but no JavaMailSender is configured (spring.mail.host); \"{}\" not sent", subject);
            return;
        }
        try {
            String html = templateEngine.process("mail/" + template, new Context(Locale.ENGLISH, variables));
            MimeMessage message = sender.createMimeMessage();
            MimeMessageHelper helper = new MimeMessageHelper(message, false, "UTF-8");
            helper.setFrom(properties.getFrom(), properties.getFromName());
            helper.setTo(to);
            helper.setSubject(subject);
            helper.setText(html, true);
            sender.send(message);
            log.info("Sent \"{}\" to {}", subject, mask(to));
        } catch (Exception e) {
            log.warn("Could not send \"{}\" to {}: {}", subject, mask(to), e.getMessage());
        }
    }

    /** k•••@gmail.com — enough to recognise in logs without writing full addresses. */
    static String mask(String email) {
        if (email == null) return "(none)";
        int at = email.indexOf('@');
        return at <= 1 ? "•••" + (at >= 0 ? email.substring(at) : "") : email.charAt(0) + "•••" + email.substring(at);
    }
}
