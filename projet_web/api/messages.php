<?php
/**
 * ECE In - API AJAX : Messagerie (envoi et récupération de messages)
 * 
 * Cette API gère tous les aspects de la messagerie :
 * - Envoi de messages (texte et fichiers joints)
 * - Suppression de messages
 * - Modification de messages
 * - Récupération de nouveaux messages (polling)
 * - Marquage des messages comme lus
 * 
 * Sécurité : Seuls les participants d'une conversation peuvent envoyer des messages
 */
require_once __DIR__ . '/../config/config.php';
requireConnexion(); // Vérifie que l'utilisateur est connecté

header('Content-Type: application/json'); // Réponse en JSON

$pdo    = getDB(); // Connexion à la base de données
$userId = $_SESSION['utilisateur_id']; // ID de l'utilisateur connecté
$action = $_POST['action'] ?? $_GET['action'] ?? ''; // Action à effectuer

// ===================== ENVOYER UN MESSAGE =====================
/**
 * Envoie un nouveau message dans une conversation
 * 
 * Paramètres POST :
 * - conversation_id : ID de la conversation (obligatoire)
 * - contenu : Texte du message (peut être vide si fichier joint)
 * - fichier : Fichier à joindre (optionnel : image, document, etc.)
 * 
 * Processus :
 * 1. Vérification que l'utilisateur fait partie de la conversation (sécurité)
 * 2. Upload du fichier si présent
 * 3. Insertion du message dans la base de données
 * 4. Retour du message créé avec toutes ses infos
 */
if ($action === 'envoyer') {
    $convId  = (int) ($_POST['conversation_id'] ?? 0);
    $contenu = trim($_POST['contenu'] ?? '');
    
    // GESTION DE L'UPLOAD DE FICHIER
    // Si un fichier est fourni, on le sauvegarde dans le dossier uploads/messages/
    $fichierJoint = null;
    if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/uploads/messages/';
        // Créer le dossier s'il n'existe pas
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        // Générer un nom de fichier unique (timestamp + nom original)
        $fileName = time() . '_' . basename($_FILES['fichier']['name']);
        $uploadFile = $uploadDir . $fileName;
        
        // Déplacer le fichier uploadé vers le dossier de destination
        if (move_uploaded_file($_FILES['fichier']['tmp_name'], $uploadFile)) {
            $fichierJoint = 'assets/uploads/messages/' . $fileName; // Chemin relatif pour la base de données
        }
    }

    // Validation : le message doit avoir du contenu OU un fichier joint
    if (empty($contenu) && !$fichierJoint) {
        echo json_encode(['succes' => false, 'message' => 'Message et fichier vides.']);
        exit();
    }

    // SÉCURITÉ : Vérifier que l'utilisateur fait partie de la conversation
    // Empêche d'envoyer des messages dans des conversations auxquelles on n'appartient pas
    $stmtCheck = $pdo->prepare("
        SELECT 1 FROM conversation_participants WHERE conversation_id = ? AND utilisateur_id = ?
    ");
    $stmtCheck->execute([$convId, $userId]);
    if (!$stmtCheck->fetchColumn()) {
        echo json_encode(['succes' => false, 'message' => 'Accès refusé.']);
        exit();
    }

    // INSERTION DU MESSAGE DANS LA BASE DE DONNÉES
    // Note: Utiliser TRY CATCH car certaines colonnes (fichier_joint, distribue) peuvent ne pas exister dans certaines bases de données
    // On essaie d'abord avec toutes les colonnes, puis on fait un fallback si nécessaire
    try {
        $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, expediteur_id, contenu, fichier_joint, distribue) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$convId, $userId, $contenu, $fichierJoint]);
    } catch (PDOException $e) {
        // Fallback 1 : Si la colonne 'distribue' n'existe pas, on l'enlève
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, expediteur_id, contenu, fichier_joint) VALUES (?, ?, ?, ?)");
            $stmt->execute([$convId, $userId, $contenu, $fichierJoint]);
        } catch (PDOException $e2) {
            // Fallback 2 : Si la colonne 'fichier_joint' n'existe pas non plus, on insère seulement le contenu
            $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, expediteur_id, contenu) VALUES (?, ?, ?)");
            $stmt->execute([$convId, $userId, $contenu]);
        }
    }
    
    // Récupérer l'ID du message qui vient d'être créé
    $msgId = $pdo->lastInsertId();

    // Récupérer le message créé avec toutes ses infos (y compris la vraie date depuis la base de données)
    // On joint la table utilisateurs pour avoir les infos de l'expéditeur (nom, prénom, photo)
    $stmt = $pdo->prepare("
        SELECT m.*, u.nom, u.prenom, u.photo AS avatar
        FROM messages m
        JOIN utilisateurs u ON u.id = m.expediteur_id
        WHERE m.id = ?
    ");
    $stmt->execute([$msgId]);
    $message = $stmt->fetch();

    if (!$message) {
        echo json_encode(['succes' => false, 'message' => 'Erreur lors de la récupération du message.']);
        exit();
    }

    // Gestion de la compatibilité : récupérer les valeurs par défaut si les colonnes n'existent pas encore
    // Ces colonnes sont utilisées pour afficher les statuts de lecture (lu/distribué) dans l'interface
    $distribue = isset($message['distribue']) ? (int)$message['distribue'] : 1; // Par défaut, un message envoyé est distribué
    $lu = isset($message['lu']) ? (int)$message['lu'] : 0; // Par défaut, un message n'est pas lu

    echo json_encode([
        'succes'  => true,
        'message' => [
            'id'            => $message['id'],
            'contenu'       => $message['contenu'],
            'fichier_joint' => $message['fichier_joint'],
            'expediteur_id' => $message['expediteur_id'],
            'prenom'        => $message['prenom'],
            'nom'           => $message['nom'],
            'avatar'        => $message['avatar'],
            'date_envoi'    => $message['date_envoi'], // Vraie date depuis la base de données
            'distribue'     => $distribue,
            'lu'            => $lu,
        ],
    ]);
    exit();
}

