package com.kienhee.blog;

import com.kienhee.blog.dto.ChangePasswordRequest;
import com.kienhee.blog.dto.ForgotPasswordRequest;
import com.kienhee.blog.dto.ProfileUpdateRequest;
import com.kienhee.blog.dto.RegisterRequest;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.AuthService;
import com.kienhee.blog.service.MailService;
import com.kienhee.blog.service.RegistrationPolicy;
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
import org.springframework.test.context.bean.override.mockito.MockitoBean;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

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
public class AuthValidationTests {

    @Autowired
    private WebApplicationContext wac;

    /** Forgot-password requests must never send real email from tests. */
    @MockitoBean
    private MailService mailService;

    /** The dev database already has accounts, which closes sign-up; these tests exercise the form itself. */
    @MockitoBean
    private RegistrationPolicy registrationPolicy;

    @Autowired
    private Validator validator;

    @Autowired
    private UserRepository userRepository;

    @Autowired
    private PasswordEncoder passwordEncoder;

    @Autowired
    private AuthService authService;

    private MockMvc mockMvc;

    @BeforeEach
    void setUp() {
        // Constraint messages are message codes now, interpolated for the current locale. A test
        // that calls the Validator directly has no request, so pin the language instead of
        // inheriting the machine's default.
        org.springframework.context.i18n.LocaleContextHolder.setLocale(java.util.Locale.ENGLISH);
        org.mockito.Mockito.when(registrationPolicy.isFirstAccount()).thenReturn(true);
        this.mockMvc = MockMvcBuilders.webAppContextSetup(this.wac)
                .apply(springSecurity())
                .defaultRequest(TestLocale.englishByDefault())
                .build();

        userRepository.findByEmail("test-owner@kienhee.test").ifPresentOrElse(
                u -> {
                    u.setPassword(passwordEncoder.encode("admin123"));
                    userRepository.save(u);
                },
                () -> {
                    User admin = User.builder()
                            .fullName("Admin Kienhee")
                            .email("test-owner@kienhee.test")
                            .password(passwordEncoder.encode("admin123"))
                            .build();
                    userRepository.save(admin);
                }
        );
    }

    @Nested
    @DisplayName("1. Bean Validation (Backend DTO Validation) Tests")
    class BeanValidationUnitTests {

        @Test
        @DisplayName("RegisterRequest: Hợp lệ không có lỗi")
        void testRegisterRequestValid() {
            RegisterRequest req = RegisterRequest.builder()
                    .fullName("Nguyen Van A")
                    .email("nguyenvana@example.com")
                    .password("secret123")
                    .build();
            Set<ConstraintViolation<RegisterRequest>> violations = validator.validate(req);
            assertTrue(violations.isEmpty(), "Dữ liệu hợp lệ không được có lỗi validation");
        }

        @Test
        @DisplayName("RegisterRequest: fullName trống hoặc chỉ có khoảng trắng")
        void testRegisterRequestBlankFullName() {
            RegisterRequest req = RegisterRequest.builder()
                    .fullName("   ")
                    .email("valid@example.com")
                    .password("secret123")
                    .build();
            Set<ConstraintViolation<RegisterRequest>> violations = validator.validate(req);
            assertFalse(violations.isEmpty());
            boolean hasExpectedMsg = violations.stream()
                    .anyMatch(v -> v.getMessage().contains("Full name is required"));
            assertTrue(hasExpectedMsg, "Phải báo lỗi Full name is required");
        }

        @Test
        @DisplayName("RegisterRequest: fullName quá ngắn (< 2 ký tự)")
        void testRegisterRequestShortFullName() {
            RegisterRequest req = RegisterRequest.builder()
                    .fullName("A")
                    .email("valid@example.com")
                    .password("secret123")
                    .build();
            Set<ConstraintViolation<RegisterRequest>> violations = validator.validate(req);
            boolean hasExpectedMsg = violations.stream()
                    .anyMatch(v -> v.getMessage().contains("Full name must have at least 2 characters"));
            assertTrue(hasExpectedMsg, "Phải báo lỗi Full name must have at least 2 characters");
        }

