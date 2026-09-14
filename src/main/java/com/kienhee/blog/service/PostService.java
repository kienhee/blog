package com.kienhee.blog.service;

import com.kienhee.blog.dto.PostCreateRequest;
import com.kienhee.blog.dto.PostUpdateRequest;
import com.kienhee.blog.entity.Post;

import java.util.List;
import java.util.Optional;

public interface PostService {

    List<Post> getAllPosts();

    Optional<Post> getPostById(Long id);

    Post createPost(PostCreateRequest request, String authorEmail);

    Post updatePost(Long id, PostUpdateRequest request);

    void deletePost(Long id);

    boolean existsBySlug(String slug);

    boolean existsBySlugExcluding(String slug, Long id);
}