// ===================== SUPPRIMER UN MESSAGE =====================
/**
 * Supprime un message (soft delete)
 * 
 * Paramètres POST :
 * - message_id : ID du message à supprimer
 * 
 * Sécurité : Seul l'expéditeur du message peut le supprimer
 * 
 * Processus :
 * 1. Vérifier que le message existe et appartient à l'utilisateur
 * 2. Remplacer le contenu par un message de suppression
 * 3. Supprimer le fichier joint s'il existe
 * 4. Marquer le message comme supprimé (si la colonne existe)
 * 
 * Note : Soft delete = le message n'est pas vraiment supprimé de la base,
 * mais son contenu est remplacé pour préserver l'historique de la conversation
 */
if ($action === 'supprimer') {
    $msgId = (int) ($_POST['message_id'] ?? 0);
    
    // SÉCURITÉ : Vérifier que le message existe et appartient à l'utilisateur
    // Seul l'expéditeur peut supprimer son propre message
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ? AND expediteur_id = ?");
    $stmt->execute([$msgId, $userId]);
    $msg = $stmt->fetch();
    
    if (!$msg) {
        echo json_encode(['succes' => false, 'message' => 'Message introuvable ou non autorisé.']);
        exit();
    }
    
    // SOFT DELETE : Remplacer le contenu par un message de suppression
    // On ne supprime pas vraiment le message pour préserver l'historique
    try {
        // Essayer avec la colonne est_supprime si elle existe
        $pdo->prepare("UPDATE messages SET contenu = 'Ce message a été supprimé.', fichier_joint = NULL, est_supprime = 1 WHERE id = ?")
            ->execute([$msgId]);
    } catch (PDOException $e) {
        // Fallback si la colonne est_supprime n'existe pas encore
        $pdo->prepare("UPDATE messages SET contenu = 'Ce message a été supprimé.', fichier_joint = NULL WHERE id = ?")
            ->execute([$msgId]);
    }
        
    echo json_encode(['succes' => true, 'message' => 'Message supprimé.']);
    exit();
}

// ===================== MODIFIER UN MESSAGE =====================
/**
 * Modifie le contenu d'un message existant
 * 
 * Paramètres POST :
 * - message_id : ID du message à modifier
 * - contenu : Nouveau contenu du message
 * 
 * Sécurité :
 * - Seul l'expéditeur peut modifier son message
 * - Limite de temps : le message ne peut être modifié que dans les 15 minutes suivant l'envoi
 * - Un message supprimé ne peut pas être modifié
 * 
 * Processus :
 * 1. Vérifier que le message existe et appartient à l'utilisateur
 * 2. Vérifier que le message n'est pas supprimé
 * 3. Vérifier que moins de 15 minutes se sont écoulées depuis l'envoi
 * 4. Mettre à jour le contenu et marquer comme modifié
 */
if ($action === 'modifier') {
    $msgId = (int) ($_POST['message_id'] ?? 0);
    $nouveauContenu = trim($_POST['contenu'] ?? '');
    
    // SÉCURITÉ : Vérifier que le message existe et appartient à l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ? AND expediteur_id = ?");
    $stmt->execute([$msgId, $userId]);
    $msg = $stmt->fetch();
    
    if (!$msg || !empty($msg['est_supprime'])) {
        echo json_encode(['succes' => false, 'message' => 'Message introuvable ou déjà supprimé.']);
        exit();
    }
    
    // Calculer le temps écoulé depuis l'envoi du message (en secondes)
    $timeSinceSent = time() - strtotime($msg['date_envoi']);
    if ($timeSinceSent > 30) {
        echo json_encode(['succes' => false, 'message' => 'Le délai de 30 secondes pour modifier ce message est écoulé.']);
        exit();
    }
    
    if (empty($nouveauContenu) && empty($msg['fichier_joint'])) {
         echo json_encode(['succes' => false, 'message' => 'Le message ne peut pas être vide.']);
         exit();
    }
    
    try {
        $pdo->prepare("UPDATE messages SET contenu = ?, est_modifie = 1 WHERE id = ?")
            ->execute([$nouveauContenu, $msgId]);
    } catch (PDOException $e) {
        // Fallback
        $pdo->prepare("UPDATE messages SET contenu = ? WHERE id = ?")
            ->execute([$nouveauContenu, $msgId]);
    }
        
    echo json_encode(['succes' => true, 'nouveau_contenu' => $nouveauContenu]);
    exit();
}

