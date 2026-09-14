package com.kienhee.blog.controller;

import com.kienhee.blog.dto.ForgotPasswordRequest;
import com.kienhee.blog.dto.RegisterRequest;
import com.kienhee.blog.service.AuthService;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import org.springframework.security.core.Authentication;
import org.springframework.security.web.authentication.logout.SecurityContextLogoutHandler;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.validation.BindingResult;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

@Controller
@RequestMapping("/auth")
@RequiredArgsConstructor
public class AuthController {

    private final AuthService authService;

    @GetMapping("/login")
    public String login(
            @RequestParam(value = "error", required = false) String error,
            @RequestParam(value = "logout", required = false) String logout,
            @RequestParam(value = "registered", required = false) String registered,
            Model model
    ) {
        if (error != null) {
            model.addAttribute("errorMessage", "Invalid email or password.");
        }
        if (logout != null) {
            model.addAttribute("successMessage", "You have been signed out successfully.");
        }
        if (registered != null) {
            model.addAttribute("successMessage", "Account created successfully! Please sign in.");
        }
        return "admin/authentication/login";
    }

    @GetMapping("/register")
    public String register(Model model) {
        if (!model.containsAttribute("registerRequest")) {
            model.addAttribute("registerRequest", new RegisterRequest());
        }
        return "admin/authentication/register";
    }

    @PostMapping("/register")
    public String handleRegister(
            @Valid @ModelAttribute("registerRequest") RegisterRequest request,
            BindingResult bindingResult,
            Model model,
            RedirectAttributes redirectAttributes
    ) {
        if (bindingResult.hasErrors()) {
            model.addAttribute("errorMessage", bindingResult.getAllErrors().get(0).getDefaultMessage());
            return "admin/authentication/register";
        }

        try {
            authService.register(request);
            redirectAttributes.addAttribute("registered", "true");
            return "redirect:/auth/login";
        } catch (IllegalArgumentException ex) {
            model.addAttribute("errorMessage", ex.getMessage());
            return "admin/authentication/register";
        }
    }

    @GetMapping("/forgot")
    public String forgot(Model model) {
        if (!model.containsAttribute("forgotRequest")) {
            model.addAttribute("forgotRequest", new ForgotPasswordRequest());
        }
        return "admin/authentication/forgot";
    }

    @PostMapping("/forgot")
    public String handleForgot(
            @Valid @ModelAttribute("forgotRequest") ForgotPasswordRequest request,
            BindingResult bindingResult,
            Model model
    ) {
        if (bindingResult.hasErrors()) {
            model.addAttribute("errorMessage", bindingResult.getAllErrors().get(0).getDefaultMessage());
            return "admin/authentication/forgot";
        }

        boolean success = authService.resetPassword(request);
        if (success) {
            model.addAttribute("successMessage", "A reset link or new password has been sent to your email.");
        } else {
            model.addAttribute("errorMessage", "No account found with that email address.");
        }
        return "admin/authentication/forgot";
    }

    @GetMapping("/logout")
    public String logout(HttpServletRequest request, HttpServletResponse response, Authentication authentication) {
        if (authentication != null) {
            new SecurityContextLogoutHandler().logout(request, response, authentication);
        }
        return "redirect:/auth/login?logout=true";
    }
}
