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
    initCountdown();
});

// Function to generate QR Code
function generateQRCode() {
    // Use the register URL passed from PHP
    const registerUrl = window.registerUrl;
    
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

// Initialize countdown timer
function initCountdown() {
    // Set target date: 31/12/2024 23:59:59 (Vietnam timezone)
    const targetDate = new Date('2025-06-06T23:59:59+07:00');
    
    function updateCountdown() {
        const now = new Date();
        const timeLeft = targetDate - now;
        
        if (timeLeft <= 0) {
            document.getElementById('countdown').innerHTML = "00:00:00:00";
            return;
        }
        
        const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
        const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
        
        // Format with leading zeros
        const formattedDays = days.toString().padStart(2, '0');
        const formattedHours = hours.toString().padStart(2, '0');
        const formattedMinutes = minutes.toString().padStart(2, '0');
        const formattedSeconds = seconds.toString().padStart(2, '0');
        
        document.getElementById('countdown').innerHTML = `${formattedDays}:${formattedHours}:${formattedMinutes}:${formattedSeconds}`;
    }
    
    // Update countdown immediately
    updateCountdown();
    
    // Update every second
    setInterval(updateCountdown, 1000);
}

// Social sharing functions
function shareOnFacebook() {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent('Trạm CORS Thái Nguyên - Miễn Phí 3 Tháng');
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'width=600,height=400');
}

function shareOnTwitter() {
    const url = encodeURIComponent(window.location.href);
    const text = encodeURIComponent('Trải nghiệm dịch vụ trạm CORS Thái Nguyên miễn phí 3 tháng!');
    window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank', 'width=600,height=400');
}

function shareOnLinkedIn() {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent('Trạm CORS Thái Nguyên - Miễn Phí 3 Tháng');
    window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}`, '_blank', 'width=600,height=400');
}

function shareViaEmail() {
    const subject = encodeURIComponent('Trạm CORS Thái Nguyên - Miễn Phí 3 Tháng');
    const body = encodeURIComponent(`Xin chào,\n\nTôi muốn chia sẻ với bạn cơ hội trải nghiệm dịch vụ trạm CORS Thái Nguyên miễn phí 3 tháng.\n\nXem chi tiết tại: ${window.location.href}\n\nTrân trọng!`);
    window.location.href = `mailto:?subject=${subject}&body=${body}`;
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
