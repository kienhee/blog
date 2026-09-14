package com.kienhee.blog.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import jakarta.validation.constraints.Pattern;
import jakarta.validation.constraints.Size;
import lombok.*;

@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class CategoryUpdateRequest {

    @NotNull(message = "Category ID is required.")
    private Long id;

    @NotBlank(message = "Name is required.")
    @Size(min = 2, max = 150, message = "Name must have at least 2 characters.")
    private String name;

    @NotBlank(message = "Slug is required.")
    @Size(min = 2, max = 170, message = "Slug must have at least 2 characters.")
    @Pattern(regexp = "^[a-z0-9]+(-[a-z0-9]+)*$", message = "Slug may only contain lowercase letters, numbers and hyphens.")
    private String slug;

    @Size(max = 2000, message = "Description must not exceed 2000 characters.")
    private String description;

    private Long parentId;

    @Builder.Default
    private boolean visible = true;
}
