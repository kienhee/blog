package com.kienhee.blog.controller.admin;

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
 */
final class BulkDelete {

    /** What deleting means on the content list pages: the rows go to the Trash. */
    static final String MOVED_TO_TRASH = "moved to trash";

    private BulkDelete() {
    }

    /** Deleting from a list page: rows move to the trash. */
    static void run(List<Long> ids, String singular, String plural, Map<Long, String> names,
                    Consumer<Long> delete, RedirectAttributes redirect) {
        run(ids, singular, plural, MOVED_TO_TRASH, names, delete, redirect);
    }

    /**
     * @param singular / plural noun for the message ("category" / "categories")
     * @param verb     past tense for the message ("moved to trash", "restored", "deleted permanently", "deleted")
     * @param names    id → display name, for naming skipped rows
     * @param action   the single-row action
     */
    static void run(List<Long> ids, String singular, String plural, String verb, Map<Long, String> names,
                    Consumer<Long> action, RedirectAttributes redirect) {
        List<Long> selected = ids == null ? List.of() : ids.stream().filter(Objects::nonNull).distinct().toList();
        if (selected.isEmpty()) {
            redirect.addFlashAttribute("errorMessage", "Select at least one " + singular + ".");
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
                skipped.add(name + ": " + e.getMessage());
            } catch (DataIntegrityViolationException e) {
                skipped.add(name + ": it is still used elsewhere.");
            }
        }

        String message = done + " " + (done == 1 ? singular : plural) + " " + verb + ".";
        if (skipped.isEmpty()) {
            redirect.addFlashAttribute("successMessage", message);
        } else {
            redirect.addFlashAttribute("errorMessage", message + " " + skipped.size() + " skipped — " + String.join(" ", skipped));
        }
    }
}
