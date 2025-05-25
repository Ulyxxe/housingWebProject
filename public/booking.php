<?php
// public/booking.php
session_start();

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

// 2. Include Database Configuration
require_once __DIR__ . '/../config/config.php'; // Defines $pdo

// Initialize variables
$housing = null;
$error_message = null;
$success_message = null;
$listing_id = null;

// 3. Get and Validate Housing ID
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $error_message = 'Invalid or missing housing ID.';
} else {
    $listing_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare(
            "SELECT h.listing_id, h.title, h.property_type, h.rent_amount, h.rent_frequency, hi.image_url AS image 
            FROM housings h
            LEFT JOIN housing_images hi ON h.listing_id = hi.listing_id AND hi.is_primary = 1
            WHERE h.listing_id = :listing_id"
        );
        $stmt->bindParam(':listing_id', $listing_id, PDO::PARAM_INT);
        $stmt->execute();
        $housing = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$housing) {
            $error_message = 'Housing listing not found.';
            $listing_id = null;
        }
    } catch (PDOException $e) {
        error_log("Booking page - Error fetching housing details: " . $e->getMessage());
        $error_message = 'A database error occurred while fetching housing details.';
        $listing_id = null;
    }
}

// 5. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $listing_id && $housing && empty($success_message)) {
    $user_id = $_SESSION['user_id'];
    $requested_move_in_date = trim($_POST['move_in_date'] ?? '');
    $user_notes = trim($_POST['user_notes'] ?? '');
    $agree_terms = isset($_POST['agree_terms']);

    if (empty($requested_move_in_date)) {
        $error_message = "Please select a preferred move-in date.";
    } elseif (!$agree_terms) {
        $error_message = "You must agree to the terms to submit a request.";
    } else {
        $date_parts = explode('-', $requested_move_in_date);
        if (count($date_parts) !== 3 || !checkdate((int)$date_parts[1], (int)$date_parts[2], (int)$date_parts[0])) {
            $error_message = "Invalid move-in date format. Please use YYYY-MM-DD.";
        } elseif (new DateTime($requested_move_in_date) < new DateTime(date('Y-m-d'))) {
            $error_message = "Move-in date cannot be in the past.";
        } else {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO bookings (user_id, listing_id, requested_move_in_date, user_notes, status) 
                     VALUES (:user_id, :listing_id, :move_in_date, :user_notes, 'pending')"
                );
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':listing_id', $listing_id, PDO::PARAM_INT);
                $stmt->bindParam(':move_in_date', $requested_move_in_date);
                $stmt->bindParam(':user_notes', $user_notes);
                
                if ($stmt->execute()) {
                    $success_message = "Your booking request has been submitted successfully! We will contact you soon. You can view your requests on your dashboard.";
                    $_POST = array();
                } else {
                    $error_message = "Failed to submit your booking request. Please try again.";
                }
            } catch (PDOException $e) {
                error_log("Booking page - Error inserting booking: " . $e->getMessage());
                if ($e->getCode() == '23000') {
                     $error_message = "You may have already submitted a request for this listing or there was a conflict. Please check your dashboard or contact support.";
                } else {
                    $error_message = "An error occurred while processing your request. Please try again later.";
                }
            }
        }
    }
}
$isLoggedIn = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Booking - <?php echo $housing ? htmlspecialchars($housing['title']) : 'CROUS-X'; ?></title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="css/booking.css">
    <link rel="icon" type="image/png" href="assets/images/icon.png">
