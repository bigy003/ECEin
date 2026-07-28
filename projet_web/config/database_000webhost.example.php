<?php
/**
 * EXEMPLE DE CONFIGURATION POUR 000WEBHOST
 * 
 * Ce fichier est un EXEMPLE. Ne l'utilisez pas directement !
 * 
 * Instructions :
 * 1. Copiez les valeurs ci-dessous
 * 2. Ouvrez config/database.php
 * 3. Remplacez les valeurs par défaut (lignes 48-52) par vos valeurs 000webhost
 * 
 * OU
 * 
 * Modifiez directement config/database.php en remplaçant les lignes 48-52 par :
 */

// ===================== CONFIGURATION POUR 000WEBHOST =====================
// Remplacez ces valeurs par celles fournies par 000webhost

// Option 1 : Modifier directement dans config/database.php (lignes 48-52)
// Remplacez cette section :
/*
} else {
    // Fallback : utiliser les variables individuelles
    $host = getenv('MYSQL_HOST') ?: getenv('MYSQLHOST');
    define('DB_HOST', $host ?: 'localhost');  // ← Remplacez 'localhost' si nécessaire
    define('DB_PORT', getenv('MYSQL_PORT') ?: getenv('MYSQLPORT') ?: '3306');
    define('DB_NAME', getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE') ?: 'reseausocial');  // ← Votre nom de base
    define('DB_USER', getenv('MYSQL_USER') ?: getenv('MYSQLUSER') ?: 'root');  // ← Votre utilisateur MySQL
    define('DB_PASS', getenv('MYSQL_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: 'root');  // ← Votre mot de passe MySQL
}
*/

// Par cette version (exemple avec des valeurs fictives) :
/*
} else {
    // Configuration 000webhost
    $host = getenv('MYSQL_HOST') ?: getenv('MYSQLHOST');
    define('DB_HOST', $host ?: 'localhost');  // Généralement 'localhost' sur 000webhost
    define('DB_PORT', getenv('MYSQL_PORT') ?: getenv('MYSQLPORT') ?: '3306');
    define('DB_NAME', getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE') ?: 'id12345678_reseausocial');  // ← VOTRE NOM DE BASE
    define('DB_USER', getenv('MYSQL_USER') ?: getenv('MYSQLUSER') ?: 'id12345678_ecein_user');  // ← VOTRE UTILISATEUR
    define('DB_PASS', getenv('MYSQL_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: 'VotreMotDePasse123!');  // ← VOTRE MOT DE PASSE
}
*/

// ===================== OÙ TROUVER CES INFORMATIONS ? =====================
/*
1. Connectez-vous à votre compte 000webhost
2. Allez dans "MySQL Databases"
3. Vous verrez :
   - Database Name : id12345678_reseausocial (c'est DB_NAME)
   - Username : id12345678_ecein_user (c'est DB_USER)
   - Password : (celui que vous avez créé, c'est DB_PASS)
   - Host : généralement "localhost" (c'est DB_HOST)
   - Port : généralement "3306" (c'est DB_PORT)
*/

// ===================== EXEMPLE DE VALEURS RÉELLES =====================
/*
Sur 000webhost, les noms de base et utilisateurs commencent souvent par "id" suivi de chiffres.

Exemple réel :
- DB_HOST : localhost
- DB_PORT : 3306
- DB_NAME : id12345678_reseausocial
- DB_USER : id12345678_ecein_user
- DB_PASS : MonMotDePasseSecurise2026!
*/
