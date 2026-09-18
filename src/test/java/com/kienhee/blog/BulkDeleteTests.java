package com.kienhee.blog;

import org.springframework.jdbc.core.JdbcTemplate;
import com.kienhee.blog.entity.Category;
import com.kienhee.blog.entity.Hashtag;
import com.kienhee.blog.entity.Post;
import com.kienhee.blog.entity.PostStatus;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.CategoryRepository;
import com.kienhee.blog.repository.HashtagRepository;
import com.kienhee.blog.repository.PostRepository;
import com.kienhee.blog.repository.RoleRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.support.TestAuth;
import com.kienhee.blog.support.TestLocale;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import java.util.ArrayList;
import java.util.List;

import static org.hamcrest.Matchers.containsString;
import static org.hamcrest.Matchers.not;
import static org.junit.jupiter.api.Assertions.*;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

/** Bulk delete on Users, Categories, Hashtags and Posts. Everything created here is removed again. */
@SpringBootTest
@DisplayName("Bulk delete on list pages")
class BulkDeleteTests {

    @Autowired private WebApplicationContext context;
    @Autowired private CategoryRepository categoryRepository;
    @Autowired private HashtagRepository hashtagRepository;
    @Autowired private PostRepository postRepository;
    @Autowired private UserRepository userRepository;
    @Autowired private RoleRepository roleRepository;
    @Autowired private PasswordEncoder passwordEncoder;
    @Autowired private JdbcTemplate jdbc;

