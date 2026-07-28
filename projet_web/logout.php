<?php
/**
 * ECE In - Déconnexion
 * 
 * Ce script gère la déconnexion d'un utilisateur :
 * 1. Vide toutes les variables de session (session_unset)
 * 2. Détruit complètement la session (session_destroy)
 * 3. Redirige vers la page de connexion
 * 
 * Sécurité :
 * - Détruit complètement la session pour éviter toute réutilisation
 * - Redirection immédiate après destruction de la session
 */
require_once __DIR__ . '/config/config.php';

// Détruire toutes les variables de session
// session_unset() vide le tableau $_SESSION mais ne détruit pas la session elle-même
session_unset();

// Détruire complètement la session
// session_destroy() supprime la session du serveur et le cookie de session
session_destroy();

// Rediriger vers la page de connexion
redirect('login.php');
