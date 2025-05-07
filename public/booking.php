<?php
// public/booking.php
session_start();

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Store the intended destination to redirect after login
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

// 3. Get and Validate Housing ID from GET parameter
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $error_message = 'Invalid or missing housing ID.';
} else {
    $listing_id = (int)$_GET['id'];

    // 4. Fetch Housing Details for display
    try {
        // Query to get housing details including the primary image
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
            $listing_id = null; // Invalidate listing_id if not found
        }
    } catch (PDOException $e) {
        error_log("Booking page - Error fetching housing details: " . $e->getMessage());
        $error_message = 'A database error occurred while fetching housing details.';
        $listing_id = null; // Invalidate on error
    }
}

// 5. Handle Form Submission (Booking Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $listing_id && $housing && empty($success_message) /* Prevent re-submission after success */) {
    // Sanitize and retrieve form data
    $user_id = $_SESSION['user_id']; // From logged-in user
    $requested_move_in_date = trim($_POST['move_in_date'] ?? '');
    $user_notes = trim($_POST['user_notes'] ?? '');
    $agree_terms = isset($_POST['agree_terms']);

    // Basic validation
    if (empty($requested_move_in_date)) {
        $error_message = "Please select a preferred move-in date.";
    } elseif (!$agree_terms) {
        $error_message = "You must agree to the terms to submit a request.";
    } else {
        // Validate date format (YYYY-MM-DD)
        $date_parts = explode('-', $requested_move_in_date);
        if (count($date_parts) !== 3 || !checkdate((int)$date_parts[1], (int)$date_parts[2], (int)$date_parts[0])) {
            $error_message = "Invalid move-in date format. Please use YYYY-MM-DD.";
        } elseif (new DateTime($requested_move_in_date) < new DateTime(date('Y-m-d'))) {
             $error_message = "Move-in date cannot be in the past.";
        } else {
            // Proceed to insert booking request
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
                    // Clear POST data to prevent re-submission on refresh
                    $_POST = array();
                } else {
                    $error_message = "Failed to submit your booking request. Please try again.";
                }
            } catch (PDOException $e) {
                error_log("Booking page - Error inserting booking: " . $e->getMessage());
                if ($e->getCode() == '23000') { // Integrity constraint violation (e.g., duplicate entry if you add unique constraints)
                     $error_message = "You may have already submitted a request for this listing or there was a conflict. Please check your dashboard or contact support.";
                } else {
                    $error_message = "An error occurred while processing your request. Please try again later.";
                }
            }
        }
    }
}

