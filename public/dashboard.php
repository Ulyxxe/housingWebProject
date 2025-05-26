<?php
session_start(); // Must be the very first thing

// Check if the user is logged in by verifying session variables
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the login page
    header("Location: login.php");
    exit; // Stop script execution after redirect
}

// The user is authenticated. Get user info safely.
// Use htmlspecialchars to prevent XSS when echoing data later.
$firstName = isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name']) : 'User';
$lastName = isset($_SESSION['last_name']) ? htmlspecialchars($_SESSION['last_name']) : '';
$userFullName = trim($firstName . ' ' . $lastName);
$userEmail = isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'N/A';
$userType = isset($_SESSION['user_type']) ? htmlspecialchars(ucfirst($_SESSION['user_type'])) : 'N/A'; // Capitalize first letter
$userID = $_SESSION['user_id'];

// --- NEW: Fetch recent applications ---
require_once __DIR__ . '/../config/config.php'; // Defines $pdo
require_once __DIR__ . '/../src/Models/BookingModel.php'; // Include the BookingModel

$recentApplications = [];
if (isset($pdo)) { // Ensure $pdo is available from config.php
    $bookingModel = new BookingModel($pdo);
    $recentApplications = $bookingModel->getApplicationsByUserId($userID, 3); // Fetch latest 3 applications
}

?>
<!DOCTYPE html>
<html lang="en" data-theme="dark" data-accent-color="crous-pink-primary">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n-key="dashboard_page_title_document">Your Dashboard - CROUS-X</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
         integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
         crossorigin=""/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="icon" type="image/png" href="assets/images/icon.png"> <!-- Corrected path -->
