package com.kienhee.blog.repository;

import com.kienhee.blog.entity.Category;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.util.List;
import java.util.Optional;

@Repository
public interface CategoryRepository extends JpaRepository<Category, Long> {

    boolean existsBySlug(String slug);

    boolean existsBySlugAndIdNot(String slug, Long id);

    boolean existsByParentId(Long parentId);

    Optional<Category> findBySlugAndVisibleTrue(String slug);

    List<Category> findByVisibleTrueOrderByNameAsc();
}
