package com.kienhee.blog;

import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.RegistrationPolicy;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.assertFalse;
import static org.junit.jupiter.api.Assertions.assertTrue;
import static org.mockito.Mockito.mock;
import static org.mockito.Mockito.when;

@DisplayName("RegistrationPolicy (unit)")
class RegistrationPolicyTests {

    @Test
    @DisplayName("only a sign-up on an empty users table is the first account")
    void firstAccountOnlyWhenEmpty() {
        UserRepository users = mock(UserRepository.class);
        RegistrationPolicy policy = new RegistrationPolicy(users);

        when(users.count()).thenReturn(0L);
        assertTrue(policy.isFirstAccount());

        when(users.count()).thenReturn(1L);
        assertFalse(policy.isFirstAccount());
    }
}