</head>
<body>

    <?php require 'header.php'; ?>

    <main class="app-container dashboard-page-wrapper">
        <div class="dashboard-header-bar">
            <h1 class="page-main-heading" data-i18n-key="dashboard_main_heading">Welcome Back, <?php echo $userFullName; ?>!</h1>
            <a href="logout.php" class="btn-auth btn-logout-dashboard" data-i18n-key="dashboard_button_logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <div class="dashboard-content-grid">
            
            <aside class="dashboard-sidebar">
                <div class="profile-summary-card">
                    <div class="profile-avatar-placeholder">
                        <i class="fas fa-user-circle"></i>
                        
                    </div>
                    <h3 class="profile-name"><?php echo $userFullName; ?></h3>
                    <p class="profile-email"><?php echo $userEmail; ?></p>
                    <p class="profile-type" data-i18n-key="dashboard_profile_account_type">Account Type: <strong><?php echo $userType; ?></strong></p>
                    <a href="profile-edit.php" class="btn-profile-edit" data-i18n-key="dashboard_button_edit_profile">
                        <i class="fas fa-pencil-alt"></i> Edit Profile
                    </a>
                </div>
                <nav class="dashboard-nav">
                    <a href="dashboard.php" class="dashboard-nav-link active" data-i18n-key="dashboard_nav_overview"><i class="fas fa-tachometer-alt"></i> Overview</a>
                    <a href="my-listings.php" class="dashboard-nav-link" data-i18n-key="dashboard_nav_my_listings"><i class="fas fa-home"></i> My Listings</a> <!-- If applicable -->
                    <a href="my-applications.php" class="dashboard-nav-link" data-i18n-key="dashboard_nav_my_applications"><i class="fas fa-clipboard-list"></i> My Applications</a>
                    <a href="#" class="dashboard-nav-link" data-i18n-key="dashboard_nav_saved_listings"><i class="fas fa-heart"></i> Saved Listings</a>
                    <a href="#" class="dashboard-nav-link" data-i18n-key="dashboard_nav_settings"><i class="fas fa-cog"></i> Account Settings</a>
                </nav>
            </aside>

            <section class="dashboard-main-content">
                <div class="dashboard-section-card">
                    <h2 class="dashboard-section-title" data-i18n-key="dashboard_section_quick_actions">Quick Actions</h2>
                    <div class="quick-actions-grid">
                        <a href="home.php" class="quick-action-item">
                            <i class="fas fa-search"></i>
                            <span data-i18n-key="dashboard_quick_action_find_housing">Find Housing</span>
                        </a>
                        <a href="add-housing.php" class="quick-action-item">
                            <i class="fas fa-plus-circle"></i>
                            <span data-i18n-key="dashboard_quick_action_add_listing">Add New Listing</span> 
                        </a>
                         <a href="#" class="quick-action-item">
                            <i class="fas fa-envelope"></i>
                            <span data-i18n-key="dashboard_quick_action_messages">Messages <span class="badge">3</span></span>
                        </a>
                    </div>
                </div>

                <div class="dashboard-section-card">
                    <h2 class="dashboard-section-title" data-i18n-key="dashboard_section_recent_activity">Recent Activity / Notifications</h2>
                    <div class="activity-feed">
                        <div class="activity-item">
                            <i class="fas fa-file-alt activity-icon"></i>
                            <p><strong data-i18n-key="dashboard_activity_app_submitted">Application Submitted:</strong> Studio Apartment near Campus - <span class="activity-time">2 hours ago</span></p>
                        </div>
                        <div class="activity-item">
                            <i class="fas fa-heart activity-icon saved"></i>
                             <p><strong data-i18n-key="dashboard_activity_listing_saved">New Listing Saved:</strong> Shared Room Downtown - <span class="activity-time">1 day ago</span></p>
                        </div>
                        <div class="activity-item">
                            <i class="fas fa-comment-dots activity-icon message"></i>
                            <p><strong data-i18n-key="dashboard_activity_new_message">New Message:</strong> From Landlord A - <span class="activity-time">3 days ago</span></p>
                        </div>
                        <!-- Add more activity items here -->
                    </div>
                </div>

                                <div class="dashboard-section-card" id="dashboard-my-applications">
                    <div class="dashboard-section-header">
                        <h2 class="dashboard-section-title" data-i18n-key="dashboard_section_my_applications_title">My Recent Applications</h2>
                        <a href="my-applications.php" class="btn-view-all" data-i18n-key="dashboard_link_view_all_applications">View All <i class="fas fa-arrow-right"></i></a>
                    </div>

                    <?php if (empty($recentApplications)): ?>
                        <div class="placeholder-content light-placeholder">
                            <i class="fas fa-file-alt"></i>
                            <p data-i18n-key="dashboard_placeholder_no_applications">You haven't submitted any applications yet.</p>
                            <a href="home.php" class="btn btn-secondary" data-i18n-key="dashboard_btn_browse_housing">Browse Housing</a>
                        </div>
                    <?php else: ?>
                        <div class="dashboard-applications-list">
                            <?php foreach ($recentApplications as $app): ?>
                                <div class="dashboard-application-item">
                                    <div class="app-item-image">
                                        <?php if (!empty($app['housing_primary_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($app['housing_primary_image']); ?>" alt="<?php echo htmlspecialchars($app['housing_title']); ?>" loading="lazy">
                                        <?php else: ?>
                                            <i class="far fa-image"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="app-item-details">
                                        <h4 class="app-item-title">
                                            <a href="housing-detail.php?id=<?php echo htmlspecialchars($app['listing_id']); ?>">
                                                <?php echo htmlspecialchars($app['housing_title']); ?>
                                            </a>
                                        </h4>
                                        <p class="app-item-meta">
                                            Requested Move-in: <?php echo htmlspecialchars(date('M j, Y', strtotime($app['requested_move_in_date']))); ?>
                                        </p>
                                    </div>
                                    <div class="app-item-status">
                                        <span class="application-status-badge status-<?php echo htmlspecialchars(strtolower($app['application_status'])); ?>">
                                            <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $app['application_status']))); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                 <div class="dashboard-placeholder-section">
                    <h3 data-i18n-key="dashboard_placeholder_saved_listings_title">Saved Listings</h3>
                    <div class="placeholder-content">
                        <i class="fas fa-heart-broken"></i>
                        <p data-i18n-key="dashboard_placeholder_no_saved_listings">You haven't saved any listings. Browse now!</p>
                    </div>
                 </div>

            </section>
        </div>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script src="script.js" defer></script>
</body>
</html>