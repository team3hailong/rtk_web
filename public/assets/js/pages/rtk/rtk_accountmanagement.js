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
    
    // Thêm sự kiện cho từng checkbox
    accountCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateExportButtonState);
    });
    
    // Xử lý nút chọn tất cả
    if (selectAllButton) {
        selectAllButton.addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('.account-checkbox');
            const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });
            
            updateExportButtonState();
            
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
    }
    
    // Filter buttons functionality
    const filterButtons = document.querySelectorAll('.filter-button');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Lấy giá trị filter mới
            const filterValue = this.dataset.filter;
            
            // Chuyển hướng đến URL với filter mới
            window.location.href = buildPaginationUrl({
                filter: filterValue,
                page: 1 // Luôn reset về trang đầu tiên khi filter thay đổi
            });
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
    }

    // Search functionality
    const searchBox = document.querySelector('.search-box');
    if (searchBox) {
        searchBox.addEventListener('input', function() {
            // Lấy giá trị tìm kiếm
            const searchTerm = this.value.toLowerCase().trim();
            
            // Áp dụng tìm kiếm client-side trong trang hiện tại
            searchAccounts(searchTerm);
        });
    }

    // Hàm tìm kiếm tài khoản trong trang hiện tại
    function searchAccounts(searchTerm) {
        const accounts = document.querySelectorAll('.accounts-table tbody tr');
        
        accounts.forEach(account => {
            if (!account.dataset.searchTerms) return; // Bỏ qua hàng không phải dữ liệu
            
            const searchTerms = account.dataset.searchTerms;
            const matchesSearch = !searchTerm || searchTerms.includes(searchTerm);
            
            account.style.display = matchesSearch ? '' : 'none';
        });
        
        // Cập nhật thông tin phân trang
        updatePaginationInfo();
    }
    
    // Cập nhật thông tin phân trang dựa trên số lượng hàng hiện đang hiển thị
    function updatePaginationInfo() {
        const paginationInfo = document.querySelector('.pagination-info');
        if (!paginationInfo) return;
        
        const visibleRows = document.querySelectorAll('.accounts-table tbody tr[style=""]').length;
        const totalFiltered = document.querySelectorAll('.accounts-table tbody tr:not([style*="none"])').length;
        
        paginationInfo.textContent = `Hiển thị ${totalFiltered} trên tổng số ${paginationConfig.totalRecords} tài khoản`;
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
        if (event.key === 'Escape' && modalOverlay && modalOverlay.classList.contains('active')) {
            closeModal();
        }
    });
    
    // Khởi tạo trạng thái nút xuất khi tải trang
    updateExportButtonState();
});