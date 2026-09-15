package com.kienhee.blog.controller.admin;

import com.kienhee.blog.dto.MediaFolderCreateRequest;
import com.kienhee.blog.dto.MediaUpdateRequest;
import com.kienhee.blog.entity.Media;
import com.kienhee.blog.entity.MediaFolder;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.service.MediaFolderService;
import com.kienhee.blog.service.MediaService;
import jakarta.validation.ConstraintViolation;
import jakarta.validation.Validator;
import lombok.RequiredArgsConstructor;
import org.springframework.http.ResponseEntity;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.GrantedAuthority;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.multipart.MultipartFile;

import java.security.Principal;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.Comparator;
import java.util.HashSet;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.Set;
import java.util.stream.Collectors;

/**
 * JSON API behind the MediaExplorer component ({@code static/scripts/media/media-explorer.js}).
 * The Media library page and every picker (e.g. the post cover image) mount that same component,
 * so the explorer only ever talks to this controller.
 *
 * <p>Business rules stay in the services; this controller maps them to JSON. A rule violation
 * ({@code IllegalArgumentException}) or invalid input comes back as 422 with a {@code message}.
 * Writes are POSTs carrying the CSRF token like the rest of the admin.
 *
 * <p>Responses are always rebuilt from {@code getAllMedia()} / {@code getFolderTree()}, which
 * join-fetch what the JSON needs: open-in-view is off, so touching a lazy association of an entity
 * returned by a write method would throw.
 */
@RestController
@RequestMapping("/admin/api/media")
@RequiredArgsConstructor
@PreAuthorize("hasAuthority('media:view')")
public class MediaApiController {

    private static final DateTimeFormatter DAY = DateTimeFormatter.ofPattern("yyyy-MM-dd");

    private final MediaService mediaService;
    private final MediaFolderService mediaFolderService;
    private final Validator validator;

    // ---------------------------------------------------------------- read

    /**
     * Everything the explorer needs in one request: the folder tree, the files (optionally only
     * {@code kind=image} or {@code kind=file}), the uploaders for the filter, the trash summary and
     * what the signed-in user may do.
     */
    @GetMapping("/library")
    public Map<String, Object> library(@RequestParam(value = "kind", required = false) String kind,
                                       Authentication authentication) {
        List<Media> all = mediaService.getAllMedia();
        Set<String> used = mediaService.getUsedMediaUrls();

        List<Map<String, Object>> files = new ArrayList<>();
        Map<Long, Map<String, Object>> uploaders = new LinkedHashMap<>();
        for (Media media : all) {
            if (!matchesKind(media, kind)) {
                continue;
            }
            files.add(fileDto(media, used));
            User uploader = media.getUploadedBy();
            if (uploader != null) {
                uploaders.putIfAbsent(uploader.getId(), Map.of("id", uploader.getId(), "name", nameOf(uploader)));
            }
        }

        MediaService.TrashSummary trash = mediaService.getTrashSummary();
        Set<String> authorities = authorities(authentication);

        Map<String, Object> body = new LinkedHashMap<>();
        body.put("folders", folderDtos());
        body.put("files", files);
        body.put("uploaders", new ArrayList<>(uploaders.values()));
        body.put("trash", Map.of(
                "fileCount", trash.fileCount(),
                "folderCount", trash.folderCount(),
                "totalBytes", trash.totalBytes()));
        body.put("permissions", Map.of(
                "create", authorities.contains("media:create"),
                "edit", authorities.contains("media:edit"),
                "delete", authorities.contains("media:delete")));
        return body;
    }

    // ---------------------------------------------------------------- files

    @PostMapping("/files")
    @PreAuthorize("hasAuthority('media:create')")
    public ResponseEntity<Map<String, Object>> upload(@RequestParam(value = "files", required = false) MultipartFile[] files,
                                                      @RequestParam(value = "folderId", required = false) Long folderId,
                                                      Principal principal) {
        String uploaderEmail = principal != null ? principal.getName() : null;
        List<Long> uploaded = new ArrayList<>();
        List<String> errors = new ArrayList<>();
        if (files != null) {
            for (MultipartFile file : files) {
                if (file == null || file.isEmpty()) {
                    continue;
                }
                try {
                    uploaded.add(mediaService.uploadMedia(file, uploaderEmail, folderId).getId());
                } catch (IllegalArgumentException e) {
                    String name = file.getOriginalFilename() != null ? file.getOriginalFilename() : "file";
                    errors.add(name + ": " + e.getMessage());
                }
            }
        }
        if (uploaded.isEmpty()) {
            return unprocessable(errors.isEmpty() ? "Please choose a file to upload." : String.join(" ", errors), errors);
        }

        String message = uploaded.size() + (uploaded.size() == 1 ? " file uploaded." : " files uploaded.")
                + (errors.isEmpty() ? "" : " " + errors.size() + " could not be uploaded: " + String.join(" ", errors));
        Map<String, Object> body = new LinkedHashMap<>();
        body.put("message", message);
        body.put("files", filesByIds(uploaded));
        body.put("errors", errors);
        return ResponseEntity.ok(body);
    }

