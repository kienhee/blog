package com.kienhee.blog.service.impl;

import com.kienhee.blog.entity.Media;
import com.kienhee.blog.entity.MediaFolder;
import com.kienhee.blog.entity.StorageBlob;
import com.kienhee.blog.repository.MediaRepository;
import com.kienhee.blog.repository.StorageBlobRepository;
import com.kienhee.blog.service.FileHasher;
import com.kienhee.blog.service.MediaFilesystemLayoutMigrationService;
import com.kienhee.blog.storage.FilenameSanitizer;
import com.kienhee.blog.storage.FilesystemStorage;
import com.kienhee.blog.storage.StorageException;
import com.kienhee.blog.storage.StoragePath;
import com.kienhee.blog.storage.StorageTransactionHelper;
import jakarta.persistence.EntityManager;
import jakarta.persistence.PersistenceContext;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.beans.factory.ObjectProvider;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Propagation;
import org.springframework.transaction.annotation.Transactional;

import java.io.IOException;
import java.io.InputStream;
import java.util.ArrayList;
import java.util.Collection;
import java.util.Comparator;
import java.util.HashSet;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Locale;
import java.util.Objects;
import java.util.Set;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * See {@link MediaFilesystemLayoutMigrationService} for the contract and safety guarantees.
 *
 * <p>Order of operations, per row: copy file + thumbnail (compensated by
 * {@link StorageTransactionHelper}: a rollback deletes only the new copies) -> update media, blob,
 * avatars, covers -> commit. Only after all rows, outside any transaction, legacy files nothing
 * references any more are MOVED (never deleted) into {@code uploads/.orphaned/}.</p>
 */
@Slf4j
@Service
@RequiredArgsConstructor
public class MediaFilesystemLayoutMigrationServiceImpl implements MediaFilesystemLayoutMigrationService {

    static final String LEGACY_URL_PREFIX = "/uploads/";
    static final String ORPHANED_DIR = ".orphaned";

    /** Pre-V16 generated names: {@code <uuid>.<ext>} and {@code <uuid>_thumb.<ext>}. */
    private static final Pattern LEGACY_NAME = Pattern.compile(
            "^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}(_thumb)?\\.[A-Za-z0-9]{1,5}$");
    private static final Pattern EMBEDDED_LEGACY_URL = Pattern.compile("/uploads/[^\\s\"'()<>\\\\]+");

    private final MediaRepository mediaRepository;
    private final StorageBlobRepository storageBlobRepository;
    private final FilesystemStorage storage;
    private final StorageTransactionHelper storageTx;
    private final MediaStorageLayout layout;
    private final FilenameSanitizer sanitizer;
    private final FileHasher fileHasher;

    /** This bean through its proxy, so {@code processRow}'s REQUIRES_NEW really applies. */
    private final ObjectProvider<MediaFilesystemLayoutMigrationService> selfProvider;

    @PersistenceContext
    private EntityManager entityManager;

    // ------------------------------------------------------------------------
    // job
    // ------------------------------------------------------------------------

