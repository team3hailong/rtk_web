<?php
$project_root_path = dirname(dirname(dirname(__DIR__)));
$base_url = '/public'; // Đơn giản hóa cho nội bộ
include $project_root_path . '/private/includes/header.php';
echo '<link rel="stylesheet" href="' . $base_url . '/public/assets/css/pages/settings/profile.css">';
?>
<style>
.contact-title, .profile-title {
    color: #28a745;
    text-align: center;
    margin-bottom: 32px;
    font-size: 2rem;
    font-weight: 700;
}
.form-section .support-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    margin-bottom: 0;
    padding-left: 0;
}
.form-section .support-info li {
    align-items: center;
    text-align: center;
    width: 100%;
    max-width: 350px;
}
.form-section h3 {
    text-align: center;
}
.support-info {
    list-style: none;
}
.support-info li {
    margin-bottom: 12px;
    font-size: 1rem;

}
.support-info a {
    color:rgb(9, 10, 9);
    text-decoration: none;
    font-weight: 500;
}
.support-info a:hover {
    text-decoration: underline;
    color:rgb(12, 12, 12);
}
.support-form .form-group label {
    color:rgb(17, 17, 17);
    font-weight: 500;
}
.support-form .form-control, .support-form select, .support-form textarea {
    border: 1px solid #b7e4c7;
    border-radius: 8px;
    padding: 10px;
    margin-top: 4px;
    width: 100%;
    box-sizing: border-box;
    font-size: 1rem;
    background: #f6fff8;
    color: #222;
}
.support-form .form-control:focus, .support-form select:focus, .support-form textarea:focus {
    border-color:rgb(10, 10, 10);
    outline: none;
    background: #e9fbe5;
}
.btn-primary {
    background: linear-gradient(90deg, #28a745 60%, #34c759 100%);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 12px 28px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-primary:hover {
    background: linear-gradient(90deg, #218838 60%, #43e97b 100%);
}
.message.success-message {
    background: #e9fbe5;
    color:rgb(17, 17, 17);
    border: 1px solid #b7e4c7;
    border-radius: 6px;
    padding: 10px 18px;
    margin-top: 1rem;
    text-align: center;
    font-weight: 500;
}
.dashboard-wrapper .content-wrapper > .container {
    max-width: 1200px;
    margin: 0 auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 16px rgba(40,167,69,0.07);
    padding: 2.5rem 2rem;
    margin-top: 50px;
    margin-bottom: 50px;
}
@media (max-width: 600px) {
    .dashboard-wrapper .content-wrapper > .container {
        padding: 1.2rem 0.5rem;
    }
}
</style>
<div class="dashboard-wrapper">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>
    <main class="content-wrapper">
        <div class="container">
            <h2 class="profile-title">Hỗ trợ</h2>
            <div class="form-section" style="margin-bottom: 2rem;">
                <h3>Thông tin liên hệ</h3>
                <ul class="support-info">
                    <li><strong>Email:</strong> <a href="nguyendo@gmail.com">nguyendo@gmail.com</a></li>
                    <li><strong>Hotline:</strong> <a href="tel:0981190564">0981190564</a></li>
                    <li><strong>Zalo:</strong> <a href="https://zalo.me/0981190564" target="_blank">0981190564</a></li>
                </ul>
            </div>
            <div class="form-section">
                <h3>Gửi yêu cầu hỗ trợ</h3>
                <form method="post" action="#" class="support-form" id="supportForm">
                    <div class="form-group">
                        <label for="support_type">Bạn cần hỗ trợ về vấn đề gì?</label>
                        <select id="support_type" name="support_type" class="form-control">
                            <option value="">-- Chọn loại hỗ trợ --</option>
                            <option value="account">Tài khoản</option>
                            <option value="transaction">Giao dịch/Thanh toán</option>
                            <option value="technical">Kỹ thuật/Hệ thống</option>
                            <option value="other">Khác</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="support_content">Nội dung cần hỗ trợ</label>
                        <textarea id="support_content" name="support_content" rows="5" class="form-control" placeholder="Vui lòng mô tả chi tiết vấn đề bạn gặp phải..."></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" id="submitBtn" class="btn btn-primary">Gửi yêu cầu hỗ trợ</button>
                    </div>
                </form>
                <div class="message success-message" id="popupSuccess" style="display:none;margin-top:1rem;">🎉 Gửi yêu cầu thành công!</div>
            </div>
        </div>
    </main>
</div>
<script>
document.getElementById('supportForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var submitBtn = document.getElementById('submitBtn');
    submitBtn.innerHTML = 'Đang gửi... ⏳';
    submitBtn.disabled = true;
    setTimeout(function() {
        submitBtn.innerHTML = 'Gửi yêu cầu hỗ trợ';
        submitBtn.disabled = false;
        document.getElementById('popupSuccess').style.display = 'block';
        setTimeout(function() {
            document.getElementById('popupSuccess').style.display = 'none';
            document.getElementById('supportForm').reset();
        }, 2000);
    }, 1500);
});
</script>
<?php
include $project_root_path . '/private/includes/footer.php';
?>
