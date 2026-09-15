package com.kienhee.blog;

import com.kienhee.blog.entity.Category;
import com.kienhee.blog.entity.Post;
import com.kienhee.blog.entity.PostStatus;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.CategoryRepository;
import com.kienhee.blog.repository.PostRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.PostService;
import com.kienhee.blog.service.impl.ScheduledPostPublisher;
import com.kienhee.blog.support.TestAuth;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Nested;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.request.MockHttpServletRequestBuilder;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.time.temporal.ChronoUnit;
import java.util.ArrayList;
import java.util.List;

import static org.hamcrest.Matchers.containsString;
import static org.junit.jupiter.api.Assertions.*;
import static org.mockito.ArgumentMatchers.any;
import static org.mockito.Mockito.*;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

/** Scheduling a post, the editor rules around it, and the job that publishes due posts. */
@DisplayName("Scheduled posts")
class ScheduledPostsTests {

    private static final DateTimeFormatter FORM = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm");

    @Nested
    @DisplayName("publisher job (unit)")
    class Job {

        @Test
        @DisplayName("calls the service when enabled, does nothing when disabled")
        void togglesWithProperty() {
            PostService service = mock(PostService.class);
            new ScheduledPostPublisher(service, true).publishDuePosts();
            verify(service).publishDuePosts(any(LocalDateTime.class));

            PostService idle = mock(PostService.class);
            new ScheduledPostPublisher(idle, false).publishDuePosts();
            verifyNoInteractions(idle);
        }
    }

    @Nested
    @SpringBootTest(properties = "app.posts.auto-publish=false")
    @DisplayName("editor and publishing (database)")
    class Database {

        @Autowired private WebApplicationContext context;
        @Autowired private PostService postService;
        @Autowired private PostRepository postRepository;
        @Autowired private CategoryRepository categoryRepository;
        @Autowired private UserRepository userRepository;

        private MockMvc mockMvc;
        private User author;
        private Category category;
        private final List<Long> postIds = new ArrayList<>();
        private final String tag = "sched" + System.nanoTime();

        @BeforeEach
        void setUp() {
            mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity()).build();
            author = userRepository.findAll().get(0);
            category = categoryRepository.save(Category.builder().name("Sched " + tag).slug("sched-" + tag).visible(true).build());
        }

        @AfterEach
        void tearDown() {
            postRepository.findAll().stream().filter(p -> p.getSlug().startsWith(tag)).forEach(postRepository::delete);
            postIds.forEach(id -> postRepository.findById(id).ifPresent(postRepository::delete));
            categoryRepository.delete(category);
        }

        private MockHttpServletRequestBuilder form(String url, String slug, PostStatus status, String scheduledAt) {
            MockHttpServletRequestBuilder request = post(url).with(csrf())
                    .param("title", "Scheduled " + slug).param("slug", slug).param("excerpt", "Excerpt")
                    .param("content", "<p>Body</p>").param("categoryId", category.getId().toString())
                    .param("status", status.name());
            if (scheduledAt != null) request.param("scheduledAt", scheduledAt);
            return request;
        }

        private Post bySlug(String slug) {
            return postRepository.findAll().stream().filter(p -> p.getSlug().equals(slug)).findFirst().orElseThrow();
        }

        private Post saved(String name, PostStatus status, LocalDateTime scheduledAt) {
            Post p = postRepository.save(Post.builder().title("Scheduled " + name + " " + tag).slug(tag + "-" + name)
                    .excerpt("Excerpt").content("<p>Body</p>").status(status).scheduledAt(scheduledAt)
                    .publishedAt(status == PostStatus.PUBLISHED ? LocalDateTime.now() : null)
                    .category(category).author(author).build());
            postIds.add(p.getId());
            return p;
        }

        @Test
        @DisplayName("scheduling a post stores the time and keeps it off the public site")
        void schedulesPost() throws Exception {
            String slug = tag + "-future";
            LocalDateTime when = LocalDateTime.now().plusDays(2).truncatedTo(ChronoUnit.MINUTES);
            mockMvc.perform(form("/admin/post/new", slug, PostStatus.SCHEDULED, when.format(FORM)).with(TestAuth.owner(author.getEmail())))
                    .andExpect(status().is3xxRedirection());

            Post post = bySlug(slug);
            assertEquals(PostStatus.SCHEDULED, post.getStatus());
            assertEquals(when, post.getScheduledAt());
            assertNull(post.getPublishedAt());
            mockMvc.perform(get("/article/" + slug)).andExpect(status().isNotFound());

            mockMvc.perform(get("/admin/post/" + post.getId() + "/edit").with(TestAuth.owner(author.getEmail())))
                    .andExpect(status().isOk())
                    .andExpect(content().string(containsString("value=\"" + when.format(FORM) + "\"")));
            mockMvc.perform(get("/admin/posts").with(TestAuth.owner(author.getEmail())))
                    .andExpect(content().string(containsString(when.format(FORM))));
        }

