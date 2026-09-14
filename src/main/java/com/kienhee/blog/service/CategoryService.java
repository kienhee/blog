package com.kienhee.blog.service;

import com.kienhee.blog.dto.CategoryCreateRequest;
import com.kienhee.blog.dto.CategoryUpdateRequest;
import com.kienhee.blog.entity.Category;

import java.util.List;
import java.util.Optional;

public interface CategoryService {

    List<Category> getAllCategories();

    Optional<Category> getCategoryById(Long id);

    Category createCategory(CategoryCreateRequest request);

    Category updateCategory(Long id, CategoryUpdateRequest request);

    void deleteCategory(Long id);

    boolean existsBySlug(String slug);

    boolean existsBySlugExcluding(String slug, Long id);
}
