package com.kienhee.blog;

import com.kienhee.blog.entity.Category;
import com.kienhee.blog.entity.Hashtag;
import com.kienhee.blog.entity.Post;
import com.kienhee.blog.entity.PostStatus;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.CategoryRepository;
import com.kienhee.blog.repository.HashtagRepository;
import com.kienhee.blog.repository.PostRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.SettingService;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.data.domain.Page;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.MvcResult;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;

import static org.hamcrest.Matchers.containsString;
import static org.hamcrest.Matchers.not;
import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

/** Public pages render real, published data to anonymous visitors. Rows created here are removed again. */
@SpringBootTest
@DisplayName("Public site")
class PublicSiteTests {

    @Autowired private WebApplicationContext context;
    @Autowired private PostRepository postRepository;
    @Autowired private CategoryRepository categoryRepository;
    @Autowired private HashtagRepository hashtagRepository;
    @Autowired private UserRepository userRepository;
    @Autowired private SettingService settingService;

    private MockMvc mockMvc;
    private final List<Post> posts = new ArrayList<>();
    private Category category;
    private Category hiddenCategory;
    private Hashtag hashtag;
    private User author;
    private String originalPageSize;
    private final String tag = "pub" + System.nanoTime();

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity()).build();
        author = userRepository.findAll().get(0);
        category = categoryRepository.save(Category.builder().name("Public " + tag).slug("cat-" + tag).visible(true).build());
        hiddenCategory = categoryRepository.save(Category.builder().name("Hidden " + tag).slug("hidden-" + tag).visible(false).build());
        hashtag = hashtagRepository.save(Hashtag.builder().name("Tag " + tag).slug("tag-" + tag).active(true).build());
        originalPageSize = settingService.get("blog.posts_per_page").orElse("10");
    }

    @AfterEach
    void tearDown() {
        postRepository.deleteAll(posts);
        categoryRepository.delete(category);
        categoryRepository.delete(hiddenCategory);
        hashtagRepository.delete(hashtag);
        settingService.saveAll(Map.of("blog.posts_per_page", originalPageSize));
    }

    private Post post(String slugSuffix, PostStatus status, String content, LocalDateTime publishedAt) {
        Post post = postRepository.save(Post.builder()
                .title("Title " + slugSuffix + " " + tag)
                .slug(slugSuffix + "-" + tag)
                .content(content)
                .status(status)
                .category(category)
                .author(author)
                .publishedAt(publishedAt)
                .build());
        posts.add(post);
        return post;
    }

    @Test
    @DisplayName("home and news list published posts only")
    void publishedOnly() throws Exception {
        Post published = post("live", PostStatus.PUBLISHED, "<p>Hello</p>", LocalDateTime.now().plusYears(5));
        Post draft = post("draft", PostStatus.DRAFT, "<p>Secret</p>", null);

        for (String page : List.of("/", "/news")) {
            mockMvc.perform(get(page))
                    .andExpect(status().isOk())
                    .andExpect(content().string(containsString(published.getTitle())))
                    .andExpect(content().string(not(containsString(draft.getTitle()))));
        }
    }

    @Test
    @DisplayName("an article renders by slug with sanitised content; drafts are 404")
    void article() throws Exception {
        Post published = post("art", PostStatus.PUBLISHED,
                "<h2>Intro</h2><p onclick=\"alert(1)\">Body text</p><script>alert('x')</script><img src=\"/media/1/a.png\">",
                LocalDateTime.now());
        Post draft = post("hidden", PostStatus.DRAFT, "<p>x</p>", null);

        mockMvc.perform(get("/article/" + published.getSlug()))
                .andExpect(status().isOk())
                .andExpect(content().string(containsString("<h2>Intro</h2>")))
                .andExpect(content().string(containsString("Body text")))
                .andExpect(content().string(containsString("src=\"/media/1/a.png\"")))
                .andExpect(content().string(not(containsString("alert("))))
                .andExpect(content().string(containsString("/author/" + author.getId())));

        mockMvc.perform(get("/article/" + draft.getSlug())).andExpect(status().isNotFound());
        mockMvc.perform(get("/article/does-not-exist-" + tag)).andExpect(status().isNotFound());
    }

    @Test
    @DisplayName("category page lists its posts; hidden categories are 404")
    void category() throws Exception {
        Post published = post("incat", PostStatus.PUBLISHED, "<p>x</p>", LocalDateTime.now());

        mockMvc.perform(get("/category/" + category.getSlug()))
                .andExpect(status().isOk())
                .andExpect(content().string(containsString(category.getName())))
                .andExpect(content().string(containsString(published.getTitle())));
        mockMvc.perform(get("/categories"))
                .andExpect(status().isOk())
                .andExpect(content().string(containsString(category.getName())))
                .andExpect(content().string(not(containsString(hiddenCategory.getName()))));
        mockMvc.perform(get("/category/" + hiddenCategory.getSlug())).andExpect(status().isNotFound());
    }

    @Test
    @DisplayName("search matches published titles and treats % literally")
    void search() throws Exception {
        Post published = post("findme", PostStatus.PUBLISHED, "<p>x</p>", LocalDateTime.now());
        post("findme-draft", PostStatus.DRAFT, "<p>x</p>", null);

        mockMvc.perform(get("/search").param("q", tag))
                .andExpect(status().isOk())
                .andExpect(content().string(containsString(published.getTitle())))
                .andExpect(content().string(containsString("1 result for")));
        // Model, not HTML: the header's search overlay lists the newest posts on every page.
        MvcResult wildcard = mockMvc.perform(get("/search").param("q", "%%")).andExpect(status().isOk()).andReturn();
        Page<?> results = (Page<?>) wildcard.getModelAndView().getModel().get("posts");
        assertEquals(0, results.getContent().stream().filter(p -> ((Post) p).getId().equals(published.getId())).count(),
                "%% must not act as a wildcard");
    }

    @Test
    @DisplayName("search?tag= lists published posts carrying that hashtag")
    void searchByHashtag() throws Exception {
        Post tagged = post("tagged", PostStatus.PUBLISHED, "<p>x</p>", LocalDateTime.now());
        tagged.setHashtags(new java.util.HashSet<>(List.of(hashtag)));
        postRepository.save(tagged);
        Post untagged = post("untagged", PostStatus.PUBLISHED, "<p>x</p>", LocalDateTime.now());

        MvcResult result = mockMvc.perform(get("/search").param("tag", hashtag.getSlug()))
                .andExpect(status().isOk())
                .andExpect(content().string(containsString("1 article tagged")))
                .andReturn();
        // Model, not HTML: the header overlay lists the newest posts on every public page.
        Page<?> found = (Page<?>) result.getModelAndView().getModel().get("posts");
        assertEquals(1, found.getTotalElements());
        assertEquals(tagged.getId(), ((Post) found.getContent().get(0)).getId());
        assertEquals(0, found.getContent().stream().filter(p -> ((Post) p).getId().equals(untagged.getId())).count());

        // The hashtag name is searchable from the free-text box too.
        MvcResult byName = mockMvc.perform(get("/search").param("q", hashtag.getName())).andExpect(status().isOk()).andReturn();
        Page<?> byNameResults = (Page<?>) byName.getModelAndView().getModel().get("posts");
        assertEquals(1, byNameResults.getContent().stream().filter(p -> ((Post) p).getId().equals(tagged.getId())).count());

        tagged.getHashtags().clear();
        postRepository.save(tagged);
    }

    @Test
    @DisplayName("category pages are paginated with the Posts per page setting")
    void pagination() throws Exception {
        settingService.saveAll(Map.of("blog.posts_per_page", "2"));
        LocalDateTime base = LocalDateTime.now();
        Post oldest = post("p1", PostStatus.PUBLISHED, "<p>x</p>", base.minusDays(3));
        post("p2", PostStatus.PUBLISHED, "<p>x</p>", base.minusDays(2));
        Post newest = post("p3", PostStatus.PUBLISHED, "<p>x</p>", base.minusDays(1));

        // Assert on the model, not the HTML: the header's search overlay lists the newest posts on every page.
        MvcResult first = mockMvc.perform(get("/category/" + category.getSlug()))
                .andExpect(status().isOk())
                .andExpect(content().string(containsString("Page 1 of 2")))
                .andReturn();
        Page<?> page1 = (Page<?>) first.getModelAndView().getModel().get("posts");
        assertEquals(2, page1.getTotalPages());
        assertEquals(List.of(newest.getId()), page1.getContent().stream().map(p -> ((Post) p).getId()).limit(1).toList());

        MvcResult second = mockMvc.perform(get("/category/" + category.getSlug()).param("page", "2")).andReturn();
        Page<?> page2 = (Page<?>) second.getModelAndView().getModel().get("posts");
        assertEquals(List.of(oldest.getId()), page2.getContent().stream().map(p -> ((Post) p).getId()).toList());
    }

    @Test
    @DisplayName("author page shows the author's published posts")
    void author() throws Exception {
        Post published = post("byauthor", PostStatus.PUBLISHED, "<p>x</p>", LocalDateTime.now());
        mockMvc.perform(get("/author/" + author.getId()))
                .andExpect(status().isOk())
                .andExpect(content().string(containsString(published.getTitle())));
        mockMvc.perform(get("/author/999999999")).andExpect(status().isNotFound());
    }

    @Test
    @DisplayName("/author renders the default author directly, without redirecting to an id")
    void defaultAuthor() throws Exception {
        post("defaultauthor", PostStatus.PUBLISHED, "<p>x</p>", LocalDateTime.now());
        mockMvc.perform(get("/author"))
                .andExpect(status().isOk())
                .andExpect(org.springframework.test.web.servlet.result.MockMvcResultMatchers.view().name("public/author"));
    }

    @Test
    @DisplayName("live search returns published matches as JSON")
    void liveSearch() throws Exception {
        Post published = post("livesearch", PostStatus.PUBLISHED, "<p>x</p>", LocalDateTime.now());
        mockMvc.perform(get("/search/live").param("q", published.getTitle()))
                .andExpect(status().isOk())
                .andExpect(content().string(containsString(published.getTitle())))
                .andExpect(content().string(containsString("/article/" + published.getSlug())));
    }
}
