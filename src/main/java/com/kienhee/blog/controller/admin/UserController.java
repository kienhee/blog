package com.kienhee.blog.controller.admin;

import com.kienhee.blog.dto.UserCreateRequest;
import com.kienhee.blog.dto.UserUpdateRequest;
import com.kienhee.blog.entity.Role;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.service.RoleService;
import com.kienhee.blog.service.UserService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.ui.Model;
import org.springframework.validation.BindingResult;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.security.Principal;
import java.util.List;

@Controller
@RequestMapping("/admin/users")
@RequiredArgsConstructor
@PreAuthorize("hasAuthority('users:view')")
public class UserController {

    private final UserService userService;
    private final RoleService roleService;

    /** Available on every view this controller renders, including validation-error re-renders. */
    @ModelAttribute("roles")
    public List<Role> roles() {
        return roleService.getAllRoles();
    }

    @GetMapping
    public String listUsers(Model model) {
        List<User> users = userService.getAllUsers();
        model.addAttribute("users", users);
        if (!model.containsAttribute("userCreateRequest")) {
            model.addAttribute("userCreateRequest", new UserCreateRequest());
        }
        if (!model.containsAttribute("userUpdateRequest")) {
            model.addAttribute("userUpdateRequest", new UserUpdateRequest());
        }
        return "admin/user/users";
    }

    @PostMapping
    @PreAuthorize("hasAuthority('users:create')")
    public String createUser(@Valid @ModelAttribute("userCreateRequest") UserCreateRequest request,
                             BindingResult bindingResult,
                             Model model,
                             RedirectAttributes redirectAttributes) {
        if (!bindingResult.hasFieldErrors("email") && userService.existsByEmail(request.getEmail())) {
            bindingResult.rejectValue("email", "error.email", "Email already in use.");
        }

        if (bindingResult.hasErrors()) {
            model.addAttribute("users", userService.getAllUsers());
            if (!model.containsAttribute("userUpdateRequest")) {
                model.addAttribute("userUpdateRequest", new UserUpdateRequest());
            }
            model.addAttribute("openOffcanvas", "create");
            return "admin/user/users";
        }

        try {
            userService.createUser(request);
            redirectAttributes.addFlashAttribute("successMessage", "User created successfully.");
            return "redirect:/admin/users";
        } catch (IllegalArgumentException e) {
            if (e.getMessage() != null && e.getMessage().toLowerCase().contains("email")) {
                bindingResult.rejectValue("email", "error.email", e.getMessage());
            }
            bindingResult.reject("createError", e.getMessage());
            model.addAttribute("users", userService.getAllUsers());
            if (!model.containsAttribute("userUpdateRequest")) {
                model.addAttribute("userUpdateRequest", new UserUpdateRequest());
            }
            model.addAttribute("openOffcanvas", "create");
            return "admin/user/users";
        }
    }

    @PostMapping("/{id}/edit")
    @PreAuthorize("hasAuthority('users:edit')")
    public String updateUser(@PathVariable Long id,
                             @Valid @ModelAttribute("userUpdateRequest") UserUpdateRequest request,
                             BindingResult bindingResult,
                             Model model,
                             RedirectAttributes redirectAttributes) {
        if (bindingResult.hasErrors()) {
            model.addAttribute("users", userService.getAllUsers());
            if (!model.containsAttribute("userCreateRequest")) {
                model.addAttribute("userCreateRequest", new UserCreateRequest());
            }
            model.addAttribute("openOffcanvas", "edit");
            return "admin/user/users";
        }

        try {
            userService.updateUser(id, request);
            redirectAttributes.addFlashAttribute("successMessage", "User updated successfully.");
            return "redirect:/admin/users";
        } catch (IllegalArgumentException e) {
            bindingResult.reject("updateError", e.getMessage());
            model.addAttribute("users", userService.getAllUsers());
            if (!model.containsAttribute("userCreateRequest")) {
                model.addAttribute("userCreateRequest", new UserCreateRequest());
            }
            model.addAttribute("openOffcanvas", "edit");
            return "admin/user/users";
        }
    }

    @PostMapping("/{id}/delete")
    @PreAuthorize("hasAuthority('users:delete')")
    public String deleteUser(@PathVariable Long id,
                             Principal principal,
                             RedirectAttributes redirectAttributes) {
        String currentEmail = (principal != null) ? principal.getName() : null;
        try {
            userService.deleteUser(id, currentEmail);
            redirectAttributes.addFlashAttribute("successMessage", "User deleted successfully.");
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", e.getMessage());
        }
        return "redirect:/admin/users";
    }
}

