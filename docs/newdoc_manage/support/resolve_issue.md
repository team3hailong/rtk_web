# Hướng dẫn xử lý vấn đề hệ thống hướng dẫn và liên hệ hỗ trợ

Tài liệu này cung cấp hướng dẫn giải quyết các vấn đề thường gặp trong hệ thống hướng dẫn và liên hệ hỗ trợ, bao gồm cả nguyên nhân và cách khắc phục.

## 1. Vấn đề với hệ thống hướng dẫn

### 1.1. Không hiển thị bài viết hướng dẫn

**Triệu chứng:** Trang hướng dẫn không hiển thị bất kỳ bài viết nào hoặc hiển thị "Không có bài viết nào phù hợp".

**Nguyên nhân có thể:**
- Chưa có bài viết hướng dẫn được tạo hoặc xuất bản
- Bài viết có trạng thái không phải "published"
- Vấn đề với kết nối database

**Cách giải quyết:**
```php
// Kiểm tra xem có bài viết nào trong database không
$check_query = "SELECT COUNT(*) FROM guide WHERE status = 'published'";
$stmt = $pdo->prepare($check_query);
$stmt->execute();
$count = $stmt->fetchColumn();

if ($count == 0) {
    // Không có bài viết nào đã được xuất bản
    error_log("No published guide articles found in database");
    // Thêm bài viết mẫu hoặc đảm bảo trạng thái bài viết là 'published'
}

// Kiểm tra kết nối database
try {
    $pdo->query("SELECT 1");
} catch (PDOException $e) {
    error_log("Database connection issue: " . $e->getMessage());
    // Khởi tạo lại kết nối database
    $db = new Database();
    $pdo = $db->getConnection();
}
```

### 1.2. Filter và tìm kiếm không hoạt động

**Triệu chứng:** Khi sử dụng filter theo chủ đề hoặc tìm kiếm theo từ khóa, không có kết quả được lọc hoặc hiển thị sai.

**Nguyên nhân có thể:**
- SQL query không chính xác
- Tham số filter không được truyền đúng
- Vấn đề với encoding ký tự

**Cách giải quyết:**
```php
// Debug SQL query và tham số
$keyword = trim($_GET['keyword'] ?? '');
$topic = trim($_GET['topic'] ?? '');

error_log("Search parameters - Keyword: '$keyword', Topic: '$topic'");

// Kiểm tra SQL query
$sql = "SELECT * FROM guide WHERE status = 'published'";
$params = [];

if (!empty($keyword)) {
    $sql .= " AND (title LIKE :kw1 OR content LIKE :kw2)";
    $params[':kw1'] = "%" . $keyword . "%";
    $params[':kw2'] = "%" . $keyword . "%";
    error_log("SQL with keyword filter: $sql");
}

if (!empty($topic)) {
    $sql .= " AND topic = :topic";
    $params[':topic'] = $topic;
    error_log("SQL with topic filter: $sql");
}

// Thực thi truy vấn với debug
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
error_log("Number of results: " . count($results));
```

### 1.3. Chi tiết bài viết không hiển thị đúng

**Triệu chứng:** Chi tiết bài viết hiển thị sai định dạng, thiếu hình ảnh, hoặc nội dung HTML không render đúng.

**Nguyên nhân có thể:**
- Đường dẫn hình ảnh không chính xác
- Nội dung HTML không hợp lệ
- Vấn đề với CSS

**Cách giải quyết:**
```php
// Kiểm tra đường dẫn hình ảnh
if (!empty($article['thumbnail'])) {
    $thumbnail_path = $admin_site . '/public/uploads/guide/' . basename($article['thumbnail']);
    if (!file_exists($_SERVER['DOCUMENT_ROOT'] . parse_url($thumbnail_path, PHP_URL_PATH))) {
        error_log("Thumbnail image not found: " . $thumbnail_path);
        // Sử dụng hình ảnh placeholder
        $thumbnail_path = $base_url . '/public/assets/images/placeholder.jpg';
    }
}

// Kiểm tra và làm sạch nội dung HTML
$content = $article['content'];
// Sử dụng thư viện HTML Purifier để làm sạch HTML không hợp lệ nếu cần

// Kiểm tra xem stylesheet đã được load chưa
echo '<link rel="stylesheet" href="' . $base_url . '/public/assets/css/pages/support/guide_detail.css">';
```

