package com.kienhee.blog.service.impl;

import com.kienhee.blog.exception.BusinessException;
import com.kienhee.blog.dto.CommentForm;
import com.kienhee.blog.entity.Comment;
import com.kienhee.blog.entity.CommentStatus;
import com.kienhee.blog.entity.Post;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.CommentRepository;
import com.kienhee.blog.repository.PostRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.CommentService;
import com.kienhee.blog.service.SettingService;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDateTime;
import java.util.*;

@Service
@RequiredArgsConstructor
public class CommentServiceImpl implements CommentService {

    private final CommentRepository commentRepository;
    private final PostRepository postRepository;
    private final UserRepository userRepository;
    private final SettingService settingService;

    @Override
    @Transactional(readOnly = true)
    public List<CommentThread> approvedThreads(Long postId) {
        List<Comment> roots = new ArrayList<>();
        Map<Long, List<Comment>> replies = new HashMap<>();
        for (Comment c : commentRepository.findApprovedByPost(postId)) {
            if (c.getParentId() == null) {
                roots.add(c);
            } else {
                replies.computeIfAbsent(c.getParentId(), k -> new ArrayList<>()).add(c);
            }
        }
        return roots.stream()
                .map(root -> new CommentThread(root, replies.getOrDefault(root.getId(), List.of())))
                .toList();
    }

    @Override
    public boolean moderationEnabled() {
        return !"false".equalsIgnoreCase(settingService.get("comments.moderation", "true").trim());
    }

    @Override
    @Transactional
    public Comment submit(Post post, CommentForm form, User user, String ipAddress, String userAgent) {
        Long rootId = null;
        if (form.getParentId() != null) {
            Comment parent = commentRepository.findById(form.getParentId())
                    .filter(p -> post.getId().equals(p.getPostId()) && p.getStatus() == CommentStatus.APPROVED)
                    .orElseThrow(() -> new BusinessException("error.comment.parent_gone"));
            rootId = parent.getParentId() != null ? parent.getParentId() : parent.getId();
        }

        boolean signedIn = user != null;
        Comment comment = Comment.builder()
                .post(postRepository.getReferenceById(post.getId()))
                .parent(rootId == null ? null : commentRepository.getReferenceById(rootId))
                .user(signedIn ? userRepository.getReferenceById(user.getId()) : null)
                .authorName(signedIn ? user.getFullName() : form.getAuthorName().trim())
                .authorEmail(signedIn ? user.getEmail() : form.getAuthorEmail().trim().toLowerCase(Locale.ROOT))
                .content(form.getContent().replace("\r\n", "\n").trim())
                .status(signedIn || !moderationEnabled() ? CommentStatus.APPROVED : CommentStatus.PENDING)
                .ipAddress(truncate(ipAddress, 45))
                .userAgent(truncate(userAgent, 255))
                .build();
        return commentRepository.save(comment);
    }

    @Override
    @Transactional(readOnly = true)
    public List<Comment> allForAdmin() {
        return commentRepository.findAllForAdmin();
    }

    @Override
    @Transactional(readOnly = true)
    public long countByStatus(CommentStatus status) {
        return commentRepository.countByStatus(status);
    }

    @Override
    @Transactional
    public int updateStatus(Collection<Long> ids, CommentStatus status) {
        return commentRepository.updateStatus(requireIds(ids), status, LocalDateTime.now());
    }

    @Override
    @Transactional
    public int delete(Collection<Long> ids) {
        Collection<Long> selected = requireIds(ids);
        int live = (int) commentRepository.countLiveByIds(selected);
        // To the Trash, replies included; the count is the comments that were selected.
        commentRepository.moveToTrash(selected, LocalDateTime.now());
        return live;
    }

    private static Collection<Long> requireIds(Collection<Long> ids) {
        if (ids == null || ids.stream().noneMatch(Objects::nonNull)) {
            throw new BusinessException("error.comment.select_one");
        }
        return ids.stream().filter(Objects::nonNull).distinct().toList();
    }

    private static String truncate(String value, int max) {
        if (value == null) return null;
        return value.length() > max ? value.substring(0, max) : value;
    }

    @Override
    @Transactional
    public Comment replyAsStaff(Long commentId, String content, String staffEmail) {
        String text = content == null ? "" : content.replace("\r\n", "\n").trim();
        if (text.isEmpty()) {
            throw new BusinessException("error.comment.reply_empty");
        }
        if (text.length() > 2000) {
            throw new BusinessException("error.comment.reply_too_long");
        }
        Comment target = commentRepository.findById(commentId)
                .orElseThrow(() -> new BusinessException("error.comment.gone"));
        User staff = userRepository.findByEmail(staffEmail == null ? "" : staffEmail.trim().toLowerCase(Locale.ROOT))
                .orElseThrow(() -> new BusinessException("error.comment.account_gone"));

        Long rootId = target.getParentId() != null ? target.getParentId() : target.getId();
        // A reply is public, so the conversation it belongs to must be too.
        for (Long id : new LinkedHashSet<>(List.of(target.getId(), rootId))) {
            commentRepository.findById(id).filter(c -> c.getStatus() != CommentStatus.APPROVED).ifPresent(c -> {
                c.setStatus(CommentStatus.APPROVED);
                commentRepository.save(c);
            });
        }

        return commentRepository.save(Comment.builder()
                .post(postRepository.getReferenceById(target.getPostId()))
                .parent(commentRepository.getReferenceById(rootId))
                .user(staff)
                .authorName(staff.getFullName())
                .authorEmail(staff.getEmail())
                .content(text)
                .status(CommentStatus.APPROVED)
                .build());
    }
}
