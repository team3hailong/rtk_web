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
<style>
:root {
  --main-green: #27ae60;
  --main-green-dark: #219150;
  --main-green-light: #eafaf1;
}
.guide-list { margin-top: 24px; }
.guide-item { border: 1px solid #d4ecd8; border-radius: 10px; margin-bottom: 22px; padding: 18px; background: var(--main-green-light); transition: box-shadow 0.2s, border 0.2s; cursor: pointer; box-shadow: 0 2px 8px rgba(39,174,96,0.04); }
.guide-item:hover { box-shadow: 0 4px 16px rgba(39,174,96,0.10); border-color: var(--main-green); }
.guide-thumb { width: 130px; height: 90px; object-fit: cover; border-radius: 8px; margin-right: 22px; box-shadow: 0 2px 8px rgba(39,174,96,0.08); background: #fff; }
.guide-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 8px; color: #111; }
.guide-summary { color: #111; margin-bottom: 10px; font-size: 1.05em; }
.guide-detail { display: none; margin-top: 14px; border-top: 1px solid #d4ecd8; padding-top: 14px; background: #fff; border-radius: 8px; color: #111; }
.guide-item.active .guide-detail { display: block; }
.guide-item .guide-meta { font-size: 0.98em; color: #888; margin-bottom: 6px; }
.guide-search-form { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 22px; align-items: flex-end; background: var(--main-green-light); padding: 14px 18px; border-radius: 8px; box-shadow: 0 1px 4px rgba(39,174,96,0.04); }
.guide-search-form input, .guide-search-form select { padding: 7px 12px; border: 1px solid #b6e3c6; border-radius: 5px; font-size: 1em; background: #fff; }
.guide-search-form label { font-weight: 600; margin-right: 6px; color: var(--main-green-dark); }
.guide-search-form button { padding: 8px 22px; background: linear-gradient(90deg,var(--main-green) 60%,var(--main-green-dark) 100%); color: #fff; border: none; border-radius: 5px; font-weight: 600; font-size: 1em; box-shadow: 0 2px 8px rgba(39,174,96,0.08); transition: background 0.2s; }
.guide-search-form button:hover { background: linear-gradient(90deg,var(--main-green-dark) 60%,var(--main-green) 100%); }
@media (max-width: 700px) {
  .guide-item { flex-direction: column; }
  .guide-thumb { margin-bottom: 12px; margin-right: 0; width: 100%; height: 120px; }
  .guide-search-form { flex-direction: column; gap: 10px; }
}
</style>
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
            <a href="guide.php" style="padding:8px 22px; background: #eee; color: #111; border-radius:5px; font-weight:600; text-decoration:none; margin-left:8px; border:1px solid #ccc;">Reset</a>
        </form>
        <div class="guide-list">
            <?php if (empty($articles)): ?>
                <div style="color: #888;">Không có bài viết nào phù hợp.</div>
            <?php else: ?>
                <?php foreach ($articles as $article): ?>
                    <div class="guide-item" tabindex="0" onclick="window.location.href='guide_detail.php?slug=<?php echo urlencode($article['slug']); ?>'">
                        <div style="display: flex; align-items: flex-start;">
                            <?php if (!empty($article['thumbnail'])): ?>
                                <img class="guide-thumb" src="<?php echo htmlspecialchars($article['thumbnail']); ?>" alt="Thumbnail">
                            <?php endif; ?>
                            <div style="flex:1;">
                                <div class="guide-title">
                                    <a href="guide_detail.php?slug=<?php echo urlencode($article['slug']); ?>" style="color:#111; text-decoration:none;">
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
