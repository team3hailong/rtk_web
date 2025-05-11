# Tài liệu: Cải tiến Hệ thống Referral (Giới thiệu người dùng)

## Thay đổi chính:

1. **Loại bỏ cron job định kỳ**
   - Không cần chạy `update_commissions.php` theo lịch nữa
   - Toàn bộ logic đã được tích hợp vào quá trình cập nhật giao dịch
   - Giảm việc xử lý dư thừa và tối ưu hóa hiệu suất

2. **Cải tiến logic cập nhật hoa hồng**
   - Khi transaction được cập nhật status='completed' và payment_confirmed=1, hệ thống tự động:
     - Kiểm tra xem đã có bản ghi hoa hồng chưa
     - Nếu chưa, tạo mới với status='approved'
     - Nếu có, cập nhật status='approved' nếu cần

3. **Thêm MySQL trigger cho an toàn dữ liệu**
   - Trigger `trg_auto_approve_commission` tự động cập nhật hoa hồng khi transaction thay đổi
   - Đảm bảo không bỏ sót giao dịch nào cần tính hoa hồng
   - Hoạt động như cơ chế backup nếu code PHP không chạy đúng

4. **Tối ưu hóa cơ sở dữ liệu**
   - Thêm index cho trường transaction_id để truy vấn nhanh hơn
   - Thêm cột updated_at để theo dõi thời gian cập nhật
   - Đảm bảo cấu trúc bảng hỗ trợ trạng thái 'approved'

## Cách hệ thống hoạt động (đã tối ưu):

1. Người dùng đăng ký qua liên kết giới thiệu
2. Khi người dùng được giới thiệu thanh toán:
   - Hệ thống cập nhật giao dịch sang `status='completed'` và `payment_confirmed=1`
   - Hệ thống **tự động** tính và duyệt hoa hồng (không cần cron job)
   - Hoa hồng được thêm vào bảng `referral_commission` với `status='approved'`
   - Số dư khả dụng của người giới thiệu được cập nhật ngay lập tức
3. Người dùng có thể yêu cầu rút tiền khi số dư khả dụng đủ lớn

## Trạng thái hoa hồng (không thay đổi):
- **Đã duyệt (approved)**: Hoa hồng đã được tự động duyệt và có thể rút
- **Đang xử lý (pending)**: Giao dịch đang xử lý, hoa hồng chưa được tính
- **Đã thanh toán (paid)**: Hoa hồng đã được thanh toán vào tài khoản người dùng
- **Đã hủy (cancelled)**: Hoa hồng đã bị hủy (ví dụ: giao dịch bị hoàn tiền)

## Kiểm tra và khắc phục sự cố:

Nếu nghi ngờ có vấn đề với hoa hồng, chạy script kiểm tra:

```
php /path/to/rtk_web/private/action/referral/optimize_referral_system.php
```

Script này sẽ:
1. Tìm giao dịch đã hoàn thành nhưng chưa có hoa hồng và tạo mới
2. Tìm hoa hồng cần được cập nhật trạng thái thành 'approved'
3. Báo cáo số lượng bản ghi đã được cập nhật

## Lưu ý quan trọng:

- Cron job `update_commissions.php` không còn cần thiết và có thể vô hiệu hóa
- Hệ thống hiện tại tự động xử lý mọi tình huống khi giao dịch hoàn thành
- Trong trường hợp hiếm hoi code PHP không chạy đúng, MySQL trigger sẽ đảm bảo hoa hồng được tạo
