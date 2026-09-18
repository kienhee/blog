package com.kienhee.blog.service.impl;

import com.kienhee.blog.exception.BusinessException;
import com.kienhee.blog.entity.Media;
import com.kienhee.blog.entity.MediaFolder;
import com.kienhee.blog.repository.MediaFolderRepository;
import com.kienhee.blog.repository.MediaRepository;
import com.kienhee.blog.repository.StorageBlobRepository;
import com.kienhee.blog.storage.FilenameSanitizer;
import com.kienhee.blog.storage.FilesystemStorage;
import com.kienhee.blog.storage.StorageException;
import com.kienhee.blog.storage.StoragePath;
import com.kienhee.blog.storage.StorageTransactionHelper;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Component;
import org.springframework.web.util.UriUtils;

import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.Set;

/**
 * Knows how the media folder tree maps onto disk ({@code uploads/<slug>/<slug>/<file>}) and owns
 * every operation that has to keep disk, {@code media.storage_path} and
 * {@code storage_blob.storage_key} in step. Shared by {@code MediaServiceImpl} and
 * {@code MediaFolderServiceImpl} (which cannot depend on each other in both directions).
 *
 * <p>All disk mutations go through {@link StorageTransactionHelper}, so they are compensated on
 * rollback; callers must be {@code @Transactional}.</p>
 */
@Slf4j
@Component
@RequiredArgsConstructor
public class MediaStorageLayout {

    /** Flat thumbnail directory: {@code .thumbnails/<mediaId>_thumb.<ext>}. */
    public static final String THUMBNAIL_DIR = ".thumbnails";
    public static final String PROVIDER_CODE = "LOCAL";
    /** Must match {@code media.storage_path VARCHAR(700)}. */
    public static final int MAX_PATH_LENGTH = 700;

    private static final String[] THUMBNAIL_EXTENSIONS = {"jpg", "png"};

    private final FilesystemStorage storage;
    private final StorageTransactionHelper storageTx;
    private final FilenameSanitizer sanitizer;
    private final MediaRepository mediaRepository;
    private final MediaFolderRepository mediaFolderRepository;
    private final StorageBlobRepository storageBlobRepository;

    // ---- URLs ---------------------------------------------------------------

    /** Stable URL: looked up by id only, the filename is cosmetic. */
    public static String publicUrl(Long mediaId, String filename) {
        return "/media/" + mediaId + "/" + UriUtils.encodePathSegment(filename, StandardCharsets.UTF_8);
    }

    public static String thumbnailUrl(Long mediaId) {
        return "/media/" + mediaId + "/thumb";
    }

    // ---- paths --------------------------------------------------------------

    /** Directory of a folder: its ancestors' slugs joined root-first. {@code null} = Home = root. */
    public StoragePath directoryOf(MediaFolder folder) {
        if (folder == null) {
            return StoragePath.root();
        }
        List<Long> ids = parsePath(folder.getPath());
        Map<Long, String> slugById = new HashMap<>();
        List<Long> ancestorIds = ids.stream().filter(id -> !id.equals(folder.getId())).toList();
        if (!ancestorIds.isEmpty()) {
            mediaFolderRepository.findAllById(ancestorIds).forEach(f -> slugById.put(f.getId(), f.getSlug()));
        }
        slugById.put(folder.getId(), folder.getSlug());

        List<String> segments = new ArrayList<>(ids.size());
        for (Long id : ids) {
            String slug = slugById.get(id);
            if (slug == null) {
                throw new IllegalStateException("Folder " + folder.getId() + " has a broken path: " + folder.getPath());
            }
            segments.add(slug);
        }
        if (segments.isEmpty()) {
            segments.add(folder.getSlug());
        }
        return StoragePath.ofSegments(segments.toArray(String[]::new));
    }

    /** Where the bytes of a row live: its storage_path, or the flat legacy name for pre-V16 rows. */
    public StoragePath effectivePath(Media media) {
        return media.getStoragePath() != null
                ? StoragePath.of(media.getStoragePath())
                : StoragePath.of(media.getStoredFilename());
    }

    public StoragePath thumbnailPath(Long mediaId, String extension) {
        return StoragePath.ofSegments(THUMBNAIL_DIR, mediaId + "_thumb." + extension);
    }

    /** The thumbnail file that actually exists for this id, or {@code null}. */
    public StoragePath existingThumbnail(Long mediaId) {
        for (String ext : THUMBNAIL_EXTENSIONS) {
            StoragePath candidate = thumbnailPath(mediaId, ext);
            if (storage.fileExists(candidate)) {
                return candidate;
            }
        }
        return null;
    }

    public List<StoragePath> thumbnailCandidates(Long mediaId) {
        List<StoragePath> all = new ArrayList<>();
        for (String ext : THUMBNAIL_EXTENSIONS) {
            all.add(thumbnailPath(mediaId, ext));
        }
        return all;
    }

    public void requireLength(StoragePath path) {
        if (path.toString().length() > MAX_PATH_LENGTH) {
            throw new BusinessException("error.media.path_too_long");
        }
    }

    /** Hidden/system entries never shown or treated as user content (trap 5/6). */
    public static boolean isSystemEntry(String name) {
        return name == null || name.isEmpty() || name.startsWith(".") || name.toLowerCase(Locale.ROOT).endsWith(".part");
    }

