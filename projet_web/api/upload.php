<?php
/**
 * ECE In - API Upload de fichiers (avatar, image de fond, photo de groupe)
 * 
 * Cette API gère l'upload de différents types de fichiers :
 * - Avatar utilisateur (photo de profil)
 * - Image de fond du profil
 * - Photo de groupe (pour les conversations de groupe)
 * 
 * Sécurité :
 * - Vérification du type MIME (uniquement images autorisées)
 * - Vérification de la taille du fichier (limites définies dans config.php)
 * - Génération de noms de fichiers uniques pour éviter les conflits
 * - Seul l'utilisateur connecté peut uploader son propre avatar/fond
 * 
 * Processus :
 * 1. Vérification de l'action et du fichier
 * 2. Validation du type MIME et de la taille
 * 3. Génération d'un nom de fichier unique
 * 4. Déplacement du fichier vers le dossier approprié
 * 5. Mise à jour de la base de données (pour avatar/fond)
 * 6. Retour de l'URL du fichier uploadé
 */
require_once __DIR__ . '/../config/config.php';
requireConnexion(); // Vérifie que l'utilisateur est connecté

header('Content-Type: application/json'); // Réponse en JSON

$pdo    = getDB(); // Connexion à la base de données
$userId = $_SESSION['utilisateur_id']; // ID de l'utilisateur connecté
$action = $_POST['action'] ?? ''; // Action à effectuer

// ===================== UPLOAD AVATAR =====================
/**
 * Upload de la photo de profil (avatar) de l'utilisateur
 * 
 * Paramètres POST :
 * - action : 'upload_avatar'
 * - avatar : Fichier image (via $_FILES)
 * 
 * Processus :
 * 1. Vérification que le fichier est présent et valide
 * 2. Validation du type MIME (PHOTO_TYPES) et de la taille (MAX_PHOTO_SIZE)
 * 3. Génération d'un nom unique : avatar_{userId}_{timestamp}.{ext}
 * 4. Déplacement vers uploads/avatars/
 * 5. Mise à jour de la colonne 'photo' dans la table utilisateurs
 * 6. Retour de l'URL du fichier
 */
if ($action === 'upload_avatar' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    
    // VALIDATION : Vérifier que le fichier est valide, du bon type et de la bonne taille
    if ($file['error'] === 0 && in_array($file['type'], PHOTO_TYPES) && $file['size'] <= MAX_PHOTO_SIZE) {
        // Extraire l'extension du fichier
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Générer un nom de fichier unique : avatar_{userId}_{timestamp}.{ext}
        // Cela évite les conflits si plusieurs utilisateurs uploadent en même temps
        $nom  = 'avatar_' . $userId . '_' . time() . '.' . $ext;
        $dest = UPLOAD_PATH . 'avatars/' . $nom;
        
        // Déplacer le fichier uploadé vers le dossier de destination
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $chemin = 'uploads/avatars/' . $nom; // Chemin relatif pour la base de données
            
            // Mettre à jour la photo de profil de l'utilisateur dans la base de données
            $pdo->prepare("UPDATE utilisateurs SET photo = ? WHERE id = ?")->execute([$chemin, $userId]);
            
            echo json_encode(['succes' => true, 'chemin' => $chemin]);
            exit();
        }
    }
    echo json_encode(['succes' => false, 'message' => t('Erreur lors du téléversement.')]);
    exit();
}

// ===================== UPLOAD IMAGE DE FOND =====================
/**
 * Upload de l'image de fond du profil utilisateur
 * 
 * Paramètres POST :
 * - action : 'upload_bg'
 * - background : Fichier image (via $_FILES)
 * 
 * Processus similaire à l'upload d'avatar, mais met à jour la colonne 'image_fond'
 */
if ($action === 'upload_bg' && isset($_FILES['background'])) {
    $file = $_FILES['background'];
    
    // VALIDATION : Vérifier que le fichier est valide, du bon type et de la bonne taille
    if ($file['error'] === 0 && in_array($file['type'], PHOTO_TYPES) && $file['size'] <= MAX_PHOTO_SIZE) {
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Générer un nom de fichier unique : bg_{userId}_{timestamp}.{ext}
        $nom  = 'bg_' . $userId . '_' . time() . '.' . $ext;
        $dest = UPLOAD_PATH . 'backgrounds/' . $nom;
        
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $chemin = 'uploads/backgrounds/' . $nom;
            
            // Mettre à jour l'image de fond de l'utilisateur dans la base de données
            $pdo->prepare("UPDATE utilisateurs SET image_fond = ? WHERE id = ?")->execute([$chemin, $userId]);
            
            echo json_encode(['succes' => true, 'chemin' => $chemin]);
            exit();
        }
    }
    echo json_encode(['succes' => false, 'message' => t('Erreur lors du téléversement.')]);
    exit();
}

// ===================== UPLOAD PHOTO DE GROUPE =====================
/**
 * Upload d'une photo pour un groupe de conversation
 * 
 * Paramètres POST :
 * - type : 'groupe'
 * - file : Fichier image (via $_FILES)
 * 
 * Différence avec avatar/fond :
 * - Ne met pas à jour directement la base de données
 * - Retourne uniquement l'URL du fichier
 * - Le frontend utilisera cette URL pour mettre à jour la photo du groupe via l'API conversations
 * 
 * Utilisé pour : Permettre aux utilisateurs de personnaliser la photo d'un groupe
 */
if (isset($_FILES['file']) && isset($_POST['type']) && $_POST['type'] === 'groupe') {
    $file = $_FILES['file'];
    
    // VALIDATION : Vérifier que le fichier est valide, du bon type et de la bonne taille
    if ($file['error'] === 0 && in_array($file['type'], PHOTO_TYPES) && $file['size'] <= MAX_PHOTO_SIZE) {
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Générer un nom de fichier unique : groupe_{timestamp}_{uniqid}.{ext}
        // Utilise uniqid() en plus du timestamp pour garantir l'unicité
        $nom  = 'groupe_' . time() . '_' . uniqid() . '.' . $ext;
        $dest = UPLOAD_PATH . 'groupes/' . $nom;
        
        // Créer le dossier s'il n'existe pas (pour éviter les erreurs)
        $dir = dirname($dest);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true); // Créer récursivement avec permissions 755
        }
        
        // Déplacer le fichier uploadé vers le dossier de destination
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $chemin = 'uploads/groupes/' . $nom;
            
            // Retourner l'URL du fichier (le frontend l'utilisera pour mettre à jour le groupe)
            echo json_encode(['succes' => true, 'url' => $chemin]);
            exit();
        }
    }
    echo json_encode(['succes' => false, 'message' => t('Erreur lors du téléversement de la photo.')]);
    exit();
}

echo json_encode(['succes' => false, 'message' => t('Action non reconnue.')]);
