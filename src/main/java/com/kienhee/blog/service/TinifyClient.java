package com.kienhee.blog.service;

public interface TinifyClient {

    /**
     * Sends the given image bytes to the TinyPNG (tinify.com) API for lossy compression
     * and returns the optimized bytes. Throws on any failure (network, auth, quota) —
     * callers should fall back to storing the original file rather than fail the upload.
     */
    byte[] compress(byte[] original, String contentType);
}
