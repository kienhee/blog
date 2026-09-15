package com.kienhee.blog;

import com.kienhee.blog.entity.Category;
import com.kienhee.blog.entity.Post;
import com.kienhee.blog.entity.PostStatus;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.CategoryRepository;
import com.kienhee.blog.repository.PostRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.support.TestAuth;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.request.MockHttpServletRequestBuilder;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import java.time.LocalDateTime;

import static org.hamcrest.Matchers.containsString;
import static org.hamcrest.Matchers.not;
import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

/** posts:publish is enforced on the server, not only hidden in the editor. */
@SpringBootTest
@DisplayName("Posts: publishing needs posts:publish")
class PostPublishPermissionTests {

    private static final String[] CONTRIBUTOR = {"posts:view", "posts:create", "posts:edit"};

    @Autowired private WebApplicationContext context;
    @Autowired private PostRepository postRepository;
    @Autowired private CategoryRepository categoryRepository;
    @Autowired private UserRepository userRepository;

    private MockMvc mockMvc;
    private User author;
    private Category category;
    private final String tag = "pub" + System.nanoTime();

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity()).build();
        author = userRepository.findAll().get(0);
        category = categoryRepository.save(Category.builder().name("Publish " + tag).slug("publish-" + tag).visible(true).build());
    }

    @AfterEach
    void tearDown() {
        postRepository.findAll().stream().filter(p -> p.getSlug().startsWith(tag)).forEach(postRepository::delete);
        categoryRepository.delete(category);
    }

    private MockHttpServletRequestBuilder form(String url, String slug, PostStatus status) {
        return post(url).with(csrf())
                .param("title", "Publish test " + slug)
                .param("slug", slug)
                .param("excerpt", "Excerpt")
                .param("content", "<p>Body</p>")
                .param("categoryId", category.getId().toString())
                .param("status", status.name());
    }

    private Post bySlug(String slug) {
        return postRepository.findAll().stream().filter(p -> p.getSlug().equals(slug)).findFirst().orElseThrow();
    }

    @Test
    @DisplayName("without posts:publish a new post is saved as a draft whatever status is sent")
    void newPostForcedToDraft() throws Exception {
        String slug = tag + "-contrib";
        mockMvc.perform(form("/admin/post/new", slug, PostStatus.PUBLISHED)
                        .with(TestAuth.withPermissions(author.getEmail(), CONTRIBUTOR)))
                .andExpect(status().is3xxRedirection());
        assertEquals(PostStatus.DRAFT, bySlug(slug).getStatus());
    }

    @Test
    @DisplayName("with posts:publish the chosen status is kept")
    void publisherPublishes() throws Exception {
        String slug = tag + "-admin";
        mockMvc.perform(form("/admin/post/new", slug, PostStatus.PUBLISHED).with(TestAuth.owner(author.getEmail())))
                .andExpect(status().is3xxRedirection());
        assertEquals(PostStatus.PUBLISHED, bySlug(slug).getStatus());
    }

    @Test
    @DisplayName("without posts:publish editing a published post can't unpublish or archive it")
    void editKeepsStatus() throws Exception {
        String slug = tag + "-live";
        Post live = postRepository.save(Post.builder().title("Live " + tag).slug(slug).excerpt("Excerpt").content("<p>Body</p>")
                .status(PostStatus.PUBLISHED).publishedAt(LocalDateTime.now()).category(category).author(author).build());

        mockMvc.perform(form("/admin/post/" + live.getId() + "/edit", slug, PostStatus.ARCHIVED)
                        .with(TestAuth.withPermissions(author.getEmail(), CONTRIBUTOR)))
                .andExpect(status().is3xxRedirection());
        assertEquals(PostStatus.PUBLISHED, bySlug(slug).getStatus());
    }

    @Test
    @DisplayName("the editor only offers Draft to someone without posts:publish")
    void editorHidesPublishOptions() throws Exception {
        mockMvc.perform(get("/admin/post/new").with(TestAuth.withPermissions(author.getEmail(), CONTRIBUTOR)))
                .andExpect(status().isOk())
                .andExpect(content().string(not(containsString("value=\"PUBLISHED\""))))
                .andExpect(content().string(containsString("Publishing needs the")));
        mockMvc.perform(get("/admin/post/new").with(TestAuth.owner(author.getEmail())))
                .andExpect(content().string(containsString("value=\"PUBLISHED\"")));
    }
}
