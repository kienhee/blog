package com.kienhee.blog;

import com.kienhee.blog.dto.UserCreateRequest;
import com.kienhee.blog.dto.UserUpdateRequest;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.UserService;
import jakarta.validation.ConstraintViolation;
import jakarta.validation.Validator;
import com.kienhee.blog.support.TestLocale;
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

import java.util.List;
import java.util.Set;

import static org.hamcrest.Matchers.containsString;
import static org.hamcrest.Matchers.not;
import static org.junit.jupiter.api.Assertions.*;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.user;
import static com.kienhee.blog.support.TestAuth.owner;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

@SpringBootTest
public class UserCrudTests {

    @Autowired
    private WebApplicationContext wac;

    @Autowired
    private Validator validator;

    @Autowired
    private UserRepository userRepository;

    @Autowired
    private UserService userService;

    @Autowired
    private PasswordEncoder passwordEncoder;

    private MockMvc mockMvc;

    @BeforeEach
    void setUp() {
        // See AuthValidationTests: direct Validator calls need an explicit language.
        org.springframework.context.i18n.LocaleContextHolder.setLocale(java.util.Locale.ENGLISH);
        this.mockMvc = MockMvcBuilders.webAppContextSetup(this.wac)
                .apply(springSecurity())
                .defaultRequest(TestLocale.englishByDefault())
                .build();

        userRepository.findByEmail("test-owner@kienhee.test").ifPresentOrElse(
                u -> {
                    u.setFullName("Admin User");
                    u.setPassword(passwordEncoder.encode("admin123"));
                    userRepository.save(u);
                },
                () -> {
                    userRepository.save(User.builder()
                            .fullName("Admin User")
                            .email("test-owner@kienhee.test")
                            .password(passwordEncoder.encode("admin123"))
                            .build());
                }
        );
    }

    @Nested
    @DisplayName("1. User DTO Validation Tests")
    class UserDtoValidationTests {

        @Test
        @DisplayName("UserCreateRequest - validate các trường bắt buộc")
        void testUserCreateRequestValidation() {
            UserCreateRequest emptyReq = UserCreateRequest.builder().build();
            Set<ConstraintViolation<UserCreateRequest>> violations = validator.validate(emptyReq);
            assertFalse(violations.isEmpty());

            UserCreateRequest invalidEmailReq = UserCreateRequest.builder()
                    .fullName("Valid Name")
                    .email("invalid-email")
                    .password("123456")
                    .build();
            violations = validator.validate(invalidEmailReq);
            assertFalse(violations.isEmpty());

            UserCreateRequest shortPasswordReq = UserCreateRequest.builder()
                    .fullName("Valid Name")
                    .email("test@example.com")
                    .password("123")
                    .build();
            violations = validator.validate(shortPasswordReq);
            assertFalse(violations.isEmpty());

            UserCreateRequest validReq = UserCreateRequest.builder()
                    .fullName("Valid Name")
                    .email("test@example.com")
                    .password("password123")
                    .phone("0912345678")
                    .address("Hanoi")
                    .bio("Developer")
                    .build();
            violations = validator.validate(validReq);
            assertTrue(violations.isEmpty());
        }

        @Test
        @DisplayName("UserUpdateRequest - validate ID và tên bắt buộc")
        void testUserUpdateRequestValidation() {
            UserUpdateRequest emptyReq = UserUpdateRequest.builder().build();
            Set<ConstraintViolation<UserUpdateRequest>> violations = validator.validate(emptyReq);
            assertFalse(violations.isEmpty());

            UserUpdateRequest validReq = UserUpdateRequest.builder()
                    .id(1L)
                    .fullName("Updated Name")
                    .phone("0987654321")
                    .address("Da Nang")
                    .bio("Updated Bio")
                    .build();
            violations = validator.validate(validReq);
            assertTrue(violations.isEmpty());
        }
    }

    @Nested
    @DisplayName("2. UserService Unit Tests")
    class UserServiceUnitTests {

