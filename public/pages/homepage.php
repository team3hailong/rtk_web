<?php
session_start();

// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(__DIR__)) . '/private/config/config.php';

// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$base_path = PUBLIC_URL; 
$project_root_path = PROJECT_ROOT_PATH;

// --- Include Database and other required files ---
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/Package.php';
require_once $project_root_path . '/private/classes/SupportRequest.php';
require_once $project_root_path . '/private/classes/Map.php';

// Fetch packages for display
$db = new Database();
$pdo = $db->getConnection();

// Initialize Package class and get all active packages
$package_obj = new Package();
$all_packages = $package_obj->getAllActivePackages();

// Get stations data for map display
$stations = Map::getAllStations($pdo);
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_accessible_stations = Map::getUserAccessibleStations($pdo, $user_id);

// Fetch company information for footer
$supportRequest = new SupportRequest($db);
$companyInfo = $supportRequest->getCompanyInfo();

// Security measures - Prevent XSS and other attacks
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dịch vụ tài khoản đo đạc RTK chất lượng cao, cung cấp các trạm base RTK trên toàn quốc">
    <title>Trang chủ - Dịch vụ tài khoản đo đạc RTK</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
      <!-- CSS styles -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/home.css">
    
    <!-- Leaflet for map display -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <!-- Leaflet z-index fix -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/leaflet-fix.css">
    
    <!-- Custom favicon -->
    <link rel="icon" href="<?php echo $base_path; ?>/assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Preload important assets -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" as="style">
    <link rel="preload" href="<?php echo $base_path; ?>/assets/css/home.css" as="style">
