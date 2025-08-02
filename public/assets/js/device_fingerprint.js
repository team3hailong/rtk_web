/**
 * Device Fingerprint Collector
 * Script để thu thập vân tay thiết bị của người dùng
 */

(function() {
    /**
     * Thu thập các thông tin cấu hình và đặc điểm của trình duyệt để tạo vân tay thiết bị
     */
    function collectDeviceData() {
        const data = {
            // Thông tin cơ bản về trình duyệt
            userAgent: navigator.userAgent,
            language: navigator.language,
            platform: navigator.platform,
            doNotTrack: navigator.doNotTrack,
            cookieEnabled: navigator.cookieEnabled,
            
            // Thông tin màn hình
            screenWidth: window.screen.width,
            screenHeight: window.screen.height,
            screenDepth: window.screen.colorDepth,
            
            // Thông tin về múi giờ
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            timezoneOffset: new Date().getTimezoneOffset(),
            
            // Thông tin về plugins
            pluginsLength: navigator.plugins ? navigator.plugins.length : 0,
            
            // Thông tin về canvas (được sử dụng phổ biến để tạo fingerprint)
            canvasFingerprint: getCanvasFingerprint()
        };
        
        return data;
    }
    
    /**
     * Tạo vân tay dựa trên canvas
     */
    function getCanvasFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = 200;
            canvas.height = 200;
            
            // Vẽ văn bản và hình dạng
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.fillStyle = '#f60';
            ctx.fillRect(10, 10, 100, 50);
            ctx.fillStyle = '#069';
            ctx.fillText('Device Fingerprint', 10, 70);
            
            // Vẽ gradient
            const gradient = ctx.createLinearGradient(0, 0, 200, 0);
            gradient.addColorStop(0, 'red');
            gradient.addColorStop(1, 'blue');
            ctx.fillStyle = gradient;
            ctx.fillRect(50, 90, 100, 50);
            
            return canvas.toDataURL().slice(-10); // Chỉ lấy phần cuối để giảm kích thước
        } catch (e) {
            return 'canvas-unsupported';
        }
    }
    
    /**
     * Tạo hash từ dữ liệu thiết bị
     */
    function hashDeviceData(deviceData) {
        // Đơn giản hóa bằng cách kết hợp các thông tin quan trọng
        const dataString = [
            deviceData.userAgent,
            deviceData.platform,
            deviceData.screenWidth + 'x' + deviceData.screenHeight,
            deviceData.screenDepth,
            deviceData.timezone,
            deviceData.canvasFingerprint
        ].join('###');
        
        // Sử dụng hàm hash đơn giản
        let hash = 0;
        for (let i = 0; i < dataString.length; i++) {
            const char = dataString.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32bit integer
        }
        
        // Chuyển đổi thành chuỗi hex
        const hashHex = (hash >>> 0).toString(16);
        return hashHex;
    }
    
    /**
     * Thu thập và gửi vân tay thiết bị
     */
    function processDeviceFingerprint() {
        const deviceData = collectDeviceData();
        const fingerprint = hashDeviceData(deviceData);
        
        // Lưu vào localStorage để sử dụng khi cần
        localStorage.setItem('deviceFingerprint', fingerprint);
        
        // Thêm fingerprint vào form đăng nhập nếu có
        const loginForm = document.querySelector('form[action*="process_login"]');
        if (loginForm) {
            // Tạo input ẩn để lưu fingerprint
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'device_fingerprint';
            input.value = fingerprint;
            loginForm.appendChild(input);
        }
        
        // Thêm fingerprint vào form đăng ký nếu có
        const registerForm = document.querySelector('form[action*="process_register"]');
        if (registerForm) {
            // Tạo input ẩn để lưu fingerprint
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'device_fingerprint';
            input.value = fingerprint;
            registerForm.appendChild(input);
        }
        
        return fingerprint;
    }
    
    // Chờ DOM load xong và thực thi
    document.addEventListener('DOMContentLoaded', function() {
        const fingerprint = processDeviceFingerprint();
        console.log('Device fingerprint generated:', fingerprint);
    });
})();