        @Test
        @DisplayName("UserService - tạo user mới thành công và mã hóa mật khẩu")
        void testCreateUserSuccess() {
            String uniqueEmail = "newuser_" + System.currentTimeMillis() + "@test.com";
            UserCreateRequest req = UserCreateRequest.builder()
                    .fullName("New Test User")
                    .email(uniqueEmail)
                    .password("secret123")
                    .phone("0123456789")
                    .address("Hue City")
                    .bio("Software Engineer")
                    .build();

            User created = userService.createUser(req);
            assertNotNull(created.getId());
            assertEquals("New Test User", created.getFullName());
            assertEquals(uniqueEmail, created.getEmail());
            assertTrue(passwordEncoder.matches("secret123", created.getPassword()));
            assertNotNull(created.getCreatedAt());

            // Clean up
            userRepository.deleteById(created.getId());
        }

        @Test
        @DisplayName("UserService - báo lỗi khi tạo user với email đã tồn tại")
        void testCreateUserDuplicateEmail() {
            UserCreateRequest req = UserCreateRequest.builder()
                    .fullName("Duplicate Email User")
                    .email("test-owner@kienhee.test")
                    .password("secret123")
                    .build();

            assertThrows(IllegalArgumentException.class, () -> userService.createUser(req));
        }

        @Test
        @DisplayName("UserService - cập nhật thông tin user")
        void testUpdateUserSuccess() {
            String uniqueEmail = "updateuser_" + System.currentTimeMillis() + "@test.com";
            User user = userRepository.save(User.builder()
                    .fullName("Old Name")
                    .email(uniqueEmail)
                    .password(passwordEncoder.encode("oldpass"))
                    .build());

            UserUpdateRequest updateReq = UserUpdateRequest.builder()
                    .id(user.getId())
                    .fullName("New Updated Name")
                    .phone("0999888777")
                    .address("HCM City")
                    .bio("Writer")
                    .password("newpass123")
                    .build();

            User updated = userService.updateUser(user.getId(), updateReq);
            assertEquals("New Updated Name", updated.getFullName());
            assertEquals("0999888777", updated.getPhone());
            assertEquals("HCM City", updated.getAddress());
            assertEquals("Writer", updated.getBio());
            assertTrue(passwordEncoder.matches("newpass123", updated.getPassword()));

            // Clean up
            userRepository.deleteById(user.getId());
        }

        @Test
        @DisplayName("UserService - không cho phép quản trị viên tự xóa chính mình")
        void testDeleteSelfPrevented() {
            User admin = userRepository.findByEmail("test-owner@kienhee.test").orElseThrow();
            assertThrows(IllegalArgumentException.class, () ->
                    userService.deleteUser(admin.getId(), "test-owner@kienhee.test")
            );
        }

        @Test
        @DisplayName("UserService - xóa user thành công")
        void testDeleteUserSuccess() {
            User temp = userRepository.save(User.builder()
                    .fullName("Delete Me")
                    .email("deleteme_" + System.currentTimeMillis() + "@test.com")
                    .password("pass")
                    .build());

            Long id = temp.getId();
            userService.deleteUser(id, "test-owner@kienhee.test");
            assertFalse(userRepository.existsById(id));
        }
    }

    @Nested
    @DisplayName("3. UserController MVC Endpoints Tests")
    class UserControllerMvcTests {

        @Test
        @DisplayName("GET /admin/users - Hiển thị danh sách user trong bảng datatable")
        void testListUsers() throws Exception {
            mockMvc.perform(get("/admin/users")
                            .with(owner()))
                    .andExpect(status().isOk())
                    .andExpect(view().name("admin/user/users"))
                    .andExpect(model().attributeExists("users"))
                    .andExpect(content().string(containsString("table class=\"dt\"")))
                    .andExpect(content().string(containsString("test-owner@kienhee.test")))
                    .andExpect(content().string(containsString("name=\"kh-posts-per-page\"")))
                    .andExpect(content().string(not(containsString("✕"))));
        }

        @Test
        @DisplayName("POST /admin/users - Tạo user mới thành công và chuyển hướng")
        void testCreateUserMvcSuccess() throws Exception {
            String email = "mvc_created_" + System.currentTimeMillis() + "@test.com";

            mockMvc.perform(post("/admin/users")
                            .with(owner())
                            .with(csrf())
                            .param("fullName", "MVC New User")
                            .param("email", email)
                            .param("password", "mvcPass123")
                            .param("phone", "0900112233")
                            .param("address", "Hanoi")
                            .param("bio", "Bio content"))
                    .andExpect(status().is3xxRedirection())
                    .andExpect(redirectedUrl("/admin/users"))
                    .andExpect(flash().attributeExists("successMessage"));

            User created = userRepository.findByEmail(email).orElseThrow();
            assertEquals("MVC New User", created.getFullName());
            assertTrue(passwordEncoder.matches("mvcPass123", created.getPassword()));

            // Clean up
            userRepository.deleteById(created.getId());
        }

