<?php
session_start();

// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';

// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$project_root_path = PROJECT_ROOT_PATH;

require_once $project_root_path . '/private/classes/Database.php';
$db = new Database();
$pdo = $db->getConnection();

// Xử lý filter
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$topic = isset($_GET['topic']) ? trim($_GET['topic']) : '';

// Lấy danh sách chủ đề
$topic_stmt = $pdo->query("SELECT DISTINCT topic FROM guide WHERE topic IS NOT NULL AND topic != ''");
$topics = $topic_stmt->fetchAll(PDO::FETCH_COLUMN);

// Xây dựng query lấy bài viết
$sql = "SELECT * FROM guide WHERE status = 'published'";
$params = [];
if ($keyword) {
    $sql .= " AND (title LIKE :kw1 OR content LIKE :kw2)";
    $params[':kw1'] = "%$keyword%";
    $params[':kw2'] = "%$keyword%";
}
if ($topic) {
    $sql .= " AND topic = :topic";
    $params[':topic'] = $topic;
}
$sql .= " ORDER BY published_at DESC, created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            </div>
            <button type="submit">Tìm kiếm</button>
            <a href="guide.php" class="reset-button">Reset</a>
        </form>
        <div class="guide-list">
            <?php if (empty($articles)): ?>
                <div class="no-results">Không có bài viết nào phù hợp.</div>
            <?php else: ?>
                <?php foreach ($articles as $article): ?>
                    <div class="guide-item" tabindex="0" onclick="window.location.href='guide_detail.php?slug=<?php echo urlencode($article['slug']); ?>'">
                        <div class="guide-item-content">
                            <?php if (!empty($article['thumbnail'])): ?>
                                <img class="guide-thumb" src="<?php echo htmlspecialchars($article['thumbnail']); ?>" alt="Thumbnail">
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
                                <div class="guide-summary"><?php echo htmlspecialchars(mb_substr(strip_tags($article['content']),0,120)) . (mb_strlen(strip_tags($article['content']))>120?'...':''); ?></div>
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
