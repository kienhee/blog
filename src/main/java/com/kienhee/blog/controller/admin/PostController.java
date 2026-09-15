package com.kienhee.blog.controller.admin;

import org.springframework.web.bind.annotation.ModelAttribute;
import com.kienhee.blog.entity.PostStatus;

import com.kienhee.blog.dto.PostCreateRequest;
import com.kienhee.blog.dto.PostUpdateRequest;
import com.kienhee.blog.entity.Post;
import com.kienhee.blog.service.CategoryService;
import com.kienhee.blog.service.HashtagService;
import com.kienhee.blog.service.PostService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.validation.BindingResult;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.security.Principal;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.stream.Collectors;

@Controller
@RequestMapping("/admin")
@RequiredArgsConstructor
@PreAuthorize("hasAuthority('posts:view')")
public class PostController {

    private final PostService postService;
    private final CategoryService categoryService;
    private final HashtagService hashtagService;

    @GetMapping("/posts")
    public String posts(Model model) {
        List<Post> posts = postService.getAllPosts();
        model.addAttribute("posts", posts);
        model.addAttribute("categories", categoryService.getAllCategories());
        return "admin/post/posts";
    }

    @GetMapping("/post/new")
    public String newPost(Model model) {
        if (!model.containsAttribute("postForm")) {
            model.addAttribute("postForm", PostCreateRequest.builder().build());
        }
        model.addAttribute("categories", categoryService.getAllCategories());
        model.addAttribute("hashtags", hashtagService.getAllHashtags());
        return "admin/post/post-new";
    }

    @PostMapping("/post/new")
    @PreAuthorize("hasAuthority('posts:create')")
    public String createPost(@Valid @ModelAttribute("postForm") PostCreateRequest request,
                              BindingResult bindingResult,
                              Principal principal,
                              Authentication authentication,
                              Model model,
                              RedirectAttributes redirectAttributes) {
        if (!canPublish(authentication)) {
            // Without posts:publish a new post is always saved as a draft.
            request.setStatus(PostStatus.DRAFT);
            request.setScheduledAt(null);
        }
        if (canPublish(authentication)) {
            validateSchedule(request.getStatus(), request.getScheduledAt(), null, bindingResult);
        }
        if (!bindingResult.hasFieldErrors("slug") && postService.existsBySlug(request.getSlug())) {
            bindingResult.rejectValue("slug", "error.slug", "Slug already in use.");
        }

        if (bindingResult.hasErrors()) {
            model.addAttribute("categories", categoryService.getAllCategories());
            model.addAttribute("hashtags", hashtagService.getAllHashtags());
            return "admin/post/post-new";
        }

        try {
            String authorEmail = principal != null ? principal.getName() : null;
            Post created = postService.createPost(request, authorEmail);
            redirectAttributes.addFlashAttribute("successMessage", "Post created successfully.");
            return "redirect:/admin/post/" + created.getId() + "/edit";
        } catch (IllegalArgumentException e) {
            applyServiceError(bindingResult, e);
            model.addAttribute("categories", categoryService.getAllCategories());
            model.addAttribute("hashtags", hashtagService.getAllHashtags());
            return "admin/post/post-new";
        }
    }

    @GetMapping("/post/{id}/edit")
    public String editPost(@PathVariable Long id, Model model, RedirectAttributes redirectAttributes) {
        return postService.getPostById(id)
                .map(post -> {
                    if (!model.containsAttribute("postForm")) {
                        model.addAttribute("postForm", PostUpdateRequest.builder()
                                .id(post.getId())
                                .title(post.getTitle())
                                .slug(post.getSlug())
                                .excerpt(post.getExcerpt())
                                .content(post.getContent())
                                .coverImage(post.getCoverImage())
                                .status(post.getStatus())
                                .categoryId(post.getCategory().getId())
                                .hashtagIds(post.getHashtags().stream().map(h -> h.getId()).collect(Collectors.toCollection(LinkedHashSet::new)))
                                .seoTitle(post.getSeoTitle())
                                .seoDescription(post.getSeoDescription())
                                .scheduledAt(post.getScheduledAt())
                                .build());
                    }
                    model.addAttribute("postId", post.getId());
                    model.addAttribute("categories", categoryService.getAllCategories());
                    model.addAttribute("hashtags", hashtagService.getAllHashtags());
                    return "admin/post/post-new";
                })
                .orElseGet(() -> {
                    redirectAttributes.addFlashAttribute("errorMessage", "Post not found.");
                    return "redirect:/admin/posts";
                });
    }

