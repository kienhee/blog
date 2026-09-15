package com.kienhee.blog;

import com.kienhee.blog.entity.Category;
import com.kienhee.blog.entity.Comment;
import com.kienhee.blog.entity.CommentStatus;
import com.kienhee.blog.entity.Post;
import com.kienhee.blog.entity.PostStatus;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.CategoryRepository;
import com.kienhee.blog.repository.CommentRepository;
import com.kienhee.blog.repository.PostRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.DashboardService;
import com.kienhee.blog.service.DashboardService.DashboardStats;
import com.kienhee.blog.support.TestAuth;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;

import static org.hamcrest.Matchers.containsString;
import static org.hamcrest.Matchers.not;
import static org.junit.jupiter.api.Assertions.*;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

/** Dashboard and sidebar show real numbers only. Posts created here are deleted (comments cascade). */
@SpringBootTest
@DisplayName("Dashboard (real data)")
class DashboardTests {

    @Autowired private WebApplicationContext context;
    @Autowired private DashboardService dashboardService;
    @Autowired private PostRepository postRepository;
    @Autowired private CategoryRepository categoryRepository;
    @Autowired private CommentRepository commentRepository;
    @Autowired private UserRepository userRepository;

    private MockMvc mockMvc;
    private User author;
    private Category category;
    private final List<Post> posts = new ArrayList<>();
    private final String tag = "dash" + System.nanoTime();

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity()).build();
        author = userRepository.findAll().get(0);
        category = categoryRepository.save(Category.builder().name("Dash " + tag).slug("dash-" + tag).visible(true).build());
    }

    @AfterEach
    void tearDown() {
        postRepository.deleteAll(posts);
        categoryRepository.delete(category);
    }

    private Post post(PostStatus status, String seoTitle, LocalDateTime publishedAt) {
        Post p = postRepository.save(Post.builder()
                .title("Dash post " + tag + " " + posts.size()).slug(tag + "-" + posts.size()).content("<p>x</p>")
                .status(status).category(category).author(author).seoTitle(seoTitle).publishedAt(publishedAt).build());
        posts.add(p);
        return p;
    }

    private Comment pendingComment(Post target, String authorName) {
        return commentRepository.save(Comment.builder().post(target).authorName(authorName).authorEmail("dash@example.com")
                .content("Pending dashboard comment " + tag).status(CommentStatus.PENDING).build());
    }

    @Test
    @DisplayName("renders real numbers and none of the old fake ones")
    void noFakeNumbers() throws Exception {
        long published = postRepository.findAll().stream().filter(p -> p.getStatus() == PostStatus.PUBLISHED).count();
        long pending = commentRepository.countByStatus(CommentStatus.PENDING);

        var result = mockMvc.perform(get("/admin/dashboard").with(TestAuth.owner()))
                .andExpect(status().isOk())
                .andExpect(content().string(not(containsString("48.2k"))))
                .andExpect(content().string(not(containsString("9,412"))))
                .andExpect(content().string(not(containsString("+12.4%"))))
                .andExpect(content().string(not(containsString("Redirect loops"))))
                .andExpect(content().string(not(containsString("Subscribers"))))
                .andReturn();
        DashboardStats stats = (DashboardStats) result.getModelAndView().getModel().get("stats");
        assertEquals(published, stats.publishedTotal());
        assertEquals(pending, stats.commentsPending());
        assertEquals(userRepository.count(), stats.users());
    }

    @Test
    @DisplayName("new posts and comments show up in the numbers and lists")
    void reflectsNewContent() throws Exception {
        DashboardStats before = dashboardService.load();

        Post fresh = post(PostStatus.PUBLISHED, null, LocalDateTime.now().minusDays(1));
        post(PostStatus.PUBLISHED, "Has a title", LocalDateTime.now().minusDays(45));
        post(PostStatus.DRAFT, null, null);
        Comment comment = pendingComment(fresh, "Dash Reader " + tag);

        DashboardStats after = dashboardService.load();
        assertEquals(before.publishedTotal() + 2, after.publishedTotal());
        assertEquals(before.publishedLast30Days() + 1, after.publishedLast30Days(), "the 1-day-old post only");
        assertEquals(before.publishedPrevious30Days() + 1, after.publishedPrevious30Days(), "the 45-day-old post only");
        assertEquals(before.drafts() + 1, after.drafts());
        assertEquals(before.missingSeoTitle() + 1, after.missingSeoTitle(), "one of the two published posts has no SEO title");
        assertEquals(before.commentsPending() + 1, after.commentsPending());
        assertEquals(comment.getId(), after.pendingComments().get(0).getId(), "newest pending comment first");
        assertTrue(after.recentPosts().stream().anyMatch(p -> p.getTitle().startsWith("Dash post " + tag)));

        mockMvc.perform(get("/admin/dashboard").with(TestAuth.owner()))
                .andExpect(content().string(containsString("Dash Reader " + tag)))
                .andExpect(content().string(containsString("Dash post " + tag)));
    }

    @Test
    @DisplayName("cards follow permissions: no comment content without comments:view")
    void followsPermissions() throws Exception {
        Post fresh = post(PostStatus.PUBLISHED, null, LocalDateTime.now());
        pendingComment(fresh, "Secret Reader " + tag);

        mockMvc.perform(get("/admin/dashboard").with(TestAuth.withPermissions("poster@test.com", "dashboard:view", "posts:view")))
                .andExpect(status().isOk())
                .andExpect(content().string(containsString("Published posts by category")))
                .andExpect(content().string(not(containsString("Secret Reader " + tag))))
                .andExpect(content().string(not(containsString("awaiting moderation"))));

        mockMvc.perform(get("/admin/dashboard").with(TestAuth.withPermissions("moderator@test.com", "dashboard:view", "comments:view")))
                .andExpect(status().isOk())
                .andExpect(content().string(containsString("Secret Reader " + tag)))
                .andExpect(content().string(not(containsString("Recently edited posts"))));
    }

    @Test
    @DisplayName("sidebar has no hardcoded counters")
    void sidebarWithoutFakeCounts() throws Exception {
        mockMvc.perform(get("/admin/dashboard").with(TestAuth.owner()))
                .andExpect(content().string(not(containsString("<span class=\"c\">"))));
    }

    @Test
    @DisplayName("media size is human readable")
    void mediaSizeFormat() {
        assertEquals("512 B", stats(512).mediaSize());
        assertEquals("48 KB", stats(48 * 1024).mediaSize());
        assertEquals("3.5 MB", stats((long) (3.5 * 1024 * 1024)).mediaSize());
        assertEquals("1.2 GB", stats((long) (1.2 * 1024 * 1024 * 1024)).mediaSize());
    }

    private static DashboardStats stats(long mediaBytes) {
        return new DashboardStats(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, mediaBytes, 0, List.of(), List.of(), List.of(), 0, 0, List.of(), 0);
    }
}
