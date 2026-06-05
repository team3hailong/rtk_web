/**
 * Kinh Tuyến Trục Popup Controller
 */
class KinhTuyenTrucPopup {
    constructor() {
        this.popupOverlay = null;
        this.popupContainer = null;
        this.dontShowAgainCheckbox = null;
        this.localStorageKey = 'kinhTuyenTrucPopupShown';
        this.targetUrl = BASE_URL + '/public/pages/support/guide_detail.php?slug=thay-doi-kinh-tuyen-truc-theo-tinh-thanh-moi-sau-khi-sap-nhap';
        
        this.init();
    }
    
    init() {
        // Check if popup has already been shown and user chose not to see it again
        if (localStorage.getItem(this.localStorageKey) === 'true') {
            return;
        }
        
        // Only show popup once per session
        if (sessionStorage.getItem(this.localStorageKey) === 'true') {
            return;
        }
        
        // Mark as shown in this session
        sessionStorage.setItem(this.localStorageKey, 'true');
        
        this.createPopupElement();
        this.attachEventListeners();
    }
    
    createPopupElement() {
        // Create popup overlay
        this.popupOverlay = document.createElement('div');
        this.popupOverlay.className = 'popup-overlay';
        
        // Create popup container
        this.popupContainer = document.createElement('div');
        this.popupContainer.className = 'popup-container';
        
        // Check if we're on mobile and adjust class if needed
        if (window.innerWidth <= 768) {
            this.popupContainer.classList.add('mobile-view');
        }
        
        // Popup HTML structure
        this.popupContainer.innerHTML = `
            <div class="popup-close">&times;</div>
            <div class="popup-header">
                <h3>Thông báo quan trọng</h3>
            </div>
            <div class="popup-content">
                <img src="${BASE_URL}/public/assets/img/popup/thay-doi-kinh-tuyen-truc.jpg" alt="Thay đổi kinh tuyến trục" class="popup-image">
                <p><strong>Thay đổi kinh tuyến trục sau khi sáp nhập tỉnh, các anh em đo đạc lưu ý nhé</strong></p>
                <p class="click-to-read">(Nhấn vào để xem chi tiết)</p>
            </div>
            <div class="popup-footer">
                <label class="dont-show-again">
                    <input type="checkbox" id="dont-show-again">
                    <span>Không hiển thị lại</span>
                </label>
            </div>
        `;
        
        // Add popup to DOM
        this.popupOverlay.appendChild(this.popupContainer);
        document.body.appendChild(this.popupOverlay);
        
        // Get checkbox reference
        this.dontShowAgainCheckbox = document.getElementById('dont-show-again');
        
        // Show popup with animation
        setTimeout(() => {
            this.popupOverlay.classList.add('active');
        }, 300);
    }
    
    attachEventListeners() {
        // Close button click
        const closeButton = this.popupContainer.querySelector('.popup-close');
        closeButton.addEventListener('click', () => this.closePopup());
        
        // Image click (redirect to specific guide page)
        const popupImage = this.popupContainer.querySelector('.popup-image');
        popupImage.addEventListener('click', () => {
            window.location.href = this.targetUrl;
        });
        
        // Content area click (also redirect to guide page)
        const popupContent = this.popupContainer.querySelector('.popup-content');
        if (popupContent) {
            popupContent.addEventListener('click', () => {
                window.location.href = this.targetUrl;
            });
        }
        
        // Click outside popup to close
        this.popupOverlay.addEventListener('click', (e) => {
            if (e.target === this.popupOverlay) {
                this.closePopup();
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', () => {
            this.handleResize();
        });
        
        // Handle device orientation change for mobile devices
        window.addEventListener('orientationchange', () => {
            this.handleResize();
        });
    }
    
    // Handle window resize or orientation change
    handleResize() {
        if (!this.popupContainer) return;
        
        if (window.innerWidth <= 768) {
            this.popupContainer.classList.add('mobile-view');
        } else {
            this.popupContainer.classList.remove('mobile-view');
        }
    }
    
    closePopup() {
        // Save preference to localStorage if checkbox is checked
        if (this.dontShowAgainCheckbox && this.dontShowAgainCheckbox.checked) {
            localStorage.setItem(this.localStorageKey, 'true');
        }
        
        // Hide popup with animation
        this.popupOverlay.classList.remove('active');
        
        // Remove popup from DOM after animation
        setTimeout(() => {
            document.body.removeChild(this.popupOverlay);
        }, 300);
    }
}

// Initialize popup when DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    new KinhTuyenTrucPopup();
});