    @Override
    public MigrationReport migrate(boolean dryRun) {
        MediaFilesystemLayoutMigrationService self = selfProvider.getObject();
        List<Long> candidates = entityManager.createQuery(
                "select m.id from Media m where m.storagePath is null order by m.id asc", Long.class)
                .getResultList();

        MigrationPlan plan = new MigrationPlan();
        int migrated = 0;
        int blobsCreated = 0;
        int thumbnailsMoved = 0;
        Set<String> sharedSources = new LinkedHashSet<>();
        List<String> rows = new ArrayList<>();
        List<String> references = new ArrayList<>();
        List<String> missing = new ArrayList<>();
        List<String> warnings = new ArrayList<>();
        List<String> errors = new ArrayList<>();

        for (Long id : candidates) {
            try {
                RowOutcome outcome = self.processRow(id, dryRun, plan);
                plan.record(outcome);
                switch (outcome.status()) {
                    case MISSING_FILE -> missing.add("media #" + id + ": " + outcome.sourceFile() + " (row left untouched)");
                    case SKIPPED -> { }
                    case MIGRATED -> {
                        migrated++;
                        if (outcome.blobCreated()) {
                            blobsCreated++;
                        }
                        if (outcome.thumbnailMoved()) {
                            thumbnailsMoved++;
                        }
                        if (outcome.sharedSource()) {
                            sharedSources.add(outcome.sourceFile());
                        }
                        rows.add("media #" + id + ": " + outcome.sourceFile() + " -> " + outcome.newPath()
                                + " | url " + outcome.oldUrl() + " -> " + outcome.newUrl()
                                + " | blob " + (outcome.blobCreated() ? "NEW" : "reused")
                                + (outcome.sharedSource() ? " | split from shared file (copy)" : "")
                                + (outcome.thumbnailMoved() ? " | thumbnail -> " + MediaStorageLayout.THUMBNAIL_DIR + "/" + id + "_thumb.*" : ""));
                        references.addAll(outcome.referencesRewritten());
                    }
                }
                outcome.warnings().forEach(w -> warnings.add("media #" + id + ": " + w));
            } catch (RuntimeException e) {
                log.warn("FS layout migration failed for media {}: {}", id, e.toString());
                errors.add("media #" + id + ": " + e.getMessage());
            }
        }

        // After every row committed: legacy files nothing points at any more -> .orphaned/.
        List<String> moved = new ArrayList<>();
        try {
            List<String> rootNames = storage.listDirectory(StoragePath.root()).stream()
                    .map(StoragePath::filename).toList();
            moved = moveUnreferencedLegacyFiles(rootNames, dryRun, plan);
        } catch (RuntimeException e) {
            errors.add("orphan sweep: " + e.getMessage());
        }

        MigrationReport report = new MigrationReport(dryRun, candidates.size(), migrated, sharedSources.size(),
                blobsCreated, thumbnailsMoved, rows, references, moved, missing,
                findContentEmbeds(plan), findUnresolvedReferences(plan), findRefCountMismatches(),
                warnings, errors);
        log.info("{}", format(report));
        return report;
    }

    // ------------------------------------------------------------------------
    // one row
    // ------------------------------------------------------------------------

