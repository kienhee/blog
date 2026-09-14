package com.kienhee.blog.service.impl;

import com.kienhee.blog.dto.ForgotPasswordRequest;
import com.kienhee.blog.dto.RegisterRequest;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.AuthService;
import lombok.RequiredArgsConstructor;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.Optional;

@Service
@RequiredArgsConstructor
public class AuthServiceImpl implements AuthService {

    private final UserRepository userRepository;
    private final PasswordEncoder passwordEncoder;

    @Override
    @Transactional
    public User register(RegisterRequest request) {
        if (userRepository.existsByEmail(request.getEmail())) {
            throw new IllegalArgumentException("Email already in use: " + request.getEmail());
        }

        User user = User.builder()
                .fullName(request.getFullName())
                .email(request.getEmail().trim().toLowerCase())
                .password(passwordEncoder.encode(request.getPassword()))
                .phone(request.getPhone())
                .address(request.getAddress())
                .bio(request.getBio())
                .build();

        return userRepository.save(user);
    }

    @Override
    @Transactional
    public boolean resetPassword(ForgotPasswordRequest request) {
        Optional<User> optionalUser = userRepository.findByEmail(request.getEmail().trim().toLowerCase());
        if (optionalUser.isEmpty()) {
            return false;
        }

        User user = optionalUser.get();
        String newRawPassword = (request.getNewPassword() != null && !request.getNewPassword().isBlank())
                ? request.getNewPassword()
                : "12345678"; // Default temp password if not specified

        user.setPassword(passwordEncoder.encode(newRawPassword));
        userRepository.save(user);
        return true;
    }

    @Override
    @Transactional(readOnly = true)
    public boolean existsByEmail(String email) {
        return userRepository.existsByEmail(email.trim().toLowerCase());
    }

    @Override
    @Transactional(readOnly = true)
    public Optional<User> findByEmail(String email) {
        return userRepository.findByEmail(email.trim().toLowerCase());
    }

    @Override
    @Transactional
    public boolean changePassword(String email, String currentPassword, String newPassword) {
        User user = userRepository.findByEmail(email.trim().toLowerCase())
                .orElseThrow(() -> new IllegalArgumentException("User not found with email: " + email));

        if (!passwordEncoder.matches(currentPassword, user.getPassword())) {
            throw new IllegalArgumentException("Current password is incorrect.");
        }

        if (currentPassword.equals(newPassword)) {
            throw new IllegalArgumentException("New password must be different from current password.");
        }

        user.setPassword(passwordEncoder.encode(newPassword));
        userRepository.save(user);
        return true;
    }

    @Override
    @Transactional
    public User updateProfile(String email, com.kienhee.blog.dto.ProfileUpdateRequest request) {
        User user = userRepository.findByEmail(email.trim().toLowerCase())
                .orElseThrow(() -> new IllegalArgumentException("User not found with email: " + email));

        user.setFullName(request.getFullName().trim());
        user.setPhone(request.getPhone());
        user.setAddress(request.getAddress());
        user.setBio(request.getBio());

        return userRepository.save(user);
    }
}

