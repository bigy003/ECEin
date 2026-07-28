<?php
/**
 * Config locale pour hébergement mutualisé (ex: 000webhost)
 *
 * 1. Copiez ce fichier en "database.local.php" (même dossier)
 * 2. Remplacez les valeurs ci-dessous par celles de votre panneau 000webhost
 *    (section "MySQL Databases")
 * 3. Ce fichier "database.local.php" n'est PAS suivi par git (voir .gitignore),
 *    vos identifiants ne partiront donc jamais sur GitHub.
 * 4. Uploadez "database.local.php" directement sur l'hébergeur (FTP / File Manager),
 *    il ne doit exister qu'en local et sur le serveur, jamais dans le repo.
 */

define('DB_HOST', 'localhost'); // généralement 'localhost' sur 000webhost
define('DB_PORT', '3306');
define('DB_NAME', 'id12345678_reseausocial'); // ← votre nom de base
define('DB_USER', 'id12345678_ecein_user');   // ← votre utilisateur MySQL
define('DB_PASS', 'VotreMotDePasse123!');     // ← votre mot de passe MySQL
