package com.kienhee.blog.dto;

import java.time.LocalDateTime;
import org.springframework.format.annotation.DateTimeFormat;
import com.kienhee.blog.entity.PostStatus;
import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import jakarta.validation.constraints.Pattern;
import jakarta.validation.constraints.Size;
import lombok.*;

import java.util.LinkedHashSet;
import java.util.Set;

@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class PostCreateRequest {

    @NotBlank(message = "Title is required.")
    @Size(min = 3, max = 200, message = "Title must have at least 3 characters.")
    private String title;

    @NotBlank(message = "Slug is required.")
    @Size(min = 3, max = 220, message = "Slug must have at least 3 characters.")
    @Pattern(regexp = "^[a-z0-9]+(-[a-z0-9]+)*$", message = "Slug may only contain lowercase letters, numbers and hyphens.")
    private String slug;

    @Size(max = 500, message = "Excerpt must not exceed 500 characters.")
    private String excerpt;

    @NotBlank(message = "Content is required.")
    private String content;

    @Size(max = 500, message = "Cover image URL must not exceed 500 characters.")
    private String coverImage;

    @NotNull(message = "Status is required.")
    @Builder.Default
    private PostStatus status = PostStatus.DRAFT;

    @NotNull(message = "Category is required.")
    private Long categoryId;

    @Builder.Default
    private Set<Long> hashtagIds = new LinkedHashSet<>();

    /** Required when status is SCHEDULED; the editor posts "yyyy-MM-dd HH:mm" (server time). */
    @DateTimeFormat(pattern = "yyyy-MM-dd HH:mm", fallbackPatterns = {"yyyy-MM-dd'T'HH:mm", "yyyy-MM-dd'T'HH:mm:ss"})
    private LocalDateTime scheduledAt;

    @Size(max = 60, message = "SEO title must not exceed 60 characters.")
    private String seoTitle;

    @Size(max = 160, message = "SEO description must not exceed 160 characters.")
    private String seoDescription;
}
