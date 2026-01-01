// Initialize Highlight.js
document.addEventListener("DOMContentLoaded", function () {
    if (typeof hljs !== "undefined") {
        hljs.highlightAll();
    }
    
    // Initialize Fancybox for images in article content
    if (typeof Fancybox !== "undefined") {
        const articleContent = document.querySelector(".article-content");
        if (articleContent) {
            // Find all images in article content
            const images = articleContent.querySelectorAll("img");
            
            images.forEach((img) => {
                // Get image source
                const imgSrc = img.src || img.getAttribute("data-src");
                if (!imgSrc) {
                    return;
                }
                
                // Check if image is already wrapped in a link
                let link = img.parentElement;
                
                if (link && link.tagName === "A") {
                    // Image already has a link, just add Fancybox attributes
                    link.setAttribute("data-fancybox", "gallery");
                    if (!link.getAttribute("data-caption") && img.alt) {
                        link.setAttribute("data-caption", img.alt);
                    }
                } else {
                    // Create a new link wrapper for Fancybox
                    link = document.createElement("a");
                    link.href = imgSrc;
                    link.setAttribute("data-fancybox", "gallery");
                    link.setAttribute("data-caption", img.alt || "");
                    
                    // Wrap image with link
                    img.parentNode.insertBefore(link, img);
                    link.appendChild(img);
                }
            });
            
            // Initialize Fancybox
            Fancybox.bind("[data-fancybox]", {
                Toolbar: {
                    display: {
                        left: ["infobar"],
                        middle: [],
                        right: ["slideshow", "download", "thumbs", "close"],
                    },
                },
                Thumbs: {
                    autoStart: false,
                },
                Image: {
                    zoom: true,
                    wheel: "slide",
                },
            });
        }
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
    
    // Tự động mở TOC khi vào trang chi tiết
    const tocContent = document.getElementById("toc-content");
    const toggleBtn = document.querySelector(".sidebar-widget-toggle");
    
    if (tocContent) {
        tocContent.classList.remove("collapsed");
    }
    
    if (toggleBtn) {
        toggleBtn.classList.remove("collapsed");
    }
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
                
                // Show success notification using toastr
                const message = data.message || (data.saved ? 'Đã lưu bài viết thành công.' : 'Đã bỏ lưu bài viết.');
                showSuccess(message);
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
     * Show success message
     */
    function showSuccess(message) {
        if (typeof toastr !== 'undefined') {
            toastr.success(message, 'Thành công', {
                timeOut: 5000,
                positionClass: 'toast-top-right'
            });
        } else {
            console.log(message);
            alert(message);
        }
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

// Comment Form functionality
document.addEventListener("DOMContentLoaded", function() {
    const commentForm = document.getElementById('comment-form');
    
    if (!commentForm) {
        return; // Form không tồn tại (có thể chưa đăng nhập hoặc comments bị tắt)
    }
    
    const commentContent = document.getElementById('comment-content');
    const submitBtn = commentForm.querySelector('button[type="submit"]');
    const commentsList = document.getElementById('comments-list');
    const commentsCountBadge = document.querySelector('.badge.bg-label-primary');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    if (!csrfToken) {
        console.error('CSRF token not found');
        return;
    }
    
    commentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const content = commentContent.value.trim();
        
        if (!content || content.length < 3) {
            showError('Nội dung bình luận phải có ít nhất 3 ký tự.');
            return;
        }
        
        if (content.length > 1000) {
            showError('Nội dung bình luận không được vượt quá 1000 ký tự.');
            return;
        }
        
        // Disable form during submission
        submitBtn.disabled = true;
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-2"></i>Đang gửi...';
        
        const formData = new FormData(commentForm);
        
        fetch('/comments', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => Promise.reject({ status: response.status, data: err }));
            }
            return response.json();
        })
        .then(data => {
            if (data.status && data.data) {
                // Clear form
                commentContent.value = '';
                
                // Add new comment to the top of the list
                const commentHtml = createCommentHtml(data.data);
                
                // Remove empty state if exists
                const emptyState = commentsList.querySelector('.text-center');
                if (emptyState) {
                    emptyState.remove();
                }
                
                // Insert new comment at the top
                const firstComment = commentsList.querySelector('.comment-item');
                if (firstComment) {
                    commentsList.insertBefore(commentHtml, firstComment);
                } else {
                    commentsList.appendChild(commentHtml);
                }
                
                // Update comments count
                if (commentsCountBadge) {
                    const currentCount = parseInt(commentsCountBadge.textContent) || 0;
                    commentsCountBadge.textContent = (currentCount + 1) + ' bình luận';
                }
                
                // Show success message
                showSuccess(data.message || 'Bình luận đã được gửi thành công.');
                
                // Scroll to new comment
                commentHtml.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                showError(data.message || 'Có lỗi xảy ra khi gửi bình luận.');
            }
        })
        .catch(error => {
            console.error('Comment submission error:', error);
            
            let message = 'Có lỗi xảy ra khi gửi bình luận. Vui lòng thử lại.';
            
            if (error.status === 401) {
                message = 'Vui lòng đăng nhập để bình luận.';
                if (confirm(message + '\n\nBạn có muốn đăng nhập không?')) {
                    const currentUrl = window.location.href;
                    window.location.href = `/auth/dang-nhap?redirect=${encodeURIComponent(currentUrl)}`;
                }
            } else if (error.status === 403) {
                message = error.data?.message || 'Bình luận đã được tắt cho bài viết này.';
            } else if (error.status === 422) {
                const errors = error.data?.errors || {};
                const firstError = Object.values(errors)[0];
                message = Array.isArray(firstError) ? firstError[0] : (firstError || 'Dữ liệu không hợp lệ.');
            } else if (error.data && error.data.message) {
                message = error.data.message;
            }
            
            showError(message);
        })
        .finally(() => {
            // Re-enable form
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });
    });
    
    /**
     * Create HTML for a new comment
     */
    function createCommentHtml(commentData) {
        const commentDiv = document.createElement('div');
        commentDiv.className = 'comment-item card mb-3';
        
        const avatarHtml = commentData.user.avatar 
            ? `<img src="${commentData.user.avatar}" alt="${commentData.user.full_name}" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">`
            : `<div class="rounded-circle bg-label-secondary d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;"><i class="bx bx-user"></i></div>`;
        
        commentDiv.innerHTML = `
            <div class="card-body">
                <div class="d-flex align-items-start">
                    ${avatarHtml}
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-2">
                            <strong class="me-2">${escapeHtml(commentData.user.full_name)}</strong>
                            <span class="text-muted small">
                                <i class="bx bx-time me-1"></i>${commentData.created_at}
                            </span>
                        </div>
                        <div class="comment-content">
                            ${escapeHtml(commentData.content)}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        return commentDiv;
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Show success message
     */
    function showSuccess(message) {
        if (typeof toastr !== 'undefined') {
            toastr.success(message, 'Thành công', {
                timeOut: 3000,
                positionClass: 'toast-top-right'
            });
        } else {
            console.log(message);
            alert(message);
        }
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

// Reply functionality
document.addEventListener("DOMContentLoaded", function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    if (!csrfToken) {
        return;
    }
    
    // Handle Reply button clicks
    document.addEventListener('click', function(e) {
        if (e.target.closest('.reply-btn')) {
            const replyBtn = e.target.closest('.reply-btn');
            const commentId = replyBtn.getAttribute('data-comment-id');
            const commentAuthor = replyBtn.getAttribute('data-comment-author');
            const replyFormContainer = document.getElementById(`reply-form-${commentId}`);
            
            if (replyFormContainer) {
                // Hide all other reply forms
                document.querySelectorAll('.reply-form-container').forEach(form => {
                    if (form.id !== `reply-form-${commentId}`) {
                        form.style.display = 'none';
                    }
                });
                
                // Toggle current reply form
                if (replyFormContainer.style.display === 'none') {
                    replyFormContainer.style.display = 'block';
                    const replyToAuthor = replyFormContainer.querySelector('.reply-to-author');
                    if (replyToAuthor) {
                        replyToAuthor.textContent = commentAuthor;
                    }
                    const textarea = replyFormContainer.querySelector('textarea');
                    if (textarea) {
                        textarea.focus();
                    }
                } else {
                    replyFormContainer.style.display = 'none';
                }
            }
        }
        
        // Handle Cancel reply button
        if (e.target.closest('.cancel-reply-btn')) {
            const cancelBtn = e.target.closest('.cancel-reply-btn');
            const replyForm = cancelBtn.closest('.reply-form-container');
            if (replyForm) {
                const textarea = replyForm.querySelector('textarea');
                if (textarea) {
                    textarea.value = '';
                }
                replyForm.style.display = 'none';
            }
        }
    });
    
    // Handle Reply form submissions
    document.addEventListener('submit', function(e) {
        if (e.target.closest('.reply-form')) {
            e.preventDefault();
            
            const replyForm = e.target.closest('.reply-form');
            const parentId = replyForm.getAttribute('data-parent-id');
            const textarea = replyForm.querySelector('textarea[name="content"]');
            const submitBtn = replyForm.querySelector('button[type="submit"]');
            const content = textarea.value.trim();
            
            if (!content || content.length < 3) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Nội dung phản hồi phải có ít nhất 3 ký tự.', 'Lỗi');
                } else {
                    alert('Nội dung phản hồi phải có ít nhất 3 ký tự.');
                }
                return;
            }
            
            if (content.length > 1000) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Nội dung phản hồi không được vượt quá 1000 ký tự.', 'Lỗi');
                } else {
                    alert('Nội dung phản hồi không được vượt quá 1000 ký tự.');
                }
                return;
            }
            
            // Disable form during submission
            submitBtn.disabled = true;
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Đang gửi...';
            
            const formData = new FormData(replyForm);
            
            fetch('/comments', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => Promise.reject({ status: response.status, data: err }));
                }
                return response.json();
            })
            .then(data => {
                if (data.status && data.data) {
                    // Clear form
                    textarea.value = '';
                    replyForm.closest('.reply-form-container').style.display = 'none';
                    
                    // Find parent comment item
                    const parentCommentItem = document.querySelector(`[data-comment-id="${parentId}"]`);
                    if (parentCommentItem) {
                        // Find or create replies list
                        let repliesList = parentCommentItem.querySelector('.replies-list');
                        if (!repliesList) {
                            repliesList = document.createElement('div');
                            repliesList.className = 'replies-list mt-3 ms-5';
                            const cardBody = parentCommentItem.querySelector('.card-body');
                            if (cardBody) {
                                cardBody.appendChild(repliesList);
                            }
                        }
                        
                        // Create reply HTML
                        const replyHtml = createReplyHtml(data.data);
                        repliesList.appendChild(replyHtml);
                        
                        // Update comments count
                        const commentsCountBadge = document.querySelector('.badge.bg-label-primary');
                        if (commentsCountBadge) {
                            const currentCount = parseInt(commentsCountBadge.textContent) || 0;
                            commentsCountBadge.textContent = (currentCount + 1) + ' bình luận';
                        }
                        
                        // Show success message
                        if (typeof toastr !== 'undefined') {
                            toastr.success(data.message || 'Phản hồi đã được gửi thành công.', 'Thành công');
                        } else {
                            alert(data.message || 'Phản hồi đã được gửi thành công.');
                        }
                        
                        // Scroll to new reply
                        replyHtml.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(data.message || 'Có lỗi xảy ra khi gửi phản hồi.', 'Lỗi');
                    } else {
                        alert(data.message || 'Có lỗi xảy ra khi gửi phản hồi.');
                    }
                }
            })
            .catch(error => {
                console.error('Reply submission error:', error);
                
                let message = 'Có lỗi xảy ra khi gửi phản hồi. Vui lòng thử lại.';
                
                if (error.status === 401) {
                    message = 'Vui lòng đăng nhập để phản hồi.';
                } else if (error.status === 403) {
                    message = error.data?.message || 'Bình luận đã được tắt cho bài viết này.';
                } else if (error.status === 422) {
                    const errors = error.data?.errors || {};
                    const firstError = Object.values(errors)[0];
                    message = Array.isArray(firstError) ? firstError[0] : (firstError || 'Dữ liệu không hợp lệ.');
                } else if (error.data && error.data.message) {
                    message = error.data.message;
                }
                
                if (typeof toastr !== 'undefined') {
                    toastr.error(message, 'Lỗi');
                } else {
                    alert(message);
                }
            })
            .finally(() => {
                // Re-enable form
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        }
    });
    
    /**
     * Create HTML for a new reply
     */
    function createReplyHtml(replyData) {
        const replyDiv = document.createElement('div');
        replyDiv.className = 'comment-item card mb-3';
        replyDiv.setAttribute('data-comment-id', replyData.id);
        
        const avatarHtml = replyData.user.avatar 
            ? `<img src="${replyData.user.avatar}" alt="${replyData.user.full_name}" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">`
            : `<div class="rounded-circle bg-label-secondary d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;"><i class="bx bx-user"></i></div>`;
        
        replyDiv.innerHTML = `
            <div class="card-body">
                <div class="d-flex align-items-start">
                    ${avatarHtml}
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-2">
                            <strong class="me-2">${escapeHtml(replyData.user.full_name)}</strong>
                            <span class="text-muted small">
                                <i class="bx bx-time me-1"></i>${replyData.created_at}
                            </span>
                        </div>
                        <div class="comment-content mb-2">
                            ${escapeHtml(replyData.content)}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        return replyDiv;
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
