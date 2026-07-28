<?php
/**
 * ECE In - API AJAX : Gestion des appels audio/vidéo
 * 
 * Cette API gère tous les aspects des appels (audio/vidéo) :
 * - Initiation d'appels (1-à-1 et groupes)
 * - Vérification des appels entrants
 * - Acceptation/refus d'appels
 * - Ajout de participants à un appel en cours
 * - Récupération de la liste des participants
 * 
 * Sécurité : 
 * - Seuls les amis acceptés peuvent être appelés
 * - Pour les groupes, seuls les membres du groupe peuvent être appelés
 */
require_once __DIR__ . '/../config/config.php';
requireConnexion(); // Vérifie que l'utilisateur est connecté

header('Content-Type: application/json'); // Réponse en JSON pour les appels AJAX

$pdo    = getDB(); // Connexion à la base de données
$userId = $_SESSION['utilisateur_id']; // ID de l'utilisateur connecté
$action = $_POST['action'] ?? $_GET['action'] ?? ''; // Action à effectuer (initier, accepter, refuser, etc.)

// ===================== INITIER UN APPEL =====================
/**
 * Crée un nouvel appel (audio ou vidéo)
 * 
 * Paramètres POST requis :
 * - conversation_id : ID de la conversation
 * - type : 'audio' ou 'video'
 * - est_groupe : '1' si appel de groupe, sinon appel 1-à-1
 * - receveur_id : ID du destinataire (pour les appels 1-à-1)
 * 
 * Pour les groupes : crée un appel pour chaque participant du groupe qui est aussi un ami accepté
 * Pour les appels 1-à-1 : crée un seul appel entre l'appelant et le receveur
 */
