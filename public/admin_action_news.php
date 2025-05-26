<?php
// public/admin_action_news.php
session_start();
require_once __DIR__ . '/../config/config.php'; // $pdo

// Admin protection
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$action = $_GET['action'] ?? null;
$article_id = isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT) ? (int)$_GET['id'] : null;

if (!$article_id || !in_array($action, ['publish', 'unpublish', 'delete'])) {
    $_SESSION['admin_message'] = "Invalid action or news article ID.";
    $_SESSION['admin_message_type'] = "error";
    header('Location: admin_manage_news.php');
    exit;
}

// CSRF Protection (Highly Recommended) - Add token check here

try {
    if ($action === 'publish') {
        // When publishing, set status to 'published' and published_at to NOW() if it's not already set
        $stmt = $pdo->prepare("UPDATE news_articles 
                               SET status = 'published', 
                                   published_at = COALESCE(published_at, NOW()), 
                                   updated_at = NOW() 
                               WHERE article_id = :article_id");
        $stmt->bindParam(':article_id', $article_id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "Article ID: $article_id has been published.";
            $_SESSION['admin_message_type'] = "success";
        } else {
            $_SESSION['admin_message'] = "Failed to publish article ID: $article_id.";
            $_SESSION['admin_message_type'] = "error";
        }
    } elseif ($action === 'unpublish') { // Set to draft
        $stmt = $pdo->prepare("UPDATE news_articles SET status = 'draft', updated_at = NOW() WHERE article_id = :article_id");
        // Note: We are not nullifying published_at here, it keeps its original published date if it had one.
        // You might choose to set published_at = NULL if you want it to reset when re-published.
        $stmt->bindParam(':article_id', $article_id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "Article ID: $article_id has been unpublished (set to draft).";
            $_SESSION['admin_message_type'] = "success";
        } else {
            $_SESSION['admin_message'] = "Failed to unpublish article ID: $article_id.";
            $_SESSION['admin_message_type'] = "error";
        }
    } elseif ($action === 'delete') {
        // Optional: Get cover_image_url to delete file if it's a local file
        $stmt_get_img = $pdo->prepare("SELECT cover_image_url FROM news_articles WHERE article_id = :article_id");
        $stmt_get_img->bindParam(':article_id', $article_id, PDO::PARAM_INT);
        $stmt_get_img->execute();
        $cover_image_to_delete = $stmt_get_img->fetchColumn();

        $stmt = $pdo->prepare("DELETE FROM news_articles WHERE article_id = :article_id");
        $stmt->bindParam(':article_id', $article_id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            // If cover_image_url points to a local file and you have an upload system
            if ($cover_image_to_delete && strpos($cover_image_to_delete, 'assets/uploads/news_images/') === 0) {
                $filename = basename($cover_image_to_delete);
                $file_path = __DIR__ . '/assets/uploads/news_images/' . $filename;
                if (file_exists($file_path) && is_writable($file_path)) {
                    @unlink($file_path);
                }
            }
            $_SESSION['admin_message'] = "Article ID: $article_id has been deleted.";
            $_SESSION['admin_message_type'] = "success";
        } else {
            $_SESSION['admin_message'] = "Failed to delete article ID: $article_id.";
            $_SESSION['admin_message_type'] = "error";
        }
    }
} catch (PDOException $e) {
    error_log("Admin Action News Error: " . $e->getMessage());
    $_SESSION['admin_message'] = "Database error performing action on news article: " . htmlspecialchars($e->getMessage());
    $_SESSION['admin_message_type'] = "error";
}

// Redirect back to the news management page, preserving filters if possible
$redirect_params = [];
if(isset($_GET['status_filter_prev'])) $redirect_params['status'] = $_GET['status_filter_prev'];
if(isset($_GET['sort_filter_prev'])) $redirect_params['sort'] = $_GET['sort_filter_prev'];
$query_string = http_build_query($redirect_params);

header('Location: admin_manage_news.php' . ($query_string ? '?' . $query_string : ''));
exit;
?>