package com.kienhee.blog.security;

import jakarta.servlet.FilterChain;
import jakarta.servlet.ServletException;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import org.springframework.web.filter.OncePerRequestFilter;

import java.io.IOException;

/**
 * Runs before Spring Security's form login: a blocked email or IP is sent back to the sign-in page
 * without the password ever being checked. Registered in {@code SecurityConfig}, not as a servlet
 * filter bean (it would otherwise run twice).
 */
public class LoginThrottleFilter extends OncePerRequestFilter {

    private final LoginAttemptService loginAttemptService;

    public LoginThrottleFilter(LoginAttemptService loginAttemptService) {
        this.loginAttemptService = loginAttemptService;
    }

    @Override
    protected boolean shouldNotFilter(HttpServletRequest request) {
        String path = request.getRequestURI().substring(request.getContextPath().length());
        return !("POST".equalsIgnoreCase(request.getMethod()) && "/auth/login".equals(path));
    }

    @Override
    protected void doFilterInternal(HttpServletRequest request, HttpServletResponse response, FilterChain chain)
            throws ServletException, IOException {
        if (loginAttemptService.isBlocked(request.getParameter("email"), request.getRemoteAddr())) {
            response.sendRedirect(request.getContextPath() + "/auth/login?locked=true");
            return;
        }
        chain.doFilter(request, response);
    }
}
