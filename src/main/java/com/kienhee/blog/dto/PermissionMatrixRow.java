package com.kienhee.blog.dto;

import java.util.List;

/**
 * One row of the role permission grid: a resource plus its view/create/edit/delete cells.
 * Built in the controller so the template doesn't have to group anything itself.
 */
public record PermissionMatrixRow(String resource, String label, List<Cell> cells) {

    public record Cell(Long id, String action, boolean granted) {
    }
}
