/**
 * Contact page functionality
 * Handles support form character counters and modal interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    // Character counter functionality
    const subjectInput = document.getElementById('subject');
    const messageTextarea = document.getElementById('message');
    const subjectCounter = document.getElementById('subject-counter');
    const messageCounter = document.getElementById('message-counter');
    
    // Update character counter for subject
    subjectInput.addEventListener('input', function() {
        const currentLength = this.value.length;
        const maxLength = this.getAttribute('maxlength');
        subjectCounter.textContent = currentLength + '/' + maxLength + ' ký tự';
        
        // Add visual feedback when approaching character limit
        if (currentLength >= maxLength * 0.9) {
            subjectCounter.classList.add('char-limit-warning');
        } else {
            subjectCounter.classList.remove('char-limit-warning');
        }
    });
    
    // Update character counter for message
    messageTextarea.addEventListener('input', function() {
        const currentLength = this.value.length;
        const maxLength = this.getAttribute('maxlength');
        messageCounter.textContent = currentLength + '/' + maxLength + ' ký tự';
        
        // Add visual feedback when approaching character limit
        if (currentLength >= maxLength * 0.9) {
            messageCounter.classList.add('char-limit-warning');
        } else {
            messageCounter.classList.remove('char-limit-warning');
        }
    });
    
    // Modal functionality
    const modal = document.getElementById('request-modal');
    const closeBtn = modal.querySelector('.modal-close-btn');
    const viewButtons = document.querySelectorAll('.btn-view-details');
    
    // Open modal with request details
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const requestData = JSON.parse(this.getAttribute('data-request'));
            const statusText = this.getAttribute('data-status-text');
            const categoryText = this.getAttribute('data-category-text');
            
            document.getElementById('modal-subject').textContent = requestData.subject;
            document.getElementById('modal-category').textContent = categoryText;
            document.getElementById('modal-status').textContent = statusText;
            document.getElementById('modal-message').textContent = requestData.message;
            
            // Format date
            const createdDate = new Date(requestData.created_at);
            const formattedDate = createdDate.toLocaleDateString('vi-VN') + ' ' + 
                                 createdDate.toLocaleTimeString('vi-VN', {hour: '2-digit', minute:'2-digit'});
            document.getElementById('modal-created').textContent = formattedDate;
            
            // Show/hide admin response
            const responseContainer = document.getElementById('response-container');
            if (requestData.admin_response) {
                document.getElementById('modal-response').textContent = requestData.admin_response;
                responseContainer.style.display = 'flex';
            } else {
                responseContainer.style.display = 'none';
            }
            
            modal.style.display = 'block';
        });
    });
    
    // Close modal
    closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // Auto-hide flash messages
    const messages = document.querySelectorAll('.message');
    messages.forEach(message => {
        setTimeout(() => {
            message.style.transition = 'opacity 0.5s ease';
            message.style.opacity = '0';
            setTimeout(() => {
                message.style.display = 'none';
            }, 500);
        }, 5000);
    });
});