<?php
// src/Views/booking/my_applications.php
// $pageTitle, $applications, $isLoggedIn, $userFullName are expected to be set by the controller
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark" data-accent-color="crous-pink-primary">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'My Applications'); ?> - CROUS-X</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="icon" type="image/png" href="../../assets/images/icon.png"> <!-- Adjusted path -->
    
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/header.css">
    <link rel="stylesheet" href="../../css/components.css">
    <link rel="stylesheet" href="../../css/listings.css"> <!-- For card styles, if reusing -->
    <link rel="stylesheet" href="../../css/my-applications.css"> <!-- New CSS for this page -->

</head>
<body>

    <?php require __DIR__ . '/../../../public/header.php'; // Adjusted path to header ?>

    <main class="app-container my-applications-page-wrapper">
        <div class="my-applications-header">
            <h1 class="page-main-heading"><?php echo htmlspecialchars($pageTitle ?? 'My Applications'); ?></h1>
            <a href="../../home.php" class="btn btn-secondary"> <!-- Link to find more housing -->
                <i class="fas fa-search"></i> Find More Housing
            </a>
        </div>

        <?php if (empty($applications)): ?>
            <div class="no-applications-message card">
                <div class="card-body">
                    <i class="fas fa-file-alt"></i>
                    <p>You haven't submitted any applications yet.</p>
                    <p><a href="../../home.php" class="btn btn-primary">Browse Housing Listings</a></p>
                </div>
            </div>
        <?php else: ?>
            <div class="my-applications-list">
                <?php foreach ($applications as $app): ?>
                    <div class="application-card card"> <!-- Reusing .card for base styling -->
                        <div class="application-card-image-section">
                             <?php if (!empty($app['housing_primary_image'])): ?>
                                <img src="../../<?php echo htmlspecialchars($app['housing_primary_image']); ?>" alt="<?php echo htmlspecialchars($app['housing_title']); ?>" class="application-housing-image" loading="lazy">
                            <?php else: ?>
                                <div class="application-housing-image-placeholder">
                                    <i class="far fa-image"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="application-card-details-section">
                            <h3 class="application-housing-title">
                                <a href="../../housing-detail.php?id=<?php echo htmlspecialchars($app['listing_id']); ?>">
                                    <?php echo htmlspecialchars($app['housing_title']); ?>
                                </a>
                            </h3>
                            <p class="application-info">
                                <strong>Type:</strong> <?php echo htmlspecialchars($app['housing_property_type']); ?>
                            </p>
                            <p class="application-info">
                                <strong>Rent:</strong> €<?php echo number_format((float)$app['housing_rent_amount'], 2); ?> / <?php echo htmlspecialchars($app['housing_rent_frequency']); ?>
                            </p>
                            <p class="application-info">
                                <strong>Requested Move-in:</strong> <?php echo htmlspecialchars(date('F j, Y', strtotime($app['requested_move_in_date']))); ?>
                            </p>
                            <p class="application-info">
                                <strong>Submitted:</strong> <?php echo htmlspecialchars(date('F j, Y H:i', strtotime($app['application_date']))); ?>
                            </p>
                            <?php if (!empty($app['user_notes'])): ?>
                                <p class="application-info user-notes">
                                    <strong>Your Notes:</strong> <?php echo nl2br(htmlspecialchars($app['user_notes'])); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="application-card-status-section">
                            <span class="application-status-badge status-<?php echo htmlspecialchars(strtolower($app['application_status'])); ?>">
                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $app['application_status']))); ?>
                            </span>
                            <!-- Add cancel button or other actions if needed -->
                            <!-- 
                            <?php if (in_array($app['application_status'], ['pending'])): // Example condition ?>
                                <a href="cancel-application.php?booking_id=<?php echo $app['booking_id']; ?>" class="btn btn-link btn-cancel-app" onclick="return confirm('Are you sure you want to cancel this application?');">
                                    Cancel Application
                                </a>
                            <?php endif; ?>
                            -->
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php require __DIR__ . '/../../../public/chat-widget.php'; // Adjusted path ?>

    <script src="../../script.js" defer></script>
    <script src="../../chatbot.js" defer></script>
</body>
</html>