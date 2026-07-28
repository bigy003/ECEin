<?php
/**
 * ECE In - Configuration générale du site
 * 
 * Ce fichier contient :
 * - La configuration de la session PHP
 * - Les constantes du site (nom, slogan, contact, etc.)
 * - Les limites de taille pour les uploads
 * - Les types MIME autorisés pour les fichiers
 * - Les fonctions utilitaires (redirection, sécurité, etc.)
 * 
 * IMPORTANT : Ce fichier est inclus dans toutes les pages PHP du site
 */

// ===================== CONFIGURATION DE LA SESSION =====================
/**
 * Démarrage de session sécurisée
 * 
 * Configuration des cookies de session pour la sécurité :
 * - lifetime : Durée de vie de la session (1 heure = 3600 secondes)
 * - path : Chemin où le cookie est valide (toute l'application)
 * - secure : false en développement, true en production avec HTTPS
 * - httponly : true pour empêcher l'accès JavaScript au cookie (protection XSS)
 * - samesite : 'Strict' pour empêcher l'envoi du cookie lors de requêtes cross-site (protection CSRF)
 * 
 * Note : session_start() est appelé uniquement si aucune session n'est déjà active
 */
if (session_status() === PHP_SESSION_NONE) {
    // Détecter si on est en production (Railway fournit HTTPS automatiquement)
    $isProduction = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' 
                    || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
                    || getenv('RAILWAY_ENVIRONMENT') !== false;
    
    session_set_cookie_params([
        'lifetime' => 3600, // 1 heure
        'path'     => '/',
        'secure'   => $isProduction, // true en production (HTTPS), false en développement
        'httponly' => true,  // Empêche l'accès JavaScript (protection XSS)
        'samesite' => 'Strict', // Protection CSRF
    ]);
    session_start();
}

// Inclusion de la configuration de base de données
// Ce fichier contient la fonction getDB() pour se connecter à MySQL
require_once __DIR__ . '/database.php';

// ===================== INFORMATIONS DU SITE =====================
/**
 * Constantes définissant les informations générales du site
 * Utilisées dans les en-têtes, pieds de page, emails, etc.
 */
define('SITE_NOM', 'ECE In');
define('SITE_SLOGAN', 'Le réseau social professionnel de la communauté ECE Paris');
define('SITE_EMAIL', 'contact@ecein.ece.fr');
define('SITE_TEL', '+33 1 44 39 06 00');
define('SITE_ADRESSE', '37 Quai de Grenelle, 75015 Paris');

// SITE_URL : Détection automatique de l'URL (local ou production)
if (getenv('RAILWAY_PUBLIC_DOMAIN')) {
    // Production Railway
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                ? 'https' : 'http';
    define('SITE_URL', $protocol . '://' . getenv('RAILWAY_PUBLIC_DOMAIN'));
} elseif (isset($_SERVER['HTTP_HOST'])) {
    // Détection automatique depuis la requête
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                ? 'https' : 'http';
    define('SITE_URL', $protocol . '://' . $_SERVER['HTTP_HOST']);
} else {
    // Fallback local
    define('SITE_URL', 'http://localhost/ecein');
}

// ===================== CHEMINS =====================
/**
 * Chemins de base pour les fichiers
 * BASE_PATH : Racine du projet
 * UPLOAD_PATH : Dossier où sont stockés les fichiers uploadés
 */
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads/');

// ===================== LIMITES D'UPLOAD =====================
/**
 * Tailles maximales des fichiers uploadés (en octets)
 * Ces limites sont vérifiées avant l'upload pour éviter les abus
 */
define('MAX_PHOTO_SIZE', 5 * 1024 * 1024);    // 5 Mo pour les images
define('MAX_VIDEO_SIZE', 50 * 1024 * 1024);   // 50 Mo pour les vidéos
define('MAX_CV_SIZE', 10 * 1024 * 1024);      // 10 Mo pour les CV/documents

