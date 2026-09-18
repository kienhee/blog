package com.kienhee.blog.controller.admin;

import com.kienhee.blog.controller.BusinessMessages;
import com.kienhee.blog.dto.MediaFolderCreateRequest;
import com.kienhee.blog.dto.MediaUpdateRequest;
import com.kienhee.blog.entity.Media;
import com.kienhee.blog.entity.MediaFolder;
import com.kienhee.blog.config.MediaTrashProperties;
import com.kienhee.blog.service.MediaFolderService;
import com.kienhee.blog.service.MediaService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import org.springframework.http.ResponseEntity;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.validation.BindingResult;
import org.springframework.validation.ObjectError;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.security.Principal;
import java.util.ArrayList;
import java.util.Comparator;
import java.util.HashMap;
import java.util.HashSet;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.stream.Collectors;

@Controller
@RequestMapping("/admin/media")
@RequiredArgsConstructor
@PreAuthorize("hasAuthority('media:view')")
public class MediaController {

    /**
     * Purging is irreversible, so it asks for more than the everyday delete permission.
     * The permission catalog is a fixed 28-row set (no media:purge exists and inventing
     * one would break the seeded roles), so the higher tier is expressed as the
     * combination media:delete + settings:delete — Owner has both, Editor only the first.
     */
    private static final String PURGE_AUTHORITY = "hasAuthority('media:purge')";

    private final MediaService mediaService;
    private final MediaFolderService mediaFolderService;
    private final MediaTrashProperties trashProperties;
    private final BusinessMessages messages;

    /**
     * The explorer on this page is the MediaExplorer component, which loads everything it shows
     * from {@link MediaApiController}. The page itself only tells it which folder to open first
     * ({@code ?folder=}, kept by the redirecting form endpoints below); an unknown id opens Home.
     */
    @GetMapping
    public String media(@RequestParam(value = "folder", required = false) Long folderId,
                        Model model) {
        boolean folderExists = folderId != null && mediaFolderService.getFolderTree().stream()
                .anyMatch(node -> folderId.equals(node.folder().getId()));
        model.addAttribute("currentFolderId", folderExists ? folderId : null);
        return "admin/media/media";
    }

    @PostMapping
    @PreAuthorize("hasAuthority('media:create')")
    public String upload(@RequestParam("files") MultipartFile[] files,
                          @RequestParam(value = "folderId", required = false) Long folderId,
                          Principal principal,
                          RedirectAttributes redirectAttributes) {
        String uploaderEmail = principal != null ? principal.getName() : null;

        int successCount = 0;
        List<String> errors = new ArrayList<>();
        for (MultipartFile file : files) {
            if (file == null || file.isEmpty()) {
                continue;
            }
            try {
                mediaService.uploadMedia(file, uploaderEmail, folderId);
                successCount++;
            } catch (IllegalArgumentException e) {
                errors.add((file.getOriginalFilename() != null ? file.getOriginalFilename() : messages.get("bulk.noun.media-files.bare")) + ": " + messages.text(e));
            }
        }

        if (successCount > 0) {
            redirectAttributes.addFlashAttribute("successMessage",
                    successCount + (successCount == 1 ? " file uploaded successfully." : " files uploaded successfully."));
        }
        if (!errors.isEmpty()) {
            redirectAttributes.addFlashAttribute("errorMessage", String.join(" · ", errors));
        }
        return redirectToFolder(folderId);
    }

    @PostMapping("/{id}/edit")
    @PreAuthorize("hasAuthority('media:edit')")
    public String edit(@PathVariable Long id,
                        @Valid @ModelAttribute("mediaUpdateRequest") MediaUpdateRequest request,
                        BindingResult bindingResult,
                        @RequestParam(value = "returnFolder", required = false) Long returnFolder,
                        RedirectAttributes redirectAttributes) {
        if (bindingResult.hasErrors()) {
            String message = bindingResult.getAllErrors().stream()
                    .map(ObjectError::getDefaultMessage)
                    .collect(Collectors.joining(" "));
            redirectAttributes.addFlashAttribute("errorMessage", message);
            return redirectToFolder(returnFolder);
        }

        try {
            mediaService.updateMedia(id, request);
            redirectAttributes.addFlashAttribute("successMessage", messages.get("msg.media.file_updated"));
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", messages.text(e));
        }
        return redirectToFolder(returnFolder);
    }

    @PostMapping("/{id}/delete")
    @PreAuthorize("hasAuthority('media:delete')")
    public String delete(@PathVariable Long id,
                          @RequestParam(value = "returnFolder", required = false) Long returnFolder,
                          RedirectAttributes redirectAttributes) {
        try {
            mediaService.deleteMedia(id);
            redirectAttributes.addFlashAttribute("successMessage", messages.get("msg.media.file_trashed"));
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", messages.text(e));
        }
        return redirectToFolder(returnFolder);
    }

