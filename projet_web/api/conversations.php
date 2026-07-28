<?php
/**
 * API pour gérer les conversations et groupes
 * 
 * Cette API gère :
 * - La création de groupes de discussion
 * - La modification de groupes (nom, photo)
 * - La récupération de la liste des participants d'un groupe
 * 
 * Sécurité :
 * - Seuls les amis acceptés peuvent être ajoutés à un groupe
 * - Seuls les membres d'un groupe peuvent le modifier
 */
require_once __DIR__ . '/../config/config.php';
requireConnexion(); // Vérifie que l'utilisateur est connecté

header('Content-Type: application/json'); // Réponse en JSON

$pdo = getDB(); // Connexion à la base de données
$userId = $_SESSION['utilisateur_id']; // ID de l'utilisateur connecté
$action = $_POST['action'] ?? $_GET['action'] ?? ''; // Action à effectuer

// ===================== CRÉER UN GROUPE =====================
/**
 * Crée un nouveau groupe de discussion
 * 
 * Paramètres POST requis :
 * - nom : Nom du groupe (obligatoire)
 * - participants : Tableau d'IDs des participants (au moins 1 obligatoire)
 * 
 * Processus :
 * 1. Validation des données (nom et participants requis)
 * 2. Vérification que tous les participants sont des amis acceptés
 * 3. Création de la conversation de type groupe avec photo par défaut
 * 4. Ajout de l'utilisateur actuel et des participants sélectionnés
 * 
 * Sécurité : Seuls les amis acceptés peuvent être ajoutés au groupe
 */
