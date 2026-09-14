package com.kienhee.blog.storage;

import com.kienhee.blog.storage.local.LocalFilesystemStorage;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Nested;
import org.junit.jupiter.api.Test;
import org.junit.jupiter.api.io.TempDir;
import org.junit.jupiter.params.ParameterizedTest;
import org.junit.jupiter.params.provider.ValueSource;

import java.io.ByteArrayInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.util.List;
import java.util.function.Consumer;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertFalse;
import static org.junit.jupiter.api.Assertions.assertThrows;
import static org.junit.jupiter.api.Assertions.assertTrue;

/**
 * Plain JUnit 5 with {@link TempDir} — no Spring context and no database, so this suite runs in
 * well under a second and can be re-run on every change to the storage layer.
 */
class LocalFilesystemStorageTests {

    @TempDir
    Path rootDir;

    private LocalFilesystemStorage storage;

    @BeforeEach
    void setUp() {
        storage = LocalFilesystemStorage.forRoot(rootDir);
    }

    private static InputStream bytes(String content) {
        return new ByteArrayInputStream(content.getBytes(StandardCharsets.UTF_8));
    }

    private String read(StoragePath path) throws IOException {
        try (InputStream in = storage.readFile(path)) {
            return new String(in.readAllBytes(), StandardCharsets.UTF_8);
        }
    }

    @Nested
    @DisplayName("Write / read / delete")
    class BasicIo {

        @Test
        void writesAndReadsInsideNestedDirectories() throws IOException {
            StoragePath path = StoragePath.of("anh-du-lich/bien/hoang-hon.jpg");
            long written = storage.writeFile(path, bytes("sunset"), 6);

            assertEquals(6, written);
            assertTrue(storage.fileExists(path));
            assertEquals("sunset", read(path));
            assertEquals(6, storage.sizeOf(path));
            assertTrue(Files.isRegularFile(rootDir.resolve("anh-du-lich/bien/hoang-hon.jpg")));
        }

        @Test
        void overwriteReplacesContentAndLeavesNoPartFile() throws IOException {
            StoragePath path = StoragePath.of("docs/bao-cao.txt");
            storage.writeFile(path, bytes("v1"), 2);
            storage.writeFile(path, bytes("version two"), 11);

            assertEquals("version two", read(path));
            try (var stream = Files.list(rootDir.resolve("docs"))) {
                assertEquals(1, stream.count(), "a leftover .part file was found");
            }
        }

        @Test
        void doesNotCloseCallerStream() {
            var tracker = new Object() {
                boolean closed = false;
            };
            InputStream in = new ByteArrayInputStream("abc".getBytes(StandardCharsets.UTF_8)) {
                @Override
                public void close() {
                    tracker.closed = true;
                }
            };
            storage.writeFile(StoragePath.of("a.txt"), in, 3);
            assertFalse(tracker.closed, "storage must not close the caller's stream");
        }

        @Test
        void deleteIsIdempotent() {
            StoragePath path = StoragePath.of("folder/x.txt");
            storage.writeFile(path, bytes("x"), 1);

            assertTrue(storage.deleteFile(path));
            assertFalse(storage.deleteFile(path));
            assertFalse(storage.fileExists(path));
        }

        @Test
        void readingMissingFileThrows() {
            assertThrows(StorageException.class, () -> storage.readFile(StoragePath.of("nope.txt")));
            assertThrows(StorageException.class, () -> storage.sizeOf(StoragePath.of("nope.txt")));
        }

        @Test
        void deletingADirectoryAsAFileIsRefused() {
            storage.createDirectory(StoragePath.of("a/b"));
            assertThrows(StorageException.class, () -> storage.deleteFile(StoragePath.of("a/b")));
            assertTrue(storage.directoryExists(StoragePath.of("a/b")));
        }
    }

    @Nested
    @DisplayName("Directories")
    class Directories {

        @Test
        void createsNestedDirectoriesAndLists() {
            storage.createDirectory(StoragePath.of("a/b/c"));
            storage.writeFile(StoragePath.of("a/one.txt"), bytes("1"), 1);
            storage.writeFile(StoragePath.of("a/two.txt"), bytes("2"), 1);

            List<StoragePath> children = storage.listDirectory(StoragePath.of("a"));
            assertEquals(List.of(StoragePath.of("a/b"), StoragePath.of("a/one.txt"),
                    StoragePath.of("a/two.txt")), children);
        }

