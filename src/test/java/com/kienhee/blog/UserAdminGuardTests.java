package com.kienhee.blog;

import com.kienhee.blog.config.AppMailProperties;
import com.kienhee.blog.service.MailService;
import com.kienhee.blog.entity.UserStatus;
import com.kienhee.blog.dto.UserUpdateRequest;
import com.kienhee.blog.entity.Role;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.PostRepository;
import com.kienhee.blog.repository.RoleRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.UserService;
import com.kienhee.blog.service.impl.UserServiceImpl;
import com.kienhee.blog.support.TestAuth;
import com.kienhee.blog.support.TestLocale;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Nested;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import java.util.ArrayList;
import java.util.List;
import java.util.Optional;

import static org.hamcrest.Matchers.containsString;
import static org.junit.jupiter.api.Assertions.*;
import static org.mockito.ArgumentMatchers.any;
import static org.mockito.Mockito.*;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

/** Without admin rights (roles:edit) nobody can grant the Admin role or change an admin, and the last admin stays. */
@DisplayName("Admin accounts are protected")
class UserAdminGuardTests {

    /** Pure service rules with mocked repositories (the last-admin case can't be staged on the shared database). */
    @Nested
    @DisplayName("service rules")
    class ServiceRules {

        private UserRepository users;
        private RoleRepository roles;
        private PostRepository posts;
        private UserServiceImpl service;
        private final Role admin = Role.builder().id(1L).name("Admin").slug("admin").systemRole(true).build();
        private final Role contributor = Role.builder().id(2L).name("User").slug("user").systemRole(false).build();

        @BeforeEach
        void setUp() {
            users = mock(UserRepository.class);
            roles = mock(RoleRepository.class);
            posts = mock(PostRepository.class);
            PasswordEncoder encoder = mock(PasswordEncoder.class);
            service = new UserServiceImpl(users, roles, encoder, posts, mock(MailService.class), new AppMailProperties());
            when(roles.findById(1L)).thenReturn(Optional.of(admin));
            when(roles.findById(2L)).thenReturn(Optional.of(contributor));
            when(users.save(any(User.class))).thenAnswer(inv -> inv.getArgument(0));
        }

        private UserUpdateRequest update(Long roleId) {
            UserUpdateRequest request = new UserUpdateRequest();
            request.setFullName("Changed Name");
            request.setRoleId(roleId);
            return request;
        }

        private User account(long id, Role role) {
            User user = User.builder().id(id).fullName("Account " + id).email("a" + id + "@test.com").role(role).build();
            when(users.findById(id)).thenReturn(Optional.of(user));
            return user;
        }

        @Test
        @DisplayName("a non-admin can't promote someone to Admin")
        void nonAdminCantPromote() {
            account(10, contributor);
            IllegalArgumentException e = assertThrows(IllegalArgumentException.class, () -> service.updateUser(10L, update(1L), false));
            assertEquals(UserService.ADMIN_ONLY, e.getMessage());
            verify(users, never()).save(any());
        }

        @Test
        @DisplayName("a non-admin can't edit or delete an admin")
        void nonAdminCantTouchAdmin() {
            account(11, admin);
            when(users.countByRole_SystemRoleTrueAndStatus(UserStatus.ACTIVE)).thenReturn(3L);
            assertThrows(IllegalArgumentException.class, () -> service.updateUser(11L, update(1L), false));
            IllegalArgumentException e = assertThrows(IllegalArgumentException.class, () -> service.deleteUser(11L, "someone@test.com", false));
            assertEquals(UserService.ADMIN_ONLY, e.getMessage());
            verify(users, never()).delete(any());
        }

        @Test
        @DisplayName("the last admin can't be demoted or deleted, even by an admin")
        void lastAdminStays() {
            account(12, admin);
            when(users.countByRole_SystemRoleTrueAndStatus(UserStatus.ACTIVE)).thenReturn(1L);
            assertEquals(UserService.LAST_ADMIN,
                    assertThrows(IllegalArgumentException.class, () -> service.updateUser(12L, update(2L), true)).getMessage());
            assertEquals(UserService.LAST_ADMIN,
                    assertThrows(IllegalArgumentException.class, () -> service.deleteUser(12L, "other@test.com", true)).getMessage());
        }

