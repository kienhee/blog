package com.kienhee.blog.repository;

import com.kienhee.blog.entity.Role;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import org.springframework.stereotype.Repository;

import java.util.List;
import java.util.Optional;

@Repository
public interface RoleRepository extends JpaRepository<Role, Long> {

    boolean existsBySlug(String slug);

    boolean existsByNameIgnoreCase(String name);

    Optional<Role> findBySlug(String slug);

    @Query("select distinct r from Role r left join fetch r.permissions order by r.name")
    List<Role> findAllWithPermissions();

    @Query("select distinct r from Role r left join fetch r.permissions where r.id = :id")
    Optional<Role> findWithPermissionsById(@Param("id") Long id);
}