    @PostMapping("/bulk-delete")
    @PreAuthorize("hasAuthority('media:delete')")
    public String bulkDelete(@RequestParam(value = "ids", required = false) List<Long> ids,
                              @RequestParam(value = "returnFolder", required = false) Long returnFolder,
                              RedirectAttributes redirectAttributes) {
        if (ids == null || ids.isEmpty()) {
            redirectAttributes.addFlashAttribute("errorMessage", messages.get("error.media.none_selected"));
            return redirectToFolder(returnFolder);
        }

        MediaService.BulkDeleteResult result = mediaService.bulkDeleteMedia(ids);
        if (result.deletedCount() > 0) {
            redirectAttributes.addFlashAttribute("successMessage",
                    result.deletedCount() + (result.deletedCount() == 1 ? " file moved to the trash." : " files moved to the trash."));
        }
        if (!result.errors().isEmpty()) {
            redirectAttributes.addFlashAttribute("errorMessage", String.join(" · ", result.errors()));
        }
        return redirectToFolder(returnFolder);
    }

    @PostMapping("/bulk-move")
    @PreAuthorize("hasAuthority('media:edit')")
    public String bulkMove(@RequestParam(value = "ids", required = false) List<Long> ids,
                            @RequestParam(value = "folderId", required = false) Long folderId,
                            @RequestParam(value = "returnFolder", required = false) Long returnFolder,
                            RedirectAttributes redirectAttributes) {
        if (ids == null || ids.isEmpty()) {
            redirectAttributes.addFlashAttribute("errorMessage", messages.get("error.media.none_selected"));
            return redirectToFolder(returnFolder);
        }
        try {
            mediaService.bulkMoveToFolder(ids, folderId);
            redirectAttributes.addFlashAttribute("successMessage", ids.size() + " file(s) moved.");
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", messages.text(e));
        }
        return redirectToFolder(returnFolder);
    }

    @PostMapping("/folders")
    @PreAuthorize("hasAuthority('media:create')")
    public String createFolder(@Valid @ModelAttribute("mediaFolderCreateRequest") MediaFolderCreateRequest request,
                                BindingResult bindingResult,
                                RedirectAttributes redirectAttributes) {
        if (bindingResult.hasErrors()) {
            String message = bindingResult.getAllErrors().stream()
                    .map(ObjectError::getDefaultMessage)
                    .collect(Collectors.joining(" "));
            redirectAttributes.addFlashAttribute("errorMessage", message);
            return redirectToFolder(request.getParentId());
        }
        try {
            mediaFolderService.createFolder(request);
            redirectAttributes.addFlashAttribute("successMessage", messages.get("msg.media.folder_created"));
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", messages.text(e));
        }
        return redirectToFolder(request.getParentId());
    }

    /**
     * Re-parents a folder. The service rewrites path/depth for the entire subtree and
     * rejects a move into the folder's own descendants.
     */
    @PostMapping("/folders/{id}/move")
    @PreAuthorize("hasAuthority('media:edit')")
    public String moveFolder(@PathVariable Long id,
                             @RequestParam(value = "parentId", required = false) Long parentId,
                             @RequestParam(value = "returnFolder", required = false) Long returnFolder,
                             RedirectAttributes redirectAttributes) {
        try {
            mediaFolderService.moveFolder(id, parentId);
            redirectAttributes.addFlashAttribute("successMessage", messages.get("msg.media.folder_moved"));
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", messages.text(e));
            return redirectToFolder(returnFolder);
        }
        return redirectToFolder(parentId);
    }

    /** Keeps the explorer on the folder the action was performed in. */
    private String redirectToFolder(Long folderId) {
        return folderId == null ? "redirect:/admin/media" : "redirect:/admin/media?folder=" + folderId;
    }

    @PostMapping("/folders/{id}/rename")
    @PreAuthorize("hasAuthority('media:edit')")
    public String renameFolder(@PathVariable Long id,
                                @RequestParam String name,
                                @RequestParam(value = "returnFolder", required = false) Long returnFolder,
                                RedirectAttributes redirectAttributes) {
        try {
            mediaFolderService.renameFolder(id, name);
            redirectAttributes.addFlashAttribute("successMessage", messages.get("msg.media.folder_renamed"));
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", messages.text(e));
        }
        return redirectToFolder(returnFolder);
    }

    @PostMapping("/folders/{id}/delete")
    @PreAuthorize("hasAuthority('media:delete')")
    public String deleteFolder(@PathVariable Long id,
                                @RequestParam(value = "returnFolder", required = false) Long returnFolder,
                                RedirectAttributes redirectAttributes) {
        try {
            mediaFolderService.deleteFolder(id);
            redirectAttributes.addFlashAttribute("successMessage", messages.get("msg.media.folder_trashed"));
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", messages.text(e));
        }
        return redirectToFolder(returnFolder);
    }

