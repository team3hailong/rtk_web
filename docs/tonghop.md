# Hướng Dẫn Sử Dụng Hệ Thống

**Kính gửi Quý khách hàng,**

Tài liệu này nhằm mục đích hướng dẫn Quý khách cách sử dụng các chức năng chính của hệ thống đã được bàn giao. Chúng tôi tập trung vào các thao tác người dùng và quy trình vận hành cơ bản. Để biết chi tiết về cấu trúc mã nguồn hoặc các khía cạnh kỹ thuật sâu hơn, Quý khách vui lòng tham khảo trực tiếp bộ mã nguồn đã được cung cấp.

<!-- HÌNH MINH HỌA: Ảnh chụp màn hình trang chủ hoặc dashboard chính của hệ thống -->

## I. Hệ Thống Xác Thực Tài Khoản

Phần này hướng dẫn bạn cách tạo tài khoản, đăng nhập, xác thực email và quản lý mật khẩu.

### 1. Tạo Tài Khoản Mới

#### a. Đăng ký tài khoản
1.  Truy cập trang **Đăng ký** của hệ thống (thường có thể được tìm thấy qua nút "Đăng ký" trên trang chủ hoặc thanh điều hướng).
2.  Điền đầy đủ thông tin vào form:
    *   Tên người dùng (Username)
    *   Địa chỉ email
    *   Mật khẩu (tối thiểu 6 ký tự)
    *   Xác nhận mật khẩu
3.  Nhập mã giới thiệu (referral code) nếu có.
4.  Nhấn nút "Đăng Ký" để hoàn tất.

    <!-- HÌNH MINH HỌA: Form đăng ký tài khoản với các trường được điền mẫu -->

#### b. Xác thực email
Sau khi đăng ký, bạn sẽ nhận được email xác thực:
1.  Kiểm tra hộp thư đến (và thư mục spam nếu cần).
2.  Mở email có tiêu đề "Xác thực tài khoản".
3.  Nhấn vào liên kết xác thực trong email.
4.  Bạn sẽ được chuyển hướng đến trang xác nhận thành công.

    <!-- HÌNH MINH HỌA: Giao diện email xác thực với nút/link xác thực được đánh dấu -->

> **Lưu ý**: Liên kết xác thực có hiệu lực trong 24 giờ. Nếu hết hạn, bạn cần yêu cầu gửi lại email xác thực.

### 2. Đăng Nhập Vào Hệ Thống

#### a. Cách đăng nhập
1.  Truy cập trang **Đăng nhập** của hệ thống (thường có thể được tìm thấy qua nút "Đăng nhập" trên trang chủ hoặc thanh điều hướng).
2.  Nhập email và mật khẩu của bạn.
3.  Nhấn nút "Đăng Nhập".

    <!-- HÌNH MINH HỌA: Form đăng nhập với các trường email và mật khẩu -->

#### b. Nếu quên mật khẩu
Nếu bạn quên mật khẩu:
1.  Nhấn vào liên kết "Quên mật khẩu?" tại trang đăng nhập.
    <!-- HÌNH MINH HỌA: Trang đăng nhập với link "Quên mật khẩu?" được khoanh tròn -->
2.  Nhập địa chỉ email của tài khoản.
3.  Nhấn nút "Gửi yêu cầu".
4.  Kiểm tra email để lấy mã OTP đặt lại mật khẩu.

### 3. Đặt Lại Mật Khẩu

#### a. Sử dụng mã OTP
1.  Sau khi yêu cầu đặt lại mật khẩu, bạn sẽ nhận mã OTP qua email.
2.  Nhập mã OTP 6 chữ số vào các ô trên trang xác thực.
    <!-- HÌNH MINH HỌA: Form nhập mã OTP để đặt lại mật khẩu -->
3.  Nhấn nút "Xác thực".
4.  Sau khi xác thực thành công, bạn sẽ được chuyển đến trang đặt mật khẩu mới.

#### b. Tạo mật khẩu mới
1.  Nhập mật khẩu mới (tối thiểu 6 ký tự).
2.  Xác nhận mật khẩu mới.
    <!-- HÌNH MINH HỌA: Form đặt lại mật khẩu mới -->
