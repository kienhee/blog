package com.kienhee.blog.config;

import org.springframework.security.core.userdetails.UserDetailsService;
import org.springframework.security.authentication.AccountStatusUserDetailsChecker;
import org.springframework.security.authentication.LockedException;
import org.springframework.security.authentication.DisabledException;
import org.springframework.security.authentication.dao.DaoAuthenticationProvider;
import org.springframework.security.web.authentication.UsernamePasswordAuthenticationFilter;
import org.springframework.security.web.authentication.SimpleUrlAuthenticationSuccessHandler;
import com.kienhee.blog.security.LoginThrottleFilter;
import com.kienhee.blog.security.LoginAttemptService;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.security.authentication.AuthenticationManager;
import org.springframework.security.config.annotation.authentication.configuration.AuthenticationConfiguration;
import org.springframework.security.config.annotation.web.builders.HttpSecurity;
import org.springframework.security.config.annotation.method.configuration.EnableMethodSecurity;
import org.springframework.security.config.annotation.web.configuration.EnableWebSecurity;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.security.web.SecurityFilterChain;

@Configuration
@EnableWebSecurity
@EnableMethodSecurity
public class SecurityConfig {
    private static final String[] PUBLIC_MATCHERS = {
        "/",
        "/news",
        "/categories/**",
        "/category/**",
        "/author/**",
        "/about",
        "/article/**",
        "/search/**",
        "/subscribe/**",
        "/rss.xml",
        "/sitemap.xml",
        "/robots.txt",
        "/auth/**",
        // The language switcher: a visitor must be able to change language before signing in.
        "/lang",
        "/styles/**",
        "/scripts/**",
        "/images/**",
        "/media/**",
        "/favicon.ico",
        "/error",
        "/error/**"
    };
    @Bean
    public PasswordEncoder passwordEncoder() {
        return new BCryptPasswordEncoder();
    }

    /**
     * Form-login provider that checks the password <em>before</em> the account status. Spring's default checks
     * disabled/locked first, which would tell anyone typing an email that the account exists and is pending.
     */
    @Bean
    public DaoAuthenticationProvider authenticationProvider(UserDetailsService userDetailsService, PasswordEncoder passwordEncoder) {
        DaoAuthenticationProvider provider = new DaoAuthenticationProvider(userDetailsService);
        provider.setPasswordEncoder(passwordEncoder);
        AccountStatusUserDetailsChecker statusChecker = new AccountStatusUserDetailsChecker();
        provider.setPreAuthenticationChecks(user -> { });
        provider.setPostAuthenticationChecks(statusChecker::check);
        return provider;
    }

    @Bean
    public AuthenticationManager authenticationManager(AuthenticationConfiguration configuration) throws Exception {
        return configuration.getAuthenticationManager();
    }

    @Bean
    public SecurityFilterChain securityFilterChain(HttpSecurity http, LoginAttemptService loginAttemptService) throws Exception {
        SimpleUrlAuthenticationSuccessHandler toDashboard = new SimpleUrlAuthenticationSuccessHandler("/admin/dashboard");
        toDashboard.setAlwaysUseDefaultTargetUrl(true);

        http
            // Brute-force protection: blocked emails/IPs never reach the password check.
            .addFilterBefore(new LoginThrottleFilter(loginAttemptService), UsernamePasswordAuthenticationFilter.class)
            .authorizeHttpRequests(auth -> auth
                .requestMatchers(PUBLIC_MATCHERS).permitAll()
                .requestMatchers("/admin/**").authenticated()
                .anyRequest().authenticated()
            )
            .formLogin(form -> form
                .loginPage("/auth/login")
                .loginProcessingUrl("/auth/login")
                .usernameParameter("email")
                .passwordParameter("password")
                .successHandler((request, response, authentication) -> {
                    loginAttemptService.recordSuccess(authentication.getName());
                    toDashboard.onAuthenticationSuccess(request, response, authentication);
                })
                .failureHandler((request, response, exception) -> {
                    // Status failures happen only after a correct password, so they are not guesses.
                    String reason;
                    if (exception instanceof DisabledException) {
                        reason = "pending=true";
                    } else if (exception instanceof LockedException) {
                        reason = "disabled=true";
                    } else {
                        loginAttemptService.recordFailure(request.getParameter("email"), request.getRemoteAddr());
                        reason = "error=true";
                    }
                    response.sendRedirect(request.getContextPath() + "/auth/login?" + reason);
                })
                .permitAll()
            )
            .logout(logout -> logout
                .logoutUrl("/auth/logout")
                .logoutSuccessUrl("/auth/login?logout=true")
                .invalidateHttpSession(true)
                .deleteCookies("JSESSIONID")
                .permitAll()
            );

        return http.build();
    }
}

