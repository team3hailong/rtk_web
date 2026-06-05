src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"

        // Initialize Feather Icons after the DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            feather.replace();
        });

        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('password-toggle-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.setAttribute('data-feather', 'eye'); // Change icon to eye-off
            } else {
                passwordInput.type = 'password';
                toggleIcon.setAttribute('data-feather', 'eye-off'); // Change icon back to eye
            }
            feather.replace(); // Re-render the Feather icon after changing its data-feather attribute
        }

