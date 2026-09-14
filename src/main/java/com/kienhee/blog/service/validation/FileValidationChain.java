package com.kienhee.blog.service.validation;

import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Component;

import java.util.Comparator;
import java.util.List;

/**
 * Runs every registered {@link FileValidator} in ascending {@link FileValidator#order()}.
 *
 * <p>Fail-fast: the first rule that throws aborts the chain, so the message the
 * user sees is the same one the old inline checks in {@code MediaServiceImpl}
 * produced (size before declared type before content sniffing).
 */
@Slf4j
@Component
public class FileValidationChain {

    private final List<FileValidator> validators;

    public FileValidationChain(List<FileValidator> validators) {
        this.validators = validators.stream()
                .sorted(Comparator.comparingInt(FileValidator::order))
                .toList();
        log.debug("Upload validation chain: {}", this.validators.stream()
                .map(v -> v.getClass().getSimpleName() + "(" + v.order() + ")")
                .toList());
    }

    /**
     * @throws IllegalArgumentException on the first rule violated
     */
    public void validate(UploadValidationContext context) {
        for (FileValidator validator : validators) {
            validator.validate(context);
        }
    }

    /** Registered rules, in execution order. Exposed for tests and diagnostics. */
    public List<FileValidator> getValidators() {
        return validators;
    }
}
