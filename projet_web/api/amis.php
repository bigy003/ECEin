<?php
/**
 * ECE In - API AJAX : Gestion des connexions / amis
 * 
 * Cette API gère tous les aspects des connexions entre utilisateurs :
 * - Envoi de demandes de connexion (invitations)
 * - Acceptation/refus de demandes
 * - Suppression de connexions (déconnexion)
 * - Liste des amis et demandes en attente
 * 
 * Sécurité :
 * - On ne peut pas s'envoyer une demande à soi-même
 * - On ne peut répondre qu'aux demandes qui nous sont destinées
 * - On ne peut supprimer que ses propres connexions
 */
require_once __DIR__ . '/../config/config.php';
requireConnexion(); // Vérifie que l'utilisateur est connecté

header('Content-Type: application/json'); // Réponse en JSON

$pdo    = getDB(); // Connexion à la base de données
$userId = $_SESSION['utilisateur_id']; // ID de l'utilisateur connecté
$action = $_POST['action'] ?? ''; // Action à effectuer

// ===================== ENVOYER UNE DEMANDE =====================
/**
 * Envoie une demande de connexion à un autre utilisateur
 * 
 * Paramètres POST :
 * - destinataire_id : ID de l'utilisateur à qui envoyer la demande
 * 
 * Processus :
 * 1. Vérifier que l'utilisateur ne s'envoie pas une demande à lui-même
 * 2. Vérifier que le destinataire existe et est actif
 * 3. Vérifier qu'aucune connexion n'existe déjà entre les deux utilisateurs
 * 4. Créer la demande dans la table connexions (statut 'en_attente')
 * 5. Notifier le destinataire de la nouvelle demande
 * 
 * Statuts possibles :
 * - 'en_attente' : Demande envoyée, en attente de réponse
 * - 'accepte' : Demande acceptée, les utilisateurs sont connectés
 * - 'refuse' : Demande refusée
 */
