<?php
// public/admin_manage_listings.php
session_start();
require_once __DIR__ . '/../config/config.php'; // $pdo

// Admin protection
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['login_error'] = "Admin access required."; // Use a consistent session key for login errors
    header('Location: login.php');
    exit;
}

$pageTitle = "Admin: Manage Listings - CROUS-X";
$isLoggedIn = true; // For header.php

// Fetch ALL listings for admin view
$listings = [];
try {
    $sql = "SELECT 
                h.*, 
                u.username AS owner_username,
                hi.image_url AS primary_image
            FROM housings h
            LEFT JOIN users u ON h.user_id = u.user_id
            LEFT JOIN housing_images hi ON h.listing_id = hi.listing_id AND hi.is_primary = 1
            ORDER BY h.created_at DESC";
    $stmt = $pdo->query($sql);
    $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Admin Manage Listings Error: " . $e->getMessage());
    // Set a page-specific error message to display
    $page_error_message = "Could not retrieve listings due to a database error. Please try again later.";
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
    <link rel="stylesheet" href="css/forms.css"> <!-- For form messages -->
    <link rel="stylesheet" href="css/admin_panel.css"> 
</head>
<body>
    <?php require 'header.php'; ?>

    <main class="app-container dashboard-page-wrapper"> <!-- Reusing dashboard wrapper -->
        <div class="dashboard-header-bar" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <h1 class="page-main-heading" style="margin-bottom: 0;">Manage Housing Listings</h1>
            <a href="add-housing.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Listing</a>
        </div>

        <!-- Session Messages for success/error from other actions -->
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


        <?php if (empty($listings) && !isset($page_error_message)): ?>
            <div class="info-message-display no-news" style="margin-top: 2rem; padding: 2rem;"> <!-- Re-use no-news styling or create specific -->
                <i class="fas fa-folder-open fa-2x" style="margin-bottom: 1rem;"></i>
                <p>No housing listings found in the system.</p>
                <p>You can <a href="add-housing.php" class="btn btn-secondary btn-sm">add the first one</a>!</p>
            </div>
        <?php elseif (!empty($listings)): ?>
            <div style="overflow-x: auto;"> <!-- For table responsiveness -->
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Owner</th>
                            <th>Status</th>
                            <th>Rent (€)</th>
                            <th>City</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listings as $listing): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($listing['listing_id']); ?></td>
                                <td>
                                    <?php if (!empty($listing['primary_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($listing['primary_image']); ?>" alt="Thumbnail" class="thumbnail">
                                    <?php else: echo 'N/A'; endif; ?>
                                </td>
                                <td>
                                    <a href="housing-detail.php?id=<?php echo $listing['listing_id']; ?>" title="View Details">
                                        <?php echo htmlspecialchars($listing['title']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($listing['owner_username'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="listing-status-badge status-<?php echo htmlspecialchars(strtolower($listing['status'])); ?>">
                                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $listing['status']))); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format((float)$listing['rent_amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($listing['address_city']); ?></td>
                                <td><?php echo date("d M Y", strtotime($listing['created_at'])); ?></td>
                                <td class="actions">
                                    <a href="housing-detail.php?id=<?php echo $listing['listing_id']; ?>" title="View"><i class="fas fa-eye"></i></a>
                                    <a href="edit-housing.php?id=<?php echo $listing['listing_id']; ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="delete_listing_admin.php?id=<?php echo $listing['listing_id']; ?>" 
                                       onclick="return confirm('Are you sure you want to delete this listing PERMANENTLY? This action cannot be undone.');" 
                                       title="Delete" class="delete-link"><i class="fas fa-trash-alt"></i></a>
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
        // Color coding for status badges based on class
        document.addEventListener('DOMContentLoaded', function() {
            const statusBadges = document.querySelectorAll('.listing-status-badge');
            statusBadges.forEach(badge => {
                badge.style.color = 'white'; // Default text color for badges
                badge.style.padding = '0.25em 0.6em';
                badge.style.fontSize = '0.8em';
                badge.style.borderRadius = '4px';
                badge.style.textTransform = 'capitalize';

                if (badge.classList.contains('status-available')) {
                    badge.style.backgroundColor = '#5cb85c'; // Green
                } else if (badge.classList.contains('status-pending_approval')) {
                    badge.style.backgroundColor = '#f0ad4e'; // Orange
                    badge.style.color = '#333'; 
                } else if (badge.classList.contains('status-unavailable')) {
                    badge.style.backgroundColor = '#d9534f'; // Red
                } else if (badge.classList.contains('status-rented')) { // Example for another status
                    badge.style.backgroundColor = '#5bc0de'; // Info Blue
                } else {
                    badge.style.backgroundColor = '#777'; // Default grey
                }
            });
        });
    </script>
</body>
</html>