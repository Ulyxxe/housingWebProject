<?php
// public/admin_dashboard.php
session_start();

// 1. Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['login_error'] = "You do not have permission to access the admin area. Please log in as an administrator.";
    header('Location: login.php');
    exit;
}

$pageTitle = "Admin Dashboard - CROUS-X";
$adminUsername = htmlspecialchars($_SESSION['username'] ?? 'Admin');
$isLoggedIn = true; // For header.php to know a session is active   
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
    <link rel="stylesheet" href="css/forms.css"> <!-- For session messages -->
    <link rel="stylesheet" href="css/dashboard.css"> <!-- Base layout from user dashboard -->
    <link rel="stylesheet" href="css/admin_panel.css"> <!-- Specific admin panel styles -->
</head>
<body>
    <?php require 'header.php'; // Shows admin name and logout ?>

    <main class="app-container dashboard-page-wrapper">
        <div class="dashboard-header-bar">
             <h1 class="page-main-heading">Admin Dashboard</h1>
        </div>

        <!-- Display any session messages (e.g., after an action on a management page) -->
        <?php if (isset($_SESSION['admin_message'])): ?>
            <div class="form-message <?php echo ($_SESSION['admin_message_type'] ?? 'success') === 'success' ? 'success-message' : 'error-message'; ?>" style="margin-bottom: 1.5rem;">
                <i class="fas <?php echo ($_SESSION['admin_message_type'] ?? 'success') === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($_SESSION['admin_message']); ?>
            </div>
            <?php unset($_SESSION['admin_message'], $_SESSION['admin_message_type']); ?>
        <?php endif; ?>

        <p class="admin-welcome">Welcome, <strong><?php echo $adminUsername; ?></strong>! Select an area to manage.</p>

        <section class="admin-quick-nav">
            <a href="admin_manage_listings.php" class="admin-nav-card">
                <i class="fas fa-home"></i>
                <h3>Manage Listings</h3>
                <p>View all, add new, edit, or delete housing listings.</p>
                <span class="btn btn-secondary">Go to Listings</span>
            </a>

            <a href="admin_manage_reviews.php" class="admin-nav-card">
                <i class="fas fa-star-half-alt"></i>
                <h3>Manage Reviews</h3>
                <p>Approve, unapprove, or delete user-submitted reviews.</p>
                <span class="btn btn-secondary">Go to Reviews</span>
            </a>

            <a href="admin_manage_news.php" class="admin-nav-card">
                <i class="fas fa-newspaper"></i>
                <h3>Manage News Articles</h3>
                <p>Create, edit, publish, or delete news articles.</p>
                <span class="btn btn-secondary">Go to News</span>
            </a>
            

        </section>
        
        <section style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color-subtle);">
            <h2 class="dashboard-section-title" style="font-size: 1.3rem; text-align:left; border-bottom:none;">Quick Stats (Example)</h2>
            <div class="quick-actions-grid"> <!-- Reusing class from user dashboard for layout -->
                <div class="quick-action-item" style="background-color: var(--input-bg);">
                    <?php
                        // Example: Count pending reviews
                        $pending_reviews_count = 0;
                        try {
                            $stmt_pr = $pdo->query("SELECT COUNT(*) FROM reviews WHERE is_approved = 0");
                            $pending_reviews_count = $stmt_pr->fetchColumn();
                        } catch (PDOException $e) { /* ignore for dashboard stat */ }
                    ?>
                    <i class="fas fa-hourglass-half"></i>
                    <span><?php echo $pending_reviews_count; ?> Pending Reviews</span>
                </div>
                <div class="quick-action-item" style="background-color: var(--input-bg);">
                     <?php
                        $total_listings_count = 0;
                        try {
                            $stmt_tl = $pdo->query("SELECT COUNT(*) FROM housings");
                            $total_listings_count = $stmt_tl->fetchColumn();
                        } catch (PDOException $e) { /* ignore */ }
                    ?>
                    <i class="fas fa-list-alt"></i>
                    <span><?php echo $total_listings_count; ?> Total Listings</span>
                </div>
                 <div class="quick-action-item" style="background-color: var(--input-bg);">
                     <?php
                        $total_users_count = 0;
                        try {
                            $stmt_tu = $pdo->query("SELECT COUNT(*) FROM users");
                            $total_users_count = $stmt_tu->fetchColumn();
                        } catch (PDOException $e) { /* ignore */ }
                    ?>
                    <i class="fas fa-users"></i>
                    <span><?php echo $total_users_count; ?> Total Users</span>
                </div>
            </div>
        </section>

    </main>

    <?php require 'chat-widget.php'; ?>
    <script src="script.js" defer></script>
    <script src="chatbot.js" defer></script>
</body>
</html>