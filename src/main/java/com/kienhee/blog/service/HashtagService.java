package com.kienhee.blog.service;

import com.kienhee.blog.dto.HashtagCreateRequest;
import com.kienhee.blog.dto.HashtagUpdateRequest;
import com.kienhee.blog.entity.Hashtag;

import java.util.List;
import java.util.Optional;

public interface HashtagService {

    List<Hashtag> getAllHashtags();

    Optional<Hashtag> getHashtagById(Long id);

    Hashtag createHashtag(HashtagCreateRequest request);

    Hashtag updateHashtag(Long id, HashtagUpdateRequest request);

    void deleteHashtag(Long id);

    boolean existsBySlug(String slug);

    boolean existsBySlugExcluding(String slug, Long id);
}
