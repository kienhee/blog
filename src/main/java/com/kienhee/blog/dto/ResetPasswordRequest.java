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

    @NotBlank(message = "{validation.reset.token}")
    private String token;

    /** BCrypt only uses the first 72 bytes, so longer passwords are refused rather than silently cut. */
    @NotBlank(message = "{validation.password.required}")
    @Size(min = 8, max = 72, message = "{validation.password.size8_72}")
    private String password;

    @NotBlank(message = "{validation.password.confirm_new}")
    private String confirmPassword;
}
