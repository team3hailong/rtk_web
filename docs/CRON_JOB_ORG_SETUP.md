# Hướng Dẫn Cấu Hình Cron Job trên cron-job.org

## 🔗 Endpoint API

File endpoint: `public/api/cron/station-schedule.php`

### URL Format:
```
https://your-domain.com/api/cron/station-schedule.php?action={action}&key={secret_key}
```

### Parameters:
- **action**: `start` hoặc `stop`
- **key**: Secret key để xác thực (mặc định: `taikhoandodac_cron_2025`)

### Examples:
```
# Start all stations
https://your-domain.com/api/cron/station-schedule.php?action=start&key=taikhoandodac_cron_2025

# Stop all stations
https://your-domain.com/api/cron/station-schedule.php?action=stop&key=taikhoandodac_cron_2025
```

## 📋 Cấu hình trên cron-job.org

### Bước 1: Đăng ký tài khoản
1. Truy cập: https://console.cron-job.org/
2. Đăng ký tài khoản miễn phí

### Bước 2: Tạo Cron Job cho Start (6:00 sáng)

1. Click **"Create cronjob"**
2. Điền thông tin:

   **Title:**
   ```
   RTK Station Auto Start - 6:00 AM
   ```

   **URL:**
   ```
   https://your-domain.com/api/cron/station-schedule.php?action=start&key=taikhoandodac_cron_2025
   ```
   ⚠️ **Thay `your-domain.com` bằng domain thực của bạn**

   **Schedule:**
   - Chọn **"Every day"**
   - Time: `06:00` (6:00 AM)
   - Timezone: `Asia/Ho_Chi_Minh` hoặc `UTC+07:00`

   **Request method:** `GET`

   **Notification:**
   - ✅ Enable: "On failure only" (tùy chọn)
   - Email: your-email@example.com

3. Click **"Create cronjob"**

### Bước 3: Tạo Cron Job cho Stop (20:00 tối)

1. Click **"Create cronjob"** lần nữa
2. Điền thông tin:

   **Title:**
   ```
   RTK Station Auto Stop - 8:00 PM
   ```

   **URL:**
   ```
   https://your-domain.com/api/cron/station-schedule.php?action=stop&key=taikhoandodac_cron_2025
   ```
   ⚠️ **Thay `your-domain.com` bằng domain thực của bạn**

   **Schedule:**
   - Chọn **"Every day"**
   - Time: `20:00` (8:00 PM)
   - Timezone: `Asia/Ho_Chi_Minh` hoặc `UTC+07:00`

   **Request method:** `GET`

   **Notification:**
   - ✅ Enable: "On failure only" (tùy chọn)
   - Email: your-email@example.com

3. Click **"Create cronjob"**

## 🔐 Bảo mật

### Thay đổi Secret Key

Mở file: `public/api/cron/station-schedule.php`

Tìm dòng:
```php
define('CRON_SECRET_KEY', 'taikhoandodac_cron_2025');
```

Thay đổi thành key phức tạp hơn:
```php
define('CRON_SECRET_KEY', 'your_very_secret_random_string_here_123456');
```

Sau đó cập nhật lại URL trong cron-job.org với key mới.

### Khuyến nghị bảo mật:
- ✅ Dùng key dài và phức tạp
- ✅ Không chia sẻ key với ai
- ✅ Thay đổi key định kỳ
- ✅ Chỉ cho phép HTTPS (nếu có SSL)

## 🧪 Test Thủ Công

Trước khi cấu hình cron, test endpoint bằng browser:

### Test Start:
```
https://your-domain.com/api/cron/station-schedule.php?action=start&key=taikhoandodac_cron_2025
```

**Response thành công:**
```json
{
  "success": true,
  "message": "Start completed successfully",
  "stations_count": 50,
  "data": { ... }
}
```

### Test Stop:
```
https://your-domain.com/api/cron/station-schedule.php?action=stop&key=taikhoandodac_cron_2025
```

**Response thành công:**
```json
{
  "success": true,
  "message": "Stop completed successfully",
  "stations_count": 50,
  "data": { ... }
}
```

### Response lỗi xác thực:
```json
{
  "success": false,
  "message": "Invalid authentication key"
}
```

## 📊 Giám sát

### Xem Log
Log được lưu tại: `private/logs/station_schedule.log`

```bash
# Windows
type private\logs\station_schedule.log

# Linux/Mac
cat private/logs/station_schedule.log
tail -f private/logs/station_schedule.log  # Real-time
```

### Kiểm tra trên cron-job.org
1. Truy cập dashboard: https://console.cron-job.org/jobs
2. Xem **"Execution history"** của mỗi job
3. Kiểm tra status code (200 = thành công)
4. Xem response body

## ⚙️ Cấu hình nâng cao

### Retry on failure
Trong cron-job.org settings:
- **Retries:** 2-3 lần
- **Retry interval:** 5-10 phút

### Timeout
- **Execution timeout:** 30 giây
- Đủ cho API call, không quá lâu

### Multiple schedules (tuỳ chọn)
Nếu cần chạy nhiều lần trong ngày:

**Morning shifts:**
- 06:00 - Start
- 08:00 - Start (backup)

**Evening shifts:**
- 18:00 - Stop (early)
- 20:00 - Stop (main)
- 22:00 - Stop (backup)

## 🚨 Xử lý sự cố

### 1. Cron không chạy
- ✅ Kiểm tra timezone đã đúng chưa
- ✅ Kiểm tra URL có accessible từ internet không
- ✅ Kiểm tra secret key có đúng không

### 2. Response 403 Forbidden
- Sai secret key
- Hosting block external requests

### 3. Response 500 Error
- Lỗi server/database
- Kiểm tra log file: `private/logs/station_schedule.log`
- Kiểm tra error log: `private/logs/error.log`

### 4. Signature không khớp
- Kiểm tra config API keys trong `config.php`
- Test thủ công endpoint trước

### 5. Không có stations
- Kiểm tra database có dữ liệu không
- Kiểm tra kết nối database

## 📝 So sánh với Cron truyền thống

| Feature | cron-job.org | Server Cron |
|---------|--------------|-------------|
| Dễ cấu hình | ✅ Rất dễ | ⚠️ Cần access SSH |
| Giám sát | ✅ Dashboard web | ❌ Phải check log |
| Thông báo | ✅ Email tự động | ⚠️ Phải cấu hình |
| Hosting shared | ✅ Hoạt động tốt | ❌ Thường bị giới hạn |
| Miễn phí | ✅ Free plan đủ dùng | ✅ Miễn phí |
| Reliability | ✅ 99%+ uptime | ⚠️ Tùy hosting |

## 🎯 Ưu điểm của cron-job.org

✅ **Không cần SSH access**
✅ **Dashboard trực quan**
✅ **Email notification tự động**
✅ **Execution history chi tiết**
✅ **Hoạt động tốt trên shared hosting**
✅ **Dễ debug và monitor**
✅ **Miễn phí**

## 📞 Support

Nếu có vấn đề:
1. Kiểm tra log: `private/logs/station_schedule.log`
2. Test endpoint thủ công qua browser
3. Xem execution history trên cron-job.org
4. Kiểm tra email notification (nếu bật)

---

**Lưu ý:** Sau khi deploy lên hosting thực, nhớ cập nhật URL trong cron-job.org!
