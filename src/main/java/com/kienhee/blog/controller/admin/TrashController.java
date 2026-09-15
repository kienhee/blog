package com.kienhee.blog.controller.admin;

import com.kienhee.blog.service.TrashService;
import com.kienhee.blog.service.TrashType;
import lombok.RequiredArgsConstructor;
import org.springframework.http.HttpStatus;
import org.springframework.security.access.AccessDeniedException;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.server.ResponseStatusException;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.util.Arrays;
import java.util.List;
import java.util.Map;
import java.util.function.BiConsumer;
import java.util.stream.Collectors;

/**
 * Shared Trash for posts, categories, hashtags and comments. Each tab (and every action on it) needs that
 * module's {@code :delete} permission, so people only see what they could have deleted themselves.
 */
@Controller
@RequestMapping("/admin/trash")
@RequiredArgsConstructor
@PreAuthorize("hasAnyAuthority('posts:delete', 'categories:delete', 'hashtags:delete', 'comments:delete')")
public class TrashController {

    public record Tab(String slug, String label, long count) {}

    private final TrashService trashService;

    @GetMapping
    public String trash(@RequestParam(value = "type", required = false) String slug, Authentication authentication, Model model) {
        List<TrashType> allowed = Arrays.stream(TrashType.values()).filter(t -> can(authentication, t)).toList();
        TrashType selected = TrashType.fromSlug(slug).filter(allowed::contains).orElse(allowed.get(0));

        model.addAttribute("tabs", allowed.stream().map(t -> new Tab(t.getSlug(), t.getLabel(), trashService.count(t))).toList());
        model.addAttribute("selected", selected);
        model.addAttribute("items", trashService.list(selected));
        return "admin/trash/trash";
    }

    @PostMapping("/{type}/restore")
    public String restore(@PathVariable String type, @RequestParam(name = "ids", required = false) List<Long> ids,
                          Authentication authentication, RedirectAttributes redirect) {
        return apply(type, ids, "restored", trashService::restore, authentication, redirect);
    }

    @PostMapping("/{type}/purge")
    public String purge(@PathVariable String type, @RequestParam(name = "ids", required = false) List<Long> ids,
                        Authentication authentication, RedirectAttributes redirect) {
        return apply(type, ids, "deleted permanently", trashService::purge, authentication, redirect);
    }

    @PostMapping("/{type}/empty")
    public String empty(@PathVariable String type, Authentication authentication, RedirectAttributes redirect) {
        TrashType trashType = resolve(type, authentication);
        List<Long> all = trashService.list(trashType).stream().map(TrashService.TrashItem::id).toList();
        if (all.isEmpty()) {
            redirect.addFlashAttribute("successMessage", "The " + trashType.getSingular() + " trash is already empty.");
            return "redirect:/admin/trash?type=" + trashType.getSlug();
        }
        return apply(type, all, "deleted permanently", trashService::purge, authentication, redirect);
    }

    private String apply(String type, List<Long> ids, String verb, BiConsumer<TrashType, Long> action,
                         Authentication authentication, RedirectAttributes redirect) {
        TrashType trashType = resolve(type, authentication);
        Map<Long, String> names = trashService.list(trashType).stream()
                .collect(Collectors.toMap(TrashService.TrashItem::id, item -> item.name() == null ? "#" + item.id() : item.name(), (a, b) -> a));
        BulkDelete.run(ids, trashType.getSingular(), trashType.getPlural(), verb, names,
                id -> action.accept(trashType, id), redirect);
        return "redirect:/admin/trash?type=" + trashType.getSlug();
    }

    private TrashType resolve(String slug, Authentication authentication) {
        TrashType type = TrashType.fromSlug(slug).orElseThrow(() -> new ResponseStatusException(HttpStatus.NOT_FOUND));
        if (!can(authentication, type)) {
            throw new AccessDeniedException("Missing " + type.getPermission());
        }
        return type;
    }

    private static boolean can(Authentication authentication, TrashType type) {
        return authentication != null && authentication.getAuthorities().stream()
                .anyMatch(a -> type.getPermission().equals(a.getAuthority()));
    }
}
