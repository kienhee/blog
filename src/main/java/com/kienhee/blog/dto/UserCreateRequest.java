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
public class UserCreateRequest {

    @NotBlank(message = "{validation.full_name.required}")
    @Size(min = 2, max = 150, message = "{validation.full_name.min}")
    private String fullName;

    @NotBlank(message = "{validation.email.required}")
    @Email(message = "{validation.email.invalid}")
    private String email;

    @NotBlank(message = "{validation.password.required}")
    @Size(min = 6, message = "{validation.password.min6}")
    private String password;

    @Size(max = 20, message = "{validation.phone.max}")
    private String phone;

    @Size(max = 255, message = "{validation.address.max}")
    private String address;

    private String bio;

    private Long roleId;
}

