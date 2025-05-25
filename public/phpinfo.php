<?php
try {
    $pdo = new PDO(
        "mysql:host=herogu.garageisep.com;port=3306;dbname=C0fg5IDZ3Q_app_g7b;charset=utf8mb4",
        "yk3Ve7Rsfs_app_g7b",
        "TIEqEsHLHXvj8z2z" // ⚠️ corrige bien ce mot de passe ici
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connexion réussie !";
} catch (PDOException $e) {
    echo "❌ Erreur de connexion : " . $e->getMessage();
}