        @Test
        void listingMissingDirectoryIsEmpty() {
            assertEquals(List.of(), storage.listDirectory(StoragePath.of("ghost")));
        }

        @Test
        void listsTheRoot() {
            storage.writeFile(StoragePath.of("home.txt"), bytes("h"), 1);
            assertEquals(List.of(StoragePath.of("home.txt")), storage.listDirectory(StoragePath.root()));
        }

        @Test
        void deleteDirectoryIfEmptyRefusesNonEmptyDirectory() {
            StoragePath dir = StoragePath.of("keep");
            storage.writeFile(dir.resolve("file.txt"), bytes("data"), 4);

            assertThrows(StorageException.class, () -> storage.deleteDirectoryIfEmpty(dir));
            assertTrue(storage.directoryExists(dir));
            assertTrue(storage.fileExists(dir.resolve("file.txt")), "contents must survive");
        }

        @Test
        void deleteDirectoryIfEmptyRemovesEmptyDirectoryAndIsIdempotent() {
            StoragePath dir = StoragePath.of("empty");
            storage.createDirectory(dir);

            assertTrue(storage.deleteDirectoryIfEmpty(dir));
            assertFalse(storage.directoryExists(dir));
            assertFalse(storage.deleteDirectoryIfEmpty(dir));
        }
    }

    @Nested
    @DisplayName("Moves")
    class Moves {

        @Test
        void movesFileBetweenDirectories() throws IOException {
            StoragePath from = StoragePath.of("src/anh.jpg");
            StoragePath to = StoragePath.of("dst/nested/anh.jpg");
            storage.writeFile(from, bytes("pixels"), 6);

            storage.moveFile(from, to);

            assertFalse(storage.fileExists(from));
            assertTrue(storage.fileExists(to));
            assertEquals("pixels", read(to));
        }

        @Test
        void movingMissingFileThrows() {
            assertThrows(StorageException.class,
                    () -> storage.moveFile(StoragePath.of("a.txt"), StoragePath.of("b.txt")));
        }

        @Test
        void movesDirectoryWithItsContents() throws IOException {
            storage.writeFile(StoragePath.of("old/bien/hoang-hon.jpg"), bytes("sunset"), 6);
            storage.writeFile(StoragePath.of("old/note.txt"), bytes("note"), 4);

            storage.moveDirectory(StoragePath.of("old"), StoragePath.of("parent/new"));

            assertFalse(storage.directoryExists(StoragePath.of("old")));
            assertEquals("sunset", read(StoragePath.of("parent/new/bien/hoang-hon.jpg")));
            assertEquals("note", read(StoragePath.of("parent/new/note.txt")));
        }

        @Test
        void renamesDirectoryInPlace() throws IOException {
            storage.writeFile(StoragePath.of("anh-cu/a.txt"), bytes("a"), 1);
            storage.moveDirectory(StoragePath.of("anh-cu"), StoragePath.of("anh-moi"));
            assertEquals("a", read(StoragePath.of("anh-moi/a.txt")));
        }

        @Test
        void refusesMovingDirectoryIntoItsOwnDescendant() {
            storage.writeFile(StoragePath.of("a/b/file.txt"), bytes("data"), 4);

            StorageException e = assertThrows(StorageException.class,
                    () -> storage.moveDirectory(StoragePath.of("a"), StoragePath.of("a/b/a")));
            assertTrue(e.getMessage().contains("descendant"), e.getMessage());
            assertTrue(storage.fileExists(StoragePath.of("a/b/file.txt")), "no data may be lost");
        }

        @Test
        void refusesMovingDirectoryOntoItself() {
            storage.writeFile(StoragePath.of("a/file.txt"), bytes("data"), 4);
            // same path is a no-op, but "a" -> "a" via a longer spelling must not destroy anything
            storage.moveDirectory(StoragePath.of("a"), StoragePath.of("./a"));
            assertTrue(storage.fileExists(StoragePath.of("a/file.txt")));
        }

        @Test
        void refusesMovingOntoAnExistingTarget() {
            storage.createDirectory(StoragePath.of("a"));
            storage.createDirectory(StoragePath.of("b"));
            assertThrows(StorageException.class,
                    () -> storage.moveDirectory(StoragePath.of("a"), StoragePath.of("b")));
        }

        @Test
        void movingMissingDirectoryThrows() {
            assertThrows(StorageException.class,
                    () -> storage.moveDirectory(StoragePath.of("ghost"), StoragePath.of("x")));
        }
    }

