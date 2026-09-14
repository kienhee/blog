package com.kienhee.blog.storage;

import com.kienhee.blog.config.UploadProperties;
import com.kienhee.blog.storage.local.LocalStorageAdapter;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Nested;
import org.junit.jupiter.api.Test;
import org.junit.jupiter.api.io.TempDir;

import java.io.ByteArrayInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.time.Duration;
import java.util.List;
import java.util.stream.Stream;

import static org.junit.jupiter.api.Assertions.assertDoesNotThrow;
import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertFalse;
import static org.junit.jupiter.api.Assertions.assertNotEquals;
import static org.junit.jupiter.api.Assertions.assertNotNull;
import static org.junit.jupiter.api.Assertions.assertNull;
import static org.junit.jupiter.api.Assertions.assertThrows;
import static org.junit.jupiter.api.Assertions.assertTrue;

/**
 * Unit test thuần cho {@link LocalStorageAdapter} và {@link StorageKeyGenerator} —
 * không {@code @SpringBootTest}, không DB: collaborator duy nhất là {@link UploadProperties} (POJO).
 */
class LocalStorageAdapterTests {

    @TempDir
    Path tempDir;

    private LocalStorageAdapter adapter;

    @BeforeEach
    void setUp() {
        UploadProperties properties = new UploadProperties();
        properties.setDir(tempDir.toString());
        adapter = new LocalStorageAdapter(properties);
    }

    private static InputStream stream(String content) {
        return new ByteArrayInputStream(content.getBytes(StandardCharsets.UTF_8));
    }

    private String read(String key) throws IOException {
        try (InputStream in = adapter.get(key)) {
            return new String(in.readAllBytes(), StandardCharsets.UTF_8);
        }
    }

    @Nested
    @DisplayName("put / get")
    class PutAndGet {

        @Test
        @DisplayName("writes the bytes and returns provider metadata")
        void putStoresContent() throws IOException {
            String key = "ab/12/ab12cd.txt";
            byte[] payload = "hello storage".getBytes(StandardCharsets.UTF_8);

            StoredObject stored = adapter.put(key, new ByteArrayInputStream(payload), payload.length, "text/plain");

            assertEquals(key, stored.getKey());
            assertEquals(payload.length, stored.getSizeBytes());
            assertEquals("text/plain", stored.getContentType());
            assertEquals("LOCAL", stored.getProvider());
            assertNotNull(stored.getStoredAt());
            assertEquals("hello storage", read(key));
        }

        @Test
        @DisplayName("creates the sharded parent directories automatically")
        void putCreatesParentDirectories() {
            adapter.put("de/ad/beef.bin", stream("x"), 1, "application/octet-stream");

            assertTrue(Files.isDirectory(tempDir.resolve("de").resolve("ad")));
        }

        @Test
        @DisplayName("overwrites an existing key")
        void putOverwritesExistingKey() throws IOException {
            String key = "aa/bb/dup.txt";
            adapter.put(key, stream("first"), 5, "text/plain");

            adapter.put(key, stream("second-and-longer"), 17, "text/plain");

            assertEquals("second-and-longer", read(key));
        }

        @Test
        @DisplayName("leaves no temporary .part file behind after a successful write")
        void putCleansUpTemporaryFiles() throws IOException {
            adapter.put("aa/bb/clean.txt", stream("data"), 4, "text/plain");

            List<String> files;
            try (Stream<Path> walk = Files.walk(tempDir)) {
                files = walk.filter(Files::isRegularFile)
                        .map(p -> p.getFileName().toString())
                        .toList();
            }
            assertEquals(List.of("clean.txt"), files);
        }

        @Test
        @DisplayName("get on a missing key throws StorageException")
        void getMissingKeyThrows() {
            assertThrows(StorageException.class, () -> adapter.get("no/pe/missing.txt"));
        }
    }

    @Nested
    @DisplayName("exists / delete")
    class ExistsAndDelete {

        @Test
        void existsReflectsStoredObjects() {
            assertFalse(adapter.exists("aa/bb/x.txt"));

            adapter.put("aa/bb/x.txt", stream("x"), 1, "text/plain");

            assertTrue(adapter.exists("aa/bb/x.txt"));
        }

        @Test
        @DisplayName("a directory is not an object")
        void existsIsFalseForDirectory() {
            adapter.put("aa/bb/x.txt", stream("x"), 1, "text/plain");

            assertFalse(adapter.exists("aa/bb"));
        }

        @Test
        void deleteRemovesTheObject() {
            adapter.put("aa/bb/gone.txt", stream("bye"), 3, "text/plain");

            assertTrue(adapter.delete("aa/bb/gone.txt"));
            assertFalse(adapter.exists("aa/bb/gone.txt"));
        }

        @Test
        @DisplayName("delete is idempotent for a missing key")
        void deleteMissingKeyIsNoOp() {
            assertDoesNotThrow(() -> adapter.delete("aa/bb/never.txt"));
            assertFalse(adapter.delete("aa/bb/never.txt"));
        }
    }

