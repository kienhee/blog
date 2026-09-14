package com.kienhee.blog.service.impl;

import com.kienhee.blog.dto.MediaFolderCreateRequest;
import com.kienhee.blog.entity.Media;
import com.kienhee.blog.entity.MediaFolder;
import com.kienhee.blog.repository.MediaFolderRepository;
import com.kienhee.blog.repository.MediaRepository;
import com.kienhee.blog.service.MediaFolderService;
import com.kienhee.blog.service.MediaService;
import com.kienhee.blog.storage.StoragePath;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.Comparator;
import java.util.HashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.Objects;

@Service
@RequiredArgsConstructor
public class MediaFolderServiceImpl implements MediaFolderService {

    private final MediaFolderRepository mediaFolderRepository;
    private final MediaRepository mediaRepository;
    /** Only for the file-level trash rules (in-use checks, status flip) — no cycle:
     *  MediaServiceImpl depends on repositories, never on this service. */
    private final MediaService mediaService;
    private final MediaStorageLayout layout;

    @Override
    @Transactional(readOnly = true)
    public List<MediaFolder> getAllFolders() {
        return mediaFolderRepository.findByStatusOrderByNameAsc(MediaFolder.Status.ACTIVE);
    }

    @Override
    @Transactional(readOnly = true)
    public List<FolderNode> getFolderTree() {
        // One query, parent join-fetched: the caller (a template) must never trigger a
        // lazy load, open-in-view is off.
        List<MediaFolder> all = mediaFolderRepository.findAllWithParent(MediaFolder.Status.ACTIVE);

        Map<Long, List<MediaFolder>> childrenByParent = new HashMap<>();
        for (MediaFolder folder : all) {
            Long parentId = folder.getParent() != null ? folder.getParent().getId() : null;
            childrenByParent.computeIfAbsent(parentId, k -> new ArrayList<>()).add(folder);
        }
        childrenByParent.values().forEach(list ->
                list.sort(Comparator.comparing(MediaFolder::getName, String.CASE_INSENSITIVE_ORDER)));

        List<FolderNode> flattened = new ArrayList<>(all.size());
        appendLevel(childrenByParent, null, 0, flattened);
        return flattened;
    }

    private void appendLevel(Map<Long, List<MediaFolder>> childrenByParent,
                             Long parentId,
                             int depth,
                             List<FolderNode> out) {
        for (MediaFolder folder : childrenByParent.getOrDefault(parentId, List.of())) {
            out.add(new FolderNode(folder, depth));
            appendLevel(childrenByParent, folder.getId(), depth + 1, out);
        }
    }

    @Override
    @Transactional(readOnly = true)
    public List<MediaFolder> getBreadcrumb(Long folderId) {
        if (folderId == null) {
            return List.of();
        }
        MediaFolder folder = mediaFolderRepository.findById(folderId)
                .orElseThrow(() -> new IllegalArgumentException("Folder not found with id: " + folderId));

        List<Long> ancestorIds = parsePath(folder.getPath());
        if (ancestorIds.isEmpty()) {
            return List.of(folder);
        }
        // The whole ancestor chain in ONE query - that is the point of the materialized
        // path. Never walk parent-by-parent.
        Map<Long, MediaFolder> byId = new HashMap<>();
        mediaFolderRepository.findAllById(ancestorIds).forEach(f -> byId.put(f.getId(), f));

        List<MediaFolder> trail = new ArrayList<>(ancestorIds.size());
        for (Long id : ancestorIds) {
            MediaFolder f = byId.get(id);
            if (f != null) {
                trail.add(f);
            }
        }
        return trail;
    }