        @Test
        @DisplayName("an admin can demote one of several admins, and edit ordinary users freely")
        void adminCanManage() {
            account(13, admin);
            when(users.countByRole_SystemRoleTrueAndStatus(UserStatus.ACTIVE)).thenReturn(2L);
            assertEquals(contributor, service.updateUser(13L, update(2L), true).getRole());

            account(14, contributor);
            assertEquals(contributor, service.updateUser(14L, update(2L), false).getRole(), "non-admin editing a non-admin is fine");
        }

        @Test
        @DisplayName("users who still have posts can't be deleted")
        void userWithPosts() {
            account(15, contributor);
            when(posts.existsByAuthor_Id(15L)).thenReturn(true);
            IllegalArgumentException e = assertThrows(IllegalArgumentException.class, () -> service.deleteUser(15L, "admin@test.com", true));
            assertEquals("error.user.has_posts", e.getMessage());
        }
    }

    /** The same rules through the Users page, with real permissions. */
    @Nested
    @SpringBootTest
    @DisplayName("through the Users page")
    class ThroughTheUsersPage {

        @Autowired private WebApplicationContext context;
        @Autowired private UserRepository userRepository;
        @Autowired private RoleRepository roleRepository;
        @Autowired private PasswordEncoder passwordEncoder;

        private MockMvc mockMvc;
        private final List<String> emails = new ArrayList<>();
        private final String tag = "guard" + System.nanoTime();
        private static final String[] USER_MANAGER = {"users:view", "users:create", "users:edit", "users:delete"};

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

        private User saved(String name, String roleSlug) {
            return userRepository.save(User.builder().fullName("Guard " + name).email(email(name))
                    .password(passwordEncoder.encode("Password123")).role(roleRepository.findBySlug(roleSlug).orElseThrow()).build());
        }

        @Test
        @DisplayName("a user manager without roles:edit can't create an admin")
        void cantCreateAdmin() throws Exception {
            String target = email("new-admin");
            mockMvc.perform(post("/admin/users").with(csrf()).with(TestAuth.withPermissions("manager@test.com", USER_MANAGER))
                            .param("fullName", "Sneaky Admin").param("email", target).param("password", "Password123")
                            .param("roleId", roleRepository.findBySlug("admin").orElseThrow().getId().toString()))
                    .andExpect(status().isOk())
                    .andExpect(content().string(containsString("Only admins can grant the Admin role")));
            assertTrue(userRepository.findByEmail(target).isEmpty());
        }

        @Test
        @DisplayName("a user manager without roles:edit can't edit an admin; an admin can create one")
        void cantEditAdmin() throws Exception {
            User victim = saved("victim", "admin");
            mockMvc.perform(post("/admin/users/" + victim.getId() + "/edit").with(csrf())
                            .with(TestAuth.withPermissions("manager@test.com", USER_MANAGER))
                            .param("id", victim.getId().toString()).param("fullName", "Hijacked").param("email", victim.getEmail())
                            .param("password", "NewPassword1").param("roleId", roleRepository.findBySlug("user").orElseThrow().getId().toString()))
                    .andExpect(status().isOk())
                    .andExpect(content().string(containsString("Only admins can grant the Admin role")));
            User reloaded = userRepository.findByEmailWithRole(victim.getEmail()).orElseThrow();
            assertEquals("Guard victim", reloaded.getFullName());
            assertEquals("admin", reloaded.getRole().getSlug());

            String promoted = email("promoted");
            mockMvc.perform(post("/admin/users").with(csrf()).with(TestAuth.owner())
                            .param("fullName", "Real Admin").param("email", promoted).param("password", "Password123")
                            .param("roleId", roleRepository.findBySlug("admin").orElseThrow().getId().toString()))
                    .andExpect(status().is3xxRedirection());
            assertEquals("admin", userRepository.findByEmailWithRole(promoted).orElseThrow().getRole().getSlug());
        }
    }
}
