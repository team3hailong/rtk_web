<?php
session_start();

// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';

// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$project_root_path = PROJECT_ROOT_PATH;
$admin_site = ADMIN_SITE;

require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/utils/guide_helper.php';

// Khởi tạo kết nối database
$db = new Database();
$pdo = $db->getConnection();

// Xử lý filter và phân trang
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$topic = isset($_GET['topic']) ? trim($_GET['topic']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 5; // Số bài viết hiển thị trên mỗi trang

// Lấy dữ liệu từ helper
$topics = get_guide_topics($pdo);
$result = get_filtered_guide_articles($pdo, $keyword, $topic, $page, $items_per_page);
$articles = $result['articles'];
$pagination = $result['pagination'];

include $project_root_path . '/private/includes/header.php';
?>
<link rel="stylesheet" href="<?php echo $base_url; ?>/public/assets/css/pages/map.css" />
<link rel="stylesheet" href="<?php echo $base_url; ?>/public/assets/css/pages/support/guide.css" />

<div class="dashboard-wrapper">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>
    <main class="content-wrapper">
        <h2 class="text-2xl font-semibold mb-4">Hướng dẫn sử dụng</h2>
        <form class="guide-search-form" method="get">
            <div>
                <label for="topic">Chủ đề</label>
                <select name="topic" id="topic">
                    <option value="">-- Tất cả --</option>
                    <?php foreach ($topics as $t): ?>
                        <option value="<?php echo htmlspecialchars($t); ?>" <?php if ($topic === $t) echo 'selected'; ?>><?php echo htmlspecialchars($t); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="keyword">Từ khóa</label>
                <input type="text" name="keyword" id="keyword" placeholder="Nhập từ khóa..." value="<?php echo htmlspecialchars($keyword); ?>">
            </div>            <button type="submit">Tìm kiếm</button>
            <button type="button" onclick="window.location.href='guide.php'" class="reset-button">Reset</button>
        </form>
        <div class="guide-list">
            <?php if (empty($articles)): ?>
                <div class="no-results">Không có bài viết nào phù hợp.</div>
            <?php else: ?>
                <?php foreach ($articles as $article): ?>
                    <div class="guide-item" tabindex="0" onclick="window.location.href='guide_detail.php?slug=<?php echo urlencode($article['slug']); ?>'">
                        <div class="guide-item-content">
                            <?php if (!empty($article['thumbnail'])): ?>
                                <img class="guide-thumb"
                                     src="<?php echo $admin_site   . '/public/uploads/guide/' . basename($article['thumbnail']); ?>"
                                     alt="Thumbnail">
                            <?php endif; ?>
                            <div class="guide-item-text">
                                <div class="guide-title">
                                    <a href="guide_detail.php?slug=<?php echo urlencode($article['slug']); ?>">
                                        <?php echo htmlspecialchars($article['title']); ?>
                                    </a>
                                </div>
                                <div class="guide-meta">
                                    <?php if (!empty($article['topic'])): ?>
                                        <span>Chủ đề: <?php echo htmlspecialchars($article['topic']); ?></span> |
                                    <?php endif; ?>
                                    <span>Ngày đăng: <?php echo date('d/m/Y', strtotime($article['created_at'])); ?></span>
                                </div>
                                <div class="guide-summary">
                                    <?php
                                    // decode HTML entities, strip tags, then truncate and escape
                                    $text = html_entity_decode(strip_tags($article['content']), ENT_QUOTES, 'UTF-8');
                                    echo htmlspecialchars(mb_substr($text, 0, 120), ENT_QUOTES, 'UTF-8')
                                         . (mb_strlen($text) > 120 ? '...' : '');
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="pagination-container">
            <div class="pagination">
                <?php
                // Build the query string for pagination links
                $query_params = [];
                if (!empty($keyword)) $query_params['keyword'] = $keyword;
                if (!empty($topic)) $query_params['topic'] = $topic;
                $query_string = !empty($query_params) ? '&' . http_build_query($query_params) : '';
                
                // Previous page link
                if ($pagination['current_page'] > 1): ?>
                    <a href="?page=<?php echo $pagination['current_page'] - 1 . $query_string; ?>" class="pagination-link">&laquo; Trước</a>
                <?php endif; ?>
                
                <?php
                // Calculate range of page numbers to show
                $range = 2; // Number of pages to show before and after current page
                $start_page = max(1, $pagination['current_page'] - $range);
                $end_page = min($pagination['total_pages'], $pagination['current_page'] + $range);
                
                // Always show first page link
                if ($start_page > 1): ?>
                    <a href="?page=1<?php echo $query_string; ?>" class="pagination-link">1</a>
                    <?php if ($start_page > 2): ?>
                        <span class="pagination-ellipsis">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $pagination['current_page']): ?>
                        <span class="pagination-current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i . $query_string; ?>" class="pagination-link"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php
                // Always show last page link
                if ($end_page < $pagination['total_pages']): ?>
                    <?php if ($end_page < $pagination['total_pages'] - 1): ?>
                        <span class="pagination-ellipsis">...</span>
                    <?php endif; ?>
                    <a href="?page=<?php echo $pagination['total_pages'] . $query_string; ?>" class="pagination-link"><?php echo $pagination['total_pages']; ?></a>
                <?php endif; ?>
                
                <?php
                // Next page link
                if ($pagination['current_page'] < $pagination['total_pages']): ?>
                    <a href="?page=<?php echo $pagination['current_page'] + 1 . $query_string; ?>" class="pagination-link">Tiếp &raquo;</a>
                <?php endif; ?>
            </div>
            <div class="pagination-info">
                Trang <?php echo $pagination['current_page']; ?> / <?php echo $pagination['total_pages']; ?> 
                (<?php echo $pagination['total_items']; ?> bài viết)
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>
<?php
if (isset($db)) $db->close();
include $project_root_path . '/private/includes/footer.php';
?>