    @Override
    @Transactional(propagation = Propagation.REQUIRES_NEW)
    public RowOutcome processRow(Long mediaId, boolean dryRun, MigrationPlan plan) {
        Media media = mediaRepository.findById(mediaId).orElse(null);
        if (media == null || media.getStoragePath() != null || plan.isMigrated(mediaId)) {
            return RowOutcome.skipped(mediaId);
        }
        String legacyName = media.getStoredFilename();
        StoragePath source = StoragePath.of(legacyName);
        if (source.isRoot() || source.depth() != 1 || !storage.fileExists(source)) {
            log.warn("FS layout migration: file missing for media {} ({}), row left untouched", mediaId, legacyName);
            return RowOutcome.missing(mediaId, legacyName);
        }
        List<String> warnings = new ArrayList<>();

        // 1. Target: folder directory via the layout + sanitised original name, unique case-insensitively.
        MediaFolder folder = media.getFolder();
        StoragePath dir = layout.directoryOf(folder);
        String filename = uniqueFilename(dir, withExtension(media.getOriginalFilename(), legacyName), plan);
        StoragePath target = dir.resolve(filename);
        layout.requireLength(target);
        String oldUrl = media.getUrl();
        String newUrl = MediaStorageLayout.publicUrl(mediaId, filename);

        // 2. Shared file / shared blob? (rows already migrated in this run no longer count)
        boolean sharedFile = entityManager.createQuery(
                        "select m.id from Media m where m.storagePath is null and m.storedFilename = :n and m.id <> :id",
                        Long.class)
                .setParameter("n", legacyName).setParameter("id", mediaId)
                .getResultList().stream().anyMatch(id -> !plan.isMigrated(id))
                // the last row of a shared group no longer sees the others: they migrated earlier in this run
                || plan.isMigratedSource(legacyName);
        StorageBlob oldBlob = media.getBlob();
        long otherBlobRows = oldBlob == null ? 0 : entityManager.createQuery(
                        "select m.id from Media m where m.blob.id = :b and m.id <> :id", Long.class)
                .setParameter("b", oldBlob.getId()).setParameter("id", mediaId)
                .getResultList().stream().filter(id -> !plan.isMigrated(id)).count();
        // The last row leaving a blob reuses it; everyone before gets a fresh one.
        boolean createBlob = oldBlob == null || otherBlobRows > 0;

        // 3. Thumbnail: legacy <uuid>_thumb.<ext> -> .thumbnails/<id>_thumb.<ext> (copy).
        StoragePath legacyThumb = null;
        StoragePath thumbTarget = null;
        String thumbUrl = media.getThumbnailUrl();
        if (thumbUrl != null && thumbUrl.startsWith(LEGACY_URL_PREFIX)) {
            String thumbName = thumbUrl.substring(LEGACY_URL_PREFIX.length());
            String ext = extensionOf(thumbName).toLowerCase(Locale.ROOT);
            if ("jpeg".equals(ext)) {
                ext = "jpg";
            }
            StoragePath candidate = StoragePath.of(thumbName);
            if (!"jpg".equals(ext) && !"png".equals(ext)) {
                warnings.add("legacy thumbnail " + thumbName + " has unsupported extension; thumbnail_url cleared (original is served as thumbnail)");
            } else if (candidate.depth() != 1 || !storage.fileExists(candidate)) {
                warnings.add("legacy thumbnail " + thumbName + " missing on disk; thumbnail_url cleared (original is served as thumbnail)");
            } else {
                legacyThumb = candidate;
                thumbTarget = layout.thumbnailPath(mediaId, ext);
                if (storage.fileExists(thumbTarget)) {
                    warnings.add(thumbTarget + " already exists; kept as is, legacy thumbnail not copied");
                    legacyThumb = null;
                }
            }
        }
        String newThumbUrl = thumbTarget != null ? MediaStorageLayout.thumbnailUrl(mediaId) : null;

        // 5. References re-pointed to this row (see OWNER_RULE).
        List<Long> userIds = new ArrayList<>();
        List<Long> postIds = new ArrayList<>();
        List<String> referenceLines = new ArrayList<>();
        if (oldUrl != null && !oldUrl.equals(newUrl)) {
            List<Media> sharing = entityManager.createQuery(
                            "select m from Media m left join fetch m.folder left join fetch m.uploadedBy "
                                    + "where m.url = :u and m.storagePath is null", Media.class)
                    .setParameter("u", oldUrl).getResultList()
                    .stream().filter(m -> !plan.isMigrated(m.getId())).toList();
            for (Object[] row : entityManager.createQuery(
                            "select u.id, u.email from User u where u.avatarUrl = :u", Object[].class)
                    .setParameter("u", oldUrl).getResultList()) {
                Long userId = (Long) row[0];
                if (plan.isUserRewritten(userId)) {
                    continue;
                }
                Media owner = chooseOwner(sharing, userId, true);
                if (owner != null && owner.getId().equals(mediaId)) {
                    userIds.add(userId);
                    referenceLines.add("user #" + userId + " (" + row[1] + ") avatar_url: " + oldUrl + " -> " + newUrl
                            + " [media #" + mediaId + (sharing.size() > 1 ? ", chosen among " + ids(sharing) : "") + "]");
                    if (owner.getStatus() != Media.Status.ACTIVE) {
                        warnings.add("avatar of user #" + userId + " points at a TRASHED media row: it will 404 publicly");
                    }
                }
            }
            for (Object[] row : entityManager.createQuery(
                            "select p.id, a.id from Post p left join p.author a where p.coverImage = :u", Object[].class)
                    .setParameter("u", oldUrl).getResultList()) {
                Long postId = (Long) row[0];
                if (plan.isPostRewritten(postId)) {
                    continue;
                }
                Media owner = chooseOwner(sharing, (Long) row[1], false);
                if (owner != null && owner.getId().equals(mediaId)) {
                    postIds.add(postId);
                    referenceLines.add("post #" + postId + " cover_image: " + oldUrl + " -> " + newUrl
                            + " [media #" + mediaId + (sharing.size() > 1 ? ", chosen among " + ids(sharing) : "") + "]");
                    if (owner.getStatus() != Media.Status.ACTIVE) {
                        warnings.add("cover of post #" + postId + " points at a TRASHED media row: it will 404 publicly");
                    }
                }
            }
        }

        if (!dryRun) {
            // Disk first. Every write below is undone (new copy deleted) if this transaction rolls back;
            // the legacy source is only READ here.
            if (!dir.isRoot()) {
                layout.createDirectory(dir);
            }
            if (storage.fileExists(target)) {
                throw new IllegalStateException("Target already exists on disk: " + target);
            }
            byte[] bytes = readAll(source);
            storageTx.writeFile(target, bytes);
            if (legacyThumb != null) {
                storageTx.writeFile(thumbTarget, readAll(legacyThumb));
            }

            String sha256 = fileHasher.hash(bytes);
            StorageBlob blob;
            if (createBlob) {
                blob = storageBlobRepository.save(StorageBlob.builder()
                        .sha256(sha256)
                        .storageKey(target.toString())
                        .storageProvider(MediaStorageLayout.PROVIDER_CODE)
                        .sizeBytes(bytes.length)
                        .contentType(media.getContentType())
                        .refCount(1)
                        .build());
            } else {
                blob = oldBlob;
                blob.setStorageKey(target.toString());
                blob.setRefCount(1);
            }
            media.setBlob(blob);
            if (media.getSha256() == null) {
                media.setSha256(sha256);
            }
            media.setStoragePath(target.toString());
            media.setStoredFilename(filename);
            media.setUrl(newUrl);
            media.setThumbnailUrl(newThumbUrl);
            mediaRepository.saveAndFlush(media);

            if (!userIds.isEmpty()) {
                entityManager.createQuery("update User u set u.avatarUrl = :n where u.id in :ids and u.avatarUrl = :o")
                        .setParameter("n", newUrl).setParameter("o", oldUrl).setParameter("ids", userIds)
                        .executeUpdate();
            }
            if (!postIds.isEmpty()) {
                entityManager.createQuery("update Post p set p.coverImage = :n where p.id in :ids and p.coverImage = :o")
                        .setParameter("n", newUrl).setParameter("o", oldUrl).setParameter("ids", postIds)
                        .executeUpdate();
            }
            if (createBlob && oldBlob != null) {
                // Clears the persistence context: keep it last.
                storageBlobRepository.addRefCount(oldBlob.getId(), -1);
            }
        }

        return new RowOutcome(mediaId, RowStatus.MIGRATED, legacyName, sharedFile || otherBlobRows > 0,
                createBlob, thumbTarget != null && legacyThumb != null, oldUrl, newUrl, target.toString(),
                referenceLines, userIds, postIds, warnings);
    }

