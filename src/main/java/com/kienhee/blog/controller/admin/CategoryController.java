package com.kienhee.blog.controller.admin;

import com.kienhee.blog.controller.BusinessMessages;
import com.kienhee.blog.exception.BusinessException;
import com.kienhee.blog.dto.CategoryCreateRequest;
import com.kienhee.blog.dto.CategoryUpdateRequest;
import com.kienhee.blog.entity.Category;
import com.kienhee.blog.service.CategoryService;
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
@RequestMapping("/admin/categories")
@RequiredArgsConstructor
@PreAuthorize("hasAuthority('categories:view')")
public class CategoryController {

    private final CategoryService categoryService;
    private final BusinessMessages messages;

    @GetMapping
    public String listCategories(Model model) {
        List<Category> categories = categoryService.getAllCategories();
        model.addAttribute("categories", categories);
        if (!model.containsAttribute("categoryCreateRequest")) {
            model.addAttribute("categoryCreateRequest", CategoryCreateRequest.builder().build());
        }
        if (!model.containsAttribute("categoryUpdateRequest")) {
            model.addAttribute("categoryUpdateRequest", CategoryUpdateRequest.builder().build());
        }
        return "admin/category/categories";
    }

    @PostMapping
    @PreAuthorize("hasAuthority('categories:create')")
    public String createCategory(@Valid @ModelAttribute("categoryCreateRequest") CategoryCreateRequest request,
                                  BindingResult bindingResult,
                                  Model model,
                                  RedirectAttributes redirectAttributes) {
        if (!bindingResult.hasFieldErrors("slug") && categoryService.existsBySlug(request.getSlug())) {
            bindingResult.rejectValue("slug", "error.slug", messages.get("msg.slug_taken"));
        }

        if (bindingResult.hasErrors()) {
            model.addAttribute("categories", categoryService.getAllCategories());
            if (!model.containsAttribute("categoryUpdateRequest")) {
                model.addAttribute("categoryUpdateRequest", CategoryUpdateRequest.builder().build());
            }
            model.addAttribute("openOffcanvas", "create");
            return "admin/category/categories";
        }

        try {
            categoryService.createCategory(request);
            redirectAttributes.addFlashAttribute("successMessage", messages.get("msg.category.created"));
            return "redirect:/admin/categories";
        } catch (IllegalArgumentException e) {
            applyServiceError(bindingResult, e);
            model.addAttribute("categories", categoryService.getAllCategories());
            if (!model.containsAttribute("categoryUpdateRequest")) {
                model.addAttribute("categoryUpdateRequest", CategoryUpdateRequest.builder().build());
            }
            model.addAttribute("openOffcanvas", "create");
            return "admin/category/categories";
        }
    }

    @PostMapping("/{id}/edit")
    @PreAuthorize("hasAuthority('categories:edit')")
    public String updateCategory(@PathVariable Long id,
                                  @Valid @ModelAttribute("categoryUpdateRequest") CategoryUpdateRequest request,
                                  BindingResult bindingResult,
                                  Model model,
                                  RedirectAttributes redirectAttributes) {
        if (!bindingResult.hasFieldErrors("slug") && categoryService.existsBySlugExcluding(request.getSlug(), id)) {
            bindingResult.rejectValue("slug", "error.slug", messages.get("msg.slug_taken"));
        }

        if (bindingResult.hasErrors()) {
            model.addAttribute("categories", categoryService.getAllCategories());
            if (!model.containsAttribute("categoryCreateRequest")) {
                model.addAttribute("categoryCreateRequest", CategoryCreateRequest.builder().build());
            }
            model.addAttribute("openOffcanvas", "edit");
            return "admin/category/categories";
        }

        try {
            categoryService.updateCategory(id, request);
            redirectAttributes.addFlashAttribute("successMessage", messages.get("msg.category.updated"));
            return "redirect:/admin/categories";
        } catch (IllegalArgumentException e) {
            applyServiceError(bindingResult, e);
            model.addAttribute("categories", categoryService.getAllCategories());
            if (!model.containsAttribute("categoryCreateRequest")) {
                model.addAttribute("categoryCreateRequest", CategoryCreateRequest.builder().build());
            }
            model.addAttribute("openOffcanvas", "edit");
            return "admin/category/categories";
        }
    }

    @PostMapping("/{id}/delete")
    @PreAuthorize("hasAuthority('categories:delete')")
    public String deleteCategory(@PathVariable Long id, RedirectAttributes redirectAttributes) {
        try {
            categoryService.deleteCategory(id);
            redirectAttributes.addFlashAttribute("successMessage", messages.get("msg.category.trashed"));
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", messages.text(e));
        }
        return "redirect:/admin/categories";
    }

    private void applyServiceError(BindingResult bindingResult, IllegalArgumentException e) {
        String message = messages.text(e);
        // Which field to blame comes from the message code; the text itself is translated.
        String code = e instanceof BusinessException business ? business.getCode() : String.valueOf(message).toLowerCase();
        if (code.contains("slug")) {
            bindingResult.rejectValue("slug", "error.slug", message);
        } else if (code.contains("parent")) {
            bindingResult.rejectValue("parentId", "error.parentId", message);
        } else {
            bindingResult.reject("categoryError", message);
        }
    }

    @PostMapping("/bulk-delete")
    @PreAuthorize("hasAuthority('categories:delete')")
    public String bulkDelete(@org.springframework.web.bind.annotation.RequestParam(name = "ids", required = false) java.util.List<Long> ids,
                             RedirectAttributes redirectAttributes) {
        java.util.Map<Long, String> names = categoryService.getAllCategories().stream().collect(java.util.stream.Collectors.toMap(c -> c.getId(), c -> c.getName(), (a, b) -> a));
        BulkDelete.run(messages, ids, "bulk.noun.categories", names, categoryService::deleteCategory, redirectAttributes);
        return "redirect:/admin/categories";
    }
}
