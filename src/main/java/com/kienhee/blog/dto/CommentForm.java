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

    @NotBlank(message = "Please enter your name.")
    @Size(max = 100, message = "Name must be at most 100 characters.")
    private String authorName;

    @NotBlank(message = "Please enter your email.")
    @Email(message = "Please enter a valid email.")
    @Size(max = 150, message = "Email must be at most 150 characters.")
    private String authorEmail;

    @NotBlank(message = "Please write a comment.")
    @Size(max = 2000, message = "Comments can be at most 2000 characters.")
    private String content;

    /** Comment being replied to, if any. */
    private Long parentId;

    /** Honeypot: hidden from people, filled in by bots. Must stay empty. */
    private String website;
}
