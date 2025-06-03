<?php
/**
 * Helper functions for guide system
 * 
 * Contains functions to fetch guide articles, topics, and handle filtering
 */

/**
 * Get list of distinct guide topics
 * 
 * @param PDO $pdo Database connection
 * @return array List of topics
 */
function get_guide_topics($pdo) {
    $topic_stmt = $pdo->query("SELECT DISTINCT topic FROM guide WHERE topic IS NOT NULL AND topic != ''");
    return $topic_stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Get filtered guide articles
 * 
 * @param PDO $pdo Database connection
 * @param string|null $keyword Search keyword
 * @param string|null $topic Filter by topic
 * @return array List of guide articles
 */
function get_filtered_guide_articles($pdo, $keyword = null, $topic = null) {
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
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get a specific guide article by slug
 * 
 * @param PDO $pdo Database connection
 * @param string $slug Article slug
 * @return array|false Article data or false if not found
 */
function get_guide_article_by_slug($pdo, $slug) {
    $stmt = $pdo->prepare("SELECT * FROM guide WHERE slug = :slug AND status = 'published'");
    $stmt->execute([':slug' => $slug]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
