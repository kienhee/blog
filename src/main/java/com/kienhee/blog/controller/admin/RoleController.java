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
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.ModelAttribute;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
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
@PreAuthorize("hasAuthority('roles:view')")
public class RoleController {

    /** Module order on the Roles page (catalog: V1__Auth.sql). Unknown modules are appended. */
    private static final Map<String, String> RESOURCE_LABELS = new LinkedHashMap<>();
    static {
        RESOURCE_LABELS.put("dashboard", "Dashboard");
        RESOURCE_LABELS.put("posts", "Posts");
        RESOURCE_LABELS.put("categories", "Categories");
        RESOURCE_LABELS.put("hashtags", "Hashtags");
        RESOURCE_LABELS.put("media", "Media library");
        RESOURCE_LABELS.put("comments", "Comments");
        RESOURCE_LABELS.put("users", "Users");
        RESOURCE_LABELS.put("roles", "Roles");
        RESOURCE_LABELS.put("settings", "Settings");
        RESOURCE_LABELS.put("subscribers", "Newsletter");
    }

    /** Action order inside a module; unknown actions are appended. */
    private static final List<String> ACTION_ORDER = List.of("view", "create", "edit", "publish", "send", "delete", "purge");

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
    @PreAuthorize("hasAuthority('roles:create')")
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
    @PreAuthorize("hasAuthority('roles:edit')")
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
    @PreAuthorize("hasAuthority('roles:edit')")
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
    @PreAuthorize("hasAuthority('roles:delete')")
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
        Set<Long> granted = selected == null ? Set.of()
                : selected.getPermissions().stream().map(Permission::getId).collect(Collectors.toSet());

        Map<String, List<Permission>> byResource = new LinkedHashMap<>();
        RESOURCE_LABELS.keySet().forEach(resource -> byResource.put(resource, new ArrayList<>()));
        for (Permission p : roleService.getAllPermissions()) {
            byResource.computeIfAbsent(p.getResource(), k -> new ArrayList<>()).add(p);
        }

        List<PermissionMatrixRow> rows = new ArrayList<>();
        byResource.forEach((resource, permissions) -> {
            if (permissions.isEmpty()) {
                return;
            }
            permissions.sort((a, b) -> Integer.compare(actionRank(a.getAction()), actionRank(b.getAction())));
            List<PermissionMatrixRow.Cell> cells = permissions.stream()
                    // A system role (Admin) always shows everything ticked and locked.
                    .map(p -> new PermissionMatrixRow.Cell(p.getId(), p.getAction(), p.getCode(), p.getLabel(),
                            (selected != null && selected.isSystemRole()) || granted.contains(p.getId())))
                    .toList();
            String label = RESOURCE_LABELS.getOrDefault(resource,
                    resource.substring(0, 1).toUpperCase() + resource.substring(1));
            rows.add(new PermissionMatrixRow(resource, label, cells));
        });
        return rows;
    }

    private static int actionRank(String action) {
        int index = ACTION_ORDER.indexOf(action);
        return index < 0 ? ACTION_ORDER.size() : index;
    }
}
