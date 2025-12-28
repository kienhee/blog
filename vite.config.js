import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/js/app.js",
                // Common - Dùng chung cho tất cả pages
                "resources/js/admin/common/utils/helper.js",
                "resources/js/admin/common/ui/badge-count.js",
                "resources/js/admin/common/ui/toastr-config.js",
                "resources/js/admin/common/forms/form-loading.js",
                "resources/js/admin/common/forms/generate-slug.js",
                "resources/js/admin/common/uploads/upload-images.js",
                "resources/js/admin/common/uploads/upload-image-alone.js",
                "resources/js/admin/common/forms/forms-selects.js",
                "resources/js/admin/common/editor/tinymce-config.js",
                "resources/js/admin/common/editor/tinymce-hljs-plugin.js",
                "resources/js/admin/common/editor/tinymce-inline-code-plugin.js",
                "resources/js/admin/common/uploads/upload-avatar.js",
                // -----------Pages-----------
                //Category
                "resources/js/admin/pages/category/list.js",
                "resources/js/admin/pages/category/form.js",
                "resources/js/admin/pages/category/tree.js",
                //Post
                "resources/js/admin/pages/post/list.js",
                "resources/js/admin/pages/post/form.js",
                "resources/js/admin/pages/post/quick-category.js",
                "resources/js/admin/pages/post/quick-hashtag.js",
                //Hashtag
                "resources/js/admin/pages/hashtag/list.js",
                "resources/js/admin/pages/hashtag/form.js",
                "resources/js/admin/pages/hashtag/slug.js",
                //Contact
                "resources/js/admin/pages/contact/list.js",
                "resources/js/admin/pages/contact/detail.js",
                //Auth
                "resources/js/admin/pages/auth/index.js",
                //Profile
                "resources/js/admin/pages/profile/index.js",
                "resources/js/admin/pages/profile/change-password.js",
                //User
                "resources/js/admin/pages/user/list.js",
                "resources/js/admin/pages/user/form.js",
                // -----------Pages-----------

                //Client
                "resources/scss/client/client.scss",
                // Client - Layout
                "resources/js/client/layout/navbar.js",
                // Client - Post
                "resources/js/client/post/index.js",
                // Client - Pages
                "resources/js/client/pages/profile/index.js",
                "resources/js/client/pages/profile/change-password.js"
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
