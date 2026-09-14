package com.kienhee.blog.repository;

import com.kienhee.blog.entity.Comment;
import com.kienhee.blog.entity.CommentStatus;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Modifying;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import java.time.LocalDateTime;
import java.util.Collection;
import java.util.List;

public interface CommentRepository extends JpaRepository<Comment, Long> {

    /** Approved comments of a post, oldest first; the user is fetched for the "Author" tag. */
    @Query("select c from Comment c left join fetch c.user " +
            "where c.postId = :postId and c.status = com.kienhee.blog.entity.CommentStatus.APPROVED " +
            "order by c.createdAt asc, c.id asc")
    List<Comment> findApprovedByPost(@Param("postId") Long postId);

    /** Admin list: everything, newest first, with what the table renders. */
    @Query("select c from Comment c join fetch c.post left join fetch c.user order by c.createdAt desc, c.id desc")
    List<Comment> findAllForAdmin();

    List<Comment> findByPostIdOrderByIdAsc(Long postId);

    long countByStatus(CommentStatus status);

    @Modifying(clearAutomatically = true)
    @Query("update Comment c set c.status = :status, c.updatedAt = :now where c.id in :ids")
    int updateStatus(@Param("ids") Collection<Long> ids, @Param("status") CommentStatus status,
                     @Param("now") LocalDateTime now);

    /** Replies go with their parent through the FK's ON DELETE CASCADE. */
    @Modifying(clearAutomatically = true)
    @Query("delete from Comment c where c.id in :ids")
    int deleteByIds(@Param("ids") Collection<Long> ids);
}
