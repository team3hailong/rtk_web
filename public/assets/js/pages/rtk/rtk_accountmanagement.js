/**
 * JavaScript for RTK Account Management Page
 */

document.addEventListener('DOMContentLoaded', function() {
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
    const searchInput = document.getElementById('account-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            // Get current active filter
            const activeFilter = document.querySelector('.filter-button.active').dataset.filter;
            
            // Apply both filter and search
            filterAndSearchAccounts(activeFilter, this.value.toLowerCase().trim());
        });
    }

    // View details buttons
    const viewButtons = document.querySelectorAll('.btn-view');
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const accountId = this.dataset.accountId;
            const username = this.dataset.username;
            const password = this.dataset.password;
            const startDate = this.dataset.start;
            const endDate = this.dataset.end;
            const status = this.dataset.status;
            const province = this.dataset.province;
            const mountpoints = JSON.parse(this.dataset.mountpoints);
            
            // Here you would typically open a modal with these details
            // For now, let's log them to console
            console.log({
                accountId, username, password, startDate, endDate, status, province, mountpoints
            });
            
            // Example implementation of a modal (you would need to add this HTML to your page)
            // You could also create the modal dynamically with JavaScript
            const modal = document.getElementById('account-detail-modal');
            if (modal) {
                // Populate modal with account details
                document.getElementById('modal-account-id').textContent = accountId;
                document.getElementById('modal-username').textContent = username;
                document.getElementById('modal-password').textContent = password;
                document.getElementById('modal-start-date').textContent = formatDate(startDate);
                document.getElementById('modal-end-date').textContent = formatDate(endDate);
                document.getElementById('modal-status').textContent = status;
                document.getElementById('modal-province').textContent = province;
                
                // Populate mountpoints
                const mountpointsList = document.getElementById('modal-mountpoints');
                mountpointsList.innerHTML = '';
                mountpoints.forEach(mp => {
                    const li = document.createElement('li');
                    li.textContent = mp.mountpoint;
                    mountpointsList.appendChild(li);
                });
                
                // Show the modal
                modal.style.display = 'block';
            }
        });
    });

    // Close modal functionality (if modal exists)
    const closeButton = document.querySelector('.close-modal');
    if (closeButton) {
        closeButton.addEventListener('click', function() {
            document.getElementById('account-detail-modal').style.display = 'none';
        });
    }

    // Function to filter accounts
    function filterAccounts(filter) {
        const accounts = document.querySelectorAll('.account-card');
        
        accounts.forEach(account => {
            const status = account.dataset.status;
            
            if (filter === 'all' || status === filter) {
                account.style.display = 'flex';
            } else {
                account.style.display = 'none';
            }
        });
    }
    
    // Function to filter and search accounts
    function filterAndSearchAccounts(filter, searchTerm) {
        const accounts = document.querySelectorAll('.account-card');
        
        accounts.forEach(account => {
            const status = account.dataset.status;
            const searchTerms = account.dataset.searchTerms || '';
            
            const matchesFilter = filter === 'all' || status === filter;
            const matchesSearch = searchTerm === '' || searchTerms.includes(searchTerm);
            
            if (matchesFilter && matchesSearch) {
                account.style.display = 'flex';
            } else {
                account.style.display = 'none';
            }
        });
    }
    
    // Helper function to format dates
    function formatDate(dateString) {
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute:'2-digit'});
        } catch (e) {
            return dateString || 'N/A';
        }
    }
});