package com.kienhee.blog.controller;

import com.kienhee.blog.exception.BusinessException;
import lombok.RequiredArgsConstructor;
import org.springframework.context.MessageSource;
import org.springframework.context.NoSuchMessageException;
import org.springframework.context.i18n.LocaleContextHolder;
import org.springframework.stereotype.Component;

/**
 * Turns a service-layer failure into words for the current request's language.
 *
 * <p>Services never touch HTTP or locales: they throw {@link BusinessException} with a message
 * code. Controllers pass the exception here before putting it in a {@code BindingResult}, a flash
 * attribute or a 422 body.</p>
 *
 * <p>An {@link IllegalArgumentException} that is not a {@code BusinessException} yet — the modules
 * still being migrated — keeps its own text, so both styles can coexist during the move.</p>
 */
@Component
@RequiredArgsConstructor
public class BusinessMessages {

    private final MessageSource messageSource;

    /** The message to show for this failure, in the language of the current request. */
    public String text(RuntimeException failure) {
        if (failure instanceof BusinessException business) {
            return resolve(business.getCode(), business.getArgs());
        }
        String message = failure.getMessage();
        return message == null ? "" : message;
    }

    /** Resolve a code directly, for controllers that raise their own message. */
    public String get(String code, Object... args) {
        return resolve(code, args);
    }

    private String resolve(String code, Object[] args) {
        try {
            return messageSource.getMessage(code, args, LocaleContextHolder.getLocale());
        } catch (NoSuchMessageException e) {
            // Better a visible code than a blank message: MessageUsageTests is what keeps this empty.
            return code;
        }
    }
}
