# Tài liệu: Hệ thống Referral (Giới thiệu người dùng)

## Thay đổi chính:

1. Cập nhật bảng `referral_commission` để thêm trạng thái 'approved'
   - Cập nhật ENUM trong cấu trúc bảng từ `('pending', 'paid', 'cancelled')` thành `('pending', 'approved', 'paid', 'cancelled')`
   - Tạo migration file để cập nhật cơ sở dữ liệu

2. Tự động duyệt hoa hồng khi giao dịch hoàn tất và thanh toán được xác nhận
   - Sửa đổi phương thức `calculateCommission` để tự động đặt status='approved' khi thêm bản ghi mới
   - Cập nhật logic kiểm tra để chỉ tính hoa hồng khi cả `status='completed'` và `payment_confirmed=1`

3. Cập nhật phương thức `getCommissionTransactions` để trả về dữ liệu chính xác
   - Sử dụng COALESCE để ưu tiên giá trị từ bảng referral_commission nếu có
   - Sử dụng giá trị tính toán từ giao dịch chỉ khi không có bản ghi trong bảng referral_commission
   - Hiển thị chính xác số tiền hoa hồng được lưu trong database

4. Cập nhật phương thức `getTotalCommissionEarned` để chỉ tính hoa hồng đã duyệt
   - Chỉ tính tổng hoa hồng có status IN ('approved', 'paid')

5. Cập nhật giao diện người dùng để hiển thị trạng thái mới
   - Thêm badge và thông tin giải thích trong tab "Hoa hồng nhận được"
   - Thay đổi hiển thị trạng thái từ "Đã nhận" thành "Đã duyệt"
   - Thêm trạng thái "Đang duyệt" cho giao dịch thành công nhưng chưa xử lý hoa hồng
   - Thêm thông tin về hệ thống duyệt tự động trong tab "Yêu cầu rút tiền"

6. Thêm công cụ bảo trì và chạy định kỳ
   - Script `check_and_update_commissions.php` để kiểm tra và sửa chữa dữ liệu hoa hồng
   - Script `update_commissions.php` để chạy định kỳ, tự động tạo bản ghi hoa hồng cho giao dịch thành công

## Hoạt động của hệ thống:

1. Người dùng đăng ký qua liên kết giới thiệu
2. Khi người dùng được giới thiệu thanh toán:
   - Nếu giao dịch `status='completed'` và `payment_confirmed=1`
   - Hệ thống tự động tính hoa hồng 5% dựa trên giá trị giao dịch
   - Hoa hồng được thêm vào bảng `referral_commission` với trạng thái `status='approved'`
   - Hoa hồng được đưa vào số dư khả dụng của người giới thiệu
3. Người dùng có thể yêu cầu rút tiền khi số dư từ 100,000 VNĐ trở lên

## Trạng thái hoa hồng:
- **Đã duyệt (approved)**: Hoa hồng đã được tự động duyệt và có thể rút
- **Đang xử lý (pending)**: Giao dịch đang xử lý, hoa hồng chưa được tính
- **Đã thanh toán (paid)**: Hoa hồng đã được thanh toán vào tài khoản người dùng
- **Đã hủy (cancelled)**: Hoa hồng đã bị hủy (ví dụ: giao dịch bị hoàn tiền)

## Quy trình duyệt tự động:
1. Hệ thống nhận thông tin khi giao dịch được cập nhật trạng thái
2. Kiểm tra nếu giao dịch là `status='completed'` và `payment_confirmed=1`
3. Kiểm tra xem đã có bản ghi hoa hồng chưa:
   - Nếu chưa, tính toán hoa hồng mới dựa trên giá trị giao dịch (5%)
   - Nếu có, kiểm tra và cập nhật trạng thái thành `approved` nếu cần
4. Tự động đặt `status='approved'` cho tất cả bản ghi hoa hồng có giao dịch hoàn tất và đã xác nhận
5. Số dư khả dụng của người giới thiệu được cập nhật tự động
6. Cron job chạy định kỳ để quét và đảm bảo không bỏ sót giao dịch nào

## Cài đặt Cron Job:

Để đảm bảo rằng tất cả giao dịch hoàn tất đều được tính hoa hồng, hãy cài đặt cron job như sau:

```
# Chạy mỗi giờ để cập nhật hoa hồng giới thiệu
0 * * * * php /path/to/rtk_web/private/cron/update_commissions.php >> /path/to/rtk_web/private/logs/commission_cron.log 2>&1
```

## Khắc phục sự cố:

Khi gặp vấn đề với hiển thị hoặc tính toán hoa hồng, hãy sử dụng script bảo trì:

```
php /path/to/rtk_web/private/action/migration/check_and_update_commissions.php
```

Script này sẽ tự động:
1. Kiểm tra cấu trúc database
2. Tìm giao dịch hoàn tất nhưng chưa có bản ghi hoa hồng
3. Tạo bản ghi hoa hồng cho những giao dịch này với status='approved'
