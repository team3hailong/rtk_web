<?php
// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(__DIR__)) . '/private/config/config.php';

// Sử dụng middleware session thay vì session_start thông thường
init_session();

// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$base_path = PUBLIC_URL; 
$project_root_path = PROJECT_ROOT_PATH;

// --- Include Database and other required files ---
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/Package.php';
require_once $project_root_path . '/private/classes/SupportRequest.php';
require_once $project_root_path . '/private/classes/Map.php';
require_once $project_root_path . '/private/classes/DeviceTracker.php';

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
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/home-custom.css">
      <!-- Leaflet for map display -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <!-- Leaflet z-index fix -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/leaflet-fix.css">
    
    <!-- Script thu thập vân tay thiết bị -->
    <script src="<?php echo $base_path; ?>/assets/js/device_fingerprint.js"></script>
    
    <!-- Custom favicon -->
    <link rel="icon" href="<?php echo $base_path; ?>/assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Preload important assets -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" as="style">
    <link rel="preload" href="<?php echo $base_path; ?>/assets/css/home.css" as="style">
</head>
<body>
    <!-- Header and Navigation -->
    <header class="site-header">
        <div class="container">            <div class="logo-container">                <a href="<?php echo $base_path; ?>/pages/auth/login.php" class="logo">
                    Taikhoandodac
                </a>
            </div>              <nav class="main-nav">
                <button class="mobile-menu-toggle" aria-label="Toggle menu">
                    <span class="hamburger"></span>
                </button>
                
                <ul class="nav-links">
                    <li><a href="#home" class="active">Trang chủ</a></li>
                    <li><a href="#features">Tính năng</a></li>
                    <li><a href="#packages">Gói tài khoản</a></li>
                    <li><a href="#support">Hỗ trợ</a></li>
                    <li><a href="#about">Thông tin</a></li>
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
    <section class="hero" id="home">
        <div class="container">
            <div class="hero-content">
                <h1>Dịch vụ tài khoản đo đạc RTK</h1>
                <p class="subtitle">Giải pháp RTK phù hợp cho các đơn vị, nhóm đo đạc nhỏ lẻ, hỗ trợ tận tâm và linh hoạt theo nhu cầu thực tế.</p>
                <div class="hero-cta">
                    <a href="#packages" class="btn btn-primary">Xem các gói tài khoản</a>
                    <a href="#features" class="btn btn-secondary">Khám phá tính năng</a>
                </div>
            </div>
            <div class="hero-info-container">
                <h2>Chất lượng tài khoản đo đạc</h2>
                <div class="hero-stats">
                    <div class="hero-stat-item">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-number">100+</div>
                        <div class="stat-text">Khách hàng đã sử dụng</div>
                    </div>
                    <div class="hero-stat-item">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-number">93%</div>
                        <div class="stat-text">Tỉ lệ hài lòng</div>
                    </div>
                    <div class="hero-stat-item">
                        <div class="stat-icon"><i class="fas fa-headset"></i></div>
                        <div class="stat-number">Tận tâm</div>
                        <div class="stat-text">Hỗ trợ cá nhân</div>
                    </div>
                </div>
                <div class="hero-action">
                    <a href="#features" class="btn-hero-action">Khám phá ngay <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <h2 class="section-title">Các tính năng nổi bật</h2>
            <p class="section-description">Chúng tôi cung cấp dịch vụ tài khoản đo đạc toàn diện nâng cao trải nghiệm dịch vụ</p>
            
            <div class="features-grid">                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <h3>Kết nối ổn định</h3>
                    <p>Hệ thống máy chủ hiện đại với kết nối băng thông cao, đảm bảo dịch vụ hoạt động ổn định 24/7 với độ trễ thấp.</p>
                    <a href="<?php echo $base_path; ?>/pages/auth/login.php" class="feature-link">Tìm hiểu thêm <i class="fas fa-chevron-right"></i></a>
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
                    <p>Cung cấp đầy đủ tài liệu hướng dẫn sử dụng dịch vụ, cài đặt và thiết lập kết nối đến các trạm đo đạc.</p>
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
            <h2 class="section-title">Các gói tài khoản</h2>
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
            
            <div class="packages-cta" id=support>
                <a href="<?php echo $base_path; ?>/pages/auth/login.php" class="btn btn-view-all">Xem tất cả các gói</a>
            </div>
        </div>
    </section>    <!-- Support Section -->




    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Bắt đầu sử dụng dịch vụ ngay hôm nay</h2>
                <p>Đăng ký tài khoản, mua gói dịch vụ và trải nghiệm các dịch vụ đo đạc RTK chất lượng cao</p>
                <div class="cta-buttons">
                    <a href="<?php echo $base_path; ?>/pages/auth/register.php" class="btn btn-primary">Đăng ký ngay</a>
                    <a href="#support" class="btn btn-outline">Liên hệ tư vấn</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Company Info Section -->
    <!--
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
    -->

    <!-- Footer -->
    <footer class="site-footer" id="about">
        <div class="container">
            <div class="footer-content">                <div class="footer-logo">
                    Taikhoandodac
                    <p>Dịch vụ tài khoản đo đạc RTK chất lượng cao</p>
                </div>
                
                <div class="footer-links">                    <div class="footer-column">
                        <h4>Dịch vụ</h4>
                        <ul>
                            <li><a href="#packages">Các gói tài khoản</a></li>
                            <li><a href="#features">Tính năng nổi bật</a></li>
                            <li><a href="#about">Về chúng tôi</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h4>Hỗ trợ</h4>
                        <ul>
                            <li><a href="#support">Liên hệ hỗ trợ</a></li>
                            <li><a href="#support">Hotline hỗ trợ</a></li>
                            <li><a href="#support">Email hỗ trợ</a></li>
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
                
            </div>
        </div>
    </footer>    <!-- JavaScript -->
    <script src="<?php echo $base_path; ?>/assets/js/home.js"></script>
</body>
</html>