    @PostMapping("/post/{id}/edit")
    @PreAuthorize("hasAuthority('posts:edit')")
    public String updatePost(@PathVariable Long id,
                              @Valid @ModelAttribute("postForm") PostUpdateRequest request,
                              BindingResult bindingResult,
                              Authentication authentication,
                              Model model,
                              RedirectAttributes redirectAttributes) {
        if (!canPublish(authentication)) {
            // Without posts:publish the status can't change: publishing, scheduling and archiving need that permission.
            postService.getPostById(id).ifPresent(existing -> {
                request.setStatus(existing.getStatus());
                request.setScheduledAt(existing.getScheduledAt());
            });
        } else {
            java.time.LocalDateTime stored = postService.getPostById(id).map(Post::getScheduledAt).orElse(null);
            validateSchedule(request.getStatus(), request.getScheduledAt(), stored, bindingResult);
        }
        if (!bindingResult.hasFieldErrors("slug") && postService.existsBySlugExcluding(request.getSlug(), id)) {
            bindingResult.rejectValue("slug", "error.slug", "Slug already in use.");
        }

        if (bindingResult.hasErrors()) {
            model.addAttribute("postId", id);
            model.addAttribute("categories", categoryService.getAllCategories());
            model.addAttribute("hashtags", hashtagService.getAllHashtags());
            return "admin/post/post-new";
        }

        try {
            postService.updatePost(id, request);
            redirectAttributes.addFlashAttribute("successMessage", "Post updated successfully.");
            return "redirect:/admin/post/" + id + "/edit";
        } catch (IllegalArgumentException e) {
            applyServiceError(bindingResult, e);
            model.addAttribute("postId", id);
            model.addAttribute("categories", categoryService.getAllCategories());
            model.addAttribute("hashtags", hashtagService.getAllHashtags());
            return "admin/post/post-new";
        }
    }

    @PostMapping("/post/{id}/delete")
    @PreAuthorize("hasAuthority('posts:delete')")
    public String deletePost(@PathVariable Long id, RedirectAttributes redirectAttributes) {
        try {
            postService.deletePost(id);
            redirectAttributes.addFlashAttribute("successMessage", "Post moved to trash.");
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", e.getMessage());
        }
        return "redirect:/admin/posts";
    }

    private void applyServiceError(BindingResult bindingResult, IllegalArgumentException e) {
        String message = e.getMessage();
        if (message != null && message.toLowerCase().contains("slug")) {
            bindingResult.rejectValue("slug", "error.slug", message);
        } else if (message != null && message.toLowerCase().contains("category")) {
            bindingResult.rejectValue("categoryId", "error.categoryId", message);
        } else if (message != null && message.toLowerCase().contains("hashtag")) {
            bindingResult.reject("postError", message);
        } else {
            bindingResult.reject("postError", message);
        }
    }

    /** Publishing, scheduling and archiving need posts:publish; everyone else can only save drafts. */
    private static boolean canPublish(Authentication authentication) {
        return authentication != null && authentication.getAuthorities().stream()
                .anyMatch(a -> "posts:publish".equals(a.getAuthority()));
    }

    @PostMapping("/posts/bulk-delete")
    @PreAuthorize("hasAuthority('posts:delete')")
    public String bulkDelete(@org.springframework.web.bind.annotation.RequestParam(name = "ids", required = false) java.util.List<Long> ids,
                             RedirectAttributes redirectAttributes) {
        java.util.Map<Long, String> names = postService.getAllPosts().stream().collect(java.util.stream.Collectors.toMap(p -> p.getId(), p -> p.getTitle(), (a, b) -> a));
        BulkDelete.run(ids, "post", "posts", names, postService::deletePost, redirectAttributes);
        return "redirect:/admin/posts";
    }

    /** Server time zone, shown under the schedule picker so "14:00" is never ambiguous. */
    @ModelAttribute("serverZone")
    public String serverZone() {
        return java.time.ZoneId.systemDefault().getId();
    }

    /** SCHEDULED needs a time, and a new or changed time must be in the future. */
    private static void validateSchedule(PostStatus status, java.time.LocalDateTime scheduledAt,
                                         java.time.LocalDateTime stored, BindingResult bindingResult) {
        if (status != PostStatus.SCHEDULED) {
            return;
        }
        if (scheduledAt == null) {
            bindingResult.rejectValue("scheduledAt", "error.scheduledAt", "Choose when the post should go live.");
        } else if (!scheduledAt.equals(stored) && !scheduledAt.isAfter(java.time.LocalDateTime.now())) {
            bindingResult.rejectValue("scheduledAt", "error.scheduledAt", "The publish time must be in the future.");
        }
    }
}