        @Test
        @DisplayName("RegisterRequest: email trống")
        void testRegisterRequestBlankEmail() {
            RegisterRequest req = RegisterRequest.builder()
                    .fullName("Nguyen Van A")
                    .email("")
                    .password("secret123")
                    .build();
            Set<ConstraintViolation<RegisterRequest>> violations = validator.validate(req);
            boolean hasExpectedMsg = violations.stream()
                    .anyMatch(v -> v.getMessage().contains("Email is required"));
            assertTrue(hasExpectedMsg, "Phải báo lỗi Email is required");
        }

        @Test
        @DisplayName("RegisterRequest: email sai định dạng")
        void testRegisterRequestInvalidEmailFormat() {
            RegisterRequest req = RegisterRequest.builder()
                    .fullName("Nguyen Van A")
                    .email("not-an-email")
                    .password("secret123")
                    .build();
            Set<ConstraintViolation<RegisterRequest>> violations = validator.validate(req);
            boolean hasExpectedMsg = violations.stream()
                    .anyMatch(v -> v.getMessage().contains("Invalid email format"));
            assertTrue(hasExpectedMsg, "Phải báo lỗi Invalid email format");
        }

        @Test
        @DisplayName("RegisterRequest: password trống")
        void testRegisterRequestBlankPassword() {
            RegisterRequest req = RegisterRequest.builder()
                    .fullName("Nguyen Van A")
                    .email("valid@example.com")
                    .password("")
                    .build();
            Set<ConstraintViolation<RegisterRequest>> violations = validator.validate(req);
            boolean hasExpectedMsg = violations.stream()
                    .anyMatch(v -> v.getMessage().contains("Password is required"));
            assertTrue(hasExpectedMsg, "Phải báo lỗi Password is required");
        }

        @Test
        @DisplayName("RegisterRequest: password quá ngắn (< 6 ký tự)")
        void testRegisterRequestShortPassword() {
            RegisterRequest req = RegisterRequest.builder()
                    .fullName("Nguyen Van A")
                    .email("valid@example.com")
                    .password("123")
                    .build();
            Set<ConstraintViolation<RegisterRequest>> violations = validator.validate(req);
            boolean hasExpectedMsg = violations.stream()
                    .anyMatch(v -> v.getMessage().contains("Password must have at least 6 characters"));
            assertTrue(hasExpectedMsg, "Phải báo lỗi Password must have at least 6 characters");
        }

        @Test
        @DisplayName("ForgotPasswordRequest: Hợp lệ")
        void testForgotPasswordRequestValid() {
            ForgotPasswordRequest req = ForgotPasswordRequest.builder()
                    .email("user@example.com")
                    .build();
            Set<ConstraintViolation<ForgotPasswordRequest>> violations = validator.validate(req);
            assertTrue(violations.isEmpty());
        }

        @Test
        @DisplayName("ForgotPasswordRequest: email trống")
        void testForgotPasswordRequestBlankEmail() {
            ForgotPasswordRequest req = ForgotPasswordRequest.builder()
                    .email("   ")
                    .build();
            Set<ConstraintViolation<ForgotPasswordRequest>> violations = validator.validate(req);
            boolean hasExpectedMsg = violations.stream()
                    .anyMatch(v -> v.getMessage().contains("Email is required"));
            assertTrue(hasExpectedMsg, "Phải báo lỗi Email is required");
        }

        @Test
        @DisplayName("ForgotPasswordRequest: email sai định dạng")
        void testForgotPasswordRequestInvalidEmail() {
            ForgotPasswordRequest req = ForgotPasswordRequest.builder()
                    .email("invalid-email-domain")
                    .build();
            Set<ConstraintViolation<ForgotPasswordRequest>> violations = validator.validate(req);
            boolean hasExpectedMsg = violations.stream()
                    .anyMatch(v -> v.getMessage().contains("Invalid email format"));
            assertTrue(hasExpectedMsg, "Phải báo lỗi Invalid email format");
        }
    }

    @Nested
    @DisplayName("2. Web MockMvc Validation & Controller Tests")
    class WebControllerValidationTests {

        @Test
        @DisplayName("POST /auth/register: Để trống toàn bộ trường -> trả về 3 lỗi tương ứng")
        void testRegisterAllEmpty() throws Exception {
            mockMvc.perform(post("/auth/register")
                            .with(csrf())
                            .param("fullName", "")
                            .param("email", "")
                            .param("password", "")
                            .with(TestLocale.en()))
                    .andExpect(status().isOk())
                    .andExpect(content().string(containsString("Full name is required")))
                    .andExpect(content().string(containsString("Email is required")))
                    .andExpect(content().string(containsString("Password is required")));
        }

