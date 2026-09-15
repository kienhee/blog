package com.kienhee.blog.repository;

import com.kienhee.blog.entity.Subscriber;
import com.kienhee.blog.entity.SubscriberStatus;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.Optional;

public interface SubscriberRepository extends JpaRepository<Subscriber, Long> {

    Optional<Subscriber> findByEmail(String email);

    Optional<Subscriber> findByConfirmTokenHash(String confirmTokenHash);

    Optional<Subscriber> findByUnsubscribeToken(String unsubscribeToken);

    long countByStatus(SubscriberStatus status);

    List<Subscriber> findByStatusOrderByIdAsc(SubscriberStatus status);

    List<Subscriber> findAllByOrderByCreatedAtDesc();
}
