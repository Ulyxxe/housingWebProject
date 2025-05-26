<?php
// public/admin_manage_news.php
session_start();
require_once __DIR__ . '/../config/config.php'; // $pdo

// Admin protection
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['login_error'] = "Admin access required.";
    header('Location: login.php');
    exit;
}

$pageTitle = "Admin: Manage News Articles - CROUS-X";
$isLoggedIn = true;

// --- Filter and Sort Options ---
$filter_status = $_GET['status'] ?? 'all'; // 'all', 'published', 'draft', 'archived'
$sort_order = $_GET['sort'] ?? 'newest_published'; // e.g., 'newest_published', 'newest_created', 'title_asc'

$news_articles = [];
$where_clauses = [];
$params = []; // For prepared statement if needed

if ($filter_status !== 'all') {
    $where_clauses[] = "na.status = :status_filter";
    $params[':status_filter'] = $filter_status;
}

$sql_where = "";
if (!empty($where_clauses)) {
    $sql_where = "WHERE " . implode(" AND ", $where_clauses);
}

$sql_order = "ORDER BY na.published_at DESC, na.created_at DESC"; // Default
switch ($sort_order) {
    case 'oldest_published':
        $sql_order = "ORDER BY na.published_at ASC, na.created_at ASC";
        break;
    case 'newest_created':
        $sql_order = "ORDER BY na.created_at DESC";
        break;
    case 'oldest_created':
        $sql_order = "ORDER BY na.created_at ASC";
        break;
    case 'title_asc':
        $sql_order = "ORDER BY na.title ASC";
        break;
    case 'title_desc':
        $sql_order = "ORDER BY na.title DESC";
        break;
}