    /**
     * Implements {@link #OWNER_RULE}: a total order, so removing non-chosen rows (as they migrate
     * one by one) never changes which row is chosen.
     */
    static Media chooseOwner(List<Media> sharing, Long referrerId, boolean avatar) {
        Comparator<Media> order = Comparator
                .comparing((Media m) -> m.getStatus() != Media.Status.ACTIVE)
                .thenComparing(m -> !(avatar && isAvatarFolder(m.getFolder())))
                .thenComparing(m -> !(m.getUploadedBy() != null && Objects.equals(m.getUploadedBy().getId(), referrerId)))
                .thenComparing(Media::getId);
        return sharing.stream().min(order).orElse(null);
    }

    private static boolean isAvatarFolder(MediaFolder folder) {
        return folder != null && folder.getSlug() != null
                && folder.getSlug().toLowerCase(Locale.ROOT).contains("avatar");
    }

    private static String ids(List<Media> rows) {
        return rows.stream().map(m -> "#" + m.getId()).toList().toString();
    }

    /** Case-insensitive (trap 2) against disk, DB rows, and paths planned earlier in this run. */
    private String uniqueFilename(StoragePath dir, String rawName, MigrationPlan plan) {
        String dirKey = dir.toString().toLowerCase(Locale.ROOT);
        Set<String> taken = new HashSet<>();
        for (StoragePath entry : storage.listDirectory(dir)) {
            taken.add(entry.filename().toLowerCase(Locale.ROOT));
        }
        String prefix = dir.isRoot() ? "" : dir + "/";
        List<String> claimed = new ArrayList<>(mediaRepository.findStoragePathsStartingWith(prefix));
        claimed.addAll(plan.plannedPaths());
        for (String path : claimed) {
            StoragePath p = StoragePath.of(path);
            if (p.parent().toString().toLowerCase(Locale.ROOT).equals(dirKey)) {
                taken.add(p.filename().toLowerCase(Locale.ROOT));
            }
        }
        return sanitizer.sanitizeUnique(rawName, c -> taken.contains(c.toLowerCase(Locale.ROOT)));
    }

