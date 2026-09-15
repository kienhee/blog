package com.kienhee.blog.service.impl;

import com.icegreen.greenmail.junit5.GreenMailExtension;
import com.icegreen.greenmail.util.ServerSetupTest;
import com.kienhee.blog.config.AppMailProperties;
import jakarta.mail.Address;
import jakarta.mail.Message;
import jakarta.mail.Session;
import jakarta.mail.internet.InternetAddress;
import jakarta.mail.internet.MimeMessage;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Nested;
import org.junit.jupiter.api.Test;
import org.junit.jupiter.api.extension.RegisterExtension;
import org.mockito.ArgumentCaptor;
import org.springframework.beans.factory.ObjectProvider;
import org.springframework.mail.MailSendException;
import org.springframework.mail.javamail.JavaMailSender;
import org.springframework.mail.javamail.JavaMailSenderImpl;
import org.springframework.scheduling.annotation.Async;
import org.thymeleaf.ITemplateEngine;
import org.thymeleaf.spring6.SpringTemplateEngine;
import org.thymeleaf.templatemode.TemplateMode;
import org.thymeleaf.templateresolver.ClassLoaderTemplateResolver;

import java.util.Map;
import java.util.Properties;

import static org.junit.jupiter.api.Assertions.*;
import static org.mockito.ArgumentMatchers.any;
import static org.mockito.ArgumentMatchers.anyString;
import static org.mockito.Mockito.*;

/**
 * {@link MailServiceImpl} without Spring: a mocked {@link JavaMailSender} for the decision logic, and an
 * in-memory GreenMail SMTP server for what actually goes over the wire. Nothing is ever sent for real.
 */
@DisplayName("MailService (unit)")
class MailServiceImplTests {

    private static final String RESET_URL = "http://localhost:8080/auth/reset?token=abcDEF123_-xyz";

    /** The real mail templates from src/main/resources/templates, rendered like in the app. */
    private static ITemplateEngine templateEngine() {
        ClassLoaderTemplateResolver resolver = new ClassLoaderTemplateResolver();
        resolver.setPrefix("templates/");
        resolver.setSuffix(".html");
        resolver.setTemplateMode(TemplateMode.HTML);
        resolver.setCharacterEncoding("UTF-8");
        resolver.setCacheable(false);
        SpringTemplateEngine engine = new SpringTemplateEngine();
        engine.setTemplateResolver(resolver);
        return engine;
    }

    private static AppMailProperties properties(boolean enabled) {
        AppMailProperties properties = new AppMailProperties();
        properties.setEnabled(enabled);
        properties.setFrom("no-reply@kienhee.com");
        properties.setFromName("Kienhee");
        return properties;
    }

    @SuppressWarnings("unchecked")
    private static ObjectProvider<JavaMailSender> provider(JavaMailSender sender) {
        ObjectProvider<JavaMailSender> provider = mock(ObjectProvider.class);
        when(provider.getIfAvailable()).thenReturn(sender);
        return provider;
    }

    private static Map<String, Object> resetVariables(String name) {
        return Map.of("name", name, "resetUrl", RESET_URL, "minutes", 30L, "siteName", "Kienhee");
    }

    @Nested
    @DisplayName("sending decisions (mocked JavaMailSender)")
    class Decisions {

        private JavaMailSender sender;

        @BeforeEach
        void setUp() {
            sender = mock(JavaMailSender.class);
            when(sender.createMimeMessage()).thenAnswer(invocation -> new MimeMessage((Session) null));
        }

        @Test
        @DisplayName("mail disabled: nothing is rendered or sent")
        void disabledSendsNothing() {
            ITemplateEngine engine = spy(templateEngine());
            ObjectProvider<JavaMailSender> provider = provider(sender);
            MailServiceImpl service = new MailServiceImpl(provider, engine, properties(false));

            service.send("reader@example.com", "Reset your Kienhee password", "password-reset", resetVariables("Reader"));

            verifyNoInteractions(sender);
            verify(provider, never()).getIfAvailable();
            verify(engine, never()).process(anyString(), any());
        }

        @Test
        @DisplayName("mail enabled but no SMTP sender configured: logs and returns, never throws")
        void noSenderDoesNotThrow() {
            MailServiceImpl service = new MailServiceImpl(provider(null), templateEngine(), properties(true));
            assertDoesNotThrow(() ->
                    service.send("reader@example.com", "Subject", "password-reset", resetVariables("Reader")));
        }

        @Test
        @DisplayName("builds the message: from name and address, recipient, subject and HTML body with the link")
        void buildsMessage() throws Exception {
            MailServiceImpl service = new MailServiceImpl(provider(sender), templateEngine(), properties(true));

            service.send("reader@example.com", "Reset your Kienhee password", "password-reset", resetVariables("Minh Tran"));

            ArgumentCaptor<MimeMessage> captor = ArgumentCaptor.forClass(MimeMessage.class);
            verify(sender).send(captor.capture());
            MimeMessage message = captor.getValue();
            // A real JavaMailSender calls saveChanges() while sending; that is what writes Content-Type.
            message.saveChanges();

            InternetAddress from = (InternetAddress) message.getFrom()[0];
            assertEquals("no-reply@kienhee.com", from.getAddress());
            assertEquals("Kienhee", from.getPersonal());
            Address[] to = message.getRecipients(Message.RecipientType.TO);
            assertEquals(1, to.length);
            assertEquals("reader@example.com", ((InternetAddress) to[0]).getAddress());
            assertEquals("Reset your Kienhee password", message.getSubject());

            String html = (String) message.getContent();
            assertTrue(message.getContentType().startsWith("text/html"), message.getContentType());
            assertTrue(html.contains("Minh Tran"), "greets by name");
            assertTrue(html.contains("href=\"" + RESET_URL + "\""), "button links to the reset URL");
            assertTrue(html.contains(">" + RESET_URL + "<"), "plain-text copy of the link for broken buttons");
            assertTrue(html.contains("30 minutes"), "states the expiry");
        }

