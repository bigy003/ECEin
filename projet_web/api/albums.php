<?php
/**
 * ECE In - API : Gestion des albums photos
 * 
 * Actions disponibles :
 * - creer_album : Créer un nouvel album
 * - modifier_album : Modifier un album existant
 * - supprimer_album : Supprimer un album
 * - ajouter_photo : Ajouter une photo (publication) à un album
 * - retirer_photo : Retirer une photo d'un album
 * - definir_couverture : Définir une photo comme couverture de l'album
 */
require_once __DIR__ . '/../config/config.php';
requireConnexion();

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ===================== CRÉER UN ALBUM =====================
if ($action === 'creer_album') {
    $nom         = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($nom)) {
        redirect('../profil.php?onglet=albums');
    }

    $pdo->prepare("INSERT INTO albums (utilisateur_id, nom, description) VALUES (?, ?, ?)")
        ->execute([$userId, $nom, $description]);
    
    redirect('../profil.php?onglet=albums');
}

// ===================== MODIFIER UN ALBUM =====================
if ($action === 'modifier_album') {
    $albumId     = (int) ($_POST['album_id'] ?? 0);
    $nom         = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($nom) || !$albumId) {
        redirect('../profil.php?onglet=albums');
    }

    // Vérifier que l'album appartient à l'utilisateur
    $stmt = $pdo->prepare("SELECT id FROM albums WHERE id = ? AND utilisateur_id = ?");
    $stmt->execute([$albumId, $userId]);
    if (!$stmt->fetch()) {
        redirect('../profil.php?onglet=albums');
    }

    $pdo->prepare("UPDATE albums SET nom = ?, description = ? WHERE id = ?")
        ->execute([$nom, $description, $albumId]);
    
    redirect('../profil.php?onglet=albums');
}

// ===================== SUPPRIMER UN ALBUM =====================
if ($action === 'supprimer_album') {
    $albumId = (int) ($_POST['album_id'] ?? 0);

    if (!$albumId) {
        redirect('../profil.php?onglet=albums');
    }

    // Vérifier que l'album appartient à l'utilisateur
    $stmt = $pdo->prepare("SELECT id FROM albums WHERE id = ? AND utilisateur_id = ?");
    $stmt->execute([$albumId, $userId]);
    if (!$stmt->fetch()) {
        redirect('../profil.php?onglet=albums');
    }

    // Supprimer l'album (les photos seront supprimées automatiquement grâce à CASCADE)
    $pdo->prepare("DELETE FROM albums WHERE id = ?")->execute([$albumId]);
    
    redirect('../profil.php?onglet=albums');
}

// ===================== AJOUTER UNE PHOTO À UN ALBUM =====================
if ($action === 'ajouter_photo') {
    header('Content-Type: application/json; charset=utf-8');
    
    $albumId      = (int) ($_POST['album_id'] ?? 0);
    $publicationId = (int) ($_POST['publication_id'] ?? 0);

    if (!$albumId || !$publicationId) {
        echo json_encode(['succes' => false, 'message' => t('Paramètres manquants.')]);
        exit();
    }

    // Vérifier que l'album appartient à l'utilisateur
    $stmt = $pdo->prepare("SELECT id FROM albums WHERE id = ? AND utilisateur_id = ?");
    $stmt->execute([$albumId, $userId]);
    if (!$stmt->fetch()) {
        echo json_encode(['succes' => false, 'message' => t('Album introuvable.')]);
        exit();
    }

    // Vérifier que la publication existe et appartient à l'utilisateur
    $stmt = $pdo->prepare("SELECT id, type, fichier FROM publications WHERE id = ? AND utilisateur_id = ?");
    $stmt->execute([$publicationId, $userId]);
    $publication = $stmt->fetch();
    
    if (!$publication) {
        echo json_encode(['succes' => false, 'message' => t('Publication introuvable.')]);
        exit();
    }

    // Vérifier que c'est une publication avec une photo
    if ($publication['type'] !== 'photo' || empty($publication['fichier'])) {
        echo json_encode(['succes' => false, 'message' => t('Cette publication ne contient pas de photo.')]);
        exit();
    }

    // Vérifier si la photo n'est pas déjà dans l'album
    $stmt = $pdo->prepare("SELECT * FROM album_photos WHERE album_id = ? AND publication_id = ?");
    $stmt->execute([$albumId, $publicationId]);
    if ($stmt->fetch()) {
        echo json_encode(['succes' => false, 'message' => t('Cette photo est déjà dans l\'album.')]);
        exit();
    }

    // Ajouter la photo à l'album
    $pdo->prepare("INSERT INTO album_photos (album_id, publication_id) VALUES (?, ?)")
        ->execute([$albumId, $publicationId]);

    // Si l'album n'a pas de couverture, utiliser cette photo comme couverture
    $stmt = $pdo->prepare("SELECT couverture FROM albums WHERE id = ?");
    $stmt->execute([$albumId]);
    $album = $stmt->fetch();
    if (empty($album['couverture'])) {
        $pdo->prepare("UPDATE albums SET couverture = ? WHERE id = ?")
            ->execute([$publication['fichier'], $albumId]);
    }

    echo json_encode(['succes' => true, 'message' => t('Photo ajoutée à l\'album.')]);
    exit();
}

