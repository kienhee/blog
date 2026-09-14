package com.kienhee.blog.service;

public interface MediaOptimizationService {

    /**
     * Compresses the stored file for the given media in the background via TinyPNG,
     * replacing it in place and updating the record. No-op if optimization is disabled,
     * the media is not found, is already optimized, or is an SVG. Failures are logged
     * and swallowed — the original file stays in place.
     */
    void optimizeAsync(Long mediaId);
}