## 2. Vấn đề với hệ thống liên hệ hỗ trợ

### 2.1. Không gửi được yêu cầu hỗ trợ

**Triệu chứng:** Form gửi yêu cầu hỗ trợ báo lỗi hoặc không có phản hồi sau khi submit.

**Nguyên nhân có thể:**
- Lỗi CSRF token
- Validation thất bại
- Lỗi database khi insert

**Cách giải quyết:**
```php
// Kiểm tra CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    error_log("CSRF token validation failed");
    // Tạo token mới và yêu cầu người dùng thử lại
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['support_error'] = 'Đã xảy ra lỗi xác thực. Vui lòng thử lại.';
    header('Location: ' . $base_url . '/public/pages/support/contact.php');
    exit;
}

// Debug thông tin form
error_log("Form data: " . print_r($_POST, true));

// Kiểm tra lỗi database
try {
    // Thử insert record
    $result = $supportRequest->createRequest($user_id, $subject, $message, $category);
    error_log("Create request result: " . print_r($result, true));
} catch (Exception $e) {
    error_log("Error creating support request: " . $e->getMessage());
    // Ghi log chi tiết lỗi và thông báo cho người dùng
    $_SESSION['support_error'] = 'Lỗi hệ thống. Vui lòng thử lại sau.';
}
```

### 2.2. Các yêu cầu hỗ trợ trước đó không hiển thị

**Triệu chứng:** Không hiển thị danh sách yêu cầu hỗ trợ đã gửi trước đó.

**Nguyên nhân có thể:**
- Lỗi truy vấn database
- User ID không khớp
- Vấn đề với hiển thị bảng

**Cách giải quyết:**
```php
// Debug truy vấn lịch sử yêu cầu
$user_id = $_SESSION['user_id'] ?? 0;
error_log("Fetching support requests for user ID: $user_id");

// Kiểm tra trực tiếp trong database
$sql = "SELECT COUNT(*) FROM support_requests WHERE user_id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$count = $stmt->fetchColumn();
error_log("Support requests found in database: $count");

// Kiểm tra kết quả từ phương thức getRequestsByUser
$requests = $supportRequest->getRequestsByUser($user_id);
error_log("Support requests returned by method: " . count($requests));

// Kiểm tra cấu trúc HTML
if (empty($supportRequests)) {
    echo '<div class="no-requests">Bạn chưa có yêu cầu hỗ trợ nào.</div>';
} else {
    // Hiển thị bảng
}
```

### 2.3. Modal chi tiết không hiển thị đúng

**Triệu chứng:** Modal chi tiết yêu cầu hỗ trợ không mở hoặc hiển thị thông tin sai.

**Nguyên nhân có thể:**
- Lỗi JavaScript
- Dữ liệu JSON không hợp lệ
- CSS không load đúng

**Cách giải quyết:**
```javascript
// Kiểm tra dữ liệu JSON khi click vào nút "Xem"
const viewButtons = document.querySelectorAll('.btn-view-details');
viewButtons.forEach(button => {
    button.addEventListener('click', function() {
        // Log dữ liệu request để debug
        try {
            const requestData = JSON.parse(this.getAttribute('data-request'));
            console.log('Request data:', requestData);
            
            // Kiểm tra các trường dữ liệu
            if (!requestData.subject || !requestData.message) {
                console.error('Invalid request data: Missing required fields');
                return;
            }
            
            // Populate modal và hiển thị
            document.getElementById('modal-subject').textContent = requestData.subject;
            document.getElementById('modal-message').textContent = requestData.message;
            // Các trường khác...
            
            // Hiển thị modal
            document.getElementById('request-modal').style.display = 'block';
            
        } catch (e) {
            console.error('Error parsing request data:', e);
            alert('Có lỗi khi hiển thị chi tiết. Vui lòng thử lại.');
        }
    });
});

// Kiểm tra CSS đã load chưa
document.addEventListener('DOMContentLoaded', function() {
    const modalStyles = window.getComputedStyle(document.getElementById('request-modal'));
    if (modalStyles.display === 'none' && modalStyles.position !== 'fixed') {
        console.error('Modal CSS not loaded correctly');
        // Thêm inline CSS cần thiết
        const style = document.createElement('style');
        style.textContent = `
            .modal { 
                display: none; position: fixed; z-index: 1000; 
                left: 0; top: 0; width: 100%; height: 100%;
                background-color: rgba(0, 0, 0, 0.4);
            }
            /* Thêm CSS cần thiết khác */
        `;
        document.head.appendChild(style);
    }
});
```

