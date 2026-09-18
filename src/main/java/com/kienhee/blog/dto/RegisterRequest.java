package com.kienhee.blog.dto;

import jakarta.validation.constraints.Email;
import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;
import lombok.*;

@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class RegisterRequest {

    @NotBlank(message = "{validation.full_name.required_short}")
    @Size(min = 2, message = "{validation.full_name.min_short}")
    private String fullName;

    @NotBlank(message = "{validation.email.required_short}")
    @Email(message = "{validation.email.invalid_short}")
    private String email;

    @NotBlank(message = "{validation.password.required_short}")
    @Size(min = 6, message = "{validation.password.min6_short}")
    private String password;

    private String phone;
    private String address;
    private String bio;
}

