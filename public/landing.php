<?php
// Include any necessary configuration or header files
$voucher_code = "TNN3THANG"; // Define voucher code here
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$register_url = $base_url . "/public/pages/auth/register.php?voucher=" . $voucher_code;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chương trình tri ân miễn phí 3 tháng sử dụng tài khoản CORS trên địa bàn tỉnh Thái Nguyên</title>
    <link rel="stylesheet" href="assets/css/landing.css">
    <!-- Font Awesome for social icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- AOS Animation library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation Header -->
    <nav class="navbar">
        <div class="container">            <div class="navbar-logo">
                <a href="#">
                    <span class="logo-text">Taikhoandodac</span>
                </a>
            </div>
            <div class="navbar-toggle" id="navbar-toggle">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
                <a href="#register-section" class="navbar-cta-button pulse-animation mobile-only">Đăng ký ngay</a>
            </div>
            <div class="navbar-menu" id="navbar-menu">
                <ul class="navbar-links">
                    <li><a href="#intro">Giới thiệu</a></li>
                    <li><a href="#features">Điểm nổi bật</a></li>
                    <li><a href="#cors-info">Trạm CORS</a></li>
                    <li><a href="#how-to">Cách nhận</a></li>
                    <li><a href="#testimonials">Đánh giá</a></li>
                </ul>            </div>
            <a href="#register-section" class="navbar-cta-button pulse-animation">Đăng ký ngay</a>
        </div>
    </nav>

    <header class="hero-section" id="intro">
        <div class="container">            <h1 data-aos="fade-up">Chương trình tri ân miễn phí 3 tháng sử dụng tài khoản CORS trên địa bàn tỉnh Thái Nguyên</h1>
            <p class="tagline" data-aos="fade-up" data-aos-delay="200">Giải pháp đo đạc chính xác, tiết kiệm thời gian và chi phí</p>
            <div class="cta-primary" data-aos="fade-up" data-aos-delay="400">
                <a href="#register-section" class="cta-button pulse-animation">ĐĂNG KÝ NGAY</a>
            </div>
        </div>
    </header>
    
      <!-- Phần 2: Điểm nổi bật -->
    <section class="section features-section" id="features">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <h2>Điểm nổi bật</h2>
                <p>Lý do nên chọn dịch vụ trạm CORS của chúng tôi</p>
            </div>
            <div class="features-grid">                <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card-header">
                        <div class="feature-icon">
                            <i class="fas fa-broadcast-tower"></i>
                        </div>
                        <h3>Phủ sóng rộng khắp</h3>
                    </div>
                    <div class="feature-card-content">
                        <p>Hệ thống trạm CORS phủ sóng toàn tỉnh Thái Nguyên, đảm bảo kết nối ổn định và liên tục trên toàn địa bàn Tỉnh</p>
                    </div>
                </div>                <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card-header">
                        <div class="feature-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3>Độ chính xác cao</h3>
                    </div>
                    <div class="feature-card-content">
                        <p>Hệ thống được hiệu chuẩn theo mốc tọa độ, cao độ VN2000, đảm bảo độ chính xác của dữ liệu mọi lúc, mọi nơi</p>
                    </div>
                </div>                <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card-header">
                        <div class="feature-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h3>Hỗ trợ nhiều loại máy</h3>
                    </div>
                    <div class="feature-card-content">
                        <p>Hệ thống hỗ trợ đầy đủ cho tất cả các dòng máy RTK phổ biến hiện nay như: CHC, ComNav, Geomate, eSurvey, Mediant,...</p>
                    </div>
                </div>
                <div class="feature-card" data-aos="fade-up" data-aos-delay="400">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Hỗ trợ 24/7</h3>
                    <p>Đội ngũ hỗ trợ khách hàng luôn sẵn sàng giải đáp mọi thắc mắc và xử lý các vấn đề kỹ thuật bất cứ khi nào bạn gặp khó khăn.</p>
                </div>
            </div>
        </div>
    </section>
      <!-- Phần 3: Định nghĩa trạm CORS và bản đồ -->
    <section class="section cors-info-section" id="cors-info">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <h2>Trạm CORS là gì?</h2>
                <p>Công nghệ định vị hiện đại cho ngành đo đạc</p>
            </div>
            <div class="cors-grid">
                <div class="cors-definition" data-aos="fade-right">
                    <h3>Định nghĩa trạm CORS</h3>
                    <p><strong>CORS</strong> (<em>Continuously Operating Reference Station</em>) là hệ thống trạm tham chiếu hoạt động liên tục, cung cấp dữ liệu vệ tinh chính xác cho các thiết bị đo đạc RTK. Công nghệ này sử dụng hệ thống vệ tinh định vị toàn cầu (GNSS) để xác định vị trí với độ chính xác cao.</p>
                    
                    <p>Ứng dụng của trạm CORS:</p>
                    
                    <ul class="tech-list">
                        <li>Khảo sát địa hình, địa chính</li>
                        <li>Quy hoạch và xây dựng công trình</li>
                        <li>Đo vẽ bản đồ địa chính</li>
                        <li>Quản lý hạ tầng và tài nguyên</li>
                    </ul>
                    
                    <p>Hệ thống trạm CORS tại Thái Nguyên được đặt tại các vị trí chiến lược, đảm bảo phủ sóng tối ưu trên toàn tỉnh, mang lại hiệu quả cao nhất cho người sử dụng.</p>
                </div>
                <div class="cors-map" data-aos="fade-left">
                    <h3>Bản đồ trạm CORS tại Thái Nguyên</h3>
                    <div class="map-container">
                        <img src="assets/img/danhsachtramTNN.png" alt="Bản đồ trạm CORS Thái Nguyên" class="map-image">
                    </div>
                    
                </div>
            </div>
        </div>
    </section>
      <!-- Phần 4: Cách nhận tài khoản miễn phí 3 tháng -->
    <section class="section how-to-section" id="how-to">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <h2>Cách nhận tài khoản miễn phí 3 tháng</h2>
            </div>
            <div class="steps-container-large">
                <div class="step-row">
                    <div class="step-card-large" data-aos="fade-right" data-aos-delay="100">
                        <div class="step-number">1</div>
                        <div class="step-image-large">
                            <img src="/public/assets/img/An-dang-ky.png" alt="Đăng ký tài khoản CORS" class="step-image">
                            <!-- Ảnh sẽ thêm sau -->
                        </div>
                        <div class="step-content">
                            <h3>Đăng ký</h3>
                            <p>Ấn vào nút đăng ký phía dưới trang web này để bắt đầu quá trình đăng ký tài khoản trạm CORS miễn phí 3 tháng</p>
                        </div>
                    </div>
                    <div class="step-card-large" data-aos="fade-left" data-aos-delay="200">
                        <div class="step-number">2</div>
                        <div class="step-image-large">
                            <img src="/public/assets/img/dang-ky-thong-tin.png" alt="Xác thực email" class="step-image">
                            <!-- Ảnh sẽ thêm sau -->
                        </div>
                        <div class="step-content">
                            <h3>Xác thực</h3>
                            <p>Thực hiện đăng ký tài khoản với thông tin của bạn và xác thực email thông qua đường dẫn được gửi tới hòm thư</p>
                        </div>
                    </div>
                </div>
                <div class="step-row">
                    <div class="step-card-large" data-aos="fade-right" data-aos-delay="300">
                        <div class="step-number">3</div>                        <div class="step-image-large">
                            <img src="/public/assets/img/dang-ky-goi-3-thang.png" alt="Chọn gói 3 tháng" class="step-image">
                            <!-- Hình ảnh tạm thời - cần thay thế bằng ảnh thực tế -->
                        </div>
                        <div class="step-content">
                            <h3>Mua tài khoản</h3>
                            <p>Chọn gói dịch vụ 3 tháng. Chọn 1 tài khoản và tỉnh Thái Nguyên rồi ấn Mua. Mã voucher giảm giá sẽ tự động áp dụng. Bỏ qua gửi ảnh minh chứng, sau đó bấm trở về và chờ duyệt tài khoản</p>
                        </div>
                    </div>
                    <div class="step-card-large" data-aos="fade-left" data-aos-delay="400">
                        <div class="step-number">4</div>
                        <div class="step-image-large">
                            <img src="/public/assets/img/thong-tin-tai-khoan.png" alt="Nhận tài khoản" class="step-image">
                        </div>
                        <div class="step-content">
                            <h3>Nhận tài khoản</h3>
                            <p>Sau khi nhận thông tin của bạn, chúng tôi sẽ xác nhận và tạo tài khoản đo đạc. Thông tin tài khoản xem tại mục Quản lý tài khoản</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
      <!-- Phần 5: Call to action -->
    <section class="section register-section" id="register-section">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <h2>Đăng ký ngay hôm nay</h2>
                <p>Cơ hội miễn phí có giới hạn - Nhanh tay đăng ký!</p>
            </div>
            <div class="register-grid">
                <div class="register-cta" data-aos="fade-right">
                    <h3>Nhận tài khoản miễn phí 3 tháng ngay!</h3>
                    <p>Trải nghiệm toàn bộ tính năng với độ chính xác tuyệt vời trong công tác đo đạc. Đừng bỏ lỡ cơ hội tiếp cận công nghệ đo đạc hiện đại nhất hiện nay mà không mất bất kỳ chi phí nào.</p>
                    <ul class="register-benefits">
                        <li><i class="fas fa-check-circle"></i> Không cần thẻ tín dụng</li>
                        <li><i class="fas fa-check-circle"></i> Hỗ trợ kỹ thuật đầy đủ</li>
                        <li><i class="fas fa-check-circle"></i> Không có ràng buộc</li>
                       
                    </ul>
                    <a href="<?php echo $register_url; ?>" class="cta-button pulse-animation">ĐĂNG KÝ NGAY</a>
                </div>
                <div class="register-qr" data-aos="fade-left">
                    <h3>Hoặc quét mã QR</h3>
                    <p class="qr-instruction">Sử dụng camera điện thoại để quét mã QR và đăng ký ngay trên thiết bị di động của bạn</p>
                    <div class="qr-container">
                        <div id="qrcode"></div>
                    </div>
                <div class="register-timer">
                <div class="countdown-label"></div>
                <div class="countdown-value"></div>
                </div>
                    
                </div>
            </div>
        </div>
    </section>
      <!-- Phần 6: Đánh giá của khách hàng -->
    <section class="section testimonials-section" id="testimonials">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <h2>Đánh giá của khách hàng</h2>
            </div>
            <div class="testimonials-grid">
                <div class="testimonial-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="testimonial-avatar">
                        <!-- Avatar placeholder -->
                    </div>
                    <div class="testimonial-content">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-text">"Từ khi dùng hệ thống này, tôi đã đo đạc được cả ngày liên tục, thoát khỏi cảnh cứ phải căn giờ đo vào sáng sớm như trước kia"</p>
                        <div class="testimonial-info">
                            <h4>Nguyễn Anh Đức</h4>
                            <span>Đồng Hỷ</span>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="testimonial-avatar">
                        <!-- Avatar placeholder -->
                    </div>
                    <div class="testimonial-content">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-text">"Rất ổn định và rộng khắp, anh em kỹ thuật cũng hỗ trợ nhiệt tình nên tôi nhanh chóng làm quen và giờ đã thành thục sử dụng hệ thống mới. Tôi chắc chắn sẽ sử dụng lâu dài với hệ thống này"</p>
                        <div class="testimonial-info">
                            <h4>Hoàng Văn Khải</h4>
                            <span>Sông Công</span>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="testimonial-avatar">
                        <!-- Avatar placeholder -->
                    </div>
                    <div class="testimonial-content">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                        </div>
                        <p class="testimonial-text">"Tôi được người anh trong giới chia sẻ về hệ thống này, mới sử dụng được 1 tháng nay nhưng dịch vụ ổn định, vùng phủ rộng. Tôi đã dừng sử dụng trạm Radio từ khi sử dụng hệ thống này"</p>
                        <div class="testimonial-info">
                            <h4>Lê Trung Hải</h4>
                            <span>Định Hóa</span>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </section>
    
    <!-- Phần 7: Footer -->
    <footer class="footer">
        <div class="container">            <div class="sharing-section">
                <p>Chia sẻ cơ hội này với bạn bè:</p>
                <div class="social-buttons">
                    <a href="javascript:void(0)" onclick="shareOnFacebook()" class="social-button"><i class="fab fa-facebook-f"></i></a>
                    <a href="javascript:void(0)" onclick="shareOnInstagram()" class="social-button"><i class="fab fa-instagram"></i></a>
                    <a href="javascript:void(0)" onclick="shareOnLinkedIn()" class="social-button"><i class="fab fa-linkedin-in"></i></a>
                    <a href="javascript:void(0)" onclick="shareViaEmail()" class="social-button"><i class="fas fa-envelope"></i></a>
                </div>
            </div>
            <div class="scroll-top">
                <a href="#" class="scroll-top-button"><i class="fas fa-chevron-up"></i></a>
            </div>
            <div class="copyright">
                <p>© <?php echo date('Y'); ?> - Tất cả quyền được bảo lưu</p>
            </div>
        </div>
    </footer>

    <!-- QR Code library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <!-- AOS Animation library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Pass the register URL to JavaScript for QR code generation
        window.registerUrl = "<?php echo $register_url; ?>";
    </script>
    <script src="assets/js/landing.js"></script>
</body>
</html>