</head>
<body>

   
   <?php require 'header.php'; ?>

    <div class="main-content-wrapper booking-page-wrapper">
        <div class="content-box" style="max-width: 700px; margin: 20px auto;">

            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success_message); ?></div>
                 <p style="text-align:center;">
                    <a href="dashboard.php" class="btn btn-register">View My Requests</a> 
                    <a href="home.php" class="btn btn-signin">Back to Listings</a>
                </p>
            <?php endif; ?>

            <?php if ($housing && !$success_message): ?>
                <h1>Request Booking For: <?php echo htmlspecialchars($housing['title']); ?></h1>
                
                <div class="housing-summary-booking">
                    <?php if (!empty($housing['image'])): ?>
                        <img src="<?php echo htmlspecialchars($housing['image']); ?>" alt="Image of <?php echo htmlspecialchars($housing['title']); ?>" class="booking-summary-image">
                    <?php else: ?>
                        <div class="image-placeholder-detail booking-summary-image-placeholder">
                            <i class="far fa-image"></i>
                            <span>No image</span>
                        </div>
                    <?php endif; ?>
                    <div class="booking-summary-details">
                        <h3><?php echo htmlspecialchars($housing['title']); ?></h3>
                        <p><strong>Type:</strong> <?php echo htmlspecialchars($housing['property_type']); ?></p>
                        <p><strong>Rent:</strong> $<?php echo number_format((float)$housing['rent_amount'], 2); ?> / <?php echo htmlspecialchars($housing['rent_frequency']); ?></p>
                    </div>
                </div>

                <form action="booking.php?id=<?php echo htmlspecialchars($listing_id); ?>" method="post" id="bookingForm" class="styled-form">
                    <div class="form-group">
                        <label for="user_email">Your Email (for confirmation)</label>
                        <input type="email" id="user_email" name="user_email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" readonly disabled>
                        <small>This is your registered email address. Updates will be sent here.</small>
                    </div>

                    <div class="form-group">
                        <label for="move_in_date">Preferred Move-in Date</label>
                        <!-- Flatpickr will attach to this input -->
                        <input type="text" id="move_in_date" name="move_in_date" 
                               value="<?php echo htmlspecialchars($_POST['move_in_date'] ?? ''); ?>" required
                               placeholder="YYYY-MM-DD">
                    </div>

                    <div class="form-group">
                        <label for="user_notes">Message / Additional Notes (Optional)</label>
                        <textarea id="user_notes" name="user_notes" rows="5" placeholder="E.g., preferred viewing times, any specific requirements or questions."><?php echo htmlspecialchars($_POST['user_notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group terms-group">
                        <input type="checkbox" id="agree_terms" name="agree_terms" required 
                               <?php echo (isset($_POST['agree_terms']) && $_POST['agree_terms']) ? 'checked' : ''; ?>>
                        <label for="agree_terms">I confirm my interest and understand this is a booking request, subject to availability and approval by the housing provider. I agree to be contacted regarding this request.</label>
                    </div>

                    <button type="submit" class="btn btn-register btn-register-submit" style="width:100%;">Submit Booking Request</button>
                </form>

            <?php elseif (!$error_message && !$success_message): ?>
                <p>The housing listing could not be found or is unavailable for booking.</p>
                <p><a href="home.php" class="btn btn-signin">Back to Listings</a></p>
            <?php endif; ?>
        </div>
    </div>

    <div id="chat-widget">
        <!-- Chat widget content remains the same -->
        <div id="chat-container" class="chat-hidden">
            <div id="chat-header"><span data-i18n-key="chat_title">CROUS-X Assistant</span><button id="chat-close-button" aria-label="Close chat">×</button></div>
            <div id="chat-messages"><div class="message bot" data-i18n-key="chat_greeting">Hi there! How can I help you navigate CROUS-X today?</div></div>
            <div id="chat-input-area"><input type="text" id="chat-input" placeholder="Ask a question..." data-i18n-key-placeholder="chat_placeholder"/><button id="chat-send-button" aria-label="Send message"><i class="fas fa-paper-plane"></i></button></div>
            <div id="chat-loading" class="chat-hidden"><i class="fas fa-spinner fa-spin"></i> <span data-i18n-key="chat_loading">Thinking...</span></div>
        </div>
        <button id="chat-toggle-button" aria-label="Toggle chat"><i class="fas fa-comments"></i></button>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="script.js" defer></script>
    <script src="chatbot.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Flatpickr
            const moveInDateElement = document.getElementById('move_in_date');
            if (moveInDateElement) {
                flatpickr(moveInDateElement, {
                    dateFormat: "Y-m-d", // Format submitted to server
                    minDate: "today",      // Prevent selecting past dates
                    altInput: true,        // Show a human-friendly date format to the user
                    altFormat: "F j, Y",   // Human-friendly format (e.g., August 24, 2023)
                    // You can add more options here: https://flatpickr.js.org/options/
                });
            }
        });
    </script>
</body>
</html>