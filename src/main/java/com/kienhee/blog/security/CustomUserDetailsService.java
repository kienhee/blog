package com.kienhee.blog.security;

import com.kienhee.blog.entity.Permission;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.UserRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.security.core.GrantedAuthority;
import org.springframework.security.core.authority.SimpleGrantedAuthority;
import org.springframework.security.core.userdetails.UserDetails;
import org.springframework.security.core.userdetails.UserDetailsService;
import org.springframework.security.core.userdetails.UsernameNotFoundException;
import org.springframework.stereotype.Service;

import java.util.ArrayList;
import java.util.List;

@Service
@RequiredArgsConstructor
public class CustomUserDetailsService implements UserDetailsService {

    private final UserRepository userRepository;

    @Override
    public UserDetails loadUserByUsername(String email) throws UsernameNotFoundException {
        User user = userRepository.findByEmailWithRole(email.trim().toLowerCase())
                .orElseThrow(() -> new UsernameNotFoundException("User not found with email: " + email));

        return org.springframework.security.core.userdetails.User.builder()
                .username(user.getEmail())
                .password(user.getPassword())
                .authorities(buildAuthorities(user))
                .build();
    }

    /**
     * Authorities are the permission codes themselves ("posts:create"), plus a
     * ROLE_<SLUG> entry so role-level checks stay possible. A user with no role
     * gets no authorities at all — they can still sign in and reach their own
     * profile, but every guarded action is denied.
     */
    private List<GrantedAuthority> buildAuthorities(User user) {
        List<GrantedAuthority> authorities = new ArrayList<>();
        if (user.getRole() == null) {
            return authorities;
        }

        authorities.add(new SimpleGrantedAuthority("ROLE_" + user.getRole().getSlug().toUpperCase()));
        for (Permission permission : user.getRole().getPermissions()) {
            authorities.add(new SimpleGrantedAuthority(permission.getCode()));
        }
        return authorities;
    }
}