// ===================== TYPES DE FICHIERS AUTORISÉS =====================
/**
 * Types MIME autorisés pour chaque catégorie de fichier
 * Ces listes sont utilisées pour valider les fichiers uploadés
 * 
 * PHOTO_TYPES : Images pour avatars, photos de profil, publications photo
 * VIDEO_TYPES : Vidéos pour publications vidéo
 * CV_TYPES : Documents PDF/XML pour les CV
 */
define('PHOTO_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/ogg']);
define('CV_TYPES', ['application/pdf', 'application/xml', 'text/xml']);

// ===================== FONCTIONS UTILITAIRES =====================

/**
 * Redirige vers une page et arrête l'exécution du script
 * 
 * Utilisation : redirect('index.php') ou redirect('login.php')
 * 
 * @param string $url URL relative ou absolue vers laquelle rediriger
 * @return void
 */
function redirect(string $url): void {
    header("Location: $url");
    exit(); // Arrêter l'exécution immédiatement après la redirection
}

/**
 * Vérifie si l'utilisateur est actuellement connecté
 * 
 * Vérifie la présence de 'utilisateur_id' dans la session
 * 
 * @return bool true si connecté, false sinon
 */
function estConnecte(): bool {
    return isset($_SESSION['utilisateur_id']);
}

/**
 * Vérifie si l'utilisateur connecté est administrateur
 * 
 * Vérifie que le rôle dans la session est 'administrateur'
 * 
 * @return bool true si administrateur, false sinon
 */
function estAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'administrateur';
}

/**
 * Redirige vers la page de connexion si l'utilisateur n'est pas connecté
 * 
 * Utilisation : Appeler au début des pages qui nécessitent une connexion
 * Exemple : requireConnexion(); // au début de index.php
 * 
 * @return void
 */
function requireConnexion(): void {
    if (!estConnecte()) {
        redirect('login.php');
    }
}

/**
 * Redirige vers la page d'accueil si l'utilisateur n'est pas administrateur
 * 
 * Utilisation : Appeler au début des pages d'administration
 * Exemple : requireAdmin(); // au début de admin/index.php
 * 
 * Note : Appelle d'abord requireConnexion() pour vérifier la connexion
 * 
 * @return void
 */
function requireAdmin(): void {
    requireConnexion(); // Vérifier d'abord que l'utilisateur est connecté
    if (!estAdmin()) {
        redirect('index.php');
    }
}

/**
 * Échappe les données HTML pour prévenir les attaques XSS (Cross-Site Scripting)
 * 
 * Convertit les caractères spéciaux HTML en entités HTML
 * Exemple : <script> devient &lt;script&gt;
 * 
 * Utilisation : Toujours utiliser cette fonction avant d'afficher des données utilisateur
 * Exemple : <?= h($user['nom']) ?>
 * 
 * @param string $data Données à échapper
 * @return string Données échappées (sécurisées pour l'affichage HTML)
 */
function h(string $data): string {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Récupère les informations complètes de l'utilisateur connecté
 * 
 * Utilise un cache statique pour éviter de multiples requêtes SQL
 * lors d'un même chargement de page
 * 
 * @return array|null Tableau associatif avec toutes les infos utilisateur, ou null si non connecté
 */
function getUtilisateurConnecte(): ?array {
    if (!estConnecte()) return null;

    // Cache statique : la variable $utilisateur persiste entre les appels de la fonction
    // Cela évite de refaire la requête SQL si la fonction est appelée plusieurs fois
    static $utilisateur = null;
    if ($utilisateur === null) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
        $stmt->execute([$_SESSION['utilisateur_id']]);
        $utilisateur = $stmt->fetch();
    }
    return $utilisateur;
}

/**
 * Récupère le nombre de notifications non lues pour l'utilisateur connecté
 * 
 * Utilisé pour afficher le badge de notifications dans la navbar
 * 
 * @return int Nombre de notifications non lues (0 si non connecté)
 */
function getNbNotificationsNonLues(): int {
    if (!estConnecte()) return 0;
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE utilisateur_id = ? AND lue = 0");
    $stmt->execute([$_SESSION['utilisateur_id']]);
    return (int) $stmt->fetchColumn();
}

