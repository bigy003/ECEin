<?php
/**
 * Bootstrap pour les tests PHPUnit
 * 
 * Ce fichier est exécuté avant chaque test pour initialiser l'environnement :
 * - Définition des constantes nécessaires
 * - Inclusion des fichiers de configuration
 * - Configuration de la session pour les tests
 */

// Définir le chemin de base du projet
define('BASE_PATH', dirname(__DIR__));

// Démarrer la session pour les tests
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers de configuration
// Note : On évite d'inclure database.php directement pour éviter les connexions réelles
// Les tests utiliseront des mocks ou une base de test séparée
require_once __DIR__ . '/../config/config.php';

// Définir les constantes si elles n'existent pas encore (pour les tests isolés)
if (!defined('SITE_NOM')) {
    define('SITE_NOM', 'ECE In');
}

if (!defined('MAX_PHOTO_SIZE')) {
    define('MAX_PHOTO_SIZE', 5 * 1024 * 1024);
}

if (!defined('PHOTO_TYPES')) {
    define('PHOTO_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
}
