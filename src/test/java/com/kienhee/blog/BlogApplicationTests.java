package com.kienhee.blog;

import com.kienhee.blog.dto.RegisterRequest;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.AuthService;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.security.test.context.support.WithMockUser;
import com.kienhee.blog.support.WithOwner;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import static org.hamcrest.Matchers.containsString;
import static org.hamcrest.Matchers.not;
import static org.junit.jupiter.api.Assertions.*;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

@SpringBootTest
class BlogApplicationTests {

	/** The dev database already has accounts (sign-up closed); these tests cover the sign-up form itself. */
	@org.springframework.test.context.bean.override.mockito.MockitoBean
	private com.kienhee.blog.service.RegistrationPolicy registrationPolicy;

	@Autowired
	private WebApplicationContext wac;

	@Autowired
	private AuthService authService;

	@Autowired
	private UserRepository userRepository;

	@Autowired
	private PasswordEncoder passwordEncoder;

	private MockMvc mockMvc;

	@BeforeEach
	void setUp() {
		org.mockito.Mockito.when(registrationPolicy.isFirstAccount()).thenReturn(true);
		this.mockMvc = MockMvcBuilders.webAppContextSetup(this.wac)
				.apply(springSecurity())
				.build();
	}

	@Test
	void contextLoads() {
	}

	@Test
	void testPublicNewsActive() throws Exception {
		mockMvc.perform(get("/news"))
				.andExpect(status().isOk())
				.andExpect(content().string(containsString("aria-current=\"page\" class=\"active\">News</a>")))
				.andExpect(content().string(not(containsString("aria-current=\"page\" class=\"active\">Home</a>"))));
	}

	@Test
	void testPublicHomeActive() throws Exception {
		mockMvc.perform(get("/"))
				.andExpect(status().isOk())
				.andExpect(content().string(containsString("aria-current=\"page\" class=\"active\">Home</a>")))
				.andExpect(content().string(not(containsString("aria-current=\"page\" class=\"active\">News</a>"))));
	}

	@Test
	void testAdminRequiresAuthentication() throws Exception {
		mockMvc.perform(get("/admin/dashboard"))
				.andExpect(status().is3xxRedirection())
				.andExpect(redirectedUrl("/auth/login"));
	}

	@Test
	@WithOwner
	void testAdminDashboardAuthenticated() throws Exception {
		mockMvc.perform(get("/admin/dashboard"))
				.andExpect(status().isOk())
				.andExpect(content().string(containsString("Dashboard")))
				.andExpect(content().string(containsString("href=\"/admin/dashboard\" aria-current=\"page\"")));
	}

	@Test
	@WithOwner
	void testAdminPosts() throws Exception {
		mockMvc.perform(get("/admin/posts"))
				.andExpect(status().isOk())
				.andExpect(content().string(containsString("Posts")))
				.andExpect(content().string(containsString("href=\"/admin/posts\" aria-current=\"page\"")));
	}

	@Test
	@WithOwner
	void testAdminCategories() throws Exception {
		mockMvc.perform(get("/admin/categories"))
				.andExpect(status().isOk())
				.andExpect(content().string(containsString("Categories")))
				.andExpect(content().string(containsString("href=\"/admin/categories\" aria-current=\"page\"")));
	}

	@Test
	@WithOwner
	void testAdminHashtags() throws Exception {
		mockMvc.perform(get("/admin/hashtags"))
				.andExpect(status().isOk())
				.andExpect(content().string(containsString("Hashtags")))
				.andExpect(content().string(containsString("href=\"/admin/hashtags\" aria-current=\"page\"")));
	}

	@Test
	@WithOwner
	void testAdminMedia() throws Exception {
		mockMvc.perform(get("/admin/media"))
				.andExpect(status().isOk())
				.andExpect(content().string(containsString("Media library")))
				.andExpect(content().string(containsString("href=\"/admin/media\" aria-current=\"page\"")));
	}

	@Test
	@WithOwner
	void testAdminComments() throws Exception {
		mockMvc.perform(get("/admin/comments"))
				.andExpect(status().isOk())
				.andExpect(content().string(containsString("Comments")))
				.andExpect(content().string(containsString("href=\"/admin/comments\" aria-current=\"page\"")));
	}

