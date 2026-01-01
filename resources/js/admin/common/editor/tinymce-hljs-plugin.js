/**
 * TinyMCE Plugin: Highlight.js Code Block
 *
 * A complete plugin for inserting and editing code blocks with Highlight.js syntax highlighting.
 * Replaces TinyMCE's default codesample plugin (Prism.js) with Highlight.js.
 *
 * @version 1.0.0
 * @requires TinyMCE 5.x or 6.x
 * @requires Highlight.js
 */

(function () {
    "use strict";

    // Plugin name
    const PLUGIN_NAME = "hljs_codeblock";

    // Default language list
    const DEFAULT_LANGUAGES = [
        { text: "Auto-detect", value: "auto" },
        { text: "HTML/XML", value: "html" },
        { text: "JavaScript", value: "javascript" },
        { text: "TypeScript", value: "typescript" },
        { text: "CSS", value: "css" },
        { text: "PHP", value: "php" },
        { text: "Python", value: "python" },
        { text: "Java", value: "java" },
        { text: "C", value: "c" },
        { text: "C++", value: "cpp" },
        { text: "C#", value: "csharp" },
        { text: "Ruby", value: "ruby" },
        { text: "Go", value: "go" },
        { text: "Rust", value: "rust" },
        { text: "SQL", value: "sql" },
        { text: "Bash/Shell", value: "bash" },
        { text: "JSON", value: "json" },
        { text: "Markdown", value: "markdown" },
        { text: "YAML", value: "yaml" },
        { text: "Plain Text", value: "plaintext" },
    ];

    // Register plugin
    tinymce.PluginManager.add(PLUGIN_NAME, function (editor, url) {
        // Get configuration
        const languages = editor.getParam(
            PLUGIN_NAME + "_languages",
            DEFAULT_LANGUAGES
        );
        const dialogWidth = editor.getParam(PLUGIN_NAME + "_dialog_width", 800);
        const dialogHeight = editor.getParam(
            PLUGIN_NAME + "_dialog_height",
            600
        );

        /**
         * Check if Highlight.js is loaded
         */
        function isHighlightJSLoaded() {
            return typeof hljs !== "undefined";
        }

        /**
         * Highlight all code blocks in the editor
         */
        function highlightCodeBlocks() {
            if (!isHighlightJSLoaded()) {
                return;
            }

            try {
                const iframe = editor.getDoc();
                if (!iframe) return;

                const codeBlocks = iframe.querySelectorAll(
                    "pre.hljs-code-block code"
                );

                codeBlocks.forEach(function (codeElement) {
                    // Skip if already highlighted
                    if (codeElement.hasAttribute("data-highlighted")) {
                        return;
                    }

                    try {
                        hljs.highlightElement(codeElement);
                    } catch (e) {
                        // Silent fail
                    }
                });
            } catch (error) {
                // Silent fail
            }
        }

        /**
         * Get current code block element under cursor
         * @returns {Element|null} The <pre.hljs-code-block> element or null
         */
        function getCurrentCodeBlock() {
            try {
                const node = editor.selection.getNode();
                const codeBlock = editor.dom.getParent(
                    node,
                    "pre.hljs-code-block"
                );
                return codeBlock;
            } catch (e) {
                return null;
            }
        }

        /**
         * Extract code and language from existing code block
         * @param {Element} preElement - The <pre.hljs-code-block> element
         * @returns {Object} { code: string, language: string }
         */
        function getCodeData(preElement) {
            if (!preElement) {
                return { code: "", language: "auto" };
            }

            try {
                const codeElement = preElement.querySelector("code");
                if (!codeElement) {
                    return { code: "", language: "auto" };
                }

                // Get plain text content (without HTML tags)
                const code =
                    codeElement.textContent || codeElement.innerText || "";

                // Extract language from class name
                let language = "auto";
                const classes = codeElement.className.split(" ");
                for (let i = 0; i < classes.length; i++) {
                    const cls = classes[i];
                    if (cls.startsWith("language-")) {
                        language = cls.replace("language-", "");
                        break;
                    }
                }

                return { code: code, language: language };
            } catch (e) {
                return { code: "", language: "auto" };
            }
        }

        /**
         * Insert or update code block
         * @param {string} code - The code content
         * @param {string} language - The language identifier
         */
        function insertCodeBlock(code, language) {
            if (!code || !code.trim()) {
                editor.windowManager.alert("Please enter some code.");
                return;
            }

            if (!isHighlightJSLoaded()) {
                editor.windowManager.alert(
                    "Highlight.js is not loaded. Please refresh the page."
                );
                return;
            }

            // Encode HTML entities in code
            const encodedCode = editor.dom.encode(code);

            // Determine final language
            let finalLanguage = language || "auto";
            if (finalLanguage === "auto") {
                try {
                    const result = hljs.highlightAuto(code);
                    finalLanguage = result.language || "plaintext";
                } catch (e) {
                    finalLanguage = "plaintext";
                }
            }

            // Build HTML structure
            const codeBlockHtml =
                '<pre class="hljs-code-block" contenteditable="false">' +
                '<code class="language-' +
                finalLanguage +
                '">' +
                encodedCode +
                "</code>" +
                "</pre>";

            // Check if editing existing block
            const existingBlock = getCurrentCodeBlock();

            if (existingBlock) {
                // Replace existing code block
                editor.undoManager.transact(function () {
                    editor.dom.setOuterHTML(existingBlock, codeBlockHtml);
                });
            } else {
                // Insert new code block
                editor.undoManager.transact(function () {
                    editor.insertContent(codeBlockHtml);
                });
            }

            // Apply highlighting after insertion
            setTimeout(function () {
                highlightCodeBlocks();
                applyNonEditableStyles();
            }, 50);
        }

        /**
         * Apply non-editable styles to code blocks
         */
        function applyNonEditableStyles() {
            try {
                const iframe = editor.getDoc();
                if (!iframe) return;

                const codeBlocks = iframe.querySelectorAll(
                    "pre.hljs-code-block"
                );

                codeBlocks.forEach(function (preElement) {
                    // Set contenteditable to false
                    preElement.setAttribute("contenteditable", "false");

                    // Prevent text selection
                    preElement.style.userSelect = "none";
                    preElement.style.webkitUserSelect = "none";
                    preElement.style.mozUserSelect = "none";
                    preElement.style.msUserSelect = "none";
                    preElement.style.cursor = "pointer";

                    // Also apply to code element
                    const codeElement = preElement.querySelector("code");
                    if (codeElement) {
                        codeElement.style.userSelect = "none";
                        codeElement.style.webkitUserSelect = "none";
                        codeElement.style.mozUserSelect = "none";
                        codeElement.style.msUserSelect = "none";
                        codeElement.style.cursor = "pointer";
                    }

                    // Prevent selection via events
                    const preventSelection = function (e) {
                        if (e.type === "selectstart") {
                            e.preventDefault();
                            return false;
                        }
                        if (e.type === "mousedown" && e.detail === 1) {
                            window.getSelection().removeAllRanges();
                        }
                    };

                    preElement.addEventListener(
                        "selectstart",
                        preventSelection,
                        true
                    );
                    preElement.addEventListener(
                        "mousedown",
                        preventSelection,
                        true
                    );

                    if (codeElement) {
                        codeElement.addEventListener(
                            "selectstart",
                            preventSelection,
                            true
                        );
                        codeElement.addEventListener(
                            "mousedown",
                            preventSelection,
                            true
                        );
                    }
                });
            } catch (error) {
                // Silent fail
            }
        }

        /**
         * Open dialog to insert/edit code block
         */
        function openCodeDialog() {
            const currentBlock = getCurrentCodeBlock();
            const isEditing = currentBlock !== null;
            const codeData = isEditing
                ? getCodeData(currentBlock)
                : { code: "", language: "auto" };

            editor.windowManager.open({
                title: isEditing ? "Edit Code Block" : "Insert Code Block",
                size: "large",
                body: {
                    type: "panel",
                    items: [
                        {
                            type: "listbox",
                            name: "language",
                            label: "Language",
                            items: languages,
                        },
                        {
                            type: "textarea",
                            name: "code",
                            label: "Code",
                            placeholder: "Paste your code here...",
                            flex: true,
                        },
                    ],
                },
                buttons: [
                    {
                        type: "cancel",
                        text: "Cancel",
                    },
                    {
                        type: "submit",
                        text: isEditing ? "Update" : "Insert",
                        primary: true,
                    },
                ],
                initialData: {
                    language: codeData.language,
                    code: codeData.code,
                },
                onSubmit: function (api) {
                    const data = api.getData();

                    // Validate code input
                    if (!data.code || !data.code.trim()) {
                        editor.windowManager.alert("Please enter some code.");
                        return;
                    }

                    // Insert or update code block
                    insertCodeBlock(data.code, data.language);
                    api.close();
                },
            });
        }

        // Register command
        editor.addCommand("mceHljsCodeBlock", openCodeDialog);

        // Register toolbar button
        editor.ui.registry.addButton(PLUGIN_NAME, {
            icon: "code-sample",
            tooltip: "Insert/Edit code block",
            onAction: openCodeDialog,
        });

        // Register menu item
        editor.ui.registry.addMenuItem(PLUGIN_NAME, {
            icon: "code-sample",
            text: "Code block",
            onAction: openCodeDialog,
        });

        // Register context menu
        editor.ui.registry.addContextMenu(PLUGIN_NAME, {
            update: function (element) {
                const codeBlock = editor.dom.getParent(
                    element,
                    "pre.hljs-code-block"
                );
                return codeBlock ? [PLUGIN_NAME] : [];
            },
        });

        // Event handlers
        editor.on("init", function () {
            setTimeout(function () {
                highlightCodeBlocks();
                applyNonEditableStyles();
            }, 200);
        });

        editor.on("SetContent", function () {
            setTimeout(function () {
                highlightCodeBlocks();
                applyNonEditableStyles();
            }, 50);
        });

        editor.on("NodeChange", function () {
            setTimeout(function () {
                highlightCodeBlocks();
                applyNonEditableStyles();
            }, 50);
        });

        // Handle double-click to edit
        editor.on("dblclick", function (e) {
            const node = e.target;
            const codeBlock = editor.dom.getParent(node, "pre.hljs-code-block");

            if (codeBlock) {
                openCodeDialog();
                return false;
            }
        });

        // Return plugin metadata
        return {
            getMetadata: function () {
                return {
                    name: "Highlight.js Code Block Plugin",
                    version: "1.0.0",
                    author: "Dashboard Team",
                    url: "",
                };
            },
        };
    });
})();


