package com.kienhee.blog.entity;

/** Whether an account may sign in. Only ACTIVE accounts can. */
public enum UserStatus {
    /** Signed up, waiting for an admin to approve the account. */
    PENDING,
    ACTIVE,
    /** Blocked by an admin; the account and its content are kept. */
    DISABLED
}
