/**
 * Session Activity Tracker
 * JavaScript helper để theo dõi hoạt động người dùng và làm mới session
 */

// Thời gian giữa các lần ping server để làm mới session (15 phút = 900000ms)
const SESSION_REFRESH_INTERVAL = 900000;

// URL endpoint để ping giữ session hoạt động
const SESSION_PING_URL = '/public/handlers/session_ping.php';

// Biến lưu trữ ID của timer
let sessionRefreshTimer;

/**
 * Thiết lập theo dõi hoạt động và làm mới session
 */
function initSessionTracker() {
    // Làm mới session khi người dùng tương tác với trang
    document.addEventListener('click', refreshUserSession);
    document.addEventListener('keypress', refreshUserSession);
    document.addEventListener('scroll', debounce(refreshUserSession, 1000));
    
    // Đặt timer để tự động làm mới session theo khoảng thời gian đã định
    resetSessionTimer();
}

/**
 * Làm mới session người dùng bằng cách gửi yêu cầu AJAX đến server
 */
function refreshUserSession() {
    // Gửi request AJAX để cập nhật thời gian hoạt động trong session
    fetch(SESSION_PING_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ action: 'refresh_session' })
    })
    .catch(error => {
        console.error('Session refresh failed:', error);
    });
    
    // Reset timer sau mỗi lần làm mới
    resetSessionTimer();
}

/**
 * Đặt lại timer làm mới session
 */
function resetSessionTimer() {
    // Xóa timer cũ nếu có
    if (sessionRefreshTimer) {
        clearTimeout(sessionRefreshTimer);
    }
    
    // Đặt timer mới
    sessionRefreshTimer = setTimeout(refreshUserSession, SESSION_REFRESH_INTERVAL);
}

/**
 * Debounce function để hạn chế số lần gọi hàm
 * @param {Function} func - Hàm cần debounce
 * @param {number} wait - Thời gian chờ (ms)
 * @returns {Function} - Hàm đã được debounce
 */
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            func.apply(context, args);
        }, wait);
    };
}

// Khởi tạo session tracker khi trang đã tải xong
document.addEventListener('DOMContentLoaded', initSessionTracker);
