package com.kienhee.blog.exception;

/**
 * A business-rule violation from the service layer, carrying a <b>message code</b> instead of a
 * sentence (duplicate slug, self-deletion, a folder still in use, ...).
 *
 * <p>It extends {@link IllegalArgumentException} on purpose: every controller in this app already
 * catches that, and the existing translation into field errors, flash messages and 422 responses
 * keeps working unchanged. What changes is where the words come from — the controller asks
 * {@code BusinessMessages} to resolve {@link #getCode()} for the reader's language, instead of
 * printing an English sentence the service baked in.</p>
 *
 * <p>{@link #getMessage()} returns the code, so logs and stack traces stay language-independent
 * and tests can assert on the code.</p>
 */
public class BusinessException extends IllegalArgumentException {

    private final String code;
    private final Object[] args;

    public BusinessException(String code, Object... args) {
        super(code);
        this.code = code;
        this.args = args == null ? new Object[0] : args;
    }

    public String getCode() {
        return code;
    }

    public Object[] getArgs() {
        return args.clone();
    }
}