3.  Nhấn nút "Đặt lại mật khẩu".
4.  Sau khi hoàn tất, bạn có thể đăng nhập bằng mật khẩu mới.

> **Lưu ý về mật khẩu**: Mật khẩu cần có ít nhất 6 ký tự để đảm bảo an toàn.

### 4. Đăng Xuất Khỏi Hệ Thống
Để đăng xuất:
1.  Nhấn vào tên người dùng hoặc biểu tượng tài khoản trên thanh điều hướng.
2.  Chọn "Đăng xuất" từ menu thả xuống.
    <!-- HÌNH MINH HỌA: Menu người dùng với tùy chọn "Đăng xuất" được đánh dấu -->
3.  Bạn sẽ được chuyển hướng đến trang chủ sau khi đăng xuất thành công.

### 5. Câu Hỏi Thường Gặp (Xác Thực)
*   **Tôi không nhận được email xác thực?**
    *   Kiểm tra thư mục spam/junk.
    *   Đảm bảo địa chỉ email đã nhập chính xác.
    *   Liên hệ hỗ trợ nếu vẫn không nhận được email sau 30 phút.
*   **Email báo "tài khoản đã tồn tại"?**
    *   Email đã được sử dụng để đăng ký. Thử đăng nhập hoặc dùng chức năng quên mật khẩu.
*   **Mã OTP không hoạt động?**
    *   Đảm bảo nhập đúng mã OTP.
    *   Kiểm tra OTP đã hết hạn chưa (thường 15 phút).
    *   Yêu cầu gửi lại mã OTP mới.
*   **Không thể đăng nhập dù đã nhập đúng thông tin?**
    *   Kiểm tra email đã được xác thực chưa.
    *   Thử chức năng quên mật khẩu.
    *   Tài khoản có thể bị khóa do nhiều lần đăng nhập thất bại.

---

## II. Quản Lý Tài Khoản Khảo Sát (RTK)

Phần này mô tả cách xem, quản lý và theo dõi các tài khoản RTK đã đăng ký.

### 1. Truy Cập Trang Quản Lý Tài Khoản
1.  Đăng nhập vào tài khoản của bạn.
2.  Từ menu điều hướng, chọn mục **Quản Lý Tài Khoản**.
    <!-- HÌNH MINH HỌA: Menu điều hướng với mục "Quản Lý Tài Khoản" được đánh dấu -->
3.  Hệ thống sẽ hiển thị danh sách tất cả tài khoản RTK của bạn.

### 2. Xem Danh Sách Tài Khoản

#### a. Thông tin hiển thị
Danh sách tài khoản hiển thị:
*   **Tên đăng nhập**: Tên đăng nhập RTK.
*   **Mật khẩu**: Mật khẩu tài khoản.
*   **Vị trí**: Tỉnh/thành phố sử dụng.
*   **Ngày bắt đầu/kết thúc**: Thời hạn hiệu lực.
*   **Thời hạn còn lại**: Số ngày còn lại.
*   **Trạng thái**: Hoạt động, Hết hạn, Đã khóa.
*   **Thao tác**: Các nút chức năng (Xem chi tiết, Đổi mật khẩu, Vô hiệu hóa/Kích hoạt).

    <!-- HÌNH MINH HỌA: Bảng danh sách tài khoản RTK với các cột thông tin chính -->

#### b. Bộ lọc và tìm kiếm
*   **Lọc theo trạng thái**: Nhấp vào "Tất cả", "Hoạt động", "Hết hạn", "Đã khóa".
*   **Lọc theo thời hạn còn lại**: Chọn khoảng thời gian (dưới 7 ngày, 7-30 ngày, v.v.).
*   **Tìm kiếm theo từ khóa**: Nhập tên đăng nhập, vị trí, mountpoint vào ô tìm kiếm.
*   **Đặt lại bộ lọc**: Nhấp "Đặt lại" để xóa bộ lọc.

    <!-- HÌNH MINH HỌA: Khu vực bộ lọc và tìm kiếm trên trang danh sách tài khoản -->

