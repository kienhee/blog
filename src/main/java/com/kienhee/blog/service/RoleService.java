package com.kienhee.blog.service;

import com.kienhee.blog.dto.RoleCreateRequest;
import com.kienhee.blog.entity.Permission;
import com.kienhee.blog.entity.Role;

import java.util.List;
import java.util.Map;

public interface RoleService {

    List<Role> getAllRoles();

    Role getRoleWithPermissions(Long id);

    List<Permission> getAllPermissions();

    /** How many users are assigned to each role id — shown next to the role name. */
    Map<Long, Long> getUserCountByRole();

    Role createRole(RoleCreateRequest request);

    Role renameRole(Long id, String name, String description);

    /** Replaces the role's whole permission set with exactly the given permission ids. */
    Role updatePermissions(Long roleId, List<Long> permissionIds);

    void deleteRole(Long id);
}
