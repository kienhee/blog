package com.kienhee.blog.controller.admin;

import com.kienhee.blog.entity.NewsletterIssue;
import com.kienhee.blog.entity.Subscriber;
import com.kienhee.blog.entity.SubscriberStatus;
import com.kienhee.blog.service.NewsletterService;
import lombok.RequiredArgsConstructor;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.security.Principal;
import java.util.List;

/** Newsletter admin: subscribers, sending an issue, and past issues. */
@Controller
@RequestMapping("/admin/subscribers")
@RequiredArgsConstructor
@PreAuthorize("hasAuthority('subscribers:view')")
public class SubscriberController {

    private static final String REDIRECT = "redirect:/admin/subscribers";

    private final NewsletterService newsletterService;

    @GetMapping
    public String subscribers(Model model) {
        List<Subscriber> subscribers = newsletterService.allSubscribers();
        model.addAttribute("subscribers", subscribers);
        model.addAttribute("confirmedCount", subscribers.stream().filter(s -> s.getStatus() == SubscriberStatus.CONFIRMED).count());
        model.addAttribute("pendingCount", subscribers.stream().filter(s -> s.getStatus() == SubscriberStatus.PENDING).count());
        model.addAttribute("unsubscribedCount", subscribers.stream().filter(s -> s.getStatus() == SubscriberStatus.UNSUBSCRIBED).count());
        model.addAttribute("issues", newsletterService.issues());
        return "admin/subscriber/subscribers";
    }

    @PostMapping("/send")
    @PreAuthorize("hasAuthority('subscribers:send')")
    public String send(@RequestParam(value = "subject", required = false) String subject,
                       @RequestParam(value = "body", required = false) String body,
                       Principal principal, RedirectAttributes redirect) {
        try {
            NewsletterIssue issue = newsletterService.send(subject, body, principal != null ? principal.getName() : null);
            redirect.addFlashAttribute("successMessage", "Sending \"" + issue.getSubject() + "\" to "
                    + issue.getRecipients() + (issue.getRecipients() == 1 ? " subscriber." : " subscribers."));
        } catch (IllegalArgumentException e) {
            redirect.addFlashAttribute("errorMessage", e.getMessage());
            redirect.addFlashAttribute("draftSubject", subject);
            redirect.addFlashAttribute("draftBody", body);
        }
        return REDIRECT;
    }

    @PostMapping("/{id}/delete")
    @PreAuthorize("hasAuthority('subscribers:delete')")
    public String delete(@PathVariable Long id, RedirectAttributes redirect) {
        try {
            newsletterService.deleteSubscriber(id);
            redirect.addFlashAttribute("successMessage", "Subscriber removed.");
        } catch (IllegalArgumentException e) {
            redirect.addFlashAttribute("errorMessage", e.getMessage());
        }
        return REDIRECT;
    }
}