        @Test
        @DisplayName("POST /admin/users - Báo lỗi validate khi dữ liệu rỗng")
        void testCreateUserMvcValidationError() throws Exception {
            mockMvc.perform(post("/admin/users")
                            .with(owner())
                            .with(csrf())
                            .param("fullName", "")
                            .param("email", "bad-email")
                            .param("password", "")
                            .with(TestLocale.en()))
                    .andExpect(status().isOk())
                    .andExpect(view().name("admin/user/users"))
                    .andExpect(model().hasErrors())
                    .andExpect(content().string(containsString("Full name is required.")));
        }

        @Test
        @DisplayName("POST /admin/users - Báo lỗi trùng email khi tạo")
        void testCreateUserMvcDuplicateEmail() throws Exception {
            mockMvc.perform(post("/admin/users")
                            .with(owner())
                            .with(csrf())
                            .param("fullName", "Admin Copy")
                            .param("email", "test-owner@kienhee.test")
                            .param("password", "admin123"))
                    .andExpect(status().isOk())
                    .andExpect(view().name("admin/user/users"))
                    .andExpect(model().hasErrors())
                    .andExpect(content().string(containsString("Email already in use.")));
        }

        @Test
        @DisplayName("POST /admin/users/{id}/edit - Cập nhật user thành công")
        void testUpdateUserMvcSuccess() throws Exception {
            String email = "to_edit_" + System.currentTimeMillis() + "@test.com";
            User target = userRepository.save(User.builder()
                    .fullName("Pre Edit")
                    .email(email)
                    .password(passwordEncoder.encode("pass123"))
                    .build());

            mockMvc.perform(post("/admin/users/" + target.getId() + "/edit")
                            .with(owner())
                            .with(csrf())
                            .param("id", String.valueOf(target.getId()))
                            .param("fullName", "Post Edit Name")
                            .param("email", email)
                            .param("phone", "0888999111")
                            .param("address", "Da Nang City")
                            .param("bio", "Updated Bio"))
                    .andExpect(status().is3xxRedirection())
                    .andExpect(redirectedUrl("/admin/users"))
                    .andExpect(flash().attributeExists("successMessage"));

            User updated = userRepository.findById(target.getId()).orElseThrow();
            assertEquals("Post Edit Name", updated.getFullName());
            assertEquals("0888999111", updated.getPhone());

            // Clean up
            userRepository.deleteById(target.getId());
        }

        @Test
        @DisplayName("POST /admin/users/{id}/delete - Ngăn chặn tự xóa chính mình")
        void testDeleteSelfMvcPrevented() throws Exception {
            User admin = userRepository.findByEmail("test-owner@kienhee.test").orElseThrow();

            mockMvc.perform(post("/admin/users/" + admin.getId() + "/delete")
                            .with(owner())
                            .with(csrf()))
                    .andExpect(status().is3xxRedirection())
                    .andExpect(redirectedUrl("/admin/users"))
                    .andExpect(flash().attributeExists("errorMessage"))
                    .andExpect(flash().attribute("errorMessage", "You cannot delete your own account."));

            assertTrue(userRepository.existsById(admin.getId()));
        }

        @Test
        @DisplayName("POST /admin/users/{id}/delete - Xóa user khác thành công")
        void testDeleteOtherUserMvcSuccess() throws Exception {
            User other = userRepository.save(User.builder()
                    .fullName("Other User")
                    .email("other_" + System.currentTimeMillis() + "@test.com")
                    .password(passwordEncoder.encode("pass"))
                    .build());

            mockMvc.perform(post("/admin/users/" + other.getId() + "/delete")
                            .with(owner())
                            .with(csrf()))
                    .andExpect(status().is3xxRedirection())
                    .andExpect(redirectedUrl("/admin/users"))
                    .andExpect(flash().attributeExists("successMessage"));

            assertFalse(userRepository.existsById(other.getId()));
        }
    }
}

