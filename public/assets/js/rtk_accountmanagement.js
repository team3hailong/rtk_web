document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.filter-button');
    const searchInput = document.getElementById('account-search');
    const accountCards = document.querySelectorAll('.account-card');
    const accountsListContainer = document.getElementById('accounts-list-container');
    const emptyStateHTML = `
        <div class="empty-state">
            <h3>Không tìm thấy tài khoản</h3>
            <p>Không có tài khoản nào khớp với tiêu chí lọc hoặc tìm kiếm của bạn.</p>
        </div>`;

    // Create modal HTML
    const modalHtml = `
        <div id="accountDetailsModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Chi tiết tài khoản</h2>
                <div class="account-details">
                    <p><strong>ID:</strong> <span id="modal-id"></span></p>
                    <p><strong>Tên đăng nhập:</strong> <span id="modal-username"></span></p>
                    <p><strong>Mật khẩu:</strong> <span id="modal-password"></span></p>
                    <p><strong>Trạng thái:</strong> <span id="modal-status"></span></p>
                    <p><strong>Thời gian bắt đầu:</strong> <span id="modal-start"></span></p>
                    <p><strong>Thời gian kết thúc:</strong> <span id="modal-end"></span></p>
                    <p><strong>Tỉnh/TP:</strong> <span id="modal-province"></span></p>
                    <p><strong>IP:</strong> <span id="modal-ip"></span></p>
                    <p><strong>Port:</strong> <span id="modal-port"></span></p>
                    <p><strong>Mount points:</strong> <span id="modal-mountpoints"></span></p>
                </div>
            </div>
        </div>
    `;

    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Add modal styles
    const modalStyles = `
        <style>
            .modal {
                position: fixed;
                z-index: 1000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
                display: none;
                align-items: center;
                justify-content: center;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            .modal.show {
                opacity: 1;
                display: flex;
            }
            .modal-content {
                background-color: #fefefe;
                padding: 20px;
                border: 1px solid #888;
                width: 90%;
                max-width: 500px;
                border-radius: 8px;
                position: relative;
                margin: 0 auto;
                transform: translateY(-20px);
                transition: transform 0.3s ease;
            }
            .modal.show .modal-content {
                transform: translateY(0);
            }
            .close {
                color: #aaa;
                position: absolute;
                right: 15px;
                top: 10px;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
            }
            .close:hover {
                color: black;
            }
            .account-details {
                margin-top: 20px;
            }
            .account-details p {
                margin: 10px 0;
                padding: 5px 0;
                border-bottom: 1px solid #eee;
            }
            .account-details strong {
                display: inline-block;
                width: 140px;
            }
            .mountpoints-section {
                margin-top: 15px;
            }
            .mountpoints-list {
                margin-left: 140px;
                padding: 5px 0;
            }
            .mountpoint-item {
                margin: 8px 0;
                padding: 8px;
                background: #f5f5f5;
                border-radius: 4px;
            }
            .mountpoint-item p {
                margin: 4px 0;
                padding: 0;
                border: none;
            }
        </style>
    `;
    document.head.insertAdjacentHTML('beforeend', modalStyles);

    // Modal functionality
    const modal = document.getElementById('accountDetailsModal');
    const closeBtn = modal.querySelector('.close');

    // Close modal when clicking X
    closeBtn.onclick = function() {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = "none";
        }, 300);
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = "none";
            }, 300);
        }
    }

    // --- Hàm Lọc và Tìm kiếm ---
    function filterAndSearchAccounts() {
        // Ensure elements exist before proceeding
        if (!accountsListContainer || !searchInput) return;

        const activeFilterButton = document.querySelector('.filter-button.active');
        const activeFilter = activeFilterButton ? activeFilterButton.getAttribute('data-filter') : 'all';
        const searchTerm = searchInput.value.toLowerCase().trim();
        let matchFound = false;

        accountCards.forEach(card => {
            const status = card.getAttribute('data-status');
            const searchTerms = card.getAttribute('data-search-terms'); // Lấy dữ liệu search đã chuẩn bị sẵn

            const statusMatch = (activeFilter === 'all' || status === activeFilter);
            // Ensure searchTerms is not null before calling includes
            const searchMatch = (searchTerm === '' || (searchTerms && searchTerms.includes(searchTerm)));

            if (statusMatch && searchMatch) {
                card.style.display = ''; // Hiện card (use default grid display)
                matchFound = true;
            } else {
                card.style.display = 'none'; // Ẩn card
            }
        });

        // Hiển thị trạng thái trống nếu không tìm thấy kết quả
        const currentEmptyState = accountsListContainer.querySelector('.empty-state');
        if (!matchFound && !currentEmptyState) {
            accountsListContainer.insertAdjacentHTML('beforeend', emptyStateHTML);
        } else if (matchFound && currentEmptyState) {
            currentEmptyState.remove();
        } else if (!matchFound && currentEmptyState) {
            // Already showing empty state, do nothing
        }
    }

    // --- Event Listener cho Nút Lọc ---
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            filterAndSearchAccounts();
        });
    });

    // --- Event Listener cho Ô Tìm kiếm ---
    if (searchInput) {
        searchInput.addEventListener('input', filterAndSearchAccounts);
    }

    // --- Event Listener cho Hiện/Ẩn Mật khẩu ---
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const passwordSpan = this.previousElementSibling; // Lấy thẻ span chứa mật khẩu
            if (!passwordSpan) return;
            const actualPassword = passwordSpan.getAttribute('data-password');
            if (passwordSpan.textContent === '**********') {
                passwordSpan.textContent = actualPassword;
                this.textContent = 'Ẩn';
            } else {
                passwordSpan.textContent = '**********';
                this.textContent = 'Hiện';
            }
        });
    });

    // --- Event Listener cho Hiện/Ẩn Danh sách Trạm ---
    document.querySelectorAll('.toggle-stations').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const targetSelector = this.getAttribute('data-target');
            if (!targetSelector) return;
            const stationList = document.querySelector(targetSelector);
            if (stationList) {
                 // Find list items that were initially hidden (more robust than checking style directly)
                 const allItems = Array.from(stationList.querySelectorAll('li'));
                 const initiallyVisibleCount = parseInt(stationList.dataset.initiallyVisible || '3'); // Assuming 3 initially visible, store this if dynamic

                if (stationList.classList.contains('expanded')) {
                    // Thu gọn
                    stationList.classList.remove('expanded');
                    allItems.forEach((item, index) => {
                        if (index >= initiallyVisibleCount) {
                            item.style.display = 'none'; // Hide items beyond the initial count
                        }
                    });
                    this.textContent = 'Hiện thêm';
                } else {
                    // Mở rộng
                    stationList.classList.add('expanded');
                     allItems.forEach(item => {
                        item.style.display = 'inline-block'; // Show all items
                    });
                    this.textContent = 'Ẩn bớt';
                }
            }
        });
        // Store initially visible count if needed for robustness
        const targetSelector = toggle.getAttribute('data-target');
        if(targetSelector){
            const stationList = document.querySelector(targetSelector);
            if(stationList){
                const visibleItems = stationList.querySelectorAll('li:not([style*="display: none"])');
                stationList.dataset.initiallyVisible = visibleItems.length;
            }
        }
    });

    // --- Event Listener cho Nút Xem Chi Tiết ---
    document.querySelectorAll('.btn-view').forEach(button => {
        button.addEventListener('click', function() {
            const data = this.dataset;
            document.getElementById('modal-id').textContent = data.accountId;
            document.getElementById('modal-username').textContent = data.username;
            document.getElementById('modal-password').textContent = data.password;
            document.getElementById('modal-status').textContent = data.status;
            document.getElementById('modal-start').textContent = data.start;
            document.getElementById('modal-end').textContent = data.end;
            document.getElementById('modal-province').textContent = data.province;
            
            try {
                const mountpoints = JSON.parse(data.mountpoints);
                if (mountpoints && mountpoints.length > 0) {
                    // Lấy IP và Port từ mount point đầu tiên
                    document.getElementById('modal-ip').textContent = mountpoints[0].ip || 'N/A';
                    document.getElementById('modal-port').textContent = mountpoints[0].port || 'N/A';
                    
                    // Format mount points as comma-separated list
                    const mountpointsList = mountpoints
                        .map(mp => mp.mountpoint)
                        .join(', ');
                    document.getElementById('modal-mountpoints').textContent = mountpointsList;
                } else {
                    document.getElementById('modal-ip').textContent = 'N/A';
                    document.getElementById('modal-port').textContent = 'N/A';
                    document.getElementById('modal-mountpoints').textContent = 'Không có mountpoint';
                }
            } catch (e) {
                document.getElementById('modal-ip').textContent = 'N/A';
                document.getElementById('modal-port').textContent = 'N/A';
                document.getElementById('modal-mountpoints').textContent = 'Lỗi hiển thị mountpoint';
            }

            modal.style.display = "flex";
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
        });
    });

     // --- Event Listener cho Nút Gia Hạn (Chuyển trang) ---
     // Đã xử lý bằng thẻ <a> với href đúng trong PHP

     // Initial filter on page load if needed
     // filterAndSearchAccounts();
});