// For header links:
$isLoggedIn = true; // User must be logged in to reach this point

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Booking - <?php echo $housing ? htmlspecialchars($housing['title']) : 'CROUS-X'; ?></title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="assets/images/icon.png"> <!-- Adjusted path for icon -->
</head>
<body>

    <header class="site-header">
        <a href="index.php" class="logo-link"><div class="logo">CROUS-X</div></a>
        <button class="hamburger" aria-label="Toggle navigation menu" aria-expanded="false">
            <span class="bar"></span><span class="bar"></span><span class="bar"></span>
        </button>
        <nav class="main-nav" aria-hidden="true">
            <ul>
                <li><a href="index.php" data-i18n-key="nav_news">News stand</a></li>
                <li><a href="help.php" data-i18n-key="nav_help">Need help ?</a></li>
                <li><a href="faq.php" data-i18n-key="nav_faq">FAQ</a></li>
                <li><a href="dashboard.php" data-i18n-key="nav_profile">My profile</a></li>
                <li class="language-switcher">
                    <button id="language-toggle" aria-label="Select language" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-globe"></i> <span class="current-lang">EN</span>
                    </button>
                    <ul id="language-options" class="language-dropdown" role="menu">
                        <li role="menuitem"><a href="#en" data-lang="en">English</a></li>
                        <li role="menuitem"><a href="#fr" data-lang="fr">Français</a></li>
                        <li role="menuitem"><a href="#es" data-lang="es">Español</a></li>
                    </ul>
                </li>
                <li>
                    <button id="theme-toggle" class="btn btn-dark-mode" aria-label="Toggle dark mode">
                        <i class="fas fa-moon"></i>
                    </button>
                </li>
                <li><a href="logout.php" class="btn btn-signin">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="main-content-wrapper booking-page-wrapper">
        <div class="content-box" style="max-width: 700px; margin: 20px auto;">

            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert" style="color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; padding: .75rem 1.25rem; margin-bottom: 1rem; border: 1px solid transparent; border-radius: .25rem;">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success" role="alert" style="color: green; background-color: #d4edda; border-color: #c3e6cb; padding: .75rem 1.25rem; margin-bottom: 1rem; border: 1px solid transparent; border-radius: .25rem;">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
                 <p style="text-align:center;">
                    <a href="dashboard.php" class="btn btn-register">View My Requests</a> 
                    <a href="index.php" class="btn btn-signin">Back to Listings</a>
                </p>
            <?php endif; ?>

            <?php if ($housing && !$success_message): ?>
                <h1>Request Booking For: <?php echo htmlspecialchars($housing['title']); ?></h1>
                
                <div class="housing-summary-booking" style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 25px; padding-bottom:20px; border-bottom:1px solid var(--grey-border);">
                    <?php if (!empty($housing['image'])): ?>
                        <img src="<?php echo htmlspecialchars($housing['image']); ?>" alt="Image of <?php echo htmlspecialchars($housing['title']); ?>" style="width: 150px; height: 100px; object-fit: cover; border-radius: 4px; flex-shrink:0;">
                    <?php else: ?>
                        <div class="image-placeholder-detail" style="width: 150px; height: 100px; font-size:0.8em; min-width:auto; flex-shrink:0;">
                            <i class="far fa-image" style="font-size:2em;"></i>
                            <span>No image</span>
                        </div>
                    <?php endif; ?>
                    <div style="flex-grow:1;">
                        <h3 style="margin-top:0; margin-bottom:5px; color:var(--primary-pink);"><?php echo htmlspecialchars($housing['title']); ?></h3>
                        <p style="margin-bottom:3px;"><strong>Type:</strong> <?php echo htmlspecialchars($housing['property_type']); ?></p>
                        <p style="margin-bottom:3px;"><strong>Rent:</strong> $<?php echo number_format((float)$housing['rent_amount'], 2); ?> / <?php echo htmlspecialchars($housing['rent_frequency']); ?></p>
                    </div>
                </div>

                <form action="booking.php?id=<?php echo htmlspecialchars($listing_id); ?>" method="post" id="bookingForm">
                    <div class="form-group">
                        <label for="user_email">Your Email (for confirmation)</label>
                        <input type="email" id="user_email" name="user_email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" readonly disabled 
                               style="background-color: var(--grey-background); color: var(--light-text); cursor: not-allowed;">
                        <small>This is your registered email address. Updates will be sent here.</small>
                    </div>

                    <div class="form-group">
                        <label for="move_in_date">Preferred Move-in Date</label>
                        <input type="date" id="move_in_date" name="move_in_date" 
                               value="<?php echo htmlspecialchars($_POST['move_in_date'] ?? ''); ?>" required
                               min="<?php echo date('Y-m-d'); /* Set min date to today */ ?>">
                    </div>

                    <div class="form-group">
                        <label for="user_notes">Message / Additional Notes (Optional)</label>
                        <textarea id="user_notes" name="user_notes" rows="4" placeholder="E.g., preferred viewing times, any specific requirements or questions."><?php echo htmlspecialchars($_POST['user_notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group terms-group">
                        <input type="checkbox" id="agree_terms" name="agree_terms" required 
                               <?php echo (isset($_POST['agree_terms']) && $_POST['agree_terms']) ? 'checked' : ''; ?>>
                        <label for="agree_terms">I confirm my interest and understand this is a booking request, subject to availability and approval by the housing provider. I agree to be contacted regarding this request.</label>
                    </div>

                    <button type="submit" class="btn btn-register btn-register-submit" style="width:100%;">Submit Booking Request</button>
                </form>

            <?php elseif (!$error_message && !$success_message): // No housing found, or error message already handled this ?>
                <p>The housing listing could not be found or is unavailable for booking.</p>
                <p><a href="index.php" class="btn btn-signin">Back to Listings</a></p>
            <?php endif; ?>
        </div>
    </div>

    <div id="chat-widget">
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
    <script src="script.js" defer></script>
    <script src="chatbot.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const datePicker = document.getElementById('move_in_date');
            if (datePicker) {
                const today = new Date();
                const yyyy = today.getFullYear();
                const mm = String(today.getMonth() + 1).padStart(2, '0'); // Months are 0-indexed
                const dd = String(today.getDate()).padStart(2, '0');
                const todayFormatted = `${yyyy}-${mm}-${dd}`;
                
                if (!datePicker.getAttribute('min') || datePicker.getAttribute('min') < todayFormatted) {
                    datePicker.setAttribute('min', todayFormatted);
                }
                 // If there's a POSTed value (e.g. after form error) and it's in the past, clear it or user can't submit
                if (datePicker.value && datePicker.value < todayFormatted) {
                    // datePicker.value = ''; // Or set to today, depending on desired UX
                }
            }
        });
    </script>
</body>
</html>