package com.kienhee.blog;

import com.kienhee.blog.entity.Role;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.RoleRepository;
import com.kienhee.blog.repository.UserRepository;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.ResultActions;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import java.util.concurrent.atomic.AtomicInteger;

import static org.hamcrest.Matchers.containsString;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf;
import static org.springframework.security.test.web.servlet.response.SecurityMockMvcResultMatchers.authenticated;
import static org.springframework.security.test.web.servlet.response.SecurityMockMvcResultMatchers.unauthenticated;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

/** Brute-force protection on the sign-in form. Each test uses its own email and client IPs. */
@SpringBootTest
@DisplayName("Sign-in throttling")
class LoginThrottleTests {

    private static final AtomicInteger IP_SEQ = new AtomicInteger();

    @Autowired private WebApplicationContext context;
    @Autowired private UserRepository userRepository;
    @Autowired private RoleRepository roleRepository;
    @Autowired private PasswordEncoder passwordEncoder;

    private MockMvc mockMvc;
    private User user;

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity()).build();
        Role role = roleRepository.findBySlug("user").orElseThrow();
        user = userRepository.save(User.builder().fullName("Throttle Tester").email("throttle" + System.nanoTime() + "@test.com")
                .password(passwordEncoder.encode("RightPassword1")).role(role).build());
    }

    @AfterEach
    void tearDown() {
        userRepository.deleteById(user.getId());
    }

    private static String freshIp() {
        int n = IP_SEQ.getAndIncrement();
        return "10.55." + (n / 250) + "." + (n % 250);
    }

    private ResultActions login(String email, String password, String ip) throws Exception {
        return mockMvc.perform(post("/auth/login").param("email", email).param("password", password).with(csrf())
                .with(request -> {
                    request.setRemoteAddr(ip);
                    return request;
                }));
    }

    @Test
    @DisplayName("after 10 wrong passwords for an email, even the right password is refused")
    void blocksEmailAfterTenFailures() throws Exception {
        for (int i = 0; i < 10; i++) {
            login(user.getEmail(), "wrong-" + i, freshIp()).andExpect(redirectedUrl("/auth/login?error=true"));
        }
        login(user.getEmail(), "RightPassword1", freshIp())
                .andExpect(redirectedUrl("/auth/login?locked=true"))
                .andExpect(unauthenticated());

        mockMvc.perform(get("/auth/login").param("locked", "true"))
                .andExpect(content().string(containsString("Too many failed sign-in attempts")));
    }

    @Test
    @DisplayName("a successful sign-in resets the email's failure count")
    void successResets() throws Exception {
        for (int i = 0; i < 9; i++) {
            login(user.getEmail(), "wrong-" + i, freshIp()).andExpect(redirectedUrl("/auth/login?error=true"));
        }
        login(user.getEmail(), "RightPassword1", freshIp()).andExpect(authenticated());
        for (int i = 0; i < 9; i++) {
            login(user.getEmail(), "wrong-again-" + i, freshIp()).andExpect(redirectedUrl("/auth/login?error=true"));
        }
        login(user.getEmail(), "RightPassword1", freshIp()).andExpect(authenticated());
    }

    @Test
    @DisplayName("30 failures from one IP block that IP for every email")
    void blocksIpAfterThirtyFailures() throws Exception {
        String ip = freshIp();
        for (int i = 0; i < 30; i++) {
            login("nobody" + i + "-" + System.nanoTime() + "@test.com", "wrong", ip).andExpect(redirectedUrl("/auth/login?error=true"));
        }
        login(user.getEmail(), "RightPassword1", ip).andExpect(redirectedUrl("/auth/login?locked=true"));
        login(user.getEmail(), "RightPassword1", freshIp()).andExpect(authenticated());
    }
}
