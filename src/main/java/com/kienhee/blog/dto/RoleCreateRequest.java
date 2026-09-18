package com.kienhee.blog.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;
import lombok.*;

@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class RoleCreateRequest {

    @NotBlank(message = "{validation.role.name_required}")
    @Size(min = 2, max = 100, message = "{validation.role.name_min}")
    private String name;

    @Size(max = 255, message = "{validation.description.max255}")
    private String description;
}