    @PostMapping("/files/{id}")
    @PreAuthorize("hasAuthority('media:edit')")
    public ResponseEntity<Map<String, Object>> update(@PathVariable Long id,
                                                      @RequestParam(value = "displayName", required = false) String displayName,
                                                      @RequestParam(value = "altText", required = false) String altText,
                                                      @RequestParam(value = "folderId", required = false) Long folderId) {
        MediaUpdateRequest request = MediaUpdateRequest.builder()
                .id(id)
                .displayName(displayName)
                .altText(altText)
                .folderId(folderId)
                .build();
        String invalid = firstViolation(request);
        if (invalid != null) {
            return unprocessable(invalid);
        }
        try {
            mediaService.updateMedia(id, request);
        } catch (IllegalArgumentException e) {
            return unprocessable(e.getMessage());
        }
        List<Map<String, Object>> updated = filesByIds(List.of(id));
        if (updated.isEmpty()) {
            return unprocessable("Media not found with id: " + id);
        }
        return ResponseEntity.ok(Map.of("message", "File updated.", "file", updated.get(0)));
    }

    /** Image editor, "Replace original": same id, URL and format; see {@link MediaService#replaceImage}. */
    @PostMapping("/files/{id}/image")
    @PreAuthorize("hasAuthority('media:edit')")
    public ResponseEntity<Map<String, Object>> replaceImage(@PathVariable Long id,
                                                            @RequestParam(value = "file", required = false) MultipartFile file,
                                                            Principal principal) {
        try {
            mediaService.replaceImage(id, file, principal != null ? principal.getName() : null);
        } catch (IllegalArgumentException e) {
            return unprocessable(e.getMessage());
        }
        List<Map<String, Object>> updated = filesByIds(List.of(id));
        if (updated.isEmpty()) {
            return unprocessable("Media not found with id: " + id);
        }
        return ResponseEntity.ok(Map.of("message", "Image updated.", "file", updated.get(0)));
    }

    /** Image editor, "Save as copy": a normal upload into the source image's folder (format may change). */
    @PostMapping("/files/{id}/image-copy")
    @PreAuthorize("hasAuthority('media:create')")
    public ResponseEntity<Map<String, Object>> copyImage(@PathVariable Long id,
                                                         @RequestParam(value = "file", required = false) MultipartFile file,
                                                         Principal principal) {
        List<Map<String, Object>> source = filesByIds(List.of(id));
        if (source.isEmpty()) {
            return unprocessable("Media not found with id: " + id);
        }
        if (!"image".equals(source.get(0).get("kind"))) {
            return unprocessable("Only images can be edited.");
        }
        if (file == null || file.isEmpty()) {
            return unprocessable("Please choose an image.");
        }
        try {
            Long folderId = (Long) source.get(0).get("folderId");
            Media copy = mediaService.uploadMedia(file, principal != null ? principal.getName() : null, folderId);
            return ResponseEntity.ok(Map.of("message", "Saved as a new image.", "file", filesByIds(List.of(copy.getId())).get(0)));
        } catch (IllegalArgumentException e) {
            return unprocessable(e.getMessage());
        }
    }

    /** Moves files into a folder ({@code folderId} omitted = Home). */
    @PostMapping("/files/move")
    @PreAuthorize("hasAuthority('media:edit')")
    public ResponseEntity<Map<String, Object>> moveFiles(@RequestParam(value = "ids", required = false) List<Long> ids,
                                                         @RequestParam(value = "folderId", required = false) Long folderId) {
        if (ids == null || ids.isEmpty()) {
            return unprocessable("No files selected.");
        }
        try {
            mediaService.bulkMoveToFolder(ids, folderId);
        } catch (IllegalArgumentException e) {
            return unprocessable(e.getMessage());
        }
        return ResponseEntity.ok(Map.of(
                "message", ids.size() + (ids.size() == 1 ? " file moved." : " files moved."),
                "files", filesByIds(ids)));
    }

    /** Soft delete: the file moves to the trash. */
    @PostMapping("/files/{id}/delete")
    @PreAuthorize("hasAuthority('media:delete')")
    public ResponseEntity<Map<String, Object>> deleteFile(@PathVariable Long id) {
        try {
            mediaService.deleteMedia(id);
        } catch (IllegalArgumentException e) {
            return unprocessable(e.getMessage());
        }
        return ResponseEntity.ok(Map.of(
                "message", "File moved to the trash.",
                "mediaIds", List.of(id),
                "folderIds", List.of()));
    }