try {
    $sql = "SELECT na.*, 
                   u.username AS author_username
            FROM news_articles na
            LEFT JOIN users u ON na.user_id = u.user_id
            $sql_where
            $sql_order";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
       $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $news_articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Admin Manage News Error: " . $e->getMessage());
    $page_error_message = "Could not retrieve news articles due to a database error.";
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark" data-accent-color="crous-pink-primary">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="icon" type="image/png" href="assets/images/icon.png">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="css/admin_panel.css">
    <!-- Specific styles for filter controls are in admin_panel.css or can be added here -->
</head>
<body>
    <?php require 'header.php'; ?>

    <main class="app-container dashboard-page-wrapper">
        <div class="dashboard-header-bar" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <h1 class="page-main-heading" style="margin-bottom:0;">Manage News Articles</h1>
            <a href="admin_add_news.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Article</a>
        </div>

        <?php if (isset($_SESSION['admin_message'])): ?>
            <div class="form-message <?php echo ($_SESSION['admin_message_type'] ?? 'success') === 'success' ? 'success-message' : 'error-message'; ?>" style="margin-top: 1rem;">
                <i class="fas <?php echo ($_SESSION['admin_message_type'] ?? 'success') === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($_SESSION['admin_message']); ?>
            </div>
            <?php unset($_SESSION['admin_message'], $_SESSION['admin_message_type']); ?>
        <?php endif; ?>

        <?php if (isset($page_error_message)): ?>
            <div class="form-message error-message" style="margin-top: 1rem;">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($page_error_message); ?>
            </div>
        <?php endif; ?>

        <form method="GET" action="admin_manage_news.php" class="filter-controls">
            <label for="status_filter">Filter Status:</label>
            <select name="status" id="status_filter" class="form-control" style="width: auto;">
                <option value="all" <?php echo ($filter_status === 'all') ? 'selected' : ''; ?>>All</option>
                <option value="published" <?php echo ($filter_status === 'published') ? 'selected' : ''; ?>>Published</option>
                <option value="draft" <?php echo ($filter_status === 'draft') ? 'selected' : ''; ?>>Draft</option>
                <option value="archived" <?php echo ($filter_status === 'archived') ? 'selected' : ''; ?>>Archived</option>
            </select>
            <label for="sort_filter">Sort by:</label>
            <select name="sort" id="sort_filter" class="form-control" style="width: auto;">
                <option value="newest_published" <?php echo ($sort_order === 'newest_published') ? 'selected' : ''; ?>>Newest Published</option>
                <option value="oldest_published" <?php echo ($sort_order === 'oldest_published') ? 'selected' : ''; ?>>Oldest Published</option>
                <option value="newest_created" <?php echo ($sort_order === 'newest_created') ? 'selected' : ''; ?>>Newest Created</option>
                <option value="oldest_created" <?php echo ($sort_order === 'oldest_created') ? 'selected' : ''; ?>>Oldest Created</option>
                <option value="title_asc" <?php echo ($sort_order === 'title_asc') ? 'selected' : ''; ?>>Title (A-Z)</option>
                <option value="title_desc" <?php echo ($sort_order === 'title_desc') ? 'selected' : ''; ?>>Title (Z-A)</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Apply Filters</button>
        </form>

        <?php if (empty($news_articles) && !isset($page_error_message)): ?>
            <div class="info-message-display no-news" style="margin-top: 1rem; padding: 2rem;">
                <i class="fas fa-newspaper fa-2x" style="margin-bottom: 1rem;"></i>
                <p>No news articles found matching the current criteria. <a href="admin_add_news.php">Add one now!</a></p>
            </div>
        <?php elseif (!empty($news_articles)): ?>
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Featured</th>
                            <th>Published</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($news_articles as $article): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($article['article_id']); ?></td>
                                <td>
                                    <a href="news-article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>" target="_blank" title="View Article: <?php echo htmlspecialchars($article['title']); ?>">
                                        <?php echo htmlspecialchars(mb_strimwidth($article['title'], 0, 40, "...")); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($article['author_username'] ?? 'System'); ?></td>
                                <td><?php echo htmlspecialchars($article['category'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="listing-status-badge status-<?php echo htmlspecialchars($article['status']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($article['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo $article['is_featured'] ? '<i class="fas fa-star" style="color: gold;"></i> Yes' : 'No'; ?></td>
                                <td><?php echo $article['published_at'] ? date("d M Y", strtotime($article['published_at'])) : 'N/A'; ?></td>
                                <td><?php echo date("d M Y", strtotime($article['created_at'])); ?></td>
                                <td class="actions">
                                    <a href="admin_edit_news.php?id=<?php echo $article['article_id']; ?>" title="Edit Article"><i class="fas fa-edit"></i></a>
                                    
                                    <?php if ($article['status'] !== 'published'): ?>
                                        <a href="admin_action_news.php?action=publish&id=<?php echo $article['article_id']; ?>" title="Publish Article"><i class="fas fa-upload" style="color: #5cb85c;"></i></a>
                                    <?php else: ?>
                                        <a href="admin_action_news.php?action=unpublish&id=<?php echo $article['article_id']; ?>" title="Unpublish (Set to Draft)"><i class="fas fa-download" style="color: #f0ad4e;"></i></a>
                                    <?php endif; ?>
                                    
                                    <a href="admin_action_news.php?action=delete&id=<?php echo $article['article_id']; ?>" 
                                       onclick="return confirm('Are you sure you want to delete this news article PERMANENTLY?');" 
                                       title="Delete Article" class="delete-link"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>

    <?php require 'chat-widget.php'; ?>
    <script src="script.js" defer></script>
    <script src="chatbot.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusBadges = document.querySelectorAll('.listing-status-badge'); // Reusing class
            statusBadges.forEach(badge => {
                badge.style.color = 'white';
                badge.style.padding = '0.25em 0.6em';
                badge.style.fontSize = '0.8em';
                badge.style.borderRadius = '4px';
                badge.style.textTransform = 'capitalize';

                if (badge.classList.contains('status-published')) {
                    badge.style.backgroundColor = '#5cb85c'; // Green
                } else if (badge.classList.contains('status-draft')) {
                    badge.style.backgroundColor = '#f0ad4e'; // Orange
                    badge.style.color = '#333';
                } else if (badge.classList.contains('status-archived')) {
                    badge.style.backgroundColor = '#777'; // Grey
                } else {
                    badge.style.backgroundColor = '#aaa'; 
                }
            });
        });
    </script>
</body>
</html>