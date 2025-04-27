<?php
// RTK API credentials
const RTK_API_URL = 'http://203.171.25.138:8090/openapi/broadcast/users';
const RTK_API_ACCESS_KEY = 'Zb5F6iKUuAISy4qY';
const RTK_API_SECRET_KEY = 'KL1KEEJj2s6HA8LB';
const RTK_API_SIGN_METHOD = 'HmacSHA256';

// Email Configuration 
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'dovannguyen2005bv@gmail.com'); // Thay bằng Gmail của bạn
define('SMTP_PASSWORD', 'qbut ryan pedr aawk'); // Thay bằng App Password từ Google
define('SMTP_FROM_EMAIL', 'dovannguyen2005bv@gmail.com'); // Cùng email với SMTP_USERNAME
define('SMTP_FROM_NAME', 'SMTP Mail');

// Site Configuration
define('SITE_URL', 'http://localhost:8080'); // thay đổi theo domain thực tế của bạn