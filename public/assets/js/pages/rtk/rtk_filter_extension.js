/**
 * Update filter function for the RTK Account Management page
 */
document.addEventListener('DOMContentLoaded', function() {
    // Get references to filter elements
    const remainingTimeFilter = document.getElementById('remaining-time-filter');
    const searchButton = document.getElementById('search-button');
    const resetButton = document.getElementById('reset-button');
    const searchBox = document.getElementById('search-input');
    const filterToggleBtn = document.querySelector('.filter-toggle-btn');
    const filterGroupContent = document.querySelector('.filter-group-content');

    // Current filter state
    let currentRemainingTimeFilter = 'all';

    // Apply remaining time filter
    function filterByRemainingTime(account, filterValue) {
        if (filterValue === 'all') return true;
        
        // Get the remaining days from the data attribute
        const remainingDays = parseInt(account.dataset.remainingDays || '0', 10);
        
        // Apply filter based on value
        switch (filterValue) {
            case 'less-than-7':
                return remainingDays >= 0 && remainingDays < 7;
            case '7-to-30':
                return remainingDays >= 7 && remainingDays <= 30;
            case '30-to-90':
                return remainingDays > 30 && remainingDays <= 90;
            case 'more-than-90':
                return remainingDays > 90;
            default:
                return true;
        }
    }

    // Override the existing applyFilters function to include remaining time filter
    if (typeof applyFilters !== 'undefined') {
        const originalApplyFilters = window.applyFilters;
        
        window.applyFilters = function() {
            const accounts = document.querySelectorAll('.accounts-table tbody tr:not(.empty-state-row)');
            let visibleCount = 0;
            
            // Get current search terms
            const searchTerms = currentSearchTerm.split(/\s+/).filter(Boolean);
            
            accounts.forEach(account => {
                if (!account.dataset.searchTerms) return; // Skip non-data rows
                
                // Text search
                const searchData = account.dataset.searchTerms.toLowerCase();
                const matchesSearch = !searchTerms.length || searchTerms.every(term => searchData.includes(term));
                
                // Status filter
                let matchesStatusFilter = true;
                if (currentFilter !== 'all') {
                    const accountStatus = account.dataset.status;
                    matchesStatusFilter = (currentFilter === accountStatus);
                }
                
                // Remaining time filter
                const matchesRemainingTimeFilter = filterByRemainingTime(account, currentRemainingTimeFilter);
                
                // Display/hide based on filter results
                const shouldDisplay = matchesSearch && matchesStatusFilter && matchesRemainingTimeFilter;
                account.style.display = shouldDisplay ? '' : 'none';
                if (shouldDisplay) visibleCount++;
            });
            
            // Show "No data" message if no accounts match
            handleEmptyState(visibleCount === 0);
            
            // Update pagination info
            updatePaginationInfo(visibleCount);
            
            // Update select all button
            if (selectAllButton) {
                selectAllButton.innerHTML = '<i class="fas fa-check-square"></i> Chọn tất cả';
            }
            
            // Uncheck all checkboxes when filters change
            document.querySelectorAll('.account-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Update export and renewal button states
            if (typeof updateExportButtonState === 'function') updateExportButtonState();
            if (typeof updateRenewalButtonState === 'function') updateRenewalButtonState();
        };
    }

    // Add event listeners for the new filter controls
    if (remainingTimeFilter) {
        remainingTimeFilter.addEventListener('change', function() {
            currentRemainingTimeFilter = this.value;
            if (typeof applyFilters === 'function') applyFilters();
        });
    }
    
    if (searchButton && searchBox) {
        searchButton.addEventListener('click', function() {
            window.currentSearchTerm = searchBox.value.toLowerCase().trim();
            if (typeof applyFilters === 'function') applyFilters();
        });
    }
    
    if (resetButton) {
        resetButton.addEventListener('click', function() {
            // Reset search box
            if (searchBox) {
                searchBox.value = '';
                window.currentSearchTerm = '';
            }
            
            // Reset remaining time filter
            if (remainingTimeFilter) {
                remainingTimeFilter.value = 'all';
                currentRemainingTimeFilter = 'all';
            }
            
            // Reset status filter buttons
            const filterButtons = document.querySelectorAll('.filter-button');
            filterButtons.forEach(button => {
                if (button.dataset.filter === 'all') {
                    button.classList.add('active');
                } else {
                    button.classList.remove('active');
                }
            });
            window.currentFilter = 'all';
            
            // Apply filters
            if (typeof applyFilters === 'function') applyFilters();
        });
    }
      // Add keypress event for search box
    if (searchBox) {
        searchBox.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                window.currentSearchTerm = this.value.toLowerCase().trim();
                if (typeof applyFilters === 'function') applyFilters();
                e.preventDefault();
            }
        });
    }
    
    // Add toggle functionality for filter container
    if (filterToggleBtn && filterGroupContent) {
        filterToggleBtn.addEventListener('click', function() {
            // Toggle the visibility of filter content
            const isVisible = filterGroupContent.style.display !== 'none';
            filterGroupContent.style.display = isVisible ? 'none' : '';
            
            // Update the icon
            const icon = filterToggleBtn.querySelector('i');
            if (icon) {
                icon.className = isVisible ? 'fas fa-chevron-up' : 'fas fa-chevron-down';
            }
        });
    }
});
