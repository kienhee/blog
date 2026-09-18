package com.kienhee.blog.service.validation.rules;

import com.kienhee.blog.exception.BusinessException;
import com.kienhee.blog.service.validation.FileValidator;
import com.kienhee.blog.service.validation.MediaTypeCatalog;
import com.kienhee.blog.service.validation.UploadValidationContext;
import org.springframework.stereotype.Component;

/**
 * Rule 100 — rejects empty uploads and anything over the 10MB cap, before any
 * disk or network resource is spent. Behaviour and messages are identical to the
 * inline checks that used to live at the top of {@code MediaServiceImpl.uploadMedia}.
 */
@Component
public class FileSizeValidator implements FileValidator {

    @Override
    public int order() {
        return 100;
    }

    @Override
    public void validate(UploadValidationContext context) {
        if (context == null || context.getSizeBytes() <= 0) {
            throw new BusinessException("error.media.choose_file");
        }
        if (context.getSizeBytes() > MediaTypeCatalog.MAX_FILE_SIZE_BYTES) {
            throw new BusinessException("error.media.too_large");
        }
    }
}
