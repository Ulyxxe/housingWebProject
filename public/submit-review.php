<?php
// public/submit_review.php
session_start();
require_once __DIR__ . '/../config/config.php'; // Database connection ($pdo)

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['review_message'] = 'You must be logged in to submit a review.';
    $_SESSION['review_message_type'] = 'error';
    // Attempt to redirect back to the referring page if possible, otherwise home
    $redirect_url = $_SERVER['HTTP_REFERER'] ?? 'home.php';
    header("Location: " . $redirect_url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $listing_id = filter_input(INPUT_POST, 'listing_id', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id']; // Already an integer from session
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $review_title = trim($_POST['review_title'] ?? '');
    $comment = trim($_POST['comment'] ?? '');

    $errors = [];

    if (!$listing_id) {
        $errors[] = "Invalid listing ID.";
    }
    if (!$rating || $rating < 1 || $rating > 5) {
        $errors[] = "Please select a rating between 1 and 5 stars.";
    }
    if (empty($comment)) {
        $errors[] = "Comment cannot be empty.";
    }
    if (strlen($comment) > 2000) { // Max comment length
        $errors[] = "Comment is too long (max 2000 characters).";
    }
    if (!empty($review_title) && strlen($review_title) > 150) {
        $errors[] = "Review title is too long (max 150 characters).";
    }

    // Construct redirect URL before potential errors clear it
    $redirect_to = $listing_id ? "housing-detail.php?id=" . $listing_id : "home.php";

    if (empty($errors)) {
        try {
            // Optional: Check if user has already reviewed this listing
            // $stmt_check = $pdo->prepare("SELECT review_id FROM reviews WHERE listing_id = :listing_id AND user_id = :user_id");
            // $stmt_check->execute(['listing_id' => $listing_id, 'user_id' => $user_id]);
            // if ($stmt_check->fetch()) {
            //     $_SESSION['review_message'] = 'You have already reviewed this listing.';
            //     $_SESSION['review_message_type'] = 'error';
            //     header("Location: " . $redirect_to);
            //     exit;
            // }

            $sql = "INSERT INTO reviews (listing_id, user_id, rating, title, comment, is_approved, review_date) 
                    VALUES (:listing_id, :user_id, :rating, :title, :comment, 0, NOW())"; // is_approved = 0 (FALSE) for moderation
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':listing_id', $listing_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
            $stmt->bindParam(':title', $review_title); // PDO::PARAM_STR by default
            $stmt->bindParam(':comment', $comment);   // PDO::PARAM_STR by default

            if ($stmt->execute()) {
                $_SESSION['review_message'] = 'Your review has been submitted and is awaiting approval. Thank you!';
                $_SESSION['review_message_type'] = 'success';
            } else {
                $_SESSION['review_message'] = 'Failed to submit your review. Please try again.';
                $_SESSION['review_message_type'] = 'error';
            }
        } catch (PDOException $e) {
            error_log("Review Submission Error: " . $e->getMessage());
            $_SESSION['review_message'] = 'A database error occurred. Could not submit review.';
            $_SESSION['review_message_type'] = 'error';
        }
    } else {
        // Store errors and form data to display back on the form
        $_SESSION['review_message'] = implode("<br>", $errors);
        $_SESSION['review_message_type'] = 'error';
        $_SESSION['form_data'] = $_POST; // Re-populate form
    }

    header("Location: " . $redirect_to);
    exit;

} else {
    // Not a POST request, redirect to home or previous page
    header("Location: home.php");
    exit;
}
?>