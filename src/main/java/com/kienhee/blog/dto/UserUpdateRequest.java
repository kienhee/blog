package com.kienhee.blog.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import jakarta.validation.constraints.Size;
import lombok.*;

@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class UserUpdateRequest {

    @NotNull(message = "{validation.user.id_required}")
    private Long id;

    @NotBlank(message = "{validation.full_name.required}")
    @Size(min = 2, max = 150, message = "{validation.full_name.min}")
    private String fullName;

    private String email;

    @Size(min = 6, message = "{validation.password.new_min6}")
    private String password;

    @Size(max = 20, message = "{validation.phone.max}")
    private String phone;

    @Size(max = 255, message = "{validation.address.max}")
    private String address;

    private String bio;

    private Long roleId;
}

