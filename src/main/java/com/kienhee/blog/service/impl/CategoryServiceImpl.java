package com.kienhee.blog.service.impl;

import com.kienhee.blog.dto.CategoryCreateRequest;
import com.kienhee.blog.dto.CategoryUpdateRequest;
import com.kienhee.blog.entity.Category;
import com.kienhee.blog.repository.CategoryRepository;
import com.kienhee.blog.service.CategoryService;
import lombok.RequiredArgsConstructor;
import org.springframework.data.domain.Sort;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;
import java.util.Optional;

@Service
@RequiredArgsConstructor
public class CategoryServiceImpl implements CategoryService {

    private final CategoryRepository categoryRepository;

    @Override
    @Transactional(readOnly = true)
    public List<Category> getAllCategories() {
        return categoryRepository.findAll(Sort.by(Sort.Direction.ASC, "name"));
    }

    @Override
    @Transactional(readOnly = true)
    public Optional<Category> getCategoryById(Long id) {
        return categoryRepository.findById(id);
    }

    @Override
    @Transactional
    public Category createCategory(CategoryCreateRequest request) {
        String slug = request.getSlug().trim().toLowerCase();
        if (categoryRepository.existsBySlug(slug)) {
            throw new IllegalArgumentException("Slug already in use: " + slug);
        }

        Category parent = resolveParent(request.getParentId(), null);

        Category category = Category.builder()
                .name(request.getName().trim())
                .slug(slug)
                .description(request.getDescription() != null && !request.getDescription().isBlank() ? request.getDescription().trim() : null)
                .parent(parent)
                .visible(request.isVisible())
                .build();

        return categoryRepository.save(category);
    }

    @Override
    @Transactional
    public Category updateCategory(Long id, CategoryUpdateRequest request) {
        Category category = categoryRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("Category not found with id: " + id));

        String slug = request.getSlug().trim().toLowerCase();
        if (categoryRepository.existsBySlugAndIdNot(slug, id)) {
            throw new IllegalArgumentException("Slug already in use: " + slug);
        }

        Category parent = resolveParent(request.getParentId(), category);

        category.setName(request.getName().trim());
        category.setSlug(slug);
        category.setDescription(request.getDescription() != null && !request.getDescription().isBlank() ? request.getDescription().trim() : null);
        category.setParent(parent);
        category.setVisible(request.isVisible());

        return categoryRepository.save(category);
    }

    @Override
    @Transactional
    public void deleteCategory(Long id) {
        Category category = categoryRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("Category not found with id: " + id));

        if (categoryRepository.existsByParentId(id)) {
            throw new IllegalArgumentException("Cannot delete a category that has subcategories.");
        }

        categoryRepository.delete(category);
    }

    @Override
    @Transactional(readOnly = true)
    public boolean existsBySlug(String slug) {
        if (slug == null) return false;
        return categoryRepository.existsBySlug(slug.trim().toLowerCase());
    }

    @Override
    @Transactional(readOnly = true)
    public boolean existsBySlugExcluding(String slug, Long id) {
        if (slug == null) return false;
        return categoryRepository.existsBySlugAndIdNot(slug.trim().toLowerCase(), id);
    }

    /**
     * Resolves and validates the requested parent, rejecting self-reference and cycles
     * (a category cannot be parented under one of its own descendants).
     */
    private Category resolveParent(Long parentId, Category self) {
        if (parentId == null) {
            return null;
        }
        if (self != null && parentId.equals(self.getId())) {
            throw new IllegalArgumentException("A category cannot be its own parent.");
        }

        Category parent = categoryRepository.findById(parentId)
                .orElseThrow(() -> new IllegalArgumentException("Parent category not found."));

        if (self != null) {
            Category ancestor = parent;
            while (ancestor != null) {
                if (ancestor.getId().equals(self.getId())) {
                    throw new IllegalArgumentException("Cannot set a descendant category as the parent.");
                }
                ancestor = ancestor.getParent();
            }
        }

        return parent;
    }
}
