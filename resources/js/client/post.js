// Initialize Highlight.js
document.addEventListener("DOMContentLoaded", function () {
    if (typeof hljs !== "undefined") {
        hljs.highlightAll();
    }
});

// Toggle TOC function - expose to global scope for onclick handlers
window.toggleTOC = function() {
    const tocContent = document.getElementById("toc-content");
    const toggleBtn = document.querySelector(".sidebar-widget-toggle");
    const tocWidget = document.querySelector(".sidebar-widget");

    if (!tocContent || !toggleBtn) return;

    tocContent.classList.toggle("collapsed");
    toggleBtn.classList.toggle("collapsed");
};

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
            const targetId = href.substring(1); // Remove the # character
            
            const target = document.getElementById(targetId);
            if (target) {
                // Calculate offset for navbar height
                const navbar = document.querySelector('.navbar');
                const navbarHeight = navbar ? navbar.offsetHeight : 0;
                
                // Get element's position using getBoundingClientRect (more reliable)
                const rect = target.getBoundingClientRect();
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                const targetPosition = rect.top + scrollTop - navbarHeight - 20; // 20px padding
                
                window.scrollTo({
                    top: Math.max(0, targetPosition),
                    behavior: "smooth"
                });
                
                // Update URL without jumping
                window.history.pushState(null, null, href);
            } else {
                console.warn('TOC target not found:', targetId);
            }
        });

        li.appendChild(a);
        tocList.appendChild(li);
    });
});

// Saved Post functionality - AJAX implementation (using vanilla JS)
document.addEventListener("DOMContentLoaded", function() {
    const saveBtn = document.getElementById('savePostBtn');
    
    if (!saveBtn) {
        console.log('Save button not found');
        return;
    }
    
    const postId = saveBtn.getAttribute('data-post-id');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    if (!postId) {
        console.error('Post ID not found');
        return;
    }
    
    if (!csrfToken) {
        console.error('CSRF token not found');
        return;
    }
    
    console.log('Save button initialized', { postId, hasToken: !!csrfToken });
    
    saveBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Save button clicked');
        
        const icon = saveBtn.querySelector('i');
        const isCurrentlySaved = saveBtn.classList.contains('saved');
        
        console.log('Current state:', { isCurrentlySaved, postId });
        
        // Disable button during request to prevent double clicks
        saveBtn.disabled = true;
        
        // Add loading state
        saveBtn.classList.add('loading');
        
        // Store original state for potential revert
        const originalState = {
            saved: isCurrentlySaved,
            title: saveBtn.getAttribute('title'),
            iconClass: icon ? icon.className : ''
        };
        
        // Optimistic UI update - update immediately for better UX
        if (isCurrentlySaved) {
            // Remove saved state immediately
            saveBtn.classList.remove('saved');
            saveBtn.setAttribute('title', 'Lưu bài viết');
            if (icon) {
                icon.classList.remove('bxs-bookmark');
                icon.classList.add('bx-bookmark');
            }
        } else {
            // Add saved state immediately
            saveBtn.classList.add('saved');
            saveBtn.setAttribute('title', 'Bỏ lưu bài viết');
            if (icon) {
                icon.classList.remove('bx-bookmark');
                icon.classList.add('bxs-bookmark');
            }
        }
        
        // Make AJAX request using Fetch API
        console.log('Making AJAX request to:', `/saved-posts/${postId}/toggle`);
        
        fetch(`/saved-posts/${postId}/toggle`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            console.log('Fetch response status:', response.status);
            if (!response.ok) {
                return response.json().then(err => Promise.reject({ status: response.status, data: err }));
            }
            return response.json();
        })
        .then(data => {
            console.log('AJAX success:', data);
            
            if (data && data.success !== undefined) {
                // Update button state based on server response
                if (data.saved) {
                    saveBtn.classList.add('saved');
                    saveBtn.setAttribute('title', 'Bỏ lưu bài viết');
                    if (icon) {
                        icon.classList.remove('bx-bookmark');
                        icon.classList.add('bxs-bookmark');
                    }
                } else {
                    saveBtn.classList.remove('saved');
                    saveBtn.setAttribute('title', 'Lưu bài viết');
                    if (icon) {
                        icon.classList.remove('bxs-bookmark');
                        icon.classList.add('bx-bookmark');
                    }
                }
                
                // Show success notification - reload page to show flash message
                const message = data.message || (data.saved ? 'Đã lưu bài viết thành công' : 'Đã bỏ lưu bài viết');
                // Store message in session storage to show after reload
                sessionStorage.setItem('success_message', message);
                // Reload page to show flash message
                window.location.reload();
            } else {
                // Invalid response, revert
                revertButtonState(saveBtn, icon, originalState);
                showError('Phản hồi không hợp lệ từ server');
            }
        })
        .catch(error => {
            console.error('AJAX error:', error);
            
            // Revert optimistic update on error
            revertButtonState(saveBtn, icon, originalState);
            
            let message = 'Có lỗi xảy ra. Vui lòng thử lại.';
            
            if (error.status === 401) {
                // Not logged in
                message = error.data?.message || 'Vui lòng đăng nhập để lưu bài viết.';
                if (confirm(message + '\n\nBạn có muốn đăng nhập không?')) {
                    const currentUrl = window.location.href;
                    window.location.href = `/client/auth/dang-nhap?redirect=${encodeURIComponent(currentUrl)}`;
                }
            } else if (error.status === 404) {
                message = 'Bài viết không tồn tại.';
                showError(message);
            } else if (error.status === 422) {
                message = error.data?.message || 'Dữ liệu không hợp lệ.';
                showError(message);
            } else if (error.status === 500) {
                message = 'Lỗi server. Vui lòng thử lại sau.';
                showError(message);
            } else if (error.data && error.data.message) {
                message = error.data.message;
                showError(message);
            } else {
                showError(message);
            }
        })
        .finally(() => {
            // Re-enable button and remove loading state
            saveBtn.disabled = false;
            saveBtn.classList.remove('loading');
        });
    });
    
    /**
     * Revert button to original state
     */
    function revertButtonState(btn, iconElement, originalState) {
        if (originalState.saved) {
            btn.classList.add('saved');
            if (iconElement) {
                iconElement.classList.remove('bx-bookmark');
                iconElement.classList.add('bxs-bookmark');
            }
        } else {
            btn.classList.remove('saved');
            if (iconElement) {
                iconElement.classList.remove('bxs-bookmark');
                iconElement.classList.add('bx-bookmark');
            }
        }
        btn.setAttribute('title', originalState.title);
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        if (typeof toastr !== 'undefined') {
            toastr.error(message, 'Lỗi', {
                timeOut: 5000,
                positionClass: 'toast-top-right'
            });
        } else {
            console.error(message);
            alert(message);
        }
    }
});
