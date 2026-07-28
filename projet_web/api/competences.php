<?php
/**
 * ECE In - API : Gestion des compétences
 * 
 * Actions disponibles :
 * - ajouter : Ajouter une nouvelle compétence
 * - modifier : Modifier une compétence existante
 * - supprimer : Supprimer une compétence
 * - lister : Récupérer toutes les compétences de l'utilisateur
 */
require_once __DIR__ . '/../config/config.php';

// Fonction utilitaire pour retourner une réponse JSON
function retournerReponseJSON($succes, $message, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['succes' => $succes, 'message' => $message], $data));
    exit();
}

// Vérifier la connexion
if (!estConnecte()) {
    retournerReponseJSON(false, 'Vous devez être connecté pour effectuer cette action.');
}

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ===================== AJOUTER UNE COMPÉTENCE =====================
if ($action === 'ajouter') {
    $nom    = trim($_POST['nom'] ?? '');
    $niveau = $_POST['niveau'] ?? 'intermediaire';

    if (empty($nom)) {
        retournerReponseJSON(false, 'Le nom de la compétence ne peut pas être vide.');
    }

    // Vérifier que le niveau est valide
    $niveauxValides = ['debutant', 'intermediaire', 'avance', 'expert'];
    if (!in_array($niveau, $niveauxValides)) {
        $niveau = 'intermediaire';
    }

    // Vérifier si la compétence existe déjà pour cet utilisateur
    $stmt = $pdo->prepare("SELECT id FROM competences WHERE utilisateur_id = ? AND nom = ?");
    $stmt->execute([$userId, $nom]);
    if ($stmt->fetch()) {
        retournerReponseJSON(false, 'Cette compétence existe déjà.');
    }

    try {
        // Récupérer le dernier ordre pour placer la nouvelle compétence à la fin
        $stmtOrdre = $pdo->prepare("SELECT MAX(ordre) FROM competences WHERE utilisateur_id = ?");
        $stmtOrdre->execute([$userId]);
        $ordre = (int)$stmtOrdre->fetchColumn() + 1;

        $stmt = $pdo->prepare("INSERT INTO competences (utilisateur_id, nom, niveau, ordre) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $nom, $niveau, $ordre]);
        
        retournerReponseJSON(true, 'Compétence ajoutée avec succès !', [
            'competence_id' => $pdo->lastInsertId(),
        ]);
    } catch (PDOException $e) {
        error_log("Erreur PDO ajouter competence: " . $e->getMessage());
        retournerReponseJSON(false, 'Erreur lors de l\'ajout de la compétence.');
    }
}

// ===================== MODIFIER UNE COMPÉTENCE =====================
if ($action === 'modifier') {
    $competenceId = (int) ($_POST['competence_id'] ?? 0);
    $nom          = trim($_POST['nom'] ?? '');
    $niveau       = $_POST['niveau'] ?? 'intermediaire';

    if (empty($nom)) {
        retournerReponseJSON(false, 'Le nom de la compétence ne peut pas être vide.');
    }

    if (!$competenceId) {
        retournerReponseJSON(false, 'Compétence introuvable.');
    }

    // Vérifier que le niveau est valide
    $niveauxValides = ['debutant', 'intermediaire', 'avance', 'expert'];
    if (!in_array($niveau, $niveauxValides)) {
        $niveau = 'intermediaire';
    }

    // Vérifier que la compétence appartient à l'utilisateur
    $stmt = $pdo->prepare("SELECT id FROM competences WHERE id = ? AND utilisateur_id = ?");
    $stmt->execute([$competenceId, $userId]);
    if (!$stmt->fetch()) {
        retournerReponseJSON(false, 'Compétence introuvable ou accès refusé.');
    }

    // Vérifier si une autre compétence avec le même nom existe déjà
    $stmt = $pdo->prepare("SELECT id FROM competences WHERE utilisateur_id = ? AND nom = ? AND id != ?");
    $stmt->execute([$userId, $nom, $competenceId]);
    if ($stmt->fetch()) {
        retournerReponseJSON(false, 'Cette compétence existe déjà.');
    }

    try {
        $stmt = $pdo->prepare("UPDATE competences SET nom = ?, niveau = ? WHERE id = ? AND utilisateur_id = ?");
        $stmt->execute([$nom, $niveau, $competenceId, $userId]);
        retournerReponseJSON(true, 'Compétence modifiée avec succès !');
    } catch (PDOException $e) {
        error_log("Erreur PDO modifier competence: " . $e->getMessage());
        retournerReponseJSON(false, 'Erreur lors de la modification de la compétence.');
    }
}

// ===================== SUPPRIMER UNE COMPÉTENCE =====================
if ($action === 'supprimer') {
    $competenceId = (int) ($_POST['competence_id'] ?? 0);

    if (!$competenceId) {
        retournerReponseJSON(false, 'Compétence introuvable.');
    }

    // Vérifier que la compétence appartient à l'utilisateur
    $stmt = $pdo->prepare("SELECT id FROM competences WHERE id = ? AND utilisateur_id = ?");
    $stmt->execute([$competenceId, $userId]);
    if (!$stmt->fetch()) {
        retournerReponseJSON(false, 'Compétence introuvable ou accès refusé.');
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM competences WHERE id = ? AND utilisateur_id = ?");
        $stmt->execute([$competenceId, $userId]);
        retournerReponseJSON(true, 'Compétence supprimée avec succès !');
    } catch (PDOException $e) {
        error_log("Erreur PDO supprimer competence: " . $e->getMessage());
        retournerReponseJSON(false, 'Erreur lors de la suppression de la compétence.');
    }
}

// ===================== LISTER LES COMPÉTENCES =====================
if ($action === 'lister') {
    $stmt = $pdo->prepare("
        SELECT id, nom, niveau, ordre, date_ajout
        FROM competences
        WHERE utilisateur_id = ?
        ORDER BY ordre ASC, date_ajout ASC
    ");
    $stmt->execute([$userId]);
    $competences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    retournerReponseJSON(true, 'Compétences chargées.', ['competences' => $competences]);
}

// Si aucune action reconnue
retournerReponseJSON(false, 'Action non reconnue.');
