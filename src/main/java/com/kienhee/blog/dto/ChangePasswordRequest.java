package com.kienhee.blog.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;
import lombok.*;

@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class ChangePasswordRequest {

    @NotBlank(message = "{validation.password.current_required}")
    private String currentPassword;

    @NotBlank(message = "{validation.password.new_required}")
    @Size(min = 6, message = "{validation.password.new_min6}")
    private String newPassword;

    @NotBlank(message = "{validation.password.confirm_required}")
    private String confirmPassword;
}