        @Test
        @DisplayName("user-controlled values are HTML-escaped in the email")
        void escapesName() throws Exception {
            MailServiceImpl service = new MailServiceImpl(provider(sender), templateEngine(), properties(true));

            service.send("reader@example.com", "Subject", "password-reset", resetVariables("<script>alert(1)</script>"));

            ArgumentCaptor<MimeMessage> captor = ArgumentCaptor.forClass(MimeMessage.class);
            verify(sender).send(captor.capture());
            String html = (String) captor.getValue().getContent();
            assertFalse(html.contains("<script>"));
            assertTrue(html.contains("&lt;script&gt;"));
        }

        @Test
        @DisplayName("an SMTP failure is swallowed (logged), so a request never fails because of mail")
        void smtpFailureIsSwallowed() {
            doThrow(new MailSendException("535 Authentication failed")).when(sender).send(any(MimeMessage.class));
            MailServiceImpl service = new MailServiceImpl(provider(sender), templateEngine(), properties(true));

            assertDoesNotThrow(() ->
                    service.send("reader@example.com", "Subject", "password-reset", resetVariables("Reader")));
            verify(sender).send(any(MimeMessage.class));
        }

        @Test
        @DisplayName("a missing template is swallowed and nothing is sent")
        void missingTemplate() {
            MailServiceImpl service = new MailServiceImpl(provider(sender), templateEngine(), properties(true));

            assertDoesNotThrow(() -> service.send("reader@example.com", "Subject", "does-not-exist", Map.of()));
            verify(sender, never()).send(any(MimeMessage.class));
        }

        @Test
        @DisplayName("send runs asynchronously (@Async), so callers never wait on the SMTP server")
        void sendIsAsync() throws Exception {
            assertNotNull(MailServiceImpl.class
                    .getMethod("send", String.class, String.class, String.class, Map.class)
                    .getAnnotation(Async.class));
        }
    }

    @Nested
    @DisplayName("log masking")
    class Masking {

        @Test
        @DisplayName("keeps the first letter and the domain only")
        void masks() {
            assertEquals("k•••@gmail.com", MailServiceImpl.mask("kienhee.it@gmail.com"));
            assertEquals("•••@x.com", MailServiceImpl.mask("a@x.com"));
            assertEquals("•••", MailServiceImpl.mask("not-an-email"));
            assertEquals("(none)", MailServiceImpl.mask(null));
        }
    }

    @Nested
    @DisplayName("over real SMTP (in-memory GreenMail server)")
    class OverSmtp {

        @RegisterExtension
        final GreenMailExtension greenMail = new GreenMailExtension(ServerSetupTest.SMTP);

        private MailServiceImpl service(AppMailProperties properties) {
            JavaMailSenderImpl sender = new JavaMailSenderImpl();
            sender.setHost("127.0.0.1");
            sender.setPort(ServerSetupTest.SMTP.getPort());
            sender.setDefaultEncoding("UTF-8");
            Properties props = new Properties();
            props.put("mail.smtp.connectiontimeout", "5000");
            props.put("mail.smtp.timeout", "5000");
            sender.setJavaMailProperties(props);
            return new MailServiceImpl(provider(sender), templateEngine(), properties);
        }

        @Test
        @DisplayName("the reset email arrives with headers, UTF-8 subject and HTML body intact")
        void deliversMessage() throws Exception {
            AppMailProperties properties = properties(true);
            properties.setFromName("Kiến Hee");

            service(properties).send("reader@example.com", "Đặt lại mật khẩu Kienhee", "password-reset",
                    resetVariables("Trần Minh"));

            assertTrue(greenMail.waitForIncomingEmail(5000, 1), "one message reaches the SMTP server");
            MimeMessage received = greenMail.getReceivedMessages()[0];

            assertEquals("Đặt lại mật khẩu Kienhee", received.getSubject());
            InternetAddress from = (InternetAddress) received.getFrom()[0];
            assertEquals("no-reply@kienhee.com", from.getAddress());
            assertEquals("Kiến Hee", from.getPersonal());
            assertEquals("reader@example.com", ((InternetAddress) received.getAllRecipients()[0]).getAddress());

            String html = (String) received.getContent();
            assertTrue(received.getContentType().toLowerCase().contains("text/html"));
            assertTrue(html.contains("Trần Minh"));
            assertTrue(html.contains(RESET_URL));
        }

        @Test
        @DisplayName("with mail disabled the SMTP server receives nothing")
        void disabledReachesNoServer() {
            service(properties(false)).send("reader@example.com", "Subject", "password-reset", resetVariables("Reader"));
            assertEquals(0, greenMail.getReceivedMessages().length);
        }
    }
}
