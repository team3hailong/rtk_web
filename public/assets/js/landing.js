// landing.js - Enhanced Landing page functionality

document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS animation library
    AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true,
        offset: 100
    });
    
    // Generate QR code
    generateQRCode();
    
    // Smooth scrolling for navigation links
    setupSmoothScrolling();
    
    // Scroll to top button visibility
    setupScrollToTop();
    
    // Mobile navigation toggle
    setupMobileNav();
    
    // Start the countdown timer
    setupCountdown();
});

// Function to generate QR Code
function generateQRCode() {
    // Get the current URL with 'register.php' appended
    const baseUrl = window.location.origin;
    const registerUrl = baseUrl + '/register.php';
    
    // Create QR code
    const qr = qrcode(0, 'M');
    qr.addData(registerUrl);
    qr.make();
    
    // Display QR code
    const qrcodeElement = document.getElementById('qrcode');
    if (qrcodeElement) {
        qrcodeElement.innerHTML = qr.createImgTag(5);
    }
}

// Set up smooth scrolling for all anchor links
function setupSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            } else {
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            }
        });
    });
}

// Show/hide scroll to top button based on scroll position
function setupScrollToTop() {
    const scrollTopButton = document.querySelector('.scroll-top-button');
    
    if (scrollTopButton) {
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollTopButton.style.opacity = '1';
                scrollTopButton.style.visibility = 'visible';
            } else {
                scrollTopButton.style.opacity = '0';
                scrollTopButton.style.visibility = 'hidden';
            }
        });
    }
}

// Social sharing functions
function shareOnFacebook() {
    const url = encodeURIComponent(window.location.href);
    const shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
    openShareWindow(shareUrl);
}

function shareOnTwitter() {
    const url = encodeURIComponent(window.location.href);
    const text = encodeURIComponent('Trải nghiệm tài khoản trạm CORS phủ khắp tỉnh Thái Nguyên miễn phí 3 tháng! Đăng ký ngay:');
    const shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${text}`;
    openShareWindow(shareUrl);
}

function shareOnLinkedIn() {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent('Trải nghiệm tài khoản trạm CORS phủ khắp tỉnh Thái Nguyên miễn phí 3 tháng!');
    const shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${url}`;
    openShareWindow(shareUrl);
}

function shareViaEmail() {
    const subject = encodeURIComponent('Trải nghiệm tài khoản trạm CORS phủ khắp tỉnh Thái Nguyên miễn phí 3 tháng!');
    const body = encodeURIComponent('Xin chào,\n\nTôi muốn chia sẻ với bạn cơ hội trải nghiệm dịch vụ tài khoản trạm CORS phủ khắp tỉnh Thái Nguyên miễn phí trong 3 tháng. Đăng ký tại đây: ' + window.location.href);
    window.location.href = `mailto:?subject=${subject}&body=${body}`;
}

function openShareWindow(url) {
    window.open(
        url,
        'share-dialog',
        'width=800,height=600,toolbar=0,status=0'
    );
}

// Setup countdown timer
function setupCountdown() {
    const countdownElement = document.getElementById('countdown');
    
    if (countdownElement) {
        // Set the countdown for 30 hours from now
        const hours = 30;
        let totalSeconds = hours * 60 * 60;
        
        // Update the countdown every second
        const countdownInterval = setInterval(function() {
            if (totalSeconds <= 0) {
                clearInterval(countdownInterval);
                countdownElement.textContent = "Đã kết thúc";
                return;
            }
            
            const hoursLeft = Math.floor(totalSeconds / 3600);
            const minutesLeft = Math.floor((totalSeconds % 3600) / 60);
            const secondsLeft = totalSeconds % 60;
            
            // Format the time
            const formattedHours = String(hoursLeft).padStart(2, '0');
            const formattedMinutes = String(minutesLeft).padStart(2, '0');
            const formattedSeconds = String(secondsLeft).padStart(2, '0');
            
            countdownElement.textContent = `${formattedHours}:${formattedMinutes}:${formattedSeconds}`;
            
            totalSeconds--;
        }, 1000);
    }
}

// Mobile Navigation Menu Toggle
function setupMobileNav() {
    const toggle = document.getElementById('navbar-toggle');
    const menu = document.getElementById('navbar-menu');
    const navLinks = document.querySelectorAll('.navbar-links li a');
    const body = document.body;
    
    if (toggle && menu) {
        toggle.addEventListener('click', function() {
            menu.classList.toggle('active');
            toggle.classList.toggle('active');
            
            // Prevent scrolling when menu is open on mobile
            if (menu.classList.contains('active')) {
                body.style.overflow = 'hidden';
            } else {
                body.style.overflow = '';
            }
        });
        
        // Close menu when clicking a nav link
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                menu.classList.remove('active');
                toggle.classList.remove('active');
                body.style.overflow = '';
            });
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const isClickInsideMenu = menu.contains(event.target);
            const isClickOnToggle = toggle.contains(event.target);
            
            if (!isClickInsideMenu && !isClickOnToggle && menu.classList.contains('active')) {
                menu.classList.remove('active');
                toggle.classList.remove('active');
                body.style.overflow = '';
            }
        });
    }
    
    // Add scrolling effect to navbar
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        }
    });
}
