package com.kienhee.blog.service;

import com.kienhee.blog.dto.MediaUpdateRequest;
import com.kienhee.blog.entity.Media;
import org.springframework.web.multipart.MultipartFile;

import java.util.List;
import java.util.Set;

public interface MediaService {

    List<Media> getAllMedia();

    /**
     * URLs of media currently used as a post cover image — for flagging unused ("orphan") files.
     */
    Set<String> getUsedMediaUrls();

    Media uploadMedia(MultipartFile file, String uploaderEmail, Long folderId);

    Media updateMedia(Long id, MediaUpdateRequest request);

    /**
     * <b>Soft</b> delete: moves the file to the trash ({@code status = TRASHED},
     * {@code deleted_at = now}). The bytes stay on disk, the blob's ref_count is untouched
     * and the uploader's quota is NOT given back — trashed files still occupy quota, or
     * "delete temporarily" would become a way to dodge the limit.
     *
     * <p>A file currently used as a post cover or a profile photo is refused here rather
     * than at purge time: hiding it from the library already breaks the public page.
     */
    void deleteMedia(Long id);

    /** Everything currently in the trash, newest deletion first. */
    List<Media> getTrashedMedia();

    /**
     * Brings a trashed file back. If its folder is gone (or itself trashed) the file
     * lands at the root instead — {@link RestoreResult#movedToRoot()} says so, and the
     * caller must tell the user.
     */
    RestoreResult restoreMedia(Long id);

    /**
     * <b>Hard</b> delete of a file that is already in the trash: decrement the blob's
     * ref_count, remove the physical file once nothing points at it any more, drop the
     * row and finally give the bytes back to the uploader's quota. Irreversible.
     */
    void purgeMedia(Long id);

    /** Purges every trashed file and folder. Best-effort, same reporting as bulk delete. */
    BulkDeleteResult emptyTrash();

    /**
     * Purges everything that has been in the trash longer than {@code retentionDays}.
     * Returns how many items were actually purged.
     */
    int purgeExpired(int retentionDays);

    /** Counters for the trash UI, including the quota those bytes still occupy. */
    TrashSummary getTrashSummary();

    /**
     * Deletes each id best-effort; ids that fail (not found, still in use) are skipped
     * and reported back rather than aborting the whole batch.
     */
    BulkDeleteResult bulkDeleteMedia(List<Long> ids);

    /**
     * Assigns every given media id to the folder (or unfiles them when folderId is null).
     */
    void bulkMoveToFolder(List<Long> ids, Long folderId);

    record BulkDeleteResult(int deletedCount, List<String> errors) {
    }

    /** @param movedToRoot true when the original folder no longer exists and the file was unfiled */
    record RestoreResult(Media media, boolean movedToRoot) {
    }

    /**
     * @param fileCount   trashed files
     * @param folderCount trashed folders
     * @param totalBytes  logical bytes those files still take out of the owners' quota
     */
    record TrashSummary(long fileCount, long folderCount, long totalBytes) {
    }
}
