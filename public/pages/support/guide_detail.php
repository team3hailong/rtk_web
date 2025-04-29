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
<style>
.guide-detail-container { max-width: 800px; margin: 32px auto 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 16px rgba(39,174,96,0.07); padding: 32px 28px; }
.guide-title { font-size: 2rem; font-weight: 700; color: #111; margin-bottom: 10px; }
.guide-meta { color: #888; font-size: 1.05em; margin-bottom: 18px; }
.guide-thumbnail { width: 100%; max-width: 350px; height: auto; border-radius: 10px; margin-bottom: 18px; box-shadow: 0 2px 8px rgba(39,174,96,0.10); }
.guide-content { color: #111; font-size: 1.13em; line-height: 1.7; }
@media (max-width: 900px) { .guide-detail-container { padding: 18px 6vw; } }
</style>
<div class="dashboard-wrapper">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>
    <main class="content-wrapper">
        <?php if (!$article): ?>
            <div class="guide-detail-container">
                <div style="color: #c00; font-size:1.2em;">Không tìm thấy bài viết hoặc bài viết đã bị ẩn.</div>
                <a href="guide.php" style="color: #27ae60; text-decoration: underline;">&larr; Quay lại danh sách bài viết</a>
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
                <div style="margin-top:32px;"><a href="guide.php" style="color: #27ae60; text-decoration: underline;">&larr; Quay lại danh sách bài viết</a></div>
            </div>
        <?php endif; ?>
    </main>
</div>
<?php
if (isset($db)) $db->close();
include $project_root_path . '/private/includes/footer.php';
?>
