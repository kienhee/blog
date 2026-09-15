package com.kienhee.blog;

import com.kienhee.blog.entity.NewsletterIssue;
import com.kienhee.blog.entity.Subscriber;
import com.kienhee.blog.entity.SubscriberStatus;
import com.kienhee.blog.repository.NewsletterIssueRepository;
import com.kienhee.blog.repository.SubscriberRepository;
import com.kienhee.blog.service.MailService;
import com.kienhee.blog.service.NewsletterService;
import com.kienhee.blog.support.TestAuth;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.mockito.ArgumentCaptor;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.test.context.bean.override.mockito.MockitoBean;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.request.MockHttpServletRequestBuilder;
import org.springframework.test.web.servlet.request.RequestPostProcessor;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import java.time.LocalDateTime;
import java.util.List;
import java.util.Map;
import java.util.concurrent.atomic.AtomicInteger;

import static org.hamcrest.Matchers.containsString;
import static org.junit.jupiter.api.Assertions.*;
import static org.mockito.ArgumentMatchers.*;
import static org.mockito.Mockito.*;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

/** Newsletter signup, confirmation, unsubscribe and sending. Mail is mocked; nothing is sent. */
@SpringBootTest
@DisplayName("Newsletter")
class NewsletterTests {

    private static final AtomicInteger IP_SEQ = new AtomicInteger();

    @MockitoBean private MailService mailService;
    @Autowired private WebApplicationContext context;
    @Autowired private SubscriberRepository subscriberRepository;
    @Autowired private NewsletterIssueRepository issueRepository;
    @Autowired private JdbcTemplate jdbc;

