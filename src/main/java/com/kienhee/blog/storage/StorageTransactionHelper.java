package com.kienhee.blog.storage;

import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Component;
import org.springframework.transaction.support.TransactionSynchronization;
import org.springframework.transaction.support.TransactionSynchronizationManager;

import java.io.ByteArrayInputStream;
import java.util.ArrayDeque;
import java.util.ArrayList;
import java.util.Deque;
import java.util.List;

/**
 * The single place that ties disk operations to the surrounding DB transaction
 * (contract: "ghi/di chuyển file trước, commit DB sau, rollback thì hoàn tác").
 *
 * <ul>
 *   <li><b>Reversible operations</b> (write, create dir, move file, move dir) run
 *       <em>immediately</em>, and their inverse is pushed onto a per-transaction undo stack. On
 *       rollback the stack is unwound LIFO, so a chain like "move dir, then move file into it"
 *       is undone in the right order.</li>
 *   <li><b>Irreversible operations</b> (delete file, delete empty dir) are <em>deferred</em> until
 *       after commit: if the DB rolls back, nothing was destroyed. If the delete then fails, the
 *       worst outcome is an orphan file, which is recoverable — the reverse (a row pointing at a
 *       deleted file) is not.</li>
 * </ul>
 *
 * <p>Every method requires an active Spring-managed transaction; calling one without it is a
 * programming error and fails fast rather than silently losing the compensation.</p>
 */
@Slf4j
@Component
@RequiredArgsConstructor
public class StorageTransactionHelper {

    private final FilesystemStorage storage;

    /** Writes the file now; deletes it again if the transaction rolls back. */
    public void writeFile(StoragePath path, byte[] bytes) {
        State state = state();
        storage.writeFile(path, new ByteArrayInputStream(bytes), bytes.length);
        state.undo.push(new Action("delete written file " + path, () -> storage.deleteFile(path)));
    }

    /** Creates the directory now (if missing); removes it again on rollback if still empty. */
    public void createDirectory(StoragePath path) {
        State state = state();
        if (storage.directoryExists(path)) {
            return;
        }
        storage.createDirectory(path);
        state.undo.push(new Action("remove created dir " + path, () -> storage.deleteDirectoryIfEmpty(path)));
    }

    /**
     * Moves the file now; moves it back on rollback. Caller must have resolved a free target
     * name first ({@link FilenameSanitizer#sanitizeUnique}) — {@code moveFile} overwrites.
     */
    public void moveFile(StoragePath from, StoragePath to) {
        State state = state();
        if (from.equals(to)) {
            return;
        }
        storage.moveFile(from, to);
        state.undo.push(new Action("move file back " + to + " -> " + from, () -> storage.moveFile(to, from)));
    }

    /** Moves the directory now; moves it back on rollback. */
    public void moveDirectory(StoragePath from, StoragePath to) {
        State state = state();
        if (from.equals(to)) {
            return;
        }
        storage.moveDirectory(from, to);
        state.undo.push(new Action("move dir back " + to + " -> " + from, () -> storage.moveDirectory(to, from)));
    }

    /** Deletes the file only once the transaction has committed. */
    public void deleteFileAfterCommit(StoragePath path) {
        state().afterCommit.add(new Action("delete file " + path, () -> storage.deleteFile(path)));
    }

    /** Deletes the directory (only if empty) once the transaction has committed. */
    public void deleteDirectoryIfEmptyAfterCommit(StoragePath path) {
        state().afterCommit.add(new Action("delete empty dir " + path, () -> storage.deleteDirectoryIfEmpty(path)));
    }

    /** Runs arbitrary work after commit (e.g. kicking off async processing of a new row). */
    public void runAfterCommit(String description, Runnable work) {
        state().afterCommit.add(new Action(description, work));
    }

    // ------------------------------------------------------------------------

    private State state() {
        if (!TransactionSynchronizationManager.isSynchronizationActive()) {
            throw new IllegalStateException(
                    "Disk operations must run inside a transaction so they can be compensated");
        }
        State state = (State) TransactionSynchronizationManager.getResource(State.KEY);
        if (state == null) {
            State created = new State();
            TransactionSynchronizationManager.bindResource(State.KEY, created);
            TransactionSynchronizationManager.registerSynchronization(new TransactionSynchronization() {
                @Override
                public void afterCompletion(int status) {
                    TransactionSynchronizationManager.unbindResourceIfPossible(State.KEY);
                    created.complete(status);
                }
            });
            state = created;
        }
        return state;
    }

    private record Action(String description, Runnable work) {
    }

    private static final class State {
        private static final Object KEY = new Object();

        private final Deque<Action> undo = new ArrayDeque<>();
        private final List<Action> afterCommit = new ArrayList<>();

        void complete(int status) {
            if (status == TransactionSynchronization.STATUS_COMMITTED) {
                for (Action action : afterCommit) {
                    run(action, "after commit");
                }
            } else if (status == TransactionSynchronization.STATUS_ROLLED_BACK) {
                while (!undo.isEmpty()) {
                    run(undo.pop(), "rollback compensation");
                }
            } else {
                // Unknown outcome (e.g. commit threw mid-way): guessing either direction could
                // destroy data, so leave the disk as it is and make it loud.
                log.error("Transaction outcome unknown; {} pending disk compensation(s) NOT applied: {}",
                        undo.size(), undo.stream().map(Action::description).toList());
            }
        }

        private static void run(Action action, String phase) {
            try {
                action.work().run();
            } catch (RuntimeException e) {
                log.error("Storage {} failed ({}): {}", phase, action.description(), e.getMessage());
            }
        }
    }
}
