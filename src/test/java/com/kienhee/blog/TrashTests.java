package com.kienhee.blog;

import com.kienhee.blog.service.TrashService;
import com.kienhee.blog.entity.Category;
import com.kienhee.blog.entity.Comment;
import com.kienhee.blog.entity.CommentStatus;
import com.kienhee.blog.entity.Hashtag;
import com.kienhee.blog.entity.Post;
import com.kienhee.blog.entity.PostStatus;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.CategoryRepository;
import com.kienhee.blog.repository.CommentRepository;
import com.kienhee.blog.repository.HashtagRepository;
import com.kienhee.blog.repository.PostRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.support.TestAuth;
import com.kienhee.blog.support.TestLocale;
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

import static org.hamcrest.Matchers.containsString;
import static org.hamcrest.Matchers.not;
import static org.junit.jupiter.api.Assertions.*;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

/**
 * Trash for posts, categories, hashtags and comments. Trashed rows are invisible to JPA, so setup checks and
 * cleanup use plain SQL; everything created here is removed again (including what is left in the trash).
 */
@SpringBootTest(properties = "app.posts.auto-publish=false")
@DisplayName("Trash")
class TrashTests {

    @Autowired private WebApplicationContext context;
    @Autowired private JdbcTemplate jdbc;
    @Autowired private TrashService trashService;
    @Autowired private PostRepository postRepository;
    @Autowired private CategoryRepository categoryRepository;
    @Autowired private HashtagRepository hashtagRepository;
    @Autowired private CommentRepository commentRepository;
    @Autowired private UserRepository userRepository;

