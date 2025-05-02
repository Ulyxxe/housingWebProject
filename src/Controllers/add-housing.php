<?php
// housing-detail.php
require_once 'config.php'; // Contains PDO/DB connection info

// Validate GET parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid housing ID.');
}

$id = intval($_GET['id']);

try {
    // Assume $pdo is already set up in config.php
    $stmt = $pdo->prepare("SELECT * FROM Housing WHERE id = ?");
    $stmt->execute([$id]);
    $housing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$housing) {
        die("Housing not found.");
    }
} catch (PDOException $e) {
    die("Error fetching housing details: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($housing['name']); ?> - Details</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <h1><?php echo htmlspecialchars($housing['name']); ?></h1>
  <img src="<?php echo htmlspecialchars($housing['image']); ?>" alt="<?php echo htmlspecialchars($housing['name']); ?>" style="max-width:300px;">
  <ul>
    <li><strong>Price:</strong> $<?php echo number_format($housing['price'], 2); ?></li>
    <li><strong>Size:</strong> <?php echo htmlspecialchars($housing['size']); ?> m²</li>
    <li><strong>Type:</strong> <?php echo htmlspecialchars($housing['type']); ?></li>
    <li><strong>Rating:</strong> <?php echo htmlspecialchars($housing['rating']); ?> ★</li>
    <li><strong>Location:</strong> Lat: <?php echo htmlspecialchars($housing['lat']); ?>, Lng: <?php echo htmlspecialchars($housing['lng']); ?></li>
  </ul>
  <p><a href="index.php">Back to listings</a></p>
</body>
</html>
