package com.kienhee.blog.controller.admin;

import com.kienhee.blog.controller.BusinessMessages;
import com.kienhee.blog.entity.CommentStatus;
import com.kienhee.blog.service.CommentService;
import lombok.RequiredArgsConstructor;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.util.List;

/** Comment moderation: approve / mark as spam / back to pending, and delete — single or bulk. */
@Controller
@RequestMapping("/admin/comments")
@RequiredArgsConstructor
@PreAuthorize("hasAuthority('comments:view')")
public class CommentController {

    private static final String REDIRECT = "redirect:/admin/comments";

    private final CommentService commentService;
    private final BusinessMessages messages;

    @GetMapping
    public String comments(Model model) {
        model.addAttribute("comments", commentService.allForAdmin());
        model.addAttribute("pendingCount", commentService.countByStatus(CommentStatus.PENDING));
        return "admin/comment/comments";
    }

    @PostMapping("/{id}/status")
    @PreAuthorize("hasAuthority('comments:edit')")
    public String updateStatus(@PathVariable Long id, @RequestParam String status, RedirectAttributes redirect) {
        return changeStatus(List.of(id), status, redirect);
    }

    @PostMapping("/bulk-status")
    @PreAuthorize("hasAuthority('comments:edit')")
    public String bulkStatus(@RequestParam(name = "ids", required = false) List<Long> ids, @RequestParam String status,
                             RedirectAttributes redirect) {
        return changeStatus(ids, status, redirect);
    }

    @PostMapping("/{id}/delete")
    @PreAuthorize("hasAuthority('comments:delete')")
    public String delete(@PathVariable Long id, RedirectAttributes redirect) {
        return remove(List.of(id), redirect);
    }

    @PostMapping("/bulk-delete")
    @PreAuthorize("hasAuthority('comments:delete')")
    public String bulkDelete(@RequestParam(name = "ids", required = false) List<Long> ids, RedirectAttributes redirect) {
        return remove(ids, redirect);
    }

    private String changeStatus(List<Long> ids, String status, RedirectAttributes redirect) {
        try {
            CommentStatus target = CommentService.parseStatus(status);
            int n = commentService.updateStatus(ids, target);
            redirect.addFlashAttribute("successMessage", messages.get("msg.comment.marked",
                    messages.get("bulk.noun.comments" + (n == 1 ? ".one" : ".other"), n),
                    messages.get("enum.comment.status." + target.name())));
        } catch (IllegalArgumentException e) {
            redirect.addFlashAttribute("errorMessage", messages.text(e));
        }
        return REDIRECT;
    }

    private String remove(List<Long> ids, RedirectAttributes redirect) {
        try {
            int n = commentService.delete(ids);
            redirect.addFlashAttribute("successMessage", messages.get("bulk.verb.trashed",
                    messages.get("bulk.noun.comments" + (n == 1 ? ".one" : ".other"), n)));
        } catch (IllegalArgumentException e) {
            redirect.addFlashAttribute("errorMessage", messages.text(e));
        }
        return REDIRECT;
    }

    @PostMapping("/{id}/reply")
    @PreAuthorize("hasAuthority('comments:edit')")
    public String reply(@PathVariable Long id, @RequestParam(value = "content", required = false) String content,
                        java.security.Principal principal, RedirectAttributes redirect) {
        try {
            commentService.replyAsStaff(id, content, principal != null ? principal.getName() : null);
            redirect.addFlashAttribute("successMessage", messages.get("msg.comment.reply_posted"));
        } catch (IllegalArgumentException e) {
            redirect.addFlashAttribute("errorMessage", messages.text(e));
        }
        return REDIRECT;
    }
}
