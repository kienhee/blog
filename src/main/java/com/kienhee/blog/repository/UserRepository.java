package com.kienhee.blog.repository;

import com.kienhee.blog.entity.UserStatus;
import com.kienhee.blog.entity.User;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import org.springframework.stereotype.Repository;

import java.util.List;
import java.util.Optional;

@Repository
public interface UserRepository extends JpaRepository<User, Long> {

    Optional<User> findByEmail(String email);

    /**
     * Used at login: role + permissions must be fetched eagerly here because
     * UserDetailsService runs outside a transaction (open-in-view is off).
     */
    @Query("select u from User u left join fetch u.role r left join fetch r.permissions where u.email = :email")
    Optional<User> findByEmailWithRole(@Param("email") String email);

    boolean existsByEmail(String email);

    long countByRoleId(Long roleId);

    /** List view renders the role name, so it must be fetched inside the transaction. */
    @Query("select u from User u left join fetch u.role order by u.id desc")
    List<User> findAllWithRole();

    boolean existsByAvatarUrl(String avatarUrl);

    @Query("select distinct u.avatarUrl from User u where u.avatarUrl is not null")
    List<String> findDistinctAvatarUrls();

    /** How many accounts hold a system (Admin) role — the last one can never lose it. */
    long countByRole_SystemRoleTrue();

    /** Admin accounts that can still sign in — the last one can never be demoted, disabled or deleted. */
    long countByRole_SystemRoleTrueAndStatus(UserStatus status);

    long countByStatus(UserStatus status);

    /** Where "new account waiting for approval" emails go. */
    @Query("select u.email from User u join u.role r where r.systemRole = true and u.status = com.kienhee.blog.entity.UserStatus.ACTIVE")
    List<String> findActiveAdminEmails();
}
