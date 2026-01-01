/**
 * Client Auth Pages JavaScript
 * Handles password toggle functionality for login, register, and reset password pages
 */

(function() {
    function initPasswordToggle() {
        // Try to use Helpers if available
        if (typeof window.Helpers !== 'undefined' && typeof window.Helpers.initPasswordToggle === 'function') {
            window.Helpers.initPasswordToggle();
            return;
        }
        
        // Fallback: Manual implementation
        const passwordToggleContainers = document.querySelectorAll('.form-password-toggle');
        
        passwordToggleContainers.forEach(function(container) {
            const toggleIcon = container.querySelector('.input-group-text i');
            const passwordInput = container.querySelector('input[type="password"], input[type="text"]');
            
            if (!toggleIcon || !passwordInput) return;
            
            // Add click event to the span container (more reliable than just icon)
            const toggleSpan = container.querySelector('.input-group-text');
            if (toggleSpan) {
                toggleSpan.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Toggle input type
                    if (passwordInput.getAttribute('type') === 'password') {
                        passwordInput.setAttribute('type', 'text');
                        toggleIcon.classList.remove('bx-hide');
                        toggleIcon.classList.add('bx-show');
                    } else {
                        passwordInput.setAttribute('type', 'password');
                        toggleIcon.classList.remove('bx-show');
                        toggleIcon.classList.add('bx-hide');
                    }
                });
            }
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPasswordToggle);
    } else {
        // DOM is already ready
        initPasswordToggle();
    }
    
    // Also try after a short delay in case Helpers loads later
    setTimeout(function() {
        if (typeof window.Helpers !== 'undefined' && typeof window.Helpers.initPasswordToggle === 'function') {
            window.Helpers.initPasswordToggle();
        }
    }, 500);
})();

