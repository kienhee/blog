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
                             org.springframework.security.core.Authentication authentication,
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
            userService.createUser(request, actorIsAdmin(authentication));
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
                             org.springframework.security.core.Authentication authentication,
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
            userService.updateUser(id, request, actorIsAdmin(authentication));
            redirectAttributes.addFlashAttribute("successMessage", "User updated successfully.");
            return "redirect:/admin/users";
        } catch (IllegalArgumentException e) {
            bindingResult.reject("updateError", e.getMessage());
            // The page's form only renders the create form's global errors; show service refusals in the banner.
            model.addAttribute("errorMessage", e.getMessage());
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
                             org.springframework.security.core.Authentication authentication,
                             RedirectAttributes redirectAttributes) {
        String currentEmail = (principal != null) ? principal.getName() : null;
        try {
            userService.deleteUser(id, currentEmail, actorIsAdmin(authentication));
            redirectAttributes.addFlashAttribute("successMessage", "User deleted successfully.");
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", e.getMessage());
        }
        return "redirect:/admin/users";
    }

    @PostMapping("/bulk-delete")
    @PreAuthorize("hasAuthority('users:delete')")
    public String bulkDelete(@org.springframework.web.bind.annotation.RequestParam(name = "ids", required = false) java.util.List<Long> ids,
                             Principal principal,
                             org.springframework.security.core.Authentication authentication,
                             RedirectAttributes redirectAttributes) {
        String currentEmail = principal != null ? principal.getName() : null;
        java.util.Map<Long, String> names = userService.getAllUsers().stream()
                .collect(java.util.stream.Collectors.toMap(User::getId, User::getFullName, (a, b) -> a));
        BulkDelete.run(ids, "user", "users", "deleted", names,
                id -> userService.deleteUser(id, currentEmail, actorIsAdmin(authentication)), redirectAttributes);
        return "redirect:/admin/users";
    }

    /** Admin rights = can reshape roles (roles:edit). Only they may grant the Admin role or touch admin accounts. */
    private static boolean actorIsAdmin(org.springframework.security.core.Authentication authentication) {
        return authentication != null && authentication.getAuthorities().stream()
                .anyMatch(a -> "roles:edit".equals(a.getAuthority()));
    }

    @PostMapping("/{id}/status")
    @PreAuthorize("hasAuthority('users:edit')")
    public String changeStatus(@PathVariable Long id,
                               @org.springframework.web.bind.annotation.RequestParam(value = "status", required = false) String status,
                               Principal principal,
                               org.springframework.security.core.Authentication authentication,
                               RedirectAttributes redirectAttributes) {
        try {
            com.kienhee.blog.entity.UserStatus target;
            try {
                target = com.kienhee.blog.entity.UserStatus.valueOf(status == null ? "" : status.trim().toUpperCase());
            } catch (IllegalArgumentException e) {
                throw new IllegalArgumentException("Unknown account status.");
            }
            com.kienhee.blog.entity.UserStatus before = userService.getUserById(id).map(User::getStatus).orElse(null);
            userService.changeStatus(id, target, principal != null ? principal.getName() : null, actorIsAdmin(authentication));
            String message = switch (target) {
                case ACTIVE -> before == com.kienhee.blog.entity.UserStatus.PENDING ? "Account approved." : "Account enabled.";
                case DISABLED -> "Account disabled.";
                case PENDING -> "Account set back to pending.";
            };
            redirectAttributes.addFlashAttribute("successMessage", message);
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", e.getMessage());
        }
        return "redirect:/admin/users";
    }
}