### 3. Xem Chi Tiết Tài Khoản
1.  Trong danh sách, nhấp vào nút "Xem chi tiết" (biểu tượng con mắt) của tài khoản muốn xem.
2.  Cửa sổ chi tiết sẽ hiển thị thông tin đầy đủ.
    <!-- HÌNH MINH HỌA: Cửa sổ popup/trang chi tiết tài khoản RTK -->

### 4. Thay Đổi Trạng Thái Tài Khoản

#### a. Vô hiệu hóa tài khoản
1.  Tìm tài khoản, nhấp nút "Vô hiệu hóa" (biểu tượng khóa).
2.  Xác nhận hành động. Trạng thái tài khoản sẽ chuyển thành "Đã khóa".
    <!-- HÌNH MINH HỌA: Nút "Vô hiệu hóa" và hộp thoại xác nhận -->

#### b. Kích hoạt lại tài khoản
1.  Tìm tài khoản "Đã khóa", nhấp nút "Kích hoạt" (biểu tượng mở khóa).
2.  Trạng thái tài khoản sẽ chuyển thành "Hoạt động" (nếu chưa hết hạn).
    <!-- HÌNH MINH HỌA: Nút "Kích hoạt" -->

### 5. Đổi Mật Khẩu Tài Khoản
1.  Tìm tài khoản, nhấp nút "Đổi mật khẩu" (biểu tượng chìa khóa).
2.  Nhập mật khẩu mới vào hộp thoại.
3.  Nhấp "Xác nhận".
    <!-- HÌNH MINH HỌA: Hộp thoại đổi mật khẩu tài khoản RTK -->

> **Lưu ý**: Hãy sử dụng mật khẩu đủ mạnh và an toàn.

### 6. Gia Hạn Tài Khoản
1.  Đánh dấu chọn các tài khoản muốn gia hạn.
2.  Nhấp vào nút "Gia hạn".
    <!-- HÌNH MINH HỌA: Danh sách tài khoản với các checkbox được chọn và nút "Gia hạn" -->
3.  Bạn sẽ được chuyển đến trang gia hạn để chọn gói và thanh toán.

> **Lưu ý**: Tài khoản dùng thử (trial) không thể gia hạn.

### 7. Xuất Dữ Liệu Tài Khoản
1.  Chọn các tài khoản muốn xuất (hoặc "Chọn tất cả").
2.  Theo dõi số lượng tài khoản đã chọn.
3.  Nhấp vào nút "Xuất Excel". File Excel sẽ được tải xuống.
    <!-- HÌNH MINH HỌA: Nút "Xuất Excel" và thông báo số lượng tài khoản đã chọn -->

### 8. Theo Dõi Thời Hạn Sử Dụng
*   Cột "Thời hạn còn lại" hiển thị số ngày còn lại.
*   Sử dụng bộ lọc "Thời hạn còn lại" để xem các tài khoản sắp hết hạn.
*   Hệ thống sẽ gửi email thông báo khi tài khoản sắp hết hạn (7, 3, 1 ngày).

### 9. Câu Hỏi Thường Gặp (Quản lý RTK)
*   **Tài khoản hiển thị "Đã khóa" là sao?**
    *   Do bạn chủ động vô hiệu hóa, vi phạm điều khoản, hoặc lỗi hệ thống. Thử kích hoạt lại hoặc liên hệ hỗ trợ.
*   **Tại sao không thể gia hạn một số tài khoản?**
    *   Là tài khoản dùng thử, bị khóa vĩnh viễn, hoặc gói dịch vụ đã ngừng. Cần đăng ký gói mới.
*   **Tài khoản hết hạn thì sao?**
    *   Không thể đăng nhập RTK. Trạng thái "Hết hạn". Có thể xem thông tin và gia hạn trong 30 ngày. Sau 30 ngày, cần đăng ký gói mới.
*   **Lọc tài khoản theo vị trí như thế nào?**
    *   Sử dụng ô tìm kiếm, nhập tên tỉnh/thành phố, nhấn Enter.

---

## III. Hệ Thống Mua Tài Khoản và Gia Hạn Dịch Vụ

Hướng dẫn này giúp bạn đăng ký, thanh toán và quản lý các gói dịch vụ.

