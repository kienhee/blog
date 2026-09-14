package com.kienhee.blog.config;

import com.kienhee.blog.service.MediaBlobBackfillService;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.boot.ApplicationArguments;
import org.springframework.boot.ApplicationRunner;
import org.springframework.boot.autoconfigure.condition.ConditionalOnProperty;
import org.springframework.core.annotation.Order;
import org.springframework.stereotype.Component;

/**
 * Chay backfill blob cho media mot lan luc khoi dong — chi khi duoc bat mot cach co y thuc.
 *
 * <p><b>Mac dinh la KHONG LAM GI.</b> Bean nay khong ton tai tru khi
 * {@code app.media.backfill.enabled=true}; va ke ca khi da bat, mac dinh van la dry-run
 * ({@code app.media.backfill.dry-run} mac dinh {@code true}) — chi in bao cao, khong ghi gi.
 *
 * <p>Cach dung:
 * <pre>
 *   # 1. Xem truoc (khong ghi gi):
 *   mvnw.cmd spring-boot:run -Dspring-boot.run.arguments=--app.media.backfill.enabled=true
 *
 *   # 2. Chay that, sau khi da doc ky bao cao o buoc 1 VA da backup DB + thu muc uploads:
 *   mvnw.cmd spring-boot:run "-Dspring-boot.run.arguments=--app.media.backfill.enabled=true --app.media.backfill.dry-run=false"
 * </pre>
 *
 * <p>Sau khi chay that thanh cong, hay TAT lai property de lan khoi dong sau khong quet nua.
 */
@Slf4j
@Component
@Order(Integer.MAX_VALUE)
@ConditionalOnProperty(name = "app.media.backfill.enabled", havingValue = "true")
@RequiredArgsConstructor
public class MediaBlobBackfillRunner implements ApplicationRunner {

    private final MediaBlobBackfillService mediaBlobBackfillService;

    /** Mac dinh TRUE: lo bat property enabled thi cung chi in bao cao, khong ghi gi. */
    @Value("${app.media.backfill.dry-run:true}")
    private boolean dryRun;

    @Override
    public void run(ApplicationArguments args) {
        log.info("Media blob backfill starting (dryRun={}). Nothing is ever deleted by this job.", dryRun);
        MediaBlobBackfillService.BackfillReport report = mediaBlobBackfillService.backfill(dryRun);
        if (dryRun) {
            log.info("Media blob backfill was a DRY-RUN: no database row and no file was touched. "
                    + "Re-run with --app.media.backfill.dry-run=false to apply {} change(s).",
                    report.rowsLinked());
        }
        if (!report.errors().isEmpty()) {
            log.warn("Media blob backfill finished with {} error(s) — see the report above.",
                    report.errors().size());
        }
    }
}
