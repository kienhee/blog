/**
 * TinyMCE Plugin: Inline Code
 *
 * Plugin để đánh dấu text đã chọn thành inline code (<code>)
 *
 * @version 1.0.0
 * @requires TinyMCE 5.x or 6.x
 */

(function () {
    "use strict";

    // Plugin name
    const PLUGIN_NAME = "inline_code";

    // Register plugin
    tinymce.PluginManager.add(PLUGIN_NAME, function (editor, url) {
        /**
         * Toggle inline code formatting
         */
        function toggleInlineCode() {
            const selection = editor.selection;
            const selectedText = selection.getContent({ format: "text" });

            // Nếu không có text được chọn, không làm gì
            if (!selectedText || !selectedText.trim()) {
                editor.windowManager.alert(
                    "Vui lòng chọn text để đánh dấu là code."
                );
                return;
            }

            // Kiểm tra xem selection có đang trong thẻ <code> không
            const node = selection.getNode();
            const codeElement = editor.dom.getParent(node, "code");

            if (codeElement) {
                // Nếu đã là code, bỏ format
                editor.formatter.remove("code");
            } else {
                // Nếu chưa là code, apply format
                editor.formatter.apply("code");
            }
        }

        // Register command
        editor.addCommand("mceInlineCode", toggleInlineCode);

        // Register toolbar toggle button
        editor.ui.registry.addToggleButton(PLUGIN_NAME, {
            icon: "format-code",
            tooltip: "Inline code (Ctrl+Shift+C)",
            onAction: toggleInlineCode,
            onSetup: function (api) {
                // Update button state based on selection
                function updateButtonState() {
                    const node = editor.selection.getNode();
                    const codeElement = editor.dom.getParent(node, "code");
                    api.setActive(codeElement !== null);
                }

                // Update on node change
                editor.on("NodeChange", updateButtonState);

                // Initial update
                updateButtonState();

                // Return function to cleanup
                return function () {
                    editor.off("NodeChange", updateButtonState);
                };
            },
        });

        // Register menu item
        editor.ui.registry.addMenuItem(PLUGIN_NAME, {
            icon: "code",
            text: "Inline code",
            onAction: toggleInlineCode,
            shortcut: "Ctrl+Shift+C",
        });

        // Register context menu item
        editor.ui.registry.addContextMenu(PLUGIN_NAME, {
            update: function (element) {
                // Hiển thị trong context menu khi có text được chọn
                const selection = editor.selection;
                const selectedText = selection.getContent({ format: "text" });
                return selectedText && selectedText.trim() ? [PLUGIN_NAME] : [];
            },
        });

        // Add keyboard shortcut (Ctrl+Shift+C)
        editor.addShortcut("Ctrl+Shift+C", "Inline code", "mceInlineCode");

        // Return plugin metadata
        return {
            getMetadata: function () {
                return {
                    name: "Inline Code Plugin",
                    version: "1.0.0",
                    author: "Dashboard Team",
                    url: "",
                };
            },
        };
    });
})();