### 1. Các Gói Dịch Vụ

#### a. Loại gói
*   **Gói Dùng thử**: Miễn phí (7 ngày) cho người dùng mới.
*   **Gói Tiêu chuẩn**: Thanh toán theo tháng/năm, đủ tính năng cơ bản.
*   **Gói Đặc biệt**: Cao cấp, nhiều tính năng nâng cao.

#### b. Xem và lựa chọn gói
1.  Đăng nhập vào tài khoản của bạn.
2.  Truy cập trang **Danh sách gói dịch vụ** (thường có thể được tìm thấy qua mục "Gói dịch vụ", "Mua gói" hoặc tương tự trên menu điều hướng).
3.  So sánh các gói dịch vụ dựa trên:
   - Giá cả
   - Thời hạn sử dụng
   - Các tính năng được cung cấp
   - Mức độ phổ biến
4.  Nhấn vào nút "Chọn gói" bên dưới gói dịch vụ mà bạn muốn mua.

    <!-- HÌNH MINH HỌA: Trang hiển thị các gói dịch vụ với nút "Chọn gói" -->

### 2. Quy Trình Mua Gói Dịch Vụ Mới

#### Bước 1: Chọn và xem chi tiết gói
*   Sau khi chọn, bạn sẽ xem mô tả đầy đủ về gói.

#### Bước 2: Nhập thông tin đơn hàng
1.  Chọn số lượng tài khoản (nếu cần nhiều).
2.  Chọn tỉnh/thành phố sử dụng.
3.  Hệ thống tự tính tổng tiền.
4.  Nhấn "Tiếp tục".
    <!-- HÌNH MINH HỌA: Form nhập thông tin đơn hàng (số lượng, vị trí) -->

#### Bước 3: Thanh toán
1.  Kiểm tra lại thông tin đơn hàng (mã đơn, gói, số lượng, khu vực, tổng tiền).
2.  Nhập mã giảm giá (nếu có) và nhấn "Áp dụng".
3.  **Phương thức thanh toán**: Hiện chỉ hỗ trợ chuyển khoản ngân hàng.
    *   Thông tin tài khoản ngân hàng và mã QR sẽ hiển thị.
    *   Nội dung chuyển khoản cần ghi đúng theo hướng dẫn (thường là mã đơn hàng).
    <!-- HÌNH MINH HỌA: Trang thanh toán với thông tin chuyển khoản và mã QR -->
4.  Sau khi chuyển khoản, nhấn "Tôi đã thanh toán".

#### Bước 4: Tải lên minh chứng thanh toán
1.  Chụp ảnh biên lai hoặc màn hình xác nhận chuyển khoản.
2.  Nhấp "Chọn tệp" để tải lên minh chứng.
3.  Kiểm tra ảnh, nhấp "Xác nhận".
    <!-- HÌNH MINH HỌA: Giao diện tải lên minh chứng thanh toán -->

#### Bước 5: Hoàn tất
*   Đơn hàng chờ xác nhận thanh toán (thường 1-24 giờ làm việc).
*   Bạn sẽ nhận email xác nhận và tài khoản sẽ được kích hoạt.
*   Theo dõi trạng thái đơn hàng trong "Quản lý tài khoản" hoặc "Quản lý giao dịch".

### 3. Đăng Ký Dùng Thử (Cho Người Dùng Mới)
*   Chỉ dành cho người chưa từng có tài khoản.
1.  Đăng nhập, vào trang danh sách gói.
2.  Nhấn "Dùng thử miễn phí" dưới gói dùng thử.
3.  Chọn tỉnh/thành phố.
4.  Nhấn "Đăng ký dùng thử". Tài khoản kích hoạt ngay.
    <!-- HÌNH MINH HỌA: Nút "Dùng thử miễn phí" trên gói dịch vụ -->

> **Lưu ý**: Dùng thử 7 ngày. Sau đó cần đăng ký gói chính thức.