## 3. Vấn đề với thông tin công ty

### 3.1. Thông tin công ty không hiển thị

**Triệu chứng:** Phần thông tin công ty trống hoặc hiển thị "Không có thông tin công ty".

**Nguyên nhân có thể:**
- Không có dữ liệu trong bảng company_info
- Lỗi phương thức getCompanyInfo()
- Section đã bị comment out trong code

**Cách giải quyết:**
```php
// Kiểm tra xem section có bị comment out
// Uncomment phần hiển thị thông tin công ty trong contact.php nếu cần

// Kiểm tra dữ liệu công ty trong database
$sql = "SELECT * FROM company_info ORDER BY id DESC LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    error_log("No company information found in database");
    // Thêm dữ liệu công ty mặc định
    $insert_sql = "INSERT INTO company_info (name, description, address, phone, email)
                   VALUES ('Công ty dịch vụ đo đạc RTK', 'Mô tả công ty', 
                          'Địa chỉ công ty', '0123456789', 'info@example.com')";
    $pdo->exec($insert_sql);
} else {
    error_log("Company info found: " . print_r($company, true));
}

// Kiểm tra phương thức getCompanyInfo
$companyInfo = $supportRequest->getCompanyInfo();
if (!$companyInfo) {
    error_log("getCompanyInfo() returned empty result");
}
```

### 3.2. Định dạng địa chỉ không đúng

**Triệu chứng:** Địa chỉ công ty hiển thị không đúng định dạng hoặc bị lỗi JSON.

**Nguyên nhân có thể:**
- Dữ liệu địa chỉ được lưu dưới dạng JSON không hợp lệ
- Vấn đề với parsing JSON
- Trường hợp edge case không được xử lý

**Cách giải quyết:**
```php
// Xử lý địa chỉ an toàn
$addressesJson = $companyInfo['address'] ?? null;
$addresses = null;

// Kiểm tra và xử lý JSON
if ($addressesJson) {
    try {
        $addresses = json_decode($addressesJson, true);
        
        // Kiểm tra kết quả decode
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            // Xử lý như địa chỉ thông thường
            $addresses = null;
        }
    } catch (Exception $e) {
        error_log("Error processing address JSON: " . $e->getMessage());
        $addresses = null;
    }
}

// Hiển thị địa chỉ với các trường hợp khác nhau
if (is_array($addresses) && !empty($addresses)) {
    foreach ($addresses as $addressEntry) {
        $typeText = '';
        if (isset($addressEntry['type'])) {
            switch ($addressEntry['type']) {
                case 'trụ sở':
                    $typeText = 'Trụ sở: ';
                    break;
                case 'chi nhánh':
                    $typeText = 'Chi nhánh: ';
                    break;
                default:
                    $typeText = '';
            }
        }
        $locationText = isset($addressEntry['location']) ? 
                        htmlspecialchars($addressEntry['location']) : 'N/A';
        
        echo '<div class="contact-item">';
        echo '<i class="fas fa-map-marker-alt"></i>';
        echo '<span><strong>' . $typeText . '</strong>' . $locationText . '</span>';
        echo '</div>';
    }
} elseif (is_string($addressesJson) && !empty($addressesJson)) {
    // Fallback cho plain text
    echo '<div class="contact-item">';
    echo '<i class="fas fa-map-marker-alt"></i>';
    echo '<span>' . htmlspecialchars($addressesJson) . '</span>';
    echo '</div>';
}
```

## 4. Vấn đề hiệu suất và tối ưu

### 4.1. Trang guide.php tải chậm

**Triệu chứng:** Trang danh sách hướng dẫn tải chậm khi có nhiều bài viết.

**Nguyên nhân có thể:**
- Truy vấn database không tối ưu
- Tải quá nhiều bài viết cùng lúc
- Hình ảnh thumbnail chưa được tối ưu

