package com.kienhee.blog.entity;

import jakarta.persistence.*;
import lombok.*;

@Entity
@Table(name = "permissions")
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class Permission {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "resource", nullable = false, length = 50)
    private String resource;

    @Column(name = "action", nullable = false, length = 20)
    private String action;

    /** Authority string exposed to Spring Security, e.g. "posts:create". */
    @Column(name = "code", nullable = false, unique = true, length = 80)
    private String code;

    @Column(name = "label", nullable = false, length = 120)
    private String label;
}
