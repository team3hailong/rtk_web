/**
 * JavaScript for RTK Account Management Page
 */

document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const modalOverlay = document.getElementById('account-details-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalAccountId = document.getElementById('modal-account-id');
    const modalUsername = document.getElementById('modal-username');
    const modalPassword = document.getElementById('modal-password');
    const modalStartTime = document.getElementById('modal-start-time');
    const modalEndTime = document.getElementById('modal-end-time');
    const modalProvince = document.getElementById('modal-province');
    const modalStatusBadge = document.getElementById('modal-status-badge');
    const modalMountpointsList = document.getElementById('modal-mountpoints-list');
    
    // Filter buttons functionality
    const filterButtons = document.querySelectorAll('.filter-button');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            // Get filter value
            const filterValue = this.dataset.filter;
            
            // Filter accounts
            filterAccounts(filterValue);
        });
    });

    // Search functionality
    const searchBox = document.querySelector('.search-box');
    if (searchBox) {
        searchBox.addEventListener('input', function() {
            // Get current active filter
            const activeFilter = document.querySelector('.filter-button.active').dataset.filter;
            
            // Apply both filter and search
            filterAndSearchAccounts(activeFilter, this.value.toLowerCase().trim());
        });
    }

    // Function to filter accounts
    function filterAccounts(filter) {
        const accounts = document.querySelectorAll('.accounts-table tbody tr');
        const searchTerm = (document.querySelector('.search-box')?.value || '').toLowerCase().trim();
        
        accounts.forEach(account => {
            if (!account.dataset.status) return; // Skip non-data rows like empty state
            
            let status = account.dataset.status;
            
            // Map "pending" to "locked" for filter purpose since we renamed "Đang xử lý" to "Đã khóa"
            const matchesFilter = filter === 'all' || 
                                 (filter === 'pending' && status === 'pending') || 
                                 (filter === 'active' && status === 'active') ||
                                 (filter === 'expired' && status === 'expired');
            
            if (searchTerm) {
                // If there's a search term, apply search filter too
                const searchTerms = account.dataset.searchTerms || '';
                const matchesSearch = searchTerm === '' || searchTerms.includes(searchTerm);
                account.style.display = (matchesFilter && matchesSearch) ? '' : 'none';
            } else {
                account.style.display = matchesFilter ? '' : 'none';
            }
        });
    }
    
    // Function to filter and search accounts
    function filterAndSearchAccounts(filter, searchTerm) {
        const accounts = document.querySelectorAll('.accounts-table tbody tr');
        
        accounts.forEach(account => {
            if (!account.dataset.status) return; // Skip non-data rows
            
            const status = account.dataset.status;
            const searchTerms = account.dataset.searchTerms || '';
            
            const matchesFilter = filter === 'all' || 
                                 (filter === 'pending' && status === 'pending') || 
                                 (filter === 'active' && status === 'active') ||
                                 (filter === 'expired' && status === 'expired');
            const matchesSearch = searchTerm === '' || searchTerms.includes(searchTerm);
            
            account.style.display = (matchesFilter && matchesSearch) ? '' : 'none';
        });
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
        
        modalTitle.textContent = `Chi Tiết Tài Khoản ${account.id}`;
        modalAccountId.textContent = account.id;
        modalUsername.textContent = account.username;
        modalPassword.textContent = account.password;
        modalStartTime.textContent = account.start_time;
        modalEndTime.textContent = account.end_time;
        modalProvince.textContent = account.province;
        
        // Set status badge
        modalStatusBadge.className = 'status-badge status-badge-modal ' + account.status_class;
        modalStatusBadge.textContent = account.status;
        
        // Populate mountpoints
        modalMountpointsList.innerHTML = '';
        if (account.mountpoints && account.mountpoints.length > 0) {
            account.mountpoints.forEach(mp => {
                const li = document.createElement('li');
                li.textContent = mp.mountpoint || mp;
                modalMountpointsList.appendChild(li);
            });
            document.getElementById('mountpoints-section').style.display = 'block';
        } else {
            document.getElementById('mountpoints-section').style.display = 'none';
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
        if (event.key === 'Escape' && modalOverlay.classList.contains('active')) {
            closeModal();
        }
    });
});