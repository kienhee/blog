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

    @NotNull(message = "User ID is required.")
    private Long id;

    @NotBlank(message = "Full name is required.")
    @Size(min = 2, max = 150, message = "Full name must have at least 2 characters.")
    private String fullName;

    private String email;

    @Size(min = 6, message = "New password must have at least 6 characters.")
    private String password;

    @Size(max = 20, message = "Phone must not exceed 20 characters.")
    private String phone;

    @Size(max = 255, message = "Address must not exceed 255 characters.")
    private String address;

    private String bio;

    private Long roleId;
}

