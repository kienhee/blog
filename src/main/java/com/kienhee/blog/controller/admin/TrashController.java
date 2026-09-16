package com.kienhee.blog.controller.admin;

import com.kienhee.blog.config.MediaTrashProperties;
import com.kienhee.blog.entity.MediaFolder;
import com.kienhee.blog.service.MediaFolderService;
import com.kienhee.blog.service.MediaService;
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
import java.util.Comparator;
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
@PreAuthorize("hasAnyAuthority('posts:delete', 'categories:delete', 'hashtags:delete', 'comments:delete', 'media:delete')")
public class TrashController {

    public record Tab(String slug, String label, long count) {}

    private final TrashService trashService;
    private final MediaService mediaService;
    private final MediaFolderService mediaFolderService;
    private final MediaTrashProperties mediaTrashProperties;

    @GetMapping
    public String trash(@RequestParam(value = "type", required = false) String slug, Authentication authentication, Model model) {
        List<TrashType> allowed = Arrays.stream(TrashType.values()).filter(t -> can(authentication, t)).toList();
        TrashType selected = TrashType.fromSlug(slug).filter(allowed::contains).orElse(allowed.get(0));

        model.addAttribute("tabs", allowed.stream().map(t -> new Tab(t.getSlug(), t.getLabel(), trashService.count(t))).toList());
        model.addAttribute("selected", selected);
        model.addAttribute("items", trashService.list(selected));
        // Deleting permanently is a second permission for media (media:purge), so the buttons follow this flag.
        model.addAttribute("canPurge", has(authentication, selected.getPurgePermission()));
        if (selected.isMedia()) {
            model.addAttribute("mediaRetentionDays", mediaTrashProperties.getRetentionDays());
            model.addAttribute("mediaAutoPurge", mediaTrashProperties.isAutoPurgeEnabled());
            model.addAttribute("mediaTrashSummary", mediaService.getTrashSummary());
        }
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
        resolve(type, authentication, true);
        return apply(type, ids, "deleted permanently", trashService::purge, authentication, redirect);
    }

    @PostMapping("/{type}/empty")
    public String empty(@PathVariable String type, Authentication authentication, RedirectAttributes redirect) {
        TrashType trashType = resolve(type, authentication, true);
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
        ids = orderFolders(trashType, ids, "deleted permanently".equals(verb));
        Map<Long, String> names = trashService.list(trashType).stream()
                .collect(Collectors.toMap(TrashService.TrashItem::id, item -> item.name() == null ? "#" + item.id() : item.name(), (a, b) -> a));
        BulkDelete.run(ids, trashType.getSingular(), trashType.getPlural(), verb, names,
                id -> action.accept(trashType, id), redirect);
        return "redirect:/admin/trash?type=" + trashType.getSlug();
    }

    /**
     * Folders are nested, so the order matters: purge the deepest first (purging a folder takes its
     * subfolders with it), restore the shallowest first (restoring a parent brings its batch back).
     * Ids that disappear as a side effect are simply reported as skipped by BulkDelete.
     */
    private List<Long> orderFolders(TrashType type, List<Long> ids, boolean purging) {
        if (type != TrashType.MEDIA_FOLDERS || ids == null || ids.size() < 2) {
            return ids;
        }
        Map<Long, Integer> depths = mediaFolderService.getTrashedFolders().stream()
                .collect(Collectors.toMap(MediaFolder::getId, MediaFolder::getDepth, (a, b) -> a));
        Comparator<Long> byDepth = Comparator.comparingInt(id -> depths.getOrDefault(id, 0));
        return ids.stream().sorted(purging ? byDepth.reversed() : byDepth).toList();
    }

    private TrashType resolve(String slug, Authentication authentication) {
        return resolve(slug, authentication, false);
    }

    /** {@code purging} switches the check to the type's purge permission, which only media differs on. */
    private TrashType resolve(String slug, Authentication authentication, boolean purging) {
        TrashType type = TrashType.fromSlug(slug).orElseThrow(() -> new ResponseStatusException(HttpStatus.NOT_FOUND));
        String needed = purging ? type.getPurgePermission() : type.getPermission();
        if (!can(authentication, type) || !has(authentication, needed)) {
            throw new AccessDeniedException("Missing " + needed);
        }
        return type;
    }

    private static boolean can(Authentication authentication, TrashType type) {
        return has(authentication, type.getPermission());
    }

    private static boolean has(Authentication authentication, String permission) {
        return authentication != null && authentication.getAuthorities().stream()
                .anyMatch(a -> permission.equals(a.getAuthority()));
    }
}