/**
 * Récupère le nombre de conversations avec des messages non lus
 * 
 * Compte le nombre de conversations distinctes où l'utilisateur a reçu
 * des messages non lus (pas envoyés par lui-même)
 * 
 * Utilisé pour afficher le badge de messages dans la navbar
 * 
 * @return int Nombre de conversations avec messages non lus (0 si non connecté)
 */
function getNbMessagesNonLus(): int {
    if (!estConnecte()) return 0;
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT m.conversation_id)
        FROM messages m
        JOIN conversation_participants cp ON cp.conversation_id = m.conversation_id
        WHERE cp.utilisateur_id = ? AND m.expediteur_id != ? AND m.lu = 0
    ");
    $stmt->execute([$_SESSION['utilisateur_id'], $_SESSION['utilisateur_id']]);
    return (int) $stmt->fetchColumn();
}

/**
 * Vérifie si deux utilisateurs sont amis
 * @param int $userId1
 * @param int $userId2
 * @return string|null ('accepte', 'en_attente', null)
 */
function statutConnexion(int $userId1, int $userId2): ?string {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT statut FROM connexions
        WHERE (demandeur_id = ? AND destinataire_id = ?)
           OR (demandeur_id = ? AND destinataire_id = ?)
    ");
    $stmt->execute([$userId1, $userId2, $userId2, $userId1]);
    $res = $stmt->fetch();
    return $res ? $res['statut'] : null;
}

/**
 * Formate une date en français
 * @param string $date
 * @return string
 */
function formatDateFr(string $date): string {
    $mois = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
    ];
    $ts = strtotime($date);
    return date('j', $ts) . ' ' . $mois[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

/**
 * Calcule le temps écoulé depuis une date
 * @param string $date
 * @return string
 */
function tempsEcoule(string $date): string {
    $diff = time() - strtotime($date);
    // Afficher l'heure exacte (HH:MM) pour les messages récents (moins de 24h)
    if ($diff < 86400) {
        return date('H:i', strtotime($date));
    }
    if ($diff < 604800) return floor($diff / 86400) . ' j';
    return formatDateFr($date);
}

/**
 * Crée une notification
 */
function creerNotification(int $userId, ?int $expediteurId, string $type, string $message, ?string $lien = null): void {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO notifications (utilisateur_id, expediteur_id, type, message, lien)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $expediteurId, $type, $message, $lien]);
}

/**
 * Détecte les jours spéciaux pour adapter le thème
 * @return array ['theme' => string, 'message' => string]
 */
function getJourSpecial(): array {
    $jour = (int) date('j');
    $mois = (int) date('n');

    $joursSpeciaux = [
        ['mois' => 2, 'jour' => 14, 'theme' => 'saint-valentin', 'message' => '💝 Bonne Saint-Valentin à tous !'],
        ['mois' => 5, 'jour_range' => [25, 31], 'semaine' => 0, 'theme' => 'fete-meres', 'message' => '🌸 Bonne Fête des Mères !'],
        ['mois' => 6, 'jour_range' => [15, 21], 'semaine' => 0, 'theme' => 'fete-peres', 'message' => '👔 Bonne Fête des Pères !'],
        ['mois' => 12, 'jour' => 25, 'theme' => 'noel', 'message' => '🎄 Joyeux Noël à tous !'],
        ['mois' => 12, 'jour' => 31, 'theme' => 'nouvel-an', 'message' => '🎆 Bonne Année 2027 !'],
        ['mois' => 1, 'jour' => 1, 'theme' => 'nouvel-an', 'message' => '🎉 Bonne Année !'],
    ];

    foreach ($joursSpeciaux as $special) {
        if (isset($special['jour']) && $special['mois'] === $mois && $special['jour'] === $jour) {
            return ['theme' => $special['theme'], 'message' => $special['message']];
        }
    }

    return ['theme' => '', 'message' => ''];
}
