package com.kienhee.blog.controller.admin;

import com.kienhee.blog.controller.BusinessMessages;
import com.kienhee.blog.exception.BusinessException;
import com.kienhee.blog.dto.HashtagCreateRequest;
import com.kienhee.blog.dto.HashtagUpdateRequest;
import com.kienhee.blog.entity.Hashtag;
import com.kienhee.blog.service.HashtagService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.validation.BindingResult;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.util.List;

@Controller
@RequestMapping("/admin/hashtags")
@RequiredArgsConstructor
@PreAuthorize("hasAuthority('hashtags:view')")
public class HashtagController {

    private final HashtagService hashtagService;
    private final BusinessMessages messages;

    @GetMapping
    public String listHashtags(Model model) {
        List<Hashtag> hashtags = hashtagService.getAllHashtags();
        model.addAttribute("hashtags", hashtags);
        if (!model.containsAttribute("hashtagCreateRequest")) {
            model.addAttribute("hashtagCreateRequest", HashtagCreateRequest.builder().build());
        }
        if (!model.containsAttribute("hashtagUpdateRequest")) {
            model.addAttribute("hashtagUpdateRequest", HashtagUpdateRequest.builder().build());
        }
        return "admin/hashtag/hashtags";
    }

    @PostMapping
    @PreAuthorize("hasAuthority('hashtags:create')")
    public String createHashtag(@Valid @ModelAttribute("hashtagCreateRequest") HashtagCreateRequest request,
                                 BindingResult bindingResult,
                                 Model model,
                                 RedirectAttributes redirectAttributes) {
        if (!bindingResult.hasFieldErrors("slug") && hashtagService.existsBySlug(request.getSlug())) {
            bindingResult.rejectValue("slug", "error.slug", messages.get("msg.slug_taken"));
        }

        if (bindingResult.hasErrors()) {
            model.addAttribute("hashtags", hashtagService.getAllHashtags());
            if (!model.containsAttribute("hashtagUpdateRequest")) {
                model.addAttribute("hashtagUpdateRequest", HashtagUpdateRequest.builder().build());
            }
            model.addAttribute("openOffcanvas", "create");
            return "admin/hashtag/hashtags";
        }

        try {
            hashtagService.createHashtag(request);
            redirectAttributes.addFlashAttribute("successMessage", messages.get("msg.hashtag.created"));
            return "redirect:/admin/hashtags";
        } catch (IllegalArgumentException e) {
            applyServiceError(bindingResult, e);
            model.addAttribute("hashtags", hashtagService.getAllHashtags());
            if (!model.containsAttribute("hashtagUpdateRequest")) {
                model.addAttribute("hashtagUpdateRequest", HashtagUpdateRequest.builder().build());
            }
            model.addAttribute("openOffcanvas", "create");
            return "admin/hashtag/hashtags";
        }
    }

    @PostMapping("/{id}/edit")
    @PreAuthorize("hasAuthority('hashtags:edit')")
    public String updateHashtag(@PathVariable Long id,
                                 @Valid @ModelAttribute("hashtagUpdateRequest") HashtagUpdateRequest request,
                                 BindingResult bindingResult,
                                 Model model,
                                 RedirectAttributes redirectAttributes) {
        if (!bindingResult.hasFieldErrors("slug") && hashtagService.existsBySlugExcluding(request.getSlug(), id)) {
            bindingResult.rejectValue("slug", "error.slug", messages.get("msg.slug_taken"));
        }

        if (bindingResult.hasErrors()) {
            model.addAttribute("hashtags", hashtagService.getAllHashtags());
            if (!model.containsAttribute("hashtagCreateRequest")) {
                model.addAttribute("hashtagCreateRequest", HashtagCreateRequest.builder().build());
            }
            model.addAttribute("openOffcanvas", "edit");
            return "admin/hashtag/hashtags";
        }

        try {
            hashtagService.updateHashtag(id, request);
            redirectAttributes.addFlashAttribute("successMessage", messages.get("msg.hashtag.updated"));
            return "redirect:/admin/hashtags";
        } catch (IllegalArgumentException e) {
            applyServiceError(bindingResult, e);
            model.addAttribute("hashtags", hashtagService.getAllHashtags());
            if (!model.containsAttribute("hashtagCreateRequest")) {
                model.addAttribute("hashtagCreateRequest", HashtagCreateRequest.builder().build());
            }
            model.addAttribute("openOffcanvas", "edit");
            return "admin/hashtag/hashtags";
        }
    }

    @PostMapping("/{id}/delete")
    @PreAuthorize("hasAuthority('hashtags:delete')")
    public String deleteHashtag(@PathVariable Long id, RedirectAttributes redirectAttributes) {
        try {
            hashtagService.deleteHashtag(id);
            redirectAttributes.addFlashAttribute("successMessage", messages.get("msg.hashtag.trashed"));
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", messages.text(e));
        }
        return "redirect:/admin/hashtags";
    }

    private void applyServiceError(BindingResult bindingResult, IllegalArgumentException e) {
        String message = messages.text(e);
        // Which field to blame comes from the message code; the text itself is translated.
        String code = e instanceof BusinessException business ? business.getCode() : String.valueOf(message).toLowerCase();
        if (code.contains("slug")) {
            bindingResult.rejectValue("slug", "error.slug", message);
        } else {
            bindingResult.reject("hashtagError", message);
        }
    }

    @PostMapping("/bulk-delete")
    @PreAuthorize("hasAuthority('hashtags:delete')")
    public String bulkDelete(@org.springframework.web.bind.annotation.RequestParam(name = "ids", required = false) java.util.List<Long> ids,
                             RedirectAttributes redirectAttributes) {
        java.util.Map<Long, String> names = hashtagService.getAllHashtags().stream().collect(java.util.stream.Collectors.toMap(h -> h.getId(), h -> h.getName(), (a, b) -> a));
        BulkDelete.run(messages, ids, "bulk.noun.hashtags", names, hashtagService::deleteHashtag, redirectAttributes);
        return "redirect:/admin/hashtags";
    }
}
