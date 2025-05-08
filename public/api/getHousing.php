<?php
// Set the header to return JSON content
header("Content-Type: application/json");

// Database connection parameters
$host     = getenv('MYSQL_HOST');
$dbname   = getenv('MYSQL_DATABASE');
$username = getenv('MYSQL_USER');
$password = getenv('MYSQL_PASSWORD');

try {
    // Establish a new PDO connection with error handling and correct charset
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query all records from housings, plus the primary image_url
    $sql = <<<SQL
SELECT
  h.*,
  hi.image_url AS image
FROM housings AS h
LEFT JOIN housing_images AS hi
  ON hi.listing_id = h.listing_id
 AND hi.is_primary = 1
SQL;

    $stmt = $pdo->query($sql);
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
