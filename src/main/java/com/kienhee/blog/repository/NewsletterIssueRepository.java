package com.kienhee.blog.repository;

import com.kienhee.blog.entity.NewsletterIssue;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;

import java.util.List;

public interface NewsletterIssueRepository extends JpaRepository<NewsletterIssue, Long> {

    /** Newest first, with the sender fetched for the admin list (open-in-view is off). */
    @Query("select i from NewsletterIssue i left join fetch i.sentBy order by i.sentAt desc, i.id desc")
    List<NewsletterIssue> findAllWithSender();
}