**Cách giải quyết:**
```php
// Thêm phân trang cho danh sách bài viết
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10; // Số bài viết mỗi trang
$offset = ($page - 1) * $perPage;

// Sửa hàm get_filtered_guide_articles() trong guide_helper.php
function get_filtered_guide_articles($pdo, $keyword = null, $topic = null, $page = 1, $perPage = 10) {
    $offset = ($page - 1) * $perPage;
    
    // SQL với LIMIT và OFFSET cho phân trang
    $sql = "SELECT id, title, slug, topic, thumbnail, created_at, 
                  LEFT(SUBSTRING(content, 1, 500), 300) as content_summary
           FROM guide 
           WHERE status = 'published'";
    
    // Thêm các điều kiện filter...
    
    $sql .= " ORDER BY published_at DESC, created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    // Thêm các bind param khác...
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Thêm các điều khiển phân trang vào guide.php
$totalArticles = count_guide_articles($pdo, $keyword, $topic);
$totalPages = ceil($totalArticles / $perPage);

// Hiển thị điều khiển phân trang
echo '<div class="pagination">';
for ($i = 1; $i <= $totalPages; $i++) {
    $active = $i === $page ? 'active' : '';
    echo '<a href="?page=' . $i . '&keyword=' . urlencode($keyword) . '&topic=' . urlencode($topic) . '" class="' . $active . '">' . $i . '</a>';
}
echo '</div>';

// Lazy loading cho hình ảnh
echo '<img class="guide-thumb" loading="lazy" src="' . $imageUrl . '" alt="Thumbnail">';
```

### 4.2. Thời gian phản hồi form liên hệ chậm

**Triệu chứng:** Form gửi yêu cầu hỗ trợ mất nhiều thời gian để xử lý và phản hồi.

**Nguyên nhân có thể:**
- Logging quá chi tiết
- Lọc đầu vào không hiệu quả
- Xử lý session không tối ưu

**Cách giải quyết:**
```php
// Tối ưu quy trình xử lý form
function process_support_request() {
    global $base_url;
    
    // Kiểm tra CSRF token hiệu quả
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['support_error'] = 'Lỗi xác thực. Vui lòng thử lại.';
        redirect_to_contact_page();
        return;
    }
    
    // Validate đầu vào nhanh
    $required_fields = ['subject', 'message', 'category'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $_SESSION['support_error'] = 'Vui lòng điền đầy đủ thông tin.';
            redirect_to_contact_page();
            return;
        }
    }
    
    // Lọc dữ liệu đầu vào hiệu quả
    $subject = substr(trim($_POST['subject']), 0, 100);
    $message = substr(trim($_POST['message']), 0, 1000);
    $category = in_array($_POST['category'], ['technical', 'billing', 'account', 'other']) 
                ? $_POST['category'] : 'other';
    
    // Xử lý database
    try {
        $db = new Database();
        $supportRequest = new SupportRequest($db);
        $result = $supportRequest->createRequest($_SESSION['user_id'], $subject, $message, $category);
        
        if ($result['success']) {
            $_SESSION['support_message'] = 'Yêu cầu hỗ trợ đã được gửi thành công.';
        } else {
            $_SESSION['support_error'] = $result['error'] ?? 'Có lỗi xảy ra.';
        }
    } catch (Exception $e) {
        $_SESSION['support_error'] = 'Lỗi hệ thống. Vui lòng thử lại sau.';
    }
    
    redirect_to_contact_page();
}

function redirect_to_contact_page() {
    global $base_url;
    header('Location: ' . $base_url . '/public/pages/support/contact.php');
    exit;
}
```

## 5. Vấn đề với Mobile Responsiveness

### 5.1. Giao diện vỡ trên thiết bị di động

**Triệu chứng:** Layout bị vỡ hoặc các phần tử hiển thị không đúng trên thiết bị di động.

**Nguyên nhân có thể:**
- Thiếu media queries
- Sử dụng kích thước cố định thay vì tương đối
- Overflow content

