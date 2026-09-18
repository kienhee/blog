package com.kienhee.blog;

import com.kienhee.blog.entity.Role;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.entity.UserStatus;
import com.kienhee.blog.repository.RoleRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.MailService;
import com.kienhee.blog.service.RegistrationPolicy;
import com.kienhee.blog.support.TestAuth;
import com.kienhee.blog.support.TestLocale;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.test.context.bean.override.mockito.MockitoBean;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import java.util.ArrayList;
import java.util.List;

import static org.hamcrest.Matchers.containsString;
import static org.junit.jupiter.api.Assertions.*;
import static org.mockito.ArgumentMatchers.*;
import static org.mockito.Mockito.*;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestBuilders.formLogin;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf;
import static org.springframework.security.test.web.servlet.response.SecurityMockMvcResultMatchers.authenticated;
import static org.springframework.security.test.web.servlet.response.SecurityMockMvcResultMatchers.unauthenticated;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

/**
 * Sign-up: the very first account becomes an active Admin; everyone after that gets the User role and waits for an
 * admin to approve them. {@link RegistrationPolicy} is mocked because the dev database already has accounts; mail
 * is mocked so nothing is sent.
 */
@SpringBootTest
@DisplayName("Registration and account approval")
class RegistrationTests {

    @MockitoBean private RegistrationPolicy registrationPolicy;
    @MockitoBean private MailService mailService;
    @Autowired private WebApplicationContext context;
    @Autowired private UserRepository userRepository;
    @Autowired private RoleRepository roleRepository;
    @Autowired private PasswordEncoder passwordEncoder;

