<?php
namespace App\Models;

use PDO;
use PDOException;

class UserModel {
    private PDO $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    /**
     * Finds a user by their email address.
     * @param string $email
     * @return array|null User data as an associative array, or null if not found.
     */
    public function findByEmail(string $email): ?array {
        try {
            $stmt = $this->db->prepare(
                "SELECT user_id, username, email, password_hash, first_name, last_name, user_type, is_active
                 FROM users
                 WHERE email = :email
                 LIMIT 1"
            );
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching user by email {$email}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Finds a user by their username.
     * @param string $username
     * @return array|null User data (only user_id for checking existence), or null if not found.
     */
    public function findByUsername(string $username): ?array {
        try {
            // Only need user_id to check for existence, but you can select more if needed elsewhere.
            $stmt = $this->db->prepare("SELECT user_id FROM users WHERE username = :username LIMIT 1");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching user by username {$username}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Creates a new user.
     * @param array $data User data (must include: username, email, password_hash, first_name, last_name).
     *                    Optional: user_type (defaults to 'student'), is_active (defaults to 1).
     * @return int|null The ID of the newly created user, or null on failure.
     */
    public function createUser(array $data): ?int {
        // Ensure required fields are present (basic check, controller should do more thorough validation)
        if (empty($data['username']) || empty($data['email']) || empty($data['password_hash']) || empty($data['first_name']) || empty($data['last_name'])) {
            error_log("Create User Error: Missing required data.");
            return null;
        }

        $sql = "INSERT INTO users (username, email, password_hash, first_name, last_name, user_type, is_active, created_at, updated_at)
                VALUES (:username, :email, :password_hash, :first_name, :last_name, :user_type, :is_active, NOW(), NOW())";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':username', $data['username']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':password_hash', $data['password_hash']);
            $stmt->bindParam(':first_name', $data['first_name']);
            $stmt->bindParam(':last_name', $data['last_name']);

            // Provide defaults if not present in $data
            $userType = $data['user_type'] ?? 'student';
            $isActive = $data['is_active'] ?? 1; // New users are active by default

            $stmt->bindParam(':user_type', $userType);
            $stmt->bindParam(':is_active', $isActive, PDO::PARAM_INT);

            $stmt->execute();
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Create User DB Error: " . $e->getMessage());
            // You could throw a custom exception here for duplicate entries if desired,
            // for the controller to catch and provide a more specific user message.
            // Example:
            // if ($e->getCode() == '23000') { // Integrity constraint violation
            //     if (str_contains(strtolower($e->getMessage()), 'duplicate entry') && str_contains(strtolower($e->getMessage()), 'for key \'users.username_unique\'')) {
            //         throw new \App\Exceptions\DuplicateUsernameException("Username already exists.");
            //     }
            //     if (str_contains(strtolower($e->getMessage()), 'duplicate entry') && str_contains(strtolower($e->getMessage()), 'for key \'users.email_unique\'')) {
            //         throw new \App\Exceptions\DuplicateEmailException("Email already exists.");
            //     }
            // }
            return null;
        }
    }

    // Add other user-related methods as needed, e.g.:
    // public function findById(int $userId): ?array
    // public function updateUser(int $userId, array $data): bool
    // public function activateUser(int $userId): bool
}
?>