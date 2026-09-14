package com.kienhee.blog.storage;

import java.io.InputStream;
import java.nio.file.Path;
import java.util.List;

/**
 * Path-oriented storage: the disk mirrors the media folder tree, so {@code uploads/} looks and
 * behaves like an ordinary directory tree ({@code uploads/anh-du-lich/bien/hoang-hon.jpg}).
 *
 * <p>This replaces the flat, opaque-key {@link StorageAdapter} model for the media module. Every
 * argument is a {@link StoragePath} relative to the storage root; implementations must resolve it
 * through a single guarded routine and reject anything that escapes the root, even though
 * {@link StoragePath} already rejects {@code ..} (defence in depth: symlinks and OS-level
 * normalisation differences are not visible to the value object).</p>
 *
 * <p>All failures are normalised to {@link StorageException}.</p>
 *
 * <p><b>Ordering rule from the contract:</b> callers write/move on disk <em>first</em> and commit
 * the DB second, compensating with a reverse move on rollback. Nothing in this interface performs
 * that orchestration; it only provides the primitives.</p>
 */
public interface FilesystemStorage {

    /**
     * Writes {@code in} to {@code path}, creating parent directories as needed and replacing any
     * existing file.
     *
     * <p>Atomic from a reader's point of view: bytes go to a sibling {@code .part} file which is
     * then moved into place, so a concurrent reader sees either the old file or the complete new
     * one. The caller's stream is <b>not</b> closed (the upload pipeline is still hashing it).</p>
     *
     * @param path      target file path, must not be the root
     * @param in        source stream, read to EOF but never closed by the implementation
     * @param sizeBytes expected size, or negative if unknown; advisory only for local disk
     * @return the number of bytes actually written
     * @throws StorageException on I/O failure or an unsafe path
     */
    long writeFile(StoragePath path, InputStream in, long sizeBytes);

    /**
     * Opens {@code path} for reading. The caller owns and must close the stream.
     *
     * @throws StorageException if the file does not exist or cannot be read
     */
    InputStream readFile(StoragePath path);

    /**
     * Deletes the file at {@code path}.
     *
     * <p><b>Idempotent</b>: a missing file is not an error (purge may be retried by a sweeper).</p>
     *
     * @return {@code true} if a file was actually removed
     * @throws StorageException if the path exists but is a directory, or cannot be deleted
     */
    boolean deleteFile(StoragePath path);

    /** @return {@code true} if a <em>regular file</em> exists at {@code path}. */
    boolean fileExists(StoragePath path);

    /** @return {@code true} if a directory exists at {@code path}. */
    boolean directoryExists(StoragePath path);

    /**
     * Creates the directory at {@code path} including any missing parents. A no-op if it already
     * exists.
     *
     * @throws StorageException if a non-directory already occupies the path
     */
    void createDirectory(StoragePath path);

    /**
     * Moves (renames) a file. Parent directories of {@code to} are created. An existing file at
     * {@code to} is replaced — callers wanting Windows-style {@code (2)} suffixes must resolve the
     * name with {@link FilenameSanitizer#sanitizeUnique} first.
     *
     * @throws StorageException if {@code from} is missing or either path is unsafe
     */
    void moveFile(StoragePath from, StoragePath to);

    /**
     * Moves a directory with its entire contents (folder rename and folder move both land here).
     *
     * <p>Tries a plain rename first (atomic within one volume) and falls back to a recursive
     * copy-then-delete across volumes. Moving a directory <em>into its own descendant</em> is
     * rejected — that would either loop forever or destroy the subtree.</p>
     *
     * @throws StorageException if {@code from} is missing, {@code to} already exists, or
     *                          {@code to} is inside {@code from}
     */
    void moveDirectory(StoragePath from, StoragePath to);

    /**
     * Deletes the directory at {@code path} <b>only if it is empty</b>. Never recursive — silent
     * recursive deletion is the worst failure mode this module could have, so it is simply not
     * offered. A missing directory is a no-op.
     *
     * @return {@code true} if a directory was removed
     * @throws StorageException if the directory is not empty, or the path is a file
     */
    boolean deleteDirectoryIfEmpty(StoragePath path);

    /**
     * Lists the immediate children of a directory (files and subdirectories), not recursive.
     *
     * @return child paths relative to the storage root; empty if the directory does not exist
     * @throws StorageException if the path is a file or cannot be read
     */
    List<StoragePath> listDirectory(StoragePath path);

    /**
     * Resolves a storage path to an absolute filesystem path, applying the same escape check as
     * every other method.
     *
     * <p>Provider-specific by nature: only the controller that streams bytes back
     * ({@code /media/{id}}) should need it. Do not persist the result.</p>
     */
    Path resolveAbsolute(StoragePath path);

    /**
     * @return the size in bytes of the file at {@code path}
     * @throws StorageException if the file does not exist
     */
    long sizeOf(StoragePath path);
}
