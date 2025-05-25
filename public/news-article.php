<?php
// public/news-article.php
session_start();
require_once __DIR__ . '/../config/config.php'; // Database connection ($pdo)

$isLoggedIn = isset($_SESSION['user_id']);
$article = null;
$article_slug = $_GET['slug'] ?? null;

if (!$article_slug) {
    // Redirect to news listing if no slug is provided, or show a 404
    header("Location: news.php");
    exit;
}

try {
    $sql = "SELECT na.*, 
                   u.username AS author_username, u.first_name AS author_first_name, u.last_name AS author_last_name
            FROM news_articles na
            LEFT JOIN users u ON na.user_id = u.user_id
            WHERE na.slug = :slug AND na.status = 'published'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':slug', $article_slug, PDO::PARAM_STR);
    $stmt->execute();
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$article) {
        // Article not found or not published, could show a 404 page
        // For now, redirect to news.php with an error message
        $_SESSION['news_page_message'] = "The requested news article was not found.";
        $_SESSION['news_page_message_type'] = "error";
        header("Location: news.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("News Article Page Error: " . $e->getMessage());
    // Redirect to news.php with an error message
    $_SESSION['news_page_message'] = "An error occurred while trying to load the article.";
    $_SESSION['news_page_message_type'] = "error";
    header("Location: news.php");
    exit;
}

$pageTitle = htmlspecialchars($article['title']) . " - CROUS-X News";

?>
<!DOCTYPE html>
<html lang="en" data-theme="dark" data-accent-color="crous-pink-primary">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="icon" type="image/png" href="assets/images/icon.png">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/news.css"> <!-- Reusing news.css -->
</head>
<body>
    <?php require 'header.php'; ?>

    <main class="app-container news-article-page-wrapper">
        <div class="article-content-area">
            <p class="back-to-news-link">
                <a href="news.php"><i class="fas fa-arrow-left"></i> Back to News Stand</a>
            </p>

            <article class="news-article-full">
                <header class="article-header">
                    <?php if (!empty($article['category'])): ?>
                        <span class="article-category-tag"><?php echo htmlspecialchars($article['category']); ?></span>
                    <?php endif; ?>
                    <h1 class="article-main-title"><?php echo htmlspecialchars($article['title']); ?></h1>
                    <p class="article-meta-info">
                        <?php 
                        $authorName = trim(htmlspecialchars($article['author_first_name'] ?? '') . ' ' . htmlspecialchars($article['author_last_name'] ?? ''));
                        $displayName = $authorName ?: htmlspecialchars($article['author_username'] ?? 'CROUS-X Team');
                        ?>
                        By <?php echo $displayName; ?>
                        <span class="meta-separator">•</span>
                        Published on <?php echo htmlspecialchars(date("F j, Y", strtotime($article['published_at'] ?? $article['created_at']))); ?>
                    </p>
                </header>

                <?php if (!empty($article['cover_image_url'])): ?>
                    <figure class="article-cover-image-container">
                        <img src="<?php echo htmlspecialchars($article['cover_image_url']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="article-cover-image">
                    </figure>
                <?php endif; ?>

                <div class="article-body-content">
                    <?php
                    // If storing HTML directly in DB (use with caution, ensure sanitization on input)
                    // echo $article['content']; 
                    
                    // If storing Markdown, you would parse it here:
                    // require_once 'path/to/Parsedown.php'; // Or your Markdown library
                    // $Parsedown = new Parsedown();
                    // echo $Parsedown->text($article['content']);

                    // For plain text with newlines preserved:
                    echo nl2br(htmlspecialchars($article['content'])); 
                    ?>
                </div>

                <?php if (!empty($article['tags'])): ?>
                    <footer class="article-footer">
                        <div class="article-tags">
                            <strong>Tags:</strong>
                            <?php 
                            $tags = explode(',', $article['tags']);
                            foreach ($tags as $index => $tag) {
                                echo htmlspecialchars(trim($tag)) . ($index < count($tags) - 1 ? ', ' : '');
                            }
                            ?>
                        </div>
                    </footer>
                <?php endif; ?>
            </article>

            <!-- Optional: Related articles or comments section could go here -->

        </div>
    </main>

    <?php require 'chat-widget.php'; ?>
    <script src="script.js" defer></script>
    <script src="chatbot.js" defer></script>
</body>
</html>