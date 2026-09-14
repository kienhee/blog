package com.kienhee.blog.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;
import lombok.*;

@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class ProfileUpdateRequest {

    @NotBlank(message = "Display name is required.")
    @Size(min = 2, max = 150, message = "Display name must have at least 2 characters.")
    private String fullName;

    private String email;

    private String phone;

    private String address;

    private String bio;
}
