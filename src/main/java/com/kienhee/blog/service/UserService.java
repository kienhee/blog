package com.kienhee.blog.service;

import com.kienhee.blog.dto.UserCreateRequest;
import com.kienhee.blog.dto.UserUpdateRequest;
import com.kienhee.blog.entity.User;

import java.util.List;
import java.util.Optional;

public interface UserService {

    List<User> getAllUsers();

    Optional<User> getUserById(Long id);

    User createUser(UserCreateRequest request);

    User updateUser(Long id, UserUpdateRequest request);

    void deleteUser(Long id, String currentAdminEmail);

    boolean existsByEmail(String email);
}

