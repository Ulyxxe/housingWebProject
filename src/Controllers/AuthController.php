<?php
namespace App\Controllers;

use App\Models\UserModel;
use PDO;

class AuthController {
    private UserModel $userModel;
    // private PDO $pdo; // You might not need $pdo directly if all DB logic is in UserModel

    public function __construct(PDO $pdo) {
        // $this->pdo = $pdo;
        $this->userModel = new UserModel($pdo);
    }

    /**
     * Displays the login form.
     */
    public function showLoginForm() {
        // If user is already logged in, redirect them to dashboard
        if (isset($_SESSION['user_id'])) {
            header("Location: /dashboard"); // Use your dashboard route
            exit;
        }

        $pageTitle = "Login";
        $loginError = $_SESSION['login_error'] ?? null; // Get error from session if redirected
        $isLoggedIn = false; // For header partial

        unset($_SESSION['login_error']); // Clear it after displaying

        // Load the view for login.php
        $this->loadView('auth/login', [
            'pageTitle' => $pageTitle,
            'loginError' => $loginError,
            'isLoggedIn' => $isLoggedIn
        ]);
    }

    /**
     * Processes the login form submission.
     */
    public function processLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /login");
            exit;
        }

        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['login_error'] = "Please fill in all the fields.";
            header("Location: /login");
            exit;
        }

        $user = $this->userModel->findByEmail($email);

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['is_active'] == 1) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['user_type'] = $user['user_type'];
                // Example: Set admin flag if applicable
                // if ($user['user_type'] === 'admin') {
                //     $_SESSION['user_is_admin'] = true;
                // }


                session_regenerate_id(true);

                if (isset($_SESSION['redirect_url'])) {
                    $redirect_to = $_SESSION['redirect_url'];
                    unset($_SESSION['redirect_url']);
                    header("Location: " . $redirect_to);
                    exit;
                } else {
                    header("Location: /dashboard"); // Default redirect to dashboard
                    exit;
                }
            } else {
                $_SESSION['login_error'] = "Your account is inactive. Please contact support.";
                header("Location: /login");
                exit;
            }
        } else {
            $_SESSION['login_error'] = "Invalid email or password.";
            header("Location: /login");
            exit;
        }
    }

    /**
     * Displays the registration form.
     */
    public function showRegisterForm() {
        if (isset($_SESSION['user_id'])) {
            header("Location: /dashboard");
            exit;
        }

        $pageTitle = "Register";
        $isLoggedIn = false; // For header partial
        // Retrieve errors and old input from session if redirected
        $errors = $_SESSION['register_errors'] ?? [];
        $oldInput = $_SESSION['register_old_input'] ?? [];
        $successMessage = $_SESSION['register_success_message'] ?? '';

        // Clear them from session after retrieving
        unset($_SESSION['register_errors'], $_SESSION['register_old_input'], $_SESSION['register_success_message']);

        $this->loadView('auth/register', [
            'pageTitle' => $pageTitle,
            'errors' => $errors,
            'oldInput' => $oldInput,
            'successMessage' => $successMessage,
            'isLoggedIn' => $isLoggedIn
        ]);
    }

    /**
     * Processes the registration form submission.
     */
    public function processRegistration() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /register");
            exit;
        }

        $errors = [];
        // Store all POST data to repopulate form if there are errors
        $_SESSION['register_old_input'] = $_POST;

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $termsAgreed = isset($_POST['terms']);

        // --- Validations ---
        if (empty($firstName)) $errors['first_name'] = "First Name is required.";
        elseif (strlen($firstName) > 50) $errors['first_name'] = "First Name cannot exceed 50 characters.";

        if (empty($lastName)) $errors['last_name'] = "Last Name is required.";
        elseif (strlen($lastName) > 50) $errors['last_name'] = "Last Name cannot exceed 50 characters.";

        if (empty($username)) $errors['username'] = "Username is required.";
        elseif (strlen($username) > 50) $errors['username'] = "Username cannot exceed 50 characters.";
        elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) $errors['username'] = "Username can only contain letters, numbers, and underscores.";

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "A valid email address is required.";
        elseif (strlen($email) > 100) $errors['email'] = "Email cannot exceed 100 characters.";

        if (empty($password)) $errors['password'] = "Password is required.";
        elseif (strlen($password) < 6) $errors['password'] = "Password must be at least 6 characters long.";
        elseif ($password !== $confirmPassword) $errors['confirm_password'] = "Passwords do not match.";

        if (!$termsAgreed) $errors['terms'] = "You must agree to the Terms of Service & Privacy Policy.";

        // --- Check if username or email already exists (if no prior validation errors) ---
        if (empty($errors)) {
            if ($this->userModel->findByUsername($username)) {
                $errors['username'] = "This username is already taken. Please choose another.";
            }
            // Check email only if username was not an issue, to avoid multiple "taken" messages if both are taken by different users.
            if (empty($errors['username']) && $this->userModel->findByEmail($email)) {
                $errors['email'] = "This email address is already registered.";
            }
        }

        if (!empty($errors)) {
            $_SESSION['register_errors'] = $errors;
            header("Location: /register"); // Redirect back to form
            exit;
        }

        // --- If no errors, proceed to insert new user ---
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $userData = [
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash,
            'first_name' => $firstName,
            'last_name' => $lastName
            // 'user_type' and 'is_active' will use defaults in the model's createUser method
        ];

        $userId = $this->userModel->createUser($userData);

        if ($userId) {
            unset($_SESSION['register_old_input']); // Clear old input on success
            $_SESSION['register_success_message'] = "Registration successful! You can now <a href='/login'>log in</a>.";
            // To log the user in automatically after registration:
            // $_SESSION['user_id'] = $userId;
            // $_SESSION['username'] = $username;
            // $_SESSION['email'] = $email;
            // $_SESSION['first_name'] = $firstName;
            // $_SESSION['last_name'] = $lastName;
            // $_SESSION['user_type'] = 'student'; // Or fetch the default from user table
            // session_regenerate_id(true);
            // header("Location: /dashboard");
            // exit;

            header("Location: /register"); // Redirect to show success message on the registration page
            exit;
        } else {
            $_SESSION['register_errors'] = ['database' => "An error occurred during registration. Please try again later."];
            header("Location: /register");
            exit;
        }
    }

    /**
     * Handles user logout.
     */
    public function logout() {
        // Unset all of the session variables.
        $_SESSION = [];

        // If it's desired to kill the session, also delete the session cookie.
        // Note: This will destroy the session, and not just the session data!
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Finally, destroy the session.
        session_destroy();

        header("Location: /login"); // Redirect to login page
        exit;
    }

    // Helper to load views (you might move this to a BaseController later)
    protected function loadView(string $viewName, array $data = []) {
        extract($data); // Extracts array keys into variables ($pageTitle, $listings, etc.)

        // Construct the path to the view file
        $viewFile = __DIR__ . '/../Views/' . $viewName . '.php';

        if (file_exists($viewFile)) {
            // $isLoggedIn is now explicitly passed or determined within the controller method
            require __DIR__ . '/../Views/layout/header.php';
            require $viewFile;
            // If you have a common footer, include it here:
            // require __DIR__ . '/../Views/layout/footer.php';
            // And ensure chat-widget is included if it's global or part of footer.
            // If chat-widget is specific to some pages, include it in those views or the layout conditionally.
            if (file_exists(__DIR__ . '/../Views/layout/chat-widget.php')) {
                 // Only include if not on login/register, or if explicitly desired there
                if (!in_array($viewName, ['auth/login', 'auth/register'])) { // Example condition
                    // require __DIR__ . '/../Views/layout/chat-widget.php';
                }
            }

        } else {
            // Handle view not found
            echo "Error: View '$viewName' not found at '$viewFile'.";
        }
    }
}
?>