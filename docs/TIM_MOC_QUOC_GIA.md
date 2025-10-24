# Hướng dẫn triển khai chức năng TÌM MỐC QUỐC GIA

## Tổng quan
Chức năng tìm mốc quốc gia cho phép người dùng tìm các mốc trong bán kính 50km từ vị trí hiện tại hoặc tọa độ nhập vào.

## Cấu trúc dự án

### Backend Logic
**File:** `private/classes/Mocqg.php`
- Class `Mocqg` chứa toàn bộ logic xử lý mốc quốc gia
- Các phương thức chính:
  - `findNearbyMocqg()` - Tìm mốc trong bán kính
  - `getAllActiveMocqg()` - Lấy tất cả mốc đang hoạt động
  - `getMocqgById()` - Lấy thông tin một mốc
  - `calculateDistance()` - Tính khoảng cách giữa 2 điểm

### API Endpoint
**File:** `public/api/map/get_nearby_mocqg.php`
- API endpoint để gọi từ frontend
- Sử dụng class `Mocqg` để xử lý logic

### Frontend
**Files:** 
- `public/pages/map_display.php` - Giao diện bản đồ với nút "Tìm mốc"
- `public/assets/js/pages/map.js` - JavaScript xử lý tương tác

### Database
**File:** `db/migrations/20251023_add_mocqg_table.sql`
- Migration tạo bảng `mocqg`

## Các file đã tạo/sửa đổi

### 1. Database Migration
**File:** `db/migrations/20251023_add_mocqg_table.sql`
- Tạo bảng `mocqg` với các cột: id, ten_moc, lat, long, status, created_at, updated_at
- Thêm index cho status và location để tối ưu tìm kiếm
- Có sẵn 3 dữ liệu mẫu

### 2. API Endpoint
**File:** `public/api/map/get_nearby_mocqg.php`
- API tìm mốc trong bán kính (mặc định 50km)
- Sử dụng công thức Haversine để tính khoảng cách
- Trả về danh sách mốc được sắp xếp theo khoảng cách

### 3. Frontend - HTML
**File:** `public/pages/map_display.php`
- Thêm nút "Tìm mốc" vào map controls
- Thêm 3 popup mới:
  - Popup chọn phương thức (vị trí hiện tại/nhập tọa độ)
  - Popup nhập tọa độ và bán kính
  - Popup hiển thị kết quả tìm kiếm

### 4. Frontend - JavaScript
**File:** `public/assets/js/pages/map.js`
- Xử lý sự kiện click nút "Tìm mốc"
- Gọi API tìm mốc
- Hiển thị markers và vòng tròn bán kính trên bản đồ
- Hiển thị danh sách kết quả trong popup

## Cách triển khai

### Bước 1: Chạy Migration
```sql
-- Chạy file migration để tạo bảng mocqg
SOURCE db/migrations/20251023_add_mocqg_table.sql;

-- Hoặc import trực tiếp vào database
```

### Bước 2: Thêm dữ liệu mốc
```sql
-- Thêm các mốc quốc gia thực tế
INSERT INTO mocqg (ten_moc, lat, `long`, status) VALUES
('Mốc 1234', 21.028511, 105.804817, 1),
('Mốc 5678', 16.047079, 108.206230, 1);
-- ... thêm các mốc khác
```

### Bước 3: Kiểm tra quyền truy cập API
Đảm bảo file `public/api/map/get_nearby_mocqg.php` có thể truy cập từ trình duyệt.

### Bước 4: Test chức năng
1. Mở trang map_display.php
2. Click nút "Tìm mốc"
3. Chọn "Dùng vị trí hiện tại" hoặc "Nhập tọa độ"
4. Xem kết quả hiển thị trên bản đồ và trong popup

## Cách sử dụng

### Tìm mốc từ vị trí hiện tại
1. Click nút "Tìm mốc"
2. Chọn "Dùng vị trí hiện tại"
3. Cho phép trình duyệt truy cập vị trí
4. Xem kết quả

### Tìm mốc từ tọa độ nhập vào
1. Click nút "Tìm mốc"
2. Chọn "Nhập tọa độ"
3. Nhập Latitude, Longitude và bán kính (km)
4. Click "Tìm kiếm"
5. Xem kết quả

## Kết quả hiển thị
- Vòng tròn màu đỏ: Phạm vi tìm kiếm
- Marker đỏ: Vị trí tâm tìm kiếm
- Marker xanh lá: Các mốc tìm được
- Đường nét đứt xanh lá: Khoảng cách từ tâm đến mốc
- Popup: Danh sách chi tiết các mốc với khoảng cách

## Tính năng
- ✅ Tìm mốc trong bán kính tùy chỉnh (mặc định 50km)
- ✅ Tìm từ vị trí hiện tại hoặc tọa độ nhập vào
- ✅ Hiển thị khoảng cách chính xác
- ✅ Sắp xếp theo khoảng cách gần nhất
- ✅ Hiển thị trực quan trên bản đồ
- ✅ Chỉ hiển thị mốc đang hoạt động (status=1)

## API Reference

### GET /api/map/get_nearby_mocqg.php

**Parameters:**
- `lat` (required): Latitude của điểm tìm kiếm
- `lng` (required): Longitude của điểm tìm kiếm
- `radius` (optional): Bán kính tìm kiếm (km), mặc định 50

**Response:**
```json
{
  "success": true,
  "data": {
    "center": {
      "lat": 21.028511,
      "lng": 105.804817
    },
    "radius_km": 50,
    "count": 2,
    "mocqg_list": [
      {
        "id": 1,
        "ten_moc": "Mốc 001",
        "lat": 21.028511,
        "lng": 105.804817,
        "status": 1,
        "created_at": "2025-10-23 10:00:00",
        "distance_km": 0.52
      }
    ]
  }
}
```

## Ghi chú
- Công thức Haversine được sử dụng để tính khoảng cách chính xác trên bề mặt Trái Đất
- Bán kính mặc định là 50km nhưng có thể thay đổi khi nhập tọa độ
- Chỉ hiển thị các mốc có status = 1 (đang hoạt động)
- Index được tạo để tối ưu hiệu suất tìm kiếm

## Cập nhật sau này
Có thể mở rộng:
- Thêm filter theo loại mốc
- Thêm thông tin chi tiết hơn cho mỗi mốc
- Xuất danh sách mốc ra file Excel/PDF
- Thêm ảnh cho mỗi mốc
- Tích hợp chỉ đường đến mốc