if ($action === 'creer_groupe') {
    $nomGroupe = trim($_POST['nom'] ?? '');
    $participants = $_POST['participants'] ?? [];
    
    if (empty($nomGroupe)) {
        echo json_encode(['succes' => false, 'message' => 'Le nom du groupe est requis.']);
        exit;
    }
    
    if (empty($participants) || !is_array($participants)) {
        echo json_encode(['succes' => false, 'message' => 'Veuillez sélectionner au moins un participant.']);
        exit;
    }
    
    // Nettoyer et valider les IDs des participants
    // Convertir en entiers et filtrer les valeurs invalides (0, négatifs, ou l'utilisateur lui-même)
    $participantsIds = array_map('intval', $participants);
    $participantsIds = array_filter($participantsIds, function($id) use ($userId) {
        return $id > 0 && $id != $userId; // Exclure l'utilisateur lui-même et les IDs invalides
    });
    
    if (empty($participantsIds)) {
        echo json_encode(['succes' => false, 'message' => 'Aucun participant valide.']);
        exit;
    }
    
    try {
        // Utiliser une transaction pour garantir la cohérence des données
        // Si une erreur survient, toutes les modifications sont annulées
        $pdo->beginTransaction();
        
        // Créer la conversation de groupe avec photo par défaut
        // Tous les groupes ont une photo par défaut au moment de la création
        $photoDefaut = 'assets/images/default_avatar.jpeg';
        $stmt = $pdo->prepare("INSERT INTO conversations (nom, est_groupe, photo) VALUES (?, 1, ?)");
        $stmt->execute([$nomGroupe, $photoDefaut]);
        $conversationId = $pdo->lastInsertId();
        
        // Ajouter l'utilisateur actuel comme participant (créateur du groupe)
        $stmtPart = $pdo->prepare("INSERT INTO conversation_participants (conversation_id, utilisateur_id) VALUES (?, ?)");
        $stmtPart->execute([$conversationId, $userId]);
        
        // Ajouter les autres participants sélectionnés
        // SÉCURITÉ : Vérifier que chaque participant est un ami accepté avant de l'ajouter
        foreach ($participantsIds as $participantId) {
            // Vérifier que c'est un ami accepté (sécurité : on ne peut pas ajouter n'importe qui)
            $stmtCheck = $pdo->prepare("
                SELECT 1 FROM connexions 
                WHERE ((demandeur_id = ? AND destinataire_id = ?) OR (demandeur_id = ? AND destinataire_id = ?))
                AND statut = 'accepte'
            ");
            $stmtCheck->execute([$userId, $participantId, $participantId, $userId]);
            
            // Seulement ajouter si c'est un ami accepté
            if ($stmtCheck->fetchColumn()) {
                $stmtPart->execute([$conversationId, $participantId]);
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'succes' => true,
            'message' => 'Groupe créé avec succès.',
            'conversation_id' => $conversationId
        ]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Erreur création groupe: ' . $e->getMessage());
        echo json_encode(['succes' => false, 'message' => 'Erreur lors de la création du groupe.']);
    }
    exit;
}

// ===================== MODIFIER LE GROUPE =====================
if ($action === 'modifier_groupe') {
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    $nouveauNom = trim($_POST['nom'] ?? '');
    $photo = $_POST['photo'] ?? null; // URL de la photo (peut être null)
    
    if (!$conversationId) {
        echo json_encode(['succes' => false, 'message' => 'ID de conversation manquant.']);
        exit;
    }
    
    // Vérifier que l'utilisateur fait partie du groupe
    $stmtCheck = $pdo->prepare("
        SELECT 1 FROM conversation_participants cp
        JOIN conversations c ON c.id = cp.conversation_id
        WHERE cp.conversation_id = ? AND cp.utilisateur_id = ? AND c.est_groupe = 1
    ");
    $stmtCheck->execute([$conversationId, $userId]);
    if (!$stmtCheck->fetchColumn()) {
        echo json_encode(['succes' => false, 'message' => 'Vous ne faites pas partie de ce groupe.']);
        exit;
    }
    
    try {
        // Construire la requête de mise à jour
        $updates = [];
        $params = [];
        
        if ($nouveauNom !== '') {
            $updates[] = "nom = ?";
            $params[] = $nouveauNom;
        }
        
        if ($photo !== null) {
            $updates[] = "photo = ?";
            $params[] = $photo;
        }
        
        if (empty($updates)) {
            echo json_encode(['succes' => false, 'message' => 'Aucune modification à apporter.']);
            exit;
        }
        
        $params[] = $conversationId;
        $sql = "UPDATE conversations SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode([
            'succes' => true,
            'message' => 'Groupe modifié avec succès.'
        ]);
    } catch (PDOException $e) {
        error_log('Erreur modification groupe: ' . $e->getMessage());
        echo json_encode(['succes' => false, 'message' => 'Erreur lors de la modification du groupe.']);
    }
    exit;
}

// ===================== LISTER LES PARTICIPANTS D'UN GROUPE =====================
if ($action === 'participants_groupe') {
    $conversationId = (int) ($_GET['conversation_id'] ?? 0);
    
    if (!$conversationId) {
        echo json_encode(['succes' => false, 'message' => 'ID de conversation manquant.']);
        exit;
    }
    
    // Vérifier que l'utilisateur fait partie du groupe
    $stmtCheck = $pdo->prepare("SELECT 1 FROM conversation_participants WHERE conversation_id = ? AND utilisateur_id = ?");
    $stmtCheck->execute([$conversationId, $userId]);
    if (!$stmtCheck->fetchColumn()) {
        echo json_encode(['succes' => false, 'message' => 'Vous ne faites pas partie de ce groupe.']);
        exit;
    }
    
    try {
        // Récupérer tous les participants du groupe
        $stmt = $pdo->prepare("
            SELECT u.id, u.prenom, u.nom, u.photo
            FROM conversation_participants cp
            JOIN utilisateurs u ON u.id = cp.utilisateur_id
            WHERE cp.conversation_id = ?
            ORDER BY u.prenom, u.nom
        ");
        $stmt->execute([$conversationId]);
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['succes' => true, 'participants' => $participants]);
    } catch (PDOException $e) {
        error_log('Erreur SQL dans participants_groupe: ' . $e->getMessage());
        echo json_encode(['succes' => false, 'message' => 'Erreur de base de données.']);
    }
    exit;
}

echo json_encode(['succes' => false, 'message' => 'Action non reconnue.']);
