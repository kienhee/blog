package com.kienhee.blog;

import com.kienhee.blog.entity.Category;
import com.kienhee.blog.entity.Post;
import com.kienhee.blog.entity.PostStatus;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.CategoryRepository;
import com.kienhee.blog.repository.PostRepository;
import com.kienhee.blog.repository.UserRepository;
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

import javax.xml.parsers.DocumentBuilderFactory;
import java.io.ByteArrayInputStream;
import java.nio.charset.StandardCharsets;
import java.time.LocalDateTime;

import static org.hamcrest.Matchers.containsString;
import static org.hamcrest.Matchers.not;
import static org.junit.jupiter.api.Assertions.assertDoesNotThrow;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

/** RSS, sitemap and robots.txt are public, well-formed, and only list what the public site shows. */
@SpringBootTest(properties = "app.posts.auto-publish=false")
@DisplayName("RSS, sitemap and robots.txt")
class FeedTests {

    @Autowired private WebApplicationContext context;
    @Autowired private PostRepository postRepository;
    @Autowired private CategoryRepository categoryRepository;
    @Autowired private UserRepository userRepository;
    @Autowired private JdbcTemplate jdbc;

    private MockMvc mockMvc;
    private Category category;
    private Post published;
    private Post draft;
    private final String tag = "feed" + System.nanoTime();

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity()).build();
        User author = userRepository.findAll().get(0);
        category = categoryRepository.save(Category.builder().name("Feed " + tag).slug(tag + "-cat").visible(true).build());
        published = postRepository.save(Post.builder().title("Feeds & <Friends> " + tag).slug(tag + "-live")
                .excerpt("Short & sweet").content("<p>Body</p>").status(PostStatus.PUBLISHED).publishedAt(LocalDateTime.now())
                .category(category).author(author).build());
        draft = postRepository.save(Post.builder().title("Secret draft " + tag).slug(tag + "-draft")
                .content("<p>Body</p>").status(PostStatus.DRAFT).category(category).author(author).build());
    }

    @AfterEach
    void tearDown() {
        jdbc.update("delete from posts where slug like ?", tag + "%");
        jdbc.update("delete from categories where slug like ?", tag + "%");
    }

    private static void wellFormed(byte[] xml) {
        assertDoesNotThrow(() -> DocumentBuilderFactory.newInstance().newDocumentBuilder().parse(new ByteArrayInputStream(xml)));
    }

    @Test
    @DisplayName("RSS lists published posts with escaped titles and absolute links")
    void rss() throws Exception {
        byte[] body = mockMvc.perform(get("/rss.xml"))
                .andExpect(status().isOk())
                .andExpect(content().contentTypeCompatibleWith("application/rss+xml"))
                .andExpect(content().string(containsString("<title>Feeds &amp; &lt;Friends&gt; " + tag + "</title>")))
                .andExpect(content().string(containsString("<link>http://localhost/article/" + published.getSlug() + "</link>")))
                .andExpect(content().string(containsString("<description>Short &amp; sweet</description>")))
                .andExpect(content().string(not(containsString(draft.getTitle()))))
                .andReturn().getResponse().getContentAsByteArray();
        wellFormed(body);
    }

    @Test
    @DisplayName("the sitemap lists published articles and visible categories, never drafts")
    void sitemap() throws Exception {
        byte[] body = mockMvc.perform(get("/sitemap.xml"))
                .andExpect(status().isOk())
                .andExpect(content().contentTypeCompatibleWith("application/xml"))
                .andExpect(content().string(containsString("<loc>http://localhost/article/" + published.getSlug() + "</loc>")))
                .andExpect(content().string(containsString("<loc>http://localhost/category/" + category.getSlug() + "</loc>")))
                .andExpect(content().string(containsString("<loc>http://localhost/news</loc>")))
                .andExpect(content().string(not(containsString(draft.getSlug()))))
                .andReturn().getResponse().getContentAsByteArray();
        wellFormed(body);
    }

    @Test
    @DisplayName("robots.txt keeps crawlers out of the admin and points at the sitemap")
    void robots() throws Exception {
        mockMvc.perform(get("/robots.txt"))
                .andExpect(status().isOk())
                .andExpect(content().contentTypeCompatibleWith("text/plain"))
                .andExpect(content().string(containsString("Disallow: /admin/")))
                .andExpect(content().string(containsString("Sitemap: http://localhost/sitemap.xml")));
    }

    @Test
    @DisplayName("public pages advertise the feed and link to it")
    void pagesLinkTheFeed() throws Exception {
        String html = mockMvc.perform(get("/")).andExpect(status().isOk()).andReturn().getResponse()
                .getContentAsString(StandardCharsets.UTF_8);
        org.junit.jupiter.api.Assertions.assertTrue(html.contains("type=\"application/rss+xml\""));
        org.junit.jupiter.api.Assertions.assertTrue(html.contains("href=\"/rss.xml\""));
    }
}
