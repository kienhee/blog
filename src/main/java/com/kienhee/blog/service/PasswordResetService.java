package com.kienhee.blog.service;

import com.kienhee.blog.entity.User;

import java.util.Optional;

/**
 * Password reset by emailed link: a random single-use token (only its SHA-256 is stored) that expires
 * after {@code 30} minutes. Requesting a new link or completing a reset invalidates older links.
 */
public interface PasswordResetService {

    /**
     * Emails a reset link when an account exists for {@code email}. Behaves identically for unknown
     * emails and when rate limited, so callers must always show the same neutral message.
     */
    void requestReset(String email, String ipAddress);

    /** The account for a usable (unused, unexpired) token. */
    Optional<User> findUserForToken(String token);

    /**
     * Sets the new password and uses up the link (and any other open links of the user).
     *
     * @throws IllegalArgumentException when the token is unknown, used or expired
     */
    void resetPassword(String token, String newPassword);
}