    private MockMvc mockMvc;
    private User author;
    private Category category;
    private final String tag = "trash" + System.nanoTime();

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity())
                .defaultRequest(TestLocale.englishByDefault()).build();
        author = userRepository.findAll().get(0);
        category = categoryRepository.save(Category.builder().name("Trash " + tag).slug(tag + "-cat").visible(true).build());
    }

    @AfterEach
    void tearDown() {
        jdbc.update("delete from posts where slug like ?", tag + "%");            // comments + hashtag links cascade
        jdbc.update("delete from categories where slug like ? and parent_id is not null", tag + "%");
        jdbc.update("delete from categories where slug like ?", tag + "%");
        jdbc.update("delete from hashtags where slug like ?", tag + "%");
    }

    private Post savePost(String name, PostStatus status) {
        return postRepository.save(Post.builder().title("Trash post " + name + " " + tag).slug(tag + "-" + name)
                .excerpt("Excerpt").content("<p>Body</p>").status(status)
                .publishedAt(status == PostStatus.PUBLISHED ? LocalDateTime.now() : null)
                .category(category).author(author).build());
    }

    private Comment comment(Post target, Comment parent, String who) {
        return commentRepository.save(Comment.builder().post(target).parent(parent).authorName(who + " " + tag)
                .authorEmail("reader@example.com").content("Comment by " + who).status(CommentStatus.APPROVED).build());
    }

    private boolean inTrash(String table, Long id) {
        Integer n = jdbc.queryForObject("select count(*) from " + table + " where id = ? and deleted_at is not null", Integer.class, id);
        return n != null && n == 1;
    }

    private boolean exists(String table, Long id) {
        Integer n = jdbc.queryForObject("select count(*) from " + table + " where id = ?", Integer.class, id);
        return n != null && n == 1;
    }

    @Test
    @DisplayName("deleting a post moves it to the trash; restoring puts it back on the site")
    void postTrashAndRestore() throws Exception {
        Post live = savePost("live", PostStatus.PUBLISHED);

        mockMvc.perform(post("/admin/post/" + live.getId() + "/delete").with(csrf()).with(TestAuth.owner()))
                .andExpect(flash().attribute("successMessage", "Post moved to trash."));
        assertTrue(inTrash("posts", live.getId()));
        assertTrue(postRepository.findById(live.getId()).isEmpty(), "hidden from JPA");
        mockMvc.perform(get("/article/" + live.getSlug())).andExpect(status().isNotFound());
        mockMvc.perform(get("/admin/trash").param("type", "posts").with(TestAuth.owner()))
                .andExpect(status().isOk())
                .andExpect(content().string(containsString(live.getTitle())));

        mockMvc.perform(post("/admin/trash/posts/restore").param("ids", live.getId().toString()).with(csrf()).with(TestAuth.owner()))
                .andExpect(redirectedUrl("/admin/trash?type=posts"))
                .andExpect(flash().attribute("successMessage", "1 post restored."));
        assertFalse(inTrash("posts", live.getId()));
        mockMvc.perform(get("/article/" + live.getSlug())).andExpect(status().isOk());
    }

    @Test
    @DisplayName("deleting a post permanently removes its comments too")
    void purgePost() throws Exception {
        Post doomed = savePost("doomed", PostStatus.PUBLISHED);
        Comment c = comment(doomed, null, "Reader");
        mockMvc.perform(post("/admin/post/" + doomed.getId() + "/delete").with(csrf()).with(TestAuth.owner()));

        mockMvc.perform(post("/admin/trash/posts/purge").param("ids", doomed.getId().toString()).with(csrf()).with(TestAuth.owner()))
                .andExpect(flash().attribute("successMessage", "1 post deleted permanently."));
        assertFalse(exists("posts", doomed.getId()));
        assertFalse(exists("comments", c.getId()));

        mockMvc.perform(post("/admin/trash/posts/purge").param("ids", doomed.getId().toString()).with(csrf()).with(TestAuth.owner()))
                .andExpect(flash().attribute("errorMessage", containsString("not in the trash")));
    }

    @Test
    @DisplayName("a category used by a trashed post can't be trashed; after the post is purged it can")
    void categoryRules() throws Exception {
        Category other = categoryRepository.save(Category.builder().name("Other " + tag).slug(tag + "-other").visible(true).build());
        Post inOther = postRepository.save(Post.builder().title("In other " + tag).slug(tag + "-in-other").content("<p>x</p>")
                .status(PostStatus.DRAFT).category(other).author(author).build());
        mockMvc.perform(post("/admin/post/" + inOther.getId() + "/delete").with(csrf()).with(TestAuth.owner()));

        mockMvc.perform(post("/admin/categories/" + other.getId() + "/delete").with(csrf()).with(TestAuth.owner()))
                .andExpect(flash().attribute("errorMessage", containsString("still has posts")));
        assertFalse(inTrash("categories", other.getId()));

        mockMvc.perform(post("/admin/trash/posts/purge").param("ids", inOther.getId().toString()).with(csrf()).with(TestAuth.owner()));
        mockMvc.perform(post("/admin/categories/" + other.getId() + "/delete").with(csrf()).with(TestAuth.owner()))
                .andExpect(flash().attribute("successMessage", "Category moved to trash."));
        assertTrue(inTrash("categories", other.getId()));
        assertTrue(categoryRepository.findAll().stream().noneMatch(c -> c.getId().equals(other.getId())), "hidden from lists");
    }

    @Test
    @DisplayName("a comment goes to the trash with its replies and comes back with them; its post must be live")
    void commentRules() throws Exception {
        Post host = savePost("host", PostStatus.PUBLISHED);
        Comment parent = comment(host, null, "Parent");
        Comment reply = comment(host, parent, "Reply");

        mockMvc.perform(post("/admin/comments/" + parent.getId() + "/delete").with(csrf()).with(TestAuth.owner()))
                .andExpect(flash().attribute("successMessage", "1 comment moved to trash."));
        assertTrue(inTrash("comments", parent.getId()));
        assertTrue(inTrash("comments", reply.getId()), "replies go with their parent");

        mockMvc.perform(post("/admin/trash/comments/restore").param("ids", parent.getId().toString()).with(csrf()).with(TestAuth.owner()))
                .andExpect(flash().attribute("successMessage", "1 comment restored."));
        assertFalse(inTrash("comments", parent.getId()));
        assertFalse(inTrash("comments", reply.getId()), "and come back with it");

        mockMvc.perform(post("/admin/comments/" + reply.getId() + "/delete").with(csrf()).with(TestAuth.owner()));
        mockMvc.perform(post("/admin/post/" + host.getId() + "/delete").with(csrf()).with(TestAuth.owner()));
        mockMvc.perform(post("/admin/trash/comments/restore").param("ids", reply.getId().toString()).with(csrf()).with(TestAuth.owner()))
                .andExpect(flash().attribute("errorMessage", containsString("Its post is in the trash")));
        assertTrue(inTrash("comments", reply.getId()));
    }

    @Test
    @DisplayName("a slug held by a trashed post can't be reused")
    void slugHeldByTrash() throws Exception {
        Post old = savePost("reused", PostStatus.DRAFT);
        mockMvc.perform(post("/admin/post/" + old.getId() + "/delete").with(csrf()).with(TestAuth.owner()));

        mockMvc.perform(post("/admin/post/new").with(csrf()).with(TestAuth.owner(author.getEmail()))
                        .param("title", "New with old slug").param("slug", old.getSlug()).param("content", "<p>x</p>")
                        .param("categoryId", category.getId().toString()).param("status", "DRAFT"))
                .andExpect(status().isOk())
                .andExpect(content().string(containsString("Slug already in use.")));
    }

    @Test
    @DisplayName("bulk restore reports skipped rows; hashtags come back on their posts")
    void bulkHashtags() throws Exception {
        Hashtag h1 = hashtagRepository.save(Hashtag.builder().name("T1 " + tag).slug(tag + "-t1").active(true).build());
        Hashtag h2 = hashtagRepository.save(Hashtag.builder().name("T2 " + tag).slug(tag + "-t2").active(true).build());
        mockMvc.perform(post("/admin/hashtags/bulk-delete").with(csrf()).with(TestAuth.owner())
                        .param("ids", h1.getId().toString(), h2.getId().toString()))
                .andExpect(flash().attribute("successMessage", "2 hashtags moved to trash."));

        mockMvc.perform(post("/admin/trash/hashtags/restore").with(csrf()).with(TestAuth.owner())
                        .param("ids", h1.getId().toString(), h2.getId().toString(), "999999999"))
                .andExpect(flash().attribute("errorMessage", containsString("2 hashtags restored. 1 skipped")));
        assertTrue(hashtagRepository.findById(h1.getId()).isPresent());
    }

    @Test
    @DisplayName("tabs and actions follow each module's delete permission")
    void permissions() throws Exception {
        mockMvc.perform(get("/admin/trash").with(TestAuth.withPermissions("tagger@test.com", "hashtags:delete")))
                .andExpect(status().isOk())
                .andExpect(content().string(containsString("type=hashtags")))
                .andExpect(content().string(not(containsString("type=posts"))));
        mockMvc.perform(post("/admin/trash/posts/restore").param("ids", "1").with(csrf())
                        .with(TestAuth.withPermissions("tagger@test.com", "hashtags:delete")))
                .andExpect(status().isForbidden());
        mockMvc.perform(get("/admin/trash").with(TestAuth.withPermissions("reader@test.com", "posts:view")))
                .andExpect(status().isForbidden());
        mockMvc.perform(post("/admin/trash/widgets/restore").param("ids", "1").with(csrf()).with(TestAuth.owner()))
                .andExpect(status().isNotFound());
        mockMvc.perform(post("/admin/trash/posts/empty").with(TestAuth.owner()))
                .andExpect(status().isForbidden());
    }

    @Test
    @DisplayName("media is two tabs of this page, and deleting permanently there needs media:purge")
    void mediaTabs() throws Exception {
        // media:delete alone sees the tabs and may restore, but not purge (the second permission).
        var mediaOnly = TestAuth.withPermissions("mediaonly@test.com", "media:delete");
        mockMvc.perform(get("/admin/trash").param("type", "media-files").with(mediaOnly))
                .andExpect(status().isOk())
                .andExpect(content().string(containsString("type=media-files")))
                .andExpect(content().string(containsString("type=media-folders")))
                .andExpect(content().string(not(containsString("data-trash-row=\"purge\""))));
        mockMvc.perform(post("/admin/trash/media-files/purge").param("ids", "1").with(csrf()).with(mediaOnly))
                .andExpect(status().isForbidden());
        mockMvc.perform(post("/admin/trash/media-files/empty").with(csrf()).with(mediaOnly))
                .andExpect(status().isForbidden());

        // Someone with neither media permission sees no media tab at all.
        mockMvc.perform(get("/admin/trash").with(TestAuth.withPermissions("tagger@test.com", "hashtags:delete")))
                .andExpect(status().isOk())
                .andExpect(content().string(not(containsString("type=media-files"))));
    }

    @Test
    @DisplayName("the sidebar links to the trash")
    void sidebarLink() throws Exception {
        mockMvc.perform(get("/admin/dashboard").with(TestAuth.owner()))
                .andExpect(content().string(containsString("href=\"/admin/trash\"")));
    }

    @Test
    @DisplayName("the retention sweep deletes only what has been in the trash longer than the retention period")
    void purgeExpired() {
        Post old = savePost("expired", PostStatus.DRAFT);
        Post recent = savePost("recent", PostStatus.DRAFT);
        jdbc.update("update posts set deleted_at = ? where id = ?", LocalDateTime.now().minusDays(40), old.getId());
        jdbc.update("update posts set deleted_at = ? where id = ?", LocalDateTime.now().minusDays(5), recent.getId());

        assertTrue(trashService.purgeExpired(30) >= 1);
        assertFalse(exists("posts", old.getId()));
        assertTrue(inTrash("posts", recent.getId()));
    }
}
