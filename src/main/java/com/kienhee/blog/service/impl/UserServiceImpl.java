package com.kienhee.blog.service.impl;

import com.kienhee.blog.dto.UserCreateRequest;
import com.kienhee.blog.dto.UserUpdateRequest;
import com.kienhee.blog.entity.Role;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.RoleRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.UserService;
import lombok.RequiredArgsConstructor;
import org.springframework.data.domain.Sort;
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
        String email = request.getEmail().trim().toLowerCase();
        if (userRepository.existsByEmail(email)) {
            throw new IllegalArgumentException("Email already in use: " + request.getEmail());
        }

        User user = User.builder()
                .fullName(request.getFullName().trim())
                .email(email)
                .password(passwordEncoder.encode(request.getPassword()))
                .phone(request.getPhone() != null && !request.getPhone().isBlank() ? request.getPhone().trim() : null)
                .address(request.getAddress() != null && !request.getAddress().isBlank() ? request.getAddress().trim() : null)
                .bio(request.getBio() != null && !request.getBio().isBlank() ? request.getBio().trim() : null)
                .role(resolveRole(request.getRoleId()))
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
        User user = userRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("User not found with id: " + id));

        user.setFullName(request.getFullName().trim());
        user.setPhone(request.getPhone() != null && !request.getPhone().isBlank() ? request.getPhone().trim() : null);
        user.setAddress(request.getAddress() != null && !request.getAddress().isBlank() ? request.getAddress().trim() : null);
        user.setBio(request.getBio() != null && !request.getBio().isBlank() ? request.getBio().trim() : null);

        if (request.getPassword() != null && !request.getPassword().isBlank()) {
            user.setPassword(passwordEncoder.encode(request.getPassword()));
        }
        user.setRole(resolveRole(request.getRoleId()));

        return userRepository.save(user);
    }

    @Override
    @Transactional
    public void deleteUser(Long id, String currentAdminEmail) {
        User user = userRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("User not found with id: " + id));

        if (currentAdminEmail != null && user.getEmail().equalsIgnoreCase(currentAdminEmail.trim())) {
            throw new IllegalArgumentException("You cannot delete your own account.");
        }

        userRepository.delete(user);
    }

    @Override
    @Transactional(readOnly = true)
    public boolean existsByEmail(String email) {
        if (email == null) return false;
        return userRepository.existsByEmail(email.trim().toLowerCase());
    }
}

