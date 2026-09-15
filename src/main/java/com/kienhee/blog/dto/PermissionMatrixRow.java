package com.kienhee.blog.dto;

import java.util.List;

/**
 * One module on the Roles page with the permissions it has. Modules have different actions
 * (posts can be published, media purged, settings only viewed/edited), so each cell carries its own label.
 */
public record PermissionMatrixRow(String resource, String label, List<Cell> cells) {

    public record Cell(Long id, String action, String code, String label, boolean granted) {
    }
}