    @Nested
    @DisplayName("path traversal protection")
    class PathTraversal {

        @Test
        @DisplayName("put rejects every key escaping the storage root")
        void putRejectsTraversal() {
            List<String> evilKeys = List.of(
                    "../../evil.txt",
                    "../evil.txt",
                    "aa/../../evil.txt",
                    "aa/bb/../../../evil.txt",
                    "./../evil.txt"
            );

            for (String key : evilKeys) {
                StorageException ex = assertThrows(StorageException.class,
                        () -> adapter.put(key, stream("pwned"), 5, "text/plain"),
                        "expected key to be rejected: " + key);
                assertTrue(ex.getMessage().contains("escapes the storage root"),
                        "unexpected message for " + key + ": " + ex.getMessage());
            }
        }

        @Test
        @DisplayName("no file is created outside the root")
        void traversalWritesNothing() {
            Path outside = tempDir.getParent().resolve("evil.txt");

            assertThrows(StorageException.class,
                    () -> adapter.put("../evil.txt", stream("pwned"), 5, "text/plain"));

            assertFalse(Files.exists(outside));
        }

        @Test
        @DisplayName("get, delete, exists and url building reject traversal too")
        void allOperationsRejectTraversal() {
            assertThrows(StorageException.class, () -> adapter.get("../../evil.txt"));
            assertThrows(StorageException.class, () -> adapter.delete("../../evil.txt"));
            assertThrows(StorageException.class, () -> adapter.exists("../../evil.txt"));
            assertThrows(StorageException.class, () -> adapter.publicUrl("../../evil.txt"));
            assertThrows(StorageException.class, () -> adapter.presignedUrl("../../evil.txt", Duration.ofMinutes(1)));
        }

        @Test
        @DisplayName("blank, backslash, absolute and root-equal keys are rejected")
        void otherIllegalKeysRejected() {
            assertThrows(StorageException.class, () -> adapter.exists(null));
            assertThrows(StorageException.class, () -> adapter.exists("  "));
            assertThrows(StorageException.class, () -> adapter.exists("..\\..\\evil.txt"));
            assertThrows(StorageException.class, () -> adapter.exists("/etc/passwd"));
            assertThrows(StorageException.class, () -> adapter.exists(".."));
        }
    }

    @Nested
    @DisplayName("urls")
    class Urls {

        @Test
        void publicUrlMapsOntoUploadsHandler() {
            assertEquals("/uploads/ab/12/file.jpg", adapter.publicUrl("ab/12/file.jpg"));
        }

        @Test
        @DisplayName("presignedUrl falls back to the public url (no real TTL locally)")
        void presignedUrlEqualsPublicUrl() {
            assertEquals(adapter.publicUrl("ab/12/file.jpg"),
                    adapter.presignedUrl("ab/12/file.jpg", Duration.ofMinutes(5)));
        }

        @Test
        void providerCodeIsLocal() {
            assertEquals("LOCAL", adapter.providerCode());
        }
    }

    @Nested
    @DisplayName("StorageKeyGenerator")
    class KeyGeneration {

        private final StorageKeyGenerator generator = new StorageKeyGenerator();

        @Test
        @DisplayName("key is a UUID with a 2-level hex shard, never the user's filename")
        void generatesShardedUuidKey() {
            String key = generator.generate("My Holiday Photo.JPG");

            assertTrue(key.matches("^[0-9a-f]{2}/[0-9a-f]{2}/[0-9a-f]{32}\\.jpg$"), key);
            assertFalse(key.toLowerCase().contains("holiday"));
            // shard directories are the first two hex pairs of the uuid itself
            assertEquals(key.substring(0, 2), key.substring(6, 8));
            assertEquals(key.substring(3, 5), key.substring(8, 10));
        }

        @Test
        void generatesUniqueKeys() {
            assertNotEquals(generator.generate("a.png"), generator.generate("a.png"));
        }

        @Test
        @DisplayName("non-whitelisted or absent extensions are dropped")
        void dropsUnknownExtension() {
            assertNull(generator.extractExtension("payload.exe"));
            assertNull(generator.extractExtension("payload.php"));
            assertNull(generator.extractExtension("noextension"));
            assertNull(generator.extractExtension(null));
            assertTrue(generator.generate("payload.exe").matches("^[0-9a-f]{2}/[0-9a-f]{2}/[0-9a-f]{32}$"));
        }

        @Test
        @DisplayName("separators in the filename cannot leak into the key")
        void ignoresPathSeparatorsInFilename() {
            assertEquals("png", generator.extractExtension("../../evil.txt/x.png"));
            assertEquals("png", generator.extractExtension("..\\..\\evil.png"));
        }

        @Test
        @DisplayName("generated keys round-trip through the adapter")
        void generatedKeysAreStorable() throws IOException {
            String key = generator.generate("report.pdf");

            adapter.put(key, stream("pdf-bytes"), 9, "application/pdf");

            assertTrue(adapter.exists(key));
            assertEquals("pdf-bytes", read(key));
        }
    }
}
