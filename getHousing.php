<?php
// Set the header to return JSON content
header("Content-Type: application/json");

// Database connection parameters
$host   = getenv('MYSQL_HOST');
$dbname = getenv('MYSQL_DATABASE');
$dbUser = getenv('MYSQL_USER');
$dbPass = getenv('MYSQL_PASSWORD');   // Replace with your MySQL password

try {
    // Establish a new PDO connection with error handling and correct charset
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    
    // Set PDO error mode to exception for debugging
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query all records from the housing table
    $stmt = $pdo->query("SELECT * FROM housing");
    
    // Fetch the results as an associative array
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the result as a JSON response
    echo json_encode($data);
    
} catch (PDOException $e) {
    // If there is any error, send an HTTP 500 response and the error message in JSON
    http_response_code(500);
    echo json_encode([
        'error' => 'Database connection or query error: ' . $e->getMessage()
    ]);
}
?>