### 4. Gia Hạn Tài Khoản Hiện Có
*   Hệ thống sẽ email báo khi tài khoản sắp hết hạn.
1.  Đăng nhập, vào "Quản lý tài khoản" hoặc "Gia hạn dịch vụ".
2.  Chọn tài khoản cần gia hạn.
3.  Chọn gói gia hạn (thời gian).
4.  Xem tổng tiền, nhấn "Tiếp tục".
5.  Quy trình thanh toán và tải minh chứng tương tự mua gói mới.
    <!-- HÌNH MINH HỌA: Trang chọn tài khoản và gói để gia hạn -->

### 5. Câu Hỏi Thường Gặp (Mua Hàng & Gia Hạn)
*   **Có thể thay đổi gói dịch vụ sau khi mua không?**
    *   Hiện tại chưa hỗ trợ. Cần dùng hết thời hạn rồi đăng ký gói khác.
*   **Đã thanh toán nhưng chưa nhận được xác nhận?**
    *   Chờ 1-24 giờ làm việc. Sau đó liên hệ hỗ trợ.
*   **Mã giảm giá không hoạt động?**
    *   Kiểm tra hạn sử dụng, điều kiện áp dụng, định dạng mã.
*   **Quên tải lên minh chứng thanh toán?**
    *   Vào "Quản lý tài khoản" / "Quản lý giao dịch", tìm đơn hàng và tải lên.
*   **Làm sao biết khi nào cần gia hạn?**
    *   Email thông báo (7, 3, 1 ngày trước khi hết hạn) hoặc kiểm tra trong "Quản lý tài khoản".

### 6. Lưu Ý Quan Trọng Khi Mua Hàng
*   Ghi đúng nội dung chuyển khoản.
*   Nếu mua nhiều tài khoản, bạn sẽ nhận nhiều tài khoản riêng biệt.
*   Thông tin tài khoản sẽ được gửi qua email sau khi thanh toán thành công. Bảo mật thông tin này.

---

## IV. Hệ Thống Giới Thiệu Người Dùng

Kiếm hoa hồng bằng cách giới thiệu người mới.

### 1. Tổng Quan
*   Chia sẻ mã/liên kết giới thiệu.
*   Khi người được giới thiệu đăng ký và giao dịch, bạn nhận 5% giá trị giao dịch làm hoa hồng.
*   Theo dõi người giới thiệu, hoa hồng và rút tiền.

### 2. Truy Cập Bảng Điều Khiển Giới Thiệu
1.  Đăng nhập vào tài khoản của bạn.
2.  Từ menu chính, chọn **"Giới thiệu"** (hoặc một mục tương tự).
    <!-- HÌNH MINH HỌA: Menu với mục "Giới thiệu" được đánh dấu -->
3.  Bảng điều khiển giới thiệu sẽ hiển thị với các tab chức năng.

### 3. Mã Giới Thiệu và Cách Chia Sẻ
*   Mã giới thiệu tự động tạo khi truy cập lần đầu.
*   **Tìm mã**: Tại tab "Tổng quan", ô "Mã giới thiệu của tôi".
*   **Chia sẻ**: Sao chép mã hoặc liên kết giới thiệu. Gửi qua tin nhắn, email, đăng mạng xã hội.
    <!-- HÌNH MINH HỌA: Khu vực hiển thị mã giới thiệu và link giới thiệu với nút sao chép -->

### 4. Theo Dõi Người Được Giới Thiệu
*   Truy cập tab **"Danh sách giới thiệu"**.
*   Xem: Tên người dùng, Email (ẩn một phần), Ngày đăng ký.
    <!-- HÌNH MINH HỌA: Bảng danh sách những người đã được giới thiệu -->

### 5. Hoa Hồng và Các Giao Dịch
*   **Cách tính**: 5% giá trị giao dịch thành công của người được giới thiệu.
*   **Xem chi tiết**: Tab **"Hoa hồng"**.
    *   Hiển thị: Tên người dùng, số tiền giao dịch, hoa hồng, trạng thái giao dịch, trạng thái hoa hồng, ngày.
    <!-- HÌNH MINH HỌA: Bảng chi tiết hoa hồng từ các giao dịch -->
*   **Tổng quan hoa hồng**:
    *   Tổng hoa hồng kiếm được.
    *   Đã rút.
    *   Đang chờ rút.
    *   Số dư khả dụng.

