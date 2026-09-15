package com.kienhee.blog.service;

import com.kienhee.blog.repository.UserRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Component;

/**
 * Sign-up is always open. The first account of a fresh install becomes an active Admin; every later account gets
 * the User role and waits for an admin to approve it. Kept as its own bean so tests can simulate a fresh install.
 */
@Component
@RequiredArgsConstructor
public class RegistrationPolicy {

    private final UserRepository userRepository;

    /** True while nobody has an account yet: that sign-up becomes the active Admin. */
    public boolean isFirstAccount() {
        return userRepository.count() == 0;
    }
}
