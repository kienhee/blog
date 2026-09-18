package com.kienhee.blog.service.validation.rules;

import com.kienhee.blog.exception.BusinessException;
import com.kienhee.blog.service.validation.FileValidator;
import com.kienhee.blog.service.validation.MediaTypeCatalog;
import com.kienhee.blog.service.validation.UploadValidationContext;
import org.springframework.stereotype.Component;

/**
 * Rule 300 — the bytes must actually look like the declared type. This is the
 * check that stops a PNG (or a script) renamed to {@code .pdf} with a spoofed
 * {@code Content-Type}.
 *
 * <p>Ported one-for-one from {@code MediaServiceImpl.looksLikeDeclaredType}:
 * <ul>
 *   <li><b>image/webp</b> — RIFF container: bytes 0-3 = {@code RIFF}, bytes 8-11 = {@code WEBP}.</li>
 *   <li><b>image/svg+xml</b> — SVG is XML text with no fixed signature, so the first
 *       1000 bytes are sniffed (lowercased) for the substring {@code <svg}.</li>
 *   <li><b>text/plain</b> — no reliable signature exists; the declared type is trusted.</li>
 *   <li>everything else — prefix compare against {@link MediaTypeCatalog#MAGIC_BYTES};
 *       a type with no entry is trusted.</li>
 * </ul>
 * The rule needs {@link UploadValidationContext#getBytes()} to be populated; with no
 * bytes it behaves as it did on a zero-length read and rejects signature-bearing types.
 */
@Component
public class MagicByteValidator implements FileValidator {

    @Override
    public int order() {
        return 300;
    }

    @Override
    public void validate(UploadValidationContext context) {
        if (!looksLikeDeclaredType(context)) {
            throw new BusinessException("error.media.magic_mismatch");
        }
    }

    private boolean looksLikeDeclaredType(UploadValidationContext context) {
        String type = context.normalizedContentType();
        if (type == null) {
            // The whitelist rule already rejected this; nothing left to verify here.
            return true;
        }
        byte[] bytes = context.bytesOrEmpty();

        if ("image/webp".equals(type)) {
            return bytes.length >= 12
                    && bytes[0] == 'R' && bytes[1] == 'I' && bytes[2] == 'F' && bytes[3] == 'F'
                    && bytes[8] == 'W' && bytes[9] == 'E' && bytes[10] == 'B' && bytes[11] == 'P';
        }

        if ("image/svg+xml".equals(type)) {
            return context.headAsText(MediaTypeCatalog.SVG_SNIFF_LENGTH).contains("<svg");
        }

        if ("text/plain".equals(type)) {
            // No reliable magic bytes for plain text; trust the declared type.
            return true;
        }

        byte[] magic = MediaTypeCatalog.MAGIC_BYTES.get(type);
        if (magic == null) {
            return true;
        }
        if (bytes.length < magic.length) {
            return false;
        }
        for (int i = 0; i < magic.length; i++) {
            if (bytes[i] != magic[i]) {
                return false;
            }
        }
        return true;
    }
}
