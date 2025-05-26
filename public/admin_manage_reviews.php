<?php
// public/admin_manage_reviews.php
session_start();
require_once __DIR__ . '/../config/config.php'; // $pdo

// Admin protection
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['login_error'] = "Admin access required.";
    header('Location: login.php');
    exit;
}

$pageTitle = "Admin: Manage Reviews - CROUS-X";
$isLoggedIn = true;

// --- Filter and Sort ---
$filter_status = $_GET['status'] ?? 'all'; // 'all', 'pending', 'approved'
$sort_order = $_GET['sort'] ?? 'newest'; // 'newest', 'oldest', 'rating_high', 'rating_low'

$reviews = [];
$where_clauses = [];
$params = [];

if ($filter_status === 'pending') {
    $where_clauses[] = "r.is_approved = 0";
} elseif ($filter_status === 'approved') {
    $where_clauses[] = "r.is_approved = 1";
}
// 'all' means no status filter

$sql_where = "";
if (!empty($where_clauses)) {
    $sql_where = "WHERE " . implode(" AND ", $where_clauses);
}

$sql_order = "ORDER BY r.review_date DESC"; // Default: newest
if ($sort_order === 'oldest') {
    $sql_order = "ORDER BY r.review_date ASC";
} elseif ($sort_order === 'rating_high') {
    $sql_order = "ORDER BY r.rating DESC, r.review_date DESC";
} elseif ($sort_order === 'rating_low') {
    $sql_order = "ORDER BY r.rating ASC, r.review_date DESC";
}


try {
    $sql = "SELECT r.*,
                   h.title AS housing_title, /* REMOVED h.slug AS housing_slug, */
                   u.username AS reviewer_username
            FROM reviews r
            JOIN housings h ON r.listing_id = h.listing_id
            JOIN users u ON r.user_id = u.user_id
            $sql_where
            $sql_order";

    $stmt = $pdo->prepare($sql); // Prepare if using params, query if not. For dynamic WHERE/ORDER, direct query is simpler here.
    // If using params for WHERE:
    // foreach ($params as $key => $value) {
    //    $stmt->bindValue($key, $value);
    // }
    $stmt->execute(); // Execute even if no params for this simplified version
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Admin Manage Reviews Error: " . $e->getMessage());
    $page_error_message = "Could not retrieve reviews due to a database error.";
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
    <style>
        .filter-controls { margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; }
        .filter-controls label { margin-bottom: 0; margin-right: 0.5rem; }
        .filter-controls select, .filter-controls button { padding: 0.4rem 0.8rem; font-size:0.9rem; }
        .review-comment-short { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    </style>
</head>
<body>
    <?php require 'header.php'; ?>

    <main class="app-container dashboard-page-wrapper">
        <div class="dashboard-header-bar">
            <h1 class="page-main-heading">Manage User Reviews</h1>
            <!-- No "Add New Review" button here, as admins typically don't add reviews for users -->
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

        <form method="GET" action="admin_manage_reviews.php" class="filter-controls">
            <label for="status_filter">Filter by Status:</label>
            <select name="status" id="status_filter" class="form-control" style="width: auto;">
                <option value="all" <?php echo ($filter_status === 'all') ? 'selected' : ''; ?>>All</option>
                <option value="pending" <?php echo ($filter_status === 'pending') ? 'selected' : ''; ?>>Pending Approval (Not Approved)</option>
                <option value="approved" <?php echo ($filter_status === 'approved') ? 'selected' : ''; ?>>Approved</option>
            </select>
            <label for="sort_filter">Sort by:</label>
            <select name="sort" id="sort_filter" class="form-control" style="width: auto;">
                <option value="newest" <?php echo ($sort_order === 'newest') ? 'selected' : ''; ?>>Newest First</option>
                <option value="oldest" <?php echo ($sort_order === 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                <option value="rating_high" <?php echo ($sort_order === 'rating_high') ? 'selected' : ''; ?>>Rating (High to Low)</option>
                <option value="rating_low" <?php echo ($sort_order === 'rating_low') ? 'selected' : ''; ?>>Rating (Low to High)</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Apply</button>
        </form>


        <?php if (empty($reviews) && !isset($page_error_message)): ?>
            <div class="info-message-display no-news" style="margin-top: 1rem; padding: 2rem;">
                <i class="fas fa-comments-dollar fa-2x" style="margin-bottom: 1rem;"></i> <!-- Changed icon -->
                <p>No reviews found matching the current criteria.</p>
            </div>
        <?php elseif (!empty($reviews)): ?>
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Listing</th>
                            <th>User</th>
                            <th>Rating</th>
                            <th>Title</th>
                            <th style="min-width: 250px;">Comment (Excerpt)</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($review['review_id']); ?></td>
                                <td>
                                    <a href="housing-detail.php?id=<?php echo $review['listing_id']; ?>" target="_blank" title="View Listing: <?php echo htmlspecialchars($review['housing_title']); ?>">
                                        <?php echo htmlspecialchars(mb_strimwidth($review['housing_title'], 0, 30, "...")); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($review['reviewer_username']); ?></td>
                                <td>
                                    <?php for($i=0; $i < 5; $i++): ?>
                                        <i class="fas fa-star" style="color: <?php echo ($i < $review['rating']) ? 'var(--accent-primary)' : 'var(--border-color)';?>;"></i>
                                    <?php endfor; ?>
                                </td>
                                <td><?php echo htmlspecialchars($review['title'] ?? 'N/A'); ?></td>
                                <td>
                                    <div class="review-comment-short" title="<?php echo htmlspecialchars($review['comment']); ?>">
                                        <?php echo htmlspecialchars(mb_strimwidth($review['comment'], 0, 100, "...")); ?>
                                    </div>
                                </td>
                                <td><?php echo date("d M Y", strtotime($review['review_date'])); ?></td>
                                <td>
                                    <?php if ($review['is_approved']): ?>
                                        <span class="listing-status-badge status-approved" style="background-color: #5cb85c; color:white;">Approved</span>
                                    <?php else: ?>
                                        <span class="listing-status-badge status-pending_approval" style="background-color: #f0ad4e; color:#333;">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <?php if (!$review['is_approved']): ?>
                                        <a href="admin_action_review.php?action=approve&id=<?php echo $review['review_id']; ?>" title="Approve Review"><i class="fas fa-check-circle" style="color: #5cb85c;"></i></a>
                                    <?php else: ?>
                                        <a href="admin_action_review.php?action=unapprove&id=<?php echo $review['review_id']; ?>" title="Unapprove Review (Set to Pending)"><i class="fas fa-times-circle" style="color: #f0ad4e;"></i></a>
                                    <?php endif; ?>
                                    <a href="admin_action_review.php?action=delete&id=<?php echo $review['review_id']; ?>" 
                                       onclick="return confirm('Are you sure you want to delete this review PERMANENTLY?');" 
                                       title="Delete Review" class="delete-link"><i class="fas fa-trash-alt"></i></a>
                                    <!-- View full review modal/page could be added here -->
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
</body>
</html>