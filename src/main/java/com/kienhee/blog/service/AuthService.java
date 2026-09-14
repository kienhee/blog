package com.kienhee.blog.service;

import com.kienhee.blog.dto.ForgotPasswordRequest;
import com.kienhee.blog.dto.RegisterRequest;
import com.kienhee.blog.entity.User;

import java.util.Optional;

public interface AuthService {

    User register(RegisterRequest request);

    boolean resetPassword(ForgotPasswordRequest request);

    boolean existsByEmail(String email);

    Optional<User> findByEmail(String email);

    boolean changePassword(String email, String currentPassword, String newPassword);

    User updateProfile(String email, com.kienhee.blog.dto.ProfileUpdateRequest request);
}

