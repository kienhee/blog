package com.kienhee.blog.service;

import java.time.LocalDate;
import java.util.List;

/** Anonymous, per-day article view counts (V23). Deciding what counts is ViewCountingPolicy's job. */
public interface PageViewService {

    record TopPost(Long id, String title, String slug, long views) {}

    /** Adds one view for the post on that day. */
    void recordView(Long postId, LocalDate day);

    /** Total views with {@code from <= day < toExclusive}. */
    long viewsBetween(LocalDate from, LocalDate toExclusive);

    /** Most viewed live, published posts since {@code from}, most first. */
    List<TopPost> topPosts(LocalDate from, int limit);
}
