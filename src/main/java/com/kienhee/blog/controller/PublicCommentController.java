package com.kienhee.blog.controller;

import com.kienhee.blog.dto.CommentForm;
import com.kienhee.blog.entity.Comment;
import com.kienhee.blog.entity.CommentStatus;
import com.kienhee.blog.entity.Post;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.CommentService;
import com.kienhee.blog.service.PublicBlogService;
import com.kienhee.blog.service.impl.CommentRateLimiter;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.validation.ConstraintViolation;
import jakarta.validation.Validator;
import lombok.RequiredArgsConstructor;
import org.springframework.http.HttpStatus;
import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.ModelAttribute;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.server.ResponseStatusException;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.security.Principal;
import java.util.Comparator;
import java.util.Set;

/** Public comment submission: POST /article/{slug}/comments, then back to the article's comment section. */
@Controller
@RequiredArgsConstructor
public class PublicCommentController {

    private final PublicBlogService blog;
    private final CommentService commentService;
    private final CommentRateLimiter rateLimiter;
    private final UserRepository userRepository;
    private final Validator validator;
    private final BusinessMessages messages;

    @PostMapping("/article/{slug}/comments")
    public String submit(@PathVariable String slug, @ModelAttribute CommentForm form, Principal principal,
                         HttpServletRequest request, RedirectAttributes redirect) {
        Post post = blog.article(slug).orElseThrow(() -> new ResponseStatusException(HttpStatus.NOT_FOUND));
        String back = "redirect:/article/" + post.getSlug() + "#comments";

        // Bots fill every field; pretend it worked so they learn nothing.
        if (form.getWebsite() != null && !form.getWebsite().isBlank()) {
            redirect.addFlashAttribute("commentSuccess", messages.get("msg.comment.thanks_pending"));
            return back;
        }

        User user = principal == null ? null : userRepository.findByEmail(principal.getName()).orElse(null);
        if (user != null) {
            form.setAuthorName(user.getFullName());
            form.setAuthorEmail(user.getEmail());
        }

        Set<ConstraintViolation<CommentForm>> violations = validator.validate(form);
        if (!violations.isEmpty()) {
            String message = violations.stream()
                    .sorted(Comparator.comparing(v -> v.getPropertyPath().toString()))
                    .map(ConstraintViolation::getMessage).findFirst().orElse(messages.get("error.comment.check"));
            return fail(redirect, form, message, back);
        }

        if (!rateLimiter.tryAcquire(request.getRemoteAddr())) {
            return fail(redirect, form, messages.get("error.comment.rate_limited"), back);
        }

        try {
            Comment saved = commentService.submit(post, form, user, request.getRemoteAddr(), request.getHeader("User-Agent"));
            redirect.addFlashAttribute("commentSuccess", saved.getStatus() == CommentStatus.APPROVED
                    ? messages.get("msg.comment.thanks_live")
                    : messages.get("msg.comment.thanks_pending"));
            return saved.getStatus() == CommentStatus.APPROVED
                    ? "redirect:/article/" + post.getSlug() + "#comment-" + saved.getId()
                    : back;
        } catch (IllegalArgumentException e) {
            return fail(redirect, form, messages.text(e), back);
        }
    }

    private static String fail(RedirectAttributes redirect, CommentForm form, String message, String back) {
        form.setWebsite(null);
        redirect.addFlashAttribute("commentError", message);
        redirect.addFlashAttribute("commentForm", form);
        return back;
    }
}
