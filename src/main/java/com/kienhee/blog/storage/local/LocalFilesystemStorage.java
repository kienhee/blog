package com.kienhee.blog.storage.local;

import com.kienhee.blog.config.UploadProperties;
import com.kienhee.blog.storage.FilesystemStorage;
import com.kienhee.blog.storage.StorageException;
import com.kienhee.blog.storage.StoragePath;
import lombok.extern.slf4j.Slf4j;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Component;

import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.nio.file.AtomicMoveNotSupportedException;
import java.nio.file.DirectoryNotEmptyException;
import java.nio.file.DirectoryStream;
import java.nio.file.FileAlreadyExistsException;
import java.nio.file.Files;
import java.nio.file.InvalidPathException;
import java.nio.file.NoSuchFileException;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.nio.file.SimpleFileVisitor;
import java.nio.file.StandardCopyOption;
import java.nio.file.attribute.BasicFileAttributes;
import java.util.ArrayList;
import java.util.Comparator;
import java.util.List;
import java.util.stream.Stream;

/**
 * {@link FilesystemStorage} backed by the local disk, rooted at {@code app.upload.dir}.
 *
 * <p>The root is resolved to an absolute, normalised {@link Path} exactly once, in the
 * constructor; every public method funnels its arguments through {@link #resolveSafely} so there
 * is a single place where "does this still live under the root?" is decided. Adding a method that
 * calls {@code root.resolve(...)} directly would defeat the whole containment guarantee.</p>
 *
 * <p>Coexists with the legacy {@code LocalStorageAdapter} during the migration; both point at the
 * same root directory but this one addresses files by their real folder path.</p>
 */
@Slf4j
@Component
public class LocalFilesystemStorage implements FilesystemStorage {

    private static final String TEMP_SUFFIX = ".part";

    private final Path root;

    // Two constructors exist (the private one backs forRoot() for tests), so Spring must be told which to use.
    @Autowired
    public LocalFilesystemStorage(UploadProperties uploadProperties) {
        this.root = Paths.get(uploadProperties.getDir()).toAbsolutePath().normalize();
    }

    private LocalFilesystemStorage(Path root, boolean direct) {
        this.root = root.toAbsolutePath().normalize();
    }

    /**
     * Binds a root directory directly (tests, tooling). A static factory rather than a second
     * constructor so Spring never has to choose between two candidate constructors.
     */
    public static LocalFilesystemStorage forRoot(Path root) {
        return new LocalFilesystemStorage(root, true);
    }

    /**
     * The only path resolution in this class. Normalises the candidate and re-checks containment
     * against the root, so neither an OS-specific quirk nor a future caller-supplied path can
     * reach outside {@code uploads/}.
     */
    private Path resolveSafely(StoragePath path) {
        if (path == null) {
            throw new StorageException("Storage path must not be null");
        }
        Path resolved;
        try {
            resolved = root.resolve(path.toString()).normalize();
        } catch (InvalidPathException e) {
            throw new StorageException("Invalid storage path: " + path, e);
        }
        if (!resolved.startsWith(root)) {
            throw new StorageException("Storage path escapes the storage root: " + path);
        }
        return resolved;
    }

    private void requireNotRoot(StoragePath path, String operation) {
        if (path == null || path.isRoot()) {
            throw new StorageException("Cannot " + operation + " the storage root itself");
        }
    }

    @Override
    public long writeFile(StoragePath path, InputStream in, long sizeBytes) {
        requireNotRoot(path, "write");
        if (in == null) {
            throw new StorageException("Source stream must not be null");
        }
        Path target = resolveSafely(path);
        Path parent = target.getParent();
        Path tmp = null;
        try {
            Files.createDirectories(parent);
            // Temp file lives in the destination directory so the final move stays on the same
            // filesystem and can therefore be atomic.
            tmp = Files.createTempFile(parent, target.getFileName().toString(), TEMP_SUFFIX);
            long written;
            try (OutputStream out = Files.newOutputStream(tmp)) {
                written = in.transferTo(out); // deliberately does not close `in`
            }
            try {
                Files.move(tmp, target, StandardCopyOption.REPLACE_EXISTING,
                        StandardCopyOption.ATOMIC_MOVE);
            } catch (AtomicMoveNotSupportedException e) {
                Files.move(tmp, target, StandardCopyOption.REPLACE_EXISTING);
            }
            return written;
        } catch (IOException e) {
            deleteQuietly(tmp);
            throw new StorageException("Failed to write file: " + path, e);
        } catch (RuntimeException e) {
            deleteQuietly(tmp);
            throw e;
        }
    }