    @Nested
    @DisplayName("Containment: every method rejects escapes")
    class Containment {

        /**
         * A malicious relative path is rejected by {@link StoragePath} itself, before it can reach
         * the filesystem at all.
         */
        @ParameterizedTest
        @ValueSource(strings = {
                "../outside.txt",
                "a/../../outside.txt",
                "..\\outside.txt",
                "/etc/passwd",
                "C:/Windows/win.ini",
                "a/b/../../../outside.txt"
        })
        void storagePathRejectsTraversalAndAbsolutePaths(String raw) {
            assertThrows(StorageException.class, () -> StoragePath.of(raw));
        }

        /**
         * Defence in depth: even if a {@code StoragePath} were built by some other route, every
         * single method must run the containment check rather than a subset of them.
         */
        @Test
        void everyMethodRejectsAnEscapingPath() throws Exception {
            StoragePath escaping = escapingPath();
            List<Consumer<StoragePath>> operations = List.of(
                    p -> storage.writeFile(p, bytes("x"), 1),
                    p -> storage.readFile(p),
                    p -> storage.deleteFile(p),
                    p -> storage.fileExists(p),
                    p -> storage.directoryExists(p),
                    p -> storage.createDirectory(p),
                    p -> storage.moveFile(p, StoragePath.of("safe.txt")),
                    p -> storage.moveFile(StoragePath.of("safe.txt"), p),
                    p -> storage.moveDirectory(p, StoragePath.of("safe")),
                    p -> storage.moveDirectory(StoragePath.of("safe"), p),
                    p -> storage.deleteDirectoryIfEmpty(p),
                    p -> storage.listDirectory(p),
                    p -> storage.resolveAbsolute(p),
                    p -> storage.sizeOf(p));

            storage.writeFile(StoragePath.of("safe.txt"), bytes("safe"), 4);
            storage.createDirectory(StoragePath.of("safe"));

            for (int i = 0; i < operations.size(); i++) {
                Consumer<StoragePath> op = operations.get(i);
                StorageException e = assertThrows(StorageException.class,
                        () -> op.accept(escaping), "operation #" + i + " did not reject the path");
                assertTrue(e.getMessage().contains("escapes the storage root"),
                        "operation #" + i + " threw the wrong error: " + e.getMessage());
            }

            assertFalse(Files.exists(rootDir.getParent().resolve("pwned.txt")),
                    "a file was created outside the storage root");
        }

        /**
         * Builds a StoragePath whose text is harmless but which resolves outside the root, by
         * pointing the storage at a deeper root than the path was written for. This exercises the
         * {@code startsWith(root)} check rather than the {@code ..} literal check.
         */
        private StoragePath escapingPath() throws Exception {
            var ctor = StoragePath.class.getDeclaredConstructor(String.class);
            ctor.setAccessible(true);
            return ctor.newInstance("../pwned.txt");
        }
    }

    @Nested
    @DisplayName("StoragePath value semantics")
    class PathSemantics {

        @Test
        void normalisesSeparatorsAndRedundantSegments() {
            assertEquals("a/b/c.txt", StoragePath.of("a\\b//./c.txt").toString());
            assertEquals("", StoragePath.of("  ").toString());
            assertEquals("", StoragePath.of(null).toString());
        }

        @Test
        void parentResolveAndFilename() {
            StoragePath p = StoragePath.of("a/b/c.txt");
            assertEquals("c.txt", p.filename());
            assertEquals("a/b", p.parent().toString());
            assertEquals("a/b/c.txt", StoragePath.of("a").resolve("b/c.txt").toString());
            assertEquals("x.txt", StoragePath.root().resolve("x.txt").toString());
            assertTrue(StoragePath.root().parent().isRoot());
        }

        @Test
        void ancestryComparesWholeSegments() {
            assertTrue(StoragePath.of("a/b").isAncestorOf(StoragePath.of("a/b/c")));
            assertTrue(StoragePath.of("a/b").isAncestorOf(StoragePath.of("a/b")));
            assertFalse(StoragePath.of("a/b").isAncestorOf(StoragePath.of("a/bc")));
            assertTrue(StoragePath.root().isAncestorOf(StoragePath.of("anything")));
        }

        @Test
        void resolveRejectsTraversal() {
            assertThrows(StorageException.class, () -> StoragePath.of("a").resolve("../b"));
        }
    }
}
