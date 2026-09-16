package com.kienhee.blog.service;

import com.kienhee.blog.entity.Category;
import com.kienhee.blog.entity.Hashtag;
import com.kienhee.blog.entity.Post;
import com.kienhee.blog.entity.User;
import org.springframework.data.domain.Page;

import java.util.List;
import java.util.Optional;

/**
 * Read-only queries for the public site. Only {@code PUBLISHED} posts are ever returned, and only
 * visible categories. Page numbers are 1-based; the page size is the {@code blog.posts_per_page} setting.
 */
public interface PublicBlogService {

    record CategoryCard(Category category, long postCount) {}

    record AuthorStats(long articles, long categories) {}

    int pageSize();

    Page<Post> latest(int page);

    Page<Post> latest(int page, int size);

    Optional<Post> article(String slug);

    /** The next older published post, if any. */
    Optional<Post> previous(Post post);

    /** The next newer published post, if any. */
    Optional<Post> next(Post post);

    /** Visible categories that have at least one published post, alphabetically. */
    List<CategoryCard> categories();

    Optional<Category> category(String slug);

    Page<Post> postsInCategory(Category category, int page);

    Optional<User> author(Long id);

    /** The author with the most published posts (the header's "Author" link). */
    Optional<Long> mainAuthorId();

    Page<Post> postsByAuthor(User author, int page);

    AuthorStats authorStats(User author);

    /** Blank or very short queries return an empty page. */
    Page<Post> search(String query, int page);

    /** Published posts tagged with this hashtag slug (case-insensitive); empty page when the slug is blank. */
    Page<Post> postsWithHashtag(String slug, int page);

    /** The hashtag itself, so the search page can show its real name; empty when unknown or inactive. */
    Optional<Hashtag> hashtag(String slug);
}
