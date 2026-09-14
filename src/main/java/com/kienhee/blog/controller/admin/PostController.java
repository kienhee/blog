package com.kienhee.blog.controller.admin;

import com.kienhee.blog.dto.PostCreateRequest;
import com.kienhee.blog.dto.PostUpdateRequest;
import com.kienhee.blog.entity.Post;
import com.kienhee.blog.service.CategoryService;
import com.kienhee.blog.service.HashtagService;
import com.kienhee.blog.service.PostService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import org.springframework.security.access.prepost.PreAuthorize;
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
                              Model model,
                              RedirectAttributes redirectAttributes) {
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
                              Model model,
                              RedirectAttributes redirectAttributes) {
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
            redirectAttributes.addFlashAttribute("successMessage", "Post deleted successfully.");
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
}
