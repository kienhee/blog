package com.kienhee.blog.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

/** The form behind an emailed reset link. */
@Getter
@Setter
@NoArgsConstructor
public class ResetPasswordRequest {

    @NotBlank(message = "This reset link is invalid or has expired. Request a new one.")
    private String token;

    /** BCrypt only uses the first 72 bytes, so longer passwords are refused rather than silently cut. */
    @NotBlank(message = "Password is required.")
    @Size(min = 8, max = 72, message = "Password must be 8 to 72 characters.")
    private String password;

    @NotBlank(message = "Please confirm the new password.")
    private String confirmPassword;
}
