package com.kienhee.blog.service.validation;

/**
 * One rule in the upload validation chain.
 *
 * <p>Adding a new rule means adding a new {@code @Component} implementing this
 * interface — {@link FileValidationChain} picks it up automatically and no
 * service code has to change.
 *
 * <p>Every rule signals a violation by throwing {@link IllegalArgumentException}
 * with a user-facing message, following the repo-wide convention (controllers
 * translate it into a {@code BindingResult} error or a flash message).
 */
public interface FileValidator {

    /**
     * Execution order, ascending. Reserved slots:
     * <ul>
     *   <li>100 — file size / emptiness ({@code FileSizeValidator})</li>
     *   <li>200 — declared content-type whitelist ({@code ContentTypeWhitelistValidator})</li>
     *   <li>300 — magic bytes ({@code MagicByteValidator})</li>
     *   <li>400 — <em>reserved</em> for the per-user storage quota rule</li>
     *   <li>500 — <em>reserved</em> for the virus scan rule</li>
     * </ul>
     * Leave gaps of 100 when inserting new rules so later rules can slot in between.
     */
    int order();

    /**
     * @throws IllegalArgumentException when the upload violates this rule
     */
    void validate(UploadValidationContext context);
}