    private MockMvc mockMvc;
    private final String tag = "bulk" + System.nanoTime();
    private final List<Long> categoryIds = new ArrayList<>();
    private final List<Long> postIds = new ArrayList<>();
    private final List<String> emails = new ArrayList<>();

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity())
                .defaultRequest(TestLocale.englishByDefault()).build();
    }

    @AfterEach
    void tearDown() {
        // Deleted rows now sit in the trash, invisible to JPA: purge them with SQL.
        jdbc.update("delete from posts where slug like ?", tag + "%");
        jdbc.update("delete from categories where slug like ? and parent_id is not null", tag + "%");
        jdbc.update("delete from categories where slug like ?", tag + "%");
        jdbc.update("delete from hashtags where slug like ?", tag + "%");
        postIds.forEach(id -> postRepository.findById(id).ifPresent(postRepository::delete));
        // children before parents
        for (int i = categoryIds.size() - 1; i >= 0; i--) {
            categoryRepository.findById(categoryIds.get(i)).ifPresent(categoryRepository::delete);
        }
        hashtagRepository.findAll().stream().filter(h -> h.getSlug().startsWith(tag)).forEach(hashtagRepository::delete);
        emails.forEach(e -> userRepository.findByEmail(e).ifPresent(userRepository::delete));
    }

    private Category category(String name, Category parent) {
        Category c = categoryRepository.save(Category.builder().name(name + " " + tag).slug(tag + "-" + name).visible(true).parent(parent).build());
        categoryIds.add(c.getId());
        return c;
    }

    private User user(String name, String roleSlug) {
        String email = tag + "-" + name + "@test.com";
        emails.add(email);
        return userRepository.save(User.builder().fullName("Bulk " + name).email(email)
                .password(passwordEncoder.encode("Password123")).role(roleRepository.findBySlug(roleSlug).orElseThrow()).build());
    }

    private Post savePost(String name, Category category, User author) {
        Post p = postRepository.save(Post.builder().title("Bulk " + name + " " + tag).slug(tag + "-post-" + name).content("<p>x</p>")
                .status(PostStatus.DRAFT).category(category).author(author).build());
        postIds.add(p.getId());
        return p;
    }

    @Test
    @DisplayName("categories: deletable ones go, the rest are skipped with the reason")
    void categories() throws Exception {
        Category a = category("a", null);
        Category b = category("b", null);
        Category parent = category("parent", null);
        category("child", parent);
        Category withPosts = category("used", null);
        savePost("in-used", withPosts, userRepository.findAll().get(0));

        mockMvc.perform(post("/admin/categories/bulk-delete").with(csrf()).with(TestAuth.owner())
                        .param("ids", a.getId().toString(), b.getId().toString(), parent.getId().toString(), withPosts.getId().toString()))
                .andExpect(status().is3xxRedirection())
                .andExpect(redirectedUrl("/admin/categories"))
                .andExpect(flash().attribute("errorMessage", containsString("2 categories moved to trash. 2 skipped")))
                .andExpect(flash().attribute("errorMessage", containsString("subcategories")))
                .andExpect(flash().attribute("errorMessage", containsString("still has posts")));

        assertTrue(categoryRepository.findById(a.getId()).isEmpty());
        assertTrue(categoryRepository.findById(b.getId()).isEmpty());
        assertTrue(categoryRepository.findById(parent.getId()).isPresent());
        assertTrue(categoryRepository.findById(withPosts.getId()).isPresent());
    }

    @Test
    @DisplayName("hashtags and posts: all selected rows are deleted")
    void hashtagsAndPosts() throws Exception {
        Hashtag h1 = hashtagRepository.save(Hashtag.builder().name("H1 " + tag).slug(tag + "-h1").active(true).build());
        Hashtag h2 = hashtagRepository.save(Hashtag.builder().name("H2 " + tag).slug(tag + "-h2").active(true).build());
        mockMvc.perform(post("/admin/hashtags/bulk-delete").with(csrf()).with(TestAuth.owner())
                        .param("ids", h1.getId().toString(), h2.getId().toString()))
                .andExpect(flash().attribute("successMessage", "2 hashtags moved to trash."));
        assertTrue(hashtagRepository.findById(h1.getId()).isEmpty());

        Category c = category("posts", null);
        User author = userRepository.findAll().get(0);
        Post p1 = savePost("p1", c, author);
        Post p2 = savePost("p2", c, author);
        mockMvc.perform(post("/admin/posts/bulk-delete").with(csrf()).with(TestAuth.owner())
                        .param("ids", p1.getId().toString(), p2.getId().toString()))
                .andExpect(redirectedUrl("/admin/posts"))
                .andExpect(flash().attribute("successMessage", "2 posts moved to trash."));
        assertTrue(postRepository.findById(p1.getId()).isEmpty());
        assertTrue(postRepository.findById(p2.getId()).isEmpty());
    }

    @Test
    @DisplayName("users: yourself and admins (without admin rights) are skipped")
    void users() throws Exception {
        User me = user("me", "user");
        User u1 = user("u1", "user");
        User u2 = user("u2", "user");
        User anAdmin = user("admin", "admin");

        mockMvc.perform(post("/admin/users/bulk-delete").with(csrf())
                        .with(TestAuth.withPermissions(me.getEmail(), "users:view", "users:delete"))
                        .param("ids", me.getId().toString(), u1.getId().toString(), u2.getId().toString(), anAdmin.getId().toString()))
                .andExpect(redirectedUrl("/admin/users"))
                .andExpect(flash().attribute("errorMessage", containsString("2 users deleted. 2 skipped")))
                .andExpect(flash().attribute("errorMessage", containsString("You cannot delete your own account.")))
                .andExpect(flash().attribute("errorMessage", containsString("Only admins")));

        assertTrue(userRepository.findById(u1.getId()).isEmpty());
        assertTrue(userRepository.findById(u2.getId()).isEmpty());
        assertTrue(userRepository.findById(me.getId()).isPresent());
        assertTrue(userRepository.findById(anAdmin.getId()).isPresent());
    }

    @Test
    @DisplayName("each bulk delete needs its resource's delete permission, and an empty selection is reported")
    void permissionsAndEmpty() throws Exception {
        Category c = category("keep", null);
        mockMvc.perform(post("/admin/categories/bulk-delete").with(csrf())
                        .with(TestAuth.withPermissions("viewer@test.com", "categories:view"))
                        .param("ids", c.getId().toString()))
                .andExpect(status().isForbidden());
        assertTrue(categoryRepository.findById(c.getId()).isPresent());

        mockMvc.perform(post("/admin/posts/bulk-delete").with(csrf()).with(TestAuth.owner()))
                .andExpect(flash().attribute("errorMessage", "Select at least one post."));
        mockMvc.perform(post("/admin/hashtags/bulk-delete").with(TestAuth.owner()).param("ids", "1"))
                .andExpect(status().isForbidden());
    }

    @Test
    @DisplayName("list pages wire bulk delete and no longer offer Export CSV")
    void listPagesWired() throws Exception {
        for (String[] page : new String[][]{
                {"/admin/users", "/admin/users/bulk-delete"},
                {"/admin/categories", "/admin/categories/bulk-delete"},
                {"/admin/hashtags", "/admin/hashtags/bulk-delete"},
                {"/admin/posts", "/admin/posts/bulk-delete"}}) {
            mockMvc.perform(get(page[0]).with(TestAuth.owner()))
                    .andExpect(status().isOk())
                    .andExpect(content().string(containsString("data-bulk-delete-url=\"" + page[1] + "\"")))
                    .andExpect(content().string(not(containsString("Export CSV"))));
        }
        mockMvc.perform(get("/admin/posts").with(TestAuth.withPermissions("reader@test.com", "posts:view")))
                .andExpect(content().string(not(containsString("data-bulk=\"Delete\""))))
                .andExpect(content().string(not(containsString("btn-del-post"))));
    }
}
