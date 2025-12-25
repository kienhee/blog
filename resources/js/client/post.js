// Initialize Highlight.js
document.addEventListener("DOMContentLoaded", function () {
    if (typeof hljs !== "undefined") {
        hljs.highlightAll();
    }
});

// Toggle TOC function
function toggleTOC() {
    const tocContent = document.getElementById("toc-content");
    const toggleBtn = document.querySelector(".sidebar-widget-toggle");
    const tocWidget = document.querySelector(".sidebar-widget");

    if (!tocContent || !toggleBtn) return;

    tocContent.classList.toggle("collapsed");
    toggleBtn.classList.toggle("collapsed");
}

// Function to create valid CSS selector ID from text
function createValidId(text) {
    if (!text) return "heading-" + Math.random().toString(36).substr(2, 9);

    // Convert to lowercase, replace spaces and special chars with hyphens
    let id = text
        .trim()
        .toLowerCase()
        .replace(/[^\w\s-]/g, "") // Remove special characters except word chars, spaces, hyphens
        .replace(/\s+/g, "-") // Replace spaces with hyphens
        .replace(/-+/g, "-") // Replace multiple hyphens with single hyphen
        .replace(/^-+|-+$/g, ""); // Remove leading/trailing hyphens

    // If starts with number, add prefix
    if (/^\d/.test(id)) {
        id = "heading-" + id;
    }

    // If empty after sanitization, use random
    if (!id) {
        id = "heading-" + Math.random().toString(36).substr(2, 9);
    }

    return id;
}

// Generate Table of Contents with hierarchical numbering
document.addEventListener("DOMContentLoaded", function () {
    const content = document.querySelector(".article-content");
    const tocList = document.getElementById("toc-list");
    const tocWidget = document.querySelector(".sidebar-widget");

    if (!content || !tocList) return;

    const headings = content.querySelectorAll("h2, h3, h4");
    if (headings.length === 0) {
        if (tocWidget) {
            tocWidget.style.display = "none";
        }
        return;
    }

    // Track used IDs to avoid duplicates
    const usedIds = new Set();

    // Numbering counters for each level
    let counterH2 = 0; // Level 2 (h2)
    let counterH3 = 0; // Level 3 (h3)
    let counterH4 = 0; // Level 4 (h4)

    // Track current parent numbers
    let currentH2 = 0;
    let currentH3 = 0;

    headings.forEach((heading, index) => {
        // Create ID for heading if not exists
        if (!heading.id) {
            let baseId = createValidId(heading.textContent);
            let finalId = baseId;
            let counter = 1;

            // Ensure unique ID
            while (usedIds.has(finalId)) {
                finalId = baseId + "-" + counter;
                counter++;
            }

            heading.id = finalId;
            usedIds.add(finalId);
        }

        // Determine level
        const level = parseInt(heading.tagName.charAt(1));
        const levelClass = "level-" + level;

        // Update counters based on level
        let numberPrefix = "";

        if (level === 2) {
            // H2: Reset H3 and H4 counters, increment H2
            counterH2++;
            counterH3 = 0;
            counterH4 = 0;
            currentH2 = counterH2;
            currentH3 = 0;
            numberPrefix = counterH2 + ". ";
        } else if (level === 3) {
            // H3: Reset H4 counter, increment H3
            counterH3++;
            counterH4 = 0;
            currentH3 = counterH3;
            numberPrefix = currentH2 + "." + counterH3 + " ";
        } else if (level === 4) {
            // H4: Increment H4
            counterH4++;
            numberPrefix = currentH2 + "." + currentH3 + "." + counterH4 + " ";
        }

        // Create TOC item
        const li = document.createElement("li");
        li.className = "toc-item " + levelClass;

        const a = document.createElement("a");
        a.href = "#" + heading.id;
        a.className = "toc-link";

        // Add number prefix to text
        const numberSpan = document.createElement("span");
        numberSpan.className = "toc-number";
        numberSpan.textContent = numberPrefix;

        const textSpan = document.createElement("span");
        textSpan.className = "toc-text";
        textSpan.textContent = heading.textContent;

        a.appendChild(numberSpan);
        a.appendChild(textSpan);

        // Smooth scroll
        a.addEventListener("click", function (e) {
            e.preventDefault();
            const href = this.getAttribute("href");
            // Escape special characters in selector
            const escapedHref = href.replace(
                /([!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~])/g,
                "\\$1"
            );
            const target = document.querySelector(escapedHref);
            if (target) {
                target.scrollIntoView({
                    behavior: "smooth",
                    block: "start",
                });
                // Update URL without jumping
                window.history.pushState(null, null, href);
            }
        });

        li.appendChild(a);
        tocList.appendChild(li);
    });
});
