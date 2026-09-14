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

    @NotBlank(message = "Folder name is required.")
    @Size(min = 2, max = 150, message = "Folder name must have at least 2 characters.")
    private String name;

    /** Parent folder id; {@code null} means a root-level folder. */
    private Long parentId;
}