### 6. Yêu Cầu Rút Tiền Hoa Hồng
*   **Điều kiện**: Tối thiểu 100,000 VNĐ, thông tin ngân hàng hợp lệ.
*   **Quy trình**:
    1.  Truy cập tab **"Yêu cầu rút tiền"**.
    2.  Điền: Số tiền muốn rút, Tên ngân hàng, Số tài khoản, Tên chủ tài khoản.
        <!-- HÌNH MINH HỌA: Form yêu cầu rút tiền hoa hồng -->
    3.  Nhấn "Gửi yêu cầu rút tiền".
    *   Yêu cầu xử lý trong 1-3 ngày làm việc.

### 7. Xem Lịch Sử Rút Tiền
*   Truy cập tab **"Lịch sử rút tiền"**.
*   Xem: Mã yêu cầu, Số tiền, Thông tin ngân hàng, Trạng thái (Đang chờ, Đã thanh toán, Đã từ chối), Ngày.
    <!-- HÌNH MINH HỌA: Bảng lịch sử các yêu cầu rút tiền -->

### 8. Bảng Xếp Hạng Giới Thiệu
*   Truy cập tab **"Bảng xếp hạng"**.
*   Xem: Bảng xếp hạng tháng và tổng. Hiển thị: Thứ hạng, Tên, Số người giới thiệu, Tổng hoa hồng.
    <!-- HÌNH MINH HỌA: Giao diện bảng xếp hạng người giới thiệu -->

### 9. Câu Hỏi Thường Gặp (Giới Thiệu)
*   **Mã giới thiệu có thời hạn không?** Không.
*   **Giới hạn số người giới thiệu?** Không.
*   **Khi nào nhận được hoa hồng?** Khi giao dịch của người được giới thiệu hoàn tất và hoa hồng được duyệt.
*   **Tại sao hoa hồng "Đang chờ"?** Giao dịch chưa hoàn tất.
*   **Mất bao lâu để xử lý rút tiền?** 1-3 ngày làm việc.
*   **Yêu cầu rút tiền bị từ chối?** Thông tin ngân hàng sai, số dư không đủ, gian lận. Kiểm tra ghi chú.
*   **Nếu người dùng quên nhập mã giới thiệu?** Cần nhập khi đăng ký. Không thêm được sau khi tạo tài khoản.

---

## V. Quản Lý Giao Dịch

Theo dõi, quản lý và thực hiện các tác vụ liên quan đến giao dịch tài chính của bạn.

### 1. Truy Cập Trang Quản Lý Giao Dịch
1.  Đăng nhập vào tài khoản của bạn.
2.  Từ menu điều hướng chính, chọn mục **Quản Lý Giao Dịch**.
    <!-- HÌNH MINH HỌA: Menu với mục "Quản Lý Giao Dịch" được đánh dấu -->

### 2. Xem Danh Sách Giao Dịch
Bảng hiển thị: ID Giao dịch, Thời gian, Số tiền, Phương thức, Trạng thái (Hoàn thành, Chờ xử lý, Thất bại, Đã hủy).
<!-- HÌNH MINH HỌA: Bảng danh sách các giao dịch -->

#### a. Lọc và Tìm kiếm Giao Dịch
*   **Lọc theo trạng thái**: "Tất cả", "Hoàn thành", "Chờ xử lý", "Thất bại".
*   **Tìm kiếm**: Nhập ID giao dịch, loại giao dịch,... vào ô tìm kiếm.
*   **Lọc theo số tiền**: Chọn các khoảng giá trị.
*   **Lọc theo thời gian**: Hôm nay, 7 ngày qua, 30 ngày qua, hoặc tùy chọn khoảng ngày.
*   **Số lượng hiển thị**: Chọn 10, 20, 50 giao dịch/trang.
    <!-- HÌNH MINH HỌA: Khu vực các bộ lọc (trạng thái, số tiền, thời gian) và tìm kiếm giao dịch -->

### 3. Xem Chi Tiết Giao Dịch
1.  Tìm giao dịch, nhấp nút "Chi tiết" (biểu tượng mắt).
2.  Cửa sổ popup hiển thị thông tin chi tiết, lý do từ chối (nếu có), ảnh minh chứng (nếu có).
    <!-- HÌNH MINH HỌA: Cửa sổ popup chi tiết một giao dịch -->

