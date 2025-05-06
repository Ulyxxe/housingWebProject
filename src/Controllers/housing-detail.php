<?php
// housing-detail.php
session_start();
// require_once 'config.php'; // For database connection

$housing_item = null;
$error_message = null;

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $housing_id = (int)$_GET['id'];

    // --- Database Fetch Logic ---
    // Example using PDO. Replace with your actual database connection and query.
    /*
    try {
        // $db = new PDO(...); // Your database connection from config.php
        $stmt = $db->prepare("SELECT * FROM housings WHERE listing_id = :id");
        $stmt->bindParam(':id', $housing_id, PDO::PARAM_INT);
        $stmt->execute();
        $housing_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$housing_item) {
            $error_message = "Housing listing not found.";
        }
    } catch (PDOException $e) {
        $error_message = "Database error. Please try again later.";
        // Log the error: error_log($e->getMessage());
    }
    */

    // --- Placeholder for when you don't have DB connected for testing ---
    if ($housing_id === 1) { // Simulate finding item with ID 1
        $housing_item = [
            'listing_id' => 1,
            'title' => 'Spacious Studio Near Campus',
            'property_type' => 'Studio',
            'rent_amount' => 750,
            'square_footage' => 30,
            'description' => 'A wonderful and cozy studio, perfect for students. Includes a small kitchenette and a private bathroom. Fully furnished.',
            'image' => 'images/placeholder-detail.jpg', // Example image path
            'latitude' => 48.8584, // Example
            'longitude' => 2.2945,  // Example
            'rating' => 4.5,
            'amenities' => 'Furnished, Wi-Fi, Laundry in building',
            'contact_email' => 'landlord@example.com',
            'contact_phone' => '123-456-7890'
        ];
    } elseif($housing_id === 2) {
         $housing_item = [
            'listing_id' => 2,
            'title' => 'Shared Apartment Downtown',
            'property_type' => 'Shared Room',
            'rent_amount' => 500,
            'square_footage' => 20, // Square footage of the room
            'description' => 'Room available in a 3-bedroom apartment. Shared kitchen, living room, and bathroom. Great location with easy access to public transport.',
            'image' => 'images/placeholder-detail-2.jpg',
            'latitude' => 48.8600,
            'longitude' => 2.3500,
            'rating' => 4.0,
            'amenities' => 'Shared Kitchen, Wi-Fi, Balcony',
            'contact_email' => 'agent@example.com',
            'contact_phone' => '987-654-3210'
        ];
    }
     else {
        $error_message = "Housing listing with ID " . htmlspecialchars($housing_id) . " not found (Placeholder).";
    }
    // --- End Placeholder ---

} else {
    $error_message = "Invalid or missing housing ID provided.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Use a dynamic title -->
    <title><?php echo $housing_item ? htmlspecialchars($housing_item['title']) : 'Housing Details'; ?> - CROUS-X</title>
    <link rel="stylesheet" href="style.css"> <!-- Your main stylesheet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/> <!-- If showing a map -->
    <link rel="icon" type="image/png" href="images/icon.png" />
    <style>
        /* Additional styles specific to housing-detail.php if needed */
        .detail-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
        }
        .detail-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .detail-map {
            height: 300px;
            width: 100%;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid var(--grey-border);
        }
        .detail-section {
            margin-bottom: 20px;
        }
        .detail-section h2 {
            color: var(--primary-pink);
            border-bottom: 2px solid var(--primary-pink);
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .amenities-list {
            list-style: disc;
            margin-left: 20px;
        }
         .back-link-container {
            margin-bottom: 20px;
            text-align: left;
        }
        .back-link {
            color: var(--primary-pink);
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; // Assuming you have a separate header file or paste your header HTML ?>

    <div class="main-content-wrapper">
        <div class="content-box detail-container">
             <div class="back-link-container">
                <a href="index.php" class="back-link">← Back to Listings</a>
            </div>
            <?php if ($error_message): ?>
                <h2>Error</h2>
                <p><?php echo htmlspecialchars($error_message); ?></p>
            <?php elseif ($housing_item): ?>
                <h1><?php echo htmlspecialchars($housing_item['title']); ?></h1>
                
                <?php if (!empty($housing_item['image'])): ?>
                    <img src="<?php echo htmlspecialchars($housing_item['image']); ?>" alt="<?php echo htmlspecialchars($housing_item['title']); ?>" class="detail-image">
                <?php endif; ?>

                <div class="detail-section">
                    <h2>Overview</h2>
                    <p><strong>Type:</strong> <?php echo htmlspecialchars($housing_item['property_type']); ?></p>
                    <p><strong>Price:</strong> $<?php echo htmlspecialchars($housing_item['rent_amount']); ?> / month</p>
                    <p><strong>Size:</strong> <?php echo htmlspecialchars($housing_item['square_footage']); ?> m²</p>
                    <?php if (isset($housing_item['rating']) && $housing_item['rating'] !== null): ?>
                        <p><strong>Rating:</strong> <?php echo htmlspecialchars($housing_item['rating']); ?> ★</p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($housing_item['description'])): ?>
                <div class="detail-section">
                    <h2>Description</h2>
                    <p><?php echo nl2br(htmlspecialchars($housing_item['description'])); ?></p>
                </div>
                <?php endif; ?>

                <?php if (!empty($housing_item['amenities'])): ?>
                <div class="detail-section">
                    <h2>Amenities</h2>
                    <ul class="amenities-list">
                        <?php 
                        $amenities = explode(',', $housing_item['amenities']);
                        foreach ($amenities as $amenity): ?>
                            <li><?php echo htmlspecialchars(trim($amenity)); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if (isset($housing_item['latitude']) && isset($housing_item['longitude'])): ?>
                <div class="detail-section">
                    <h2>Location</h2>
                    <div id="detail-map" class="detail-map"></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($housing_item['contact_email']) || !empty($housing_item['contact_phone'])): ?>
                <div class="detail-section">
                    <h2>Contact Information</h2>
                    <?php if (!empty($housing_item['contact_email'])): ?>
                        <p><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($housing_item['contact_email']); ?>"><?php echo htmlspecialchars($housing_item['contact_email']); ?></a></p>
                    <?php endif; ?>
                     <?php if (!empty($housing_item['contact_phone'])): ?>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($housing_item['contact_phone']); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <p>Housing details are not available at the moment.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php // include 'footer.php'; // Assuming you have a separate footer file ?>

    <?php if ($housing_item && isset($housing_item['latitude']) && isset($housing_item['longitude'])): ?>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Initialize Leaflet map on the detail page
        var map = L.map('detail-map').setView([<?php echo $housing_item['latitude']; ?>, <?php echo $housing_item['longitude']; ?>], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        L.marker([<?php echo $housing_item['latitude']; ?>, <?php echo $housing_item['longitude']; ?>]).addTo(map)
            .bindPopup('<?php echo htmlspecialchars($housing_item['title'], ENT_QUOTES); ?>')
            .openPopup();
    </script>
    <?php endif; ?>
    <script src="script.js"></script> <!-- If you need global scripts like dark mode, language toggle -->
</body>
</html>