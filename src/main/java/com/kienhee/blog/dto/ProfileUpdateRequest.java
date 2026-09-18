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

    @NotBlank(message = "{validation.display_name.required}")
    @Size(min = 2, max = 150, message = "{validation.display_name.min}")
    private String fullName;

    private String email;

    private String phone;

    private String address;

    private String bio;
}