    @Override
    public InputStream readFile(StoragePath path) {
        requireNotRoot(path, "read");
        Path target = resolveSafely(path);
        try {
            return Files.newInputStream(target);
        } catch (NoSuchFileException e) {
            throw new StorageException("File not found: " + path, e);
        } catch (IOException e) {
            throw new StorageException("Failed to read file: " + path, e);
        }
    }

    @Override
    public boolean deleteFile(StoragePath path) {
        requireNotRoot(path, "delete");
        Path target = resolveSafely(path);
        if (Files.isDirectory(target)) {
            throw new StorageException("Refusing to delete a directory as a file: " + path);
        }
        try {
            return Files.deleteIfExists(target);
        } catch (IOException e) {
            throw new StorageException("Failed to delete file: " + path, e);
        }
    }

    @Override
    public boolean fileExists(StoragePath path) {
        if (path == null || path.isRoot()) {
            return false;
        }
        return Files.isRegularFile(resolveSafely(path));
    }

    @Override
    public boolean directoryExists(StoragePath path) {
        return Files.isDirectory(resolveSafely(path));
    }

    @Override
    public void createDirectory(StoragePath path) {
        Path target = resolveSafely(path);
        if (Files.exists(target) && !Files.isDirectory(target)) {
            throw new StorageException("A file already exists at: " + path);
        }
        try {
            Files.createDirectories(target);
        } catch (IOException e) {
            throw new StorageException("Failed to create directory: " + path, e);
        }
    }

    @Override
    public void moveFile(StoragePath from, StoragePath to) {
        requireNotRoot(from, "move");
        requireNotRoot(to, "move to");
        Path source = resolveSafely(from);
        Path target = resolveSafely(to);
        if (source.equals(target)) {
            return;
        }
        if (!Files.exists(source)) {
            throw new StorageException("File not found: " + from);
        }
        if (Files.isDirectory(source)) {
            throw new StorageException("Not a file, use moveDirectory: " + from);
        }
        try {
            Files.createDirectories(target.getParent());
            try {
                Files.move(source, target, StandardCopyOption.REPLACE_EXISTING,
                        StandardCopyOption.ATOMIC_MOVE);
            } catch (AtomicMoveNotSupportedException e) {
                Files.move(source, target, StandardCopyOption.REPLACE_EXISTING);
            }
        } catch (IOException e) {
            throw new StorageException("Failed to move file " + from + " -> " + to, e);
        }
    }

    @Override
    public void moveDirectory(StoragePath from, StoragePath to) {
        requireNotRoot(from, "move");
        requireNotRoot(to, "move to");
        if (from.equals(to)) {
            return;
        }
        // A directory cannot be moved inside itself: the copy fallback would recurse forever and
        // the rename would orphan the subtree.
        if (from.isAncestorOf(to)) {
            throw new StorageException(
                    "Cannot move a directory into its own descendant: " + from + " -> " + to);
        }
        Path source = resolveSafely(from);
        Path target = resolveSafely(to);
        if (!Files.isDirectory(source)) {
            throw new StorageException("Directory not found: " + from);
        }
        if (Files.exists(target)) {
            throw new StorageException("Target already exists: " + to);
        }
        try {
            Files.createDirectories(target.getParent());
        } catch (IOException e) {
            throw new StorageException("Failed to prepare target for: " + to, e);
        }
        try {
            Files.move(source, target, StandardCopyOption.ATOMIC_MOVE);
            return;
        } catch (AtomicMoveNotSupportedException | FileAlreadyExistsException e) {
            log.debug("Atomic directory move unavailable for {} -> {}, copying", from, to);
        } catch (IOException e) {
            log.debug("Directory rename failed for {} -> {}, copying: {}", from, to, e.toString());
        }
        copyRecursively(source, target, from, to);
        deleteRecursively(source, from);
    }

