/**
 * JavaScript for RTK Account Management Page
 */

document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const modalOverlay = document.getElementById('account-details-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalUsername = document.getElementById('modal-username');
    const modalPassword = document.getElementById('modal-password');
    const modalStartTime = document.getElementById('modal-start-time');
    const modalEndTime = document.getElementById('modal-end-time');
    const modalMountpointsList = document.getElementById('modal-mountpoints-list');
    const perPageSelect = document.getElementById('per-page');
    
    // Export elements
    const exportButton = document.getElementById('export-excel');
    const selectAllButton = document.getElementById('select-all-accounts');
    const selectedCountElement = document.getElementById('selected-count');
    const accountCheckboxes = document.querySelectorAll('.account-checkbox');
    const exportForm = document.getElementById('export-form');

    // Renewal elements
    const renewalBtn = document.getElementById('renewal-btn');
    const renewalForm = document.getElementById('renewal-form');
    
    // Update Survey Account elements
    const updateSurveyAccountsBtn = document.getElementById('update-survey-accounts');
    const updateSurveyAccountModal = document.getElementById('update-survey-account-modal');
    const addAccountRowBtn = document.getElementById('add-account-row');
    const confirmUpdateAccountsBtn = document.getElementById('confirm-update-accounts');
    
    // Change Password elements
    const changePasswordModal = document.getElementById('change-password-modal');
    const confirmChangePasswordBtn = document.getElementById('confirm-change-password');
      // Theo dõi trạng thái lọc và tìm kiếm hiện tại
    let currentFilter = paginationConfig.currentFilter || 'all';
    let currentSearchTerm = '';
    let currentRemainingTimeFilter = 'all';
    // Xử lý chọn tài khoản và cập nhật trạng thái nút xuất Excel
    function updateExportButtonState() {
        const checkedBoxes = document.querySelectorAll('.account-checkbox:checked');
        const count = checkedBoxes.length;
        
        // Cập nhật số lượng tài khoản đã chọn
        if (selectedCountElement) {
            selectedCountElement.textContent = count;
        }
        
        // Bật/tắt nút xuất Excel
        if (exportButton) {
            exportButton.disabled = count === 0;
        }
    }

    // Function to update renewal button state
    function updateRenewalButtonState() {
        const checkedBoxes = document.querySelectorAll('.account-checkbox:checked');
        if (renewalBtn) {
            renewalBtn.disabled = checkedBoxes.length === 0;
        }
    }
      // Thêm sự kiện cho từng checkbox
    accountCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            // Check if this is a package_id = 7 account and show warning if it's selected
            if (checkbox.checked && checkbox.dataset.packageId === "7") {
                alert('Tài khoản này sử dụng gói dùng thử và không thể gia hạn.');
            }
            
            updateExportButtonState();
            updateRenewalButtonState();
        });
    });
    
    // Xử lý nút chọn tất cả
    if (selectAllButton) {
        selectAllButton.addEventListener('click', function() {
            // Only select checkboxes in visible rows
            const checkboxes = Array.from(document.querySelectorAll('.account-checkbox')).filter(cb => {
                const row = cb.closest('tr');
                return row && row.style.display !== 'none';
            });
            const allChecked = checkboxes.length > 0 && checkboxes.every(checkbox => checkbox.checked);
            checkboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });
            updateExportButtonState();
            updateRenewalButtonState();
            // Cập nhật text của nút
            this.innerHTML = !allChecked ? 
                '<i class="fas fa-times-square"></i> Bỏ chọn tất cả' : 
                '<i class="fas fa-check-square"></i> Chọn tất cả';
        });
    }
    
    // Xử lý nút xuất Excel
    if (exportButton) {
        exportButton.addEventListener('click', function() {
            if (document.querySelectorAll('.account-checkbox:checked').length > 0) {
                exportForm.submit();
            }
        });
    }    // Handle renewal form submission
    if (renewalForm) {
        renewalForm.addEventListener('submit', function(e) {
            // Clear previous hidden inputs for selected accounts to avoid duplicates
            const existingInputs = renewalForm.querySelectorAll('input[type="hidden"][name="selected_accounts[]"]');
            existingInputs.forEach(input => input.remove());

            const checkedBoxes = document.querySelectorAll('.account-checkbox:checked');
            
            if (checkedBoxes.length === 0) {
                // If no accounts are selected, prevent form submission.
                // alert('Vui lòng chọn ít nhất một tài khoản để gia hạn.'); // Optional: display a message
                e.preventDefault(); 
                return; 
            }
            
            // Check if any selected account has package_id = 7
            let hasPackage7 = false;
            checkedBoxes.forEach(cb => {
                if (cb.dataset.packageId === "7") {
                    hasPackage7 = true;
                }
            });
            
            if (hasPackage7) {
                alert('Một hoặc nhiều tài khoản được chọn không thể gia hạn vì đang sử dụng gói dùng thử.');
                e.preventDefault();
                return;
            }

            // Add hidden input for each selected account
            checkedBoxes.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_accounts[]';
                input.value = cb.value;
                renewalForm.appendChild(input);
            });
        });
    }
    
    // Filter buttons functionality
    const filterButtons = document.querySelectorAll('.filter-button');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Lấy giá trị filter mới
            const filterValue = this.dataset.filter;
            
            // Cập nhật lớp active cho button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Thiết lập filter hiện tại và áp dụng
            currentFilter = filterValue;
            applyFilters();
            
            // Nếu người dùng không chỉ muốn lọc tạm thời, có thể chuyển hướng URL
            if (button.hasAttribute('data-permanent')) {
                // Chuyển hướng đến URL với filter mới
                window.location.href = buildPaginationUrl({
                    filter: filterValue,
                    page: 1 // Luôn reset về trang đầu tiên khi filter thay đổi
                });
            }
        });
    });

    // Xử lý thay đổi số mục trên mỗi trang
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            // Lấy số mục trên mỗi trang từ giá trị đã chọn
            const perPage = this.value;
            
            // Chuyển hướng đến URL với per_page mới
            window.location.href = buildPaginationUrl({
                perPage: perPage,
                page: 1 // Luôn reset về trang đầu tiên khi số lượng mục thay đổi
            });
        });
    }

    // Xây dựng URL phân trang với các tham số được cung cấp
    function buildPaginationUrl(params = {}) {
        // Lấy các tham số hiện tại từ URL
        const urlParams = new URLSearchParams(window.location.search);
        
        // Lấy tham số hiện tại
        let page = params.page || urlParams.get('page') || paginationConfig.currentPage;
        let perPage = params.perPage || urlParams.get('per_page') || paginationConfig.perPage;
        let filter = params.filter || urlParams.get('filter') || paginationConfig.currentFilter;
        
        // Loại bỏ giá trị mặc định nếu không cần thiết
        if (filter === 'all') filter = null;
        
        // Tạo đối tượng URL params mới
        const newParams = new URLSearchParams();
        
        // Thêm các tham số vào URL
        if (page && page !== '1') newParams.append('page', page);
        if (perPage && perPage !== '10') newParams.append('per_page', perPage);
        if (filter) newParams.append('filter', filter);
        
        // Trả về URL với các tham số mới
        const queryString = newParams.toString();
        return queryString ? `?${queryString}` : window.location.pathname;
    }    // Search functionality
    const searchBox = document.querySelector('.search-box');
    const searchButton = document.getElementById('search-button');
    const resetButton = document.getElementById('reset-button');
    
    // Search button click handler
    if (searchButton) {
        searchButton.addEventListener('click', function() {
            if (searchBox) {
                currentSearchTerm = searchBox.value.toLowerCase().trim();
                applyFilters();
            }
        });
    }
    
    // Reset button click handler
    if (resetButton) {
        resetButton.addEventListener('click', function() {
            if (searchBox) {
                searchBox.value = '';
                currentSearchTerm = '';
            }
            
            if (remainingTimeFilter) {
                remainingTimeFilter.value = 'all';
                currentRemainingTimeFilter = 'all';
            }
            
            // Reset status buttons if not server-side filters
            const filterButtons = document.querySelectorAll('.filter-button');
            filterButtons.forEach(button => {
                if (button.dataset.filter === 'all') {
                    button.classList.add('active');
                } else {
                    button.classList.remove('active');
                }
            });
            currentFilter = 'all';
            
            // Apply the filters after reset
            applyFilters();
        });
    }
    
    // Also keep input event for real-time filtering if preferred
    if (searchBox) {
        searchBox.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                currentSearchTerm = this.value.toLowerCase().trim();
                applyFilters();
                e.preventDefault();
            }
        });
    }

    // Remaining time filter functionality
    const remainingTimeFilter = document.getElementById('remaining-time-filter');
    if (remainingTimeFilter) {
        remainingTimeFilter.addEventListener('change', function() {
            // Lấy giá trị filter thời hạn còn lại
            currentRemainingTimeFilter = this.value;
            
            // Áp dụng lọc và tìm kiếm
            applyFilters();
        });
    }// Tập hợp tất cả các bộ lọc và áp dụng vào danh sách tài khoản
    function applyFilters() {
        const accounts = document.querySelectorAll('.accounts-table tbody tr:not(.empty-state-row)');
        let visibleCount = 0;
        // Split search terms for multi-keyword search
        const searchTerms = currentSearchTerm.split(/\s+/).filter(Boolean);
        accounts.forEach(account => {
            if (!account.dataset.searchTerms) return; // Bỏ qua hàng không phải dữ liệu
            // Multi-keyword, case-insensitive search
            const searchData = account.dataset.searchTerms.toLowerCase();
            const matchesSearch = !searchTerms.length || searchTerms.every(term => searchData.includes(term));
            
            // Kiểm tra điều kiện lọc theo trạng thái
            let matchesFilter = true;
            if (currentFilter !== 'all') {
                const accountStatus = account.dataset.status;
                // Đảm bảo filter status khớp với data-status và hiển thị đúng trạng thái
                matchesFilter = (currentFilter === accountStatus);
            }
            
            // Kiểm tra điều kiện lọc theo thời hạn còn lại
            let matchesRemainingTime = true;
            if (currentRemainingTimeFilter !== 'all') {
                const remainingDays = parseInt(account.dataset.remainingDays, 10);
                
                switch(currentRemainingTimeFilter) {
                    case 'less-than-7':
                        matchesRemainingTime = (remainingDays >= 0 && remainingDays < 7);
                        break;
                    case '7-to-30':
                        matchesRemainingTime = (remainingDays >= 7 && remainingDays <= 30);
                        break;
                    case '30-to-90':
                        matchesRemainingTime = (remainingDays > 30 && remainingDays <= 90);
                        break;
                    case 'more-than-90':
                        matchesRemainingTime = (remainingDays > 90);
                        break;
                }
            }
            
            // Hiển thị/ẩn dựa trên kết quả lọc
            const shouldDisplay = matchesSearch && matchesFilter && matchesRemainingTime;
            account.style.display = shouldDisplay ? '' : 'none';
            if (shouldDisplay) visibleCount++;
        });
        // Hiển thị thông báo "Không có dữ liệu" nếu không có tài khoản nào phù hợp
        handleEmptyState(visibleCount === 0);
        // Cập nhật thông tin phân trang
        updatePaginationInfo(visibleCount);
        // Cập nhật nút chọn tất cả
        if (selectAllButton) {
            selectAllButton.innerHTML = '<i class="fas fa-check-square"></i> Chọn tất cả';
        }
    }
    
    // Xử lý trạng thái khi không có dữ liệu
    function handleEmptyState(isEmpty) {
        // Kiểm tra xem đã có dòng thông báo chưa
        let emptyRow = document.querySelector('.accounts-table tbody tr.empty-state-row');
        
        if (isEmpty) {
            if (!emptyRow) {
                const tableBody = document.querySelector('.accounts-table tbody');
                emptyRow = document.createElement('tr');
                emptyRow.classList.add('empty-state-row');
                
                const emptyCell = document.createElement('td');
                emptyCell.setAttribute('colspan', '8');
                emptyCell.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <p>Không tìm thấy tài khoản nào phù hợp</p>
                    </div>
                `;
                
                emptyRow.appendChild(emptyCell);
                tableBody.appendChild(emptyRow);
            }
            emptyRow.style.display = '';
        } else if (emptyRow) {
            emptyRow.style.display = 'none';
        }
    }
    
    // Cập nhật thông tin phân trang dựa trên số lượng hàng hiện đang hiển thị
    function updatePaginationInfo(visibleCount) {
        const paginationInfo = document.querySelector('.pagination-info');
        if (!paginationInfo) return;
        
        paginationInfo.textContent = `Hiển thị ${visibleCount} trên tổng số ${paginationConfig.totalRecords} tài khoản`;
    }

    

    // Close Modal
    window.closeModal = function() {
        if (modalOverlay) {
            modalOverlay.classList.remove('active');
        }
    };

    // Show Account Details Modal
    window.showAccountDetails = function(account) {
        if (!modalOverlay || !account) return;
        
        modalTitle.textContent = `Chi Tiết Tài Khoản`;
        modalUsername.textContent = account.username;
        modalPassword.textContent = account.password;
        modalStartTime.textContent = account.start_time;
        modalEndTime.textContent = account.end_time;
        
        // Populate mountpoints as a table
        modalMountpointsList.innerHTML = '';
        if (account.mountpoints && account.mountpoints.length > 0) {
            account.mountpoints.forEach(mp => {
                const row = document.createElement('tr');
                
                // Đổi thứ tự thành "IP, Port, Trạm" thay vì "Trạm, IP, Port"
                const ipCell = document.createElement('td');
                ipCell.textContent = mp.ip || 'N/A';
                
                const portCell = document.createElement('td');
                portCell.textContent = mp.port || 'N/A';
                
                const mpCell = document.createElement('td');
                mpCell.textContent = mp.mountpoint || 'N/A';
                
                // Thêm các ô vào hàng theo thứ tự mới
                row.appendChild(ipCell);
                row.appendChild(portCell);
                row.appendChild(mpCell);
                
                modalMountpointsList.appendChild(row);
            });
            document.getElementById('mountpoints-section').style.display = 'block';
        } else {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.setAttribute('colspan', '3');
            cell.textContent = 'Không có dữ liệu trạm';
            cell.style.textAlign = 'center';
            row.appendChild(cell);
            modalMountpointsList.appendChild(row);
        }
        
        // Show the modal
        modalOverlay.classList.add('active');
    };
    
    // Xử lý nút copy trong modal chi tiết
    const copyButtons = document.querySelectorAll('.copy-btn');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Lấy target để copy
            const targetId = this.getAttribute('data-copy-target');
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                // Tạo một textarea element để copy text
                const textarea = document.createElement('textarea');
                textarea.value = targetElement.textContent;
                document.body.appendChild(textarea);
                textarea.select();
                
                try {
                    // Sao chép vào clipboard
                    document.execCommand('copy');
                    
                    // Thay đổi icon và thêm class để chỉ ra đã copy thành công
                    const icon = this.querySelector('i');
                    if (icon) {
                        const originalClass = icon.className;
                        icon.className = 'fas fa-check';
                        this.classList.add('copied');
                        
                        // Sau 2 giây, đổi lại icon và class ban đầu
                        setTimeout(() => {
                            icon.className = originalClass;
                            this.classList.remove('copied');
                        }, 2000);
                    }
                } catch (err) {
                    console.error('Không thể sao chép: ', err);
                }
                
                // Dọn dẹp
                document.body.removeChild(textarea);
            }
        });
    });
    
    // Close modal when clicking outside content
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(event) {
            if (event.target === modalOverlay) {
                closeModal();
            }
        });
    }
      // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            if (modalOverlay && modalOverlay.classList.contains('active')) {
                closeModal();
            }
            if (updateSurveyAccountModal && updateSurveyAccountModal.classList.contains('active')) {
                closeUpdateAccountModal();
            }
            if (changePasswordModal && changePasswordModal.classList.contains('active')) {
                closeChangePasswordModal();
            }
        }
    });
    
    // Update Survey Account button click handler
    if (updateSurveyAccountsBtn) {
        updateSurveyAccountsBtn.addEventListener('click', function() {
            showUpdateAccountModal();
        });
    }
    
    // Add row to update accounts table
    if (addAccountRowBtn) {
        addAccountRowBtn.addEventListener('click', function() {
            addAccountRow();
        });
    }
    
    // Confirm update accounts button handler
    if (confirmUpdateAccountsBtn) {
        confirmUpdateAccountsBtn.addEventListener('click', function() {
            validateAndUpdateAccounts();
        });
    }
    
    // Change password confirmation button handler
    if (confirmChangePasswordBtn) {
        confirmChangePasswordBtn.addEventListener('click', function() {
            changePassword();
        });
    }
    
    // Nếu đã chọn filter, tự động kích hoạt nút filter tương ứng
    if (currentFilter && currentFilter !== 'all') {
        const activeFilterButton = document.querySelector(`.filter-button[data-filter="${currentFilter}"]`);
        if (activeFilterButton) {
            activeFilterButton.classList.add('active');
        }
    }
    
    // Khởi tạo trạng thái nút xuất khi tải trang
    updateExportButtonState();
    // Initialize renewal button state on page load
    updateRenewalButtonState();

    // OTP modal controls
    const otpBtn = document.getElementById('confirm-otp-btn');
    if (otpBtn) {
        otpBtn.addEventListener('click', function() {
            const regId = document.getElementById('otp-registration-id').value;
            const otp = document.getElementById('otp-input').value.trim();
            if (!otp) {
                alert('Vui lòng nhập mã OTP');
                return;
            }
            fetch('../handlers/confirm_transfer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `registration_id=${regId}&otp=${encodeURIComponent(otp)}`
            })
            .then(response => response.json())
            .then(resp => {
                alert(resp.message);
                if (resp.success) {
                    closeOtpModal();
                    window.location.reload();
                }
            })
            .catch(() => alert('Lỗi khi xác thực OTP'));
        });
    }
});

// Show update account modal
function showUpdateAccountModal() {
    if (!document.getElementById('update-survey-account-modal')) return;
    
    // Clear any existing values
    const inputs = document.querySelectorAll('#update-accounts-tbody input');
    inputs.forEach(input => {
        input.value = '';
    });
    
    document.getElementById('update-survey-account-modal').classList.add('active');
}

// Close update account modal
function closeUpdateAccountModal() {
    if (!document.getElementById('update-survey-account-modal')) return;
    document.getElementById('update-survey-account-modal').classList.remove('active');
}

// Add a new row to the update accounts table
function addAccountRow() {
    const tbody = document.getElementById('update-accounts-tbody');
    if (!tbody) return;
    
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td><input type="text" class="form-input username-input" placeholder="Tên đăng nhập"></td>
        <td><input type="text" class="form-input password-input" placeholder="Mật khẩu"></td>
    `;
    
    tbody.appendChild(newRow);
}

// Validate and update accounts
function validateAndUpdateAccounts() {
    // Collect all account credentials
    const tbody = document.getElementById('update-accounts-tbody');
    if (!tbody) return;
    
    const rows = tbody.querySelectorAll('tr');
    const accounts = [];
    
    rows.forEach(row => {
        const usernameInput = row.querySelector('.username-input');
        const passwordInput = row.querySelector('.password-input');
        
        if (usernameInput && passwordInput && usernameInput.value.trim() && passwordInput.value.trim()) {
            accounts.push({
                username: usernameInput.value.trim(),
                password: passwordInput.value.trim()
            });
        }
    });
    
    if (accounts.length === 0) {
        alert('Vui lòng nhập ít nhất một tài khoản để cập nhật quyền sở hữu.');
        return;
    }
    
    // Send to server for validation
    const formData = new FormData();
    formData.append('action', 'validate_accounts');
    formData.append('accounts', JSON.stringify(accounts));
    
    // Disable the confirm button and show loading state
    const confirmBtn = document.getElementById('confirm-update-accounts');
    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
    }
    
    fetch(baseUrl + '/public/handlers/rtk_account_handlers.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Re-enable the confirm button
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = 'Xác nhận';
        }

        if (data.success) {
            // If some transfers require confirmation, show OTP modal
            const toConfirm = data.results.find(r => r.requires_confirmation);
            if (toConfirm) {
                closeUpdateAccountModal();
                showOtpModal(toConfirm.registration_id);
                return;
            }
            // Show appropriate message based on validation results
            if (data.all_valid) {
                alert(`Cập nhật quyền sở hữu thành công cho ${data.updated_count} tài khoản!`);
                closeUpdateAccountModal();

                // If accounts were updated, reload the page to show updated ownership
                if (data.updated_count > 0) {
                    window.location.reload();
                }
            } else {
                let invalidAccounts = data.results.filter(result => !result.valid).map(result => result.username).join(', ');
                alert(`Sai tên đăng nhập hoặc mật khẩu cho tài khoản: ${invalidAccounts}`);
            }
        } else {
            alert(data.message || 'Có lỗi xảy ra khi xử lý yêu cầu.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi kết nối đến máy chủ.');
        
        // Re-enable the confirm button on error
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = 'Xác nhận';
        }
    });
}

