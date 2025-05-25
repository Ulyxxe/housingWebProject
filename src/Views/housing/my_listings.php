<?php
// src/Views/housing/my_listings.php

// $pageTitle, $listings, $isLoggedIn, $userFullName are expected to be set by the controller
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark" data-accent-color="crous-pink-primary">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'My Listings'); ?> - CROUS-X</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="icon" type="image/png" href="../../assets/images/icon.png"> <!-- Adjusted path -->
    
    <!-- Assuming these are relative to the public directory when served -->
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/header.css">
    <link rel="stylesheet" href="../../css/components.css">
    <link rel="stylesheet" href="../../css/listings.css"> <!-- For card styles -->
    <link rel="stylesheet" href="../../css/my-listings.css"> <!-- New CSS for this page -->

</head>
<body>

    <?php require __DIR__ . '/../../../public/header.php'; // Adjusted path to header ?>

    <main class="app-container my-listings-page-wrapper">
        <div class="my-listings-header">
            <h1 class="page-main-heading"><?php echo htmlspecialchars($pageTitle ?? 'My Listings'); ?></h1>
            <a href="../../add-housing.php" class="btn btn-primary"> <!-- Adjusted path -->
                <i class="fas fa-plus-circle"></i> Add New Listing
            </a>
        </div>

        <?php if (empty($listings)): ?>
            <div class="no-listings-message card">
                <div class="card-body">
                    <i class="fas fa-folder-open"></i>
                    <p>You haven't added any housing listings yet.</p>
                    <p><a href="../../add-housing.php" class="btn btn-secondary">Create Your First Listing</a></p> <!-- Adjusted path -->
                </div>
            </div>
        <?php else: ?>
            <div class="my-listings-grid">
                <?php foreach ($listings as $listing): ?>
                    <div class="result-card my-listing-card"> <!-- Reusing .result-card for styling -->
                        <a href="../../housing-detail.php?id=<?php echo htmlspecialchars($listing['listing_id']); ?>" class="card-image-link"> <!-- Adjusted path -->
                            <div class="card-image-placeholder">
                                <?php if (!empty($listing['primary_image'])): ?>
                                    <img src="../../<?php echo htmlspecialchars($listing['primary_image']); ?>" alt="<?php echo htmlspecialchars($listing['title']); ?>" loading="lazy"> <!-- Adjusted path -->
                                <?php else: ?>
                                    <i class="far fa-image"></i> <!-- Placeholder icon -->
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="card-content">
                            <h4 class="card-title">
                                <a href="../../housing-detail.php?id=<?php echo htmlspecialchars($listing['listing_id']); ?>"> <!-- Adjusted path -->
                                    <?php echo htmlspecialchars($listing['title']); ?>
                                </a>
                            </h4>
                            <p class="card-price">
                                €<?php echo number_format((float)$listing['rent_amount'], 2); ?> / <?php echo htmlspecialchars($listing['rent_frequency']); ?>
                            </p>
                            <p class="card-info">
                                <span class="listing-status-badge status-<?php echo htmlspecialchars(strtolower($listing['status'])); ?>">
                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $listing['status']))); ?>
                                </span>
                            </p>
                            <p class="card-info">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($listing['address_city']); ?>, <?php echo htmlspecialchars($listing['address_country']); ?>
                            </p>
                             <p class="card-info">
                                <i class="fas fa-calendar-alt"></i> Added: <?php echo htmlspecialchars(date('M j, Y', strtotime($listing['created_at']))); ?>
                            </p>
                        </div>
                        <div class="card-actions">
                            <a href="edit-housing.php?id=<?php echo htmlspecialchars($listing['listing_id']); ?>" class="btn btn-secondary btn-edit-listing">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="delete-housing.php?id=<?php echo htmlspecialchars($listing['listing_id']); ?>" class="btn btn-danger btn-delete-listing" onclick="return confirm('Are you sure you want to delete this listing?');">
                                <i class="fas fa-trash-alt"></i> Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php require __DIR__ . '/../../../public/chat-widget.php'; // Adjusted path ?>

    <script src="../../script.js" defer></script> <!-- Adjusted path -->
    <script src="../../chatbot.js" defer></script> <!-- Adjusted path -->
</body>
</html>