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
public class PostUpdateRequest {

    @NotNull(message = "{validation.post.id_required}")
    private Long id;

    @NotBlank(message = "{validation.title.required}")
    @Size(min = 3, max = 200, message = "{validation.title.min}")
    private String title;

    @NotBlank(message = "{validation.slug.required}")
    @Size(min = 3, max = 220, message = "{validation.slug.min3}")
    @Pattern(regexp = "^[a-z0-9]+(-[a-z0-9]+)*$", message = "{validation.slug.pattern}")
    private String slug;

    @Size(max = 500, message = "{validation.excerpt.max}")
    private String excerpt;

    @NotBlank(message = "{validation.content.required}")
    private String content;

    @Size(max = 500, message = "{validation.cover.max}")
    private String coverImage;

    @NotNull(message = "{validation.status.required}")
    @Builder.Default
    private PostStatus status = PostStatus.DRAFT;

    @NotNull(message = "{validation.category.required}")
    private Long categoryId;

    @Builder.Default
    private Set<Long> hashtagIds = new LinkedHashSet<>();

    /** Required when status is SCHEDULED; the editor posts "yyyy-MM-dd HH:mm" (server time). */
    @DateTimeFormat(pattern = "yyyy-MM-dd HH:mm", fallbackPatterns = {"yyyy-MM-dd'T'HH:mm", "yyyy-MM-dd'T'HH:mm:ss"})
    private LocalDateTime scheduledAt;

    @Size(max = 60, message = "{validation.seo_title.max}")
    private String seoTitle;

    @Size(max = 160, message = "{validation.seo_description.max}")
    private String seoDescription;
}
