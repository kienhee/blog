package com.kienhee.blog.service.impl;

import com.kienhee.blog.config.AppMailProperties;
import com.kienhee.blog.service.MailService;
import com.kienhee.blog.entity.UserStatus;
import com.kienhee.blog.dto.UserCreateRequest;
import com.kienhee.blog.dto.UserUpdateRequest;
import com.kienhee.blog.entity.Role;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.PostRepository;
import com.kienhee.blog.repository.RoleRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.UserService;
import lombok.RequiredArgsConstructor;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;
import java.util.Optional;

@Service
@RequiredArgsConstructor
public class UserServiceImpl implements UserService {

    private final UserRepository userRepository;
    private final RoleRepository roleRepository;
    private final PasswordEncoder passwordEncoder;
    private final PostRepository postRepository;
    private final MailService mailService;
    private final AppMailProperties mailProperties;

    @Override
    @Transactional(readOnly = true)
    public List<User> getAllUsers() {
        return userRepository.findAllWithRole();
    }

    @Override
    @Transactional(readOnly = true)
    public Optional<User> getUserById(Long id) {
        return userRepository.findById(id);
    }

    @Override
    @Transactional
    public User createUser(UserCreateRequest request) {
        return createUser(request, true);
    }

    @Override
    @Transactional
    public User createUser(UserCreateRequest request, boolean actorIsAdmin) {
        String email = request.getEmail().trim().toLowerCase();
        if (userRepository.existsByEmail(email)) {
            throw new IllegalArgumentException("Email already in use: " + request.getEmail());
        }

        Role role = resolveRole(request.getRoleId());
        if (!actorIsAdmin && isAdminRole(role)) {
            throw new IllegalArgumentException(ADMIN_ONLY);
        }

        User user = User.builder()
                .fullName(request.getFullName().trim())
                .email(email)
                .password(passwordEncoder.encode(request.getPassword()))
                .phone(request.getPhone() != null && !request.getPhone().isBlank() ? request.getPhone().trim() : null)
                .address(request.getAddress() != null && !request.getAddress().isBlank() ? request.getAddress().trim() : null)
                .bio(request.getBio() != null && !request.getBio().isBlank() ? request.getBio().trim() : null)
                .role(role)
                .status(UserStatus.ACTIVE)
                .build();

        return userRepository.save(user);
    }

    private Role resolveRole(Long roleId) {
        if (roleId == null) {
            return null;
        }
        return roleRepository.findById(roleId)
                .orElseThrow(() -> new IllegalArgumentException("Role not found."));
    }

    @Override
    @Transactional
    public User updateUser(Long id, UserUpdateRequest request) {
        return updateUser(id, request, true);
    }

    @Override
    @Transactional
    public User updateUser(Long id, UserUpdateRequest request, boolean actorIsAdmin) {
        User user = userRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("User not found with id: " + id));
        Role newRole = resolveRole(request.getRoleId());

        boolean targetIsAdmin = isAdminRole(user.getRole());
        if (!actorIsAdmin && (targetIsAdmin || isAdminRole(newRole))) {
            throw new IllegalArgumentException(ADMIN_ONLY);
        }
        if (targetIsAdmin && user.getStatus() == UserStatus.ACTIVE && !isAdminRole(newRole)
                && userRepository.countByRole_SystemRoleTrueAndStatus(UserStatus.ACTIVE) <= 1) {
            throw new IllegalArgumentException(LAST_ADMIN);
        }

        user.setFullName(request.getFullName().trim());
        user.setPhone(request.getPhone() != null && !request.getPhone().isBlank() ? request.getPhone().trim() : null);
        user.setAddress(request.getAddress() != null && !request.getAddress().isBlank() ? request.getAddress().trim() : null);
        user.setBio(request.getBio() != null && !request.getBio().isBlank() ? request.getBio().trim() : null);

        if (request.getPassword() != null && !request.getPassword().isBlank()) {
            user.setPassword(passwordEncoder.encode(request.getPassword()));
        }
        user.setRole(newRole);

        return userRepository.save(user);
    }

    @Override
    @Transactional
    public void deleteUser(Long id, String currentAdminEmail) {
        deleteUser(id, currentAdminEmail, true);
    }

    @Override
    @Transactional
    public void deleteUser(Long id, String currentAdminEmail, boolean actorIsAdmin) {
        User user = userRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("User not found with id: " + id));

        if (currentAdminEmail != null && user.getEmail().equalsIgnoreCase(currentAdminEmail.trim())) {
            throw new IllegalArgumentException("You cannot delete your own account.");
        }
        boolean targetIsAdmin = isAdminRole(user.getRole());
        if (targetIsAdmin && !actorIsAdmin) {
            throw new IllegalArgumentException(ADMIN_ONLY);
        }
        if (targetIsAdmin && user.getStatus() == UserStatus.ACTIVE
                && userRepository.countByRole_SystemRoleTrueAndStatus(UserStatus.ACTIVE) <= 1) {
            throw new IllegalArgumentException(LAST_ADMIN);
        }
        if (postRepository.existsByAuthor_Id(id)) {
            throw new IllegalArgumentException("This user still has posts. Delete or reassign their posts first.");
        }

        userRepository.delete(user);
    }

    @Override
    @Transactional(readOnly = true)
    public boolean existsByEmail(String email) {
        if (email == null) return false;
        return userRepository.existsByEmail(email.trim().toLowerCase());
    }

    private static boolean isAdminRole(Role role) {
        return role != null && role.isSystemRole();
    }

    @Override
    @Transactional
    public void changeStatus(Long id, UserStatus status, String actorEmail, boolean actorIsAdmin) {
        User user = userRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("User not found with id: " + id));
        if (actorEmail != null && user.getEmail().equalsIgnoreCase(actorEmail.trim())) {
            throw new IllegalArgumentException("You can't change the status of your own account.");
        }
        boolean targetIsAdmin = isAdminRole(user.getRole());
        if (targetIsAdmin && !actorIsAdmin) {
            throw new IllegalArgumentException(ADMIN_ONLY);
        }
        if (targetIsAdmin && user.getStatus() == UserStatus.ACTIVE && status != UserStatus.ACTIVE
                && userRepository.countByRole_SystemRoleTrueAndStatus(UserStatus.ACTIVE) <= 1) {
            throw new IllegalArgumentException(LAST_ADMIN);
        }

        boolean approving = user.getStatus() == UserStatus.PENDING && status == UserStatus.ACTIVE;
        if (status == UserStatus.ACTIVE && user.getRole() == null) {
            roleRepository.findBySlug("user").ifPresent(user::setRole);
        }
        user.setStatus(status);
        userRepository.save(user);

        if (approving && mailService != null) {
            String to = user.getEmail();
            java.util.Map<String, Object> variables = java.util.Map.of(
                    "name", user.getFullName(),
                    "loginUrl", mailProperties.getBaseUrl().replaceAll("/+$", "") + "/auth/login",
                    "siteName", mailProperties.getFromName());
            org.springframework.transaction.support.TransactionSynchronizationManager.registerSynchronization(
                    new org.springframework.transaction.support.TransactionSynchronization() {
                        @Override
                        public void afterCommit() {
                            mailService.send(to, "Your account is approved", "account-approved", variables);
                        }
                    });
        }
    }
}