        @Test
        @DisplayName("POST /auth/register: fullName ngắn, email sai định dạng, password ngắn")
        void testRegisterInvalidFormats() throws Exception {
            mockMvc.perform(post("/auth/register")
                            .with(csrf())
                            .param("fullName", "X")
                            .param("email", "bad-email")
                            .param("password", "12")
                            .with(TestLocale.en()))
                    .andExpect(status().isOk())
                    .andExpect(content().string(containsString("Full name must have at least 2 characters")))
                    .andExpect(content().string(containsString("Invalid email format")))
                    .andExpect(content().string(containsString("Password must have at least 6 characters")));
        }

        @Test
        @DisplayName("POST /auth/register: Email đã tồn tại -> báo lỗi Email already in use")
        void testRegisterDuplicateEmail() throws Exception {
            mockMvc.perform(post("/auth/register")
                            .with(csrf())
                            .param("fullName", "Admin Duplicate")
                            .param("email", "test-owner@kienhee.test")
                            .param("password", "newpassword123"))
                    .andExpect(status().isOk())
                    .andExpect(content().string(containsString("Email already in use")));
        }

        @Test
        @DisplayName("POST /auth/register: Dữ liệu hợp lệ -> redirect /auth/login?registered=true và lưu DB")
        void testRegisterSuccess() throws Exception {
            String testEmail = "testuser_" + System.currentTimeMillis() + "@kienhee.com";
            mockMvc.perform(post("/auth/register")
                            .with(csrf())
                            .param("fullName", "Test User Success")
                            .param("email", testEmail)
                            .param("password", "secret123"))
                    .andExpect(status().is3xxRedirection())
                    .andExpect(redirectedUrl("/auth/login?registered=true"));

            User created = userRepository.findByEmail(testEmail).orElse(null);
            assertNotNull(created, "User mới phải được tạo trong database");
            userRepository.delete(created);
        }

        @Test
        @DisplayName("POST /auth/forgot: Email trống -> hiển thị Email is required")
        void testForgotBlankEmail() throws Exception {
            mockMvc.perform(post("/auth/forgot")
                            .with(csrf())
                            .param("email", "")
                            .with(TestLocale.en()))
                    .andExpect(status().isOk())
                    .andExpect(content().string(containsString("Email is required")));
        }

        @Test
        @DisplayName("POST /auth/forgot: Email sai định dạng -> hiển thị Invalid email format")
        void testForgotInvalidEmailFormat() throws Exception {
            mockMvc.perform(post("/auth/forgot")
                            .with(csrf())
                            .param("email", "invalid-domain")
                            .with(TestLocale.en()))
                    .andExpect(status().isOk())
                    .andExpect(content().string(containsString("Invalid email format")));
        }

        @Test
        @DisplayName("POST /auth/forgot: Email không tồn tại -> cùng thông báo trung lập (không lộ tài khoản)")
        void testForgotEmailNotFound() throws Exception {
            mockMvc.perform(post("/auth/forgot")
                            .with(csrf())
                            .param("email", "notfound_404@kienhee.com"))
                    .andExpect(status().is3xxRedirection())
                    .andExpect(redirectedUrl("/auth/forgot"))
                    .andExpect(flash().attribute("successMessage", containsString("If an account exists for that email")));
        }

        @Test
        @DisplayName("POST /auth/forgot: Email hợp lệ -> cùng thông báo trung lập, hiển thị sau redirect")
        void testForgotEmailSuccess() throws Exception {
            mockMvc.perform(post("/auth/forgot")
                            .with(csrf())
                            .param("email", "test-owner@kienhee.test"))
                    .andExpect(status().is3xxRedirection())
                    .andExpect(redirectedUrl("/auth/forgot"))
                    .andExpect(flash().attribute("successMessage", containsString("If an account exists for that email")));
        }

        @Test
        @DisplayName("POST /auth/login: Sai mật khẩu -> redirect về /auth/login?error=true")
        void testLoginBadCredentials() throws Exception {
            mockMvc.perform(post("/auth/login")
                            .with(csrf())
                            .param("email", "test-owner@kienhee.test")
                            .param("password", "wrong_password"))
                    .andExpect(status().is3xxRedirection())
                    .andExpect(redirectedUrl("/auth/login?error=true"));
        }

