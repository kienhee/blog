/**
 * Hàm gọi AJAX để cập nhật badge count
 * @param {string} badgeId - ID của badge element
 * @param {string} url - URL route để gọi API
 */
function updateBadgeCount(badgeId, url) {
    const badgeElement = document.getElementById(badgeId);
    if (!badgeElement) {
        console.warn(`Badge element with id "${badgeId}" not found`);
        return;
    }

    // Gọi AJAX để lấy count
    $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            if (response.success && typeof response.count !== 'undefined') {
                const count = parseInt(response.count);
                badgeElement.textContent = count;

                // Chỉ hiển thị badge nếu count > 1 (CSS sẽ xử lý hiển thị/ẩn)
                if (count > 1) {
                    badgeElement.classList.add('badge-heartbeat');
                } else {
                    badgeElement.classList.remove('badge-heartbeat');
                }
            } else {
                console.error('Invalid response format:', response);
                badgeElement.classList.remove('badge-heartbeat');
            }
        },
        error: function(xhr, status, error) {
            console.error(`Error updating badge count for ${badgeId}:`, error);
            badgeElement.classList.remove('badge-heartbeat');
        }
    });
}

/**
 * Khởi tạo tất cả badge counts từ config
 * @param {Object} badgeConfigs - Object chứa config { badgeId: url }
 */
function initBadgeCounts(badgeConfigs) {
    if (!badgeConfigs || typeof badgeConfigs !== 'object') {
        console.warn('Badge configs not provided or invalid');
        return;
    }

    // Cập nhật tất cả badge counts
    Object.keys(badgeConfigs).forEach(function(badgeId) {
        const url = badgeConfigs[badgeId];
        if (url) {
            updateBadgeCount(badgeId, url);
        }
    });
}

// Export cho sử dụng global
window.updateBadgeCount = updateBadgeCount;
window.initBadgeCounts = initBadgeCounts;


