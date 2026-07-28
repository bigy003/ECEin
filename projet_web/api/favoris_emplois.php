<?php
/**
 * ECE In - API : Gestion des favoris d'emplois
 * 
 * Actions disponibles :
 * - ajouter : Ajouter une offre aux favoris
 * - retirer : Retirer une offre des favoris
 * - verifier : Vérifier si une offre est en favoris
 * - liste : Récupérer la liste des favoris d'un utilisateur
 */
require_once __DIR__ . '/../config/config.php';
requireConnexion();

header('Content-Type: application/json; charset=utf-8');

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ===================== AJOUTER UN FAVORI =====================
if ($action === 'ajouter') {
    $emploiId = (int) ($_POST['emploi_id'] ?? 0);
    
    if (!$emploiId) {
        echo json_encode(['succes' => false, 'message' => 'ID d\'offre manquant.']);
        exit();
    }
    
    // Vérifier que l'offre existe
    $stmt = $pdo->prepare("SELECT id FROM emplois WHERE id = ? AND actif = 1");
    $stmt->execute([$emploiId]);
    if (!$stmt->fetch()) {
        echo json_encode(['succes' => false, 'message' => 'Offre introuvable.']);
        exit();
    }
    
    // Vérifier si déjà en favoris
    $stmt = $pdo->prepare("SELECT id FROM favoris_emplois WHERE utilisateur_id = ? AND emploi_id = ?");
    $stmt->execute([$userId, $emploiId]);
    if ($stmt->fetch()) {
        echo json_encode(['succes' => false, 'message' => 'Cette offre est déjà dans vos favoris.']);
        exit();
    }
    
    // Ajouter aux favoris
    try {
        $pdo->prepare("INSERT INTO favoris_emplois (utilisateur_id, emploi_id) VALUES (?, ?)")
            ->execute([$userId, $emploiId]);
        
        echo json_encode(['succes' => true, 'message' => 'Offre ajoutée aux favoris.']);
    } catch (PDOException $e) {
        echo json_encode(['succes' => false, 'message' => 'Erreur lors de l\'ajout.']);
    }
    exit();
}

// ===================== RETIRER UN FAVORI =====================
if ($action === 'retirer') {
    $emploiId = (int) ($_POST['emploi_id'] ?? 0);
    
    if (!$emploiId) {
        echo json_encode(['succes' => false, 'message' => 'ID d\'offre manquant.']);
        exit();
    }
    
    // Retirer des favoris
    $stmt = $pdo->prepare("DELETE FROM favoris_emplois WHERE utilisateur_id = ? AND emploi_id = ?");
    $stmt->execute([$userId, $emploiId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['succes' => true, 'message' => 'Offre retirée des favoris.']);
    } else {
        echo json_encode(['succes' => false, 'message' => 'Offre non trouvée dans vos favoris.']);
    }
    exit();
}

// ===================== VÉRIFIER SI EN FAVORIS =====================
if ($action === 'verifier') {
    $emploiId = (int) ($_GET['emploi_id'] ?? 0);
    
    if (!$emploiId) {
        echo json_encode(['succes' => false, 'est_favori' => false]);
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT id FROM favoris_emplois WHERE utilisateur_id = ? AND emploi_id = ?");
    $stmt->execute([$userId, $emploiId]);
    $estFavori = $stmt->fetch() !== false;
    
    echo json_encode(['succes' => true, 'est_favori' => $estFavori]);
    exit();
}

// ===================== LISTE DES FAVORIS =====================
if ($action === 'liste') {
    $stmt = $pdo->prepare("
        SELECT e.*, u.nom, u.prenom, u.photo, f.date_ajout
        FROM favoris_emplois f
        JOIN emplois e ON e.id = f.emploi_id
        JOIN utilisateurs u ON u.id = e.publie_par
        WHERE f.utilisateur_id = ? AND e.actif = 1
        ORDER BY f.date_ajout DESC
    ");
    $stmt->execute([$userId]);
    $favoris = $stmt->fetchAll();
    
    echo json_encode(['succes' => true, 'favoris' => $favoris]);
    exit();
}

echo json_encode(['succes' => false, 'message' => 'Action non reconnue.']);
