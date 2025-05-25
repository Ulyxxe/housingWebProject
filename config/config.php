<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
echo "Mot de passe : " . $_SERVER['MYSQL_PASSWORD'];
var_dump($_SERVER['MYSQL_PASSWORD']); // ou getenv('MYSQL_PASSWORD')

exit;

$host     = $_SERVER['MYSQL_HOST'];
$port     = $_SERVER['MYSQL_PORT'];
$dbname   = $_SERVER['MYSQL_DATABASE'];
$username = $_SERVER['MYSQL_USER'];
$password = $_SERVER['MYSQL_PASSWORD'];

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connexion réussie via .env + \$_SERVER !";
} catch (PDOException $e) {
    echo "❌ Erreur PDO : " . $e->getMessage();
}


