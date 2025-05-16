document.addEventListener('DOMContentLoaded', function() {
    // Mobile notification bell functionality
    setupNotificationBell();
    
    // Popup details functionality if needed
    setupDetailsPopup();
});

function setupNotificationBell() {
    // First, check if we need to create the notification bell for mobile
    if (window.innerWidth <= 768) {
        createNotificationBell();
    }
    
    // Listen for window resize events to add/remove notification bell
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            if (!document.querySelector('.notification-bell')) {
                createNotificationBell();
            }
        } else {
            const bell = document.querySelector('.notification-bell');
            if (bell) {
                bell.remove();
            }
        }
    });
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
    
    // Clone activities from the activities box
    const activitiesBox = document.querySelector('.activities-box .activity-list');
    if (activitiesBox) {
        const clonedActivities = activitiesBox.cloneNode(true);
        clonedActivities.querySelectorAll('.activity-item').forEach(item => {
            item.classList.add('notification-item');
        });
        dropdown.appendChild(clonedActivities);
    } else {
        const emptyMessage = document.createElement('p');
        emptyMessage.className = 'empty-message';
        emptyMessage.textContent = 'Không có thông báo nào.';
        dropdown.appendChild(emptyMessage);
    }
    
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
    });
    
    // Add to the document
    document.body.appendChild(bell);
    document.body.appendChild(dropdown);
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