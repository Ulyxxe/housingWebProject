<?php
// public/admin_action_review.php
session_start();
require_once __DIR__ . '/../config/config.php'; // $pdo

// Admin protection
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    // Silently redirect or show a generic error if not admin, to not reveal script existence
    header('Location: login.php');
    exit;
}

$action = $_GET['action'] ?? null;
$review_id = isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT) ? (int)$_GET['id'] : null;

if (!$review_id || !in_array($action, ['approve', 'unapprove', 'delete'])) {
    $_SESSION['admin_message'] = "Invalid action or review ID.";
    $_SESSION['admin_message_type'] = "error";
    header('Location: admin_manage_reviews.php');
    exit;
}

// CSRF Protection (Highly Recommended - implement a proper CSRF token system)
// Add a check here: if (!verify_csrf_token($_GET['token'] ?? '')) { ... exit ... }


try {
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE reviews SET is_approved = 1 WHERE review_id = :review_id");
        $stmt->bindParam(':review_id', $review_id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "Review ID: $review_id has been approved.";
            $_SESSION['admin_message_type'] = "success";
        } else {
            $_SESSION['admin_message'] = "Failed to approve review ID: $review_id.";
            $_SESSION['admin_message_type'] = "error";
        }
    } elseif ($action === 'unapprove') {
        $stmt = $pdo->prepare("UPDATE reviews SET is_approved = 0 WHERE review_id = :review_id");
        $stmt->bindParam(':review_id', $review_id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "Review ID: $review_id has been unapproved (set to pending).";
            $_SESSION['admin_message_type'] = "success";
        } else {
            $_SESSION['admin_message'] = "Failed to unapprove review ID: $review_id.";
            $_SESSION['admin_message_type'] = "error";
        }
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE review_id = :review_id");
        $stmt->bindParam(':review_id', $review_id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "Review ID: $review_id has been deleted.";
            $_SESSION['admin_message_type'] = "success";
        } else {
            $_SESSION['admin_message'] = "Failed to delete review ID: $review_id.";
            $_SESSION['admin_message_type'] = "error";
        }
    }
} catch (PDOException $e) {
    error_log("Admin Action Review Error: " . $e->getMessage());
    $_SESSION['admin_message'] = "Database error performing action on review: " . htmlspecialchars($e->getMessage());
    $_SESSION['admin_message_type'] = "error";
}

// Redirect back to the review management page, preserving filters if possible
$redirect_params = [];
if(isset($_GET['status_filter_prev'])) $redirect_params['status'] = $_GET['status_filter_prev'];
if(isset($_GET['sort_filter_prev'])) $redirect_params['sort'] = $_GET['sort_filter_prev'];
$query_string = http_build_query($redirect_params);

header('Location: admin_manage_reviews.php' . ($query_string ? '?' . $query_string : ''));
exit;
?>