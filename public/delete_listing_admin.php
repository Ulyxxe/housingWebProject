<?php
// public/delete_listing_admin.php
session_start();
require_once __DIR__ . '/../config/config.php'; // $pdo

// Admin protection
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['admin_message'] = "Admin access required to delete listings.";
    $_SESSION['admin_message_type'] = "error";
    header('Location: admin_manage_listings.php'); // Or login.php if session totally invalid
    exit;
}

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['admin_message'] = "Invalid or missing listing ID for deletion.";
    $_SESSION['admin_message_type'] = "error";
    header('Location: admin_manage_listings.php');
    exit;
}
$listing_id_to_delete = (int)$_GET['id'];

// CSRF Protection (Highly Recommended - implement a proper CSRF token system)
// For now, a basic check or skip if not yet implemented.
// if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
//     $_SESSION['admin_message'] = "Invalid security token. Deletion failed.";
//     $_SESSION['admin_message_type'] = "error";
//     header('Location: admin_manage_listings.php');
//     exit;
// }


try {
    $pdo->beginTransaction();

    // 1. Get all image URLs for the listing to delete files from server
    $stmt_images = $pdo->prepare("SELECT image_url FROM housing_images WHERE listing_id = :listing_id");
    $stmt_images->bindParam(':listing_id', $listing_id_to_delete, PDO::PARAM_INT);
    $stmt_images->execute();
    $images_to_delete = $stmt_images->fetchAll(PDO::FETCH_COLUMN);

    // 2. Delete image records from housing_images table (CASCADE might handle this if set on FK)
    // If ON DELETE CASCADE is set for listing_id in housing_images, this step is optional but good for explicitness
    $stmt_delete_img_records = $pdo->prepare("DELETE FROM housing_images WHERE listing_id = :listing_id");
    $stmt_delete_img_records->bindParam(':listing_id', $listing_id_to_delete, PDO::PARAM_INT);
    $stmt_delete_img_records->execute();
    
    // 3. Delete booking records associated with the listing (CASCADE might handle this if set on FK)
    // If ON DELETE CASCADE is set for listing_id in bookings, this step is optional
    $stmt_delete_bookings = $pdo->prepare("DELETE FROM bookings WHERE listing_id = :listing_id");
    $stmt_delete_bookings->bindParam(':listing_id', $listing_id_to_delete, PDO::PARAM_INT);
    $stmt_delete_bookings->execute();

    // 4. Delete other related records (e.g., housing_amenities, reviews) if ON DELETE CASCADE isn't set
    // Example for reviews:
    $stmt_delete_reviews = $pdo->prepare("DELETE FROM reviews WHERE listing_id = :listing_id");
    $stmt_delete_reviews->bindParam(':listing_id', $listing_id_to_delete, PDO::PARAM_INT);
    $stmt_delete_reviews->execute();

    // 5. Delete the main housing listing
    $stmt_delete_housing = $pdo->prepare("DELETE FROM housings WHERE listing_id = :listing_id");
    $stmt_delete_housing->bindParam(':listing_id', $listing_id_to_delete, PDO::PARAM_INT);
    $delete_success = $stmt_delete_housing->execute();

    if ($delete_success) {
        // 6. Delete actual image files from the server
        define('UPLOAD_DIR_FOR_DELETE', __DIR__ . '/assets/uploads/housing_images/');
        foreach ($images_to_delete as $image_url) {
            // Construct full path carefully. Assumes $image_url is like 'assets/uploads/housing_images/filename.jpg'
            if (strpos($image_url, 'assets/uploads/housing_images/') === 0) {
                $filename = basename($image_url);
                $file_path = UPLOAD_DIR_FOR_DELETE . $filename;
                if (file_exists($file_path) && is_writable($file_path)) {
                    @unlink($file_path);
                } else {
                    error_log("Admin Delete: Could not delete file or file not found: " . $file_path);
                }
            }
        }
        $pdo->commit();
        $_SESSION['admin_message'] = "Listing ID: " . $listing_id_to_delete . " and all associated data deleted successfully.";
        $_SESSION['admin_message_type'] = "success";
    } else {
        $pdo->rollBack();
        $_SESSION['admin_message'] = "Failed to delete listing ID: " . $listing_id_to_delete . ".";
        $_SESSION['admin_message_type'] = "error";
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Admin Delete Listing Error: " . $e->getMessage());
    $_SESSION['admin_message'] = "Database error during deletion: " . $e->getMessage();
    $_SESSION['admin_message_type'] = "error";
}

header('Location: admin_manage_listings.php');
exit;
?>