    /** Soft delete of several files, each in its own transaction: one file still in use does not undo the rest. */
    @PostMapping("/files/delete")
    @PreAuthorize("hasAuthority('media:delete')")
    public ResponseEntity<Map<String, Object>> deleteFiles(@RequestParam(value = "ids", required = false) List<Long> ids) {
        if (ids == null || ids.isEmpty()) {
            return unprocessable("No files selected.");
        }
        List<Long> deleted = new ArrayList<>();
        List<String> errors = new ArrayList<>();
        for (Long id : ids) {
            try {
                mediaService.deleteMedia(id);
                deleted.add(id);
            } catch (IllegalArgumentException e) {
                errors.add(e.getMessage());
            }
        }
        String message = deleted.isEmpty()
                ? String.join(" ", errors)
                : deleted.size() + (deleted.size() == 1 ? " file moved to the trash." : " files moved to the trash.")
                        + (errors.isEmpty() ? "" : " " + errors.size() + " could not be deleted: " + String.join(" ", errors));
        Map<String, Object> body = new LinkedHashMap<>();
        body.put("message", message);
        body.put("mediaIds", deleted);
        body.put("folderIds", List.of());
        body.put("errors", errors);
        return ResponseEntity.status(deleted.isEmpty() ? 422 : 200).body(body);
    }

    // ---------------------------------------------------------------- folders

    @PostMapping("/folders")
    @PreAuthorize("hasAuthority('media:create')")
    public ResponseEntity<Map<String, Object>> createFolder(@RequestParam(value = "name", required = false) String name,
                                                            @RequestParam(value = "parentId", required = false) Long parentId) {
        MediaFolderCreateRequest request = MediaFolderCreateRequest.builder()
                .name(name != null ? name.trim() : null)
                .parentId(parentId)
                .build();
        String invalid = firstViolation(request);
        if (invalid != null) {
            return unprocessable(invalid);
        }
        MediaFolder created;
        try {
            created = mediaFolderService.createFolder(request);
        } catch (IllegalArgumentException e) {
            return unprocessable(e.getMessage());
        }
        List<Map<String, Object>> folders = folderDtos();
        Map<String, Object> body = new LinkedHashMap<>();
        body.put("message", "Folder created.");
        body.put("folder", folders.stream().filter(f -> created.getId().equals(f.get("id"))).findFirst().orElse(null));
        body.put("folders", folders);
        return ResponseEntity.ok(body);
    }

    @PostMapping("/folders/{id}/rename")
    @PreAuthorize("hasAuthority('media:edit')")
    public ResponseEntity<Map<String, Object>> renameFolder(@PathVariable Long id,
                                                            @RequestParam(value = "name", required = false) String name) {
        try {
            mediaFolderService.renameFolder(id, name);
        } catch (IllegalArgumentException e) {
            return unprocessable(e.getMessage());
        }
        return ResponseEntity.ok(Map.of("message", "Folder renamed.", "folders", folderDtos()));
    }

    /** Re-parents a folder ({@code parentId} omitted = Home); the whole subtree moves with it. */
    @PostMapping("/folders/{id}/move")
    @PreAuthorize("hasAuthority('media:edit')")
    public ResponseEntity<Map<String, Object>> moveFolder(@PathVariable Long id,
                                                          @RequestParam(value = "parentId", required = false) Long parentId) {
        try {
            mediaFolderService.moveFolder(id, parentId);
        } catch (IllegalArgumentException e) {
            return unprocessable(e.getMessage());
        }
        return ResponseEntity.ok(Map.of("message", "Folder moved.", "folders", folderDtos()));
    }

    /** Soft delete: the folder, its subfolders and their files move to the trash. */
    @PostMapping("/folders/{id}/delete")
    @PreAuthorize("hasAuthority('media:delete')")
    public ResponseEntity<Map<String, Object>> deleteFolder(@PathVariable Long id) {
        MediaFolderService.FolderDeleteResult result;
        try {
            result = mediaFolderService.deleteFolder(id);
        } catch (IllegalArgumentException e) {
            return unprocessable(e.getMessage());
        }
        return ResponseEntity.ok(Map.of(
                "message", "Folder moved to the trash, together with its subfolders and files.",
                "folderIds", result.folderIds(),
                "mediaIds", result.mediaIds()));
    }

    // ---------------------------------------------------------------- mapping

    private List<Map<String, Object>> filesByIds(List<Long> ids) {
        Set<Long> wanted = new HashSet<>(ids);
        Set<String> used = mediaService.getUsedMediaUrls();
        return mediaService.getAllMedia().stream()
                .filter(m -> wanted.contains(m.getId()))
                .map(m -> fileDto(m, used))
                .collect(Collectors.toList());
    }

