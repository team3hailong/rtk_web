# Tài liệu: Hệ thống Referral (Giới thiệu người dùng)

## Thay đổi chính:

1. Cập nhật bảng `referral_commission` để thêm trạng thái 'approved'
   - Cập nhật ENUM trong cấu trúc bảng từ `('pending', 'paid', 'cancelled')` thành `('pending', 'approved', 'paid', 'cancelled')`
   - Tạo migration file để cập nhật cơ sở dữ liệu

2. Tự động duyệt hoa hồng khi giao dịch hoàn tất và thanh toán được xác nhận
   - Sửa đổi phương thức `calculateCommission` để tự động đặt status='approved' khi thêm bản ghi mới
   - Cập nhật logic kiểm tra để chỉ tính hoa hồng khi cả `status='completed'` và `payment_confirmed=1`

3. Cập nhật phương thức `getCommissionTransactions` để trả về trạng thái chính xác
   - Thay đổi status mặc định thành 'approved' khi giao dịch thành công và thanh toán được xác nhận
   - Đổi status từ 'waiting' thành 'pending' cho giao dịch đang xử lý

4. Cập nhật phương thức `getTotalCommissionEarned` để chỉ tính hoa hồng đã duyệt
   - Chỉ tính tổng hoa hồng có status IN ('approved', 'paid')

5. Cập nhật giao diện người dùng để hiển thị trạng thái mới
   - Thêm badge và thông tin giải thích trong tab "Hoa hồng nhận được"
   - Thay đổi hiển thị trạng thái từ "Đã nhận" thành "Đã duyệt"
   - Thêm thông tin về hệ thống duyệt tự động trong tab "Yêu cầu rút tiền"

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
3. Tính toán hoa hồng dựa trên giá trị giao dịch (5%)
4. Tự động đặt `status='approved'` khi thêm bản ghi mới vào bảng `referral_commission`
5. Số dư khả dụng của người giới thiệu được cập nhật tự động