if ($action === 'envoyer_demande') {
    $destinataireId = (int) ($_POST['destinataire_id'] ?? 0);

    // SÉCURITÉ : Vérifier que l'utilisateur ne s'envoie pas une demande à lui-même
    if ($destinataireId === $userId) {
        echo json_encode(['succes' => false, 'message' => t('Vous ne pouvez pas vous connecter avec vous-même.')]);
        exit();
    }

    // Vérifier que le destinataire existe et est actif
    // On ne peut envoyer des demandes qu'aux utilisateurs actifs
    $stmtUser = $pdo->prepare("SELECT id, prenom, nom FROM utilisateurs WHERE id = ? AND actif = 1");
    $stmtUser->execute([$destinataireId]);
    $destinataire = $stmtUser->fetch();

    if (!$destinataire) {
        echo json_encode(['succes' => false, 'message' => t('Utilisateur introuvable.')]);
        exit();
    }

    // Vérifier si une connexion existe déjà entre les deux utilisateurs
    // On vérifie dans les deux sens (demandeur->destinataire et destinataire->demandeur)
    // car une connexion peut exister dans n'importe quel sens
    $stmtCheck = $pdo->prepare("
        SELECT statut FROM connexions
        WHERE (demandeur_id = ? AND destinataire_id = ?)
           OR (demandeur_id = ? AND destinataire_id = ?)
    ");
    $stmtCheck->execute([$userId, $destinataireId, $destinataireId, $userId]);
    $existante = $stmtCheck->fetch();

    // Si une connexion existe déjà, on retourne un message selon son statut
    if ($existante) {
        $msg = match($existante['statut']) {
            'accepte'    => 'Vous êtes déjà connectés.',
            'en_attente' => 'Une invitation est déjà en attente.',
            'refuse'     => 'Cette invitation a été refusée.',
            default      => 'Connexion déjà existante.',
        };
        echo json_encode(['succes' => false, 'message' => t($msg)]);
        exit();
    }

    // Créer la demande de connexion dans la base de données
    // Le statut par défaut est 'en_attente' (sera changé à 'accepte' ou 'refuse' par le destinataire)
    $pdo->prepare("INSERT INTO connexions (demandeur_id, destinataire_id) VALUES (?, ?)")
        ->execute([$userId, $destinataireId]);

    // Notifier le destinataire de la nouvelle demande
    // Cela créera une notification visible dans son interface
    $user = getUtilisateurConnecte();
    creerNotification(
        $destinataireId, $userId, 'demande_ami',
        $user['prenom'] . ' ' . $user['nom'] . ' souhaite se connecter avec vous.',
        'reseau.php#section-demandes'
    );

    echo json_encode([
        'succes'  => true,
        'message' => t('Invitation envoyée à %s !', $destinataire['prenom'] . ' ' . $destinataire['nom']),
    ]);
    exit();
}

// ===================== RÉPONDRE À UNE DEMANDE =====================
/**
 * Accepte ou refuse une demande de connexion
 * 
 * Paramètres POST :
 * - connexion_id : ID de la connexion (demande)
 * - reponse : 'accepter' ou 'refuser'
 * 
 * Sécurité : Seul le destinataire peut répondre à une demande qui lui est destinée
 * 
 * Processus :
 * 1. Vérifier que la demande existe et est destinée à l'utilisateur
 * 2. Vérifier que la demande est en statut 'en_attente'
 * 3. Mettre à jour le statut à 'accepte' ou 'refuse'
 * 4. Enregistrer la date de réponse
 * 5. Notifier le demandeur si la demande est acceptée
 */
if ($action === 'repondre') {
    $connexionId = (int) ($_POST['connexion_id'] ?? 0);
    $reponse     = $_POST['reponse'] ?? ''; // 'accepter' ou 'refuser'

    // SÉCURITÉ : Vérifier que la demande existe, est destinée à l'utilisateur et est en attente
    // Seul le destinataire peut répondre à une demande qui lui est adressée
    $stmt = $pdo->prepare("
        SELECT * FROM connexions WHERE id = ? AND destinataire_id = ? AND statut = 'en_attente'
    ");
    $stmt->execute([$connexionId, $userId]);
    $connexion = $stmt->fetch();

    if (!$connexion) {
        echo json_encode(['succes' => false, 'message' => t('Invitation introuvable.')]);
        exit();
    }

    // Déterminer le nouveau statut selon la réponse
    $statut = $reponse === 'accepter' ? 'accepte' : 'refuse';
    // Mettre à jour le statut et enregistrer la date de réponse
    $pdo->prepare("UPDATE connexions SET statut = ?, date_reponse = NOW() WHERE id = ?")
        ->execute([$statut, $connexionId]);

    // Notifier le demandeur si la demande est acceptée
    // Si refusée, pas de notification (pour éviter de spammer l'utilisateur)
    if ($statut === 'accepte') {
        $user = getUtilisateurConnecte();
        creerNotification(
            $connexion['demandeur_id'], $userId, 'ami_accepte',
            $user['prenom'] . ' ' . $user['nom'] . ' a accepté votre invitation.',
            'reseau.php'
        );
    }

    echo json_encode([
        'succes'  => true,
        'statut'  => $statut,
        'message' => $statut === 'accepte' ? t('Connexion acceptée !') : t('Invitation refusée.'),
    ]);
    exit();
}

// ===================== ANNULER UNE DEMANDE =====================
if ($action === 'annuler') {
    $connexionId = (int) ($_POST['connexion_id'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT id FROM connexions WHERE id = ? AND demandeur_id = ? AND statut = 'en_attente'
    ");
    $stmt->execute([$connexionId, $userId]);
    if (!$stmt->fetch()) {
        echo json_encode(['succes' => false, 'message' => t('Invitation introuvable.')]);
        exit();
    }

    $pdo->prepare("DELETE FROM connexions WHERE id = ?")->execute([$connexionId]);
    echo json_encode(['succes' => true, 'message' => t('Invitation annulée.')]);
    exit();
}

// ===================== SUPPRIMER UNE CONNEXION (NE PLUS SUIVRE) =====================
if ($action === 'supprimer') {
    $autreId = (int) ($_POST['autre_id'] ?? 0);

    // Vérifier que la connexion existe et est acceptée
    $stmt = $pdo->prepare("
        SELECT id FROM connexions 
        WHERE ((demandeur_id = ? AND destinataire_id = ?) 
           OR (demandeur_id = ? AND destinataire_id = ?))
          AND statut = 'accepte'
    ");
    $stmt->execute([$userId, $autreId, $autreId, $userId]);
    $connexion = $stmt->fetch();

    if (!$connexion) {
        echo json_encode(['succes' => false, 'message' => t('Connexion introuvable.')]);
        exit();
    }

    // Supprimer la connexion
    $pdo->prepare("DELETE FROM connexions WHERE id = ?")->execute([$connexion['id']]);
    echo json_encode(['succes' => true, 'message' => t('Vous ne suivez plus cette personne.')]);
    exit();
}

echo json_encode(['succes' => false, 'message' => t('Action non reconnue.')]);
