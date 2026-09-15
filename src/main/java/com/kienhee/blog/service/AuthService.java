package com.kienhee.blog.service;

import com.kienhee.blog.dto.ForgotPasswordRequest;
import com.kienhee.blog.dto.RegisterRequest;
import com.kienhee.blog.entity.User;

import java.util.Optional;

public interface AuthService {

    /**
     * Creates an account. The first account of the site is an active Admin; later accounts get the User role and
     * stay PENDING until an admin approves them (active admins are emailed).
     *
     * @throws IllegalArgumentException when the email is taken
     */
    User register(RegisterRequest request);

    /** True while the site has no account yet. */
    boolean isFirstAccount();

    boolean existsByEmail(String email);

    Optional<User> findByEmail(String email);

    boolean changePassword(String email, String currentPassword, String newPassword);

    User updateProfile(String email, com.kienhee.blog.dto.ProfileUpdateRequest request);
}

