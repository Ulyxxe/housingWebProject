<?php
// public/news.php
session_start();
require_once __DIR__ . '/../config/config.php'; // Database connection ($pdo)

$isLoggedIn = isset($_SESSION['user_id']);
$pageTitle = "News Stand - CROUS-X";

// --- Fetch Published News Articles ---
$articlesPerPage = 10; // Configure how many articles to show per page
$currentPage = isset($_GET['page']) && filter_var($_GET['page'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $articlesPerPage;

$news_articles = [];
$totalArticles = 0;

try {
    // Get total count of published articles for pagination
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM news_articles WHERE status = 'published'");
    $stmtCount->execute();
    $totalArticles = (int)$stmtCount->fetchColumn();

    // Fetch articles for the current page
    $sql = "SELECT na.article_id, na.title, na.slug, na.summary, na.cover_image_url, na.published_at, na.category,
                   u.username AS author_username, u.first_name AS author_first_name, u.last_name AS author_last_name
            FROM news_articles na
            LEFT JOIN users u ON na.user_id = u.user_id
            WHERE na.status = 'published'
            ORDER BY na.published_at DESC, na.created_at DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':limit', $articlesPerPage, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $news_articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("News Page Error: " . $e->getMessage());
    // You could set an error message to display to the user
    $news_error_message = "Could not retrieve news articles at this time. Please try again later.";
}

$totalPages = ceil($totalArticles / $articlesPerPage);

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
    <link rel="stylesheet" href="css/news.css"> <!-- We will create this CSS file -->
</head>
<body>
    <?php require 'header.php'; ?>

    <main class="app-container news-page-wrapper">
        <div class="news-header-bar">
            <h1 class="page-main-heading" data-i18n-key="news_stand_title">News Stand</h1>
            <!-- Optional: Filters or search for news could go here -->
        </div>

        <?php if (isset($news_error_message)): ?>
            <div class="form-message error-message" style="margin-bottom: 2rem;">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($news_error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($news_articles) && !isset($news_error_message)): ?>
            <div class="info-message-display no-news">
                <i class="fas fa-newspaper"></i>
                <p data-i18n-key="no_news_articles_found">No news articles found at the moment. Check back soon!</p>
            </div>
        <?php else: ?>
            <div class="news-grid">
                <?php foreach ($news_articles as $article): ?>
                    <article class="news-card card">
                        <?php if (!empty($article['cover_image_url'])): ?>
                            <a href="news-article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>" class="news-card-image-link">
                                <img src="<?php echo htmlspecialchars($article['cover_image_url']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="news-card-image card-image-top" loading="lazy">
                            </a>
                        <?php else: ?>
                             <a href="news-article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>" class="news-card-image-link">
                                <div class="card-image-placeholder news-card-image-placeholder card-image-top">
                                    <i class="far fa-newspaper"></i>
                                </div>
                            </a>
                        <?php endif; ?>
                        <div class="card-body">
                            <?php if (!empty($article['category'])): ?>
                                <span class="news-card-category"><?php echo htmlspecialchars($article['category']); ?></span>
                            <?php endif; ?>
                            <h3 class="news-card-title card-title">
                                <a href="news-article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>">
                                    <?php echo htmlspecialchars($article['title']); ?>
                                </a>
                            </h3>
                            <p class="news-card-meta">
                                <?php 
                                $authorName = trim(htmlspecialchars($article['author_first_name'] ?? '') . ' ' . htmlspecialchars($article['author_last_name'] ?? ''));
                                $displayName = $authorName ?: htmlspecialchars($article['author_username'] ?? 'CROUS-X Team');
                                ?>
                                By <?php echo $displayName; ?>
                                <span class="meta-separator">•</span>
                                <?php echo htmlspecialchars(date("F j, Y", strtotime($article['published_at'] ?? $article['created_at']))); ?>
                            </p>
                            <?php if (!empty($article['summary'])): ?>
                                <p class="news-card-summary card-text"><?php echo nl2br(htmlspecialchars($article['summary'])); ?></p>
                            <?php endif; ?>
                            <a href="news-article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>" class="btn btn-link news-read-more" data-i18n-key="read_more_button">Read More <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="pagination-nav" aria-label="News articles navigation">
                    <ul class="pagination">
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item"><a class="page-link" href="news.php?page=<?php echo $currentPage - 1; ?>">« Previous</a></li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                                <a class="page-link" href="news.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item"><a class="page-link" href="news.php?page=<?php echo $currentPage + 1; ?>">Next »</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php endif; ?>
    </main>

    <?php require 'chat-widget.php'; ?>
    <script src="script.js" defer></script>
    <script src="chatbot.js" defer></script>
</body>
</html>