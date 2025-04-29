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
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$article = null;
if ($slug) {
    $stmt = $pdo->prepare("SELECT * FROM guide WHERE slug = :slug AND status = 'published' LIMIT 1");
    $stmt->execute([':slug' => $slug]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
}
include $project_root_path . '/private/includes/header.php';
?>
<link rel="stylesheet" href="<?php echo $base_url; ?>/public/assets/css/pages/map.css" />
<link rel="stylesheet" href="<?php echo $base_url; ?>/public/assets/css/pages/support/guide_detail.css" />

<div class="dashboard-wrapper">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>
    <main class="content-wrapper">
        <?php if (!$article): ?>
            <div class="guide-detail-container">
                <div class="guide-error">Không tìm thấy bài viết hoặc bài viết đã bị ẩn.</div>
                <a href="guide.php" class="guide-back-link">&larr; Quay lại danh sách bài viết</a>
            </div>
        <?php else: ?>
            <div class="guide-detail-container">
                <div class="guide-title"><?php echo htmlspecialchars($article['title']); ?></div>
                <div class="guide-meta">
                    <?php if (!empty($article['topic'])): ?>
                        <span>Chủ đề: <?php echo htmlspecialchars($article['topic']); ?></span> |
                    <?php endif; ?>
                    <span>Ngày đăng: <?php echo date('d/m/Y', strtotime($article['created_at'])); ?></span>
                </div>
                <?php if (!empty($article['thumbnail'])): ?>
                    <img class="guide-thumbnail" src="<?php echo htmlspecialchars($article['thumbnail']); ?>" alt="Thumbnail">
                <?php endif; ?>
                <div class="guide-content"><?php echo $article['content']; ?></div>
                <div class="guide-back-container"><a href="guide.php" class="guide-back-link">&larr; Quay lại danh sách bài viết</a></div>
            </div>
        <?php endif; ?>
    </main>
</div>
<?php
if (isset($db)) $db->close();
include $project_root_path . '/private/includes/footer.php';
?>