        @Test
        @DisplayName("POST /auth/login: Email không tồn tại -> redirect về /auth/login?error=true")
        void testLoginUnknownUser() throws Exception {
            mockMvc.perform(post("/auth/login")
                            .with(csrf())
                            .param("email", "ghost_user@kienhee.com")
                            .param("password", "password123"))
                    .andExpect(status().is3xxRedirection())
                    .andExpect(redirectedUrl("/auth/login?error=true"));
        }

        @Test
        @DisplayName("POST /auth/login: Đúng email và mật khẩu -> đăng nhập thành công redirect sang /admin/dashboard")
        void testLoginSuccess() throws Exception {
            mockMvc.perform(post("/auth/login")
                            .with(csrf())
                            .param("email", "test-owner@kienhee.test")
                            .param("password", "admin123"))
                    .andExpect(status().is3xxRedirection())
                    .andExpect(redirectedUrl("/admin/dashboard"));
        }

        @Test
        @DisplayName("GET /auth/login?error: Hiển thị thông báo Invalid email or password.")
        void testLoginErrorBanner() throws Exception {
            mockMvc.perform(get("/auth/login").param("error", "true"))
                    .andExpect(status().isOk())
                    .andExpect(content().string(containsString("Invalid email or password.")));
        }

        @Test
        @DisplayName("GET /auth/login?logout: Hiển thị thông báo đăng xuất thành công")
        void testLoginLogoutBanner() throws Exception {
            mockMvc.perform(get("/auth/login").param("logout", "true"))
                    .andExpect(status().isOk())
                    .andExpect(content().string(containsString("You have been signed out successfully.")));
        }

        @Test
        @DisplayName("GET /auth/login?registered=true: Hiển thị thông báo tạo tài khoản thành công")
        void testLoginRegisteredBanner() throws Exception {
            mockMvc.perform(get("/auth/login").param("registered", "true"))
                    .andExpect(status().isOk())
                    .andExpect(content().string(containsString("Account created successfully! Please sign in.")));
        }
    }

    @Nested
    @DisplayName("3. Kiểm tra không còn ký hiệu ✕, không còn thuộc tính required và đã tích hợp jquery.validate")
    class UIIntegrityTests {

        @Test
        @DisplayName("Không còn ký hiệu ✕ trong các trang Auth")
        void testNoCrossSymbolInAuthPages() throws Exception {
            mockMvc.perform(get("/auth/login"))
                    .andExpect(status().isOk())
                    .andExpect(content().string(not(containsString("✕"))));

            mockMvc.perform(get("/auth/register"))
                    .andExpect(status().isOk())
                    .andExpect(content().string(not(containsString("✕"))));

            mockMvc.perform(get("/auth/forgot"))
                    .andExpect(status().isOk())
                    .andExpect(content().string(not(containsString("✕"))));
        }

        @Test
        @DisplayName("Đã loại bỏ toàn bộ thuộc tính required khỏi input ở các trang Auth")
        void testNoRequiredAttributeInInputs() throws Exception {
            mockMvc.perform(get("/auth/login"))
                    .andExpect(status().isOk())
                    .andExpect(content().string(not(containsString(" required>"))))
                    .andExpect(content().string(not(containsString(" required "))));

            mockMvc.perform(get("/auth/register"))
                    .andExpect(status().isOk())
                    .andExpect(content().string(not(containsString(" required>"))))
                    .andExpect(content().string(not(containsString(" required "))));

            mockMvc.perform(get("/auth/forgot"))
                    .andExpect(status().isOk())
                    .andExpect(content().string(not(containsString(" required>"))))
                    .andExpect(content().string(not(containsString(" required "))));
        }

        @Test
        @DisplayName("Các trang Auth đều nạp jquery.validate.min.js")
        void testAuthPagesLoadJqueryValidate() throws Exception {
            mockMvc.perform(get("/auth/login"))
                    .andExpect(status().isOk())
                    .andExpect(content().string(containsString("jquery.validate.min.js")));

            mockMvc.perform(get("/auth/register"))
                    .andExpect(status().isOk())
                    .andExpect(content().string(containsString("jquery.validate.min.js")));

            mockMvc.perform(get("/auth/forgot"))
                    .andExpect(status().isOk())
                    .andExpect(content().string(containsString("jquery.validate.min.js")));
        }
    }