    // ---- Trash (soft delete / restore / purge) ------------------------------

    /**
     * The media trash is now two tabs of the shared Trash page. Kept as a redirect so old links,
     * bookmarks and the button in the media explorer still land in the right place.
     */
    @GetMapping("/trash")
    public String trash() {
        return "redirect:/admin/trash?type=media-files";
    }

    @PostMapping("/{id}/restore")
    @PreAuthorize("hasAuthority('media:edit')")
    public String restore(@PathVariable Long id, RedirectAttributes redirectAttributes) {
        try {
            MediaService.RestoreResult result = mediaService.restoreMedia(id);
            redirectAttributes.addFlashAttribute("successMessage", messages.get(result.movedToRoot()
                    ? "msg.media.file_restored_home" : "msg.media.file_restored"));
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", messages.text(e));
        }
        return "redirect:/admin/trash?type=media-files";
    }

    /**
     * Irreversible. The URL keeps the "/delete" segment on purpose: the shared confirm
     * dialog in core/admin.js only submits forms whose action contains it.
     */
    @PostMapping("/{id}/delete-permanent")
    @PreAuthorize(PURGE_AUTHORITY)
    public String purge(@PathVariable Long id, RedirectAttributes redirectAttributes) {
        try {
            mediaService.purgeMedia(id);
            redirectAttributes.addFlashAttribute("successMessage", messages.get("msg.media.file_purged"));
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", messages.text(e));
        }
        return "redirect:/admin/trash?type=media-files";
    }

    @PostMapping("/folders/{id}/restore")
    @PreAuthorize("hasAuthority('media:edit')")
    public String restoreFolder(@PathVariable Long id, RedirectAttributes redirectAttributes) {
        try {
            MediaFolderService.FolderRestoreResult result = mediaFolderService.restoreFolder(id);
            redirectAttributes.addFlashAttribute("successMessage", messages.get(result.movedToRoot()
                    ? "msg.media.folder_restored_home" : "msg.media.folder_restored"));
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", messages.text(e));
        }
        return "redirect:/admin/trash?type=media-files";
    }

    @PostMapping("/folders/{id}/delete-permanent")
    @PreAuthorize(PURGE_AUTHORITY)
    public String purgeFolder(@PathVariable Long id, RedirectAttributes redirectAttributes) {
        try {
            mediaFolderService.purgeFolder(id);
            redirectAttributes.addFlashAttribute("successMessage",
                    messages.get("msg.media.folder_purged"));
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", messages.text(e));
        }
        return "redirect:/admin/trash?type=media-files";
    }

    // ---- JSON bulk actions for the trash page ------------------------------
    // The trash table drops rows in place. Which rows to drop is worked out from the trash contents
    // before and after the batch, so anything that left the trash as a side effect disappears too:
    // a restored folder brings its subfolders and files back, a purged folder takes its subfolders.

    @PostMapping("/api/trash/restore")
    @PreAuthorize("hasAuthority('media:edit')")
    @ResponseBody
    public ResponseEntity<Map<String, Object>> restoreSelectedJson(
            @RequestParam(value = "mediaIds", required = false) List<Long> mediaIds,
            @RequestParam(value = "folderIds", required = false) List<Long> folderIds) {
        return trashBatch(mediaIds, folderIds, false);
    }

    @PostMapping("/api/trash/delete-permanent")
    @PreAuthorize(PURGE_AUTHORITY)
    @ResponseBody
    public ResponseEntity<Map<String, Object>> purgeSelectedJson(
            @RequestParam(value = "mediaIds", required = false) List<Long> mediaIds,
            @RequestParam(value = "folderIds", required = false) List<Long> folderIds) {
        return trashBatch(mediaIds, folderIds, true);
    }

