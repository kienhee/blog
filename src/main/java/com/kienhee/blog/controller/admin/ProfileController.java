package com.kienhee.blog.controller.admin;

import com.kienhee.blog.dto.ChangePasswordRequest;
import com.kienhee.blog.dto.ProfileUpdateRequest;
import com.kienhee.blog.entity.Media;
import com.kienhee.blog.entity.MediaFolder;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.AuthService;
import com.kienhee.blog.service.MediaFolderService;
import com.kienhee.blog.service.MediaService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.validation.BindingResult;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.security.Principal;
import java.util.Locale;

@Controller
@RequestMapping("/admin/profile")
@RequiredArgsConstructor
public class ProfileController {

    private static final String AVATARS_FOLDER_NAME = "Avatars";

    private final AuthService authService;
    private final UserRepository userRepository;
    private final MediaService mediaService;
    private final MediaFolderService mediaFolderService;

    @GetMapping
    public String profile(Principal principal,
                          Model model,
                          @RequestParam(name = "tab", defaultValue = "info") String tab) {
        populateProfileRequest(principal, model);
        if (!model.containsAttribute("changePasswordRequest")) {
            model.addAttribute("changePasswordRequest", new ChangePasswordRequest());
        }
        model.addAttribute("activeTab", tab);
        return "admin/user/profile";
    }

    @PostMapping
    public String updateProfile(@Valid @ModelAttribute("profileRequest") ProfileUpdateRequest profileRequest,
                                BindingResult bindingResult,
                                Principal principal,
                                Model model,
                                RedirectAttributes redirectAttributes) {
        if (principal == null) {
            return "redirect:/auth/login";
        }

        if (bindingResult.hasErrors()) {
            if (!model.containsAttribute("changePasswordRequest")) {
                model.addAttribute("changePasswordRequest", new ChangePasswordRequest());
            }
            model.addAttribute("activeTab", "info");
            return "admin/user/profile";
        }

        try {
            authService.updateProfile(principal.getName(), profileRequest);
            redirectAttributes.addFlashAttribute("profileSuccess", "Profile updated successfully.");
            return "redirect:/admin/profile?tab=info";
        } catch (Exception e) {
            bindingResult.reject("profileError", e.getMessage() != null ? e.getMessage() : "Failed to update profile.");
            if (!model.containsAttribute("changePasswordRequest")) {
                model.addAttribute("changePasswordRequest", new ChangePasswordRequest());
            }
            model.addAttribute("activeTab", "info");
            return "admin/user/profile";
        }
    }

    @PostMapping("/password")
    public String changePassword(@Valid @ModelAttribute("changePasswordRequest") ChangePasswordRequest changePasswordRequest,
                                 BindingResult bindingResult,
                                 Principal principal,
                                 Model model,
                                 RedirectAttributes redirectAttributes) {
        if (principal == null) {
            return "redirect:/auth/login";
        }

        if (changePasswordRequest.getNewPassword() != null &&
                !changePasswordRequest.getNewPassword().isBlank() &&
                changePasswordRequest.getConfirmPassword() != null &&
                !changePasswordRequest.getNewPassword().equals(changePasswordRequest.getConfirmPassword())) {
            bindingResult.rejectValue("confirmPassword", "error.confirmPassword", "Passwords do not match.");
        }

        if (bindingResult.hasErrors()) {
            populateProfileRequest(principal, model);
            model.addAttribute("activeTab", "security");
            return "admin/user/profile";
        }

        try {
            authService.changePassword(principal.getName(), changePasswordRequest.getCurrentPassword(), changePasswordRequest.getNewPassword());
            redirectAttributes.addFlashAttribute("passwordSuccess", "Password updated successfully.");
            return "redirect:/admin/profile?tab=security";
        } catch (IllegalArgumentException e) {
            String msg = e.getMessage();
            if (msg != null && msg.contains("Current password")) {
                bindingResult.rejectValue("currentPassword", "error.currentPassword", msg);
            } else if (msg != null && msg.contains("New password")) {
                bindingResult.rejectValue("newPassword", "error.newPassword", msg);
            } else {
                bindingResult.reject("passwordError", msg != null ? msg : "Failed to change password.");
            }
            populateProfileRequest(principal, model);
            model.addAttribute("activeTab", "security");
            return "admin/user/profile";
        }
    }

    @PostMapping("/avatar")
    public String updateAvatar(@RequestParam("file") MultipartFile file,
                                Principal principal,
                                RedirectAttributes redirectAttributes) {
        if (principal == null) {
            return "redirect:/auth/login";
        }

        String contentType = file != null ? file.getContentType() : null;
        if (file == null || file.isEmpty()) {
            redirectAttributes.addFlashAttribute("errorMessage", "Please choose a photo to upload.");
            return "redirect:/admin/profile?tab=info";
        }
        if (contentType == null || !contentType.toLowerCase(Locale.ROOT).startsWith("image/")) {
            redirectAttributes.addFlashAttribute("errorMessage", "Avatar must be an image (JPG, PNG, WEBP, GIF or SVG).");
            return "redirect:/admin/profile?tab=info";
        }

        try {
            MediaFolder avatarsFolder = mediaFolderService.getOrCreateByName(AVATARS_FOLDER_NAME);
            Media media = mediaService.uploadMedia(file, principal.getName(), avatarsFolder.getId());

            User user = userRepository.findByEmail(principal.getName().trim().toLowerCase())
                    .orElseThrow(() -> new IllegalArgumentException("User not found."));
            user.setAvatarUrl(media.getUrl());
            userRepository.save(user);

            redirectAttributes.addFlashAttribute("profileSuccess", "Profile photo updated successfully.");
        } catch (IllegalArgumentException e) {
            redirectAttributes.addFlashAttribute("errorMessage", e.getMessage());
        }
        return "redirect:/admin/profile?tab=info";
    }

    private void populateProfileRequest(Principal principal, Model model) {
        if (!model.containsAttribute("profileRequest")) {
            String email = (principal != null) ? principal.getName() : null;
            User user = (email != null) ? userRepository.findByEmail(email.trim().toLowerCase()).orElse(null) : null;
            ProfileUpdateRequest profileRequest = ProfileUpdateRequest.builder()
                    .fullName(user != null ? user.getFullName() : "")
                    .email(user != null ? user.getEmail() : (email != null ? email : ""))
                    .phone(user != null ? user.getPhone() : "")
                    .address(user != null ? user.getAddress() : "")
                    .bio(user != null ? user.getBio() : "")
                    .build();
            model.addAttribute("profileRequest", profileRequest);
        }
    }
}