    @Nested
    @DisplayName("5. Profile & Change Password Tests")
    class ProfileAndPasswordTests {

        @Test
        @DisplayName("ProfileUpdateRequest - validation thành công khi hợp lệ, lỗi khi thiếu fullName hoặc quá ngắn")
        void testProfileUpdateRequestValidation() {
            ProfileUpdateRequest invalidReq = ProfileUpdateRequest.builder()
                    .fullName("")
                    .build();
            Set<ConstraintViolation<ProfileUpdateRequest>> violations = validator.validate(invalidReq);
            assertFalse(violations.isEmpty());

            ProfileUpdateRequest shortReq = ProfileUpdateRequest.builder()
                    .fullName("A")
                    .build();
            violations = validator.validate(shortReq);
            assertFalse(violations.isEmpty());

            ProfileUpdateRequest validReq = ProfileUpdateRequest.builder()
                    .fullName("Kien Hee")
                    .email("kien@test.com")
                    .phone("0912345678")
                    .address("Hanoi")
                    .bio("Developer")
                    .build();
            violations = validator.validate(validReq);
            assertTrue(violations.isEmpty());
        }

        @Test
        @DisplayName("ChangePasswordRequest - validation bắt lỗi khi thiếu trường hoặc mật khẩu quá ngắn")
        void testChangePasswordRequestValidation() {
            ChangePasswordRequest emptyReq = ChangePasswordRequest.builder().build();
            Set<ConstraintViolation<ChangePasswordRequest>> violations = validator.validate(emptyReq);
            assertEquals(3, violations.size());

            ChangePasswordRequest shortReq = ChangePasswordRequest.builder()
                    .currentPassword("admin123")
                    .newPassword("12345")
                    .confirmPassword("12345")
                    .build();
            violations = validator.validate(shortReq);
            assertFalse(violations.isEmpty());

            ChangePasswordRequest validReq = ChangePasswordRequest.builder()
                    .currentPassword("admin123")
                    .newPassword("newPass123")
                    .confirmPassword("newPass123")
                    .build();
            violations = validator.validate(validReq);
            assertTrue(violations.isEmpty());
        }

        @Test
        @DisplayName("AuthService - updateProfile cập nhật thành công thông tin user")
        void testAuthServiceUpdateProfile() {
            ProfileUpdateRequest req = ProfileUpdateRequest.builder()
                    .fullName("Admin Updated")
                    .phone("0123456789")
                    .address("Da Nang")
                    .bio("Updated Bio")
                    .build();

            User updated = authService.updateProfile("test-owner@kienhee.test", req);
            assertEquals("Admin Updated", updated.getFullName());
            assertEquals("0123456789", updated.getPhone());
            assertEquals("Da Nang", updated.getAddress());
            assertEquals("Updated Bio", updated.getBio());

            User dbUser = userRepository.findByEmail("test-owner@kienhee.test").orElseThrow();
            assertEquals("Admin Updated", dbUser.getFullName());
        }

        @Test
        @DisplayName("AuthService - changePassword bắt lỗi khi mật khẩu hiện tại sai hoặc mật khẩu mới trùng mật khẩu cũ")
        void testAuthServiceChangePasswordValidation() {
            // Wrong current password
            assertThrows(IllegalArgumentException.class, () ->
                    authService.changePassword("test-owner@kienhee.test", "wrongPass", "newSecret123")
            );

            // New password same as current password
            assertThrows(IllegalArgumentException.class, () ->
                    authService.changePassword("test-owner@kienhee.test", "admin123", "admin123")
            );

            // Success
            boolean result = authService.changePassword("test-owner@kienhee.test", "admin123", "newSecret123");
            assertTrue(result);

            User dbUser = userRepository.findByEmail("test-owner@kienhee.test").orElseThrow();
            assertTrue(passwordEncoder.matches("newSecret123", dbUser.getPassword()));
        }

