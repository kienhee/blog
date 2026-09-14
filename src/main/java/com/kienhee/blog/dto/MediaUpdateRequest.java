package com.kienhee.blog.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import jakarta.validation.constraints.Size;
import lombok.*;

@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class MediaUpdateRequest {

    @NotNull(message = "Media ID is required.")
    private Long id;

    @NotBlank(message = "Display name is required.")
    @Size(max = 255, message = "Display name must not exceed 255 characters.")
    private String displayName;

    @Size(max = 255, message = "Alt text must not exceed 255 characters.")
    private String altText;

    private Long folderId;
}
