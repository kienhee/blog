package com.kienhee.blog.controller.admin;

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
            bindingResult.rejectValue("slug", "error.slug", "Slug already in use.");
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
            redirectAttributes.addFlashAttribute("successMessage", "Hashtag created successfully.");
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
            bindingResult.rejectValue("slug", "error.slug", "Slug already in use.");
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
            redirectAttributes.addFlashAttribute("successMessage", "Hashtag updated successfully.");
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
            redirectAttributes.addFlashAttribute("successMessage", "Hashtag deleted successfully.");
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", e.getMessage());
        }
        return "redirect:/admin/hashtags";
    }

    private void applyServiceError(BindingResult bindingResult, IllegalArgumentException e) {
        String message = e.getMessage();
        if (message != null && message.toLowerCase().contains("slug")) {
            bindingResult.rejectValue("slug", "error.slug", message);
        } else {
            bindingResult.reject("hashtagError", message);
        }
    }
}