// OTP modal controls
function showOtpModal(registrationId) {
    const modal = document.getElementById('otp-confirm-modal');
    document.getElementById('otp-registration-id').value = registrationId;
    document.getElementById('otp-input').value = '';
    modal.classList.add('active');
}

function closeOtpModal() {
    document.getElementById('otp-confirm-modal').classList.remove('active');
}

document.getElementById('confirm-otp-btn').addEventListener('click', function() {
    const regId = document.getElementById('otp-registration-id').value;
    const otp = document.getElementById('otp-input').value.trim();
    if (!otp) {
        alert('Vui lòng nhập mã OTP');
        return;
    }
    fetch(`${baseUrl}/public/handlers/confirm_transfer.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `registration_id=${regId}&otp=${encodeURIComponent(otp)}`
    })
    .then(response => response.json())
    .then(resp => {
        alert(resp.message);
        if (resp.success) {
            closeOtpModal();
            window.location.reload();
        }
    })
    .catch(() => alert('Lỗi khi xác thực OTP'));
});

// Show change password modal
window.showChangePasswordModal = function(account) {
    if (!document.getElementById('change-password-modal') || !account) return;
    
    document.getElementById('cp-username').value = account.username;
    document.getElementById('cp-current-password').value = account.password;
    document.getElementById('cp-new-password').value = '';
    document.getElementById('cp-confirm-password').value = '';
    document.getElementById('cp-account-id').value = account.id;
    
    document.getElementById('change-password-modal').classList.add('active');
};

// Close change password modal
function closeChangePasswordModal() {
    if (!document.getElementById('change-password-modal')) return;
    document.getElementById('change-password-modal').classList.remove('active');
}

// Process password change
function changePassword() {
    const accountId = document.getElementById('cp-account-id').value;
    const newPassword = document.getElementById('cp-new-password').value;
    const confirmPassword = document.getElementById('cp-confirm-password').value;
    
    if (!newPassword) {
        alert('Vui lòng nhập mật khẩu mới.');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        alert('Mật khẩu mới và xác nhận mật khẩu không khớp.');
        return;
    }
    
    // Send to server for processing
    const formData = new FormData();
    formData.append('action', 'change_password');
    formData.append('account_id', accountId);
    formData.append('new_password', newPassword);
    
    // Disable the confirm button and show loading state
    const confirmBtn = document.getElementById('confirm-change-password');
    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
    }
    
    fetch(baseUrl + '/public/handlers/rtk_account_handlers.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Re-enable the confirm button
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = 'Xác nhận';
        }
        
        if (data.success) {
            alert('Đổi mật khẩu thành công!');
            closeChangePasswordModal();
            
            // Update the password in the table
            const accountRows = document.querySelectorAll('.accounts-table tbody tr');
            accountRows.forEach(row => {
                const checkbox = row.querySelector('.account-checkbox');
                if (checkbox && checkbox.value === accountId) {
                    const passwordCell = row.querySelector('td:nth-child(3)');
                    if (passwordCell) {
                        passwordCell.textContent = newPassword;
                    }
                }
            });
        } else {
            alert(data.message || 'Có lỗi xảy ra khi xử lý yêu cầu.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi kết nối đến máy chủ.');
        
        // Re-enable the confirm button on error
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = 'Xác nhận';
        }
    });
}

