document.addEventListener('DOMContentLoaded', function() {
    // --- Company Details Toggle Removed ---

    // --- Input Focus Styling ---
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        if (!input.readOnly) { // Don't apply focus styles to readonly fields
            input.addEventListener('focus', () => {
                // Use CSS variables if available, otherwise fallback
                input.style.borderColor = 'var(--primary-500, #4caf50)';
                input.style.boxShadow = '0 0 0 2px rgba(76, 175, 80, 0.2)'; // Example focus shadow
            });
            input.addEventListener('blur', () => {
                input.style.borderColor = 'var(--gray-300, #ccc)';
                input.style.boxShadow = 'none';
            });
        }
    });

    // --- Auto-hide Flash Messages ---
    const messages = document.querySelectorAll('.message');
    messages.forEach(message => {
        setTimeout(() => {
            message.style.transition = 'opacity 0.5s ease';
            message.style.opacity = '0';
            // Remove the element from the DOM after the transition completes
            setTimeout(() => message.remove(), 500);
        }, 5000); // Hide after 5 seconds (5000 milliseconds)
    });
    
    // --- Responsive Adjustments for Mobile ---
    function handleResponsiveLayout() {
        const windowWidth = window.innerWidth;
        const formGroups = document.querySelectorAll('.form-group');
        
        if (windowWidth <= 480) {
            // Apply mobile-specific styles
            formGroups.forEach(group => {
                // Add additional classes or modify styles for very small screens
                group.classList.add('mobile-view');
            });
        } else {
            // Remove mobile-specific styles when on larger screens
            formGroups.forEach(group => {
                group.classList.remove('mobile-view');
            });
        }
    }
    
    // Call once on page load
    handleResponsiveLayout();
    
    // Add resize listener for responsive adjustments
    window.addEventListener('resize', handleResponsiveLayout);
});
