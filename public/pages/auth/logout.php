<?php
// Thay vì truy cập trực tiếp file xử lý trong thư mục private, 
// chuyển hướng người dùng đến cầu nối trung gian
header("Location: /public/handlers/action_handler.php?module=auth&action=process_logout");
exit;
?>
