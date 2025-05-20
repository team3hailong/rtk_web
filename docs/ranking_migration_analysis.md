# Tài liệu: Phân tích chi tiết về migration bảng xếp hạng

## Tổng quan migration

Migration `Nguyen_20052025_add_ranking_table.sql` thực hiện tạo và cấu hình hệ thống xếp hạng người dùng (user ranking) để theo dõi và đánh giá hiệu suất của những người giới thiệu (referrers) trong hệ thống. Migration này được thiết kế để tự động tính toán và cập nhật xếp hạng người dùng dựa trên số lượng người được giới thiệu và hoa hồng đã kiếm được.

**Ngày triển khai:** 20-05-2025

## 1. Cấu trúc bảng dữ liệu

Bảng `user_ranking` được tạo với cấu trúc đơn giản hóa, không lưu trữ cột rank trực tiếp:

```sql
CREATE TABLE IF NOT EXISTS `user_ranking` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `referral_count` INT NOT NULL DEFAULT 0,
  `monthly_commission` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_commission` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_ranking` (`user_id`),
  INDEX `idx_total_commission` (`total_commission`),
  INDEX `idx_monthly_commission` (`monthly_commission`),
  CONSTRAINT `fk_ranking_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
)
```

### Các trường trong bảng:

| Trường | Kiểu dữ liệu | Mô tả |
|--------|--------------|-------|
| `id` | INT | Khóa chính, tự động tăng |
| `user_id` | INT | ID của người dùng, tham chiếu đến bảng `user` |
| `referral_count` | INT | Số lượng người được giới thiệu |
| `monthly_commission` | DECIMAL(15,2) | Tổng hoa hồng trong tháng hiện tại |
| `total_commission` | DECIMAL(15,2) | Tổng hoa hồng tích lũy từ trước đến nay |
| `created_at` | TIMESTAMP | Thời điểm bản ghi được tạo |
| `updated_at` | TIMESTAMP | Thời điểm bản ghi được cập nhật gần nhất |

### Các indexes và constraints:

- `PRIMARY KEY (id)`: Khóa chính trên trường id
- `UNIQUE KEY unique_user_ranking (user_id)`: Đảm bảo mỗi user chỉ có một bản ghi xếp hạng
- `INDEX idx_total_commission (total_commission)`: Index trên tổng hoa hồng để tối ưu truy vấn sắp xếp 
- `INDEX idx_monthly_commission (monthly_commission)`: Index trên hoa hồng tháng để tối ưu truy vấn sắp xếp
- `FOREIGN KEY (user_id) REFERENCES user(id)`: Ràng buộc tham chiếu đến bảng user, cascade khi update hoặc delete

## 2. Stored Procedure

Migration tạo stored procedure `update_user_rankings` để tính toán và cập nhật thông tin xếp hạng cho tất cả người dùng:

```sql
CREATE PROCEDURE update_user_rankings()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE user_id_var INT;
    DECLARE ref_count INT;
    DECLARE month_commission DECIMAL(15,2);
    DECLARE total_commission DECIMAL(15,2);
    
    -- Cursor for selecting users with their totals
    DECLARE user_cursor CURSOR FOR
        SELECT 
            u.id AS user_id,
            COUNT(DISTINCT ru.referred_user_id) AS referral_count,
            IFNULL(SUM(CASE 
                WHEN rc.status IN ('approved', 'paid') 
                AND MONTH(rc.created_at) = MONTH(CURRENT_DATE()) 
                AND YEAR(rc.created_at) = YEAR(CURRENT_DATE()) 
                THEN rc.commission_amount ELSE 0 END), 0) AS monthly_commission,
            IFNULL(SUM(CASE 
                WHEN rc.status IN ('approved', 'paid') 
                THEN rc.commission_amount ELSE 0 END), 0) AS total_commission
        FROM 
            user u
            LEFT JOIN referral r ON u.id = r.user_id
            LEFT JOIN referred_user ru ON r.user_id = ru.referrer_id
            LEFT JOIN referral_commission rc ON r.user_id = rc.referrer_id
        GROUP BY 
            u.id;
            
    -- Transaction processing and update logic
    -- ...
END
```

### Chi tiết cơ chế hoạt động:

1. **Truy vấn dữ liệu**:
   - SELECT tất cả user từ bảng `user`
   - JOIN với các bảng liên quan để lấy thông tin giới thiệu và hoa hồng
   - Tính toán:
     - Số lượng người được giới thiệu (COUNT DISTINCT)
     - Hoa hồng tháng hiện tại (status = 'approved' hoặc 'paid' + cùng tháng/năm với ngày hiện tại)
     - Tổng hoa hồng tích lũy (status = 'approved' hoặc 'paid')

2. **Cập nhật dữ liệu**:
   - Sử dụng cursor để duyệt qua từng user
   - Với mỗi user:
     - INSERT INTO... ON DUPLICATE KEY UPDATE để tạo mới hoặc cập nhật bản ghi xếp hạng
     - Cập nhật các trường: referral_count, monthly_commission, total_commission, updated_at

3. **Xử lý transaction**:
   - Toàn bộ quá trình được bao bọc trong transaction để đảm bảo tính nhất quán
   - Nếu có lỗi, transaction sẽ rollback

## 3. Các Events tự động

### Event reset hoa hồng tháng:

```sql
CREATE EVENT IF NOT EXISTS reset_monthly_commission
ON SCHEDULE EVERY 1 MONTH
STARTS TIMESTAMP(CONCAT(DATE_FORMAT(LAST_DAY(NOW() + INTERVAL 1 MONTH), '%Y-%m-01'), ' 00:00:01'))
DO
BEGIN
    UPDATE user_ranking SET monthly_commission = 0.00, updated_at = NOW();
    CALL update_user_rankings();
