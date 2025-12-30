/**
 * Contact page functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // 1. Character Counter (Giữ nguyên logic đếm ký tự)
    const subjectInput = document.getElementById('subject');
    const messageTextarea = document.getElementById('message');
    const subjectCounter = document.getElementById('subject-counter');
    const messageCounter = document.getElementById('message-counter');
    
    if (subjectInput && subjectCounter) {
        subjectInput.addEventListener('input', function() {
            const currentLength = this.value.length;
            const maxLength = this.getAttribute('maxlength');
            subjectCounter.textContent = currentLength + '/' + maxLength + ' ký tự';
            if (currentLength >= maxLength * 0.9) subjectCounter.classList.add('char-limit-warning');
            else subjectCounter.classList.remove('char-limit-warning');
        });
    }
    
    if (messageTextarea && messageCounter) {
        messageTextarea.addEventListener('input', function() {
            const currentLength = this.value.length;
            const maxLength = this.getAttribute('maxlength');
            messageCounter.textContent = currentLength + '/' + maxLength + ' ký tự';
            if (currentLength >= maxLength * 0.9) messageCounter.classList.add('char-limit-warning');
            else messageCounter.classList.remove('char-limit-warning');
        });
    }

    // 2. QR Code & Link Actions (UPDATED)
    const btnShare = document.getElementById('btn-share-zalo');
    const btnCopy = document.getElementById('btn-copy-link');
    const btnDownload = document.getElementById('btn-download-qr');
    // Lấy link từ thẻ input ẩn (Chính là link Zalo thật)
    const shareLinkVal = document.getElementById('share-link-val') ? document.getElementById('share-link-val').value : '';
    const qrImg = document.getElementById('zalo-qr-img');

    // Button: Share (Mobile Native Share)
    if (btnShare) {
        btnShare.addEventListener('click', async () => {
            if (navigator.share) {
                try {
                    await navigator.share({
                        title: 'Hỗ trợ Zalo',
                        text: 'Tham gia nhóm Zalo để được hỗ trợ nhanh nhất:',
                        url: shareLinkVal // Link Zalo thật
                    });
                } catch (err) {
                    console.log('User cancelled share or error:', err);
                }
            } else {
                // Fallback cho PC: Copy Link
                copyToClipboard(shareLinkVal);
                const originalHtml = btnShare.innerHTML;
                btnShare.innerHTML = '<i class="fas fa-check"></i> Đã Copy';
                setTimeout(() => { btnShare.innerHTML = originalHtml; }, 2000);
            }
        });
    }

    // Button: Copy Link
    if (btnCopy) {
        btnCopy.addEventListener('click', function() {
            copyToClipboard(shareLinkVal);
            const originalHtml = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check"></i> Đã Copy';
            // Thêm class đổi màu nếu muốn
            this.style.backgroundColor = '#d4edda';
            this.style.borderColor = '#c3e6cb';
            this.style.color = '#155724';
            
            setTimeout(() => {
                this.innerHTML = originalHtml;
                // Reset style
                this.style.backgroundColor = '';
                this.style.borderColor = '';
                this.style.color = '';
            }, 2000);
        });
    }

    // Helper Function: Copy Logic
    function copyToClipboard(text) {
        if (!navigator.clipboard) {
            // Fallback for older browsers
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try { document.execCommand('copy'); } catch (err) {}
            document.body.removeChild(textArea);
            return;
        }
        navigator.clipboard.writeText(text);
    }

    // Button: Download QR Image
    if (btnDownload && qrImg) {
        btnDownload.addEventListener('click', function() {
            const imgSrc = qrImg.src;
            const originalHtml = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

            fetch(imgSrc, {
                method: 'GET',
                mode: 'cors',
                cache: 'no-cache'
            })
            .then(response => {
                if (!response.ok) throw new Error('Network error');
                return response.blob();
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'zalo-group-qr.png';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
                this.innerHTML = originalHtml;
            })
            .catch(err => {
                console.error('Download failed:', err);
                // Fallback: Mở tab mới
                window.open(imgSrc, '_blank');
                this.innerHTML = originalHtml;
            });
        });
    }

    // 3. Modal Functionality (Giữ nguyên)
    const modal = document.getElementById('request-modal');
    if (modal) {
        const closeBtn = modal.querySelector('.modal-close-btn');
        const viewButtons = document.querySelectorAll('.btn-view-details');
        
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const requestData = JSON.parse(this.getAttribute('data-request'));
                const statusText = this.getAttribute('data-status-text');
                const categoryText = this.getAttribute('data-category-text');
                
                document.getElementById('modal-subject').textContent = requestData.subject;
                document.getElementById('modal-category').textContent = categoryText;
                document.getElementById('modal-status').textContent = statusText;
                document.getElementById('modal-message').textContent = requestData.message;
                
                const createdDate = new Date(requestData.created_at);
                const formattedDate = createdDate.toLocaleDateString('vi-VN') + ' ' + 
                                     createdDate.toLocaleTimeString('vi-VN', {hour: '2-digit', minute:'2-digit'});
                document.getElementById('modal-created').textContent = formattedDate;
                
                const responseContainer = document.getElementById('response-container');
                if (requestData.admin_response) {
                    document.getElementById('modal-response').textContent = requestData.admin_response;
                    responseContainer.style.display = 'flex';
                } else {
                    responseContainer.style.display = 'none';
                }
                modal.style.display = 'block';
            });
        });
        
        if (closeBtn) closeBtn.addEventListener('click', () => modal.style.display = 'none');
        window.addEventListener('click', (event) => {
            if (event.target === modal) modal.style.display = 'none';
        });
    }

    // 4. Auto Hide Messages
    const messages = document.querySelectorAll('.message');
    messages.forEach(msg => {
        setTimeout(() => {
            msg.style.opacity = '0';
            setTimeout(() => msg.style.display = 'none', 500);
        }, 5000);
    });
});

function switchTab(tabName) {
    // 1. Xóa active ở tất cả các nút
    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(btn => btn.classList.remove('active'));

    // 2. Ẩn tất cả nội dung
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(content => content.classList.remove('active'));

    // 3. Kích hoạt nút được bấm
    // Tìm nút vừa bấm dựa trên onclick (hoặc truyền this vào)
    // Ở đây ta dùng cách đơn giản: click cái nào active cái đó thông qua event
    const clickedBtn = event.currentTarget;
    clickedBtn.classList.add('active');

    // 4. Hiện nội dung tương ứng
    if (tabName === 'form') {
        document.getElementById('tab-form').classList.add('active');
    } else if (tabName === 'qr') {
        document.getElementById('tab-qr').classList.add('active');
    }
}