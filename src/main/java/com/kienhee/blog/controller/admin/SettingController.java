package com.kienhee.blog.controller.admin;

import com.kienhee.blog.service.SettingService;
import lombok.RequiredArgsConstructor;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.util.MultiValueMap;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.util.LinkedHashMap;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * Site settings, stored as generic key/value pairs ({@link SettingService}).
 *
 * <p>Convention for the form: every field named {@code settings[<key>]} is saved under {@code <key>}
 * and every other parameter (e.g. {@code _csrf}) is ignored — so a new setting only needs a new field
 * in {@code settings.html}. Fields that are not posted are left untouched. When a name is posted more
 * than once the last value wins, which lets a checkbox follow a hidden {@code false} fallback.
 */
@Controller
@RequestMapping("/admin/settings")
@RequiredArgsConstructor
@PreAuthorize("hasAuthority('settings:view')")
public class SettingController {

    private static final Pattern FIELD = Pattern.compile("^settings\\[(.+)]$");

    private final SettingService settingService;

    @GetMapping
    public String settings(Model model) {
        model.addAttribute("settings", settingService.getAll());
        return "admin/setting/settings";
    }

    @PostMapping
    @PreAuthorize("hasAuthority('settings:edit')")
    public String save(@RequestParam MultiValueMap<String, String> params, RedirectAttributes redirectAttributes) {
        Map<String, String> values = new LinkedHashMap<>();
        params.forEach((name, posted) -> {
            Matcher field = FIELD.matcher(name);
            if (field.matches() && posted != null && !posted.isEmpty()) {
                values.put(field.group(1), posted.get(posted.size() - 1));
            }
        });

        try {
            settingService.saveAll(values);
            redirectAttributes.addFlashAttribute("successMessage", "Settings saved.");
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", e.getMessage());
        }
        return "redirect:/admin/settings";
    }
}
