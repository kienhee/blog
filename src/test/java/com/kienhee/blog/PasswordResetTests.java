package com.kienhee.blog;

import com.kienhee.blog.entity.PasswordResetToken;
import com.kienhee.blog.entity.Role;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.PasswordResetTokenRepository;
import com.kienhee.blog.repository.RoleRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.MailService;
import com.kienhee.blog.support.TestLocale;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.mockito.ArgumentCaptor;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.test.context.bean.override.mockito.MockitoBean;
import org.springframework.test.web.servlet.MockMvc;
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
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestBuilders.formLogin;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf;
import static org.springframework.security.test.web.servlet.response.SecurityMockMvcResultMatchers.authenticated;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

/** Forgot password → emailed single-use link → new password. The mail service is mocked: nothing is sent. */
@SpringBootTest
@DisplayName("Password reset by email link")
class PasswordResetTests {

    private static final AtomicInteger IP_SEQ = new AtomicInteger();

    @MockitoBean private MailService mailService;
    @Autowired private WebApplicationContext context;
    @Autowired private UserRepository userRepository;
    @Autowired private RoleRepository roleRepository;
    @Autowired private PasswordResetTokenRepository tokenRepository;
    @Autowired private PasswordEncoder passwordEncoder;

    private MockMvc mockMvc;
    private User user;

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity())
                .defaultRequest(TestLocale.englishByDefault()).build();
        Role role = roleRepository.findAll().stream().findFirst().orElseThrow();
        user = userRepository.save(User.builder()
                .fullName("Reset Tester")
                .email("reset" + System.nanoTime() + "@test.com")
                .password(passwordEncoder.encode("OldPassword1"))
                .role(role)
                .build());
    }

    @AfterEach
    void tearDown() {
        userRepository.deleteById(user.getId()); // tokens go with it (ON DELETE CASCADE)
    }

    /** Separate client IPs keep the per-IP limit from leaking between tests. */
    private static RequestPostProcessor freshIp() {
        int n = IP_SEQ.getAndIncrement();
        return request -> {
            request.setRemoteAddr("10.66." + (n / 250) + "." + (n % 250));
            return request;
        };
    }

    private void requestLink(String email) throws Exception {
        mockMvc.perform(post("/auth/forgot").param("email", email).with(csrf()).with(freshIp()))
                .andExpect(status().is3xxRedirection())
                .andExpect(redirectedUrl("/auth/forgot"))
                .andExpect(flash().attribute("successMessage", containsString("If an account exists")));
    }

    /** The raw token from the most recent mocked email to the test user. */
    @SuppressWarnings("unchecked")
    private String lastEmailedToken() {
        ArgumentCaptor<Map<String, Object>> vars = ArgumentCaptor.forClass(Map.class);
        verify(mailService, atLeastOnce()).send(eq(user.getEmail()), any(), eq("password-reset"), anyString(), vars.capture(), any());
        String url = (String) vars.getValue().get("resetUrl");
        assertTrue(url.contains("/auth/reset?token="), url);
        return url.substring(url.indexOf("token=") + "token=".length());
    }

    @Test
    @DisplayName("an existing account gets an email with a link; only the token's hash is stored")
    void emailsLink() throws Exception {
        requestLink(user.getEmail().toUpperCase());

        String token = lastEmailedToken();
        assertEquals(43, token.length());
        List<PasswordResetToken> rows = tokenRepository.findByUser_IdOrderByIdAsc(user.getId());
        assertEquals(1, rows.size());
        assertNotEquals(token, rows.get(0).getTokenHash(), "the raw token is never stored");
        assertEquals(64, rows.get(0).getTokenHash().length());
        assertTrue(rows.get(0).getExpiresAt().isAfter(LocalDateTime.now().plusMinutes(29)));
        assertTrue(passwordEncoder.matches("OldPassword1", userRepository.findById(user.getId()).orElseThrow().getPassword()),
                "requesting a link never changes the password");
    }

    @Test
    @DisplayName("an unknown email gets the same answer and no email")
    void unknownEmailLooksTheSame() throws Exception {
        requestLink("nobody-" + System.nanoTime() + "@test.com");
        verify(mailService, never()).send(anyString(), any(), anyString(), anyString(), anyMap(), any());
    }

    @Test
    @DisplayName("the old hole is closed: a newPassword field is ignored")
    void newPasswordParamIgnored() throws Exception {
        mockMvc.perform(post("/auth/forgot").param("email", user.getEmail()).param("newPassword", "Hacked123")
                        .with(csrf()).with(freshIp()))
                .andExpect(status().is3xxRedirection());
        User reloaded = userRepository.findById(user.getId()).orElseThrow();
        assertFalse(passwordEncoder.matches("Hacked123", reloaded.getPassword()));
        assertFalse(passwordEncoder.matches("12345678", reloaded.getPassword()));
        assertTrue(passwordEncoder.matches("OldPassword1", reloaded.getPassword()));
    }

    @Test
    @DisplayName("the link sets a new password once, then signing in with it works")
    void resetWithLink() throws Exception {
        requestLink(user.getEmail());
        String token = lastEmailedToken();

        mockMvc.perform(get("/auth/reset").param("token", token).with(TestLocale.en()))
                .andExpect(status().isOk())
                .andExpect(header().string("Referrer-Policy", "no-referrer"))
                .andExpect(content().string(containsString("Choose a new password")));

        mockMvc.perform(post("/auth/reset").param("token", token)
                        .param("password", "BrandNewPass9").param("confirmPassword", "BrandNewPass9").with(csrf()))
                .andExpect(status().is3xxRedirection())
                .andExpect(redirectedUrl("/auth/login?reset=true"));

        assertTrue(passwordEncoder.matches("BrandNewPass9", userRepository.findById(user.getId()).orElseThrow().getPassword()));
        assertTrue(tokenRepository.findByUser_IdOrderByIdAsc(user.getId()).stream().allMatch(t -> t.getUsedAt() != null));

        mockMvc.perform(formLogin("/auth/login").userParameter("email").user(user.getEmail()).password("BrandNewPass9"))
                .andExpect(authenticated());

        // single use
        mockMvc.perform(post("/auth/reset").param("token", token)
                        .param("password", "AnotherPass9").param("confirmPassword", "AnotherPass9").with(csrf()).with(TestLocale.en()))
                .andExpect(status().isOk())
                .andExpect(content().string(containsString("Link expired")));
        assertTrue(passwordEncoder.matches("BrandNewPass9", userRepository.findById(user.getId()).orElseThrow().getPassword()));
    }

    @Test
    @DisplayName("expired, tampered and superseded links are refused")
    void badLinks() throws Exception {
        requestLink(user.getEmail());
        String first = lastEmailedToken();
        requestLink(user.getEmail());
        String second = lastEmailedToken();
        assertNotEquals(first, second);

        mockMvc.perform(get("/auth/reset").param("token", first).with(TestLocale.en()))
                .andExpect(content().string(containsString("Link expired")));
        mockMvc.perform(get("/auth/reset").param("token", second.substring(0, 42) + (second.endsWith("A") ? "B" : "A")).with(TestLocale.en()))
                .andExpect(content().string(containsString("Link expired")));
        mockMvc.perform(get("/auth/reset").with(TestLocale.en())).andExpect(content().string(containsString("Link expired")));

        PasswordResetToken open = tokenRepository.findByUser_IdOrderByIdAsc(user.getId()).stream()
                .filter(t -> t.getUsedAt() == null).findFirst().orElseThrow();
        open.setExpiresAt(LocalDateTime.now().minusMinutes(1));
        tokenRepository.save(open);
        mockMvc.perform(post("/auth/reset").param("token", second)
                        .param("password", "BrandNewPass9").param("confirmPassword", "BrandNewPass9").with(csrf()).with(TestLocale.en()))
                .andExpect(content().string(containsString("Link expired")));
        assertTrue(passwordEncoder.matches("OldPassword1", userRepository.findById(user.getId()).orElseThrow().getPassword()));
    }

    @Test
    @DisplayName("mismatched or too-short passwords are refused and the link stays usable")
    void passwordRules() throws Exception {
        requestLink(user.getEmail());
        String token = lastEmailedToken();

        mockMvc.perform(post("/auth/reset").param("token", token)
                        .param("password", "BrandNewPass9").param("confirmPassword", "Different9").with(csrf()))
                .andExpect(status().isOk())
                .andExpect(content().string(containsString("don&#39;t match")));
        mockMvc.perform(post("/auth/reset").param("token", token)
                        .param("password", "short").param("confirmPassword", "short").with(csrf())
                        .with(TestLocale.en()))
                .andExpect(content().string(containsString("8 to 72 characters")));

        assertTrue(passwordEncoder.matches("OldPassword1", userRepository.findById(user.getId()).orElseThrow().getPassword()));
        mockMvc.perform(get("/auth/reset").param("token", token).with(TestLocale.en()))
                .andExpect(content().string(containsString("Choose a new password")));
    }

    @Test
    @DisplayName("at most 3 emails per address in 15 minutes; extra requests look identical")
    void rateLimitedPerEmail() throws Exception {
        for (int i = 0; i < 5; i++) {
            requestLink(user.getEmail());
        }
        verify(mailService, times(3)).send(eq(user.getEmail()), any(), eq("password-reset"), anyString(), anyMap(), any());
    }

    @Test
    @DisplayName("a POST without a CSRF token is rejected")
    void csrfRequired() throws Exception {
        mockMvc.perform(post("/auth/forgot").param("email", user.getEmail()).with(freshIp()))
                .andExpect(status().isForbidden());
        verify(mailService, never()).send(anyString(), any(), anyString(), anyString(), anyMap(), any());
    }
}
