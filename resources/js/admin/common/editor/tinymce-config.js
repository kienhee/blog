// FILE: /resources/js/tinymce-config.js

document.addEventListener("DOMContentLoaded", () => {
    // Utility: Get admin theme
    const getAdminTheme = () => {
        // Get template name from document
        const templateName =
            document.documentElement.getAttribute("data-template") ||
            "vertical-menu-template";

        // Get stored style from localStorage (same way admin system does)
        const storedStyle =
            localStorage.getItem(`templateCustomizer-${templateName}--Style`) ||
            (window.templateCustomizer?.settings?.defaultStyle ?? "light");

        // If system mode, check system preference
        if (storedStyle === "system") {
            return window.matchMedia("(prefers-color-scheme: dark)").matches
                ? "dark"
                : "light";
        }

        return storedStyle; // 'light' or 'dark'
    };

    // Utility: Check if dark mode
    const isDarkMode = () => {
        return getAdminTheme() === "dark";
    };

    // Utility: Hide loading spinner
    const hideLoader = () => {
        const loader = document.getElementById("editor-loading");
        const editor = document.getElementById("editor");
        if (loader) loader.classList.add("d-none");
        if (editor) editor.classList.remove("d-none");
    };

    // Initialize Highlight.js on code blocks in TinyMCE editor
    const initHighlightJS = (editor) => {
        if (typeof hljs === "undefined") {
            console.warn("Highlight.js is not loaded");
            return;
        }

        try {
            const iframe = editor.getDoc();
            if (!iframe) return;

            // Find all code blocks (pre > code or pre with class)
            const codeBlocks = iframe.querySelectorAll(
                'pre code, pre[class*="language"], code[class*="language"]'
            );

            codeBlocks.forEach((block) => {
                // Only highlight if not already highlighted
                if (!block.classList.contains("hljs")) {
                    hljs.highlightElement(block);
                }
            });
        } catch (error) {
            console.warn("Error initializing Highlight.js in TinyMCE:", error);
        }
    };

    // Initialize TinyMCE with current theme
    const initTinyMCE = () => {
        const useDarkMode = isDarkMode();

        tinymce.init({
            selector: "textarea#editor",

            // Basic settings
            height: 600,
            max_height: 800,
            menubar: false,
            promotion: false,
            branding: false,
            license_key: "gpl",
            // Dark mode support - sync with admin theme
            skin: useDarkMode ? "oxide-dark" : "oxide",

            // Unified CSS for article content - ensures 100% consistency with client-side view
            // Import both article-content.css and Highlight.js theme CSS
            content_css: [
                window.TINYMCE_EDITOR_CONTENT_CSS ||
                    "/resources/shared/css/article-content.css",
                window.HIGHLIGHT_JS_CSS ||
                    "/resources/shared/vendor/highlight/styles/atom-one-dark.min.css",
            ],

            // Set body class to match client-side wrapper
            body_class: "article-content",

            // Plugins - All free plugins available in TinyMCE
            plugins: [
                "accordion", // Accordion/collapsible content
                "advlist", // Advanced lists
                "anchor", // Anchors/bookmarks
                "autolink", // Auto-detect URLs
                "autoresize", // Auto resize editor
                "autosave", // Auto save content
                "charmap", // Special characters
                "code", // HTML source code
                "hljs_codeblock", // Highlight.js code blocks with auto-detect
                "inline_code", // Inline code formatting
                "directionality", // Text direction (LTR/RTL)
                "emoticons", // Emojis
                "fullscreen", // Fullscreen mode
                "help", // Help dialog
                "image", // Images
                "importcss", // Import CSS classes
                "insertdatetime", // Insert date/time
                "link", // Links
                "lists", // Lists
                "media", // Video/audio embed
                "nonbreaking", // Non-breaking space
                "pagebreak", // Page breaks
                "preview", // Preview
                "quickbars", // Quick toolbars on selection
                "save", // Save button
                "searchreplace", // Find & replace
                "table", // Tables
                "visualblocks", // Show blocks outline
                "visualchars", // Show invisible characters
                "wordcount", // Word count
            ],

            // Toolbar
            toolbar:
                "undo redo | accordion accordionremove | blocks fontsize | bold italic underline strikethrough | inline_code | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | table media | forecolor backcolor removeformat | charmap emoticons | code fullscreen preview | save | pagebreak anchor hljs_codeblock | ltr rtl | searchreplace visualblocks | help",

            // Sticky toolbar (giữ thanh công cụ khi cuộn trang)
            toolbar_sticky: true,
            // Độ lệch để không đè lên navbar admin (có thể chỉnh lại nếu cần)
            toolbar_sticky_offset: 70,

            // Content style - prevent selection and cursor in code blocks + custom scrollbar
            content_style: `
                body.article-content { 
                    padding: 1em; 
                    overflow-y: auto;
                    overflow-x: hidden;
                }
                /* Custom scrollbar for editor content area */
                body.article-content::-webkit-scrollbar {
                    width: 10px;
                }
                body.article-content::-webkit-scrollbar-track {
                    background: ${useDarkMode ? "#1e2126" : "#f1f1f1"};
                    border-radius: 5px;
                }
                body.article-content::-webkit-scrollbar-thumb {
                    background: ${useDarkMode ? "#5c6370" : "#888"};
                    border-radius: 5px;
                    border: 2px solid ${useDarkMode ? "#1e2126" : "#f1f1f1"};
                }
                body.article-content::-webkit-scrollbar-thumb:hover {
                    background: ${useDarkMode ? "#6c7280" : "#555"};
                }
                /* Prevent text selection and cursor in Highlight.js code blocks */
                body.article-content pre.hljs-code-block,
                body.article-content pre.hljs-code-block code {
                    user-select: none !important;
                    -webkit-user-select: none !important;
                    -moz-user-select: none !important;
                    -ms-user-select: none !important;
                    cursor: pointer !important;
                }
                /* Custom scrollbar for code blocks in editor */
                body.article-content pre::-webkit-scrollbar,
                body.article-content pre.hljs-code-block::-webkit-scrollbar {
                    height: 4px;
                }
                body.article-content pre::-webkit-scrollbar-track,
                body.article-content pre.hljs-code-block::-webkit-scrollbar-track {
                    background: ${useDarkMode ? "#1e2126" : "#f1f1f1"};
                    border-radius: 2px;
                }
                body.article-content pre::-webkit-scrollbar-thumb,
                body.article-content pre.hljs-code-block::-webkit-scrollbar-thumb {
                    background: ${useDarkMode ? "#5c6370" : "#888"};
                    border-radius: 2px;
                    border: 0;
                }
                body.article-content pre::-webkit-scrollbar-thumb:hover,
                body.article-content pre.hljs-code-block::-webkit-scrollbar-thumb:hover {
                    background: ${useDarkMode ? "#6c7280" : "#555"};
                }
                body.article-content pre,
                body.article-content pre.hljs-code-block {
                    scrollbar-width: thin;
                    scrollbar-color: ${
                        useDarkMode ? "#5c6370 #1e2126" : "#888 #f1f1f1"
                    };
                }
            `,

            // File picker callback - Laravel File Manager
            file_picker_callback: (callback, value, meta) => {
                const width = Math.min(window.innerWidth * 0.8, 1200);
                const height = Math.min(window.innerHeight * 0.8, 800);
                const type = meta.filetype === "image" ? "Images" : "Files";
                const url = `/filemanager?editor=${meta.fieldname}&type=${type}`;

                tinymce.activeEditor.windowManager.openUrl({
                    url,
                    title: "File Manager",
                    width,
                    height,
                    resizable: true,
                    close_previous: false,
                    onMessage: (api, message) => {
                        callback(message.content);
                    },
                });
            },

            // URL settings
            relative_urls: false,
            remove_script_host: false,
            convert_urls: true,

            // Image options
            image_advtab: true,
            image_caption: true,

            // Paste options
            paste_data_images: true,

            // Autosave settings
            autosave_ask_before_unload: true,
            autosave_interval: "30s",
            autosave_prefix: "{path}{query}-{id}-",
            autosave_restore_when_empty: false,
            autosave_retention: "2m",

            // Autoresize settings
            min_height: 600,
            max_height: 800,
            autoresize_bottom_margin: 50,
            autoresize_overflow_padding: 50,
            autoresize_on_init: true,

            // Quickbars settings
            quickbars_selection_toolbar:
                "bold italic hljs_codeblock quicklink h2 h3 blockquote quickimage quicktable",
            quickbars_insert_toolbar: "image media table accordion hljs_codeblock hr",

            // Context menu
            contextmenu: "link image table",

            // Toolbar mode
            toolbar_mode: "sliding",

            // Non-editable class
            noneditable_class: "mceNonEditable",

            // Import CSS settings
            importcss_append: true,

            // Preserve HTML classes and attributes (important for Highlight.js)
            valid_elements: "*[*]",
            extended_valid_elements: "*[*]",
            valid_children: "+body[style]",

            // Format definitions - đảm bảo code formatter hoạt động
            formats: {
                code: { inline: "code" },
            },

            // Setup - Hide loader when initialized and initialize Highlight.js
            setup: (editor) => {
                // Store editor instance globally for theme updates
                window.tinyMCEEditor = editor;

                editor.on("init", () => {
                    hideLoader();
                    // Initialize Highlight.js on editor content
                    initHighlightJS(editor);

                    // Thêm "Inline code" vào blocks dropdown (Paragraph dropdown)
                    // Override blocks menu để thêm inline_code
                    try {
                        const registry = editor.ui.registry;
                        const allItems = registry.getAll();

                        // Lấy blocks menu items hiện tại
                        if (allItems.menuItems && allItems.menuItems.blocks) {
                            const blocksMenu = allItems.menuItems.blocks;
                            if (blocksMenu.getItems) {
                                const originalGetItems = blocksMenu.getItems;
                                blocksMenu.getItems = function () {
                                    const items = originalGetItems.call(this);
                                    // Thêm inline_code vào sau "Preformatted"
                                    const preformattedIndex = items.findIndex(
                                        function (item) {
                                            return item === "preformatted";
                                        }
                                    );
                                    if (preformattedIndex !== -1) {
                                        items.splice(
                                            preformattedIndex + 1,
                                            0,
                                            "|",
                                            "inline_code"
                                        );
                                    } else {
                                        items.push("|", "inline_code");
                                    }
                                    return items;
                                };
                            }
                        }
                    } catch (e) {
                        console.warn(
                            "Could not add inline_code to blocks dropdown:",
                            e
                        );
                    }
                });

                // Re-run Highlight.js when content changes
                editor.on("SetContent", () => {
                    setTimeout(() => {
                        initHighlightJS(editor);
                    }, 100);
                });

                // Re-run Highlight.js after node change (e.g., when code block is inserted)
                editor.on("NodeChange", () => {
                    setTimeout(() => {
                        initHighlightJS(editor);
                    }, 100);
                });
            },
        });
    };

    // Initialize TinyMCE
    initTinyMCE();

    // Listen for theme changes from admin system
    const templateName =
        document.documentElement.getAttribute("data-template") ||
        "vertical-menu-template";

    // Listen to localStorage changes (when admin theme changes in other tabs only)
    window.addEventListener("storage", (e) => {
        if (e.key === `templateCustomizer-${templateName}--Style`) {
            updateTinyMCETheme();
        }
    });

    // Function to update TinyMCE theme
    const updateTinyMCETheme = () => {
        if (!window.tinyMCEEditor) return;

        const editor = window.tinyMCEEditor;
        const currentContent = editor.getContent();

        // Save current content and destroy editor
        editor.remove();

        // Re-initialize with new theme
        setTimeout(() => {
            initTinyMCE();
            // Restore content after a short delay
            setTimeout(() => {
                if (window.tinyMCEEditor) {
                    window.tinyMCEEditor.setContent(currentContent);
                }
            }, 500);
        }, 100);
    };

    // Only listen to clicks on theme switcher (if available)
    const styleSwitcher = document.querySelector(".dropdown-style-switcher");
    if (styleSwitcher) {
        const styleSwitcherItems =
            styleSwitcher.querySelectorAll(".dropdown-item");
        styleSwitcherItems.forEach((item) => {
            item.addEventListener("click", () => {
                // Wait a bit for localStorage to update
                setTimeout(() => {
                    updateTinyMCETheme();
                }, 100);
            });
        });
    }
});
