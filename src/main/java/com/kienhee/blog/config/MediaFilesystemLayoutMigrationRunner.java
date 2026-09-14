package com.kienhee.blog.config;

import com.kienhee.blog.service.MediaFilesystemLayoutMigrationService;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.boot.ApplicationArguments;
import org.springframework.boot.ApplicationRunner;
import org.springframework.boot.autoconfigure.condition.ConditionalOnProperty;
import org.springframework.core.annotation.Order;
import org.springframework.stereotype.Component;

/**
 * Runs the flat-uploads -> filesystem-mirror data migration once at startup, only when asked.
 *
 * <p><b>Does nothing by default.</b> The bean only exists with
 * {@code app.media.fs-migration.enabled=true}, and even then it is a DRY-RUN unless
 * {@code app.media.fs-migration.dry-run=false}.</p>
 *
 * <pre>
 *   # 1. Preview (writes nothing):
 *   mvnw.cmd spring-boot:run "-Dspring-boot.run.arguments=--app.media.fs-migration.enabled=true"
 *
 *   # 2. Apply, after reading the preview AND backing up the DB and uploads/:
 *   mvnw.cmd spring-boot:run "-Dspring-boot.run.arguments=--app.media.fs-migration.enabled=true --app.media.fs-migration.dry-run=false"
 * </pre>
 *
 * <p>Turn the property off again afterwards (a re-run is harmless but pointless).</p>
 */
@Slf4j
@Component
@Order(Integer.MAX_VALUE)
@ConditionalOnProperty(name = "app.media.fs-migration.enabled", havingValue = "true")
@RequiredArgsConstructor
public class MediaFilesystemLayoutMigrationRunner implements ApplicationRunner {

    private final MediaFilesystemLayoutMigrationService migrationService;

    @Value("${app.media.fs-migration.dry-run:true}")
    private boolean dryRun;

    @Override
    public void run(ApplicationArguments args) {
        log.info("Media filesystem layout migration starting (dryRun={}). This job never deletes a file.", dryRun);
        MediaFilesystemLayoutMigrationService.MigrationReport report = migrationService.migrate(dryRun);
        if (dryRun) {
            log.info("Media filesystem layout migration was a DRY-RUN: nothing written. Re-run with "
                    + "--app.media.fs-migration.dry-run=false to apply {} row(s).", report.migrated());
        }
        if (!report.errors().isEmpty() || !report.unresolvedReferences().isEmpty()) {
            log.warn("Media filesystem layout migration: {} error(s), {} unresolved reference(s) — see report.",
                    report.errors().size(), report.unresolvedReferences().size());
        }
    }
}
