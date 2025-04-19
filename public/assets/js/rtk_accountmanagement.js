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


    // --- Event Listener cho Nút Xem Chi Tiết (Placeholder) ---
    document.querySelectorAll('.btn-view').forEach(button => {
        button.addEventListener('click', function() {
            const accountId = this.getAttribute('data-account-id');
            // Thay thế bằng logic thực tế (ví dụ: mở modal, chuyển trang)
            alert('Xem chi tiết tài khoản #' + accountId);
            // Check if baseUrl is defined before using it
            if (typeof baseUrl !== 'undefined') {
                 // window.location.href = `${baseUrl}/pages/account_details.php?id=${accountId}`;
            } else {
                console.error("Base URL is not defined for redirection.");
                // Fallback or error handling
                // window.location.href = `/pages/account_details.php?id=${accountId}`; // Example fallback
            }
        });
    });

     // --- Event Listener cho Nút Gia Hạn (Chuyển trang) ---
     // Đã xử lý bằng thẻ <a> với href đúng trong PHP

     // Initial filter on page load if needed
     // filterAndSearchAccounts();
});
