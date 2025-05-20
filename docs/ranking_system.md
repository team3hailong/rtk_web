# Tài liệu: Hệ thống xếp hạng người giới thiệu (Ranking System)

## Tổng quan

Hệ thống xếp hạng người giới thiệu tự động cập nhật thứ hạng dựa trên tổng hoa hồng mà người dùng nhận được từ việc giới thiệu. Hệ thống cung cấp cơ chế xếp hạng minh bạch cho người dùng.

## Cấu trúc bảng dữ liệu

Bảng `user_ranking` có các trường sau:

1. `id` - Khóa chính, tự động tăng
2. `user_id` - ID của người dùng (tham chiếu đến bảng `user`)
3. `referral_count` - Số lượng người được giới thiệu
4. `monthly_commission` - Tổng hoa hồng theo tháng (tính từ đầu tháng đến cuối tháng, reset vào đầu tháng mới)
5. `total_commission` - Tổng hoa hồng nhận được từ trước đến nay
6. `rank` - Thứ hạng hiện tại của người dùng
7. `created_at` - Thời gian tạo bản ghi
8. `updated_at` - Thời gian cập nhật bản ghi gần nhất

## Cơ chế cập nhật xếp hạng

Hệ thống cập nhật xếp hạng tự động trong các trường hợp sau:

1. Khi có hoa hồng mới được thêm vào (bảng `referral_commission`)
2. Khi trạng thái hoa hồng được cập nhật (ví dụ: từ 'pending' thành 'approved')
3. Khi có người dùng mới được giới thiệu (thêm vào bảng `referred_user`)

## Cách tính thứ hạng

Việc xếp hạng được xác định theo thứ tự ưu tiên sau:
1. Tổng hoa hồng cao nhất
2. Số lượng người giới thiệu nhiều nhất (trong trường hợp hoa hồng bằng nhau)

## Chu kỳ reset hoa hồng theo tháng

- Hoa hồng theo tháng (`monthly_commission`) được tự động reset vào đầu mỗi tháng (ngày 01)
- Dữ liệu xếp hạng vẫn được lưu trữ và chỉ trường `monthly_commission` được đặt về 0

## Stored Procedure và Trigger

1. `update_user_rankings` - Stored procedure cập nhật thứ hạng của tất cả người dùng
2. `trg_update_ranking_after_commission_change` - Trigger kích hoạt khi thêm hoa hồng mới
3. `trg_update_ranking_after_commission_update` - Trigger kích hoạt khi cập nhật trạng thái hoa hồng
4. `trg_update_ranking_after_referral_add` - Trigger kích hoạt khi thêm người dùng được giới thiệu mới
5. `reset_monthly_commission` - Event tự động reset hoa hồng theo tháng vào đầu mỗi tháng

## Tích hợp vào hệ thống

Hệ thống xếp hạng được tích hợp với hệ thống giới thiệu hiện có và không yêu cầu thay đổi code ứng dụng để hoạt động. Việc cập nhật thứ hạng được xử lý hoàn toàn ở cấp độ cơ sở dữ liệu thông qua triggers và stored procedures.
