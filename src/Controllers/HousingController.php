<?php
namespace App\Controllers;

use App\Models\HousingModel; // Use your model
use PDO;

class HousingController {
    private HousingModel $housingModel;
    private PDO $pdo; // Keep PDO if needed for other things or pass to more models

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo; // Store PDO
        $this->housingModel = new HousingModel($pdo); // Instantiate the model
    }

    // Corresponds to public/home.php
    public function listAll() {
        // Data fetching is now in the model
        // $listings = $this->housingModel->getAllListings(); // This would be for the main listing page

        // For now, this is just an example placeholder action
        // In a real scenario, you'd fetch data and pass it to a view
        // For the home.php page, you actually don't need to fetch data here
        // as it's fetched by JavaScript via api/getHousing.php
        // So this controller action might just load the view.

        $pageTitle = "Find Student Housing";
        // Load the view for home.php
        $this->loadView('housing/list', ['pageTitle' => $pageTitle]);
    }

    // Corresponds to api/getHousing.php
    public function getListingsApi() {
        $listings = $this->housingModel->getAllListings();
        header('Content-Type: application/json');
        echo json_encode($listings);
        exit; // Important for API endpoints
    }

    // Corresponds to public/housing-detail.php
    public function showDetail(int $id) {
        $housing = $this->housingModel->findById($id);
        $isLoggedIn = isset($_SESSION['user_id']); // Example of data needed by view

        if (!$housing) {
            // Handle not found, e.g., show 404 view
            $this->loadView('errors/404', ['pageTitle' => 'Listing Not Found']);
            return;
        }
        $pageTitle = htmlspecialchars($housing['title']) . " - Details";
        $this->loadView('housing/detail', [
            'housing' => $housing,
            'pageTitle' => $pageTitle,
            'isLoggedIn' => $isLoggedIn
            // You'll need to fetch other images here or in the view if the model doesn't get all
        ]);
    }

    // Corresponds to GET request for public/add-housing.php
    public function showAddForm() {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['redirect_url'] = '/housing/add'; // Or whatever your route will be
            header("Location: /login"); // Redirect to login route
            exit;
        }

        $pageTitle = "Add New Housing";
        $isLoggedIn = true; // User must be logged in
        // Pass any necessary data like property types, current user email for default etc.
        $property_types = ['Studio', 'Apartment', 'Shared Room', 'House', 'Other'];
        $rent_frequencies = ['monthly', 'weekly', 'annually'];
        $listing_statuses = ['available', 'pending_approval', 'unavailable'];
        $userEmail = $_SESSION['email'] ?? '';

        $this->loadView('housing/add', [
            'pageTitle' => $pageTitle,
            'isLoggedIn' => $isLoggedIn,
            'errors' => $_SESSION['form_errors'] ?? [], // Get errors from session if redirected
            'old_input' => $_SESSION['form_old_input'] ?? [], // Get old input from session
            'success_message' => $_SESSION['form_success_message'] ?? '',
            'property_types' => $property_types,
            'rent_frequencies' => $rent_frequencies,
            'listing_statuses' => $listing_statuses,
            'userEmail' => $userEmail
        ]);
        // Clear session messages after displaying
        unset($_SESSION['form_errors'], $_SESSION['form_old_input'], $_SESSION['form_success_message']);
    }

    // Corresponds to POST request from public/add-housing.php
    public function processAddForm() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403); // Forbidden
            echo "Not authorized";
            exit;
        }

        // --- Form Data Retrieval & Basic Validation (Keep this logic) ---
        // This part is largely the same as your current add-housing.php
        $errors = [];
        $input = $_POST; // Keep all input for repopulating form
        $_SESSION['form_old_input'] = $input;


        $user_id = $_SESSION['user_id'];
        $title = trim($_POST['title'] ?? '');
        // ... (all other $_POST retrievals and validations from your add-housing.php)
        $description = trim($_POST['description'] ?? '');
        // ...
        $status = in_array($_POST['status'] ?? '', ['available', 'pending_approval', 'unavailable']) ? $_POST['status'] : 'pending_approval';

        // Basic Validations
        if (empty($title)) $errors['title'] = "Title is required.";
        // ... (all other validations from your add-housing.php)

        // --- Image Validation (Keep this logic) ---
        // Define UPLOAD_DIR_BASE, MAX_FILE_SIZE, $allowed_mime_types, $allowed_extensions
        // This part is complex and should ideally be a separate service/helper class,
        // but for now, you can adapt it here.
        define('UPLOAD_DIR_BASE_CTRL', __DIR__ . '/../../public/assets/uploads/housing_images/');
        $uploaded_primary_image_path_db = null; // Path for DB
        // ... (your image validation logic from add-housing.php) ...
        // If primary image is valid:
        if (isset($_FILES['primary_image']) && $_FILES['primary_image']['error'] === UPLOAD_ERR_OK /* && no errors from validation */) {
            // Generate unique filename, move file, set $uploaded_primary_image_path_db
            // Example:
            // $file_extension = strtolower(pathinfo($_FILES['primary_image']['name'], PATHINFO_EXTENSION));
            // $new_filename = uniqid('primary_', true) . '.' . $file_extension;
            // $destination_path = UPLOAD_DIR_BASE_CTRL . $new_filename;
            // if (move_uploaded_file($_FILES['primary_image']['tmp_name'], $destination_path)) {
            //     $uploaded_primary_image_path_db = 'assets/uploads/housing_images/' . $new_filename;
            // } else { $errors['primary_image_move'] = "Failed to move primary image."; }
        }


        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            header("Location: /housing/add"); // Redirect back to form (use your route)
            exit;
        }

        // --- If no errors, insert into database via Model ---
        $housingData = [
            'user_id' => $user_id,
            'title' => $title,
            'description' => $description,
            // ... all other fields for the housings table ...
            'address_street' => trim($_POST['address_street'] ?? ''),
            'address_city' => trim($_POST['address_city'] ?? ''),
            'address_state' => trim($_POST['address_state'] ?? ''),
            'address_zipcode' => trim($_POST['address_zipcode'] ?? ''),
            'address_country' => trim($_POST['address_country'] ?? ''),
            'latitude' => filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT, ['flags' => FILTER_NULL_ON_FAILURE]),
            'longitude' => filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT, ['flags' => FILTER_NULL_ON_FAILURE]),
            'property_type' => $_POST['property_type'] ?? null,
            'rent_amount' => filter_input(INPUT_POST, 'rent_amount', FILTER_VALIDATE_FLOAT, ['flags' => FILTER_NULL_ON_FAILURE]),
            'rent_frequency' => $_POST['rent_frequency'] ?? null,
            'num_bedrooms' => filter_input(INPUT_POST, 'num_bedrooms', FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE, 'options' => ['min_range' => 0]]),
            'num_bathrooms' => filter_input(INPUT_POST, 'num_bathrooms', FILTER_VALIDATE_FLOAT, ['flags' => FILTER_NULL_ON_FAILURE, 'options' => ['min_range' => 0]]),
            'square_footage' => filter_input(INPUT_POST, 'square_footage', FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE, 'options' => ['min_range' => 1]]),
            'availability_date' => trim($_POST['availability_date'] ?? ''), // Ensure valid date format
            'lease_term_months' => filter_input(INPUT_POST, 'lease_term_months', FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE, 'options' => ['min_range' => 1]]),
            'is_furnished' => isset($_POST['is_furnished']) ? 1 : 0,
            'allows_pets' => isset($_POST['allows_pets']) ? 1 : 0,
            'contact_email' => filter_input(INPUT_POST, 'contact_email', FILTER_VALIDATE_EMAIL),
            'contact_phone' => trim($_POST['contact_phone'] ?? ''),
            'status' => $status,
        ];

        // Begin transaction (optional, but good for multi-step inserts like housing + images)
        // $this->pdo->beginTransaction();

        $listing_id = $this->housingModel->addListing($housingData);

        if ($listing_id) {
            // Handle image uploads and add to housing_images table using $this->housingModel->addImage()
            // This needs the full image upload logic from your add-housing.php, adapted
            // Primary Image
            if (isset($_FILES['primary_image']) && $_FILES['primary_image']['error'] === UPLOAD_ERR_OK) {
                $file_extension = strtolower(pathinfo($_FILES['primary_image']['name'], PATHINFO_EXTENSION));
                $new_filename = uniqid('primary_', true) . '.' . $file_extension;
                $destination_path = UPLOAD_DIR_BASE_CTRL . $new_filename; // Use the constant defined in controller
                $relative_path_db = 'assets/uploads/housing_images/' . $new_filename;

                if (move_uploaded_file($_FILES['primary_image']['tmp_name'], $destination_path)) {
                    if(!$this->housingModel->addImage($listing_id, $relative_path_db, true)) {
                        // Handle image DB insert error, maybe rollback
                        $errors['database'] = "Failed to save primary image record.";
                    }
                } else {
                    $errors['primary_image_move'] = "Failed to move primary uploaded image.";
                }
            }
            // Other Images (loop)
            // ...

            if (empty($errors)) {
                // $this->pdo->commit();
                unset($_SESSION['form_old_input']); // Clear old input on success
                $_SESSION['form_success_message'] = "Housing listing added successfully!";
                header("Location: /dashboard"); // Redirect to dashboard or success page
                exit;
            } else {
                // $this->pdo->rollBack();
                $_SESSION['form_errors'] = $errors;
                header("Location: /housing/add"); // Redirect back with errors
                exit;
            }

        } else {
            // $this->pdo->rollBack();
            $errors['database'] = "Failed to add listing details. Please try again.";
            $_SESSION['form_errors'] = $errors;
            header("Location: /housing/add"); // Redirect back with errors
            exit;
        }
    }


    // Helper to load views
    protected function loadView(string $viewName, array $data = []) {
        extract($data); // Extracts array keys into variables ($pageTitle, $listings, etc.)

        // Construct the path to the view file
        $viewFile = __DIR__ . '/../Views/' . $viewName . '.php';

        if (file_exists($viewFile)) {
            // Include a common layout/header/footer if you have one
            // For example, a simple layout:
            // require __DIR__ . '/../Views/layout/header.php'; // Passes $pageTitle, $isLoggedIn etc.
            require $viewFile;
            // require __DIR__ . '/../Views/layout/footer.php';
        } else {
            // Handle view not found
            // For now, just echo an error
            echo "Error: View '$viewName' not found.";
        }
    }
}
?>