</head>
<body>
    <!-- Header and Navigation -->
    <header class="site-header">
        <div class="container">
            <div class="logo-container">
                <a href="<?php echo $base_path; ?>/pages/auth/login.php" class="logo">
                    <img src="<?php echo $base_path; ?>/assets/images/logo.png" alt="Đo đạc RTK Logo">
                </a>
            </div>
              <nav class="main-nav">
                <button class="mobile-menu-toggle" aria-label="Toggle menu">
                    <span class="hamburger"></span>
                </button>
                
                <ul class="nav-links">
                    <li><a href="<?php echo $base_path; ?>/pages/auth/login.php" class="active">Trang chủ</a></li>
                    <li><a href="<?php echo $base_path; ?>/pages/auth/login.php">Map hiển thị</a></li>
                    <li><a href="<?php echo $base_path; ?>/pages/auth/login.php">Mua tài khoản</a></li>
                    <li><a href="<?php echo $base_path; ?>/pages/auth/login.php">Hướng dẫn</a></li>
                    <li><a href="<?php echo $base_path; ?>/pages/auth/login.php">Hỗ trợ</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="<?php echo $base_path; ?>/pages/dashboard.php">Dashboard</a></li>
                    <?php else: ?>
                    <li><a href="<?php echo $base_path; ?>/pages/auth/login.php" class="btn btn-login">Đăng nhập</a></li>
                    <li><a href="<?php echo $base_path; ?>/pages/auth/register.php" class="btn btn-register">Đăng ký</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Dịch vụ tài khoản đo đạc RTK</h1>
                <p class="subtitle">Cung cấp dịch vụ đo đạc RTK chính xác, ổn định và đáng tin cậy cho các công trình xây dựng, đo đạc địa chính và khảo sát địa hình.</p>
                <div class="hero-cta">
                    <a href="<?php echo $base_path; ?>/pages/auth/login.php" class="btn btn-primary">Xem các gói dịch vụ</a>
                    <a href="<?php echo $base_path; ?>/pages/auth/login.php" class="btn btn-secondary">Xem bản đồ trạm</a>
                </div>
            </div>            <div class="hero-map-container">
                <h3>Bản đồ trạm base</h3>
                <div id="hero-map" class="hero-map"></div>
                <div class="view-all-stations">
                    <a href="<?php echo $base_path; ?>/pages/auth/login.php" class="btn-view-all-stations">Xem chi tiết bản đồ <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <h2 class="section-title">Các tính năng ưu việt</h2>
            <p class="section-description">Chúng tôi cung cấp giải pháp đo đạc RTK toàn diện với nhiều tính năng hàng đầu</p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <h3>Map hiển thị trạm</h3>
                    <p>Theo dõi trạng thái hoạt động của các trạm base RTK trên toàn quốc thông qua bản đồ trực quan, cập nhật theo thời gian thực.</p>
                    <a href="<?php echo $base_path; ?>/pages/auth/login.php" class="feature-link">Xem bản đồ <i class="fas fa-chevron-right"></i></a>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h3>Đa dạng gói dịch vụ</h3>
                    <p>Nhiều lựa chọn gói dịch vụ phù hợp với nhu cầu sử dụng, từ các gói ngắn hạn đến dài hạn, đáp ứng nhu cầu của người dùng</p>
                    <a href="<?php echo $base_path; ?>/pages/auth/login.php" class="feature-link">Xem các gói <i class="fas fa-chevron-right"></i></a>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h3>Tài liệu hướng dẫn</h3>
                    <p>Cung cấp đầy đủ tài liệu hướng dẫn sử dụng dịch vụ, cài đặt và thiết lập kết nối đến các trạm base.</p>
                    <a href="<?php echo $base_path; ?>/pages/auth/login.php" class="feature-link">Xem hướng dẫn <i class="fas fa-chevron-right"></i></a>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Hỗ trợ 24/7</h3>
                    <p>Đội ngũ kỹ thuật hỗ trợ 24/7, giải đáp mọi thắc mắc và xử lý sự cố nhanh chóng, đảm bảo dịch vụ liên tục.</p>
                    <a href="<?php echo $base_path; ?>/pages/auth/login.php" class="feature-link">Liên hệ hỗ trợ <i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
        </div>
    </section>

    <!-- Packages Section -->
    <section class="packages" id="packages">
        <div class="container">
            <h2 class="section-title">Các gói dịch vụ</h2>
            <p class="section-description">Lựa chọn gói dịch vụ phù hợp với nhu cầu của bạn</p>
            
            <div class="packages-slider">
                <?php if (empty($all_packages)): ?>
                <p class="no-packages">Hiện tại không có gói dịch vụ nào.</p>
                <?php else: ?>                <?php foreach ($all_packages as $index => $package): ?>
                    <?php
                    // Decode features JSON
                    $features = json_decode($package['features_json'] ?? '[]', true);
                    if ($features === null) {
                        $features = []; // Handle potential JSON decode error
                    }

                    // Xác định class cho card (thêm 'recommended' nếu cần)
                    $card_classes = 'package-card';
                    if (isset($package['is_recommended']) && $package['is_recommended']) {
                        $card_classes .= ' recommended';
                    }
                    ?>
                    <div class="<?php echo $card_classes; ?>">
                        <?php if (isset($package['is_recommended']) && $package['is_recommended']): ?>
                            <div class="recommended-badge">Phổ biến</div>
                        <?php endif; ?>

                        <h3><?php echo htmlspecialchars($package['name'] ?? 'Gói dịch vụ'); ?></h3>

                        <div class="package-price">
                            <?php echo number_format($package['price'] ?? 0, 0, ',', '.'); ?>đ
                            <span class="duration"><?php echo htmlspecialchars($package['duration_text'] ?? ''); ?></span>
                        </div>

                        <ul class="package-features">
                            <?php foreach ($features as $feature): ?>
                                <li>
                                    <i class="fas <?php echo htmlspecialchars($feature['icon'] ?? 'fa-check'); ?>"></i>
                                    <span><?php echo htmlspecialchars($feature['text'] ?? ''); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <a href="<?php echo $base_path; ?>/pages/auth/login.php" class="btn btn-package">Xem chi tiết</a>
                    </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="packages-cta">
                <a href="<?php echo $base_path; ?>/pages/auth/login.php" class="btn btn-view-all">Xem tất cả các gói</a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Bắt đầu sử dụng dịch vụ ngay hôm nay</h2>
                <p>Đăng ký tài khoản, mua gói dịch vụ và trải nghiệm các dịch vụ đo đạc RTK chất lượng cao</p>
                <div class="cta-buttons">
                    <a href="<?php echo $base_path; ?>/pages/auth/register.php" class="btn btn-primary">Đăng ký ngay</a>
                    <a href="<?php echo $base_path; ?>/pages/auth/login.php" class="btn btn-outline">Liên hệ tư vấn</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Company Info Section -->
    <section class="company-info" id="about">
        <div class="container">
            <h2 class="section-title">Về chúng tôi</h2>
            
            <div class="company-content">
                <div class="company-details">
                    <?php if ($companyInfo): ?>
                    <h3><?php echo htmlspecialchars($companyInfo['name'] ?? 'Công ty dịch vụ đo đạc RTK'); ?></h3>
                    <div class="company-description">
                        <?php echo nl2br(htmlspecialchars($companyInfo['description'] ?? 'Chúng tôi chuyên cung cấp dịch vụ đo đạc RTK chính xác, ổn định và đáng tin cậy.')); ?>
                    </div>
                    
                    <div class="contact-info">
                        <?php
                        $addressesJson = $companyInfo['address'] ?? null;
                        if ($addressesJson) {
                            $addresses = json_decode($addressesJson, true);
                            if (is_array($addresses) && !empty($addresses)) {
                                foreach ($addresses as $addressEntry) {
                                    $typeText = '';
                                    if (isset($addressEntry['type'])) {
                                        if ($addressEntry['type'] === 'trụ sở') {
                                            $typeText = 'Trụ sở: ';
                                        } elseif ($addressEntry['type'] === 'chi nhánh') {
                                            $typeText = 'Chi nhánh: ';
                                        }
                                    }
                                    $locationText = isset($addressEntry['location']) ? htmlspecialchars($addressEntry['location']) : 'N/A';
                        ?>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><strong><?php echo $typeText; ?></strong><?php echo $locationText; ?></span>
                        </div>
                        <?php
                                }
                            } elseif (is_string($companyInfo['address']) && !empty($companyInfo['address'])) {
                                // Fallback for plain text address
                        ?>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($companyInfo['address']); ?></span>
                        </div>
                        <?php
                            }
                        }
                        ?>
                        
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($companyInfo['phone'] ?? 'N/A'); ?></span>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($companyInfo['email'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                    <?php else: ?>
                    <h3>Công ty dịch vụ đo đạc RTK</h3>
                    <div class="company-description">
                        <p>Chúng tôi chuyên cung cấp dịch vụ đo đạc RTK chính xác, ổn định và đáng tin cậy cho các công trình xây dựng, đo đạc địa chính và khảo sát địa hình trên toàn quốc.</p>
                    </div>
                    
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Địa chỉ: 123 Đường XYZ, Quận ABC, TP. HCM</span>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span>Hotline: 0123 456 789</span>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>Email: info@rtk-service.com</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="company-map">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.2616421555595!2d106.7010838!3d10.7912472!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x317528afcee4a483%3A0x2a9ee85ce3cb7876!2zMTIzIMSQxrDhu51uZyBYWVosIFF14bqtbiAzLCBI4buTIENow60gTWluaCwgVmnhu4d0IE5hbQ!5e0!3m2!1svi!2s!4v1621325641258!5m2!1svi!2s" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <img src="<?php echo $base_path; ?>/assets/images/logo.png" alt="Đo đạc RTK Logo">
                    <p>Dịch vụ tài khoản đo đạc RTK chất lượng cao</p>
                </div>
                
                <div class="footer-links">
                    <div class="footer-column">
                        <h4>Dịch vụ</h4>
                        <ul>
                            <li><a href="<?php echo $base_path; ?>/pages/auth/login.php">Các gói dịch vụ</a></li>
                            <li><a href="<?php echo $base_path; ?>/pages/auth/login.php">Bản đồ trạm</a></li>
                            <li><a href="<?php echo $base_path; ?>/pages/auth/login.php">Hướng dẫn sử dụng</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h4>Hỗ trợ</h4>
                        <ul>
                            <li><a href="<?php echo $base_path; ?>/pages/auth/login.php">Liên hệ</a></li>
                            <li><a href="<?php echo $base_path; ?>/pages/auth/login.php">Hướng dẫn cài đặt</a></li>
                            <li><a href="<?php echo $base_path; ?>/pages/auth/login.php">Yêu cầu hỗ trợ</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h4>Tài khoản</h4>
                        <ul>
                            <li><a href="<?php echo $base_path; ?>/pages/auth/login.php">Đăng nhập</a></li>
                            <li><a href="<?php echo $base_path; ?>/pages/auth/register.php">Đăng ký</a></li>
                            <li><a href="<?php echo $base_path; ?>/pages/auth/login.php">Chương trình giới thiệu</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Dịch vụ đo đạc RTK. Tất cả các quyền đã được bảo lưu.</p>
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Youtube"><i class="fab fa-youtube"></i></a>
                    <a href="#" aria-label="Zalo">Zalo</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
            const navLinks = document.querySelector('.nav-links');
            
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    navLinks.classList.toggle('show');
                    mobileMenuToggle.classList.toggle('active');
                });
                
                // Add better touch area for mobile
                mobileMenuToggle.addEventListener('touchstart', function(e) {
                    e.stopPropagation();
                    navLinks.classList.toggle('show');
                    mobileMenuToggle.classList.toggle('active');
                }, {passive: true});
            }
            
            // Make each nav link item also close the menu when clicked
            const navItems = document.querySelectorAll('.nav-links li a');
            navItems.forEach(item => {
                item.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        navLinks.classList.remove('show');
                        mobileMenuToggle.classList.remove('active');
                    }
                });
            });
            
            // Close mobile menu when clicking outside
            document.addEventListener('click', function(event) {
                if (!event.target.closest('.main-nav') && navLinks.classList.contains('show')) {
                    navLinks.classList.remove('show');
                    mobileMenuToggle.classList.remove('active');
                }
            });
            
            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href').substring(1);
                    if (!targetId) return;
                    
                    const targetElement = document.getElementById(targetId);
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 80,
                            behavior: 'smooth'
                        });
                        
                        // Close mobile menu after clicking
                        if (navLinks.classList.contains('show')) {
                            navLinks.classList.remove('show');
                            mobileMenuToggle.classList.remove('active');
                        }
                    }
                });            });
        });
    </script>
    
    <!-- Leaflet map script -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Data for map
            const stationsData = <?php echo json_encode($stations); ?>;
            const userAccessibleStations = <?php echo json_encode($user_accessible_stations); ?>;
            
            // Initialize map
            const initialCenter = [16.0, 106.0]; // Center of Vietnam
            const initialZoom = 5;
            const map = L.map('hero-map').setView(initialCenter, initialZoom);
            
            // Add tile layer (map background)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);
            
            // Add stations to map
            const radiusKm = 15;
            stationsData.forEach((station) => {
                // Skip stations with status 0 (Bị tắt) or invalid coordinates
                if (station.lat && station.long && station.status != 0 && station.status != -1) {
                    const pos = [parseFloat(station.lat), parseFloat(station.long)];
                    let circleColor = '#3cb043'; // Default: Green for Status 1 (Hoạt động)
                    
                    // Check if user has access to this station
                    let isUserAccessible = userAccessibleStations.includes(station.id);
                    
                    // Set color based on status and accessibility
                    if (station.status == 3) {
                        circleColor = '#e74c3c'; // Red for Status 3 (Không hoạt động)
                    } else if (isUserAccessible) {
                        circleColor = '#3498db'; // Blue for stations user has access to
                    }
                    
                    // Add circle to map
                    const circle = L.circle(pos, {
                        radius: radiusKm * 1000,
                        color: circleColor,
                        fillColor: circleColor,
                        fillOpacity: 0.3,
                        weight: 1,
                        className: 'map-circle' // Add a class for styling
                    }).addTo(map);
                }
            });
        });
    </script>
</body>
</html>
