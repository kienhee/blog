package com.kienhee.blog.service;

import com.kienhee.blog.entity.Comment;
import com.kienhee.blog.entity.Post;

import java.util.List;

/** Real numbers for the admin Dashboard, read straight from the database on every visit. */
public interface DashboardService {

    record CategoryCount(String name, long posts) {}

    record DashboardStats(
            long publishedLast30Days,
            long publishedPrevious30Days,
            long publishedTotal,
            long drafts,
            long scheduled,
            long scheduledWithoutTime,
            long archived,
            long staleDrafts,
            long missingSeoTitle,
            long missingSeoDescription,
            long commentsApproved,
            long commentsPending,
            long commentsSpam,
            long mediaFiles,
            long mediaBytes,
            long users,
            List<CategoryCount> topCategories,
            List<Comment> pendingComments,
            List<Post> recentPosts,
            long viewsLast30Days,
            long viewsPrevious30Days,
            List<PageViewService.TopPost> topPosts,
            long usersPending
    ) {
        /** Views in the last 30 days minus the 30 days before. */
        public long viewsDelta() {
            return viewsLast30Days - viewsPrevious30Days;
        }

        /** Published in the last 30 days minus the 30 days before. */
        public long publishedDelta() {
            return publishedLast30Days - publishedPrevious30Days;
        }

        /** Largest category count, for scaling the bars (never 0). */
        public long maxCategoryPosts() {
            return Math.max(1, topCategories.stream().mapToLong(CategoryCount::posts).max().orElse(1));
        }

        /** "48 KB", "3.2 MB", "1.1 GB". */
        public String mediaSize() {
            if (mediaBytes < 1024) return mediaBytes + " B";
            if (mediaBytes < 1024L * 1024) return Math.round(mediaBytes / 1024.0) + " KB";
            if (mediaBytes < 1024L * 1024 * 1024) return String.format(java.util.Locale.ROOT, "%.1f MB", mediaBytes / (1024.0 * 1024));
            return String.format(java.util.Locale.ROOT, "%.1f GB", mediaBytes / (1024.0 * 1024 * 1024));
        }
    }

    DashboardStats load();
}
