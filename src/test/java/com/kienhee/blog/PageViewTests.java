package com.kienhee.blog;

import com.kienhee.blog.controller.ViewCountingPolicy;
import com.kienhee.blog.entity.Category;
import com.kienhee.blog.entity.Post;
import com.kienhee.blog.entity.PostStatus;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.CategoryRepository;
import com.kienhee.blog.repository.PostRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.DashboardService;
import com.kienhee.blog.support.TestAuth;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.mock.web.MockHttpServletRequest;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import java.time.LocalDate;
import java.time.LocalDateTime;

import static org.hamcrest.Matchers.containsString;
import static org.junit.jupiter.api.Assertions.*;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.content;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;

/** Article views: counted per day for real readers only; shown on the dashboard. */
@SpringBootTest(properties = "app.posts.auto-publish=false")
@DisplayName("Page views")
class PageViewTests {

    private static final String BROWSER = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130.0 Safari/537.36";

    @Autowired private WebApplicationContext context;
    @Autowired private JdbcTemplate jdbc;
    @Autowired private PostRepository postRepository;
    @Autowired private CategoryRepository categoryRepository;
    @Autowired private UserRepository userRepository;
    @Autowired private DashboardService dashboardService;

    private MockMvc mockMvc;
    private Post post;
    private Category category;
    private User author;
    private final String tag = "views" + System.nanoTime();

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity()).build();
        author = userRepository.findAll().get(0);
        category = categoryRepository.save(Category.builder().name("Views " + tag).slug(tag + "-cat").visible(true).build());
        post = postRepository.save(Post.builder().title("Most read " + tag).slug(tag + "-post").content("<p>x</p>")
                .status(PostStatus.PUBLISHED).publishedAt(LocalDateTime.now()).category(category).author(author).build());
    }

    @AfterEach
    void tearDown() {
        jdbc.update("delete from posts where slug like ?", tag + "%"); // views cascade
        jdbc.update("delete from categories where slug like ?", tag + "%");
    }

    private long viewsToday(Long postId) {
        Long n = jdbc.queryForObject("select coalesce(sum(views), 0) from post_view_daily where post_id = ? and day = ?",
                Long.class, postId, LocalDate.now());
        return n == null ? 0 : n;
    }

    @Test
    @DisplayName("browser visits count; bots, previews, prefetches, missing user agents and staff don't")
    void countsReadersOnly() throws Exception {
        String url = "/article/" + post.getSlug();
        mockMvc.perform(get(url).header("User-Agent", BROWSER)).andExpect(status().isOk());
        mockMvc.perform(get(url).header("User-Agent", BROWSER)).andExpect(status().isOk());
        assertEquals(2, viewsToday(post.getId()));

        mockMvc.perform(get(url).header("User-Agent", "Mozilla/5.0 (compatible; Googlebot/2.1)"));
        mockMvc.perform(get(url).header("User-Agent", "curl/8.4"));
        mockMvc.perform(get(url));
        mockMvc.perform(get(url).header("User-Agent", BROWSER).header("Sec-Purpose", "prefetch"));
        mockMvc.perform(get(url).header("User-Agent", BROWSER).with(TestAuth.withPermissions("staff@test.com", "dashboard:view")));
        assertEquals(2, viewsToday(post.getId()), "none of those counted");
    }

    @Test
    @DisplayName("a request that isn't a GET never counts")
    void onlyGets() {
        MockHttpServletRequest head = new MockHttpServletRequest("HEAD", "/article/x");
        head.addHeader("User-Agent", BROWSER);
        assertFalse(ViewCountingPolicy.isCountable(head));
    }

    @Test
    @DisplayName("unpublished posts are 404 and record nothing")
    void draftsRecordNothing() throws Exception {
        Post draft = postRepository.save(Post.builder().title("Draft " + tag).slug(tag + "-draft").content("<p>x</p>")
                .status(PostStatus.DRAFT).category(category).author(author).build());
        mockMvc.perform(get("/article/" + draft.getSlug()).header("User-Agent", BROWSER)).andExpect(status().isNotFound());
        assertEquals(0, viewsToday(draft.getId()));
    }

    @Test
    @DisplayName("the dashboard shows views for the last 30 days and the most read posts")
    void dashboard() throws Exception {
        DashboardService.DashboardStats before = dashboardService.load();
        for (int i = 0; i < 3; i++) {
            mockMvc.perform(get("/article/" + post.getSlug()).header("User-Agent", BROWSER));
        }
        jdbc.update("insert into post_view_daily (post_id, day, views) values (?, ?, 4)", post.getId(), LocalDate.now().minusDays(40));

        DashboardService.DashboardStats after = dashboardService.load();
        assertEquals(before.viewsLast30Days() + 3, after.viewsLast30Days());
        assertEquals(before.viewsPrevious30Days() + 4, after.viewsPrevious30Days(), "40 days ago falls in the previous window");
        assertTrue(after.topPosts().stream().anyMatch(t -> t.id().equals(post.getId()) && t.views() == 3));

        mockMvc.perform(get("/admin/dashboard").with(TestAuth.owner()))
                .andExpect(content().string(containsString("Most read · last 30 days")))
                .andExpect(content().string(containsString(post.getTitle())));
    }
}
