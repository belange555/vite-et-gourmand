
<?php

// Détecter la base de données hébergée sur Heroku
$urlBase = getenv('JAWSDB_URL') ?: getenv('JAWSDB_MARIA_URL');

if ($urlBase) {

    // Récupérer les informations de connexion depuis JawsDB
    $informations = parse_url($urlBase);

    if ($informations === false) {
        die("Configuration de la base de données invalide.");
    }

    $host = $informations['host'];
    $dbname = rawurldecode(ltrim($informations['path'], '/'));
    $username = rawurldecode($informations['user']);
    $password = rawurldecode($informations['pass']);
    $port = $informations['port'] ?? 3306;

} else {

    // Configuration locale avec XAMPP
    $host = 'localhost';
    $dbname = 'vite-et-gourmand';
    $username = 'root';
    $password = '';
    $port = 3306;
}

try {

    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    error_log("Erreur de connexion à la base de données : " . $e->getMessage());

    die("Erreur de connexion à la base de données.");
}