END
```

- **Chức năng**: Reset trường monthly_commission về 0 vào đầu mỗi tháng
- **Lịch chạy**: Bắt đầu từ ngày 01 của tháng tiếp theo, lặp lại mỗi tháng
- **Thời gian**: 00:00:01 (sau nửa đêm) của ngày đầu tháng

### Event cập nhật xếp hạng theo giờ:

```sql
CREATE EVENT IF NOT EXISTS hourly_ranking_update
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    CALL update_user_rankings();
END
```

- **Chức năng**: Đảm bảo xếp hạng luôn được cập nhật, ngay cả khi trigger không hoạt động
- **Lịch chạy**: Chạy mỗi giờ
- **Thời gian bắt đầu**: Ngay sau khi migration được thực thi

## 4. Các Triggers

Migration tạo 3 triggers để tự động cập nhật xếp hạng khi có thay đổi:

### Trigger khi thêm hoa hồng mới:

```sql
CREATE TRIGGER trg_update_ranking_after_commission_change
AFTER INSERT ON referral_commission
FOR EACH ROW
BEGIN
    CALL update_user_rankings();
END
```

- **Kích hoạt**: Sau khi có bản ghi mới được thêm vào bảng `referral_commission`
- **Hành động**: Gọi stored procedure `update_user_rankings` để cập nhật xếp hạng

### Trigger khi cập nhật hoa hồng:

```sql
CREATE TRIGGER trg_update_ranking_after_commission_update
AFTER UPDATE ON referral_commission
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status OR OLD.commission_amount != NEW.commission_amount THEN
        CALL update_user_rankings();
    END IF;
END
```

- **Kích hoạt**: Sau khi cập nhật bản ghi trong bảng `referral_commission`
- **Điều kiện**: Chỉ cập nhật nếu status hoặc commission_amount thay đổi
- **Hành động**: Gọi stored procedure `update_user_rankings` để cập nhật xếp hạng

### Trigger khi thêm người dùng được giới thiệu:

```sql
CREATE TRIGGER trg_update_ranking_after_referral_add
AFTER INSERT ON referred_user
FOR EACH ROW
BEGIN
    CALL update_user_rankings();
END
```

- **Kích hoạt**: Sau khi có bản ghi mới được thêm vào bảng `referred_user`
- **Hành động**: Gọi stored procedure `update_user_rankings` để cập nhật xếp hạng

## 5. Ưu điểm thiết kế

1. **Tự động hóa cao**:
   - Hệ thống tự động cập nhật xếp hạng khi có sự thay đổi liên quan
   - Không cần tác động từ ứng dụng, mọi logic được xử lý ở tầng database

2. **Hiệu suất tối ưu**:
   - Sử dụng indexes để tối ưu truy vấn
   - Chỉ cập nhật khi có thay đổi ảnh hưởng đến xếp hạng
   - Bao bọc trong transaction để đảm bảo tính nhất quán

3. **Tính nhất quán cao**:
   - Luôn có backup cập nhật bằng event chạy theo giờ
   - Cơ chế reset tháng tự động và đáng tin cậy

4. **Thiết kế linh hoạt**:
   - Không lưu trữ rank trực tiếp, cho phép tính toán xếp hạng động
   - Dễ dàng mở rộng thêm các tiêu chí xếp hạng khác

## 6. Lưu ý khi vận hành

1. **Chi phí tài nguyên**:
   - Stored procedure `update_user_rankings` có thể tốn nhiều tài nguyên khi số lượng user lớn
   - Nên theo dõi hiệu suất và cân nhắc tối ưu nếu số lượng user vượt quá 100,000

2. **Khả năng mở rộng**:
   - Với số lượng user rất lớn, có thể cần thay đổi cách tiếp cận, ví dụ:
     - Chỉ cập nhật cho người dùng bị ảnh hưởng thay vì tất cả
     - Batch processing cho update
     - Cân nhắc denormalizing data để giảm joins

3. **Giám sát**:
   - Theo dõi thời gian chạy của stored procedure
   - Kiểm tra định kỳ để đảm bảo events đang hoạt động
   - Xem xét log MySQL để phát hiện vấn đề tiềm ẩn

4. **Sao lưu và khôi phục**:
   - Đảm bảo bảng `user_ranking` được bao gồm trong chiến lược sao lưu
   - Trong trường hợp mất dữ liệu, có thể khôi phục bằng cách gọi `CALL update_user_rankings()`

## 7. Kế hoạch nâng cấp trong tương lai

1. **Thêm bảng lịch sử xếp hạng**:
   - Lưu trữ lịch sử xếp hạng theo tháng
   - Cho phép phân tích xu hướng và báo cáo

2. **Tối ưu hiệu suất cho quy mô lớn**:
   - Triển khai cập nhật theo batch thay vì toàn bộ users
   - Cân nhắc sử dụng materialized views cho báo cáo

3. **Mở rộng tiêu chí xếp hạng**:
   - Thêm trọng số cho các loại giao dịch khác nhau
   - Tích hợp với hệ thống khuyến mãi/ưu đãi dựa trên xếp hạng

## 8. Tổng kết

Migration `Nguyen_20052025_add_ranking_table.sql` cung cấp một hệ thống xếp hạng người dùng hoàn chỉnh, tự động và hiệu quả. Thiết kế tập trung vào tính tự động hóa cao và đảm bảo dữ liệu luôn được cập nhật. Hệ thống được tích hợp chặt chẽ với các chức năng giới thiệu và hoa hồng hiện có, giúp cung cấp động lực và công cụ minh bạch cho người dùng tham gia chương trình giới thiệu.
