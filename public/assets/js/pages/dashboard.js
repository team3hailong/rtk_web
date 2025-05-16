document.addEventListener('DOMContentLoaded', function() {
    // Mobile notification bell functionality
    setupNotificationBell();
    
    // Popup details functionality if needed
    setupDetailsPopup();
    
    // Add padding to main content on mobile to avoid header overlap
    adjustContentPadding();
    
    // Listen for window resize events
    window.addEventListener('resize', function() {
        adjustContentPadding();
    });
});

function adjustContentPadding() {
    const contentWrapper = document.querySelector('.content-wrapper');
    if (contentWrapper && window.innerWidth <= 768) {
        contentWrapper.style.paddingTop = '60px';
    } else if (contentWrapper) {
        contentWrapper.style.paddingTop = '0';
    }
}

function setupNotificationBell() {
    // Create notification bell - always visible on all devices
    createNotificationBell();
}

function createNotificationBell() {
    // Only create if it doesn't exist yet
    if (document.querySelector('.notification-bell')) return;
    
    // Create notification bell
    const bell = document.createElement('div');
    bell.className = 'notification-bell';
    bell.innerHTML = '<i class="fas fa-bell"></i>';
    
    // Create badge for unread count if we have any unread notifications
    const unreadCount = window.dashboardData?.unreadNotificationsCount || 0;
    if (unreadCount > 0) {
        const badge = document.createElement('span');
        badge.className = 'notification-badge';
        badge.textContent = unreadCount > 9 ? '9+' : unreadCount;
        bell.appendChild(badge);
    }
    
    // Create notification dropdown
    const dropdown = document.createElement('div');
    dropdown.className = 'notification-dropdown';
    
    // Add header to dropdown
    const header = document.createElement('div');
    header.className = 'notification-header';
    header.innerHTML = `
        <div class="notification-title">Thông báo</div>
        <button class="mark-all-read">Đánh dấu đã đọc</button>
    `;
    dropdown.appendChild(header);
    
    // Get activities data and append to the dropdown
    // Instead of cloning from activity-list which might be hidden on mobile
    // We'll create a new list manually with the recent activities data
    const notificationsList = document.createElement('div');
    notificationsList.className = 'activity-list';
    
    if (window.dashboardData && window.dashboardData.recentActivities && window.dashboardData.recentActivities.length > 0) {
        window.dashboardData.recentActivities.forEach(activity => {
            const notificationItem = document.createElement('div');
            notificationItem.className = 'activity-item notification-item';
            if (activity.has_read === 0) {
                notificationItem.classList.add('unread');
            }
            
            const content = document.createElement('div');
            content.className = 'activity-content';
            
            const description = document.createElement('p');
            description.innerHTML = activity.description;
            
            const time = document.createElement('small');
            time.className = 'activity-time';
            
            // Format date: DD/MM/YYYY HH:MM
            const date = new Date(activity.created_at);
            const formattedDate = date.toLocaleString('vi-VN', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });
            
            time.textContent = formattedDate;
            
            content.appendChild(description);
            content.appendChild(time);
            notificationItem.appendChild(content);
            notificationsList.appendChild(notificationItem);
        });
    } else {
        const emptyMessage = document.createElement('p');
        emptyMessage.className = 'empty-message';
        emptyMessage.textContent = 'Không có thông báo nào.';
        notificationsList.appendChild(emptyMessage);
    }
    
    dropdown.appendChild(notificationsList);
    
    // Add event listeners
    bell.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('open');
    });
    
    document.addEventListener('click', function(e) {
        if (dropdown.classList.contains('open')) {
            dropdown.classList.remove('open');
        }
    });
    
    dropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // Mark all as read functionality
    dropdown.querySelector('.mark-all-read')?.addEventListener('click', function() {
        const unreadItems = dropdown.querySelectorAll('.notification-item.unread');
        unreadItems.forEach(item => {
            item.classList.remove('unread');
        });
        
        const badge = bell.querySelector('.notification-badge');
        if (badge) {
            badge.remove();
        }
        
        // Here you would also make an AJAX call to mark notifications as read in the database
        markNotificationsAsRead();
    });    // Add to the document - position in the header next to hamburger menu
    const headerRight = document.querySelector('.header-right');
    if (headerRight) {
        // Insert bell into header-right (mobile view)
        headerRight.appendChild(bell);
    } else {
        // Fallback if header-right doesn't exist
        document.body.appendChild(bell);
    }
    
    // Append dropdown to the body for proper z-index behavior
    document.body.appendChild(dropdown);
    
    // Position dropdown relative to the bell
    const updateDropdownPosition = () => {
        const bellRect = bell.getBoundingClientRect();
        
        if (window.innerWidth <= 768) {
            // Mobile positioning
            dropdown.style.position = 'fixed';
            dropdown.style.top = (bellRect.bottom + 10) + 'px';
            dropdown.style.right = '1rem';
        } else {
            // Desktop positioning
            dropdown.style.position = 'fixed';
            dropdown.style.top = (bellRect.bottom + 10) + 'px';
            dropdown.style.right = '2rem';
        }
    };
    
    // Update position on window resize
    window.addEventListener('resize', updateDropdownPosition);
    
    // Initial position update
    updateDropdownPosition();
}

function markNotificationsAsRead() {
    // Simple AJAX implementation to mark notifications as read
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/rtk_web/public/handlers/mark_notifications_read.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            console.log('Notifications marked as read');
        }
    };
    xhr.send();
}

function setupDetailsPopup() {
    const detailsPopup = document.getElementById('details-popup');
    const closePopupBtn = document.getElementById('close-popup');
    
    if (!detailsPopup || !closePopupBtn) return;
    
    // Setup click event for view details buttons
    document.querySelectorAll('.btn-view-details').forEach(button => {
        button.addEventListener('click', function() {
            const newValues = JSON.parse(this.getAttribute('data-new-values'));
            const tableBody = document.getElementById('details-table-body');
            
            if (!tableBody) return;
            
            // Clear previous content
            tableBody.innerHTML = '';
            
            // Populate with new data
            for (const key in newValues) {
                const row = document.createElement('tr');
                
                const keyCell = document.createElement('td');
                keyCell.textContent = key; // You could translate this if needed
                
                const valueCell = document.createElement('td');
                valueCell.textContent = typeof newValues[key] === 'object' 
                    ? JSON.stringify(newValues[key]) 
                    : newValues[key];
                
                row.appendChild(keyCell);
                row.appendChild(valueCell);
                tableBody.appendChild(row);
            }
            
            detailsPopup.classList.remove('hidden');
        });
    });
    
    // Close popup when close button is clicked
    closePopupBtn.addEventListener('click', function() {
        detailsPopup.classList.add('hidden');
    });
    
    // Close popup when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === detailsPopup) {
            detailsPopup.classList.add('hidden');
        }
    });
}