    /** Directory listing with {@code .thumbnails}, {@code .orphaned}, dotfiles and {@code .part} removed. */
    public List<StoragePath> listVisible(StoragePath dir) {
        return storage.listDirectory(dir).stream()
                .filter(p -> !isSystemEntry(p.filename()))
                .toList();
    }

    /**
     * A sanitised name that is free in {@code dir}, Windows-style {@code (2)} suffix otherwise.
     * "Taken" is decided case-insensitively (trap 2) against both what is on disk (including
     * sub-directories, and hidden entries — they still occupy the name) and what rows already
     * claim in the DB.
     */
    public String uniqueFilename(StoragePath dir, String rawName) {
        Set<String> taken = new HashSet<>();
        for (StoragePath entry : storage.listDirectory(dir)) {
            taken.add(entry.filename().toLowerCase(Locale.ROOT));
        }
        String prefix = dir.isRoot() ? "" : dir + "/";
        for (String path : mediaRepository.findStoragePathsStartingWith(prefix)) {
            StoragePath p = StoragePath.of(path);
            if (p.parent().equals(dir)) {
                taken.add(p.filename().toLowerCase(Locale.ROOT));
            }
        }
        return sanitizer.sanitizeUnique(rawName, candidate -> taken.contains(candidate.toLowerCase(Locale.ROOT)));
    }

    // ---- file operations -----------------------------------------------------

    /**
     * Files a row into {@code target} (null = Home), moving the physical file along and keeping
     * storage_path / stored_filename / storage_key in step. The caller saves the row.
     *
     * <p>Pre-V16 rows (no storage_path) only get the DB change: their file is still in the flat
     * legacy layout and may be shared by several rows, so it is left for the data migration.</p>
     */
    public void relocate(Media media, MediaFolder target) {
        if (media.getStoragePath() == null) {
            media.setFolder(target);
            return;
        }
        StoragePath from = StoragePath.of(media.getStoragePath());
        StoragePath dir = directoryOf(target);
        media.setFolder(target);
        if (from.parent().equals(dir)) {
            return;
        }
        // Trap 3: moveFile overwrites — always pick a free name first.
        String name = uniqueFilename(dir, from.filename());
        StoragePath to = dir.resolve(name);
        requireLength(to);
        try {
            if (storage.fileExists(from)) {
                storageTx.moveFile(from, to);
            } else {
                log.warn("Media {} file {} missing on disk; updating its path only", media.getId(), from);
            }
        } catch (StorageException e) {
            log.warn("Could not move media {} {} -> {}: {}", media.getId(), from, to, e.getMessage());
            throw new BusinessException("error.media.move_file_failed", media.getOriginalFilename());
        }
        media.setStoragePath(to.toString());
        media.setStoredFilename(name);
        if (media.getBlob() != null) {
            media.getBlob().setStorageKey(to.toString());
        }
    }

    // ---- directory operations ------------------------------------------------

    public void createDirectory(StoragePath dir) {
        requireLength(dir);
        try {
            storageTx.createDirectory(dir);
        } catch (StorageException e) {
            log.warn("Could not create directory {}: {}", dir, e.getMessage());
            throw new BusinessException("error.media.create_dir_failed");
        }
    }

    /**
     * Moves a folder's directory and rewrites media.storage_path AND storage_blob.storage_key for
     * the whole subtree in the current transaction (trap 1: forgetting the blob key only blows up
     * on the <em>second</em> rename, through its UNIQUE index).
     *
     * <p>Issues bulk updates that clear the persistence context: call it after the folder rows
     * have been saved, and do not rely on previously loaded entities being managed afterwards.</p>
     */
    public void moveDirectory(StoragePath from, StoragePath to) {
        if (from.equals(to)) {
            return;
        }
        requireLength(to);
        try {
            if (storage.directoryExists(from)) {
                storageTx.moveDirectory(from, to);
            } else {
                // Folder created before the mirror existed: nothing to carry, just materialise it.
                storageTx.createDirectory(to);
            }
        } catch (StorageException e) {
            log.warn("Could not move directory {} -> {}: {}", from, to, e.getMessage());
            throw new BusinessException("error.media.move_dir_failed");
        }
        String oldPrefix = from + "/";
        String newPrefix = to + "/";
        mediaRepository.rewriteStoragePathPrefix(oldPrefix, newPrefix);
        storageBlobRepository.rewriteStorageKeyPrefix(oldPrefix, newPrefix);
    }

    /**
     * Hard-deletes a folder row. Files still filed in it (trashed ones) are re-homed to the root
     * on disk too, so "folder_id NULL" keeps meaning "lives in uploads/". The now-empty directory
     * is removed after commit; if something unexpected is still in it, it is left and logged.
     */
    public void purgeFolder(MediaFolder folder) {
        StoragePath dir = directoryOf(folder);
        for (Media media : mediaRepository.findByFolderId(folder.getId())) {
            relocate(media, null);
            mediaRepository.save(media);
        }
        mediaFolderRepository.delete(folder);
        storageTx.deleteDirectoryIfEmptyAfterCommit(dir);
    }

    private List<Long> parsePath(String path) {
        List<Long> ids = new ArrayList<>();
        if (path == null) {
            return ids;
        }
        for (String segment : path.split("/")) {
            if (!segment.isBlank()) {
                try {
                    ids.add(Long.parseLong(segment));
                } catch (NumberFormatException ignored) {
                    // malformed segment contributes nothing
                }
            }
        }
        return ids;
    }
}
