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
public class HashtagUpdateRequest {

    @NotNull(message = "{validation.hashtag.id_required}")
    private Long id;

    @NotBlank(message = "{validation.name.required}")
    @Size(min = 2, max = 100, message = "{validation.name.min}")
    private String name;

    @NotBlank(message = "{validation.slug.required}")
    @Size(min = 2, max = 120, message = "{validation.slug.min2}")
    @Pattern(regexp = "^[a-z0-9]+(-[a-z0-9]+)*$", message = "{validation.slug.pattern}")
    private String slug;

    @Size(max = 2000, message = "{validation.description.max2000}")
    private String description;

    @Builder.Default
    private boolean active = true;
}