### 4. Gửi Minh Chứng Thanh Toán
*   Áp dụng cho giao dịch "Chờ xử lý".
1.  Tìm giao dịch, nhấp nút "Gửi MC" (biểu tượng tải lên).
2.  Chuyển đến trang tải lên, chọn tệp ảnh minh chứng.
3.  Nhấn "Xác nhận".
    <!-- HÌNH MINH HỌA: Nút "Gửi MC" và trang tải lên minh chứng -->

### 5. Yêu Cầu Xuất Hóa Đơn VAT
*   Áp dụng cho giao dịch "Hoàn thành".
1.  Tìm giao dịch, nhấp nút "Hóa đơn" (biểu tượng hóa đơn).
2.  Chuyển đến trang yêu cầu, kiểm tra thông tin.
    <!-- HÌNH MINH HỌA: Nút "Hóa đơn" trên giao dịch -->
3.  Nếu thông tin công ty chưa đủ, nhấp "Cập nhật thông tin ngay" để điền.
    <!-- HÌNH MINH HỌA: Trang yêu cầu xuất hóa đơn, có thể có link cập nhật thông tin công ty -->
4.  Nhấn "Yêu cầu xuất hóa đơn".

### 6. Xem Hóa Đơn Đã Xuất
1.  Tìm giao dịch, nhấp nút "Xem HĐ" (biểu tượng dấu tích).
2.  Xem thông tin, trạng thái yêu cầu, nút tải xuống hóa đơn (nếu đã duyệt).
    <!-- HÌNH MINH HỌA: Trang xem thông tin hóa đơn đã xuất với nút tải về -->

### 7. Xuất Hóa Đơn Bán Lẻ (Nhiều Giao Dịch)
1.  Chọn các giao dịch (tối đa 5) bằng cách đánh dấu ô kiểm.
2.  Nhấp nút "Xuất HĐ bán lẻ" phía trên danh sách.
3.  File PDF hóa đơn sẽ tự động tải xuống.
    <!-- HÌNH MINH HỌA: Các checkbox được chọn và nút "Xuất HĐ bán lẻ" -->

### 8. Phân Trang
Sử dụng các nút `<<`, `<`, `số trang`, `>`, `>>` ở cuối danh sách để duyệt.

### 9. Các Trạng Thái Giao Dịch và Màu Sắc
*   **Hoàn thành**: Xanh lá.
*   **Chờ xử lý**: Cam.
*   **Thất bại**: Đỏ.
*   **Đã hủy**: Xám.

### 10. Xử Lý Sự Cố Thường Gặp (Giao Dịch)
*   **Không tìm thấy giao dịch?**
    *   Kiểm tra bộ lọc trạng thái, khoảng thời gian.
    *   Dùng ID giao dịch chính xác để tìm.
*   **Không thể yêu cầu xuất hóa đơn?**
    *   Giao dịch phải "Hoàn thành".
    *   Thông tin công ty, MST phải đủ.
*   **Không thể xuất hóa đơn bán lẻ?**
    *   Đã chọn giao dịch chưa? Không quá 5 giao dịch.
*   **Không thể tải lên minh chứng thanh toán?**
    *   Kiểm tra định dạng (JPG, PNG), kích thước file (<5MB), kết nối mạng.

---

## VI. Liên Hệ Hỗ Trợ Chung

Nếu bạn gặp bất kỳ vấn đề nào trong quá trình sử dụng hệ thống hoặc có câu hỏi không được đề cập trong tài liệu này, vui lòng liên hệ với chúng tôi:
*   **Email**: support@example.com
*   **Điện thoại/Hotline**: 0123-456-789 (hoặc 1900-xxxx)
*   **Form liên hệ**: (Nếu có, mô tả cách truy cập form liên hệ trên trang web)
*   **Thời gian hỗ trợ**: (Ví dụ: 8:00 - 18:00, Thứ Hai - Thứ Sáu)

**Trân trọng cảm ơn Quý khách đã sử dụng dịch vụ!**