// ===================== RÉCUPÉRER LES NOUVEAUX MESSAGES (polling) =====================
if ($action === 'polling') {
    $convId    = (int) ($_GET['conversation_id'] ?? 0);
    $dernierMsgId = (int) ($_GET['dernier_id'] ?? 0);

    if (!$convId) {
        echo json_encode(['succes' => false, 'messages' => []]);
        exit();
    }

    // Vérifier participation
    $stmtCheck = $pdo->prepare("
        SELECT 1 FROM conversation_participants WHERE conversation_id = ? AND utilisateur_id = ?
    ");
    $stmtCheck->execute([$convId, $userId]);
    if (!$stmtCheck->fetchColumn()) {
        echo json_encode(['succes' => false, 'messages' => []]);
        exit();
    }

    // Récupérer les nouveaux messages
    $stmt = $pdo->prepare("
        SELECT m.*, u.nom, u.prenom, u.photo AS avatar
        FROM messages m
        JOIN utilisateurs u ON u.id = m.expediteur_id
        WHERE m.conversation_id = ? AND m.id > ?
        ORDER BY m.date_envoi ASC
    ");
    $stmt->execute([$convId, $dernierMsgId]);
    $messages = $stmt->fetchAll();

    // Marquer comme lus les messages reçus (pas ceux envoyés par l'utilisateur)
    try {
        $pdo->prepare("UPDATE messages SET lu = 1 WHERE conversation_id = ? AND expediteur_id != ? AND lu = 0")
            ->execute([$convId, $userId]);
    } catch (PDOException $e) {
        // Si la colonne lu n'existe pas encore, ignorer
    }

    echo json_encode(['succes' => true, 'messages' => $messages]);
    exit();
}

// ===================== MARQUER UN MESSAGE COMME LU =====================
if ($action === 'marquer_lu') {
    $msgId = (int) ($_POST['message_id'] ?? 0);
    
    if (!$msgId) {
        echo json_encode(['succes' => false, 'message' => 'ID de message manquant.']);
        exit();
    }
    
    // Vérifier que le message existe et que l'utilisateur est dans la conversation
    $stmt = $pdo->prepare("
        SELECT m.conversation_id, m.expediteur_id
        FROM messages m
        JOIN conversation_participants cp ON cp.conversation_id = m.conversation_id
        WHERE m.id = ? AND cp.utilisateur_id = ?
    ");
    $stmt->execute([$msgId, $userId]);
    $msg = $stmt->fetch();
    
    if (!$msg) {
        echo json_encode(['succes' => false, 'message' => 'Message introuvable ou accès refusé.']);
        exit();
    }
    
    // Marquer comme lu seulement si le message n'a pas été envoyé par l'utilisateur actuel
    if ($msg['expediteur_id'] != $userId) {
        try {
            $pdo->prepare("UPDATE messages SET lu = 1 WHERE id = ?")
                ->execute([$msgId]);
        } catch (PDOException $e) {
            // Si la colonne lu n'existe pas encore, ignorer
        }
    }
    
    echo json_encode(['succes' => true]);
    exit();
}

// ===================== MARQUER TOUS LES MESSAGES D'UNE CONVERSATION COMME LU =====================
if ($action === 'marquer_tous_lus') {
    $convId = (int) ($_POST['conversation_id'] ?? 0);
    
    if (!$convId) {
        echo json_encode(['succes' => false, 'message' => 'ID de conversation manquant.']);
        exit();
    }
    
    // Vérifier participation
    $stmtCheck = $pdo->prepare("
        SELECT 1 FROM conversation_participants WHERE conversation_id = ? AND utilisateur_id = ?
    ");
    $stmtCheck->execute([$convId, $userId]);
    if (!$stmtCheck->fetchColumn()) {
        echo json_encode(['succes' => false, 'message' => 'Accès refusé.']);
        exit();
    }
    
    // Marquer comme lus tous les messages reçus (pas ceux envoyés par l'utilisateur)
    try {
        $pdo->prepare("UPDATE messages SET lu = 1 WHERE conversation_id = ? AND expediteur_id != ? AND lu = 0")
            ->execute([$convId, $userId]);
    } catch (PDOException $e) {
        // Si la colonne lu n'existe pas encore, ignorer
    }
    
    echo json_encode(['succes' => true]);
    exit();
}

echo json_encode(['succes' => false, 'message' => 'Action non reconnue.']);
