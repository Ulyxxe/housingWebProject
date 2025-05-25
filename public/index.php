<?php
// public/index.php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

require_once __DIR__ . '/../config/config.php'; // $pdo is defined here

// Basic Router
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string from URI
$requestPath = strtok($requestUri, '?');

// Define routes
// Format: 'METHOD /path' => [ControllerClass, 'methodName']
$routes = [
    // Housing related routes
    'GET /home' => [\App\Controllers\HousingController::class, 'listAll'], // For your old home.php
    'GET /api/listings' => [\App\Controllers\HousingController::class, 'getListingsApi'], // For your old api/getHousing.php
    'GET /housing/detail' => [\App\Controllers\HousingController::class, 'showDetail'], // Needs an ID, see below
    'GET /housing/add' => [\App\Controllers\HousingController::class, 'showAddForm'],
    'POST /housing/add' => [\App\Controllers\HousingController::class, 'processAddForm'],

    // Auth related routes (you'll create AuthController later)
    'GET /login' => [\App\Controllers\AuthController::class, 'showLoginForm'],
    'POST /login' => [\App\Controllers\AuthController::class, 'processLogin'],
    'GET /register' => [\App\Controllers\AuthController::class, 'showRegisterForm'],
    'POST /register' => [\App\Controllers\AuthController::class, 'processRegistration'],
    'GET /logout' => [\App\Controllers\AuthController::class, 'logout'],

    // Dashboard (you'll create DashboardController or UserController)
    'GET /dashboard' => [\App\Controllers\DashboardController::class, 'index'],

    // Static pages (you'll create PageController later)
    'GET /faq' => [\App\Controllers\PageController::class, 'faq'],
    'GET /help' => [\App\Controllers\PageController::class, 'help'],

    // Landing Page (if you moved old index.php to landing_page.php)
    'GET /' => function() {
        require __DIR__ . '/landing_page.php'; // Or your new path for the landing page
    },

    

    // Add more routes for booking.php, admin.php etc.
];

$routeKey = $requestMethod . ' ' . $requestPath;

$controllerAction = null;
$params = [];

// Check for routes with parameters (like /housing/detail?id=X)
// This is a very basic way to handle one parameter. A real router is more complex.
if (strpos($requestPath, '/housing/detail') === 0 && $requestMethod === 'GET') {
    if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
        $controllerAction = [\App\Controllers\HousingController::class, 'showDetail'];
        $params[] = (int)$_GET['id']; // Pass ID as parameter
    } else {
        // No ID or invalid ID, maybe show an error or redirect
    }
}
// Add similar logic for booking.php?id=X if needed for GET request to show form
// e.g., 'GET /booking' => [\App\Controllers\BookingController::class, 'showBookingForm'],
// and then extract 'id' from $_GET['id'] to pass to the controller method.

if (!$controllerAction && isset($routes[$routeKey])) {
    $controllerAction = $routes[$routeKey];
}

if ($controllerAction) {
    if (is_callable($controllerAction)) { // For simple function routes like GET /
        call_user_func($controllerAction);
    } else {
        [$controllerClass, $method] = $controllerAction;
        // Check if class and method exist
        if (class_exists($controllerClass) && method_exists($controllerClass, $method)) {
            $controllerInstance = new $controllerClass($pdo); // Pass $pdo to controller
            call_user_func_array([$controllerInstance, $method], $params);
        } else {
            http_response_code(404);
            echo "404 - Page Not Found (Controller/Method Error)";
        }
    }
} else {
    http_response_code(404);
    // You can load a specific 404 view here
    // $pageController = new \App\Controllers\PageController($pdo);
    // $pageController->notFound();
    echo "404 - Page Not Found";
}
?>