    /** Only reads fields that getAllMedia() loaded: the row itself, uploadedBy and folder. */
    static Map<String, Object> fileDto(Media media, Set<String> usedUrls) {
        String name = media.getOriginalFilename();
        boolean image = isImage(media);
        Map<String, Object> row = new LinkedHashMap<>();
        row.put("id", media.getId());
        row.put("name", name);
        row.put("url", media.getUrl());
        row.put("thumbUrl", media.getThumbnailUrl() != null ? media.getThumbnailUrl() : media.getUrl());
        row.put("contentType", media.getContentType());
        row.put("kind", image ? "image" : "file");
        row.put("ext", name != null && name.contains(".")
                ? name.substring(name.lastIndexOf('.') + 1).toUpperCase(Locale.ROOT)
                : "FILE");
        row.put("sizeBytes", media.getSizeBytes());
        row.put("originalSizeBytes", media.getOriginalSizeBytes());
        row.put("optimized", media.isOptimized());
        // Changes whenever the bytes change (image editor): the explorer appends it to preview URLs.
        row.put("version", media.getSha256() != null && media.getSha256().length() >= 12
                ? media.getSha256().substring(0, 12)
                : String.valueOf(media.getSizeBytes()));
        row.put("width", media.getWidth());
        row.put("height", media.getHeight());
        row.put("altText", media.getAltText());
        row.put("folderId", media.getFolder() != null ? media.getFolder().getId() : null);
        row.put("uploaderId", media.getUploadedBy() != null ? media.getUploadedBy().getId() : null);
        row.put("uploaderName", media.getUploadedBy() != null ? nameOf(media.getUploadedBy()) : null);
        row.put("createdAt", media.getCreatedAt() != null ? media.getCreatedAt().toString() : null);
        row.put("used", usedUrls != null && usedUrls.contains(media.getUrl()));
        return row;
    }

    /** Depth-first, name-sorted tree; the parent comes from the materialized path, never a lazy load. */
    private List<Map<String, Object>> folderDtos() {
        List<Map<String, Object>> out = new ArrayList<>();
        for (MediaFolderService.FolderNode node : mediaFolderService.getFolderTree()) {
            MediaFolder folder = node.folder();
            Map<String, Object> row = new LinkedHashMap<>();
            row.put("id", folder.getId());
            row.put("name", folder.getName());
            row.put("path", folder.getPath());
            row.put("depth", node.depth());
            row.put("parentId", parentIdOf(folder.getPath()));
            row.put("createdAt", folder.getCreatedAt() != null ? folder.getCreatedAt().format(DAY) : null);
            out.add(row);
        }
        return out;
    }

    private static Long parentIdOf(String path) {
        if (path == null) {
            return null;
        }
        List<String> segments = new ArrayList<>();
        for (String part : path.split("/")) {
            if (!part.isBlank()) {
                segments.add(part);
            }
        }
        if (segments.size() < 2) {
            return null;
        }
        try {
            return Long.valueOf(segments.get(segments.size() - 2));
        } catch (NumberFormatException e) {
            return null;
        }
    }

    private static boolean isImage(Media media) {
        return media.getContentType() != null && media.getContentType().toLowerCase(Locale.ROOT).startsWith("image/");
    }

    private static boolean matchesKind(Media media, String kind) {
        if ("image".equalsIgnoreCase(kind)) {
            return isImage(media);
        }
        if ("file".equalsIgnoreCase(kind)) {
            return !isImage(media);
        }
        return true;
    }

    private static String nameOf(User user) {
        return user.getFullName() != null && !user.getFullName().isBlank() ? user.getFullName() : user.getEmail();
    }

    private static Set<String> authorities(Authentication authentication) {
        if (authentication == null) {
            return Set.of();
        }
        return authentication.getAuthorities().stream().map(GrantedAuthority::getAuthority).collect(Collectors.toSet());
    }

    /** First bean-validation message of a DTO, or null when it is valid. Reuses the DTOs' own rules. */
    private String firstViolation(Object dto) {
        return validator.validate(dto).stream()
                .sorted(Comparator.comparing((ConstraintViolation<Object> v) -> v.getPropertyPath().toString()))
                .map(ConstraintViolation::getMessage)
                .findFirst()
                .orElse(null);
    }

    private static ResponseEntity<Map<String, Object>> unprocessable(String message) {
        return ResponseEntity.status(422).body(Map.of("message", message != null ? message : "Request failed."));
    }

    private static ResponseEntity<Map<String, Object>> unprocessable(String message, List<String> errors) {
        Map<String, Object> body = new LinkedHashMap<>();
        body.put("message", message != null ? message : "Request failed.");
        body.put("errors", errors);
        return ResponseEntity.status(422).body(body);
    }
}