    private MockMvc mockMvc;
    private final List<String> emails = new ArrayList<>();
    private final String tag = "signup" + System.nanoTime();

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity())
                .defaultRequest(TestLocale.englishByDefault()).build();
    }

    @AfterEach
    void tearDown() {
        emails.forEach(e -> userRepository.findByEmail(e).ifPresent(userRepository::delete));
    }

    private String email(String name) {
        String e = tag + "-" + name + "@test.com";
        emails.add(e);
        return e;
    }

    private void register(String email) throws Exception {
        mockMvc.perform(post("/auth/register").with(csrf())
                .param("fullName", "New Person").param("email", email).param("password", "Password123"));
    }

    private User saved(String name, String roleSlug, UserStatus status) {
        Role role = roleRepository.findBySlug(roleSlug).orElseThrow();
        return userRepository.save(User.builder().fullName("Account " + name).email(email(name))
                .password(passwordEncoder.encode("Password123")).role(role).status(status).build());
    }

    @Test
    @DisplayName("the first account is an active Admin and can sign in right away")
    void firstAccount() throws Exception {
        when(registrationPolicy.isFirstAccount()).thenReturn(true);
        String address = email("owner");

        mockMvc.perform(post("/auth/register").with(csrf())
                        .param("fullName", "Site Owner").param("email", address.toUpperCase()).param("password", "Password123"))
                .andExpect(redirectedUrl("/auth/login?registered=true"));

        User created = userRepository.findByEmailWithRole(address).orElseThrow();
        assertEquals("admin", created.getRole().getSlug());
        assertEquals(UserStatus.ACTIVE, created.getStatus());
        mockMvc.perform(formLogin("/auth/login").userParameter("email").user(address).password("Password123"))
                .andExpect(authenticated());
        verify(mailService, never()).send(anyString(), any(), eq("account-pending-admin"), anyString(), anyMap());
    }

    @Test
    @DisplayName("later accounts are Users waiting for approval; admins are emailed")
    void laterAccountsWait() throws Exception {
        when(registrationPolicy.isFirstAccount()).thenReturn(false);
        saved("approver", "admin", UserStatus.ACTIVE);
        String address = email("newcomer");

        mockMvc.perform(post("/auth/register").with(csrf())
                        .param("fullName", "New Person").param("email", address).param("password", "Password123"))
                .andExpect(redirectedUrl("/auth/login?registered=pending"));
        mockMvc.perform(get("/auth/login").param("registered", "pending"))
                .andExpect(content().string(containsString("An administrator needs to approve it")));

        User created = userRepository.findByEmailWithRole(address).orElseThrow();
        assertEquals("user", created.getRole().getSlug());
        assertEquals(UserStatus.PENDING, created.getStatus());
        verify(mailService, atLeastOnce()).send(anyString(), any(), eq("account-pending-admin"), anyString(), anyMap());
    }

    @Test
    @DisplayName("a pending account can't sign in; only the right password reveals why")
    void pendingCantSignIn() throws Exception {
        User pending = saved("pending", "user", UserStatus.PENDING);

        mockMvc.perform(formLogin("/auth/login").userParameter("email").user(pending.getEmail()).password("Password123"))
                .andExpect(unauthenticated())
                .andExpect(redirectedUrl("/auth/login?pending=true"));
        mockMvc.perform(formLogin("/auth/login").userParameter("email").user(pending.getEmail()).password("WrongPassword1"))
                .andExpect(unauthenticated())
                .andExpect(redirectedUrl("/auth/login?error=true"));
        mockMvc.perform(get("/auth/login").param("pending", "true"))
                .andExpect(content().string(containsString("waiting for an administrator")));
    }

    @Test
    @DisplayName("an admin approves, disables and re-enables an account; the person is emailed on approval")
    void approveDisableEnable() throws Exception {
        User person = saved("person", "user", UserStatus.PENDING);
        String path = "/admin/users/" + person.getId() + "/status";

        mockMvc.perform(post(path).param("status", "ACTIVE").with(csrf()).with(TestAuth.owner()))
                .andExpect(redirectedUrl("/admin/users"))
                .andExpect(flash().attribute("successMessage", "Account approved."));
        assertEquals(UserStatus.ACTIVE, userRepository.findById(person.getId()).orElseThrow().getStatus());
        verify(mailService).send(eq(person.getEmail()), any(), eq("account-approved"), anyString(), anyMap());
        mockMvc.perform(formLogin("/auth/login").userParameter("email").user(person.getEmail()).password("Password123"))
                .andExpect(authenticated());

        mockMvc.perform(post(path).param("status", "DISABLED").with(csrf()).with(TestAuth.owner()))
                .andExpect(flash().attribute("successMessage", "Account disabled."));
        mockMvc.perform(formLogin("/auth/login").userParameter("email").user(person.getEmail()).password("Password123"))
                .andExpect(unauthenticated())
                .andExpect(redirectedUrl("/auth/login?disabled=true"));

        mockMvc.perform(post(path).param("status", "ACTIVE").with(csrf()).with(TestAuth.owner()))
                .andExpect(flash().attribute("successMessage", "Account enabled."));
        verify(mailService, times(1)).send(eq(person.getEmail()), any(), eq("account-approved"), anyString(), anyMap());
    }

    @Test
    @DisplayName("nobody changes their own status; only admins change an admin's; bad values are refused")
    void statusRules() throws Exception {
        User manager = saved("manager", "user", UserStatus.ACTIVE);
        User anAdmin = saved("other-admin", "admin", UserStatus.ACTIVE);

        mockMvc.perform(post("/admin/users/" + manager.getId() + "/status").param("status", "DISABLED").with(csrf())
                        .with(TestAuth.withPermissions(manager.getEmail(), "users:view", "users:edit", "roles:edit")))
                .andExpect(flash().attribute("errorMessage", "You can't change the status of your own account."));
        mockMvc.perform(post("/admin/users/" + anAdmin.getId() + "/status").param("status", "DISABLED").with(csrf())
                        .with(TestAuth.withPermissions(manager.getEmail(), "users:view", "users:edit")))
                .andExpect(flash().attribute("errorMessage", containsString("Only admins")));
        mockMvc.perform(post("/admin/users/" + manager.getId() + "/status").param("status", "BANANA").with(csrf()).with(TestAuth.owner()))
                .andExpect(flash().attribute("errorMessage", "Unknown account status."));
        mockMvc.perform(post("/admin/users/" + manager.getId() + "/status").param("status", "DISABLED").with(csrf())
                        .with(TestAuth.withPermissions(manager.getEmail(), "users:view")))
                .andExpect(status().isForbidden());

        assertEquals(UserStatus.ACTIVE, userRepository.findById(manager.getId()).orElseThrow().getStatus());
        assertEquals(UserStatus.ACTIVE, userRepository.findById(anAdmin.getId()).orElseThrow().getStatus());
    }

    @Test
    @DisplayName("the Users page flags pending accounts; sign-up is always offered")
    void pagesShowStatus() throws Exception {
        User pending = saved("listed", "user", UserStatus.PENDING);
        mockMvc.perform(get("/admin/users").with(TestAuth.owner()).with(TestLocale.en()))
                .andExpect(content().string(containsString("waiting for approval")))
                .andExpect(content().string(containsString("data-status=\"ACTIVE\"")))
                .andExpect(content().string(containsString(pending.getEmail())));
        mockMvc.perform(get("/auth/login").with(TestLocale.en())).andExpect(content().string(containsString("Create an account")));
        when(registrationPolicy.isFirstAccount()).thenReturn(false);
        mockMvc.perform(get("/auth/register").with(TestLocale.en()))
                .andExpect(status().isOk())
                .andExpect(content().string(containsString("An administrator reviews new accounts")));
    }
}
