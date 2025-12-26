(function () {
    const multiLevelDropdown = document.querySelector(".multi-level-dropdown");
    if (!multiLevelDropdown) return;

    const menuStackContainer = multiLevelDropdown.querySelector(
        ".menu-stack-container"
    );
    if (!menuStackContainer) return;

    function isMobile() {
        return window.innerWidth < 992;
    }

    // Menu stack navigation manager
    class MenuStackNavigation {
        constructor(container) {
            this.container = container;
            this.panels = Array.from(
                container.querySelectorAll(".menu-stack-panel")
            );
            this.activePanel = container.querySelector(
                ".menu-stack-panel.active"
            );
            this.history = [0]; // Track navigation history with panel indices
            this.init();
        }

        init() {
            // Set initial active panel
            if (this.activePanel) {
                const panelId = this.activePanel.dataset.panelId || "0";
                this.history = [parseInt(panelId)];
            }

            // Handle menu item clicks
            this.container.addEventListener("click", (e) => {
                // Check if clicked on a link (not a menu item with children)
                const link = e.target.closest(".menu-stack-item > a");
                if (link) {
                    // Allow normal link navigation, don't prevent default
                    return;
                }

                // Handle menu items with children (submenu)
                const menuItem = e.target.closest(
                    ".menu-stack-item.has-children"
                );
                if (menuItem) {
                    e.preventDefault();
                    e.stopPropagation();
                    const targetId = menuItem.dataset.target;
                    if (targetId) {
                        this.navigateTo(parseInt(targetId));
                    }
                    return;
                }

                // Handle back/close button
                const backButton = e.target.closest(".menu-stack-back");
                if (backButton) {
                    e.preventDefault();
                    e.stopPropagation();

                    // If it's close button on root menu, close dropdown
                    if (backButton.classList.contains("menu-stack-close")) {
                        this.closeDropdown();
                        return;
                    }

                    // Otherwise, go back in menu stack
                    this.goBack();
                }
            });
        }

        closeDropdown() {
            const dropdownToggle = document.querySelector(
                "#multiLevelDropdown"
            );
            if (dropdownToggle) {
                // Try Bootstrap Dropdown API first
                if (typeof bootstrap !== "undefined" && bootstrap.Dropdown) {
                    const bsDropdown =
                        bootstrap.Dropdown.getInstance(dropdownToggle);
                    if (bsDropdown) {
                        bsDropdown.hide();
                        return;
                    }
                }

                // Fallback: remove show class and aria-expanded
                dropdownToggle.setAttribute("aria-expanded", "false");
                const dropdownMenu = dropdownToggle.nextElementSibling;
                if (dropdownMenu) {
                    dropdownMenu.classList.remove("show");
                }

                // Trigger click to close (Bootstrap will handle it)
                dropdownToggle.click();
            }
        }

        navigateTo(targetId) {
            if (!isMobile()) return;

            const targetPanel = this.panels.find(
                (p) => p.dataset.panelId === String(targetId)
            );
            if (!targetPanel) return;

            const currentId = this.history[this.history.length - 1];
            const currentPanel = this.panels.find(
                (p) => p.dataset.panelId === String(currentId)
            );

            // Update classes
            if (currentPanel) {
                currentPanel.classList.remove("active");
                currentPanel.classList.add("prev");
            }

            targetPanel.classList.remove("prev");
            targetPanel.classList.add("active");

            // Update history
            this.history.push(targetId);

            // Remove prev class after animation
            setTimeout(() => {
                if (currentPanel) {
                    currentPanel.classList.remove("prev");
                }
            }, 300);
        }

        goBack() {
            if (!isMobile()) return;
            if (this.history.length <= 1) return;

            // Remove current from history
            const currentId = this.history.pop();
            const previousId = this.history[this.history.length - 1];

            const currentPanel = this.panels.find(
                (p) => p.dataset.panelId === String(currentId)
            );
            const previousPanel = this.panels.find(
                (p) => p.dataset.panelId === String(previousId)
            );

            if (currentPanel && previousPanel) {
                // Slide out current
                currentPanel.classList.remove("active");
                currentPanel.style.transform = "translateX(100%)";

                // Slide in previous
                previousPanel.classList.remove("prev");
                previousPanel.classList.add("active");

                // Reset transform after animation
                setTimeout(() => {
                    currentPanel.style.transform = "";
                }, 300);
            }
        }

        reset() {
            // Reset to root panel
            this.panels.forEach((panel) => {
                panel.classList.remove("active", "prev");
                panel.style.transform = "";
            });

            const rootPanel = this.panels.find(
                (p) => p.dataset.panelId === "0"
            );
            if (rootPanel) {
                rootPanel.classList.add("active");
                this.history = [0];
            }
        }
    }

    // Initialize menu stack navigation
    let menuStackNav = null;

    function initMenuStack() {
        if (isMobile() && menuStackContainer) {
            if (!menuStackNav) {
                menuStackNav = new MenuStackNavigation(menuStackContainer);
            }
        } else if (menuStackNav) {
            menuStackNav.reset();
        }
    }

    // Initialize on load
    initMenuStack();

    // Handle resize
    let resizeTimer;
    window.addEventListener("resize", function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            initMenuStack();
        }, 150);
    });

    // Handle dropdown show/hide events
    const dropdownToggle = multiLevelDropdown.querySelector(
        '[data-bs-toggle="dropdown"]'
    );
    const menuStackOverlay = multiLevelDropdown.querySelector(
        ".menu-stack-overlay"
    );
    const dropdownWrapper = multiLevelDropdown.querySelector(
        ".dropdown-menu-wrapper"
    );

    if (dropdownToggle) {
        // Show overlay when dropdown opens
        dropdownToggle.addEventListener("shown.bs.dropdown", function () {
            if (isMobile() && menuStackOverlay) {
                menuStackOverlay.style.display = "block";
            }
            if (menuStackNav && isMobile()) {
                menuStackNav.reset();
            }
        });

        // Hide overlay and reset menu stack when dropdown closes
        dropdownToggle.addEventListener("hidden.bs.dropdown", function () {
            if (menuStackOverlay) {
                menuStackOverlay.style.display = "none";
            }
            if (menuStackNav) {
                menuStackNav.reset();
            }
        });
    }

    // Also handle click on overlay to close dropdown
    if (menuStackOverlay) {
        menuStackOverlay.addEventListener("click", function () {
            if (dropdownToggle) {
                if (typeof bootstrap !== "undefined" && bootstrap.Dropdown) {
                    const bsDropdown =
                        bootstrap.Dropdown.getInstance(dropdownToggle);
                    if (bsDropdown) {
                        bsDropdown.hide();
                    }
                } else {
                    dropdownToggle.click();
                }
            }
        });
    }
})();