    /** Same idea as upload: a name without extension borrows the legacy file's extension. */
    private static String withExtension(String originalFilename, String legacyName) {
        String name = originalFilename == null || originalFilename.isBlank() ? "file" : originalFilename;
        String last = name.replace('\\', '/');
        last = last.substring(last.lastIndexOf('/') + 1);
        if (!extensionOf(last).isEmpty()) {
            return name;
        }
        String ext = extensionOf(legacyName);
        return ext.isEmpty() ? name : name + "." + ext;
    }

    private static String extensionOf(String name) {
        int dot = name.lastIndexOf('.');
        return (dot <= 0 || dot == name.length() - 1) ? "" : name.substring(dot + 1);
    }

    private byte[] readAll(StoragePath path) {
        try (InputStream in = storage.readFile(path)) {
            return in.readAllBytes();
        } catch (IOException e) {
            throw new StorageException("Cannot read " + path, e);
        }
    }

    // ------------------------------------------------------------------------
    // after commit: legacy files -> .orphaned (never deleted)
    // ------------------------------------------------------------------------

    @Override
    public List<String> moveUnreferencedLegacyFiles(Collection<String> rootFileNames, boolean dryRun, MigrationPlan plan) {
        Set<String> referenced = new HashSet<>();
        for (Object[] row : entityManager.createQuery(
                "select m.id, m.storedFilename, m.thumbnailUrl, m.storagePath from Media m", Object[].class)
                .getResultList()) {
            Long id = (Long) row[0];
            String storagePath = (String) row[3];
            if (storagePath != null) {
                StoragePath p = StoragePath.of(storagePath);
                if (p.depth() == 1) {
                    referenced.add(p.filename().toLowerCase(Locale.ROOT));
                }
                continue;
            }
            if (plan.isMigrated(id)) {
                continue; // dry-run: this row would no longer use its legacy files
            }
            if (row[1] != null) {
                referenced.add(((String) row[1]).toLowerCase(Locale.ROOT));
            }
            String thumb = (String) row[2];
            if (thumb != null && thumb.startsWith(LEGACY_URL_PREFIX)) {
                referenced.add(thumb.substring(LEGACY_URL_PREFIX.length()).toLowerCase(Locale.ROOT));
            }
        }
        plan.plannedPaths().stream().filter(p -> !p.contains("/")).forEach(referenced::add);

        StoragePath orphanDir = StoragePath.of(ORPHANED_DIR);
        Set<String> takenInOrphaned = new HashSet<>();
        storage.listDirectory(orphanDir).forEach(p -> takenInOrphaned.add(p.filename().toLowerCase(Locale.ROOT)));

        List<String> lines = new ArrayList<>();
        for (String name : rootFileNames.stream().sorted().toList()) {
            if (name == null || !LEGACY_NAME.matcher(name).matches()
                    || referenced.contains(name.toLowerCase(Locale.ROOT))) {
                continue;
            }
            StoragePath from = StoragePath.of(name);
            if (!storage.fileExists(from)) {
                continue;
            }
            // moveFile overwrites (trap 3): pick a free name inside .orphaned first.
            String free = sanitizer.sanitizeUnique(name, c -> takenInOrphaned.contains(c.toLowerCase(Locale.ROOT)));
            takenInOrphaned.add(free.toLowerCase(Locale.ROOT));
            StoragePath to = orphanDir.resolve(free);
            String reason = plan.isMigratedSource(name) ? "source of migrated row(s)"
                    : name.contains("_thumb.") ? "legacy thumbnail, no row uses it" : "no row references it";
            try {
                if (!dryRun) {
                    storage.moveFile(from, to);
                }
                lines.add(name + " -> " + to + " (" + reason + ")");
            } catch (StorageException e) {
                lines.add(name + " NOT moved: " + e.getMessage());
            }
        }
        return lines;
    }

