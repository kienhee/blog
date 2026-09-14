package com.kienhee.blog.service;

import com.kienhee.blog.dto.CommentForm;
import com.kienhee.blog.entity.Comment;
import com.kienhee.blog.entity.CommentStatus;
import com.kienhee.blog.entity.Post;
import com.kienhee.blog.entity.User;

import java.util.Collection;
import java.util.List;

public interface CommentService {

    /** A top-level comment and its approved replies (oldest first). */
    record CommentThread(Comment comment, List<Comment> replies) {
        public int size() {
            return 1 + replies.size();
        }
    }

    /** Approved comments of a post grouped into threads. Replies under a non-approved parent are hidden. */
    List<CommentThread> approvedThreads(Long postId);

    /** Whether guest comments wait for approval ({@code comments.moderation} setting, on by default). */
    boolean moderationEnabled();

    /**
     * Saves a comment. Signed-in users ({@code user != null}) are approved right away and named from
     * their account; guests are PENDING while moderation is on. A reply to a reply is stored under
     * the top-level comment.
     *
     * @throws IllegalArgumentException when the parent is missing, not approved or on another post
     */
    Comment submit(Post post, CommentForm form, User user, String ipAddress, String userAgent);

    List<Comment> allForAdmin();

    long countByStatus(CommentStatus status);

    /** @throws IllegalArgumentException when no ids are given */
    int updateStatus(Collection<Long> ids, CommentStatus status);

    /** Deletes the comments and their replies. @throws IllegalArgumentException when no ids are given */
    int delete(Collection<Long> ids);

    /** "approved" / "pending" / "spam" (any case) → status. @throws IllegalArgumentException otherwise */
    static CommentStatus parseStatus(String value) {
        try {
            return CommentStatus.valueOf(value == null ? "" : value.trim().toUpperCase());
        } catch (IllegalArgumentException e) {
            throw new IllegalArgumentException("Unknown comment status.");
        }
    }
}
