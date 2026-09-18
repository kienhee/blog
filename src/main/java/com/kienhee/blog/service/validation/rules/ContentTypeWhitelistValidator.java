package com.kienhee.blog.service.validation.rules;

import com.kienhee.blog.exception.BusinessException;
import com.kienhee.blog.service.validation.FileValidator;
import com.kienhee.blog.service.validation.MediaTypeCatalog;
import com.kienhee.blog.service.validation.UploadValidationContext;
import org.springframework.stereotype.Component;

/**
 * Rule 200 — the declared content type must be on the whitelist (compared
 * lowercased, as before). Whitelist, never blacklist.
 *
 * <p>This only checks what the client <em>claims</em>; {@link MagicByteValidator}
 * (rule 300) is what actually verifies the bytes.
 */
@Component
public class ContentTypeWhitelistValidator implements FileValidator {

    @Override
    public int order() {
        return 200;
    }

    @Override
    public void validate(UploadValidationContext context) {
        if (!MediaTypeCatalog.isAllowed(context.getDeclaredContentType())) {
            throw new BusinessException("error.media.unsupported_type");
        }
    }
}
