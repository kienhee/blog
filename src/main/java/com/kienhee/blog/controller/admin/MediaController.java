package com.kienhee.blog.controller.admin;

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
    private static final String PURGE_AUTHORITY =
            "hasAuthority('media:delete') and hasAuthority('settings:delete')";

    private final MediaService mediaService;
    private final MediaFolderService mediaFolderService;
    private final MediaTrashProperties trashProperties;

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
                errors.add((file.getOriginalFilename() != null ? file.getOriginalFilename() : "file") + ": " + e.getMessage());
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
            redirectAttributes.addFlashAttribute("successMessage", "File updated successfully.");
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", e.getMessage());
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
            redirectAttributes.addFlashAttribute("successMessage", "File moved to the trash.");
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", e.getMessage());
        }
        return redirectToFolder(returnFolder);
    }

    @PostMapping("/bulk-delete")
    @PreAuthorize("hasAuthority('media:delete')")
    public String bulkDelete(@RequestParam(value = "ids", required = false) List<Long> ids,
                              @RequestParam(value = "returnFolder", required = false) Long returnFolder,
                              RedirectAttributes redirectAttributes) {
        if (ids == null || ids.isEmpty()) {
            redirectAttributes.addFlashAttribute("errorMessage", "No files selected.");
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
            redirectAttributes.addFlashAttribute("errorMessage", "No files selected.");
            return redirectToFolder(returnFolder);
        }
        try {
            mediaService.bulkMoveToFolder(ids, folderId);
            redirectAttributes.addFlashAttribute("successMessage", ids.size() + " file(s) moved.");
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", e.getMessage());
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
            redirectAttributes.addFlashAttribute("successMessage", "Folder created.");
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", e.getMessage());
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
            redirectAttributes.addFlashAttribute("successMessage", "Folder moved.");
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", e.getMessage());
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
            redirectAttributes.addFlashAttribute("successMessage", "Folder renamed.");
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", e.getMessage());
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
            redirectAttributes.addFlashAttribute("successMessage", "Folder moved to the trash, together with its subfolders and files.");
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", e.getMessage());
        }
        return redirectToFolder(returnFolder);
    }

    // ---- Trash (soft delete / restore / purge) ------------------------------

    @GetMapping("/trash")
    public String trash(Model model) {
        model.addAttribute("trashedMedia", mediaService.getTrashedMedia());
        model.addAttribute("trashedFolders", mediaFolderService.getTrashedFolders());
        model.addAttribute("trashSummary", mediaService.getTrashSummary());
        model.addAttribute("retentionDays", trashProperties.getRetentionDays());
        model.addAttribute("autoPurgeEnabled", trashProperties.isAutoPurgeEnabled());
        return "admin/media/trash";
    }

    @PostMapping("/{id}/restore")
    @PreAuthorize("hasAuthority('media:edit')")
    public String restore(@PathVariable Long id, RedirectAttributes redirectAttributes) {
        try {
            MediaService.RestoreResult result = mediaService.restoreMedia(id);
            redirectAttributes.addFlashAttribute("successMessage", result.movedToRoot()
                    ? "File restored to Home — the folder it used to live in no longer exists."
                    : "File restored.");
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", e.getMessage());
        }
        return "redirect:/admin/media/trash";
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
            redirectAttributes.addFlashAttribute("successMessage", "File permanently deleted.");
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", e.getMessage());
        }
        return "redirect:/admin/media/trash";
    }

    @PostMapping("/folders/{id}/restore")
    @PreAuthorize("hasAuthority('media:edit')")
    public String restoreFolder(@PathVariable Long id, RedirectAttributes redirectAttributes) {
        try {
            MediaFolderService.FolderRestoreResult result = mediaFolderService.restoreFolder(id);
            redirectAttributes.addFlashAttribute("successMessage", result.movedToRoot()
                    ? "Folder restored to Home — its parent folder no longer exists."
                    : "Folder restored with its subfolders and files.");
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", e.getMessage());
        }
        return "redirect:/admin/media/trash";
    }

    @PostMapping("/folders/{id}/delete-permanent")
    @PreAuthorize(PURGE_AUTHORITY)
    public String purgeFolder(@PathVariable Long id, RedirectAttributes redirectAttributes) {
        try {
            mediaFolderService.purgeFolder(id);
            redirectAttributes.addFlashAttribute("successMessage",
                    "Folder permanently deleted. Any files still inside it stay in the trash at Home.");
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", e.getMessage());
        }
        return "redirect:/admin/media/trash";
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
            return ResponseEntity.status(422).body(Map.of("message", "Nothing selected."));
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
        String verb = purge ? "permanently deleted" : "restored";
        String message = done == 0
                ? (errors.isEmpty() ? "Nothing was " + verb + "." : String.join(" ", errors))
                : done + (done == 1 ? " item " : " items ") + verb + "."
                        + (errors.isEmpty() ? "" : " " + errors.size() + " could not be " + verb + ": " + String.join(" ", errors));

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
            errors.add(e.getMessage());
        }
    }

    private void purgeOrRestoreFolder(Long id, boolean purge, Map<Long, Integer> trashedAtStart, List<String> errors) {
        if (!trashedAtStart.containsKey(id)) {
            errors.add("Folder #" + id + " is not in the trash.");
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
            errors.add(e.getMessage());
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
                    result.deletedCount() + " item(s) permanently deleted. Storage has been freed.");
        } else if (result.errors().isEmpty()) {
            redirectAttributes.addFlashAttribute("successMessage", "The trash is already empty.");
        }
        if (!result.errors().isEmpty()) {
            redirectAttributes.addFlashAttribute("errorMessage", String.join(" · ", result.errors()));
        }
        return "redirect:/admin/media/trash";
    }
}
