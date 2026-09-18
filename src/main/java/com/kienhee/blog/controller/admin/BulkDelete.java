package com.kienhee.blog.controller.admin;

import com.kienhee.blog.controller.BusinessMessages;
import org.springframework.dao.DataIntegrityViolationException;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.Objects;
import java.util.function.Consumer;

/**
 * Shared "apply an action to the selected rows" for admin list pages and the Trash. Each id goes through the
 * resource's normal single action (same rules, own transaction); rows that can't be handled are skipped with
 * the reason, and the flash message says exactly what happened, e.g.
 * "2 categories moved to trash. 1 skipped — Design: ...".
 *
 * <p>The words come from the message catalogue: callers pass a <b>noun key</b>
 * ({@code bulk.noun.categories}, which has {@code .one} / {@code .other} / {@code .bare} forms) and a
 * <b>verb key</b> ({@code bulk.verb.trashed}, ...), never a noun or a verb in English.</p>
 */
final class BulkDelete {

    /** What deleting means on the content list pages: the rows go to the Trash. */
    static final String MOVED_TO_TRASH = "bulk.verb.trashed";
    static final String DELETED = "bulk.verb.deleted";
    static final String RESTORED = "bulk.verb.restored";
    static final String PURGED = "bulk.verb.purged";

    private BulkDelete() {
    }

    /** Deleting from a list page: rows move to the trash. */
    static void run(BusinessMessages messages, List<Long> ids, String nounKey, Map<Long, String> names,
                    Consumer<Long> delete, RedirectAttributes redirect) {
        run(messages, ids, nounKey, MOVED_TO_TRASH, names, delete, redirect);
    }

    /**
     * @param nounKey base key for the noun ("bulk.noun.categories"), with .one/.other/.bare forms
     * @param verbKey what happened ("bulk.verb.trashed", "bulk.verb.restored", ...)
     * @param names   id → display name, for naming skipped rows
     * @param action  the single-row action
     */
    static void run(BusinessMessages messages, List<Long> ids, String nounKey, String verbKey,
                    Map<Long, String> names, Consumer<Long> action, RedirectAttributes redirect) {
        List<Long> selected = ids == null ? List.of() : ids.stream().filter(Objects::nonNull).distinct().toList();
        if (selected.isEmpty()) {
            redirect.addFlashAttribute("errorMessage",
                    messages.get("bulk.select_one", messages.get(nounKey + ".bare")));
            return;
        }

        int done = 0;
        List<String> skipped = new ArrayList<>();
        for (Long id : selected) {
            String name = names.getOrDefault(id, "#" + id);
            try {
                action.accept(id);
                done++;
            } catch (IllegalArgumentException e) {
                skipped.add(name + ": " + messages.text(e));
            } catch (DataIntegrityViolationException e) {
                skipped.add(name + ": " + messages.get("bulk.still_used"));
            }
        }

        String counted = messages.get(nounKey + (done == 1 ? ".one" : ".other"), done);
        String message = messages.get(verbKey, counted);
        if (skipped.isEmpty()) {
            redirect.addFlashAttribute("successMessage", message);
        } else {
            redirect.addFlashAttribute("errorMessage",
                    message + " " + messages.get("bulk.skipped", skipped.size(), String.join(" ", skipped)));
        }
    }
}
