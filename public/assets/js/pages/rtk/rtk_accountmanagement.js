/**
 * Consolidated & Optimized JavaScript for RTK Account Management Page
 */
document.addEventListener('DOMContentLoaded', function() {
    // --- State Variables ---
    let currentStatusFilter = paginationConfig.currentFilter || 'all';
    let currentSearchTerm = '';
    let currentRemainingTimeFilter = 'all';

    // --- DOM Element References ---
    const perPageSelect = document.getElementById('per-page');
    const exportButton = document.getElementById('export-excel');
    const selectAllButton = document.getElementById('select-all-accounts');
    const renewalBtn = document.getElementById('renewal-btn');
    const updateSurveyAccountsBtn = document.getElementById('update-survey-accounts');
    const searchBox = document.getElementById('search-input');
    const searchButton = document.getElementById('search-button');
    const resetButton = document.getElementById('reset-button');
    const remainingTimeFilter = document.getElementById('remaining-time-filter');
    const filterToggleBtn = document.querySelector('.filter-toggle-btn');
    const filterGroupContent = document.querySelector('.filter-group-content');
    const selectedCountElement = document.getElementById('selected-count');
    const exportForm = document.getElementById('export-form');
    const renewalForm = document.getElementById('renewal-form');
    const statusFilterMobile = document.getElementById('status-filter-mobile');
    
    // --- Main Filter Logic ---
    function applyFilters() {
        const tableBody = document.querySelector('.accounts-table tbody');
        if (!tableBody) return;
        
        const accounts = tableBody.querySelectorAll('tr[data-search-terms]');
        let visibleCount = 0;
        const searchTerms = currentSearchTerm.toLowerCase().split(/\s+/).filter(Boolean);

        accounts.forEach(account => {
            const searchData = account.dataset.searchTerms;
            const accountStatus = account.dataset.status;
            const remainingDays = parseInt(account.dataset.remainingDays, 10);
            
            // 1. Search Filter
            const matchesSearch = !searchTerms.length || searchTerms.every(term => searchData.includes(term));
            
            // 2. Status Filter
            const matchesStatus = currentStatusFilter === 'all' || currentStatusFilter === accountStatus;
            
            // 3. Remaining Time Filter
            let matchesRemainingTime = true;
            if (currentRemainingTimeFilter !== 'all') {
                switch (currentRemainingTimeFilter) {
                    case 'less-than-7':   matchesRemainingTime = (remainingDays >= 0 && remainingDays < 7); break;
                    case '7-to-30':       matchesRemainingTime = (remainingDays >= 7 && remainingDays <= 30); break;
                    case '30-to-90':      matchesRemainingTime = (remainingDays > 30 && remainingDays <= 90); break;
                    case 'more-than-90':  matchesRemainingTime = (remainingDays > 90); break;
                }
            }
            
            const shouldDisplay = matchesSearch && matchesStatus && matchesRemainingTime;
            account.style.display = shouldDisplay ? '' : 'none';
            if (shouldDisplay) visibleCount++;
        });

        handleEmptyState(visibleCount === 0 && accounts.length > 0);
        updatePaginationInfo(visibleCount);
        resetSelectionOnFilter();
    }
    
    // --- Event Listeners ---
    if (filterToggleBtn && filterGroupContent) {
    // Lắng nghe sự kiện click trên toàn bộ header của bộ lọc
    filterToggleBtn.parentElement.addEventListener('click', function() {
        // Kiểm tra trạng thái hiển thị của nội dung bộ lọc
        const isHidden = filterGroupContent.style.display === 'none';
        
        // Thay đổi trạng thái hiển thị
        filterGroupContent.style.display = isHidden ? 'flex' : 'none';
        
        // Thay đổi icon mũi tên
        const icon = filterToggleBtn.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-chevron-down', !isHidden);
            icon.classList.toggle('fa-chevron-up', isHidden);
        }
    });
}

    document.querySelectorAll('.filter-button').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.filter-button').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            currentStatusFilter = this.dataset.filter;
            if (statusFilterMobile) {
                statusFilterMobile.value = currentStatusFilter;
            }
            applyFilters();
        });
    });

    if (statusFilterMobile) {
        statusFilterMobile.addEventListener('change', function() {
            currentStatusFilter = this.value;
            const desktopButton = document.querySelector(`.filter-button[data-filter="${this.value}"]`);
            if (desktopButton) {
                document.querySelectorAll('.filter-button').forEach(btn => btn.classList.remove('active'));
                desktopButton.classList.add('active');
            }
            applyFilters();
        });
    }

    if (remainingTimeFilter) {
        remainingTimeFilter.addEventListener('change', function() {
            currentRemainingTimeFilter = this.value;
            applyFilters();
        });
    }

    if (searchButton) searchButton.addEventListener('click', () => {
        currentSearchTerm = searchBox.value.trim();
        applyFilters();
    });
    
    if (searchBox) searchBox.addEventListener('keypress', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            currentSearchTerm = searchBox.value.trim();
            applyFilters();
        }
    });
    
    if (resetButton) resetButton.addEventListener('click', () => {
        searchBox.value = '';
        currentSearchTerm = '';
        if(remainingTimeFilter) remainingTimeFilter.value = 'all';
        currentRemainingTimeFilter = 'all';
        document.querySelector('.filter-button[data-filter="all"]')?.click();
        applyFilters();
    });

    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            const url = new URL(window.location);
            url.searchParams.set('per_page', this.value);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        });
    }

    // --- Selection & Actions Logic ---
    function updateActionButtonsState() {
        const checkedBoxes = document.querySelectorAll('.account-checkbox:checked');
        const count = checkedBoxes.length;
        if (selectedCountElement) selectedCountElement.textContent = count;
        if (exportButton) exportButton.disabled = count === 0;
        if (renewalBtn) renewalBtn.disabled = count === 0;
    }

    function resetSelectionOnFilter() {
        document.querySelectorAll('.account-checkbox').forEach(cb => cb.checked = false);
        if (selectAllButton) selectAllButton.innerHTML = '<i class="fas fa-check-square"></i> Chọn tất cả';
        updateActionButtonsState();
    }
    
    document.querySelectorAll('.account-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateActionButtonsState);
    });

    if (selectAllButton) {
        selectAllButton.addEventListener('click', function() {
            const visibleCheckboxes = Array.from(document.querySelectorAll('.account-checkbox')).filter(cb => cb.closest('tr').style.display !== 'none');
            const allChecked = visibleCheckboxes.length > 0 && visibleCheckboxes.every(cb => cb.checked);
            visibleCheckboxes.forEach(cb => { cb.checked = !allChecked; });
            this.innerHTML = !allChecked ? '<i class="fas fa-times-square"></i> Bỏ chọn' : '<i class="fas fa-check-square"></i> Chọn tất cả';
            updateActionButtonsState();
        });
    }

    if (exportButton) {
        exportButton.addEventListener('click', () => {
            if (document.querySelectorAll('.account-checkbox:checked').length > 0) {
                 const selected = Array.from(document.querySelectorAll('.account-checkbox:checked')).map(cb => cb.value);
                 const hiddenInput = document.createElement('input');
                 hiddenInput.type = 'hidden';
                 hiddenInput.name = 'selected_accounts_json';
                 hiddenInput.value = JSON.stringify(selected);
                 exportForm.appendChild(hiddenInput);
                 exportForm.submit();
                 exportForm.removeChild(hiddenInput);
            }
        });
    }

    if (renewalForm) {
        renewalForm.addEventListener('submit', function(e) {
            this.querySelectorAll('input[name="selected_accounts[]"]').forEach(i => i.remove());
            const checkedBoxes = document.querySelectorAll('.account-checkbox:checked');
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                return;
            }
            checkedBoxes.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_accounts[]';
                input.value = cb.value;
                this.appendChild(input);
            });
        });
    }
    
    // --- UI Helper Functions ---
    function handleEmptyState(isEmpty) {
        let emptyRow = document.querySelector('.accounts-table tbody .empty-state-row');
        if (isEmpty) {
            if (!emptyRow) {
                emptyRow = document.createElement('tr');
                emptyRow.className = 'empty-state-row';
                emptyRow.innerHTML = `<td colspan="8"><div class="empty-state"><i class="fas fa-search"></i><p>Không tìm thấy tài khoản phù hợp</p></div></td>`;
                document.querySelector('.accounts-table tbody').appendChild(emptyRow);
            }
            emptyRow.style.display = '';
        } else if (emptyRow) {
            emptyRow.style.display = 'none';
        }
    }

    function updatePaginationInfo(visibleCount) {
        const paginationInfo = document.querySelector('.pagination-info');
        if (!paginationInfo) return;
        const isFiltering = currentSearchTerm || currentRemainingTimeFilter !== 'all' || currentStatusFilter !== paginationConfig.currentFilter;
        if (isFiltering) {
             paginationInfo.textContent = `Tìm thấy ${visibleCount} tài khoản`;
        } else {
             const startRecord = (paginationConfig.currentPage - 1) * paginationConfig.perPage + 1;
             const endRecord = Math.min(startRecord + paginationConfig.perPage - 1, paginationConfig.totalRecords);
             paginationInfo.textContent = `Hiển thị ${startRecord} đến ${endRecord} trong tổng số ${paginationConfig.totalRecords} tài khoản`;
        }
    }

    // --- Modal Management ---
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if(modal) modal.classList.add('active');
    }

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if(modal) modal.classList.remove('active');
    }

    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeModal(this.id);
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === "Escape") {
            document.querySelectorAll('.modal-overlay.active').forEach(modal => closeModal(modal.id));
        }
    });
    
    // --- Specific Modal Logic ---
    window.showAccountDetails = function(account) {
        document.getElementById('modal-username').textContent = account.username;
        document.getElementById('modal-password').textContent = account.password;
        document.getElementById('modal-start-time').textContent = account.start_time;
        document.getElementById('modal-end-time').textContent = account.end_time;
        
        const mountpointsList = document.getElementById('modal-mountpoints-list');
        mountpointsList.innerHTML = '';
        if (account.mountpoints && account.mountpoints.length > 0) {
            account.mountpoints.forEach(mp => {
                mountpointsList.innerHTML += `<tr><td>${mp.ip || 'N/A'}</td><td>${mp.port || 'N/A'}</td><td>${mp.mountpoint || 'N/A'}</td></tr>`;
            });
            document.getElementById('mountpoints-section').style.display = 'block';
        } else {
            mountpointsList.innerHTML = `<tr><td colspan="3" style="text-align: center;">Không có dữ liệu trạm</td></tr>`;
        }
        openModal('account-details-modal');
    };
    
    window.showChangePasswordModal = function(account) {
        document.getElementById('cp-username').value = account.username;
        document.getElementById('cp-current-password').value = account.password;
        document.getElementById('cp-new-password').value = '';
        document.getElementById('cp-confirm-password').value = '';
        document.getElementById('cp-account-id').value = account.id;
        openModal('change-password-modal');
    };

    if (updateSurveyAccountsBtn) {
        updateSurveyAccountsBtn.addEventListener('click', () => {
            const inputs = document.querySelectorAll('#update-accounts-tbody input');
            inputs.forEach(input => input.value = '');
            openModal('update-survey-account-modal');
        });
    }

    // --- API & Form Handling ---
    document.getElementById('add-account-row')?.addEventListener('click', () => {
        const tbody = document.getElementById('update-accounts-tbody');
        const newRow = document.createElement('tr');
        newRow.innerHTML = `<td><input type="text" class="form-input username-input" placeholder="Tên đăng nhập"></td><td><input type="text" class="form-input password-input" placeholder="Mật khẩu"></td>`;
        tbody.appendChild(newRow);
    });

    document.getElementById('confirm-update-accounts')?.addEventListener('click', function() {
        const accounts = [];
        document.querySelectorAll('#update-accounts-tbody tr').forEach(row => {
            const username = row.querySelector('.username-input').value.trim();
            const password = row.querySelector('.password-input').value.trim();
            if (username && password) accounts.push({ username, password });
        });
        if (accounts.length === 0) {
            alert('Vui lòng nhập ít nhất một tài khoản.');
            return;
        }
        
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';

        fetch(`${baseUrl}/public/handlers/rtk_account_handlers.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'validate_accounts', accounts: accounts })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const toConfirm = data.results.find(r => r.requires_confirmation);
                if (toConfirm) {
                    closeModal('update-survey-account-modal');
                    document.getElementById('otp-registration-id').value = toConfirm.registration_id;
                    openModal('otp-confirm-modal');
                } else {
                    alert(`Cập nhật thành công cho ${data.updated_count} tài khoản!`);
                    if (data.updated_count > 0) window.location.reload();
                    else closeModal('update-survey-account-modal');
                }
            } else {
                alert(data.message || 'Có lỗi xảy ra.');
            }
        }).catch(() => alert('Lỗi kết nối.'))
        .finally(() => {
            this.disabled = false;
            this.innerHTML = 'Xác nhận';
        });
    });

    document.getElementById('confirm-change-password')?.addEventListener('click', function() {
        const accountId = document.getElementById('cp-account-id').value;
        const newPassword = document.getElementById('cp-new-password').value;
        if (newPassword !== document.getElementById('cp-confirm-password').value) {
            alert('Mật khẩu xác nhận không khớp.');
            return;
        }
        if (!newPassword) {
            alert('Vui lòng nhập mật khẩu mới.');
            return;
        }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        
        const formData = new FormData();
        formData.append('action', 'change_password');
        formData.append('account_id', accountId);
        formData.append('new_password', newPassword);

        fetch(`${baseUrl}/public/handlers/rtk_account_handlers.php`, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                closeModal('change-password-modal');
                window.location.reload();
            }
        }).catch(() => alert('Lỗi kết nối.'))
        .finally(() => {
            this.disabled = false;
            this.innerHTML = 'Xác nhận';
        });
    });

    document.getElementById('confirm-otp-btn')?.addEventListener('click', function() {
        const regId = document.getElementById('otp-registration-id').value;
        const otp = document.getElementById('otp-input').value.trim();
        if (!otp) {
            alert('Vui lòng nhập OTP.');
            return;
        }
        
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        
        fetch(`${baseUrl}/public/handlers/confirm_transfer.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `registration_id=${regId}&otp=${encodeURIComponent(otp)}`
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                closeModal('otp-confirm-modal');
                window.location.reload();
            }
        }).catch(() => alert('Lỗi kết nối.'))
        .finally(() => {
            this.disabled = false;
            this.innerHTML = 'Xác nhận';
        });
    });
    
    document.querySelectorAll('.copy-btn').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.dataset.copyTarget;
            const textToCopy = document.getElementById(targetId)?.textContent;
            if (textToCopy) {
                navigator.clipboard.writeText(textToCopy).then(() => {
                    const icon = this.querySelector('i');
                    const originalIcon = icon.className;
                    icon.className = 'fas fa-check';
                    this.classList.add('copied');
                    setTimeout(() => {
                        icon.className = originalIcon;
                        this.classList.remove('copied');
                    }, 2000);
                }).catch(err => console.error('Failed to copy: ', err));
            }
        });
    });

    // --- Initialization ---
    updateActionButtonsState();
    updatePaginationInfo(document.querySelectorAll('.accounts-table tbody tr[data-search-terms]').length);
});