    private MockMvc mockMvc;
    private final String tag = "news" + System.nanoTime();

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity()).build();
    }

    @AfterEach
    void tearDown() {
        jdbc.update("delete from subscribers where email like ?", tag + "%");
        jdbc.update("delete from newsletter_issues where subject like ?", "%" + tag + "%");
    }

    private String email(String name) {
        return tag + "-" + name + "@test.com";
    }

    private static RequestPostProcessor freshIp() {
        int n = IP_SEQ.getAndIncrement();
        return request -> {
            request.setRemoteAddr("10.44." + (n / 250) + "." + (n % 250));
            return request;
        };
    }

    private MockHttpServletRequestBuilder signup(String address) {
        return post("/subscribe").param("email", address).with(csrf()).with(freshIp());
    }

    @SuppressWarnings("unchecked")
    private Map<String, Object> lastMail(String to, String template) {
        ArgumentCaptor<Map<String, Object>> vars = ArgumentCaptor.forClass(Map.class);
        verify(mailService, atLeastOnce()).send(eq(to), anyString(), eq(template), vars.capture());
        return vars.getValue();
    }

    private static String tokenOf(Object url) {
        String s = (String) url;
        return s.substring(s.indexOf("token=") + "token=".length());
    }

    private Subscriber confirmedSubscriber(String address) {
        return subscriberRepository.save(Subscriber.builder().email(address).status(SubscriberStatus.CONFIRMED)
                .unsubscribeToken(("u" + System.nanoTime() + "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx").substring(0, 43))
                .createdAt(LocalDateTime.now()).confirmedAt(LocalDateTime.now()).build());
    }

    @Test
    @DisplayName("sign up → confirmation email → link confirms the subscription")
    void doubleOptIn() throws Exception {
        String address = email("reader");
        mockMvc.perform(signup(address))
                .andExpect(redirectedUrl("/subscribe"))
                .andExpect(flash().attribute("newsletterSuccess", NewsletterService.SUBSCRIBE_MESSAGE));

        Subscriber pending = subscriberRepository.findByEmail(address).orElseThrow();
        assertEquals(SubscriberStatus.PENDING, pending.getStatus());
        String token = tokenOf(lastMail(address, "newsletter-confirm").get("confirmUrl"));
        assertNotEquals(token, pending.getConfirmTokenHash(), "only the hash is stored");

        mockMvc.perform(get("/subscribe/confirm").param("token", token))
                .andExpect(status().isOk())
                .andExpect(header().string("Referrer-Policy", "no-referrer"))
                .andExpect(content().string(containsString("You are subscribed")));
        assertEquals(SubscriberStatus.CONFIRMED, subscriberRepository.findByEmail(address).orElseThrow().getStatus());

        mockMvc.perform(get("/subscribe/confirm").param("token", token))
                .andExpect(content().string(containsString("Link expired")));
    }

    @Test
    @DisplayName("an address already confirmed gets the same answer and no email; the home form returns home")
    void alreadyConfirmed() throws Exception {
        String address = email("known");
        confirmedSubscriber(address);
        mockMvc.perform(signup(address).param("from", "home"))
                .andExpect(redirectedUrl("/#newsletter"))
                .andExpect(flash().attribute("newsletterSuccess", NewsletterService.SUBSCRIBE_MESSAGE));
        verify(mailService, never()).send(eq(address), anyString(), anyString(), anyMap());
    }

    @Test
    @DisplayName("invalid emails are refused; the honeypot silently drops bots")
    void invalidAndBots() throws Exception {
        mockMvc.perform(post("/subscribe").param("email", "not-an-email").with(csrf()).with(freshIp()))
                .andExpect(flash().attribute("newsletterError", "Enter a valid email address."));
        String bot = email("bot");
        mockMvc.perform(signup(bot).param("website", "http://spam.example"))
                .andExpect(flash().attribute("newsletterSuccess", NewsletterService.SUBSCRIBE_MESSAGE));
        assertTrue(subscriberRepository.findByEmail(bot).isEmpty());
    }

    @Test
    @DisplayName("an expired confirmation link is refused")
    void expiredLink() throws Exception {
        String address = email("late");
        mockMvc.perform(signup(address));
        String token = tokenOf(lastMail(address, "newsletter-confirm").get("confirmUrl"));
        Subscriber s = subscriberRepository.findByEmail(address).orElseThrow();
        s.setConfirmSentAt(LocalDateTime.now().minusDays(8));
        subscriberRepository.save(s);

        mockMvc.perform(get("/subscribe/confirm").param("token", token)).andExpect(content().string(containsString("Link expired")));
        assertEquals(SubscriberStatus.PENDING, subscriberRepository.findByEmail(address).orElseThrow().getStatus());
    }

    @Test
    @DisplayName("unsubscribing takes a button press; signing up again starts a new confirmation")
    void unsubscribeAndReturn() throws Exception {
        String address = email("leaver");
        Subscriber s = confirmedSubscriber(address);

        mockMvc.perform(get("/subscribe/unsubscribe").param("token", s.getUnsubscribeToken()))
                .andExpect(content().string(containsString("Unsubscribe?")));
        assertEquals(SubscriberStatus.CONFIRMED, subscriberRepository.findByEmail(address).orElseThrow().getStatus(),
                "opening the link alone changes nothing");

        mockMvc.perform(post("/subscribe/unsubscribe").param("token", s.getUnsubscribeToken()).with(csrf()))
                .andExpect(content().string(containsString("You are unsubscribed")));
        assertEquals(SubscriberStatus.UNSUBSCRIBED, subscriberRepository.findByEmail(address).orElseThrow().getStatus());

        mockMvc.perform(get("/subscribe/unsubscribe").param("token", "nope")).andExpect(content().string(containsString("Link expired")));

        mockMvc.perform(signup(address));
        assertEquals(SubscriberStatus.PENDING, subscriberRepository.findByEmail(address).orElseThrow().getStatus());
        verify(mailService).send(eq(address), anyString(), eq("newsletter-confirm"), anyMap());
    }

    @Test
    @DisplayName("at most 3 confirmation emails per address in 15 minutes")
    void rateLimited() throws Exception {
        String address = email("eager");
        for (int i = 0; i < 5; i++) {
            mockMvc.perform(signup(address));
        }
        verify(mailService, times(3)).send(eq(address), anyString(), eq("newsletter-confirm"), anyMap());
    }

    @Test
    @DisplayName("admins send an issue to confirmed subscribers, each with their own unsubscribe link")
    @SuppressWarnings("unchecked")
    void sendIssue() throws Exception {
        Subscriber reader = confirmedSubscriber(email("confirmed"));
        String pendingAddress = email("pending");
        subscriberRepository.save(Subscriber.builder().email(pendingAddress).status(SubscriberStatus.PENDING)
                .unsubscribeToken(("p" + System.nanoTime() + "yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy").substring(0, 43))
                .createdAt(LocalDateTime.now()).build());

        mockMvc.perform(post("/admin/subscribers/send").with(csrf())
                        .with(TestAuth.withPermissions("viewer@test.com", "subscribers:view"))
                        .param("subject", "Issue " + tag).param("body", "Hello readers"))
                .andExpect(status().isForbidden());

        mockMvc.perform(post("/admin/subscribers/send").with(csrf()).with(TestAuth.owner())
                        .param("subject", "Issue " + tag).param("body", "First paragraph here.\n\nSecond <b>paragraph</b>."))
                .andExpect(redirectedUrl("/admin/subscribers"))
                .andExpect(flash().attribute("successMessage", containsString("Sending \"Issue " + tag + "\" to")));

        ArgumentCaptor<Map<String, Object>> vars = ArgumentCaptor.forClass(Map.class);
        verify(mailService).send(eq(reader.getEmail()), eq("Issue " + tag), eq("newsletter-issue"), vars.capture());
        assertEquals(List.of("First paragraph here.", "Second <b>paragraph</b>."), vars.getValue().get("paragraphs"));
        assertTrue(((String) vars.getValue().get("unsubscribeUrl")).endsWith("token=" + reader.getUnsubscribeToken()));
        verify(mailService, never()).send(eq(pendingAddress), anyString(), eq("newsletter-issue"), anyMap());

        NewsletterIssue issue = issueRepository.findAllWithSender().stream()
                .filter(i -> i.getSubject().equals("Issue " + tag)).findFirst().orElseThrow();
        assertTrue(issue.getRecipients() >= 1);

        mockMvc.perform(post("/admin/subscribers/send").with(csrf()).with(TestAuth.owner())
                        .param("subject", "x").param("body", "short"))
                .andExpect(flash().attribute("errorMessage", "The subject must be 3 to 200 characters."));
    }

    @Test
    @DisplayName("the admin page lists subscribers; removing one needs subscribers:delete")
    void adminListAndDelete() throws Exception {
        Subscriber s = confirmedSubscriber(email("listed"));
        mockMvc.perform(get("/admin/subscribers").with(TestAuth.withPermissions("viewer@test.com", "subscribers:view")))
                .andExpect(status().isOk())
                .andExpect(content().string(containsString(s.getEmail())));
        mockMvc.perform(get("/admin/subscribers").with(TestAuth.withPermissions("nobody@test.com", "posts:view")))
                .andExpect(status().isForbidden());

        mockMvc.perform(post("/admin/subscribers/" + s.getId() + "/delete").with(csrf())
                        .with(TestAuth.withPermissions("viewer@test.com", "subscribers:view")))
                .andExpect(status().isForbidden());
        mockMvc.perform(post("/admin/subscribers/" + s.getId() + "/delete").with(csrf()).with(TestAuth.owner()))
                .andExpect(flash().attribute("successMessage", "Subscriber removed."));
        assertTrue(subscriberRepository.findById(s.getId()).isEmpty());
    }

    @Test
    @DisplayName("the home page and /subscribe show a working signup form")
    void formsWired() throws Exception {
        for (String page : List.of("/", "/subscribe")) {
            mockMvc.perform(get(page))
                    .andExpect(status().isOk())
                    .andExpect(content().string(containsString("action=\"/subscribe\"")))
                    .andExpect(content().string(containsString("name=\"email\"")));
        }
    }
}