    /** {@code "/1/17/"} maps to {@code [1, 17]}: root-to-leaf, includes the folder itself. */
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
                    // A malformed path segment simply doesn't contribute an ancestor.
                }
            }
        }
        return ids;
    }

    @Override
    @Transactional
    public MediaFolder createFolder(MediaFolderCreateRequest request) {
        MediaFolder parent = resolveParent(request.getParentId());
        return createFolder(request.getName().trim(), parent, false);
    }

    @Override
    @Transactional
    public MediaFolder getOrCreateByName(String name) {
        // Scoped to the root: with a nested tree the same name may legitimately exist in
        // another branch, and an unscoped lookup would blow up with NonUniqueResultException.
        return mediaFolderRepository
                .findFirstByParentIsNullAndNameIgnoreCaseAndStatus(name, MediaFolder.Status.ACTIVE)
                .orElseGet(() -> createFolder(name, null, true));
    }

    private MediaFolder resolveParent(Long parentId) {
        if (parentId == null) {
            return null;
        }
        return mediaFolderRepository.findById(parentId)
                .orElseThrow(() -> new IllegalArgumentException("Parent folder not found with id: " + parentId));
    }

    /**
     * @param uniquifySlug when true a colliding slug gets a numeric suffix instead of
     *                     failing - only for internally auto-created folders (avatars);
     *                     a user-driven create must report the collision instead.
     */
    private MediaFolder createFolder(String name, MediaFolder parent, boolean uniquifySlug) {
        int depth = parent == null ? 0 : parent.getDepth() + 1;
        if (depth > MAX_DEPTH) {
            throw new IllegalArgumentException(
                    "Cannot create: folders can only be nested " + MAX_DEPTH + " levels deep.");
        }

        Long parentId = parent != null ? parent.getId() : null;
        String slug = slugify(name);

        // Uniqueness is per parent now, not global. MySQL does not enforce the
        // (parent_id, slug) UNIQUE index when parent_id IS NULL, so root collisions have to
        // be caught here or duplicates slip straight through.
        if (uniquifySlug) {
            String baseSlug = slug;
            int suffix = 2;
            while (slugTaken(parentId, slug)) {
                slug = baseSlug + "-" + suffix;
                suffix++;
            }
        } else if (slugTaken(parentId, slug)) {
            throw new IllegalArgumentException("A folder named \"" + name + "\" already exists here.");
        }

        MediaFolder folder = mediaFolderRepository.save(MediaFolder.builder()
                .name(name)
                .slug(slug)
                .parent(parent)
                .depth(depth)
                .path("/")
                .build());

        // The path contains the folder's own id, so it can only be built after the insert.
        folder.setPath(pathOf(parent, folder.getId()));
        MediaFolder saved = mediaFolderRepository.save(folder);
        // Mirror on disk; removed again if the transaction rolls back.
        layout.createDirectory(layout.directoryOf(saved));
        return saved;
    }

    private boolean slugTaken(Long parentId, String slug) {
        return parentId == null
                ? mediaFolderRepository.existsByParentIsNullAndSlug(slug)
                : mediaFolderRepository.existsByParentIdAndSlug(parentId, slug);
    }

    private boolean slugTakenByOther(Long parentId, String slug, Long selfId) {
        return parentId == null
                ? mediaFolderRepository.existsByParentIsNullAndSlugAndIdNot(slug, selfId)
                : mediaFolderRepository.existsByParentIdAndSlugAndIdNot(parentId, slug, selfId);
    }

    private String pathOf(MediaFolder parent, Long id) {
        String parentPath = parent == null ? "/" : parent.getPath();
        return parentPath + id + "/";
    }

    private String slugify(String name) {
        String slug = name.toLowerCase(Locale.ROOT)
                .replaceAll("[^a-z0-9]+", "-")
                .replaceAll("^-|-$", "");
        return slug.isBlank() ? "folder" : slug;
    }

    @Override
    @Transactional
    public MediaFolder renameFolder(Long id, String name) {
        if (name == null || name.trim().length() < 2) {
            throw new IllegalArgumentException("Folder name must have at least 2 characters.");
        }
        MediaFolder folder = mediaFolderRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("Folder not found with id: " + id));

        String trimmed = name.trim();
        String slug = slugify(trimmed);
        Long parentId = folder.getParent() != null ? folder.getParent().getId() : null;
        if (slugTakenByOther(parentId, slug, folder.getId())) {
            throw new IllegalArgumentException("A folder named \"" + trimmed + "\" already exists here.");
        }

        StoragePath oldDir = layout.directoryOf(folder);
        boolean slugChanged = !slug.equals(folder.getSlug());
        folder.setName(trimmed);
        folder.setSlug(slug);
        MediaFolder saved = mediaFolderRepository.saveAndFlush(folder);
        if (slugChanged) {
            // Directory renamed + storage_path AND storage_key rewritten for the subtree.
            layout.moveDirectory(oldDir, oldDir.parent().resolve(slug));
        }
        return saved;
    }

    @Override
    @Transactional
    public MediaFolder moveFolder(Long id, Long newParentId) {
        MediaFolder folder = mediaFolderRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("Folder not found with id: " + id));
        MediaFolder newParent = resolveParent(newParentId);

        Long currentParentId = folder.getParent() != null ? folder.getParent().getId() : null;
        if (Objects.equals(currentParentId, newParentId)) {
            return folder;
        }

        if (newParent != null) {
            // A folder must never become its own descendant - that would detach the whole
            // branch from the tree. The path prefix answers this without a recursive walk.
            if (newParent.getId().equals(folder.getId())
                    || (newParent.getPath() != null && newParent.getPath().startsWith(folder.getPath()))) {
                throw new IllegalArgumentException("Cannot move a folder into itself or one of its subfolders.");
            }
        }

        if (slugTakenByOther(newParentId, folder.getSlug(), folder.getId())) {
            throw new IllegalArgumentException(
                    "A folder named \"" + folder.getName() + "\" already exists in the destination.");
        }

        List<MediaFolder> subtree = mediaFolderRepository.findSubtree(folder.getPath());
        int newDepth = newParent == null ? 0 : newParent.getDepth() + 1;
        int delta = newDepth - folder.getDepth();

        int deepest = subtree.stream().mapToInt(MediaFolder::getDepth).max().orElse(folder.getDepth());
        if (deepest + delta > MAX_DEPTH) {
            throw new IllegalArgumentException(
                    "Cannot move: folders can only be nested " + MAX_DEPTH + " levels deep.");
        }

        String oldPath = folder.getPath();
        String newPath = pathOf(newParent, folder.getId());
        StoragePath oldDir = layout.directoryOf(folder);
        StoragePath newDir = layout.directoryOf(newParent).resolve(folder.getSlug());

        folder.setParent(newParent);
        folder.setDepth(newDepth);
        folder.setPath(newPath);
        mediaFolderRepository.save(folder);

        // Rewriting only the moved node would leave every descendant pointing at a path
        // that no longer exists, so the whole subtree is rewritten.
        for (MediaFolder descendant : subtree) {
            if (descendant.getId().equals(folder.getId())) {
                continue;
            }
            descendant.setPath(newPath + descendant.getPath().substring(oldPath.length()));
            descendant.setDepth(descendant.getDepth() + delta);
            mediaFolderRepository.save(descendant);
        }
        mediaFolderRepository.flush();
        // Last: its bulk updates clear the persistence context.
        layout.moveDirectory(oldDir, newDir);
        return folder;
    }

    @Override
    @Transactional
    public FolderDeleteResult deleteFolder(Long id) {
        MediaFolder folder = mediaFolderRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("Folder not found with id: " + id));
        if (folder.getStatus() == MediaFolder.Status.TRASHED) {
            return new FolderDeleteResult(List.of(), List.of()); // already in the trash
        }

        // Like a desktop file manager: deleting a folder takes everything inside it along -
        // subfolders at any depth and their files - into the trash. All of it shares one
        // deleted_at so restoreFolder() can bring back exactly this batch.
        //
        // Files go to the trash WITH their folder. Soft-deleting leaves the folder row in place,
        // so the ON DELETE SET NULL FK never fires; a file left pointing at a trashed folder would
        // vanish from the grid without showing up in the trash either. deleteMedia() keeps the
        // cover/avatar guard, so a subtree holding a file still in use is refused as a whole
        // (the exception rolls back the entire transaction).
        LocalDateTime deletedAt = LocalDateTime.now();
        List<Long> folderIds = new java.util.ArrayList<>();
        List<Long> mediaIds = new java.util.ArrayList<>();
        for (MediaFolder node : mediaFolderRepository.findSubtreeByStatus(folder.getPath(), MediaFolder.Status.ACTIVE)) {
            for (Media media : mediaRepository.findByFolderIdAndStatus(node.getId(), Media.Status.ACTIVE)) {
                mediaService.deleteMedia(media.getId());
                mediaIds.add(media.getId());
            }
            node.setStatus(MediaFolder.Status.TRASHED);
            node.setDeletedAt(deletedAt);
            mediaFolderRepository.save(node);
            folderIds.add(node.getId());
        }
        return new FolderDeleteResult(folderIds, mediaIds);
    }

    @Override
    @Transactional(readOnly = true)
    public List<MediaFolder> getTrashedFolders() {
        return mediaFolderRepository.findByStatusOrderByDeletedAtDesc(MediaFolder.Status.TRASHED);
    }

    @Override
    @Transactional
    public FolderRestoreResult restoreFolder(Long id) {
        MediaFolder folder = mediaFolderRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("Folder not found with id: " + id));
        if (folder.getStatus() != MediaFolder.Status.TRASHED) {
            throw new IllegalArgumentException("This folder is not in the trash.");
        }
        LocalDateTime batchDeletedAt = folder.getDeletedAt();

        MediaFolder parent = folder.getParent();
        boolean movedToRoot = false;
        if (parent != null && parent.getStatus() != MediaFolder.Status.ACTIVE) {
            // Parent purged or still trashed: re-attach at the root rather than restoring
            // into something the user cannot see.
            parent = null;
            movedToRoot = true;
        }
        if (movedToRoot) {
            if (slugTakenByOther(null, folder.getSlug(), folder.getId())) {
                throw new IllegalArgumentException(
                        "Cannot restore: a folder named \"" + folder.getName() + "\" already exists at Home.");
            }
            StoragePath oldDir = layout.directoryOf(folder);
            rehome(folder, null);
            mediaFolderRepository.saveAndFlush(folder);
            layout.moveDirectory(oldDir, StoragePath.of(folder.getSlug()));
            folder = mediaFolderRepository.findById(id).orElseThrow();
        }

        // Bring back the whole batch that went to the trash with this folder: its subfolders
        // and every file inside them. Anything trashed on its own EARLIER keeps an older
        // deleted_at and stays in the trash. The cutoff must be captured before deleted_at
        // is cleared below - reading it afterwards always yields null and restores everything.
        LocalDateTime cutoff = batchDeletedAt == null ? null : batchDeletedAt.minusSeconds(2);
        MediaFolder saved = null;
        for (MediaFolder node : mediaFolderRepository.findSubtreeByStatus(folder.getPath(), MediaFolder.Status.TRASHED)) {
            boolean self = node.getId().equals(folder.getId());
            if (!self && !inBatch(node.getDeletedAt(), cutoff)) {
                continue;
            }
            for (Media media : mediaRepository.findByFolderIdAndStatus(node.getId(), Media.Status.TRASHED)) {
                if (!inBatch(media.getDeletedAt(), cutoff)) {
                    continue;
                }
                media.setStatus(Media.Status.ACTIVE);
                media.setDeletedAt(null);
                mediaRepository.save(media);
            }
            node.setStatus(MediaFolder.Status.ACTIVE);
            node.setDeletedAt(null);
            MediaFolder persisted = mediaFolderRepository.save(node);
            if (self) {
                saved = persisted;
            }
        }
        return new FolderRestoreResult(saved, movedToRoot);
    }

    /** True when something trashed at {@code deletedAt} belongs to the batch starting at {@code cutoff}. */
    private static boolean inBatch(LocalDateTime deletedAt, LocalDateTime cutoff) {
        return cutoff == null || deletedAt == null || !deletedAt.isBefore(cutoff);
    }

    /** Re-parents a folder, shifting the path/depth of every descendant row with it. */
    private void rehome(MediaFolder folder, MediaFolder newParent) {
        String oldPath = folder.getPath();
        String newPath = pathOf(newParent, folder.getId());
        int newDepth = newParent == null ? 0 : newParent.getDepth() + 1;
        int delta = newDepth - folder.getDepth();
        folder.setParent(newParent);
        folder.setDepth(newDepth);
        folder.setPath(newPath);
        for (MediaFolder descendant : mediaFolderRepository.findSubtree(oldPath)) {
            if (descendant.getId().equals(folder.getId())) {
                continue;
            }
            descendant.setPath(newPath + descendant.getPath().substring(oldPath.length()));
            descendant.setDepth(descendant.getDepth() + delta);
            mediaFolderRepository.save(descendant);
        }
    }

    @Override
    @Transactional
    public void purgeFolder(Long id) {
        MediaFolder folder = mediaFolderRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("Folder not found with id: " + id));
        if (folder.getStatus() != MediaFolder.Status.TRASHED) {
            throw new IllegalArgumentException(
                    "Cannot permanently delete: move the folder to the trash first.");
        }
        // The subtree went to the trash together, so it is purged together. A subfolder that was
        // restored on its own since then is live data: refuse rather than destroy it.
        List<MediaFolder> descendants = mediaFolderRepository.findDescendants(folder.getPath(), id);
        if (descendants.stream().anyMatch(d -> d.getStatus() == MediaFolder.Status.ACTIVE)) {
            throw new IllegalArgumentException(
                    "Cannot permanently delete: a subfolder inside it is no longer in the trash. Move it out first.");
        }
        // The parent FK is ON DELETE RESTRICT, so rows go deepest-first. Files still trashed inside
        // are unfiled and remain in the trash at the root (moved to uploads/ on disk as well), and
        // each emptied directory is removed after commit. A folder purge never destroys files implicitly.
        descendants.sort(java.util.Comparator.comparingInt(MediaFolder::getDepth).reversed());
        for (MediaFolder descendant : descendants) {
            layout.purgeFolder(descendant);
            mediaFolderRepository.flush();
        }
        layout.purgeFolder(folder);
    }
}
