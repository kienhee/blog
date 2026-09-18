package com.kienhee.blog.service.impl;

import com.kienhee.blog.exception.BusinessException;
import com.kienhee.blog.dto.RoleCreateRequest;
import com.kienhee.blog.entity.Permission;
import com.kienhee.blog.entity.Role;
import com.kienhee.blog.repository.PermissionRepository;
import com.kienhee.blog.repository.RoleRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.RoleService;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.HashMap;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Locale;
import java.util.Map;

@Service
@RequiredArgsConstructor
public class RoleServiceImpl implements RoleService {

    private final RoleRepository roleRepository;
    private final PermissionRepository permissionRepository;
    private final UserRepository userRepository;

    @Override
    @Transactional(readOnly = true)
    public List<Role> getAllRoles() {
        return roleRepository.findAllWithPermissions();
    }

    @Override
    @Transactional(readOnly = true)
    public Role getRoleWithPermissions(Long id) {
        return roleRepository.findWithPermissionsById(id)
                .orElseThrow(() -> new BusinessException("error.role.not_found", id));
    }

    @Override
    @Transactional(readOnly = true)
    public List<Permission> getAllPermissions() {
        return permissionRepository.findAllByOrderByResourceAscActionAsc();
    }

    @Override
    @Transactional(readOnly = true)
    public Map<Long, Long> getUserCountByRole() {
        Map<Long, Long> counts = new HashMap<>();
        for (Role role : roleRepository.findAll()) {
            counts.put(role.getId(), userRepository.countByRoleId(role.getId()));
        }
        return counts;
    }

    @Override
    @Transactional
    public Role createRole(RoleCreateRequest request) {
        String name = request.getName().trim();
        if (roleRepository.existsByNameIgnoreCase(name)) {
            throw new BusinessException("error.role.name_taken");
        }

        Role role = Role.builder()
                .name(name)
                .slug(uniqueSlug(name))
                .description(request.getDescription() != null && !request.getDescription().isBlank()
                        ? request.getDescription().trim() : null)
                .systemRole(false)
                .build();

        return roleRepository.save(role);
    }

    @Override
    @Transactional
    public Role renameRole(Long id, String name, String description) {
        if (name == null || name.trim().length() < 2) {
            throw new BusinessException("error.role.name_min");
        }
        Role role = roleRepository.findById(id)
                .orElseThrow(() -> new BusinessException("error.role.not_found", id));

        role.setName(name.trim());
        role.setDescription(description != null && !description.isBlank() ? description.trim() : null);
        return roleRepository.save(role);
    }

    @Override
    @Transactional
    public Role updatePermissions(Long roleId, List<Long> permissionIds) {
        Role role = roleRepository.findWithPermissionsById(roleId)
                .orElseThrow(() -> new BusinessException("error.role.not_found", roleId));

        if (role.isSystemRole()) {
            throw new BusinessException("error.role.system_locked", role.getName());
        }

        List<Permission> permissions = (permissionIds == null || permissionIds.isEmpty())
                ? List.of()
                : permissionRepository.findAllById(permissionIds);

        if (permissionIds != null && permissions.size() != permissionIds.size()) {
            throw new BusinessException("error.role.permission_missing");
        }

        role.getPermissions().clear();
        role.getPermissions().addAll(new LinkedHashSet<>(permissions));
        return roleRepository.save(role);
    }

    @Override
    @Transactional
    public void deleteRole(Long id) {
        Role role = roleRepository.findById(id)
                .orElseThrow(() -> new BusinessException("error.role.not_found", id));

        if (role.isSystemRole()) {
            throw new BusinessException("error.role.system_required", role.getName());
        }

        long inUse = userRepository.countByRoleId(id);
        if (inUse > 0) {
            throw new BusinessException("error.role.in_use", inUse);
        }

        roleRepository.delete(role);
    }

    private String uniqueSlug(String name) {
        String base = name.toLowerCase(Locale.ROOT)
                .replaceAll("[^a-z0-9]+", "-")
                .replaceAll("^-|-$", "");
        if (base.isBlank()) {
            base = "role";
        }
        String slug = base;
        int suffix = 2;
        while (roleRepository.existsBySlug(slug)) {
            slug = base + "-" + suffix;
            suffix++;
        }
        return slug;
    }
}
