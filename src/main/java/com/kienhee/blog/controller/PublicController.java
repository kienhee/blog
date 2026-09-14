package com.kienhee.blog.controller;

import com.kienhee.blog.dto.CommentForm;
import com.kienhee.blog.entity.Category;
import com.kienhee.blog.service.CommentService;
import com.kienhee.blog.entity.Post;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.service.PublicBlogService;
import lombok.RequiredArgsConstructor;
import org.springframework.data.domain.Page;
import org.springframework.http.HttpStatus;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.server.ResponseStatusException;

import java.util.List;

/**
 * Public site. Everything here reads published content only (see {@link PublicBlogService});
 * list pages take a 1-based {@code ?page=} and use the "Posts per page" setting.
 */
@Controller
@RequiredArgsConstructor
public class PublicController {

    private static final int HOME_LATEST = 5;

    private final PublicBlogService blog;
    private final CommentService commentService;

    private static ResponseStatusException notFound() {
        return new ResponseStatusException(HttpStatus.NOT_FOUND);
    }

    @GetMapping("/")
    public String index(Model model) {
        List<Post> posts = blog.latest(1, HOME_LATEST + 1).getContent();
        model.addAttribute("featured", posts.isEmpty() ? null : posts.get(0));
        model.addAttribute("latest", posts.size() > 1 ? posts.subList(1, posts.size()) : List.of());
        return "public/index";
    }

    @GetMapping("/news")
    public String news(@RequestParam(defaultValue = "1") int page, Model model) {
        model.addAttribute("posts", blog.latest(page));
        model.addAttribute("categories", blog.categories());
        return "public/news";
    }

    @GetMapping("/categories")
    public String categories(Model model) {
        model.addAttribute("categories", blog.categories());
        return "public/categories";
    }

    @GetMapping("/category")
    public String categoryIndex() {
        return "redirect:/categories";
    }

    @GetMapping("/category/{slug}")
    public String category(@PathVariable String slug, @RequestParam(defaultValue = "1") int page, Model model) {
        Category category = blog.category(slug).orElseThrow(PublicController::notFound);
        model.addAttribute("category", category);
        model.addAttribute("posts", blog.postsInCategory(category, page));
        return "public/category";
    }

    @GetMapping("/author")
    public String authorIndex() {
        return blog.mainAuthorId().map(id -> "redirect:/author/" + id).orElse("redirect:/about");
    }

    @GetMapping("/author/{id}")
    public String author(@PathVariable Long id, @RequestParam(defaultValue = "1") int page, Model model) {
        User author = blog.author(id).orElseThrow(PublicController::notFound);
        Page<Post> posts = blog.postsByAuthor(author, page);
        if (posts.getTotalElements() == 0) {
            // Only people who have published something get a public profile.
            throw notFound();
        }
        model.addAttribute("author", author);
        model.addAttribute("posts", posts);
        model.addAttribute("stats", blog.authorStats(author));
        return "public/author";
    }

    @GetMapping("/about")
    public String about() {
        return "public/about";
    }

    @GetMapping("/article")
    public String articleIndex() {
        return "redirect:/news";
    }

    @GetMapping("/article/{slug}")
    public String article(@PathVariable String slug, Model model) {
        Post post = blog.article(slug).orElseThrow(PublicController::notFound);
        model.addAttribute("post", post);
        model.addAttribute("previous", blog.previous(post).orElse(null));
        model.addAttribute("next", blog.next(post).orElse(null));

        List<CommentService.CommentThread> threads = commentService.approvedThreads(post.getId());
        model.addAttribute("comments", threads);
        model.addAttribute("commentCount", threads.stream().mapToInt(CommentService.CommentThread::size).sum());
        model.addAttribute("moderation", commentService.moderationEnabled());
        if (!model.containsAttribute("commentForm")) {
            // A failed submit flashes the form back so the reader's text isn't lost.
            model.addAttribute("commentForm", new CommentForm());
        }
        return "public/article";
    }

    @GetMapping("/search")
    public String search(@RequestParam(required = false) String q, @RequestParam(defaultValue = "1") int page, Model model) {
        String query = q == null ? "" : q.trim();
        model.addAttribute("q", query);
        model.addAttribute("posts", blog.search(query, page));
        return "public/search";
    }

    @GetMapping("/subscribe")
    public String subscribe() {
        return "public/subscribe";
    }
}