**Cách giải quyết:**
```css
/* Thêm vào file CSS của trang guide và contact */
@media (max-width: 768px) {
    .guide-search-form {
        flex-direction: column;
        gap: 12px;
    }
    
    .guide-search-form > div {
        width: 100%;
    }
    
    .guide-search-form button, 
    .reset-button {
        width: 100%;
        margin-top: 8px;
    }
    
    .guide-item-content {
        flex-direction: column;
    }
    
    .guide-thumb {
        width: 100%;
        height: auto;
        margin-right: 0;
        margin-bottom: 12px;
    }
    
    .support-grid {
        grid-template-columns: 1fr;
    }
    
    .requests-table {
        display: block;
        overflow-x: auto;
    }
    
    .modal-content {
        width: 95%;
        margin: 10% auto;
    }
}

/* Thêm viewport meta tag vào tất cả các trang */
echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
```

### 5.2. Modal không hoạt động đúng trên mobile

**Triệu chứng:** Modal chi tiết yêu cầu hỗ trợ hiển thị không đúng hoặc khó đóng trên thiết bị di động.

**Nguyên nhân có thể:**
- Z-index không đúng
- Thiếu touch events
- Vấn đề scrolling

**Cách giải quyết:**
```javascript
// Cải thiện xử lý modal trên mobile
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('request-modal');
    const closeBtn = document.querySelector('.modal-close-btn');
    const modalContent = document.querySelector('.modal-content');
    
    // Thêm xử lý cho touch events
    closeBtn.addEventListener('touchend', function(e) {
        e.preventDefault();
        modal.style.display = 'none';
        enableBodyScroll();
    });
    
    // Ngăn không cho click bên trong modal đóng modal
    modalContent.addEventListener('touchend', function(e) {
        e.stopPropagation();
    });
    
    // Đóng modal khi tap bên ngoài
    modal.addEventListener('touchend', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
            enableBodyScroll();
        }
    });
    
    // Các nút mở modal
    const viewButtons = document.querySelectorAll('.btn-view-details');
    viewButtons.forEach(button => {
        button.addEventListener('touchend', function(e) {
            e.preventDefault();
            // Mở modal
            modal.style.display = 'block';
            disableBodyScroll();
            // Populate data...
        });
    });
    
    // Disable scrolling khi modal mở
    function disableBodyScroll() {
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.width = '100%';
    }
    
    // Enable scrolling khi modal đóng
    function enableBodyScroll() {
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.width = '';
    }
});
```

## 6. Các vấn đề khác

### 6.1. Lỗi CSRF token

**Triệu chứng:** Form không submit được hoặc báo lỗi CSRF token không hợp lệ.

**Nguyên nhân có thể:**
- Session bị hết hạn
- CSRF token không được tạo hoặc lưu đúng cách
- Conflict giữa các form trên cùng một trang

**Cách giải quyết:**
```php
// Kiểm tra và tạo mới CSRF token nếu cần
function ensure_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Hàm tạo input field CSRF
function generate_csrf_input() {
    $token = ensure_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

// Kiểm tra CSRF token
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

// Tái tạo token sau mỗi lần submit thành công
function regenerate_csrf_token() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
```

### 6.2. Lỗi hiển thị ký tự đặc biệt

**Triệu chứng:** Nội dung hiển thị các ký tự đặc biệt không đúng (tiếng Việt, emojis, etc.).

**Nguyên nhân có thể:**
- Vấn đề với encoding database
- Thiếu meta charset
- HTML entities không được xử lý đúng

**Cách giải quyết:**
```php
// Đảm bảo charset UTF-8 trong header
echo '<meta charset="UTF-8">';

// Đảm bảo kết nối database sử dụng UTF-8
$pdo->exec("SET NAMES utf8mb4");
$pdo->exec("SET CHARACTER SET utf8mb4");

// Xử lý đúng HTML entities
function safe_content($text) {
    // Decode entities trước, sau đó encode lại để tránh double encoding
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Sử dụng hàm này khi hiển thị nội dung
echo '<div class="guide-content">' . safe_content($article['content']) . '</div>';

// Đối với nội dung HTML, sử dụng thư viện HTML Purifier hoặc method an toàn
if ($allowHtml) {
    // Nếu cho phép HTML, sử dụng HTML Purifier hoặc filter theo whitelist tags
    $content = filter_html($article['content']);
    echo $content;
} else {
    echo nl2br(htmlspecialchars($article['content']));
}
```
