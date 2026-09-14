package com.kienhee.blog.service.impl;

import com.kienhee.blog.dto.PostCreateRequest;
import com.kienhee.blog.dto.PostUpdateRequest;
import com.kienhee.blog.entity.Category;
import com.kienhee.blog.entity.Hashtag;
import com.kienhee.blog.entity.Post;
import com.kienhee.blog.entity.PostStatus;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.CategoryRepository;
import com.kienhee.blog.repository.HashtagRepository;
import com.kienhee.blog.repository.PostRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.PostService;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDateTime;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Optional;
import java.util.Set;

@Service
@RequiredArgsConstructor
public class PostServiceImpl implements PostService {

    private final PostRepository postRepository;
    private final CategoryRepository categoryRepository;
    private final HashtagRepository hashtagRepository;
    private final UserRepository userRepository;

    @Override
    @Transactional(readOnly = true)
    public List<Post> getAllPosts() {
        return postRepository.findAllWithDetails();
    }

    @Override
    @Transactional(readOnly = true)
    public Optional<Post> getPostById(Long id) {
        return postRepository.findWithDetailsById(id);
    }

    @Override
    @Transactional
    public Post createPost(PostCreateRequest request, String authorEmail) {
        String slug = request.getSlug().trim().toLowerCase();
        if (postRepository.existsBySlug(slug)) {
            throw new IllegalArgumentException("Slug already in use: " + slug);
        }

        Category category = categoryRepository.findById(request.getCategoryId())
                .orElseThrow(() -> new IllegalArgumentException("Category not found."));

        User author = userRepository.findByEmail(authorEmail)
                .orElseThrow(() -> new IllegalArgumentException("Author not found."));

        Set<Hashtag> hashtags = resolveHashtags(request.getHashtagIds());

        Post post = Post.builder()
                .title(request.getTitle().trim())
                .slug(slug)
                .excerpt(request.getExcerpt() != null && !request.getExcerpt().isBlank() ? request.getExcerpt().trim() : null)
                .content(request.getContent())
                .coverImage(request.getCoverImage() != null && !request.getCoverImage().isBlank() ? request.getCoverImage().trim() : null)
                .status(request.getStatus())
                .category(category)
                .author(author)
                .hashtags(hashtags)
                .seoTitle(request.getSeoTitle() != null && !request.getSeoTitle().isBlank() ? request.getSeoTitle().trim() : null)
                .seoDescription(request.getSeoDescription() != null && !request.getSeoDescription().isBlank() ? request.getSeoDescription().trim() : null)
                .publishedAt(request.getStatus() == PostStatus.PUBLISHED ? LocalDateTime.now() : null)
                .build();

        return postRepository.save(post);
    }

    @Override
    @Transactional
    public Post updatePost(Long id, PostUpdateRequest request) {
        Post post = postRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("Post not found with id: " + id));

        String slug = request.getSlug().trim().toLowerCase();
        if (postRepository.existsBySlugAndIdNot(slug, id)) {
            throw new IllegalArgumentException("Slug already in use: " + slug);
        }

        Category category = categoryRepository.findById(request.getCategoryId())
                .orElseThrow(() -> new IllegalArgumentException("Category not found."));

        Set<Hashtag> hashtags = resolveHashtags(request.getHashtagIds());

        boolean becomingPublished = request.getStatus() == PostStatus.PUBLISHED && post.getPublishedAt() == null;

        post.setTitle(request.getTitle().trim());
        post.setSlug(slug);
        post.setExcerpt(request.getExcerpt() != null && !request.getExcerpt().isBlank() ? request.getExcerpt().trim() : null);
        post.setContent(request.getContent());
        post.setCoverImage(request.getCoverImage() != null && !request.getCoverImage().isBlank() ? request.getCoverImage().trim() : null);
        post.setStatus(request.getStatus());
        post.setCategory(category);
        post.getHashtags().clear();
        post.getHashtags().addAll(hashtags);
        post.setSeoTitle(request.getSeoTitle() != null && !request.getSeoTitle().isBlank() ? request.getSeoTitle().trim() : null);
        post.setSeoDescription(request.getSeoDescription() != null && !request.getSeoDescription().isBlank() ? request.getSeoDescription().trim() : null);
        if (becomingPublished) {
            post.setPublishedAt(LocalDateTime.now());
        }

        return postRepository.save(post);
    }

    @Override
    @Transactional
    public void deletePost(Long id) {
        Post post = postRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("Post not found with id: " + id));
        postRepository.delete(post);
    }

    @Override
    @Transactional(readOnly = true)
    public boolean existsBySlug(String slug) {
        if (slug == null) return false;
        return postRepository.existsBySlug(slug.trim().toLowerCase());
    }

    @Override
    @Transactional(readOnly = true)
    public boolean existsBySlugExcluding(String slug, Long id) {
        if (slug == null) return false;
        return postRepository.existsBySlugAndIdNot(slug.trim().toLowerCase(), id);
    }

    private Set<Hashtag> resolveHashtags(Set<Long> hashtagIds) {
        if (hashtagIds == null || hashtagIds.isEmpty()) {
            return new LinkedHashSet<>();
        }
        List<Hashtag> found = hashtagRepository.findAllById(hashtagIds);
        if (found.size() != hashtagIds.size()) {
            throw new IllegalArgumentException("One or more selected hashtags were not found.");
        }
        return new LinkedHashSet<>(found);
    }
}