    private ResponseEntity<Map<String, Object>> trashBatch(List<Long> mediaIds, List<Long> folderIds, boolean purge) {
        List<Long> media = mediaIds == null ? List.of() : mediaIds;
        List<Long> folders = folderIds == null ? new ArrayList<>() : new ArrayList<>(folderIds);
        if (media.isEmpty() && folders.isEmpty()) {
            return ResponseEntity.status(422).body(Map.of("message", messages.get("error.media.nothing_selected")));
        }

        Set<Long> mediaBefore = trashedMediaIds();
        Map<Long, Integer> folderDepthsBefore = trashedFolderDepths();
        List<String> errors = new ArrayList<>();

        if (purge) {
            // Files first; then folders deepest-first, since a folder purge takes its subfolders along.
            for (Long id : media) {
                purgeOrRestoreMedia(id, true, mediaBefore, errors);
            }
            folders.sort(Comparator.<Long>comparingInt(id -> folderDepthsBefore.getOrDefault(id, 0)).reversed());
            for (Long id : folders) {
                purgeOrRestoreFolder(id, true, folderDepthsBefore, errors);
            }
        } else {
            // Folders first, shallowest first: restoring a parent already brings its batch back.
            folders.sort(Comparator.comparingInt(id -> folderDepthsBefore.getOrDefault(id, 0)));
            for (Long id : folders) {
                purgeOrRestoreFolder(id, false, folderDepthsBefore, errors);
            }
            for (Long id : media) {
                purgeOrRestoreMedia(id, false, mediaBefore, errors);
            }
        }

        Set<Long> mediaAfter = trashedMediaIds();
        Set<Long> foldersAfter = trashedFolderDepths().keySet();
        List<Long> removedMedia = mediaBefore.stream().filter(id -> !mediaAfter.contains(id)).toList();
        List<Long> removedFolders = folderDepthsBefore.keySet().stream().filter(id -> !foldersAfter.contains(id)).toList();

        int done = removedMedia.size() + removedFolders.size();
        String counted = messages.get("bulk.noun.items" + (done == 1 ? ".one" : ".other"), done);
        String verbKey = purge ? "bulk.verb.purged" : "bulk.verb.restored";
        String message = done == 0
                ? (errors.isEmpty() ? messages.get("msg.media.nothing_done") : String.join(" ", errors))
                : messages.get(verbKey, counted)
                        + (errors.isEmpty() ? "" : " " + messages.get("msg.media.some_failed", errors.size(), String.join(" ", errors)));

        MediaService.TrashSummary summary = mediaService.getTrashSummary();
        Map<String, Object> body = new LinkedHashMap<>();
        body.put("message", message);
        body.put("removedMediaIds", removedMedia);
        body.put("removedFolderIds", removedFolders);
        body.put("errors", errors);
        body.put("fileCount", summary.fileCount());
        body.put("folderCount", summary.folderCount());
        body.put("totalBytes", summary.totalBytes());
        return ResponseEntity.status(done == 0 ? 422 : 200).body(body);
    }

    private void purgeOrRestoreMedia(Long id, boolean purge, Set<Long> trashedAtStart, List<String> errors) {
        if (!trashedAtStart.contains(id)) {
            errors.add("File #" + id + " is not in the trash.");
            return;
        }
        if (!trashedMediaIds().contains(id)) {
            return; // already left the trash with a folder processed earlier in this batch
        }
        try {
            if (purge) {
                mediaService.purgeMedia(id);
            } else {
                mediaService.restoreMedia(id);
            }
        } catch (IllegalArgumentException e) {
            errors.add(messages.text(e));
        }
    }

    private void purgeOrRestoreFolder(Long id, boolean purge, Map<Long, Integer> trashedAtStart, List<String> errors) {
        if (!trashedAtStart.containsKey(id)) {
            errors.add(messages.get("error.media.folder_not_trashed_id", id));
            return;
        }
        if (!trashedFolderDepths().containsKey(id)) {
            return; // already handled together with a parent folder in this batch
        }
        try {
            if (purge) {
                mediaFolderService.purgeFolder(id);
            } else {
                mediaFolderService.restoreFolder(id);
            }
        } catch (IllegalArgumentException e) {
            errors.add(messages.text(e));
        }
    }

    private Set<Long> trashedMediaIds() {
        return mediaService.getTrashedMedia().stream().map(Media::getId).collect(Collectors.toCollection(HashSet::new));
    }

    private Map<Long, Integer> trashedFolderDepths() {
        Map<Long, Integer> depths = new HashMap<>();
        for (MediaFolder folder : mediaFolderService.getTrashedFolders()) {
            depths.put(folder.getId(), folder.getDepth());
        }
        return depths;
    }

    @PostMapping("/trash/delete-all")
    @PreAuthorize(PURGE_AUTHORITY)
    public String emptyTrash(RedirectAttributes redirectAttributes) {
        MediaService.BulkDeleteResult result = mediaService.emptyTrash();
        if (result.deletedCount() > 0) {
            redirectAttributes.addFlashAttribute("successMessage",
                    messages.get("msg.media.trash_emptied", result.deletedCount()));
        } else if (result.errors().isEmpty()) {
            redirectAttributes.addFlashAttribute("successMessage", messages.get("msg.media.trash_already_empty"));
        }
        if (!result.errors().isEmpty()) {
            redirectAttributes.addFlashAttribute("errorMessage", String.join(" · ", result.errors()));
        }
        return "redirect:/admin/trash?type=media-files";
    }
}