        @Test
        @DisplayName("a scheduled post needs a time, and it must be in the future")
        void needsFutureTime() throws Exception {
            String missing = tag + "-no-time";
            mockMvc.perform(form("/admin/post/new", missing, PostStatus.SCHEDULED, null).with(TestAuth.owner(author.getEmail())))
                    .andExpect(status().isOk())
                    .andExpect(content().string(containsString("Choose when the post should go live.")));
            String past = tag + "-past";
            mockMvc.perform(form("/admin/post/new", past, PostStatus.SCHEDULED, LocalDateTime.now().minusHours(1).format(FORM))
                            .with(TestAuth.owner(author.getEmail())))
                    .andExpect(status().isOk())
                    .andExpect(content().string(containsString("The publish time must be in the future.")));
            assertTrue(postRepository.findAll().stream().noneMatch(p -> p.getSlug().equals(missing) || p.getSlug().equals(past)));
        }

        @Test
        @DisplayName("the job publishes due posts once, with published_at = the scheduled time")
        void publishesDuePosts() throws Exception {
            LocalDateTime dueAt = LocalDateTime.now().minusMinutes(5).truncatedTo(ChronoUnit.SECONDS);
            Post due = saved("due", PostStatus.SCHEDULED, dueAt);
            Post later = saved("later", PostStatus.SCHEDULED, LocalDateTime.now().plusHours(3));
            Post noTime = saved("no-time", PostStatus.SCHEDULED, null);
            Post draft = saved("draft", PostStatus.DRAFT, null);

            assertTrue(postService.publishDuePosts(LocalDateTime.now()) >= 1);
            assertEquals(0, postService.publishDuePosts(LocalDateTime.now()) , "running again publishes nothing new");

            Post published = postRepository.findById(due.getId()).orElseThrow();
            assertEquals(PostStatus.PUBLISHED, published.getStatus());
            assertEquals(dueAt, published.getPublishedAt());
            assertEquals(PostStatus.SCHEDULED, postRepository.findById(later.getId()).orElseThrow().getStatus());
            assertEquals(PostStatus.SCHEDULED, postRepository.findById(noTime.getId()).orElseThrow().getStatus());
            assertEquals(PostStatus.DRAFT, postRepository.findById(draft.getId()).orElseThrow().getStatus());

            mockMvc.perform(get("/article/" + due.getSlug())).andExpect(status().isOk());
        }

        @Test
        @DisplayName("moving a scheduled post to another status clears its time")
        void otherStatusClearsTime() throws Exception {
            Post scheduled = saved("to-draft", PostStatus.SCHEDULED, LocalDateTime.now().plusDays(1));
            mockMvc.perform(form("/admin/post/" + scheduled.getId() + "/edit", scheduled.getSlug(), PostStatus.DRAFT,
                            LocalDateTime.now().plusDays(1).format(FORM))
                            .param("id", scheduled.getId().toString())
                            .with(TestAuth.owner(author.getEmail())))
                    .andExpect(status().is3xxRedirection());
            Post reloaded = postRepository.findById(scheduled.getId()).orElseThrow();
            assertEquals(PostStatus.DRAFT, reloaded.getStatus());
            assertNull(reloaded.getScheduledAt());
        }

        @Test
        @DisplayName("without posts:publish, editing a scheduled post keeps its status and time")
        void contributorCantReschedule() throws Exception {
            LocalDateTime original = LocalDateTime.now().plusDays(3).truncatedTo(ChronoUnit.MINUTES);
            Post scheduled = saved("contrib", PostStatus.SCHEDULED, original);
            mockMvc.perform(form("/admin/post/" + scheduled.getId() + "/edit", scheduled.getSlug(), PostStatus.SCHEDULED,
                            LocalDateTime.now().plusMinutes(10).format(FORM))
                            .param("id", scheduled.getId().toString())
                            .with(TestAuth.withPermissions(author.getEmail(), "posts:view", "posts:create", "posts:edit")))
                    .andExpect(status().is3xxRedirection());
            Post reloaded = postRepository.findById(scheduled.getId()).orElseThrow();
            assertEquals(PostStatus.SCHEDULED, reloaded.getStatus());
            assertEquals(original, reloaded.getScheduledAt());
        }

        @Test
        @DisplayName("a published post rescheduled for later leaves the public site until then")
        void unpublishUntilScheduled() throws Exception {
            Post live = saved("live", PostStatus.PUBLISHED, null);
            mockMvc.perform(form("/admin/post/" + live.getId() + "/edit", live.getSlug(), PostStatus.SCHEDULED,
                            LocalDateTime.now().plusDays(1).format(FORM))
                            .param("id", live.getId().toString())
                            .with(TestAuth.owner(author.getEmail())))
                    .andExpect(status().is3xxRedirection());
            Post reloaded = postRepository.findById(live.getId()).orElseThrow();
            assertEquals(PostStatus.SCHEDULED, reloaded.getStatus());
            assertNull(reloaded.getPublishedAt());
            mockMvc.perform(get("/article/" + live.getSlug())).andExpect(status().isNotFound());
        }
    }
}
