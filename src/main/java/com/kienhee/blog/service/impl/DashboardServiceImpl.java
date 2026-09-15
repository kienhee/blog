package com.kienhee.blog.service.impl;

import com.kienhee.blog.entity.UserStatus;
import java.time.LocalDate;
import com.kienhee.blog.service.PageViewService;
import com.kienhee.blog.entity.Comment;
import com.kienhee.blog.entity.CommentStatus;
import com.kienhee.blog.entity.Media;
import com.kienhee.blog.entity.Post;
import com.kienhee.blog.entity.PostStatus;
import com.kienhee.blog.service.DashboardService;
import jakarta.persistence.EntityManager;
import jakarta.persistence.TypedQuery;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDateTime;
import java.util.List;
import java.util.Map;

@Service
@RequiredArgsConstructor
public class DashboardServiceImpl implements DashboardService {

    private static final int TOP_CATEGORIES = 6;
    private static final int LIST_SIZE = 5;

    private final EntityManager em;
    private final PageViewService pageViewService;

    @Override
    @Transactional(readOnly = true)
    public DashboardStats load() {
        LocalDateTime now = LocalDateTime.now();
        LocalDateTime days30 = now.minusDays(30);
        LocalDateTime days60 = now.minusDays(60);

        long publishedLast30 = count("select count(p) from Post p where p.status = :s and p.publishedAt > :from and p.publishedAt <= :to",
                Map.of("s", PostStatus.PUBLISHED, "from", days30, "to", now));
        long publishedPrevious30 = count("select count(p) from Post p where p.status = :s and p.publishedAt > :from and p.publishedAt <= :to",
                Map.of("s", PostStatus.PUBLISHED, "from", days60, "to", days30));

        long published = countPosts(PostStatus.PUBLISHED);
        long drafts = countPosts(PostStatus.DRAFT);
        long scheduled = countPosts(PostStatus.SCHEDULED);
        long archived = countPosts(PostStatus.ARCHIVED);
        long scheduledWithoutTime = count("select count(p) from Post p where p.status = :s and p.scheduledAt is null",
                Map.of("s", PostStatus.SCHEDULED));
        long staleDrafts = count("select count(p) from Post p where p.status = :s and p.updatedAt < :before",
                Map.of("s", PostStatus.DRAFT, "before", days30));
        long missingSeoTitle = count("select count(p) from Post p where p.status = :s and (p.seoTitle is null or trim(p.seoTitle) = '')",
                Map.of("s", PostStatus.PUBLISHED));
        long missingSeoDescription = count("select count(p) from Post p where p.status = :s and (p.seoDescription is null or trim(p.seoDescription) = '')",
                Map.of("s", PostStatus.PUBLISHED));

        long approved = countComments(CommentStatus.APPROVED);
        long pending = countComments(CommentStatus.PENDING);
        long spam = countComments(CommentStatus.SPAM);

        Object[] media = em.createQuery(
                        "select count(m), coalesce(sum(m.sizeBytes), 0) from Media m where m.status = :s", Object[].class)
                .setParameter("s", Media.Status.ACTIVE)
                .getSingleResult();

        long users = count("select count(u) from User u", Map.of());
        long usersPending = count("select count(u) from User u where u.status = :s", Map.of("s", UserStatus.PENDING));

        List<CategoryCount> topCategories = em.createQuery(
                        "select c.name, count(p) from Post p join p.category c where p.status = :s " +
                                "group by c.id, c.name order by count(p) desc, c.name asc", Object[].class)
                .setParameter("s", PostStatus.PUBLISHED)
                .setMaxResults(TOP_CATEGORIES)
                .getResultList().stream()
                .map(row -> new CategoryCount((String) row[0], ((Number) row[1]).longValue()))
                .toList();

        // Rendered in the template: fetch what it touches (open-in-view is off).
        List<Comment> pendingComments = em.createQuery(
                        "select c from Comment c join fetch c.post where c.status = :s order by c.createdAt desc, c.id desc", Comment.class)
                .setParameter("s", CommentStatus.PENDING)
                .setMaxResults(LIST_SIZE)
                .getResultList();

        List<Post> recentPosts = em.createQuery(
                        "select p from Post p join fetch p.category order by p.updatedAt desc, p.id desc", Post.class)
                .setMaxResults(LIST_SIZE)
                .getResultList();

        LocalDate today = LocalDate.now();
        long views30 = pageViewService.viewsBetween(today.minusDays(29), today.plusDays(1));
        long viewsPrevious30 = pageViewService.viewsBetween(today.minusDays(59), today.minusDays(29));
        List<PageViewService.TopPost> topPosts = pageViewService.topPosts(today.minusDays(29), LIST_SIZE);

        return new DashboardStats(publishedLast30, publishedPrevious30, published, drafts, scheduled, scheduledWithoutTime, archived,
                staleDrafts, missingSeoTitle, missingSeoDescription, approved, pending, spam,
                ((Number) media[0]).longValue(), ((Number) media[1]).longValue(), users,
                topCategories, pendingComments, recentPosts, views30, viewsPrevious30, topPosts, usersPending);
    }

    private long countPosts(PostStatus status) {
        return count("select count(p) from Post p where p.status = :s", Map.of("s", status));
    }

    private long countComments(CommentStatus status) {
        return count("select count(c) from Comment c where c.status = :s", Map.of("s", status));
    }

    private long count(String jpql, Map<String, Object> params) {
        TypedQuery<Long> query = em.createQuery(jpql, Long.class);
        params.forEach(query::setParameter);
        return query.getSingleResult();
    }
}