	@Test
	@WithOwner
	void testAdminUsers() throws Exception {
		mockMvc.perform(get("/admin/users"))
				.andExpect(status().isOk())
				.andExpect(content().string(containsString("Users")))
				.andExpect(content().string(containsString("href=\"/admin/users\" aria-current=\"page\"")));
	}

	@Test
	@WithOwner
	void testAdminRoles() throws Exception {
		mockMvc.perform(get("/admin/roles"))
				.andExpect(status().isOk())
				.andExpect(content().string(containsString("Roles")))
				.andExpect(content().string(containsString("href=\"/admin/roles\" aria-current=\"page\"")));
	}

	@Test
	@WithOwner
	void testAdminSettings() throws Exception {
		mockMvc.perform(get("/admin/settings"))
				.andExpect(status().isOk())
				.andExpect(content().string(containsString("Settings")))
				.andExpect(content().string(containsString("href=\"/admin/settings\" aria-current=\"page\"")));
	}

	@Test
	@WithOwner
	void testAdminProfile() throws Exception {
		mockMvc.perform(get("/admin/profile"))
				.andExpect(status().isOk())
				.andExpect(content().string(containsString("My profile")));
	}

	@Test
	void testAuthPages() throws Exception {
		mockMvc.perform(get("/auth/login"))
				.andExpect(status().isOk())
				.andExpect(content().string(containsString("Sign in")))
				.andExpect(content().string(containsString("href=\"/auth/register\"")));

		mockMvc.perform(get("/auth/register"))
				.andExpect(status().isOk())
				.andExpect(content().string(containsString("Create account")))
				.andExpect(content().string(containsString("href=\"/auth/login\"")));

		mockMvc.perform(get("/auth/forgot"))
				.andExpect(status().isOk())
				.andExpect(content().string(containsString("Reset password")))
				.andExpect(content().string(containsString("href=\"/auth/login\"")));
	}

	@Test
	void testAuthServiceRegister() {
		String testEmail = "testuser_" + System.currentTimeMillis() + "@example.com";
		RegisterRequest request = RegisterRequest.builder()
				.fullName("Test User")
				.email(testEmail)
				.password("secret123")
				.phone("0987654321")
				.address("Hanoi, Vietnam")
				.bio("AI Enthusiast")
				.build();

		User registered = authService.register(request);
		assertNotNull(registered.getId());
		assertEquals("Test User", registered.getFullName());
		assertEquals(testEmail, registered.getEmail());
		assertTrue(passwordEncoder.matches("secret123", registered.getPassword()));

		// Cleanup test user
		userRepository.delete(registered);
	}

	@Test
	void testRegisterValidationFailure() throws Exception {
		mockMvc.perform(org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post("/auth/register")
						.with(org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf())
						.param("fullName", "")
						.param("email", "invalid-email")
						.param("password", "123"))
				.andExpect(status().isOk())
				.andExpect(content().string(containsString("Full name is required")))
				.andExpect(content().string(containsString("Invalid email format")))
				.andExpect(content().string(containsString("Password must have at least 6 characters")));
	}

	@Test
	void testForgotValidationFailure() throws Exception {
		mockMvc.perform(org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post("/auth/forgot")
						.with(org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf())
						.param("email", ""))
				.andExpect(status().isOk())
				.andExpect(content().string(containsString("Email is required")));
	}

	@Test
	void testAuthPagesContainJqueryValidation() throws Exception {
		mockMvc.perform(org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get("/auth/login"))
				.andExpect(status().isOk())
				.andExpect(content().string(containsString("jquery.validate.min.js")));

		mockMvc.perform(org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get("/auth/register"))
				.andExpect(status().isOk())
				.andExpect(content().string(containsString("jquery.validate.min.js")));

		mockMvc.perform(org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get("/auth/forgot"))
				.andExpect(status().isOk())
				.andExpect(content().string(containsString("jquery.validate.min.js")));
	}
}
