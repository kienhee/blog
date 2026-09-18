package com.kienhee.blog.service;

import com.kienhee.blog.entity.UserStatus;
import com.kienhee.blog.dto.UserCreateRequest;
import com.kienhee.blog.dto.UserUpdateRequest;
import com.kienhee.blog.entity.User;

import java.util.List;
import java.util.Optional;

public interface UserService {

    /** Shown when someone without admin rights tries to grant the Admin role or touch an admin account. */
    String ADMIN_ONLY = "error.user.admin_only";

    String LAST_ADMIN = "error.user.last_admin";

    List<User> getAllUsers();

    Optional<User> getUserById(Long id);

    /** Trusted internal call (no actor checks). Admin pages use {@link #createUser(UserCreateRequest, boolean)}. */
    User createUser(UserCreateRequest request);

    /**
     * @param actorIsAdmin whether the person doing this holds admin rights ({@code roles:edit}); only they can
     *                     grant the Admin (system) role
     */
    User createUser(UserCreateRequest request, boolean actorIsAdmin);

    /** Trusted internal call (no actor checks). Admin pages use {@link #updateUser(Long, UserUpdateRequest, boolean)}. */
    User updateUser(Long id, UserUpdateRequest request);

    /**
     * Only admins may grant the Admin role or change an account that has it, and the last admin can never
     * lose the role.
     */
    User updateUser(Long id, UserUpdateRequest request, boolean actorIsAdmin);

    /** Trusted internal call: self-delete, last-admin and has-posts rules still apply. */
    void deleteUser(Long id, String currentAdminEmail);

    void deleteUser(Long id, String currentAdminEmail, boolean actorIsAdmin);

    boolean existsByEmail(String email);

    /**
     * Approves (PENDING → ACTIVE), disables or re-enables an account. Nobody changes their own status, only admins
     * change an admin's, and the last active admin stays active. Approving emails the person; an approved account
     * without a role gets the User role.
     */
    void changeStatus(Long id, UserStatus status, String actorEmail, boolean actorIsAdmin);
}
