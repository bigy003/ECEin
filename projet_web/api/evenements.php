<?php
/**
 * ECE In - API : Gestion des événements
 * 
 * Actions disponibles :
 * - creer : Créer un nouvel événement (admin uniquement)
 * - modifier : Modifier un événement existant (admin uniquement)
 * - supprimer : Supprimer un événement (admin uniquement)
 */
require_once __DIR__ . '/../config/config.php';

// Fonction utilitaire pour retourner une réponse JSON
function retournerReponseJSON($succes, $message, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['succes' => $succes, 'message' => t($message)], $data));
    exit();
}

// Vérifier la connexion
if (!estConnecte()) {
    retournerReponseJSON(false, 'Vous devez être connecté pour effectuer cette action.');
}

// Vérifier que l'utilisateur est administrateur
if (!estAdmin()) {
    retournerReponseJSON(false, 'Vous devez être administrateur pour effectuer cette action.');
}

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ===================== CRÉER UN ÉVÉNEMENT =====================
if ($action === 'creer') {
    $titre        = trim($_POST['titre'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $lieu         = trim($_POST['lieu'] ?? '');
    $date_debut   = $_POST['date_debut'] ?? '';
    $date_fin     = $_POST['date_fin'] ?? '';
    $lien         = trim($_POST['lien'] ?? '');
    $est_officiel = isset($_POST['est_officiel']) ? (int)$_POST['est_officiel'] : 0;

    if (empty($titre)) {
        retournerReponseJSON(false, 'Le titre de l\'événement est requis.');
    }

    if (empty($date_debut)) {
        retournerReponseJSON(false, 'La date de début est requise.');
    }

    // Valider le format de date
    if (!strtotime($date_debut)) {
        retournerReponseJSON(false, 'Format de date de début invalide.');
    }

    if ($date_fin && !strtotime($date_fin)) {
        retournerReponseJSON(false, 'Format de date de fin invalide.');
    }

    // Vérifier que la date de fin est après la date de début
    if ($date_fin && strtotime($date_fin) < strtotime($date_debut)) {
        retournerReponseJSON(false, 'La date de fin doit être postérieure à la date de début.');
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO evenements (organisateur_id, titre, description, lieu, date_debut, date_fin, lien, est_officiel)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $titre,
            $description ?: null,
            $lieu ?: null,
            $date_debut,
            $date_fin ?: null,
            $lien ?: null,
            $est_officiel
        ]);
        
        retournerReponseJSON(true, 'Événement créé avec succès !', [
            'evenement_id' => $pdo->lastInsertId(),
        ]);
    } catch (PDOException $e) {
        error_log("Erreur PDO creer evenement: " . $e->getMessage());
        retournerReponseJSON(false, 'Erreur lors de la création de l\'événement.');
    }
}

// ===================== MODIFIER UN ÉVÉNEMENT =====================
if ($action === 'modifier') {
    $evenementId  = (int) ($_POST['evenement_id'] ?? 0);
    $titre        = trim($_POST['titre'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $lieu         = trim($_POST['lieu'] ?? '');
    $date_debut   = $_POST['date_debut'] ?? '';
    $date_fin     = $_POST['date_fin'] ?? '';
    $lien         = trim($_POST['lien'] ?? '');
    $est_officiel = isset($_POST['est_officiel']) ? (int)$_POST['est_officiel'] : 0;

    if (empty($titre)) {
        retournerReponseJSON(false, 'Le titre de l\'événement est requis.');
    }

    if (empty($date_debut)) {
        retournerReponseJSON(false, 'La date de début est requise.');
    }

    if (!$evenementId) {
        retournerReponseJSON(false, 'Événement introuvable.');
    }

    // Valider le format de date
    if (!strtotime($date_debut)) {
        retournerReponseJSON(false, 'Format de date de début invalide.');
    }

    if ($date_fin && !strtotime($date_fin)) {
        retournerReponseJSON(false, 'Format de date de fin invalide.');
    }

    // Vérifier que la date de fin est après la date de début
    if ($date_fin && strtotime($date_fin) < strtotime($date_debut)) {
        retournerReponseJSON(false, 'La date de fin doit être postérieure à la date de début.');
    }

    // Vérifier que l'événement existe
    $stmt = $pdo->prepare("SELECT id FROM evenements WHERE id = ?");
    $stmt->execute([$evenementId]);
    if (!$stmt->fetch()) {
        retournerReponseJSON(false, 'Événement introuvable.');
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE evenements 
            SET titre = ?, description = ?, lieu = ?, date_debut = ?, date_fin = ?, lien = ?, est_officiel = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $titre,
            $description ?: null,
            $lieu ?: null,
            $date_debut,
            $date_fin ?: null,
            $lien ?: null,
            $est_officiel,
            $evenementId
        ]);
        retournerReponseJSON(true, 'Événement modifié avec succès !');
    } catch (PDOException $e) {
        error_log("Erreur PDO modifier evenement: " . $e->getMessage());
        retournerReponseJSON(false, 'Erreur lors de la modification de l\'événement.');
    }
}

// ===================== SUPPRIMER UN ÉVÉNEMENT =====================
if ($action === 'supprimer') {
    $evenementId = (int) ($_POST['evenement_id'] ?? 0);

    if (!$evenementId) {
        retournerReponseJSON(false, 'Événement introuvable.');
    }

    // Vérifier que l'événement existe
    $stmt = $pdo->prepare("SELECT id FROM evenements WHERE id = ?");
    $stmt->execute([$evenementId]);
    if (!$stmt->fetch()) {
        retournerReponseJSON(false, 'Événement introuvable.');
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM evenements WHERE id = ?");
        $stmt->execute([$evenementId]);
        retournerReponseJSON(true, 'Événement supprimé avec succès !');
    } catch (PDOException $e) {
        error_log("Erreur PDO supprimer evenement: " . $e->getMessage());
        retournerReponseJSON(false, 'Erreur lors de la suppression de l\'événement.');
    }
}

// Si aucune action reconnue
retournerReponseJSON(false, 'Action non reconnue.');
