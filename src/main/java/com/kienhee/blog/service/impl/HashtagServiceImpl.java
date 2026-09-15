package com.kienhee.blog.service.impl;

import java.time.LocalDateTime;
import com.kienhee.blog.dto.HashtagCreateRequest;
import com.kienhee.blog.dto.HashtagUpdateRequest;
import com.kienhee.blog.entity.Hashtag;
import com.kienhee.blog.repository.HashtagRepository;
import com.kienhee.blog.service.HashtagService;
import lombok.RequiredArgsConstructor;
import org.springframework.data.domain.Sort;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;
import java.util.Optional;

@Service
@RequiredArgsConstructor
public class HashtagServiceImpl implements HashtagService {

    private final HashtagRepository hashtagRepository;

    @Override
    @Transactional(readOnly = true)
    public List<Hashtag> getAllHashtags() {
        return hashtagRepository.findAll(Sort.by(Sort.Direction.ASC, "name"));
    }

    @Override
    @Transactional(readOnly = true)
    public Optional<Hashtag> getHashtagById(Long id) {
        return hashtagRepository.findById(id);
    }

    @Override
    @Transactional
    public Hashtag createHashtag(HashtagCreateRequest request) {
        String slug = request.getSlug().trim().toLowerCase();
        if (hashtagRepository.existsBySlug(slug)) {
            throw new IllegalArgumentException("Slug already in use: " + slug);
        }

        Hashtag hashtag = Hashtag.builder()
                .name(request.getName().trim())
                .slug(slug)
                .description(request.getDescription() != null && !request.getDescription().isBlank() ? request.getDescription().trim() : null)
                .active(request.isActive())
                .build();

        return hashtagRepository.save(hashtag);
    }

    @Override
    @Transactional
    public Hashtag updateHashtag(Long id, HashtagUpdateRequest request) {
        Hashtag hashtag = hashtagRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("Hashtag not found with id: " + id));

        String slug = request.getSlug().trim().toLowerCase();
        if (hashtagRepository.existsBySlugAndIdNot(slug, id)) {
            throw new IllegalArgumentException("Slug already in use: " + slug);
        }

        hashtag.setName(request.getName().trim());
        hashtag.setSlug(slug);
        hashtag.setDescription(request.getDescription() != null && !request.getDescription().isBlank() ? request.getDescription().trim() : null);
        hashtag.setActive(request.isActive());

        return hashtagRepository.save(hashtag);
    }

    @Override
    @Transactional
    public void deleteHashtag(Long id) {
        Hashtag hashtag = hashtagRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("Hashtag not found with id: " + id));

        // To the Trash: it disappears from posts until restored.
        hashtagRepository.moveToTrash(hashtag.getId(), LocalDateTime.now());
    }

    @Override
    @Transactional(readOnly = true)
    public boolean existsBySlug(String slug) {
        if (slug == null) return false;
        return hashtagRepository.existsBySlug(slug.trim().toLowerCase());
    }

    @Override
    @Transactional(readOnly = true)
    public boolean existsBySlugExcluding(String slug, Long id) {
        if (slug == null) return false;
        return hashtagRepository.existsBySlugAndIdNot(slug.trim().toLowerCase(), id);
    }
}
