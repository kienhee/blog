package com.kienhee.blog.controller;

import com.kienhee.blog.entity.UserStatus;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.dto.ForgotPasswordRequest;
import com.kienhee.blog.dto.RegisterRequest;
import com.kienhee.blog.dto.ResetPasswordRequest;
import com.kienhee.blog.service.AuthService;
import com.kienhee.blog.service.PasswordResetService;
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

    /**
     * Shown for every forgot-password request, so the page never reveals which emails have accounts.
     * A message code: the words come from the catalogue for the visitor's language.
     */
    static final String RESET_REQUESTED_MESSAGE = "msg.auth.reset_sent";

    private final AuthService authService;
    private final PasswordResetService passwordResetService;
    private final BusinessMessages messages;

    @GetMapping("/login")
    public String login(
            @RequestParam(value = "error", required = false) String error,
            @RequestParam(value = "logout", required = false) String logout,
            @RequestParam(value = "registered", required = false) String registered,
            @RequestParam(value = "reset", required = false) String reset,
            @RequestParam(value = "locked", required = false) String locked,
            @RequestParam(value = "pending", required = false) String pending,
            @RequestParam(value = "disabled", required = false) String disabled,
            Model model
    ) {
        if (error != null) {
            model.addAttribute("errorMessage", messages.get("error.auth.bad_credentials"));
        }
        if (logout != null) {
            model.addAttribute("successMessage", messages.get("msg.auth.signed_out"));
        }
        if (registered != null) {
            model.addAttribute("successMessage", "pending".equals(registered)
                    ? messages.get("msg.auth.registered_pending")
                    : messages.get("msg.auth.registered"));
        }
        if (pending != null) {
            model.addAttribute("errorMessage", messages.get("error.auth.pending"));
        }
        if (disabled != null) {
            model.addAttribute("errorMessage", messages.get("error.auth.disabled"));
        }
        if (locked != null) {
            model.addAttribute("errorMessage", messages.get("error.auth.locked"));
        }
        if (reset != null) {
            model.addAttribute("successMessage", messages.get("msg.auth.password_changed"));
        }
        return "admin/authentication/login";
    }

    @GetMapping("/register")
    public String register(Model model) {
        model.addAttribute("firstAccount", authService.isFirstAccount());
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
            model.addAttribute("firstAccount", authService.isFirstAccount());
            model.addAttribute("errorMessage", bindingResult.getAllErrors().get(0).getDefaultMessage());
            return "admin/authentication/register";
        }

        try {
            User created = authService.register(request);
            redirectAttributes.addAttribute("registered", created.getStatus() == UserStatus.ACTIVE ? "true" : "pending");
            return "redirect:/auth/login";
        } catch (IllegalArgumentException ex) {
            model.addAttribute("errorMessage", messages.text(ex));
            model.addAttribute("firstAccount", authService.isFirstAccount());
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

    /** Emails a single-use reset link; the answer is the same whether or not the account exists. */
    @PostMapping("/forgot")
    public String handleForgot(
            @Valid @ModelAttribute("forgotRequest") ForgotPasswordRequest request,
            BindingResult bindingResult,
            Model model,
            HttpServletRequest httpRequest,
            RedirectAttributes redirectAttributes
    ) {
        if (bindingResult.hasErrors()) {
            model.addAttribute("errorMessage", bindingResult.getAllErrors().get(0).getDefaultMessage());
            return "admin/authentication/forgot";
        }
        passwordResetService.requestReset(request.getEmail(), httpRequest.getRemoteAddr());
        redirectAttributes.addFlashAttribute("successMessage", messages.get(RESET_REQUESTED_MESSAGE));
        return "redirect:/auth/forgot";
    }

    @GetMapping("/reset")
    public String reset(@RequestParam(value = "token", required = false) String token, Model model,
                        HttpServletResponse response) {
        // The token is in the URL: never leak it to other sites through the Referer header.
        response.setHeader("Referrer-Policy", "no-referrer");
        boolean valid = passwordResetService.findUserForToken(token).isPresent();
        model.addAttribute("tokenValid", valid);
        if (valid && !model.containsAttribute("resetRequest")) {
            ResetPasswordRequest form = new ResetPasswordRequest();
            form.setToken(token);
            model.addAttribute("resetRequest", form);
        }
        return "admin/authentication/reset";
    }

    @PostMapping("/reset")
    public String handleReset(
            @Valid @ModelAttribute("resetRequest") ResetPasswordRequest request,
            BindingResult bindingResult,
            Model model,
            HttpServletResponse response
    ) {
        response.setHeader("Referrer-Policy", "no-referrer");
        boolean valid = passwordResetService.findUserForToken(request.getToken()).isPresent();
        model.addAttribute("tokenValid", valid);
        if (!valid) {
            return "admin/authentication/reset";
        }
        if (bindingResult.hasErrors()) {
            model.addAttribute("errorMessage", bindingResult.getAllErrors().get(0).getDefaultMessage());
            return "admin/authentication/reset";
        }
        if (!request.getPassword().equals(request.getConfirmPassword())) {
            model.addAttribute("errorMessage", messages.get("error.auth.passwords_differ_two"));
            return "admin/authentication/reset";
        }
        try {
            passwordResetService.resetPassword(request.getToken(), request.getPassword());
        } catch (IllegalArgumentException ex) {
            model.addAttribute("tokenValid", false);
            return "admin/authentication/reset";
        }
        return "redirect:/auth/login?reset=true";
    }

    @GetMapping("/logout")
    public String logout(HttpServletRequest request, HttpServletResponse response, Authentication authentication) {
        if (authentication != null) {
            new SecurityContextLogoutHandler().logout(request, response, authentication);
        }
        return "redirect:/auth/login?logout=true";
    }
}