// ===================== RETIRER UNE PHOTO D'UN ALBUM =====================
if ($action === 'retirer_photo') {
    header('Content-Type: application/json; charset=utf-8');
    
    $albumId      = (int) ($_POST['album_id'] ?? 0);
    $publicationId = (int) ($_POST['publication_id'] ?? 0);

    if (!$albumId || !$publicationId) {
        echo json_encode(['succes' => false, 'message' => t('Paramètres manquants.')]);
        exit();
    }

    // Vérifier que l'album appartient à l'utilisateur
    $stmt = $pdo->prepare("SELECT id FROM albums WHERE id = ? AND utilisateur_id = ?");
    $stmt->execute([$albumId, $userId]);
    if (!$stmt->fetch()) {
        echo json_encode(['succes' => false, 'message' => t('Album introuvable.')]);
        exit();
    }

    // Retirer la photo de l'album
    $pdo->prepare("DELETE FROM album_photos WHERE album_id = ? AND publication_id = ?")
        ->execute([$albumId, $publicationId]);

    // Si cette photo était la couverture, définir une autre photo comme couverture
    $stmt = $pdo->prepare("SELECT couverture FROM albums WHERE id = ?");
    $stmt->execute([$albumId]);
    $album = $stmt->fetch();
    if ($album['couverture']) {
        $stmt = $pdo->prepare("SELECT p.fichier FROM publications p 
                               JOIN album_photos ap ON ap.publication_id = p.id 
                               WHERE ap.album_id = ? AND p.fichier IS NOT NULL 
                               LIMIT 1");
        $stmt->execute([$albumId]);
        $nouvelleCouverture = $stmt->fetchColumn();
        $pdo->prepare("UPDATE albums SET couverture = ? WHERE id = ?")
            ->execute([$nouvelleCouverture ?: null, $albumId]);
    }

    echo json_encode(['succes' => true, 'message' => t('Photo retirée de l\'album.')]);
    exit();
}

// ===================== DÉFINIR COUVERTURE =====================
if ($action === 'definir_couverture') {
    header('Content-Type: application/json; charset=utf-8');
    
    $albumId      = (int) ($_POST['album_id'] ?? 0);
    $publicationId = (int) ($_POST['publication_id'] ?? 0);

    if (!$albumId || !$publicationId) {
        echo json_encode(['succes' => false, 'message' => t('Paramètres manquants.')]);
        exit();
    }

    // Vérifier que l'album appartient à l'utilisateur
    $stmt = $pdo->prepare("SELECT id FROM albums WHERE id = ? AND utilisateur_id = ?");
    $stmt->execute([$albumId, $userId]);
    if (!$stmt->fetch()) {
        echo json_encode(['succes' => false, 'message' => t('Album introuvable.')]);
        exit();
    }

    // Vérifier que la photo est dans l'album
    $stmt = $pdo->prepare("SELECT p.fichier FROM publications p 
                           JOIN album_photos ap ON ap.publication_id = p.id 
                           WHERE ap.album_id = ? AND p.id = ?");
    $stmt->execute([$albumId, $publicationId]);
    $photo = $stmt->fetch();
    
    if (!$photo) {
        echo json_encode(['succes' => false, 'message' => t('Photo introuvable dans l\'album.')]);
        exit();
    }

    // Définir comme couverture
    $pdo->prepare("UPDATE albums SET couverture = ? WHERE id = ?")
        ->execute([$photo['fichier'], $albumId]);

    echo json_encode(['succes' => true, 'message' => t('Couverture mise à jour.')]);
    exit();
}

// ===================== RÉCUPÉRER LES PHOTOS DISPONIBLES =====================
if ($action === 'photos_disponibles') {
    header('Content-Type: application/json; charset=utf-8');
    
    $albumId = (int) ($_GET['album_id'] ?? 0);
    
    if (!$albumId) {
        echo json_encode(['succes' => false, 'message' => t('Album introuvable.')]);
        exit();
    }
    
    // Vérifier que l'album appartient à l'utilisateur
    $stmt = $pdo->prepare("SELECT id FROM albums WHERE id = ? AND utilisateur_id = ?");
    $stmt->execute([$albumId, $userId]);
    if (!$stmt->fetch()) {
        echo json_encode(['succes' => false, 'message' => t('Album introuvable.')]);
        exit();
    }
    
    // Récupérer les photos de l'utilisateur qui ne sont pas déjà dans l'album
    $stmt = $pdo->prepare("
        SELECT p.id, p.fichier, p.contenu, p.date_publication
        FROM publications p
        WHERE p.utilisateur_id = ? AND p.type = 'photo' AND p.fichier IS NOT NULL
          AND p.id NOT IN (SELECT publication_id FROM album_photos WHERE album_id = ?)
        ORDER BY p.date_publication DESC
        LIMIT 50
    ");
    $stmt->execute([$userId, $albumId]);
    $photos = $stmt->fetchAll();
    
    echo json_encode(['succes' => true, 'photos' => $photos]);
    exit();
}

redirect('../profil.php?onglet=albums');
