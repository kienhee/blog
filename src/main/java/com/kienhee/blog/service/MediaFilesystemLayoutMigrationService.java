package com.kienhee.blog.service;

import java.util.ArrayList;
import java.util.Collection;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.Set;

/**
 * One-off data migration from the flat pre-V16 layout ({@code uploads/<uuid>.<ext>}, served at
 * {@code /uploads/**}) to the filesystem mirror ({@code uploads/<folder slugs>/<original name>},
 * served at {@code /media/{id}/{filename}}).
 *
 * <ul>
 *   <li><b>Idempotent:</b> only rows with {@code storage_path IS NULL} are candidates; a second
 *       run scans 0 rows.</li>
 *   <li><b>One transaction per row</b> ({@code REQUIRES_NEW}, called through the Spring proxy):
 *       copy file(s) first, then update media + blob + {@code users.avatar_url} +
 *       {@code posts.cover_image} and commit. A rollback deletes only the new copies.</li>
 *   <li><b>Never deletes a file.</b> Legacy source files are only <em>moved</em> to
 *       {@code uploads/.orphaned/}, and only after every row using them has committed.</li>
 *   <li><b>Shared legacy files</b> (several rows on one physical file / one blob) are split by
 *       COPY: every row gets its own file, its own thumbnail copy and its own blob
 *       ({@code ref_count = 1}); the last row to leave the old blob reuses it.</li>
 *   <li><b>Which row a shared URL's references move to</b> — see {@link #OWNER_RULE}.</li>
 * </ul>
 */
public interface MediaFilesystemLayoutMigrationService {

    /** Human-readable rule, printed in the report so the operator sees what was decided. */
    String OWNER_RULE = "A user avatar / post cover pointing at a legacy URL shared by several media rows is "
            + "re-pointed to exactly ONE of those rows, the first by: (1) ACTIVE before TRASHED "
            + "(trashed files 404 publicly); (2) avatars only: row filed in a folder whose slug contains "
            + "'avatar'; (3) row uploaded by that user (avatar) / by the post author (cover); (4) lowest id.";

    /**
     * Runs the whole migration.
     *
     * @param dryRun {@code true} = the same full report, but no DB write and no disk change
     */
    MigrationReport migrate(boolean dryRun);

    /**
     * Migrates exactly ONE row in its own transaction. On the interface only so {@link #migrate}
     * can call it through the proxy; tests call it on their own fixtures.
     *
     * @param plan accumulated state of this run; the caller must {@link MigrationPlan#record}
     *             a successful outcome before processing the next row (dry-run relies on it)
     */
    RowOutcome processRow(Long mediaId, boolean dryRun, MigrationPlan plan);

    /**
     * Moves legacy-named files ({@code <uuid>[_thumb].<ext>} at the storage root) that no row
     * references any more into {@code uploads/.orphaned/}. Never deletes. Runs outside any
     * transaction, i.e. only after the rows' commits.
     *
     * @param rootFileNames file names at the storage root to consider (the job passes all of them)
     * @return one line per moved (or, in dry-run, to-be-moved) file
     */
    List<String> moveUnreferencedLegacyFiles(Collection<String> rootFileNames, boolean dryRun, MigrationPlan plan);

    /** Mutable per-run state. Not thread-safe; one instance per run. */
    final class MigrationPlan {
        private final Set<Long> migratedIds = new HashSet<>();
        /** Lower-cased legacy file names whose rows migrated in this run. */
        private final Set<String> migratedSources = new HashSet<>();
        /** Lower-cased new storage paths claimed in this run (dry-run: nothing on disk yet). */
        private final Set<String> plannedPaths = new HashSet<>();
        private final Set<Long> rewrittenUserIds = new HashSet<>();
        private final Set<Long> rewrittenPostIds = new HashSet<>();
        private final Map<String, List<String>> newUrlsByOldUrl = new HashMap<>();

        public void record(RowOutcome outcome) {
            if (outcome.status() != RowStatus.MIGRATED) {
                return;
            }
            migratedIds.add(outcome.mediaId());
            migratedSources.add(outcome.sourceFile().toLowerCase(Locale.ROOT));
            plannedPaths.add(outcome.newPath().toLowerCase(Locale.ROOT));
            rewrittenUserIds.addAll(outcome.rewrittenUserIds());
            rewrittenPostIds.addAll(outcome.rewrittenPostIds());
            newUrlsByOldUrl.computeIfAbsent(outcome.oldUrl(), k -> new ArrayList<>()).add(outcome.newUrl());
        }

        public boolean isMigratedSource(String legacyName) {
            return migratedSources.contains(legacyName.toLowerCase(Locale.ROOT));
        }

        public boolean isMigrated(Long id) {
            return migratedIds.contains(id);
        }

        public boolean isPathPlanned(String path) {
            return plannedPaths.contains(path.toLowerCase(Locale.ROOT));
        }

        public Set<String> plannedPaths() {
            return plannedPaths;
        }

        public boolean isUserRewritten(Long id) {
            return rewrittenUserIds.contains(id);
        }

        public boolean isPostRewritten(Long id) {
            return rewrittenPostIds.contains(id);
        }

        public Map<String, List<String>> newUrlsByOldUrl() {
            return newUrlsByOldUrl;
        }
    }

    enum RowStatus { MIGRATED, SKIPPED, MISSING_FILE }

    record RowOutcome(
            Long mediaId,
            RowStatus status,
            String sourceFile,
            boolean sharedSource,
            boolean blobCreated,
            boolean thumbnailMoved,
            String oldUrl,
            String newUrl,
            String newPath,
            List<String> referencesRewritten,
            List<Long> rewrittenUserIds,
            List<Long> rewrittenPostIds,
            List<String> warnings) {

        public static RowOutcome skipped(Long id) {
            return new RowOutcome(id, RowStatus.SKIPPED, null, false, false, false, null, null, null,
                    List.of(), List.of(), List.of(), List.of());
        }

        public static RowOutcome missing(Long id, String sourceFile) {
            return new RowOutcome(id, RowStatus.MISSING_FILE, sourceFile, false, false, false, null, null, null,
                    List.of(), List.of(), List.of(), List.of());
        }
    }

    record MigrationReport(
            boolean dryRun,
            int scanned,
            int migrated,
            int sharedFilesSplit,
            int blobsCreated,
            int thumbnailsMoved,
            List<String> rows,
            List<String> referencesRewritten,
            List<String> movedToOrphaned,
            List<String> missingFiles,
            List<String> contentEmbedsFound,
            List<String> unresolvedReferences,
            List<String> refCountMismatches,
            List<String> warnings,
            List<String> errors) {
    }
}
