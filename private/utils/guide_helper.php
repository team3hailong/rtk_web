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
 * @param int $page Current page number
 * @param int $items_per_page Number of items per page
 * @return array List of guide articles with pagination data
 */
function get_filtered_guide_articles($pdo, $keyword = null, $topic = null, $page = 1, $items_per_page = 5) {
    $sql = "SELECT * FROM guide WHERE status = 'published'";
    $count_sql = "SELECT COUNT(*) FROM guide WHERE status = 'published'";
    $params = [];
    
    if ($keyword) {
        $sql .= " AND (title LIKE :kw1 OR content LIKE :kw2)";
        $count_sql .= " AND (title LIKE :kw1 OR content LIKE :kw2)";
        $params[':kw1'] = "%$keyword%";
        $params[':kw2'] = "%$keyword%";
    }
    
    if ($topic) {
        $sql .= " AND topic = :topic";
        $count_sql .= " AND topic = :topic";
        $params[':topic'] = $topic;
    }
    
    // Count total matching records for pagination
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_items = $count_stmt->fetchColumn();
    
    // Calculate pagination variables
    $total_pages = ceil($total_items / $items_per_page);
    $page = max(1, min($page, $total_pages)); // Ensure page is between 1 and total_pages
    $offset = ($page - 1) * $items_per_page;
    
    // Get paginated results
    $sql .= " ORDER BY published_at DESC, created_at DESC LIMIT :offset, :limit";
    $stmt = $pdo->prepare($sql);
    
    // Bind pagination parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    
    $stmt->execute();
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'articles' => $articles,
        'pagination' => [
            'total_items' => $total_items,
            'items_per_page' => $items_per_page,
            'current_page' => $page,
            'total_pages' => $total_pages
        ]
    ];
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
