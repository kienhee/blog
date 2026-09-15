package com.kienhee.blog.controller;

import com.kienhee.blog.service.NewsletterService;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.util.regex.Pattern;

/** Public newsletter flows: sign up (home page or /subscribe), confirm by email link, unsubscribe. */
@Controller
@RequiredArgsConstructor
public class NewsletterController {

    private static final Pattern EMAIL = Pattern.compile("^[^@\\s]+@[^@\\s]+\\.[^@\\s]+$");
    private static final String STATUS_VIEW = "public/newsletter-status";

    private final NewsletterService newsletterService;

    @PostMapping("/subscribe")
    public String subscribe(@RequestParam(value = "email", required = false) String email,
                            @RequestParam(value = "website", required = false) String website,
                            @RequestParam(value = "from", required = false) String from,
                            HttpServletRequest request, RedirectAttributes redirect) {
        String back = "home".equals(from) ? "redirect:/#newsletter" : "redirect:/subscribe";
        // Honeypot filled in: a bot. Answer like a success and do nothing.
        if (website != null && !website.isBlank()) {
            redirect.addFlashAttribute("newsletterSuccess", NewsletterService.SUBSCRIBE_MESSAGE);
            return back;
        }
        String value = email == null ? "" : email.trim();
        if (value.length() > 150 || !EMAIL.matcher(value).matches()) {
            redirect.addFlashAttribute("newsletterError", "Enter a valid email address.");
            redirect.addFlashAttribute("newsletterEmail", value);
            return back;
        }
        newsletterService.subscribe(value, request.getRemoteAddr());
        redirect.addFlashAttribute("newsletterSuccess", NewsletterService.SUBSCRIBE_MESSAGE);
        return back;
    }

    @GetMapping("/subscribe/confirm")
    public String confirm(@RequestParam(value = "token", required = false) String token, Model model, HttpServletResponse response) {
        response.setHeader("Referrer-Policy", "no-referrer");
        model.addAttribute("state", newsletterService.confirm(token) ? "confirmed" : "invalid");
        return STATUS_VIEW;
    }

    /** A page with a button (POST), so mail scanners that open links don't unsubscribe anyone. */
    @GetMapping("/subscribe/unsubscribe")
    public String unsubscribePage(@RequestParam(value = "token", required = false) String token, Model model,
                                  HttpServletResponse response) {
        response.setHeader("Referrer-Policy", "no-referrer");
        boolean known = newsletterService.findByUnsubscribeToken(token).isPresent();
        model.addAttribute("state", known ? "unsubscribe" : "invalid");
        model.addAttribute("token", token);
        return STATUS_VIEW;
    }

    @PostMapping("/subscribe/unsubscribe")
    public String unsubscribe(@RequestParam(value = "token", required = false) String token, Model model,
                              HttpServletResponse response) {
        response.setHeader("Referrer-Policy", "no-referrer");
        model.addAttribute("state", newsletterService.unsubscribe(token) ? "unsubscribed" : "invalid");
        return STATUS_VIEW;
    }
}
