package com.kienhee.blog.support;

import com.kienhee.blog.entity.MediaFolder;
import com.kienhee.blog.repository.MediaFolderRepository;
import com.kienhee.blog.service.impl.MediaStorageLayout;
import com.kienhee.blog.storage.FilesystemStorage;
import com.kienhee.blog.storage.StoragePath;

import java.util.ArrayList;
import java.util.Collection;
import java.util.Comparator;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

/**
 * Since folders mirror real directories under {@code uploads/}, deleting a test's folder rows is
 * not enough: the (by then empty) directories have to go too. Directories are resolved before
 * any row is removed (the path needs the ancestors' slugs), then removed deepest first and only
 * if empty — never recursively.
 */
public final class MediaFolderCleanup {

    private MediaFolderCleanup() {
    }

    public static void deleteFoldersAndDirectories(Collection<Long> folderIds,
                                                   MediaFolderRepository repository,
                                                   MediaStorageLayout layout,
                                                   FilesystemStorage storage) {
        List<MediaFolder> rows = new ArrayList<>();
        for (Long id : folderIds) {
            repository.findById(id).ifPresent(rows::add);
        }
        rows.sort(Comparator.comparingInt(MediaFolder::getDepth).reversed());

        Map<MediaFolder, StoragePath> dirs = new LinkedHashMap<>();
        for (MediaFolder folder : rows) {
            try {
                dirs.put(folder, layout.directoryOf(folder));
            } catch (RuntimeException e) {
                dirs.put(folder, null);
            }
        }
        for (Map.Entry<MediaFolder, StoragePath> entry : dirs.entrySet()) {
            try {
                repository.delete(entry.getKey());
            } catch (RuntimeException ignored) {
                // best effort
            }
        }
        dirs.values().stream()
                .filter(p -> p != null && !p.isRoot())
                .sorted(Comparator.comparingInt(StoragePath::depth).reversed())
                .forEach(p -> {
                    try {
                        storage.deleteDirectoryIfEmpty(p);
                    } catch (RuntimeException ignored) {
                        // not empty / already gone
                    }
                });
    }
}
