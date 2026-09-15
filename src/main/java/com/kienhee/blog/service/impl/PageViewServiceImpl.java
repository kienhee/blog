package com.kienhee.blog.service.impl;

import com.kienhee.blog.service.PageViewService;
import lombok.RequiredArgsConstructor;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDate;
import java.util.List;

@Service
@RequiredArgsConstructor
public class PageViewServiceImpl implements PageViewService {

    private final JdbcTemplate jdbc;

    @Override
    @Transactional
    public void recordView(Long postId, LocalDate day) {
        // One statement, safe under concurrency: the (post_id, day) primary key turns a race into an increment.
        jdbc.update("insert into post_view_daily (post_id, day, views) values (?, ?, 1) "
                + "on duplicate key update views = views + 1", postId, day);
    }

    @Override
    @Transactional(readOnly = true)
    public long viewsBetween(LocalDate from, LocalDate toExclusive) {
        Long total = jdbc.queryForObject(
                "select coalesce(sum(views), 0) from post_view_daily where day >= ? and day < ?", Long.class, from, toExclusive);
        return total == null ? 0 : total;
    }

    @Override
    @Transactional(readOnly = true)
    public List<TopPost> topPosts(LocalDate from, int limit) {
        return jdbc.query("select p.id, p.title, p.slug, sum(v.views) as total from post_view_daily v "
                        + "join posts p on p.id = v.post_id "
                        + "where v.day >= ? and p.deleted_at is null and p.status = 'PUBLISHED' "
                        + "group by p.id, p.title, p.slug order by total desc, p.id desc limit ?",
                (rs, i) -> new TopPost(rs.getLong("id"), rs.getString("title"), rs.getString("slug"), rs.getLong("total")),
                from, limit);
    }
}