    // ------------------------------------------------------------------------
    // report-only checks
    // ------------------------------------------------------------------------

    /** posts.content is NEVER edited; embedded legacy URLs are only listed. */
    private List<String> findContentEmbeds(MigrationPlan plan) {
        List<String> lines = new ArrayList<>();
        for (Object[] row : entityManager.createQuery(
                "select p.id, p.content from Post p where p.content like '%/uploads/%'", Object[].class)
                .getResultList()) {
            Matcher m = EMBEDDED_LEGACY_URL.matcher(String.valueOf(row[1]));
            Set<String> urls = new LinkedHashSet<>();
            while (m.find()) {
                urls.add(m.group());
            }
            for (String url : urls) {
                List<String> candidates = plan.newUrlsByOldUrl().get(url);
                lines.add("post #" + row[0] + " content embeds " + url
                        + (candidates != null ? " (new URL candidates: " + candidates + ")" : " (no migrated row for it)"));
            }
            if (urls.isEmpty()) {
                lines.add("post #" + row[0] + " content contains '/uploads/' (no parsable URL)");
            }
        }
        return lines;
    }

    private List<String> findUnresolvedReferences(MigrationPlan plan) {
        List<String> lines = new ArrayList<>();
        for (Object[] row : entityManager.createQuery(
                "select u.id, u.avatarUrl from User u where u.avatarUrl like '%/uploads/%'", Object[].class)
                .getResultList()) {
            if (!plan.isUserRewritten((Long) row[0])) {
                lines.add("user #" + row[0] + " avatar_url still " + row[1]);
            }
        }
        for (Object[] row : entityManager.createQuery(
                "select p.id, p.coverImage from Post p where p.coverImage like '%/uploads/%'", Object[].class)
                .getResultList()) {
            if (!plan.isPostRewritten((Long) row[0])) {
                lines.add("post #" + row[0] + " cover_image still " + row[1]);
            }
        }
        return lines;
    }

    private List<String> findRefCountMismatches() {
        List<String> lines = new ArrayList<>();
        for (Object[] row : entityManager.createQuery(
                "select b.id, b.refCount, (select count(m) from Media m where m.blob = b) from StorageBlob b",
                Object[].class).getResultList()) {
            long refs = ((Number) row[2]).longValue();
            if (((Number) row[1]).longValue() != refs) {
                lines.add("blob #" + row[0] + " ref_count=" + row[1] + " but " + refs + " media row(s)");
            }
        }
        return lines;
    }

    public static String format(MigrationReport r) {
        StringBuilder sb = new StringBuilder("\n=== Media filesystem layout migration report ===")
                .append("\n  mode               : ").append(r.dryRun() ? "DRY-RUN (nothing written, disk untouched)" : "APPLIED")
                .append("\n  scanned            : ").append(r.scanned())
                .append("\n  migrated           : ").append(r.migrated())
                .append("\n  sharedFilesSplit   : ").append(r.sharedFilesSplit())
                .append("\n  blobsCreated       : ").append(r.blobsCreated())
                .append("\n  thumbnailsMoved    : ").append(r.thumbnailsMoved())
                .append("\n  owner rule         : ").append(OWNER_RULE);
        appendList(sb, "rows", r.rows());
        appendList(sb, "referencesRewritten", r.referencesRewritten());
        appendList(sb, "movedToOrphaned (moved, NOT deleted)", r.movedToOrphaned());
        appendList(sb, "missingFiles (row kept)", r.missingFiles());
        appendList(sb, "contentEmbedsFound (post HTML NOT edited)", r.contentEmbedsFound());
        appendList(sb, "unresolvedReferences (still /uploads/ after this run)", r.unresolvedReferences());
        appendList(sb, "refCountMismatches (" + (r.dryRun() ? "current DB, before migration" : "after migration") + ")",
                r.refCountMismatches());
        appendList(sb, "warnings", r.warnings());
        appendList(sb, "errors", r.errors());
        return sb.append("\n=== end of report ===").toString();
    }

    private static void appendList(StringBuilder sb, String label, List<String> values) {
        sb.append("\n  ").append(label).append(" : ").append(values.size());
        for (String v : values) {
            sb.append("\n      - ").append(v);
        }
    }
}
