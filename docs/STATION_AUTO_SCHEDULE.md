# Hướng Dẫn Cài Đặt Tự Động Start/Stop Trạm NTRIP

## Mô tả
Hệ thống tự động start/stop các trạm NTRIP theo lịch:
- **6:00 sáng**: Tự động start tất cả các trạm
- **20:00 tối (8:00 PM)**: Tự động stop tất cả các trạm

## Cấu trúc file

### 1. Trang quản lý trạm
**File**: `public/pages/station_management.php`
- Hiển thị danh sách tất cả các trạm
- Chọn đơn lẻ hoặc hàng loạt
- Nút Start/Stop cho từng trạm
- Nút Start/Stop hàng loạt
- Thông tin lịch tự động

### 2. Handler xử lý API
**File**: `public/handlers/station_control.php`
- Xử lý các request start/stop từ giao diện
- Gọi API RTK với signature đúng chuẩn
- Trả về kết quả JSON

### 3. Script tự động
**File**: `scripts/station_auto_schedule.php`
- Script chạy tự động theo cron job
- Ghi log vào `private/logs/station_schedule.log`
- Có thể chạy thủ công để test

## Cài đặt Tự động (Windows)

### Sử dụng Task Scheduler

1. **Mở Task Scheduler**
   - Nhấn `Win + R`, gõ `taskschd.msc`, Enter

2. **Tạo Task cho Start (6:00 sáng)**
   - Click "Create Basic Task"
   - Name: `RTK Station Auto Start`
   - Description: `Tự động start các trạm NTRIP lúc 6:00 sáng`
   - Trigger: Daily
   - Start time: `06:00:00`
   - Action: Start a program
   - Program/script: `C:\laragon\bin\php\php-8.x.x\php.exe` (điều chỉnh đường dẫn PHP của bạn)
   - Add arguments: `"C:\laragon\www\02062025_user_web\scripts\station_auto_schedule.php" start`
   - Click Finish

3. **Tạo Task cho Stop (20:00 tối)**
   - Làm tương tự như trên
   - Name: `RTK Station Auto Stop`
   - Description: `Tự động stop các trạm NTRIP lúc 20:00 tối`
   - Start time: `20:00:00`
   - Add arguments: `"C:\laragon\www\02062025_user_web\scripts\station_auto_schedule.php" stop`

4. **Cấu hình nâng cao (Optional)**
   - Right-click vào task vừa tạo → Properties
   - Tab "General":
     - ✅ Run whether user is logged on or not
     - ✅ Run with highest privileges
   - Tab "Conditions":
     - ❌ Start the task only if the computer is on AC power (bỏ tick nếu muốn chạy kể cả khi dùng pin)
   - Tab "Settings":
     - ✅ Allow task to be run on demand
     - ✅ Run task as soon as possible after a scheduled start is missed

## Cài đặt Tự động (Linux/Mac)

### Sử dụng Crontab

1. **Mở crontab**
```bash
crontab -e
```

2. **Thêm các dòng sau**
```bash
# Start stations at 6:00 AM every day
0 6 * * * /usr/bin/php /path/to/02062025_user_web/scripts/station_auto_schedule.php start

# Stop stations at 8:00 PM every day
0 20 * * * /usr/bin/php /path/to/02062025_user_web/scripts/station_auto_schedule.php stop
```

3. **Lưu và thoát** (`:wq` trong vim)

## Test Script thủ công

### Windows (PowerShell hoặc CMD)
```powershell
# Test start
php C:\laragon\www\02062025_user_web\scripts\station_auto_schedule.php start

# Test stop
php C:\laragon\www\02062025_user_web\scripts\station_auto_schedule.php stop
```

### Linux/Mac (Terminal)
```bash
# Test start
php /path/to/02062025_user_web/scripts/station_auto_schedule.php start

# Test stop
php /path/to/02062025_user_web/scripts/station_auto_schedule.php stop
```

## Xem Log

Log file được lưu tại: `private/logs/station_schedule.log`

```bash
# Windows
type private\logs\station_schedule.log

# Linux/Mac
cat private/logs/station_schedule.log
tail -f private/logs/station_schedule.log  # Xem real-time
```

## Truy cập Trang quản lý

URL: `https://your-domain.com/pages/station_management.php`

Yêu cầu:
- ✅ Đã đăng nhập
- ✅ Có quyền admin

## API Endpoints

Script sử dụng các API endpoints sau:

### Start Stations
```
POST /stream/stations/batch-start
Content-Type: application/json

{
  "station_ids": [1, 2, 3, ...]
}
```

### Stop Stations
```
POST /stream/stations/batch-stop
Content-Type: application/json

{
  "station_ids": [1, 2, 3, ...]
}
```

## Xử lý sự cố

### 1. Script không chạy
- Kiểm tra đường dẫn PHP có đúng không
- Kiểm tra quyền thực thi file
- Xem log file để biết lỗi

### 2. API trả về lỗi
- Kiểm tra cấu hình API trong `config.php`:
  - `RTK_API_URL`
  - `RTK_API_ACCESS_KEY`
  - `RTK_API_SECRET_KEY`
  - `RTK_API_SIGN_METHOD`
- Kiểm tra API endpoint có đúng không
- Xem response trong log file

### 3. Task Scheduler không chạy
- Kiểm tra task có enabled không
- Kiểm tra user account có quyền chạy task không
- Xem History tab trong Task Scheduler để biết lỗi

### 4. Crontab không chạy
```bash
# Kiểm tra cron service có chạy không
sudo service cron status

# Restart cron service
sudo service cron restart

# Xem log của cron
grep CRON /var/log/syslog
```

## Tùy chỉnh giờ chạy

Muốn thay đổi giờ start/stop:

### Windows Task Scheduler
- Right-click task → Properties → Triggers → Edit
- Thay đổi Start time

### Linux/Mac Crontab
Cú pháp: `minute hour day month weekday command`
- Ví dụ: `30 5 * * *` = 5:30 sáng mỗi ngày
- Ví dụ: `0 22 * * *` = 10:00 tối mỗi ngày

## Ghi chú bảo mật

- Script chỉ có thể chạy bởi admin user
- API keys được lưu trong config.php (không commit lên git)
- Log file chứa thông tin nhạy cảm, cần bảo vệ

## Tác giả
Được tạo bởi GitHub Copilot
Ngày: 2025-11-06
