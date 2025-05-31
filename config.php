<?php
// config.php

// 1) Démarrage de la session si ce n’est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2) Paramètres de connexion à MySQL – à adapter selon vos identifiants
define('DB_HOST', 'localhost');
define('DB_NAME', 'sportify');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    // DSN (Data Source Name) pour PDO
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Exceptions en cas d’erreur
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Résultats en tableaux associatifs
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Préparés “réels”
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    exit('Erreur de connexion à la base de données : ' . $e->getMessage());
}

// 3) Fonctions utilitaires pour la session
/**
 * Retourne true si l’utilisateur est connecté (user_id dans $_SESSION).
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Retourne le rôle de l’utilisateur connecté (admin|coach|client) ou null si non connecté.
 */
function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}
