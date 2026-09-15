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
import com.kienhee.blog.support.TestAuth;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import java.time.LocalDateTime;
import java.util.List;

import static org.hamcrest.Matchers.containsString;
import static org.junit.jupiter.api.Assertions.*;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

/** Replying from the Comments page: posted as the signed-in staff member, approved, one level deep. */
@SpringBootTest(properties = "app.posts.auto-publish=false")
@DisplayName("Reply from the Comments page")
class CommentReplyTests {

    @Autowired private WebApplicationContext context;
    @Autowired private PostRepository postRepository;
    @Autowired private CategoryRepository categoryRepository;
    @Autowired private CommentRepository commentRepository;
    @Autowired private UserRepository userRepository;
    @Autowired private JdbcTemplate jdbc;

    private MockMvc mockMvc;
    private User staff;
    private Post post;
    private final String tag = "reply" + System.nanoTime();

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity()).build();
        staff = userRepository.findAll().get(0);
        Category category = categoryRepository.save(Category.builder().name("Reply " + tag).slug(tag + "-cat").visible(true).build());
        post = postRepository.save(Post.builder().title("Reply post " + tag).slug(tag + "-post").content("<p>x</p>")
                .status(PostStatus.PUBLISHED).publishedAt(LocalDateTime.now()).category(category).author(staff).build());
    }

    @AfterEach
    void tearDown() {
        jdbc.update("delete from posts where slug like ?", tag + "%");
        jdbc.update("delete from categories where slug like ?", tag + "%");
    }

    private Comment guest(Comment parent, CommentStatus status) {
        return commentRepository.save(Comment.builder().post(post).parent(parent).authorName("Guest " + tag)
                .authorEmail("guest@example.com").content("Question from a reader").status(status).build());
    }

    @Test
    @DisplayName("a reply is posted under the staff account, approved, and approves the pending comment")
    void replyApprovesAndLinksStaff() throws Exception {
        Comment pending = guest(null, CommentStatus.PENDING);

        mockMvc.perform(post("/admin/comments/" + pending.getId() + "/reply").param("content", "Thanks for asking! " + tag)
                        .with(csrf()).with(TestAuth.owner(staff.getEmail())))
                .andExpect(status().is3xxRedirection())
                .andExpect(redirectedUrl("/admin/comments"))
                .andExpect(flash().attribute("successMessage", "Reply posted."));

        List<Comment> all = commentRepository.findByPostIdOrderByIdAsc(post.getId());
        assertEquals(2, all.size());
        assertEquals(CommentStatus.APPROVED, all.get(0).getStatus(), "replying approves the comment");
        Comment reply = all.get(1);
        assertEquals(CommentStatus.APPROVED, reply.getStatus());
        assertEquals(pending.getId(), reply.getParentId());
        assertEquals(staff.getEmail(), reply.getAuthorEmail());
        assertEquals(staff.getFullName(), reply.getAuthorName());

        mockMvc.perform(get("/article/" + post.getSlug()))
                .andExpect(content().string(containsString("Thanks for asking! " + tag)))
                .andExpect(content().string(containsString("class=\"tag\">Author</span>")));
    }

    @Test
    @DisplayName("replying to a reply goes under the top-level comment")
    void replyToReplyStaysOneLevel() throws Exception {
        Comment root = guest(null, CommentStatus.APPROVED);
        Comment child = guest(root, CommentStatus.APPROVED);
        mockMvc.perform(post("/admin/comments/" + child.getId() + "/reply").param("content", "Following up")
                        .with(csrf()).with(TestAuth.owner(staff.getEmail())))
                .andExpect(flash().attribute("successMessage", "Reply posted."));
        Comment reply = commentRepository.findByPostIdOrderByIdAsc(post.getId()).get(2);
        assertEquals(root.getId(), reply.getParentId());
    }

    @Test
    @DisplayName("an empty reply is refused, and replying needs comments:edit and CSRF")
    void rules() throws Exception {
        Comment c = guest(null, CommentStatus.APPROVED);
        mockMvc.perform(post("/admin/comments/" + c.getId() + "/reply").param("content", "   ")
                        .with(csrf()).with(TestAuth.owner(staff.getEmail())))
                .andExpect(flash().attribute("errorMessage", "Write a reply first."));
        mockMvc.perform(post("/admin/comments/" + c.getId() + "/reply").param("content", "Hi")
                        .with(csrf()).with(TestAuth.withPermissions(staff.getEmail(), "comments:view")))
                .andExpect(status().isForbidden());
        mockMvc.perform(post("/admin/comments/" + c.getId() + "/reply").param("content", "Hi").with(TestAuth.owner(staff.getEmail())))
                .andExpect(status().isForbidden());
        assertEquals(1, commentRepository.findByPostIdOrderByIdAsc(post.getId()).size());

        mockMvc.perform(get("/admin/comments").with(TestAuth.owner(staff.getEmail())))
                .andExpect(content().string(containsString("btn-reply-comment")))
                .andExpect(content().string(containsString("id=\"comment-reply-modal\"")));
    }
}