    private void copyRecursively(Path source, Path target, StoragePath from, StoragePath to) {
        try {
            Files.walkFileTree(source, new SimpleFileVisitor<Path>() {
                @Override
                public java.nio.file.FileVisitResult preVisitDirectory(
                        Path dir, BasicFileAttributes attrs) throws IOException {
                    Files.createDirectories(target.resolve(source.relativize(dir)));
                    return java.nio.file.FileVisitResult.CONTINUE;
                }

                @Override
                public java.nio.file.FileVisitResult visitFile(
                        Path file, BasicFileAttributes attrs) throws IOException {
                    Files.copy(file, target.resolve(source.relativize(file)),
                            StandardCopyOption.REPLACE_EXISTING);
                    return java.nio.file.FileVisitResult.CONTINUE;
                }
            });
        } catch (IOException e) {
            throw new StorageException("Failed to copy directory " + from + " -> " + to, e);
        }
    }

    /**
     * Only ever used on the <em>source</em> of a completed cross-volume copy, never exposed:
     * recursive deletion must not be reachable from the public API.
     */
    private void deleteRecursively(Path dir, StoragePath original) {
        try (Stream<Path> walk = Files.walk(dir)) {
            List<Path> entries = walk.sorted(Comparator.reverseOrder()).toList();
            for (Path entry : entries) {
                Files.deleteIfExists(entry);
            }
        } catch (IOException e) {
            throw new StorageException(
                    "Directory copied but the original could not be removed: " + original, e);
        }
    }

    @Override
    public boolean deleteDirectoryIfEmpty(StoragePath path) {
        requireNotRoot(path, "delete");
        Path target = resolveSafely(path);
        if (!Files.exists(target)) {
            return false;
        }
        if (!Files.isDirectory(target)) {
            throw new StorageException("Not a directory: " + path);
        }
        try {
            Files.delete(target);
            return true;
        } catch (DirectoryNotEmptyException e) {
            throw new StorageException("Directory is not empty: " + path, e);
        } catch (IOException e) {
            throw new StorageException("Failed to delete directory: " + path, e);
        }
    }

    @Override
    public List<StoragePath> listDirectory(StoragePath path) {
        Path target = resolveSafely(path);
        if (!Files.exists(target)) {
            return List.of();
        }
        if (!Files.isDirectory(target)) {
            throw new StorageException("Not a directory: " + path);
        }
        List<StoragePath> children = new ArrayList<>();
        try (DirectoryStream<Path> stream = Files.newDirectoryStream(target)) {
            for (Path child : stream) {
                children.add(path.resolve(child.getFileName().toString()));
            }
        } catch (IOException e) {
            throw new StorageException("Failed to list directory: " + path, e);
        }
        children.sort(Comparator.comparing(StoragePath::toString));
        return children;
    }

    @Override
    public Path resolveAbsolute(StoragePath path) {
        return resolveSafely(path);
    }

    @Override
    public long sizeOf(StoragePath path) {
        requireNotRoot(path, "size");
        Path target = resolveSafely(path);
        try {
            return Files.size(target);
        } catch (NoSuchFileException e) {
            throw new StorageException("File not found: " + path, e);
        } catch (IOException e) {
            throw new StorageException("Failed to stat file: " + path, e);
        }
    }

    private void deleteQuietly(Path p) {
        if (p == null) {
            return;
        }
        try {
            Files.deleteIfExists(p);
        } catch (IOException ignored) {
            log.warn("Could not remove temp file {}", p);
        }
    }
}