        @Test
        @DisplayName("GET /admin/profile - Fill thông tin người dùng đăng nhập vào form profile, không chứa ký hiệu ✕ và required")
        void testGetProfilePageWithCurrentUser() throws Exception {
            mockMvc.perform(get("/admin/profile")
                            .with(owner()))
                    .andExpect(status().isOk())
                    .andExpect(view().name("admin/user/profile"))
                    .andExpect(model().attributeExists("profileRequest", "changePasswordRequest", "currentUser"))
                    .andExpect(content().string(containsString("test-owner@kienhee.test")))
                    .andExpect(content().string(containsString("jquery.validate.min.js")))
                    .andExpect(content().string(not(containsString("✕"))))
                    .andExpect(content().string(not(containsString(" required>"))))
                    .andExpect(content().string(not(containsString(" required "))));
        }

        @Test
        @DisplayName("POST /admin/profile - Validate lỗi khi display name trống")
        void testPostProfileValidationError() throws Exception {
            mockMvc.perform(post("/admin/profile")
                            .with(owner())
                            .with(csrf())
                            .param("fullName", "")
                            .with(TestLocale.en()))
                    .andExpect(status().isOk())
                    .andExpect(view().name("admin/user/profile"))
                    .andExpect(model().hasErrors())
                    .andExpect(model().attributeErrorCount("profileRequest", 2))
                    .andExpect(content().string(containsString("Display name is required.")));
        }

        @Test
        @DisplayName("POST /admin/profile - Cập nhật thông tin profile thành công và chuyển hướng")
        void testPostProfileSuccess() throws Exception {
            mockMvc.perform(post("/admin/profile")
                            .with(owner())
                            .with(csrf())
                            .param("fullName", "Kienhee Master")
                            .param("phone", "0999888777")
                            .param("address", "Hanoi City")
                            .param("bio", "Full Stack Developer"))
                    .andExpect(status().is3xxRedirection())
                    .andExpect(redirectedUrl("/admin/profile?tab=info"))
                    .andExpect(flash().attributeExists("profileSuccess"));

            User dbUser = userRepository.findByEmail("test-owner@kienhee.test").orElseThrow();
            assertEquals("Kienhee Master", dbUser.getFullName());
            assertEquals("0999888777", dbUser.getPhone());
            assertEquals("Hanoi City", dbUser.getAddress());
        }

        @Test
        @DisplayName("POST /admin/profile/password - Validate lỗi khi mật khẩu xác nhận không khớp")
        void testPostChangePasswordMismatch() throws Exception {
            mockMvc.perform(post("/admin/profile/password")
                            .with(owner())
                            .with(csrf())
                            .param("currentPassword", "admin123")
                            .param("newPassword", "brandNewPass1")
                            .param("confirmPassword", "brandNewPass2"))
                    .andExpect(status().isOk())
                    .andExpect(view().name("admin/user/profile"))
                    .andExpect(model().hasErrors())
                    .andExpect(content().string(containsString("Passwords do not match.")));
        }

        @Test
        @DisplayName("POST /admin/profile/password - Validate lỗi khi mật khẩu hiện tại không đúng")
        void testPostChangePasswordWrongCurrent() throws Exception {
            mockMvc.perform(post("/admin/profile/password")
                            .with(owner())
                            .with(csrf())
                            .param("currentPassword", "wrongOldPass")
                            .param("newPassword", "brandNewPass1")
                            .param("confirmPassword", "brandNewPass1"))
                    .andExpect(status().isOk())
                    .andExpect(view().name("admin/user/profile"))
                    .andExpect(model().hasErrors())
                    .andExpect(content().string(containsString("Current password is incorrect.")));
        }

        @Test
        @DisplayName("POST /admin/profile/password - Đổi mật khẩu thành công và cập nhật hash trong database")
        void testPostChangePasswordSuccess() throws Exception {
            mockMvc.perform(post("/admin/profile/password")
                            .with(owner())
                            .with(csrf())
                            .param("currentPassword", "admin123")
                            .param("newPassword", "superNewPass123")
                            .param("confirmPassword", "superNewPass123"))
                    .andExpect(status().is3xxRedirection())
                    .andExpect(redirectedUrl("/admin/profile?tab=security"))
                    .andExpect(flash().attributeExists("passwordSuccess"));

            User dbUser = userRepository.findByEmail("test-owner@kienhee.test").orElseThrow();
            assertTrue(passwordEncoder.matches("superNewPass123", dbUser.getPassword()));
        }
    }
}

