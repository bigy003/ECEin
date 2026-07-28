<?php
/**
 * ECE In - Configuration de la base de données
 * 
 * Ce fichier gère la connexion à la base de données MySQL via PDO
 * 
 * Configuration :
 * - Utilise PDO (PHP Data Objects) pour une connexion sécurisée
 * - Supporte les variables d'environnement pour le déploiement cloud (Railway, etc.)
 * - Fallback sur la configuration locale MAMP par défaut
 * 
 * Sécurité :
 * - Utilise des requêtes préparées (via PDO)
 * - Gestion des erreurs avec exceptions
 * - Connexion unique réutilisée (singleton pattern)
 */

// ===================== CONFIGURATION DE LA BASE DE DONNÉES =====================
/**
 * Configuration avec support des variables d'environnement (Railway, etc.)
 * 
 * Railway peut exposer les variables de plusieurs façons :
 * 1. MYSQL_URL ou MYSQL_PRIVATE_URL (chaîne complète mysql://user:pass@host:port/db)
 * 2. Variables individuelles (MYSQLHOST, MYSQLPORT, etc.)
 * 3. Variables avec underscore (MYSQL_HOST, MYSQL_PORT, etc.)
 */

// Essayer d'abord MYSQL_PRIVATE_URL (Railway interne), puis MYSQL_URL, puis MYSQL_PUBLIC_URL
$mysqlUrl = getenv('MYSQL_PRIVATE_URL') ?: getenv('MYSQL_URL') ?: getenv('MYSQL_PUBLIC_URL');
$parsedUrl = null;

if ($mysqlUrl) {
    // Parser l'URL MySQL : mysql://user:password@host:port/database
    $parsedUrl = parse_url($mysqlUrl);
}

if ($parsedUrl && isset($parsedUrl['host'])) {
    // Utiliser les valeurs de l'URL
    $host = $parsedUrl['host'];
    define('DB_HOST', $host);
    define('DB_PORT', $parsedUrl['port'] ?? '3306');
    define('DB_NAME', ltrim($parsedUrl['path'] ?? '', '/') ?: 'railway');
    define('DB_USER', $parsedUrl['user'] ?? 'root');
    define('DB_PASS', $parsedUrl['pass'] ?? '');
} elseif (file_exists(__DIR__ . '/database.local.php')) {
    // Config locale non versionnée (ex: identifiants 000webhost).
    // Utile sur les hébergements mutualisés qui ne permettent pas de définir
    // des variables d'environnement. Ce fichier N'EST PAS commité (voir .gitignore).
    require __DIR__ . '/database.local.php';
} else {
    // Fallback : utiliser les variables individuelles
    // Configuration locale par défaut (MAMP) ou variables d'environnement pour la production
    $host = getenv('MYSQL_HOST') ?: getenv('MYSQLHOST');
    define('DB_HOST', $host ?: 'localhost');  // Local par défaut, ou variable d'environnement
    define('DB_PORT', getenv('MYSQL_PORT') ?: getenv('MYSQLPORT') ?: '3306');
    define('DB_NAME', getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE') ?: 'reseausocial');  // Local par défaut
    define('DB_USER', getenv('MYSQL_USER') ?: getenv('MYSQLUSER') ?: 'root');  // Local par défaut
    define('DB_PASS', getenv('MYSQL_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: 'root');  // Local par défaut
}

define('DB_CHARSET', 'utf8mb4');      // Encodage UTF-8 complet (supporte les emojis)

// ===================== FONCTION DE CONNEXION =====================
/**
 * Retourne une instance PDO de connexion à la base de données
 * 
 * Utilise le pattern Singleton : une seule connexion est créée et réutilisée
 * pour toutes les requêtes de la page, ce qui améliore les performances
 * 
 * Configuration PDO :
 * - ERRMODE_EXCEPTION : Lance des exceptions en cas d'erreur SQL (plus sûr)
 * - FETCH_ASSOC : Retourne les résultats sous forme de tableaux associatifs
 * - EMULATE_PREPARES : false pour utiliser les vraies requêtes préparées MySQL
 * 
 * @return PDO Instance PDO connectée à la base de données
 * @throws PDOException Si la connexion échoue
 */
function getDB(): PDO {
    // Variable statique : persiste entre les appels de la fonction
    // Cela garantit qu'une seule connexion est créée par page
    static $pdo = null;

    if ($pdo === null) {
        // Construction du DSN (Data Source Name) pour PDO
        // Forcer l'utilisation de TCP en spécifiant explicitement le port
        // PDO utilise un socket Unix si host=localhost, donc on force TCP avec 127.0.0.1 ou le host réel
        $host = DB_HOST;
        
        // Si localhost, forcer 127.0.0.1 pour utiliser TCP
        if ($host === 'localhost') {
            $host = '127.0.0.1';
        }
        
        // Construire le DSN avec le port explicite pour forcer TCP
        $dsn = "mysql:host=" . $host . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        // Options de configuration PDO pour la sécurité et les performances
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lancer des exceptions en cas d'erreur
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retourner des tableaux associatifs
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Utiliser les vraies requêtes préparées MySQL
        ];

        try {
            // Création de la connexion PDO
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // En cas d'erreur de connexion, afficher un message détaillé pour le debug
            $errorMsg = 'Erreur PDO : ' . $e->getMessage();
            $errorMsg .= "\n\nDebug info:\n";
            $errorMsg .= "Host: " . DB_HOST . "\n";
            $errorMsg .= "Port: " . DB_PORT . "\n";
            $errorMsg .= "Database: " . DB_NAME . "\n";
            $errorMsg .= "User: " . DB_USER . "\n";
            $errorMsg .= "DSN: " . $dsn . "\n";
            
            // En production, on pourrait logger l'erreur au lieu de l'afficher
            if (php_sapi_name() === 'cli' || isset($_GET['debug'])) {
                die($errorMsg);
            } else {
                die('Erreur de connexion à la base de données. Vérifiez la configuration.');
            }
        }
    }

    return $pdo;
}
