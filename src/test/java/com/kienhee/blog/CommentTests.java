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
import com.kienhee.blog.service.SettingService;
import com.kienhee.blog.support.TestAuth;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.ResultActions;
import org.springframework.test.web.servlet.request.MockHttpServletRequestBuilder;
import org.springframework.test.web.servlet.request.RequestPostProcessor;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.concurrent.atomic.AtomicInteger;

import static org.hamcrest.Matchers.*;
import static org.junit.jupiter.api.Assertions.*;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

/** Public comment submission and admin moderation. Posts created here are deleted, which cascades to comments. */
@SpringBootTest
@DisplayName("Comments")
class CommentTests {

    private static final AtomicInteger IP_SEQ = new AtomicInteger();

    @Autowired private WebApplicationContext context;
    @Autowired private PostRepository postRepository;
    @Autowired private CategoryRepository categoryRepository;
    @Autowired private UserRepository userRepository;
    @Autowired private CommentRepository commentRepository;
    @Autowired private SettingService settingService;

    private MockMvc mockMvc;
    private final List<Post> posts = new ArrayList<>();
    private Category category;
    private User author;
    private Post post;
    private String originalModeration;
    private final String tag = "cmt" + System.nanoTime();

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity()).build();
        author = userRepository.findAll().get(0);
        category = categoryRepository.save(Category.builder().name("Comments " + tag).slug("c-" + tag).visible(true).build());
        post = savePost("live", PostStatus.PUBLISHED);
        originalModeration = settingService.get("comments.moderation").orElse("true");
        settingService.saveAll(Map.of("comments.moderation", "true"));
    }

    @AfterEach
    void tearDown() {
        postRepository.deleteAll(posts);
        categoryRepository.delete(category);
        settingService.saveAll(Map.of("comments.moderation", originalModeration));
    }

    private Post savePost(String suffix, PostStatus status) {
        Post p = postRepository.save(Post.builder()
                .title("Post " + suffix + " " + tag).slug(suffix + "-" + tag).content("<p>Body</p>")
                .status(status).category(category).author(author)
                .publishedAt(status == PostStatus.PUBLISHED ? LocalDateTime.now() : null)
                .build());
        posts.add(p);
        return p;
    }

    /** Each call gets its own client IP so the per-IP rate limit never leaks between tests. */
    private static RequestPostProcessor freshIp() {
        return ip("10.77." + (IP_SEQ.get() / 250) + "." + (IP_SEQ.getAndIncrement() % 250));
    }

    private static RequestPostProcessor ip(String address) {
        return request -> {
            request.setRemoteAddr(address);
            return request;
        };
    }

    private MockHttpServletRequestBuilder guestComment(Post target, String content, Long parentId) {
        MockHttpServletRequestBuilder req = post("/article/" + target.getSlug() + "/comments")
                .param("authorName", "Guest Reader")
                .param("authorEmail", "Guest@Example.com")
                .param("content", content)
                .with(csrf());
        if (parentId != null) req.param("parentId", parentId.toString());
        return req;
    }

    private List<Comment> commentsOf(Post target) {
        return commentRepository.findByPostIdOrderByIdAsc(target.getId());
    }

    private ResultActions approve(Long id) throws Exception {
        return mockMvc.perform(post("/admin/comments/" + id + "/status").param("status", "APPROVED")
                .with(TestAuth.owner()).with(csrf()));
    }

    @Test
    @DisplayName("a guest comment is saved as pending and not shown until approved")
    void guestCommentPendingThenApproved() throws Exception {
        String text = "Pending text " + tag;
        mockMvc.perform(guestComment(post, text, null).with(freshIp()))
                .andExpect(status().is3xxRedirection())
                .andExpect(redirectedUrl("/article/" + post.getSlug() + "#comments"))
                .andExpect(flash().attribute("commentSuccess", containsString("awaiting moderation")));

        List<Comment> saved = commentsOf(post);
        assertEquals(1, saved.size());
        Comment c = saved.get(0);
        assertEquals(CommentStatus.PENDING, c.getStatus());
        assertEquals("guest@example.com", c.getAuthorEmail(), "email is normalised");
        assertNotNull(c.getIpAddress());

        mockMvc.perform(get("/article/" + post.getSlug()))
                .andExpect(content().string(not(containsString(text))))
                .andExpect(content().string(containsString("0 comments")));

        approve(c.getId()).andExpect(status().is3xxRedirection());

        mockMvc.perform(get("/article/" + post.getSlug()))
                .andExpect(content().string(containsString(text)))
                .andExpect(content().string(containsString("1 comment")))
                .andExpect(content().string(not(containsString("guest@example.com"))));
    }

    @Test
    @DisplayName("a reply to a reply is stored under the top-level comment")
    void repliesAreOneLevelDeep() throws Exception {
        settingService.saveAll(Map.of("comments.moderation", "false"));
        mockMvc.perform(guestComment(post, "Root " + tag, null).with(freshIp()));
        Long rootId = commentsOf(post).get(0).getId();
        mockMvc.perform(guestComment(post, "Reply " + tag, rootId).with(freshIp()));
        Long replyId = commentsOf(post).get(1).getId();
        mockMvc.perform(guestComment(post, "Reply to reply " + tag, replyId).with(freshIp()));

        List<Comment> saved = commentsOf(post);
        assertEquals(3, saved.size());
        assertEquals(rootId, saved.get(1).getParentId());
        assertEquals(rootId, saved.get(2).getParentId());
        assertTrue(saved.stream().allMatch(c -> c.getStatus() == CommentStatus.APPROVED), "moderation is off");

        mockMvc.perform(get("/article/" + post.getSlug()))
                .andExpect(content().string(containsString("Reply to reply " + tag)))
                .andExpect(content().string(containsString("3 comments")));
    }

    @Test
    @DisplayName("a signed-in user is approved immediately and named from the account")
    void signedInUserApproved() throws Exception {
        mockMvc.perform(post("/article/" + post.getSlug() + "/comments")
                        .param("content", "Staff note " + tag)
                        .with(TestAuth.owner(author.getEmail())).with(csrf()).with(freshIp()))
                .andExpect(status().is3xxRedirection())
                .andExpect(flash().attribute("commentSuccess", containsString("live")));

        Comment c = commentsOf(post).get(0);
        assertEquals(CommentStatus.APPROVED, c.getStatus());
        assertEquals(author.getFullName(), c.getAuthorName());
        assertEquals(author.getEmail(), c.getAuthorEmail());

        mockMvc.perform(get("/article/" + post.getSlug()))
                .andExpect(content().string(containsString("Staff note " + tag)))
                .andExpect(content().string(containsString("class=\"tag\">Author</span>")));
    }

    @Test
    @DisplayName("invalid input is rejected with a message and the text is kept")
    void validation() throws Exception {
        mockMvc.perform(post("/article/" + post.getSlug() + "/comments")
                        .param("authorName", "Guest").param("authorEmail", "not-an-email").param("content", "Keep me")
                        .with(csrf()).with(freshIp()))
                .andExpect(flash().attribute("commentError", "Please enter a valid email."))
                .andExpect(flash().attributeExists("commentForm"));
        mockMvc.perform(guestComment(post, "   ", null).with(freshIp()))
                .andExpect(flash().attribute("commentError", "Please write a comment."));
        mockMvc.perform(guestComment(post, "x".repeat(2001), null).with(freshIp()))
                .andExpect(flash().attributeExists("commentError"));
        assertTrue(commentsOf(post).isEmpty());
    }

    @Test
    @DisplayName("comments are rendered as text, never as HTML")
    void contentIsEscaped() throws Exception {
        settingService.saveAll(Map.of("comments.moderation", "false"));
        mockMvc.perform(guestComment(post, "<script>alert('x')</script> " + tag, null).with(freshIp()));
        mockMvc.perform(get("/article/" + post.getSlug()))
                .andExpect(content().string(containsString("&lt;script&gt;")))
                .andExpect(content().string(not(containsString("<script>alert('x')</script>"))));
    }

    @Test
    @DisplayName("the honeypot silently drops bot submissions")
    void honeypot() throws Exception {
        mockMvc.perform(guestComment(post, "Buy followers " + tag, null).param("website", "http://spam.example").with(freshIp()))
                .andExpect(status().is3xxRedirection())
                .andExpect(flash().attributeExists("commentSuccess"));
        assertTrue(commentsOf(post).isEmpty());
    }

    @Test
    @DisplayName("replying to a comment from another post or a pending comment is refused")
    void invalidParent() throws Exception {
        Post other = savePost("other", PostStatus.PUBLISHED);
        settingService.saveAll(Map.of("comments.moderation", "false"));
        mockMvc.perform(guestComment(other, "On other " + tag, null).with(freshIp()));
        Long foreign = commentsOf(other).get(0).getId();

        mockMvc.perform(guestComment(post, "Sneaky reply " + tag, foreign).with(freshIp()))
                .andExpect(flash().attribute("commentError", containsString("no longer available")));
        assertTrue(commentsOf(post).isEmpty());

        settingService.saveAll(Map.of("comments.moderation", "true"));
        mockMvc.perform(guestComment(post, "Pending root " + tag, null).with(freshIp()));
        Long pendingRoot = commentsOf(post).get(0).getId();
        mockMvc.perform(guestComment(post, "Reply to pending " + tag, pendingRoot).with(freshIp()))
                .andExpect(flash().attribute("commentError", containsString("no longer available")));
        assertEquals(1, commentsOf(post).size());
    }

    @Test
    @DisplayName("unpublished or unknown posts cannot be commented on")
    void onlyPublishedPosts() throws Exception {
        Post draft = savePost("draft", PostStatus.DRAFT);
        mockMvc.perform(guestComment(draft, "Hi " + tag, null).with(freshIp())).andExpect(status().isNotFound());
        mockMvc.perform(post("/article/missing-" + tag + "/comments").param("content", "x").with(csrf()).with(freshIp()))
                .andExpect(status().isNotFound());
        assertTrue(commentsOf(draft).isEmpty());
    }

    @Test
    @DisplayName("more than 5 comments from one IP in 10 minutes are refused")
    void rateLimit() throws Exception {
        RequestPostProcessor sameIp = ip("10.88.1." + (IP_SEQ.getAndIncrement() % 250));
        for (int i = 0; i < 5; i++) {
            mockMvc.perform(guestComment(post, "Comment " + i + " " + tag, null).with(sameIp))
                    .andExpect(flash().attributeExists("commentSuccess"));
        }
        mockMvc.perform(guestComment(post, "One too many " + tag, null).with(sameIp))
                .andExpect(flash().attribute("commentError", startsWith("Too many comments")));
        assertEquals(5, commentsOf(post).size());
    }

    @Test
    @DisplayName("a comment POST without a CSRF token is rejected")
    void csrfRequired() throws Exception {
        mockMvc.perform(post("/article/" + post.getSlug() + "/comments")
                        .param("authorName", "G").param("authorEmail", "g@example.com").param("content", "No token")
                        .with(freshIp()))
                .andExpect(status().isForbidden());
        assertTrue(commentsOf(post).isEmpty());
    }

    @Test
    @DisplayName("admin list shows comments with the pending count")
    void adminList() throws Exception {
        mockMvc.perform(guestComment(post, "Visible in admin " + tag, null).with(freshIp()));
        mockMvc.perform(get("/admin/comments").with(TestAuth.owner()))
                .andExpect(status().isOk())
                .andExpect(content().string(containsString("Visible in admin " + tag)))
                .andExpect(content().string(containsString("awaiting moderation")))
                .andExpect(content().string(containsString("guest@example.com")));
    }

    @Test
    @DisplayName("bulk status and bulk delete, each behind its own permission")
    void bulkAndPermissions() throws Exception {
        mockMvc.perform(guestComment(post, "A " + tag, null).with(freshIp()));
        mockMvc.perform(guestComment(post, "B " + tag, null).with(freshIp()));
        List<Comment> saved = commentsOf(post);
        String[] ids = saved.stream().map(c -> c.getId().toString()).toArray(String[]::new);

        mockMvc.perform(post("/admin/comments/" + ids[0] + "/status").param("status", "APPROVED")
                        .with(TestAuth.withPermissions("viewer@test.com", "comments:view")).with(csrf()))
                .andExpect(status().isForbidden());

        mockMvc.perform(post("/admin/comments/bulk-status").param("ids", ids).param("status", "spam")
                        .with(TestAuth.withPermissions("mod@test.com", "comments:view", "comments:edit")).with(csrf()))
                .andExpect(flash().attribute("successMessage", "2 comments marked as spam."));
        assertTrue(commentsOf(post).stream().allMatch(c -> c.getStatus() == CommentStatus.SPAM));

        mockMvc.perform(post("/admin/comments/bulk-delete").param("ids", ids)
                        .with(TestAuth.withPermissions("mod@test.com", "comments:view", "comments:edit")).with(csrf()))
                .andExpect(status().isForbidden());
        assertEquals(2, commentsOf(post).size());

        mockMvc.perform(post("/admin/comments/bulk-status").param("status", "APPROVED")
                        .with(TestAuth.owner()).with(csrf()))
                .andExpect(flash().attribute("errorMessage", "Select at least one comment."));
        mockMvc.perform(post("/admin/comments/bulk-status").param("ids", ids).param("status", "bogus")
                        .with(TestAuth.owner()).with(csrf()))
                .andExpect(flash().attribute("errorMessage", "Unknown comment status."));

        mockMvc.perform(post("/admin/comments/bulk-delete").param("ids", ids).with(TestAuth.owner()).with(csrf()))
                .andExpect(flash().attribute("successMessage", "2 comments moved to trash."));
        assertTrue(commentsOf(post).isEmpty());
    }

    @Test
    @DisplayName("deleting a comment also deletes its replies")
    void deleteCascadesToReplies() throws Exception {
        settingService.saveAll(Map.of("comments.moderation", "false"));
        mockMvc.perform(guestComment(post, "Parent " + tag, null).with(freshIp()));
        Long rootId = commentsOf(post).get(0).getId();
        mockMvc.perform(guestComment(post, "Child " + tag, rootId).with(freshIp()));
        assertEquals(2, commentsOf(post).size());

        mockMvc.perform(post("/admin/comments/" + rootId + "/delete").with(TestAuth.owner()).with(csrf()))
                .andExpect(flash().attribute("successMessage", "1 comment moved to trash."));
        assertTrue(commentsOf(post).isEmpty());
    }
}
