package com.kienhee.blog.dto;

import jakarta.validation.constraints.Email;
import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

/** Public comment form. For signed-in users, name and email are filled from the account before validation. */
@Getter
@Setter
@NoArgsConstructor
public class CommentForm {

    @NotBlank(message = "{validation.comment.name_required}")
    @Size(max = 100, message = "{validation.comment.name_max}")
    private String authorName;

    @NotBlank(message = "{validation.comment.email_required}")
    @Email(message = "{validation.comment.email_invalid}")
    @Size(max = 150, message = "{validation.comment.email_max}")
    private String authorEmail;

    @NotBlank(message = "{validation.comment.content_required}")
    @Size(max = 2000, message = "{validation.comment.content_max}")
    private String content;

    /** Comment being replied to, if any. */
    private Long parentId;

    /** Honeypot: hidden from people, filled in by bots. Must stay empty. */
    private String website;
}
