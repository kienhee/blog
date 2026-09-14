package com.kienhee.blog.service;

import com.kienhee.blog.dto.MediaFolderCreateRequest;
import com.kienhee.blog.entity.MediaFolder;

import java.util.List;

public interface MediaFolderService {

    /** Hard cap on nesting: a folder's depth (root = 0) may never exceed this. */
    int MAX_DEPTH = 10;

    List<MediaFolder> getAllFolders();

    /**
     * The whole tree flattened depth-first, children sorted by name — the order the
     * sidebar renders in. Every element carries its indentation depth, so the template
     * never has to touch {@code folder.parent} (LAZY, open-in-view is off).
     */
    List<FolderNode> getFolderTree();

    /**
     * Ancestors of {@code folderId} from the root down to the folder itself. Built from
     * the materialized path with a single {@code findAllById}, never one query per level.
     * Returns an empty list for {@code null} (= Home).
     */
    List<MediaFolder> getBreadcrumb(Long folderId);

    MediaFolder createFolder(MediaFolderCreateRequest request);

    MediaFolder renameFolder(Long id, String name);

    /** Re-parents a folder and rewrites {@code path}/{@code depth} for its whole subtree. */
    MediaFolder moveFolder(Long id, Long newParentId);

    /**
     * <b>Soft</b> delete: the folder goes to the trash and every file still inside goes
     * with it (see {@code MediaService#deleteMedia}). Refused while the folder still has
     * live subfolders, and refused if one of its files is in use as a cover/avatar.
     *
     * <p>Design note: the files are cascaded into the trash rather than unfiled to the
     * root. The folder row survives a soft delete, so the {@code ON DELETE SET NULL} FK
     * never fires; leaving the files pointing at a trashed folder would make them
     * invisible in the grid (their folder is gone from the tree) yet absent from the
     * trash — the classic "ghost file". Cascading keeps every file in exactly one visible
     * place, and restoring the folder brings them back with it.
     */
    FolderDeleteResult deleteFolder(Long id);

    /** Folders currently in the trash, newest deletion first. */
    List<MediaFolder> getTrashedFolders();

    /**
     * Restores a trashed folder and the files that went to the trash with it. If the
     * parent folder is gone or still trashed, the folder is re-attached to the root —
     * {@link FolderRestoreResult#movedToRoot()} reports that so the caller can say so.
     */
    FolderRestoreResult restoreFolder(Long id);

    /**
     * <b>Hard</b> delete of a folder that is already in the trash. Only the folder row is
     * removed; files still trashed inside it are unfiled by the {@code ON DELETE SET NULL}
     * FK and stay in the trash at the root, so nothing is destroyed implicitly.
     */
    void purgeFolder(Long id);

    /**
     * Finds the folder with this name (case-insensitive), creating it if it doesn't exist yet.
     * Used by features that file uploads into a well-known folder automatically (e.g. avatars).
     */
    MediaFolder getOrCreateByName(String name);

    /** @param movedToRoot true when the parent folder was gone and the folder was re-attached to the root */
    /** Ids of everything one folder delete sent to the trash, so the explorer can drop them in place. */
    record FolderDeleteResult(List<Long> folderIds, List<Long> mediaIds) {
    }

    record FolderRestoreResult(MediaFolder folder, boolean movedToRoot) {
    }

    /** A folder plus its indentation depth, for rendering the tree without lazy loading. */
    record FolderNode(MediaFolder folder, int depth) {
        public Long getId() {
            return folder.getId();
        }

        public String getName() {
            return folder.getName();
        }

        public MediaFolder getFolder() {
            return folder;
        }

        public int getDepth() {
            return depth;
        }
    }
}
