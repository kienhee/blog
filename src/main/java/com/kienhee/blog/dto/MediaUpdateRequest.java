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

    @NotNull(message = "{validation.media.id_required}")
    private Long id;

    @NotBlank(message = "{validation.display_name.required}")
    @Size(max = 255, message = "{validation.display_name.max}")
    private String displayName;

    @Size(max = 255, message = "{validation.alt.max}")
    private String altText;

    private Long folderId;
}
