package com.kienhee.blog.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;
import lombok.*;

@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class MediaFolderCreateRequest {

    @NotBlank(message = "{validation.folder.name_required}")
    @Size(min = 2, max = 150, message = "{validation.folder.name_min}")
    private String name;

    /** Parent folder id; {@code null} means a root-level folder. */
    private Long parentId;
}
