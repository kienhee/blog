package com.kienhee.blog.controller.admin;

import com.kienhee.blog.dto.PermissionMatrixRow;
import com.kienhee.blog.dto.RoleCreateRequest;
import com.kienhee.blog.entity.Permission;
import com.kienhee.blog.entity.Role;
import com.kienhee.blog.service.RoleService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.validation.BindingResult;
import org.springframework.validation.ObjectError;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.stream.Collectors;

@Controller
@RequestMapping("/admin/roles")
@RequiredArgsConstructor
@PreAuthorize("hasAuthority('users:view')")
public class RoleController {

    /** Display order of the grid, matching the resource list the UI was designed around. */
    private static final List<String> RESOURCE_ORDER =
            List.of("posts", "categories", "hashtags", "media", "comments", "users", "settings");
    private static final List<String> ACTION_ORDER = List.of("view", "create", "edit", "delete");

    private final RoleService roleService;

    @GetMapping
    public String roles(@RequestParam(value = "roleId", required = false) Long roleId, Model model) {
        List<Role> roles = roleService.getAllRoles();
        model.addAttribute("roles", roles);
        model.addAttribute("userCounts", roleService.getUserCountByRole());

        Role selected = roles.stream()
                .filter(r -> roleId == null || r.getId().equals(roleId))
                .findFirst()
                .orElse(null);

        model.addAttribute("selectedRole", selected);
        model.addAttribute("permissionMatrix", buildMatrix(selected));
        if (!model.containsAttribute("roleCreateRequest")) {
            model.addAttribute("roleCreateRequest", RoleCreateRequest.builder().build());
        }
        return "admin/role/roles";
    }

    @PostMapping
    @PreAuthorize("hasAuthority('users:create')")
    public String createRole(@Valid @ModelAttribute("roleCreateRequest") RoleCreateRequest request,
                              BindingResult bindingResult,
                              RedirectAttributes redirectAttributes) {
        if (bindingResult.hasErrors()) {
            redirectAttributes.addFlashAttribute("errorMessage", bindingResult.getAllErrors().stream()
                    .map(ObjectError::getDefaultMessage).collect(Collectors.joining(" ")));
            return "redirect:/admin/roles";
        }
        try {
            Role created = roleService.createRole(request);
            redirectAttributes.addFlashAttribute("successMessage", "Role created.");
            return "redirect:/admin/roles?roleId=" + created.getId();
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", e.getMessage());
            return "redirect:/admin/roles";
        }
    }

    @PostMapping("/{id}/edit")
    @PreAuthorize("hasAuthority('users:edit')")
    public String renameRole(@PathVariable Long id,
                              @RequestParam String name,
                              @RequestParam(required = false) String description,
                              RedirectAttributes redirectAttributes) {
        try {
            roleService.renameRole(id, name, description);
            redirectAttributes.addFlashAttribute("successMessage", "Role updated.");
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", e.getMessage());
        }
        return "redirect:/admin/roles?roleId=" + id;
    }

    @PostMapping("/{id}/permissions")
    @PreAuthorize("hasAuthority('users:edit')")
    public String savePermissions(@PathVariable Long id,
                                   @RequestParam(value = "permissionIds", required = false) List<Long> permissionIds,
                                   RedirectAttributes redirectAttributes) {
        try {
            roleService.updatePermissions(id, permissionIds);
            redirectAttributes.addFlashAttribute("successMessage", "Permissions saved.");
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", e.getMessage());
        }
        return "redirect:/admin/roles?roleId=" + id;
    }

    @PostMapping("/{id}/delete")
    @PreAuthorize("hasAuthority('users:delete')")
    public String deleteRole(@PathVariable Long id, RedirectAttributes redirectAttributes) {
        try {
            roleService.deleteRole(id);
            redirectAttributes.addFlashAttribute("successMessage", "Role deleted.");
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", e.getMessage());
        }
        return "redirect:/admin/roles";
    }

    private List<PermissionMatrixRow> buildMatrix(Role selected) {
        List<Permission> all = roleService.getAllPermissions();
        Set<Long> granted = selected == null ? Set.of()
                : selected.getPermissions().stream().map(Permission::getId).collect(Collectors.toSet());

        Map<String, Map<String, Permission>> byResource = new LinkedHashMap<>();
        for (Permission p : all) {
            byResource.computeIfAbsent(p.getResource(), k -> new LinkedHashMap<>()).put(p.getAction(), p);
        }

        List<PermissionMatrixRow> rows = new ArrayList<>();
        for (String resource : RESOURCE_ORDER) {
            Map<String, Permission> actions = byResource.get(resource);
            if (actions == null) continue;

            List<PermissionMatrixRow.Cell> cells = new ArrayList<>();
            for (String action : ACTION_ORDER) {
                Permission p = actions.get(action);
                if (p == null) continue;
                // A system role (Owner) always shows everything ticked and locked.
                boolean isGranted = (selected != null && selected.isSystemRole()) || granted.contains(p.getId());
                cells.add(new PermissionMatrixRow.Cell(p.getId(), action, isGranted));
            }
            rows.add(new PermissionMatrixRow(resource, capitalize(resource), cells));
        }
        return rows;
    }

    private String capitalize(String s) {
        return s.substring(0, 1).toUpperCase() + s.substring(1);
    }
}
