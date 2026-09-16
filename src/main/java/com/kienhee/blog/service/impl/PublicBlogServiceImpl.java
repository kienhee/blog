package com.kienhee.blog.service.impl;

import com.kienhee.blog.entity.Category;
import com.kienhee.blog.entity.Hashtag;
import com.kienhee.blog.entity.Post;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.CategoryRepository;
import com.kienhee.blog.repository.HashtagRepository;
import com.kienhee.blog.repository.PostRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.PublicBlogService;
import com.kienhee.blog.service.SettingService;
import lombok.RequiredArgsConstructor;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.PageRequest;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.Optional;

@Service
@RequiredArgsConstructor
@Transactional(readOnly = true)
public class PublicBlogServiceImpl implements PublicBlogService {

    // One character is enough: the query runs as LIKE %q%, so a single letter is a valid search.
    static final int MIN_QUERY_LENGTH = 1;
    static final int MAX_QUERY_LENGTH = 100;

    private final PostRepository postRepository;
    private final CategoryRepository categoryRepository;
    private final HashtagRepository hashtagRepository;
    private final UserRepository userRepository;
    private final SettingService settingService;

    @Override
    public int pageSize() {
        int size = settingService.getInt("blog.posts_per_page", 10);
        return size >= 1 && size <= 100 ? size : 10;
    }

    private PageRequest pageRequest(int page) {
        return PageRequest.of(Math.max(page, 1) - 1, pageSize());
    }

    @Override
    public Page<Post> latest(int page) {
        return postRepository.findPublished(pageRequest(page));
    }

    @Override
    public Page<Post> latest(int page, int size) {
        return postRepository.findPublished(PageRequest.of(Math.max(page, 1) - 1, Math.max(size, 1)));
    }

    @Override
    public Optional<Post> article(String slug) {
        return slug == null || slug.isBlank() ? Optional.empty() : postRepository.findPublishedBySlug(slug);
    }

    @Override
    public Optional<Post> previous(Post post) {
        if (post.getPublishedAt() == null) return Optional.empty();
        return postRepository.findPublishedBefore(post.getPublishedAt(), post.getId(), PageRequest.of(0, 1))
                .stream().findFirst();
    }

    @Override
    public Optional<Post> next(Post post) {
        if (post.getPublishedAt() == null) return Optional.empty();
        return postRepository.findPublishedAfter(post.getPublishedAt(), post.getId(), PageRequest.of(0, 1))
                .stream().findFirst();
    }

    @Override
    public List<CategoryCard> categories() {
        Map<Long, Long> counts = new HashMap<>();
        for (Object[] row : postRepository.countPublishedByCategory()) {
            counts.put((Long) row[0], (Long) row[1]);
        }
        return categoryRepository.findByVisibleTrueOrderByNameAsc().stream()
                .filter(c -> counts.getOrDefault(c.getId(), 0L) > 0)
                .map(c -> new CategoryCard(c, counts.get(c.getId())))
                .toList();
    }

    @Override
    public Optional<Category> category(String slug) {
        return slug == null || slug.isBlank() ? Optional.empty() : categoryRepository.findBySlugAndVisibleTrue(slug);
    }

    @Override
    public Page<Post> postsInCategory(Category category, int page) {
        return postRepository.findPublishedByCategory(category.getId(), pageRequest(page));
    }

    @Override
    public Optional<User> author(Long id) {
        return id == null ? Optional.empty() : userRepository.findById(id);
    }

    @Override
    public Optional<Long> mainAuthorId() {
        return postRepository.findPublishingAuthorIds(PageRequest.of(0, 1)).stream().findFirst();
    }

    @Override
    public Page<Post> postsByAuthor(User author, int page) {
        return postRepository.findPublishedByAuthor(author.getId(), pageRequest(page));
    }

    @Override
    public AuthorStats authorStats(User author) {
        long articles = postRepository.findPublishedByAuthor(author.getId(), PageRequest.of(0, 1)).getTotalElements();
        return new AuthorStats(articles, postRepository.countPublishedCategoriesByAuthor(author.getId()));
    }

    @Override
    public Page<Post> search(String query, int page) {
        String q = normalizeQuery(query);
        if (q.length() < MIN_QUERY_LENGTH) {
            return Page.empty(pageRequest(page));
        }
        return postRepository.searchPublished(escapeLike(q), pageRequest(page));
    }

    @Override
    public Page<Post> postsWithHashtag(String slug, int page) {
        String tag = slug == null ? "" : slug.trim();
        if (tag.isEmpty()) {
            return Page.empty(pageRequest(page));
        }
        return postRepository.findPublishedByHashtag(tag, pageRequest(page));
    }

    @Override
    public Optional<Hashtag> hashtag(String slug) {
        String tag = slug == null ? "" : slug.trim();
        if (tag.isEmpty()) {
            return Optional.empty();
        }
        return hashtagRepository.findBySlugIgnoreCase(tag).filter(Hashtag::isActive);
    }

    static String normalizeQuery(String query) {
        if (query == null) return "";
        String q = query.trim().replaceAll("\\s+", " ");
        return q.length() > MAX_QUERY_LENGTH ? q.substring(0, MAX_QUERY_LENGTH) : q;
    }

    /** % and _ typed by a reader are searched for literally (MySQL's default LIKE escape is backslash). */
    static String escapeLike(String q) {
        return q.replace("\\", "\\\\").replace("%", "\\%").replace("_", "\\_");
    }
}
