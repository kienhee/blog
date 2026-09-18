package com.kienhee.blog.service.impl;

import com.kienhee.blog.config.I18n;

import com.kienhee.blog.exception.BusinessException;
import java.util.List;
import org.springframework.transaction.support.TransactionSynchronizationManager;
import org.springframework.transaction.support.TransactionSynchronization;
import com.kienhee.blog.config.AppMailProperties;
import com.kienhee.blog.service.MailService;
import com.kienhee.blog.entity.UserStatus;
import com.kienhee.blog.dto.ForgotPasswordRequest;
import com.kienhee.blog.dto.RegisterRequest;
import com.kienhee.blog.entity.Role;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.RoleRepository;
import com.kienhee.blog.service.RegistrationPolicy;
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
    private final RoleRepository roleRepository;
    private final RegistrationPolicy registrationPolicy;
    private final MailService mailService;
    private final AppMailProperties mailProperties;

    @Override
    @Transactional
    public User register(RegisterRequest request) {
        // Lock the Admin role row first: two sign-ups racing on an empty database are serialised here,
        // so only one of them can become the first (Admin) account.
        Role admin = roleRepository.findBySlugForUpdate("admin")
                .orElseThrow(() -> new IllegalStateException("The Admin role is missing (see V1__Auth.sql)."));
        boolean first = registrationPolicy.isFirstAccount();
        if (userRepository.existsByEmail(request.getEmail())) {
            throw new BusinessException("error.user.email_taken", request.getEmail());
        }

        User user = User.builder()
                .fullName(request.getFullName())
                .email(request.getEmail().trim().toLowerCase())
                .password(passwordEncoder.encode(request.getPassword()))
                .phone(request.getPhone())
                .address(request.getAddress())
                .bio(request.getBio())
                .role(first ? admin : roleRepository.findBySlug("user").orElse(null))
                .status(first ? UserStatus.ACTIVE : UserStatus.PENDING)
                .build();
        User saved = userRepository.save(user);

        if (!first) {
            List<String> admins = userRepository.findActiveAdminEmails();
            java.util.Map<String, Object> variables = java.util.Map.of(
                    "name", saved.getFullName(),
                    "email", saved.getEmail(),
                    "usersUrl", mailProperties.getBaseUrl().replaceAll("/+$", "") + "/admin/users",
                    "siteName", mailProperties.getFromName());
            TransactionSynchronizationManager.registerSynchronization(new TransactionSynchronization() {
                @Override
                public void afterCommit() {
                    admins.forEach(to -> mailService.send(to, I18n.DEFAULT, "account-pending-admin",
                            "mail.pending_admin.subject", variables));
                }
            });
        }
        return saved;
    }

    @Override
    public boolean isFirstAccount() {
        return registrationPolicy.isFirstAccount();
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
                .orElseThrow(() -> new BusinessException("error.auth.user_not_found", email));

        if (!passwordEncoder.matches(currentPassword, user.getPassword())) {
            throw new BusinessException("error.auth.wrong_password");
        }

        if (currentPassword.equals(newPassword)) {
            throw new BusinessException("error.auth.same_password");
        }

        user.setPassword(passwordEncoder.encode(newPassword));
        userRepository.save(user);
        return true;
    }

    @Override
    @Transactional
    public User updateProfile(String email, com.kienhee.blog.dto.ProfileUpdateRequest request) {
        User user = userRepository.findByEmail(email.trim().toLowerCase())
                .orElseThrow(() -> new BusinessException("error.auth.user_not_found", email));

        user.setFullName(request.getFullName().trim());
        user.setPhone(request.getPhone());
        user.setAddress(request.getAddress());
        user.setBio(request.getBio());

        return userRepository.save(user);
    }
}

