package com.kienhee.blog.controller;

import com.kienhee.blog.entity.Media;
import com.kienhee.blog.repository.MediaRepository;
import com.kienhee.blog.service.impl.MediaStorageLayout;
import com.kienhee.blog.storage.FilesystemStorage;
import com.kienhee.blog.storage.StorageException;
import com.kienhee.blog.storage.StoragePath;
import lombok.RequiredArgsConstructor;
import org.springframework.core.io.FileSystemResource;
import org.springframework.core.io.Resource;
import org.springframework.http.CacheControl;
import org.springframework.http.ContentDisposition;
import org.springframework.http.HttpHeaders;
import org.springframework.http.HttpStatus;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.security.authentication.AnonymousAuthenticationToken;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.context.request.WebRequest;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.server.ResponseStatusException;

import java.nio.charset.StandardCharsets;
import java.util.Locale;

/**
 * Serves media bytes at stable, id-based URLs — the only way files leave {@code uploads/} now that
 * the {@code /uploads/**} resource handler is gone.
 *
 * <ul>
 *   <li>Lookup is by {@code {id}} only. {@code {filename}} is cosmetic and is <b>never</b> used to
 *       locate a file: the path comes from the DB row and goes through the storage layer's root
 *       containment check.</li>
 *   <li>Raster images are served inline. SVG (can carry script) and every non-image are served as
 *       {@code attachment}, always with {@code nosniff}; SVG additionally gets a sandboxing CSP in
 *       case it is opened directly. {@code <img>} tags still render an attachment SVG.</li>
 *   <li>Trashed items: visible only to a signed-in user holding {@code media:view} (the Trash page
 *       previews them before purge), with {@code no-store}. Everyone else gets 404, exactly as if
 *       the file did not exist — a soft delete must take the file off the public site.</li>
 * </ul>
 */
@Controller
@RequestMapping("/media")
@RequiredArgsConstructor
public class MediaFileController {

    private static final String SVG = "image/svg+xml";

    private final MediaRepository mediaRepository;
    private final FilesystemStorage storage;
    private final MediaStorageLayout layout;

    @GetMapping("/{id}/thumb")
    public ResponseEntity<Resource> thumbnail(@PathVariable Long id, WebRequest request) {
        Media media = findVisible(id);
        StoragePath thumb = layout.existingThumbnail(id);
        if (thumb == null) {
            // No separate thumbnail (small image, SVG, legacy row): the original is the thumbnail.
            return serve(media, layout.effectivePath(media), media.getContentType(), "o", request);
        }
        String type = thumb.filename().endsWith(".jpg") ? MediaType.IMAGE_JPEG_VALUE : MediaType.IMAGE_PNG_VALUE;
        return serve(media, thumb, type, "t", request);
    }

    @GetMapping("/{id}/{filename}")
    public ResponseEntity<Resource> file(@PathVariable Long id, @PathVariable String filename, WebRequest request) {
        Media media = findVisible(id);
        return serve(media, layout.effectivePath(media), media.getContentType(), "o", request);
    }

    private Media findVisible(Long id) {
        Media media = mediaRepository.findById(id).orElseThrow(MediaFileController::notFound);
        if (media.getStatus() == Media.Status.TRASHED && !canViewTrash()) {
            throw notFound();
        }
        return media;
    }

    private boolean canViewTrash() {
        Authentication auth = SecurityContextHolder.getContext().getAuthentication();
        return auth != null
                && auth.isAuthenticated()
                && !(auth instanceof AnonymousAuthenticationToken)
                && auth.getAuthorities().stream().anyMatch(a -> "media:view".equals(a.getAuthority()));
    }

    private ResponseEntity<Resource> serve(Media media, StoragePath path, String contentType, String variant,
                                           WebRequest request) {
        java.nio.file.Path absolute;
        try {
            if (!storage.fileExists(path)) {
                throw notFound();
            }
            absolute = storage.resolveAbsolute(path);
        } catch (StorageException e) {
            throw notFound();
        }

        MediaType type;
        try {
            type = MediaType.parseMediaType(contentType);
        } catch (RuntimeException e) {
            type = MediaType.APPLICATION_OCTET_STREAM;
        }
        String normalized = type.getType().toLowerCase(Locale.ROOT) + "/" + type.getSubtype().toLowerCase(Locale.ROOT);
        boolean svg = SVG.equals(normalized);
        boolean inline = "image".equalsIgnoreCase(type.getType()) && !svg;

        String downloadName = media.getStoredFilename() != null ? media.getStoredFilename() : path.filename();
        ContentDisposition disposition = (inline ? ContentDisposition.inline() : ContentDisposition.attachment())
                .filename(downloadName, StandardCharsets.UTF_8)
                .build();

        HttpHeaders headers = new HttpHeaders();
        headers.setContentDisposition(disposition);
        headers.set("X-Content-Type-Options", "nosniff");
        if (svg) {
            headers.set("Content-Security-Policy", "default-src 'none'; style-src 'unsafe-inline'; sandbox");
        }
        // The URL stays the same when an image is edited in place, so browsers revalidate every time
        // (cheap 304 via the ETag, which follows the file's hash) instead of caching blindly for an hour.
        CacheControl cache = media.getStatus() == Media.Status.TRASHED
                ? CacheControl.noStore()
                : CacheControl.noCache();
        String version = media.getSha256() != null ? media.getSha256() : media.getSizeBytes() + "-" + media.getWidth() + "x" + media.getHeight();
        String etag = "\"" + media.getId() + "-" + variant + "-" + version + "\"";
        if (media.getStatus() != Media.Status.TRASHED && request.checkNotModified(etag)) {
            return ResponseEntity.status(304).eTag(etag).cacheControl(cache).build();
        }

        return ResponseEntity.ok()
                .eTag(etag)
                .headers(headers)
                .cacheControl(cache)
                .contentType(type)
                .body(new FileSystemResource(absolute));
    }

    private static ResponseStatusException notFound() {
        return new ResponseStatusException(HttpStatus.NOT_FOUND);
    }
}