if ($action === 'initier') {
    $convId     = (int) ($_POST['conversation_id'] ?? 0);
    $receveurId = (int) ($_POST['receveur_id'] ?? 0);
    $type       = in_array($_POST['type'] ?? '', ['audio', 'video']) ? $_POST['type'] : 'audio';
    $estGroupe  = isset($_POST['est_groupe']) && $_POST['est_groupe'] == '1';

    if (!$convId) {
        echo json_encode(['succes' => false, 'message' => 'ID de conversation manquant.']);
        exit;
    }

    // SÉCURITÉ : Vérifier que l'utilisateur fait partie de la conversation
    // Empêche d'appeler depuis une conversation à laquelle on n'appartient pas
    $stmtCheck = $pdo->prepare("SELECT 1 FROM conversation_participants WHERE conversation_id = ? AND utilisateur_id = ?");
    $stmtCheck->execute([$convId, $userId]);
    if (!$stmtCheck->fetchColumn()) {
        echo json_encode(['succes' => false, 'message' => 'Vous ne faites pas partie de cette conversation.']);
        exit;
    }

    // Annuler les appels en sonnerie existants de cet utilisateur
    // Évite d'avoir plusieurs appels en attente en même temps
    $pdo->prepare("UPDATE appels SET statut = 'manque', date_fin = NOW() WHERE appelant_id = ? AND statut = 'sonnerie'")
        ->execute([$userId]);

    if ($estGroupe) {
        /**
         * APPEL DE GROUPE
         * 
         * Pour un appel de groupe, on crée un appel séparé pour chaque participant.
         * Sécurité : Seuls les participants du groupe qui sont aussi des amis acceptés sont appelés.
         * Cela évite d'appeler des personnes non connectées ou non membres du groupe.
         */
        $stmtParticipants = $pdo->prepare("
            SELECT cp.utilisateur_id 
            FROM conversation_participants cp
            JOIN connexions conn ON (
                (conn.demandeur_id = ? AND conn.destinataire_id = cp.utilisateur_id)
                OR (conn.demandeur_id = cp.utilisateur_id AND conn.destinataire_id = ?)
            )
            WHERE cp.conversation_id = ? 
              AND cp.utilisateur_id != ?
              AND conn.statut = 'accepte'
        ");
        $stmtParticipants->execute([$userId, $userId, $convId, $userId]);
        $participants = $stmtParticipants->fetchAll(PDO::FETCH_COLUMN);
        
        $appelIds = [];
        $stmt = $pdo->prepare("INSERT INTO appels (appelant_id, receveur_id, conversation_id, type) VALUES (?, ?, ?, ?)");
        
        foreach ($participants as $participantId) {
            $stmt->execute([$userId, $participantId, $convId, $type]);
            $appelIds[] = (int) $pdo->lastInsertId();
        }
        
        echo json_encode([
            'succes' => true, 
            'appel_id' => $appelIds[0] ?? 0, // Retourner le premier ID pour compatibilité
            'appel_ids' => $appelIds,
            'est_groupe' => true
        ]);
    } else {
        /**
         * APPEL 1-À-1
         * 
         * Pour un appel entre deux personnes, on crée un seul enregistrement dans la table appels.
         * L'appelant et le receveur sont stockés dans les colonnes appelant_id et receveur_id.
         */
        if (!$receveurId) {
            echo json_encode(['succes' => false, 'message' => 'ID du destinataire manquant.']);
            exit;
        }
        
        // Créer l'appel dans la base de données
        $stmt = $pdo->prepare("INSERT INTO appels (appelant_id, receveur_id, conversation_id, type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $receveurId, $convId, $type]);
        
        echo json_encode([
            'succes' => true, 
            'appel_id' => (int) $pdo->lastInsertId(),
            'est_groupe' => false
        ]);
    }
    exit;
}

// ===================== VÉRIFIER LES APPELS ENTRANTS =====================
/**
 * Vérifie s'il y a un appel entrant pour l'utilisateur
 * 
 * Cette fonction est appelée régulièrement par le frontend (polling) pour détecter les appels entrants.
 * 
 * Retourne :
 * - Le dernier appel en statut 'sonnerie' destiné à l'utilisateur
 * - Uniquement les appels créés dans les 35 dernières secondes (pour éviter les appels expirés)
 * - Les infos de l'appelant et de la conversation (pour afficher qui appelle)
 * 
 * Utilisé pour : Afficher une notification d'appel entrant en temps réel
 */
if ($action === 'verifier') {
    // Récupérer le dernier appel entrant (peut être un appel de groupe)
    // On joint les tables utilisateurs et conversations pour avoir toutes les infos nécessaires
    $stmt = $pdo->prepare("
        SELECT a.*, u.prenom, u.nom, u.photo, c.est_groupe, c.nom AS nom_groupe
        FROM appels a
        JOIN utilisateurs u ON u.id = a.appelant_id
        JOIN conversations c ON c.id = a.conversation_id
        WHERE a.receveur_id = ? AND a.statut = 'sonnerie'
          AND a.date_debut > DATE_SUB(NOW(), INTERVAL 35 SECOND) -- Ignorer les appels trop anciens
        ORDER BY a.date_debut DESC LIMIT 1
    ");
    $stmt->execute([$userId]);
    $appel = $stmt->fetch();

    echo json_encode(['succes' => true, 'appel' => $appel ?: null]);
    exit;
}

// ===================== STATUT D'UN APPEL =====================
/**
 * Récupère le statut actuel d'un appel
 * 
 * Paramètres GET :
 * - appel_id : ID de l'appel
 * 
 * Sécurité : Vérifie que l'utilisateur fait partie de l'appel (appelant ou receveur)
 * 
 * Utilisé pour : Vérifier si un appel est toujours actif (en_cours, sonnerie, etc.)
 */
if ($action === 'statut') {
    $appelId = (int) ($_GET['appel_id'] ?? 0);
    // SÉCURITÉ : Vérifier que l'utilisateur fait partie de l'appel
    $stmt = $pdo->prepare("SELECT statut FROM appels WHERE id = ? AND (appelant_id = ? OR receveur_id = ?)");
    $stmt->execute([$appelId, $userId, $userId]);
    $appel = $stmt->fetch();

    echo json_encode(['succes' => true, 'statut' => $appel ? $appel['statut'] : 'termine']);
    exit;
}

// ===================== ACCEPTER =====================
if ($action === 'accepter') {
    $appelId = (int) ($_POST['appel_id'] ?? 0);
    $pdo->prepare("UPDATE appels SET statut = 'en_cours' WHERE id = ? AND receveur_id = ? AND statut = 'sonnerie'")
        ->execute([$appelId, $userId]);

    echo json_encode(['succes' => true]);
    exit;
}

// ===================== REFUSER =====================
if ($action === 'refuser') {
    $appelId = (int) ($_POST['appel_id'] ?? 0);
    $pdo->prepare("UPDATE appels SET statut = 'refuse', date_fin = NOW() WHERE id = ? AND receveur_id = ? AND statut = 'sonnerie'")
        ->execute([$appelId, $userId]);

    echo json_encode(['succes' => true]);
    exit;
}

// ===================== TERMINER =====================
if ($action === 'terminer') {
    $appelId = (int) ($_POST['appel_id'] ?? 0);
    $pdo->prepare("UPDATE appels SET statut = 'termine', date_fin = NOW() WHERE id = ? AND (appelant_id = ? OR receveur_id = ?) AND statut IN ('sonnerie', 'en_cours')")
        ->execute([$appelId, $userId, $userId]);

    echo json_encode(['succes' => true]);
    exit;
}

// ===================== AJOUTER UN PARTICIPANT À UN APPEL =====================
/**
 * Ajoute un nouveau participant à un appel en cours
 * 
 * Paramètres POST requis :
 * - appel_id : ID de l'appel auquel ajouter le participant
 * - participant_id : ID de l'utilisateur à ajouter
 * 
 * Vérifications de sécurité :
 * 1. L'utilisateur doit faire partie de l'appel
 * 2. Le participant doit être un ami accepté
 * 3. Pour les groupes, le participant doit être dans la conversation
 * 4. Le participant ne doit pas déjà être dans l'appel
 */
if ($action === 'ajouter_participant') {
    $appelId = (int) ($_POST['appel_id'] ?? 0);
    $nouveauParticipantId = (int) ($_POST['participant_id'] ?? 0);
    
    if (!$appelId || !$nouveauParticipantId) {
        echo json_encode(['succes' => false, 'message' => 'Paramètres manquants.']);
        exit;
    }
    
    // SÉCURITÉ 1 : Vérifier que l'utilisateur fait partie de l'appel (en_cours ou sonnerie)
    // Seuls les participants de l'appel peuvent ajouter d'autres personnes
    $stmtCheck = $pdo->prepare("
        SELECT 1 FROM appels 
        WHERE id = ? AND (appelant_id = ? OR receveur_id = ?) AND statut IN ('en_cours', 'sonnerie')
    ");
    $stmtCheck->execute([$appelId, $userId, $userId]);
    if (!$stmtCheck->fetchColumn()) {
        echo json_encode(['succes' => false, 'message' => 'Vous ne faites pas partie de cet appel.']);
        exit;
    }
    
    // Récupérer les infos de l'appel actuel (conversation_id, type audio/video, statut)
    $stmtAppel = $pdo->prepare("SELECT conversation_id, type, statut FROM appels WHERE id = ?");
    $stmtAppel->execute([$appelId]);
    $appel = $stmtAppel->fetch();
    
    if (!$appel) {
        echo json_encode(['succes' => false, 'message' => 'Appel introuvable.']);
        exit;
    }
    
    // SÉCURITÉ 2 : Vérifier que le participant est un ami accepté
    // On ne peut pas ajouter quelqu'un avec qui on n'est pas connecté
    $stmtCheckAmi = $pdo->prepare("
        SELECT 1 FROM connexions 
        WHERE ((demandeur_id = ? AND destinataire_id = ?) OR (demandeur_id = ? AND destinataire_id = ?))
        AND statut = 'accepte'
    ");
    $stmtCheckAmi->execute([$userId, $nouveauParticipantId, $nouveauParticipantId, $userId]);
    if (!$stmtCheckAmi->fetchColumn()) {
        echo json_encode(['succes' => false, 'message' => 'Vous devez être connecté avec cette personne pour l\'ajouter à l\'appel.']);
        exit;
    }
    
    // SÉCURITÉ 3 : Vérifier que le participant fait partie de la conversation (pour les appels de groupe)
    // Pour les groupes, on ne peut ajouter que des membres du groupe
    $stmtCheckConv = $pdo->prepare("
        SELECT 1 FROM conversation_participants 
        WHERE conversation_id = ? AND utilisateur_id = ?
    ");
    $stmtCheckConv->execute([$appel['conversation_id'], $nouveauParticipantId]);
    $estDansConversation = $stmtCheckConv->fetchColumn();
    
    // Vérifier si c'est un groupe ou une conversation 1-à-1
    $stmtCheckGroupe = $pdo->prepare("SELECT est_groupe FROM conversations WHERE id = ?");
    $stmtCheckGroupe->execute([$appel['conversation_id']]);
    $conversation = $stmtCheckGroupe->fetch();
    
    // Si c'est un groupe, le participant doit être dans la conversation
    if ($conversation && $conversation['est_groupe'] && !$estDansConversation) {
        echo json_encode(['succes' => false, 'message' => 'Cette personne ne fait pas partie du groupe.']);
        exit;
    }
    
    // SÉCURITÉ 4 : Vérifier que le participant n'est pas déjà dans l'appel
    // Évite les doublons dans la liste des participants
    $stmtCheck2 = $pdo->prepare("
        SELECT 1 FROM appels 
        WHERE conversation_id = ? AND statut IN ('sonnerie', 'en_cours')
        AND ((appelant_id = ? AND receveur_id = ?) OR (appelant_id = ? AND receveur_id = ?))
    ");
    $stmtCheck2->execute([$appel['conversation_id'], $userId, $nouveauParticipantId, $nouveauParticipantId, $userId]);
    if ($stmtCheck2->fetchColumn()) {
        echo json_encode(['succes' => false, 'message' => 'Cette personne est déjà dans l\'appel.']);
        exit;
    }
    
    // Vérifier aussi dans la table appel_participants (pour les participants ajoutés dynamiquement)
    try {
        $stmtCheck3 = $pdo->prepare("SELECT 1 FROM appel_participants WHERE appel_id = ? AND utilisateur_id = ?");
        $stmtCheck3->execute([$appelId, $nouveauParticipantId]);
        if ($stmtCheck3->fetchColumn()) {
            echo json_encode(['succes' => false, 'message' => 'Cette personne est déjà dans l\'appel.']);
            exit;
        }
    } catch (PDOException $e) {
        // Table n'existe peut-être pas encore, continuer sans erreur
    }
    
    // Si l'appel est en sonnerie, créer un nouvel appel pour le participant
    // Sinon, utiliser la table appel_participants
    if ($appel['statut'] === 'sonnerie') {
        // Créer un nouvel appel pour inviter cette personne
        $stmt = $pdo->prepare("INSERT INTO appels (appelant_id, receveur_id, conversation_id, type, statut) VALUES (?, ?, ?, ?, 'sonnerie')");
        $stmt->execute([$userId, $nouveauParticipantId, $appel['conversation_id'], $appel['type']]);
        
        $stmtUser = $pdo->prepare("SELECT id, prenom, nom, photo FROM utilisateurs WHERE id = ?");
        $stmtUser->execute([$nouveauParticipantId]);
        $participant = $stmtUser->fetch();
        
        echo json_encode([
            'succes' => true, 
            'message' => 'Invitation envoyée.',
            'participant' => $participant
        ]);
    } else {
        // Appel en cours : utiliser la table appel_participants
        try {
            $stmt = $pdo->prepare("
                INSERT INTO appel_participants (appel_id, utilisateur_id, statut) 
                VALUES (?, ?, 'invite')
                ON DUPLICATE KEY UPDATE statut = 'invite'
            ");
            $stmt->execute([$appelId, $nouveauParticipantId]);
            
            // Créer aussi un appel pour notifier le participant
            $stmt = $pdo->prepare("INSERT INTO appels (appelant_id, receveur_id, conversation_id, type, statut) VALUES (?, ?, ?, ?, 'sonnerie')");
            $stmt->execute([$userId, $nouveauParticipantId, $appel['conversation_id'], $appel['type']]);
            
            $stmtUser = $pdo->prepare("SELECT id, prenom, nom, photo FROM utilisateurs WHERE id = ?");
            $stmtUser->execute([$nouveauParticipantId]);
            $participant = $stmtUser->fetch();
            
            echo json_encode([
                'succes' => true, 
                'message' => 'Invitation envoyée.',
                'participant' => $participant
            ]);
        } catch (PDOException $e) {
            // Fallback : créer un nouvel appel
            $stmt = $pdo->prepare("INSERT INTO appels (appelant_id, receveur_id, conversation_id, type, statut) VALUES (?, ?, ?, ?, 'sonnerie')");
            $stmt->execute([$userId, $nouveauParticipantId, $appel['conversation_id'], $appel['type']]);
            
            $stmtUser = $pdo->prepare("SELECT id, prenom, nom, photo FROM utilisateurs WHERE id = ?");
            $stmtUser->execute([$nouveauParticipantId]);
            $participant = $stmtUser->fetch();
            
            echo json_encode([
                'succes' => true, 
                'message' => 'Invitation envoyée.',
                'participant' => $participant
            ]);
        }
    }
    exit;
}

// ===================== LISTER LES PARTICIPANTS D'UN APPEL =====================
/**
 * Récupère la liste de tous les participants d'un appel (pour les appels de groupe)
 * 
 * Paramètres GET :
 * - appel_id : ID de l'appel
 * 
 * Processus :
 * 1. Récupérer la conversation associée à l'appel
 * 2. Vérifier si c'est un groupe ou une conversation 1-à-1
 * 3. Pour les groupes : récupérer tous les appels actifs de cette conversation
 * 4. Compiler la liste unique de tous les participants (appelants + receveurs)
 * 5. Filtrer pour ne garder que les amis acceptés (sécurité)
 * 
 * Utilisé pour : Afficher la liste des participants dans l'interface d'appel de groupe
 */
if ($action === 'participants') {
    $appelId = (int) ($_GET['appel_id'] ?? 0);
    
    if (!$appelId) {
        echo json_encode(['succes' => false, 'participants' => []]);
        exit;
    }
    
    try {
        // Récupérer le conversation_id de l'appel principal
        // Tous les appels d'un même groupe partagent le même conversation_id
        $stmtAppel = $pdo->prepare("SELECT conversation_id FROM appels WHERE id = ?");
        $stmtAppel->execute([$appelId]);
        $appelPrincipal = $stmtAppel->fetch();
        
        if (!$appelPrincipal) {
            echo json_encode(['succes' => false, 'participants' => []]);
            exit;
        }
        
        $conversationId = $appelPrincipal['conversation_id'];
        
        // Vérifier si c'est un groupe ou une conversation 1-à-1
        // Pour les groupes, on doit récupérer tous les appels de la conversation
        $stmtCheckGroupe = $pdo->prepare("SELECT est_groupe FROM conversations WHERE id = ?");
        $stmtCheckGroupe->execute([$conversationId]);
        $conversation = $stmtCheckGroupe->fetch();
        $estGroupe = $conversation && $conversation['est_groupe'] == 1;
        
        // Récupérer les participants de la conversation (pour vérifier qui peut être dans l'appel)
        // Utilisé pour filtrer et s'assurer que seuls les membres du groupe sont listés
        $stmtParticipantsConv = $pdo->prepare("SELECT utilisateur_id FROM conversation_participants WHERE conversation_id = ?");
        $stmtParticipantsConv->execute([$conversationId]);
        $participantsConversation = $stmtParticipantsConv->fetchAll(PDO::FETCH_COLUMN);
        
        // Récupérer TOUS les appels qui partagent le même conversation_id et qui sont actifs
        // Pour un appel de groupe, il y a plusieurs enregistrements dans la table appels (un par participant)
        $stmtTousAppels = $pdo->prepare("
            SELECT DISTINCT appelant_id, receveur_id, statut 
            FROM appels 
            WHERE conversation_id = ? AND statut IN ('sonnerie', 'en_cours')
        ");
        $stmtTousAppels->execute([$conversationId]);
        $tousAppels = $stmtTousAppels->fetchAll();
        
        // Collecter tous les IDs uniques des participants de tous les appels actifs
        // On parcourt tous les appels et on extrait les appelants et receveurs
        $participantIds = [];
        $participantsData = [];
        
        foreach ($tousAppels as $appel) {
            $appelantId = $appel['appelant_id'];
            $receveurId = $appel['receveur_id'];
            $statutAppel = $appel['statut'];
            
            // Pour chaque appel, on vérifie l'appelant et le receveur
            // SÉCURITÉ : Pour les groupes, vérifier que les participants sont dans la conversation
            // SÉCURITÉ : Pour les appels 1-à-1, vérifier qu'ils sont des amis acceptés
            foreach ([$appelantId, $receveurId] as $participantId) {
                // Éviter les doublons
                if (in_array($participantId, $participantIds)) continue;
                
                // SÉCURITÉ : Si c'est un groupe, vérifier que le participant est dans la conversation
                // On ne peut pas avoir de participants qui ne font pas partie du groupe
                if ($estGroupe && !in_array($participantId, $participantsConversation)) {
                    continue; // Ignorer ce participant (ne fait pas partie du groupe)
                }
                
                // SÉCURITÉ : Si ce n'est pas un groupe, vérifier que c'est un ami accepté
                // Pour les appels 1-à-1, on ne peut appeler que des amis acceptés
                if (!$estGroupe && $participantId != $userId) {
                    $stmtCheckAmi = $pdo->prepare("
                        SELECT 1 FROM connexions 
                        WHERE ((demandeur_id = ? AND destinataire_id = ?) OR (demandeur_id = ? AND destinataire_id = ?))
                        AND statut = 'accepte'
                    ");
                    $stmtCheckAmi->execute([$userId, $participantId, $participantId, $userId]);
                    if (!$stmtCheckAmi->fetchColumn()) {
                        continue; // Ignorer ce participant (n'est pas un ami accepté)
                    }
                }
                
                $participantIds[] = $participantId;
                $stmtUser = $pdo->prepare("SELECT id, prenom, nom, photo FROM utilisateurs WHERE id = ?");
                $stmtUser->execute([$participantId]);
                $user = $stmtUser->fetch();
                if ($user) {
                    $participantsData[] = [
                        'utilisateur_id' => $user['id'],
                        'prenom' => $user['prenom'],
                        'nom' => $user['nom'],
                        'photo' => $user['photo'],
                        'statut' => ($statutAppel === 'en_cours' ? 'en_cours' : 'invite')
                    ];
                }
            }
        }
        
        // Ajouter aussi les participants de la table appel_participants si elle existe
        try {
            $stmtAppelParticipants = $pdo->prepare("
                SELECT ap.utilisateur_id, ap.statut
                FROM appel_participants ap
                JOIN appels a ON a.id = ap.appel_id
                WHERE a.conversation_id = ? AND a.statut IN ('sonnerie', 'en_cours')
            ");
            $stmtAppelParticipants->execute([$conversationId]);
            $appelParticipants = $stmtAppelParticipants->fetchAll();
            
            foreach ($appelParticipants as $ap) {
                $participantId = $ap['utilisateur_id'];
                if (in_array($participantId, $participantIds)) continue;
                
                // Vérifier que le participant est dans la conversation (pour les groupes)
                if ($estGroupe && !in_array($participantId, $participantsConversation)) {
                    continue;
                }
                
                // Vérifier que c'est un ami accepté
                $stmtCheckAmi = $pdo->prepare("
                    SELECT 1 FROM connexions 
                    WHERE ((demandeur_id = ? AND destinataire_id = ?) OR (demandeur_id = ? AND destinataire_id = ?))
                    AND statut = 'accepte'
                ");
                $stmtCheckAmi->execute([$userId, $participantId, $participantId, $userId]);
                if (!$stmtCheckAmi->fetchColumn()) {
                    continue;
                }
                
                $participantIds[] = $participantId;
                $stmtUser = $pdo->prepare("SELECT id, prenom, nom, photo FROM utilisateurs WHERE id = ?");
                $stmtUser->execute([$participantId]);
                $user = $stmtUser->fetch();
                if ($user) {
                    $participantsData[] = [
                        'utilisateur_id' => $user['id'],
                        'prenom' => $user['prenom'],
                        'nom' => $user['nom'],
                        'photo' => $user['photo'],
                        'statut' => ($ap['statut'] === 'en_cours' ? 'en_cours' : 'invite')
                    ];
                }
            }
        } catch (PDOException $e) {
            // Table n'existe peut-être pas encore, continuer
        }
        
        echo json_encode(['succes' => true, 'participants' => $participantsData]);
    } catch (PDOException $e) {
        // Fallback : retourner seulement appelant et receveur de l'appel principal
        $stmtAppel = $pdo->prepare("SELECT appelant_id, receveur_id FROM appels WHERE id = ?");
        $stmtAppel->execute([$appelId]);
        $appel = $stmtAppel->fetch();
        
        $participants = [];
        if ($appel) {
            $stmtUser1 = $pdo->prepare("SELECT id, prenom, nom, photo FROM utilisateurs WHERE id = ?");
            $stmtUser1->execute([$appel['appelant_id']]);
            $user1 = $stmtUser1->fetch();
            if ($user1) {
                $participants[] = ['utilisateur_id' => $user1['id'], 'prenom' => $user1['prenom'], 'nom' => $user1['nom'], 'photo' => $user1['photo'], 'statut' => 'en_cours'];
            }
            
            $stmtUser2 = $pdo->prepare("SELECT id, prenom, nom, photo FROM utilisateurs WHERE id = ?");
            $stmtUser2->execute([$appel['receveur_id']]);
            $user2 = $stmtUser2->fetch();
            if ($user2) {
                $participants[] = ['utilisateur_id' => $user2['id'], 'prenom' => $user2['prenom'], 'nom' => $user2['nom'], 'photo' => $user2['photo'], 'statut' => 'en_cours'];
            }
        }
        
        echo json_encode(['succes' => true, 'participants' => $participants]);
    }
    exit;
}

echo json_encode(['succes' => false, 'message' => 'Action non reconnue.']);
