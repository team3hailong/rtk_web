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

// Xử lý filter
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$topic = isset($_GET['topic']) ? trim($_GET['topic']) : '';

// Lấy dữ liệu từ helper
$topics = get_guide_topics($pdo);
$articles = get_filtered_guide_articles($pdo, $keyword, $topic);
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
    </main>
</div>
<?php
if (isset($db)) $db->close();
include $project_root_path . '/private/includes/footer.php';
?>
