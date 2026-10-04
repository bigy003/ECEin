<?php
/**
 * ECE In - Page Messagerie (Version Alt)
 * 
 * Cette page gère l'interface de messagerie avec :
 * - Liste des conversations (1-à-1 et groupes)
 * - Affichage des messages
 * - Création de nouvelles conversations
 * - Création et gestion de groupes
 * - Appels audio/vidéo
 * 
 * Sécurité : Seules les conversations avec des amis acceptés sont affichées
 */
require_once __DIR__ . '/config/config.php';
requireConnexion(); // Vérifie que l'utilisateur est connecté, sinon redirige vers login.php

// Initialisation des variables de base
$pdo         = getDB(); // Connexion à la base de données
$userId      = $_SESSION['utilisateur_id']; // ID de l'utilisateur connecté
$userCourant = getUtilisateurConnecte(); // Récupère les infos complètes de l'utilisateur
$pageTitle   = t('Messagerie'); // Titre de la page (affiché dans l'en-tête)
$pageScript  = 'messagerie.js'; // Script JavaScript à charger (gestion des appels, envoi de messages)

// Récupération de l'ID du contact depuis l'URL (si création d'une nouvelle conversation)
$contactId = (int) ($_GET['contact'] ?? 0);
/**
 * GESTION DE LA CRÉATION D'UNE NOUVELLE CONVERSATION 1-À-1
 * 
 * Si un contact est spécifié dans l'URL (?contact=ID), on vérifie :
 * 1. Que le contact est un ami accepté (sécurité)
 * 2. Si une conversation existe déjà entre les deux utilisateurs
 * 3. Sinon, on crée une nouvelle conversation et on ajoute les deux participants
 */
if ($contactId > 0 && $contactId !== $userId) {
    // Vérifier que le contact est un ami accepté (sécurité : on ne peut pas créer de conversation avec n'importe qui)
    $stmtCheckAmi = $pdo->prepare("
        SELECT 1 FROM connexions 
        WHERE ((demandeur_id = ? AND destinataire_id = ?) OR (demandeur_id = ? AND destinataire_id = ?))
        AND statut = 'accepte'
    ");
    $stmtCheckAmi->execute([$userId, $contactId, $contactId, $userId]);
    
    if ($stmtCheckAmi->fetchColumn()) {
        // L'utilisateur est un ami accepté, on peut créer/afficher la conversation
        // Vérifier si une conversation 1-à-1 existe déjà entre ces deux utilisateurs
        $stmtCheck = $pdo->prepare("
            SELECT c.id FROM conversations c
            JOIN conversation_participants p1 ON p1.conversation_id = c.id AND p1.utilisateur_id = :uid
            JOIN conversation_participants p2 ON p2.conversation_id = c.id AND p2.utilisateur_id = :cid
            WHERE c.est_groupe = 0
            LIMIT 1
        ");
        $stmtCheck->execute([':uid' => $userId, ':cid' => $contactId]);
        $convExistante = $stmtCheck->fetchColumn();

        if (!$convExistante) {
            // Aucune conversation n'existe, on en crée une nouvelle
            $pdo->prepare("INSERT INTO conversations (est_groupe) VALUES (0)")->execute();
            $convId = $pdo->lastInsertId();
            // Ajouter les deux participants à la conversation
            $pdo->prepare("INSERT INTO conversation_participants (conversation_id, utilisateur_id) VALUES (?, ?), (?, ?)")
                ->execute([$convId, $userId, $convId, $contactId]);
            $convActiveId = $convId;
        } else {
            // Une conversation existe déjà, on l'utilise
            $convActiveId = $convExistante;
        }
    }
    // Si ce n'est pas un ami accepté, on ne fait rien (pas de création de conversation pour des raisons de sécurité)
}

// Récupération de l'ID de la conversation active depuis l'URL (?conv=ID) ou depuis la création ci-dessus
$convActiveId = $convActiveId ?? (int) ($_GET['conv'] ?? 0);

/**
 * RÉCUPÉRATION DE LA LISTE DES CONVERSATIONS
 * 
 * Cette requête récupère toutes les conversations de l'utilisateur avec :
 * - Les groupes (tous affichés)
 * - Les conversations 1-à-1 (uniquement avec des amis acceptés pour la sécurité)
 * 
 * Pour chaque conversation, on récupère :
 * - Les infos de base (id, nom, type, photo)
 * - Le dernier message et sa date
 * - Le nombre de messages non lus
 * - Les infos du contact (pour les conversations 1-à-1)
 * - Le nombre de participants (pour les groupes)
 */
try {
    $stmtConvs = $pdo->prepare("
        SELECT c.id, c.nom, c.est_groupe, c.photo, c.date_creation,
            -- Sous-requête : récupère le contenu du dernier message
            (SELECT m.contenu FROM messages m WHERE m.conversation_id = c.id ORDER BY m.date_envoi DESC LIMIT 1) AS dernier_message,
            -- Sous-requête : récupère la date du dernier message
            (SELECT m.date_envoi FROM messages m WHERE m.conversation_id = c.id ORDER BY m.date_envoi DESC LIMIT 1) AS date_dernier_message,
            -- Sous-requête : compte les messages non lus (envoyés par d'autres, pas encore lus)
            (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.expediteur_id != :uid AND m.lu = 0) AS nb_non_lus,
            -- Sous-requêtes : récupère les infos du contact pour les conversations 1-à-1
            (SELECT u2.prenom FROM conversation_participants p2 JOIN utilisateurs u2 ON u2.id = p2.utilisateur_id WHERE p2.conversation_id = c.id AND p2.utilisateur_id != :uid2 LIMIT 1) AS contact_prenom,
            (SELECT u2.nom FROM conversation_participants p2 JOIN utilisateurs u2 ON u2.id = p2.utilisateur_id WHERE p2.conversation_id = c.id AND p2.utilisateur_id != :uid3 LIMIT 1) AS contact_nom,
            (SELECT u2.photo FROM conversation_participants p2 JOIN utilisateurs u2 ON u2.id = p2.utilisateur_id WHERE p2.conversation_id = c.id AND p2.utilisateur_id != :uid4 LIMIT 1) AS contact_photo,
            (SELECT u2.id FROM conversation_participants p2 JOIN utilisateurs u2 ON u2.id = p2.utilisateur_id WHERE p2.conversation_id = c.id AND p2.utilisateur_id != :uid5 LIMIT 1) AS contact_id,
            -- Sous-requête : compte le nombre de participants (utile pour les groupes)
            (SELECT COUNT(*) FROM conversation_participants p3 WHERE p3.conversation_id = c.id) AS nb_participants
        FROM conversations c
        JOIN conversation_participants cp ON cp.conversation_id = c.id AND cp.utilisateur_id = :uid6
        WHERE 
            -- Afficher tous les groupes
            c.est_groupe = 1 
           OR 
            -- Pour les conversations 1-à-1, vérifier que l'autre participant est un ami accepté (sécurité)
            EXISTS (
                SELECT 1 FROM conversation_participants cp2
                JOIN connexions conn ON (
                    (conn.demandeur_id = :uid7 AND conn.destinataire_id = cp2.utilisateur_id)
                    OR (conn.demandeur_id = cp2.utilisateur_id AND conn.destinataire_id = :uid8)
                )
                WHERE cp2.conversation_id = c.id 
                  AND cp2.utilisateur_id != :uid9
                  AND conn.statut = 'accepte'
                LIMIT 1
            )
        ORDER BY date_dernier_message DESC, c.date_creation DESC
    ");
    $stmtConvs->execute([':uid' => $userId, ':uid2' => $userId, ':uid3' => $userId, ':uid4' => $userId, ':uid5' => $userId, ':uid6' => $userId, ':uid7' => $userId, ':uid8' => $userId, ':uid9' => $userId]);
    $conversations = $stmtConvs->fetchAll();
    } catch (PDOException $e) {
        // GESTION D'ERREUR : Si la colonne 'lu' n'existe pas dans la table messages
        // (cas d'une base de données non à jour), on utilise une requête alternative sans cette colonne
        try {
            $stmtConvs = $pdo->prepare("
                SELECT c.id, c.nom, c.est_groupe, c.photo, c.date_creation,
                    (SELECT m.contenu FROM messages m WHERE m.conversation_id = c.id ORDER BY m.date_envoi DESC LIMIT 1) AS dernier_message,
                    (SELECT m.date_envoi FROM messages m WHERE m.conversation_id = c.id ORDER BY m.date_envoi DESC LIMIT 1) AS date_dernier_message,
                    0 AS nb_non_lus,
                    (SELECT u2.prenom FROM conversation_participants p2 JOIN utilisateurs u2 ON u2.id = p2.utilisateur_id WHERE p2.conversation_id = c.id AND p2.utilisateur_id != :uid2 LIMIT 1) AS contact_prenom,
                    (SELECT u2.nom FROM conversation_participants p2 JOIN utilisateurs u2 ON u2.id = p2.utilisateur_id WHERE p2.conversation_id = c.id AND p2.utilisateur_id != :uid3 LIMIT 1) AS contact_nom,
                    (SELECT u2.photo FROM conversation_participants p2 JOIN utilisateurs u2 ON u2.id = p2.utilisateur_id WHERE p2.conversation_id = c.id AND p2.utilisateur_id != :uid4 LIMIT 1) AS contact_photo,
                    (SELECT u2.id FROM conversation_participants p2 JOIN utilisateurs u2 ON u2.id = p2.utilisateur_id WHERE p2.conversation_id = c.id AND p2.utilisateur_id != :uid5 LIMIT 1) AS contact_id,
                    (SELECT COUNT(*) FROM conversation_participants p3 WHERE p3.conversation_id = c.id) AS nb_participants
                FROM conversations c
                JOIN conversation_participants cp ON cp.conversation_id = c.id AND cp.utilisateur_id = :uid6
                WHERE c.est_groupe = 1 
                   OR EXISTS (
                       SELECT 1 FROM conversation_participants cp2
                       JOIN connexions conn ON (
                           (conn.demandeur_id = :uid7 AND conn.destinataire_id = cp2.utilisateur_id)
                           OR (conn.demandeur_id = cp2.utilisateur_id AND conn.destinataire_id = :uid8)
                       )
                       WHERE cp2.conversation_id = c.id 
                         AND cp2.utilisateur_id != :uid9
                         AND conn.statut = 'accepte'
                       LIMIT 1
                   )
                ORDER BY date_dernier_message DESC, c.date_creation DESC
            ");
            $stmtConvs->execute([':uid2' => $userId, ':uid3' => $userId, ':uid4' => $userId, ':uid5' => $userId, ':uid6' => $userId, ':uid7' => $userId, ':uid8' => $userId, ':uid9' => $userId]);
            $conversations = $stmtConvs->fetchAll();
    } catch (PDOException $e2) {
        // En cas d'erreur, initialiser un tableau vide
        $conversations = [];
        error_log("Erreur SQL dans messagerie.php: " . $e2->getMessage());
    }
}

/**
 * RÉCUPÉRATION DES DONNÉES DE LA CONVERSATION ACTIVE
 * 
 * Si une conversation est sélectionnée (convActiveId > 0), on récupère :
 * - Les messages de la conversation
 * - Les infos de la conversation (nom, type, photo)
 * - Les participants (pour les groupes) ou le contact (pour les conversations 1-à-1)
 * - On marque les messages comme lus
 */
$messages = [];
$contactActif = null;
$conversationActif = null;
$participantsGroupe = [];

if ($convActiveId > 0) {
    // Vérifier que l'utilisateur fait bien partie de cette conversation (sécurité)
    $stmtVerif = $pdo->prepare("SELECT 1 FROM conversation_participants WHERE conversation_id = ? AND utilisateur_id = ?");
    $stmtVerif->execute([$convActiveId, $userId]);
    if ($stmtVerif->fetchColumn()) {
        // Récupérer les infos de base de la conversation (nom, type groupe/1-à-1, photo)
        $stmtConv = $pdo->prepare("SELECT id, nom, est_groupe, photo FROM conversations WHERE id = ?");
        $stmtConv->execute([$convActiveId]);
        $conversationActif = $stmtConv->fetch();
        
        // Récupérer tous les messages de la conversation avec les infos de l'expéditeur
        $stmtMsgs = $pdo->prepare("
            SELECT m.*, u.nom, u.prenom, u.photo AS avatar
            FROM messages m JOIN utilisateurs u ON u.id = m.expediteur_id
            WHERE m.conversation_id = ? ORDER BY m.date_envoi ASC
        ");
        $stmtMsgs->execute([$convActiveId]);
        $messages = $stmtMsgs->fetchAll();
        
        // Marquer comme lus les messages reçus (pas ceux envoyés par l'utilisateur)
        // Cela permet d'afficher le badge "non lu" uniquement pour les nouveaux messages
        try {
            $pdo->prepare("UPDATE messages SET lu = 1 WHERE conversation_id = ? AND expediteur_id != ? AND lu = 0")
            ->execute([$convActiveId, $userId]);
        } catch (PDOException $e) {
            // Si la colonne lu n'existe pas encore dans la base, on ignore l'erreur
        }
        
        if ($conversationActif && $conversationActif['est_groupe']) {
            // Pour un groupe : récupérer tous les participants pour afficher la liste
            $stmtParticipants = $pdo->prepare("
                SELECT u.id, u.prenom, u.nom, u.photo, u.titre
                FROM conversation_participants cp
                JOIN utilisateurs u ON u.id = cp.utilisateur_id
                WHERE cp.conversation_id = ?
                ORDER BY u.prenom, u.nom
            ");
            $stmtParticipants->execute([$convActiveId]);
            $participantsGroupe = $stmtParticipants->fetchAll();
        } else {
            // Pour une conversation 1-à-1 : récupérer les infos du contact (l'autre participant)
            $stmtContact = $pdo->prepare("SELECT u.* FROM conversation_participants cp JOIN utilisateurs u ON u.id = cp.utilisateur_id WHERE cp.conversation_id = ? AND cp.utilisateur_id != ? LIMIT 1");
            $stmtContact->execute([$convActiveId, $userId]);
            $contactActif = $stmtContact->fetch();
        }
    }
}

/**
 * RÉCUPÉRATION DES LISTES DE CONTACTS
 * 
 * Ces requêtes récupèrent les utilisateurs avec qui l'utilisateur peut interagir.
 * Sécurité : Seuls les amis acceptés sont inclus dans ces listes.
 */

// Récupérer les contacts disponibles pour les appels de groupe (uniquement les connexions acceptées)
// Utilisé pour la fonctionnalité "Ajouter un participant à un appel"
$stmtContactsAppel = $pdo->prepare("
    SELECT DISTINCT u.id, u.nom, u.prenom, u.photo, u.titre
    FROM connexions c
    JOIN utilisateurs u ON u.id = CASE WHEN c.demandeur_id = ? THEN c.destinataire_id ELSE c.demandeur_id END
    WHERE (c.demandeur_id = ? OR c.destinataire_id = ?)
      AND c.statut = 'accepte'  -- Seulement les amis acceptés
      AND u.actif = 1            -- Seulement les utilisateurs actifs
      AND u.id != ?              -- Exclure l'utilisateur lui-même
    ORDER BY u.prenom, u.nom
");
$stmtContactsAppel->execute([$userId, $userId, $userId, $userId]);
$contactsAppel = $stmtContactsAppel->fetchAll();

// Récupérer uniquement les amis acceptés pour créer une nouvelle conversation
// Utilisé dans la modal "Nouvelle conversation" - sécurité : on ne peut pas créer de conversation avec n'importe qui
$stmtTousUtilisateurs = $pdo->prepare("
    SELECT DISTINCT u.id, u.nom, u.prenom, u.photo, u.titre
    FROM connexions c
    JOIN utilisateurs u ON u.id = CASE WHEN c.demandeur_id = ? THEN c.destinataire_id ELSE c.demandeur_id END
    WHERE (c.demandeur_id = ? OR c.destinataire_id = ?)
      AND c.statut = 'accepte'  -- Seulement les amis acceptés
      AND u.actif = 1            -- Seulement les utilisateurs actifs
      AND u.id != ?              -- Exclure l'utilisateur lui-même
    ORDER BY u.prenom, u.nom
");
$stmtTousUtilisateurs->execute([$userId, $userId, $userId, $userId]);
$tousUtilisateurs = $stmtTousUtilisateurs->fetchAll();

// Récupérer les amis pour l'affichage dans la modal de création de groupe
// Utilisé pour sélectionner les participants lors de la création d'un groupe
$stmtAmis = $pdo->prepare("
    SELECT u.id, u.nom, u.prenom, u.photo, u.titre
    FROM connexions c
    JOIN utilisateurs u ON u.id = CASE WHEN c.demandeur_id = ? THEN c.destinataire_id ELSE c.demandeur_id END
    WHERE (c.demandeur_id = ? OR c.destinataire_id = ?) AND c.statut = 'accepte'
    ORDER BY u.nom
");
$stmtAmis->execute([$userId, $userId, $userId]);
$amis = $stmtAmis->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="glass messagerie-wrapper p-0" style="min-height:600px">
    <style>
        .message-wrapper:hover .message-actions {
            opacity: 1 !important;
        }
        .editing-mode {
            background-color: var(--accent-light) !important;
            border: 1px solid var(--accent);
        }
    </style>
    <div class="row g-0" style="min-height:600px">

        <!-- Liste des conversations -->
        <div class="col-md-4 col-lg-3" style="border-right:1px solid var(--glass-border)">
            <div class="p-3 d-flex justify-content-between align-items-center" style="border-bottom:1px solid var(--glass-border)">
                <h6 class="fw-bold mb-0" style="font-size:.85rem"><i class="bi bi-chat-dots me-2" style="color:var(--accent)"></i><?= t('Messages') ?></h6>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-ecein-primary" style="width:32px;height:32px;padding:0;border-radius:10px"
                            data-bs-toggle="modal" data-bs-target="#modalNouveauGroupe" title="<?= t('Nouveau groupe') ?>">
                        <i class="bi bi-people-fill" style="font-size:.8rem"></i>
                    </button>
                <button class="btn btn-sm btn-ecein-primary" style="width:32px;height:32px;padding:0;border-radius:10px"
                        data-bs-toggle="modal" data-bs-target="#modalNouvelleConv" title="<?= t('Nouvelle conversation') ?>">
                    <i class="bi bi-plus-lg" style="font-size:.8rem"></i>
                </button>
                </div>
            </div>
            <div class="p-2" style="border-bottom:1px solid var(--glass-border)">
                <label for="recherche-conv" class="visually-hidden"><?= t('Rechercher parmi vos conversations') ?></label>
                <input type="search" class="form-control form-control-sm" placeholder="<?= t('Rechercher parmi vos conversations...') ?>"
                       id="recherche-conv" oninput="filtrerConversations(this.value)" style="border-radius:var(--radius-sm)">
            </div>
            <div class="liste-conversations" style="overflow-y:auto;max-height:510px">
                <?php if (empty($conversations)): ?>
                <div class="text-center p-4 small" style="color:var(--text-3)">
                    <i class="bi bi-chat fs-2 d-block mb-2" style="opacity:.25"></i>
                    <?= t('Aucune conversation') ?>
                </div>
                <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                <?php 
                // Préparer les données pour la recherche
                $nomRecherche = '';
                if ($conv['est_groupe']) {
                    $nomRecherche = trim(strtolower($conv['nom'] ?? 'groupe'));
                } else {
                    $prenom = trim($conv['contact_prenom'] ?? '');
                    $nom = trim($conv['contact_nom'] ?? '');
                    $nomRecherche = trim(strtolower($prenom . ' ' . $nom));
                }
                $dernierMsgRecherche = trim(strtolower($conv['dernier_message'] ?? ''));
                ?>
                <a href="messagerie.php?conv=<?= $conv['id'] ?>"
                   class="d-flex align-items-center gap-3 p-3 text-decoration-none conversation-item <?= $convActiveId == $conv['id'] ? 'active' : '' ?>"
                   style="border-bottom:1px solid var(--glass-border)"
                   data-nom="<?= h($nomRecherche) ?>"
                   data-message="<?= h($dernierMsgRecherche) ?>">
                    <div class="position-relative flex-shrink-0">
                        <?php if ($conv['est_groupe']): ?>
                        <img src="<?= h($conv['photo'] ?? 'assets/images/default_avatar.svg') ?>" alt="" class="rounded-3" width="44" height="44" style="object-fit:cover">
                        <?php else: ?>
                        <img src="<?= h($conv['contact_photo'] ?? 'assets/images/default_avatar.svg') ?>"
                             alt="" class="rounded-3" width="44" height="44" style="object-fit:cover">
                        <?php endif; ?>
                        <?php if ($conv['nb_non_lus'] > 0): ?>
                        <span class="badge-notif"><?= $conv['nb_non_lus'] ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold small <?= $conv['nb_non_lus'] > 0 ? 'fw-bold' : '' ?>" style="color:var(--text)">
                                <?php if ($conv['est_groupe']): ?>
                                    <?= h($conv['nom'] ?? 'Groupe') ?>
                                    <?php if (!empty($conv['nb_participants']) && $conv['nb_participants'] > 0): ?>
                                        <small class="text-muted">(<?= (int) $conv['nb_participants'] ?>)</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?= h($conv['contact_prenom'] . ' ' . $conv['contact_nom']) ?>
                                <?php endif; ?>
                            </span>
                            <?php if ($conv['date_dernier_message']): ?>
                            <span style="font-size:.65rem;color:var(--text-3)"><?= tempsEcoule($conv['date_dernier_message']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="text-truncate" style="font-size:.75rem;color:var(--text-3)">
                            <?= h(substr($conv['dernier_message'] ?? t('Démarrer la conversation...'), 0, 50)) ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat -->
        <div class="col-md-8 col-lg-9 d-flex flex-column" style="background:var(--bg)">
            <?php if ($convActiveId > 0 && ($contactActif || ($conversationActif && $conversationActif['est_groupe']))): ?>
            <div class="p-3 d-flex align-items-center gap-3" style="border-bottom:1px solid var(--glass-border)">
                <?php if ($conversationActif && $conversationActif['est_groupe']): ?>
                <div class="position-relative" style="cursor:pointer;" onclick="ouvrirModalModifierGroupe()">
                    <img src="<?= h($conversationActif['photo'] ?? 'assets/images/default_avatar.svg') ?>" alt="" class="rounded-3" width="40" height="40" style="object-fit:cover">
                    <div class="position-absolute" style="bottom:-2px;right:-2px;background:var(--accent);border-radius:50%;width:16px;height:16px;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-pencil-fill" style="font-size:0.6rem;color:white;"></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-bold small" style="cursor:pointer;" onclick="ouvrirModalModifierGroupe()"><?= h($conversationActif['nom'] ?? t('Groupe')) ?></div>
                    <div style="font-size:.72rem;color:var(--text-3)">
                        <?= count($participantsGroupe) ?> <?= count($participantsGroupe) > 1 ? t('participants') : t('participant') ?>
                    </div>
                </div>
                <?php elseif ($contactActif): ?>
                <img src="<?= h($contactActif['photo'] ?? 'assets/images/default_avatar.svg') ?>" alt="" class="rounded-3" width="40" height="40" style="object-fit:cover">
                <div>
                    <div class="fw-bold small"><?= h(($contactActif['prenom'] ?? '') . ' ' . ($contactActif['nom'] ?? '')) ?></div>
                    <div style="font-size:.72rem;color:var(--text-3)"><?= h($contactActif['titre'] ?? '') ?></div>
                </div>
                <?php endif; ?>
                <div class="ms-auto d-flex gap-2">
                    <?php if ($conversationActif && $conversationActif['est_groupe']): ?>
                    <button class="btn btn-sm btn-light" onclick="lancerAppelGroupe('audio')" title="<?= t('Appel audio groupe') ?>" style="border-radius:10px">
                        <i class="bi bi-telephone-fill" style="color:var(--emerald)"></i>
                    </button>
                    <button class="btn btn-sm btn-light" onclick="lancerAppelGroupe('video')" title="<?= t('Appel vidéo groupe') ?>" style="border-radius:10px">
                        <i class="bi bi-camera-video-fill" style="color:var(--accent)"></i>
                    </button>
                    <?php elseif ($contactActif): ?>
                    <button class="btn btn-sm btn-light" onclick="lancerAppel('audio')" title="<?= t('Appel audio') ?>" style="border-radius:10px">
                        <i class="bi bi-telephone-fill" style="color:var(--emerald)"></i>
                    </button>
                    <button class="btn btn-sm btn-light" onclick="lancerAppel('video')" title="<?= t('Appel vidéo') ?>" style="border-radius:10px">
                        <i class="bi bi-camera-video-fill" style="color:var(--accent)"></i>
                    </button>
                    <a href="utilisateur.php?id=<?= $contactActif['id'] ?? 0 ?>" class="btn btn-sm btn-light" title="<?= t('Voir profil') ?>" style="border-radius:10px">
                        <i class="bi bi-person"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="chat-messages flex-grow-1 p-3" id="chat-messages" style="overflow-y:auto">
                <?php foreach ($messages as $msg): ?>
                <?php 
                $estMoi = $msg['expediteur_id'] == $userId; 
                $estSupprime = !empty($msg['est_supprime']);
                $estModifie = !empty($msg['est_modifie']);
                // Vérifier si le message a moins de 30 secondes
                $modifiable = $estMoi && !$estSupprime && (time() - strtotime($msg['date_envoi']) <= 30);
                ?>
                <div class="message-wrapper <?= $estMoi ? 'message-moi' : 'message-autre' ?> mb-3" data-msg-id="<?= $msg['id'] ?>" data-date="<?= strtotime($msg['date_envoi']) ?>">
                    <?php if (!$estMoi): ?>
                    <img src="<?= h($msg['avatar']) ?>" alt="" class="rounded-3 me-2" width="30" height="30" style="object-fit:cover">
                    <?php endif; ?>
                    
                    <?php if ($estMoi && !$estSupprime): ?>
                    <div class="message-actions me-2 d-flex flex-column gap-1" style="opacity: 0; transition: opacity 0.2s;">
                        <button class="btn btn-sm btn-light py-0 px-1 btn-edit-msg" title="<?= t('Modifier') ?>" onclick="editerMessage(<?= $msg['id'] ?>)" <?= $modifiable ? '' : 'style="display:none;"' ?>><i class="bi bi-pencil" style="font-size:0.75rem;"></i></button>
                        <button class="btn btn-sm btn-light py-0 px-1 text-danger btn-delete-msg" title="<?= t('Supprimer') ?>" onclick="supprimerMessage(<?= $msg['id'] ?>)"><i class="bi bi-trash" style="font-size:0.75rem;"></i></button>
                    </div>
                    <?php endif; ?>

                    <div class="message-bulle <?= $estMoi ? 'message-bubble-moi' : 'message-bubble-autre' ?> <?= $estSupprime ? 'fst-italic text-muted' : '' ?>" id="msg-content-<?= $msg['id'] ?>">
                        <?php if ($estSupprime): ?>
                            <div><i class="bi bi-slash-circle me-1"></i> <?= t('Ce message a été supprimé.') ?></div>
                        <?php else: ?>
                            <?php if (!empty($msg['fichier_joint'])): ?>
                                <div class="mb-2">
                                    <?php 
                                    $ext = strtolower(pathinfo($msg['fichier_joint'], PATHINFO_EXTENSION));
                                    if (in_array($ext, ['jpg','jpeg','png','gif','webp'])): ?>
                                        <a href="<?= h($msg['fichier_joint']) ?>" target="_blank">
                                            <img src="<?= h($msg['fichier_joint']) ?>" alt="<?= t('Pièce jointe') ?>" class="img-fluid rounded" style="max-height: 200px;">
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= h($msg['fichier_joint']) ?>" target="_blank" class="text-decoration-none d-flex align-items-center gap-2 p-2 bg-light rounded text-dark" style="background: rgba(255,255,255,0.2) !important;">
                                            <i class="bi bi-file-earmark-text fs-4"></i>
                                            <span class="small text-truncate" style="max-width: 150px;"><?= h(basename($msg['fichier_joint'])) ?></span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($msg['contenu'])): ?>
                                <div class="msg-text"><?= nl2br(h($msg['contenu'])) ?></div>
                            <?php endif; ?>
                            <?php if ($estModifie): ?>
                                <small class="text-white-50" style="font-size: 0.65rem;">(<?= t('modifié') ?>)</small>
                            <?php endif; ?>
                        <?php endif; ?>
                        <div class="d-flex align-items-center gap-1" style="justify-content: <?= $estMoi ? 'flex-end' : 'flex-start'; ?>;">
                        <div class="message-time"><?= tempsEcoule($msg['date_envoi']) ?></div>
                            <?php if ($estMoi && !$estSupprime): ?>
                                <?php 
                                // Par défaut, un message envoyé est toujours distribué (sauf si la colonne n'existe pas)
                                $distribue = isset($msg['distribue']) ? (int)$msg['distribue'] : 1;
                                $lu = isset($msg['lu']) ? (int)$msg['lu'] : 0;
                                ?>
                                <span class="message-status" style="font-size: 0.75rem; margin-left: 4px;">
                                    <?php if ($lu == 1): ?>
                                        <i class="bi bi-check2-all" style="color: #4FC3F7;"></i>
                                    <?php elseif ($distribue == 1): ?>
                                        <i class="bi bi-check2-all" style="color: var(--text-3);"></i>
                                    <?php else: ?>
                                        <i class="bi bi-check2" style="color: var(--text-3);"></i>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($estMoi): ?>
                    <img src="<?= h($userCourant['photo']) ?>" alt="" class="rounded-3 ms-2" width="30" height="30" style="object-fit:cover">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <?php if (empty($messages)): ?>
                <div class="empty-state">
                    <i class="bi bi-chat-dots"></i>
                    <?php if ($conversationActif && $conversationActif['est_groupe']): ?>
                    <p><?= t('Démarrez la conversation dans %s !', h($conversationActif['nom'] ?? t('ce groupe'))) ?></p>
                    <?php elseif ($contactActif): ?>
                    <p><?= t('Démarrez la conversation avec %s !', h($contactActif['prenom'] ?? t('cette personne'))) ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="p-3 position-relative" style="border-top:1px solid var(--glass-border);background:var(--bg-2)">
                <div id="emoji-picker-container" style="display: none; position: absolute; bottom: 100%; left: 1rem; z-index: 1050; margin-bottom: 5px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); border-radius: 8px; background: white; overflow: hidden;">
                    <emoji-picker></emoji-picker>
                </div>
                <form id="formMessage" class="d-flex gap-2" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="envoyer">
                    <input type="hidden" name="conversation_id" value="<?= $convActiveId ?>">
                    <div class="input-group">
                        <button type="button" class="btn btn-light" id="btn-emoji" title="Emoji" style="border-radius:var(--radius-sm) 0 0 var(--radius-sm)">
                            <i class="bi bi-emoji-smile"></i>
                        </button>
                        <button type="button" class="btn btn-light border-start-0" id="btn-piece-jointe" title="<?= t('Joindre un fichier') ?>" onclick="document.getElementById('input-fichier').click()">
                            <i class="bi bi-paperclip"></i>
                        </button>
                        <input type="file" name="fichier" id="input-fichier" style="display:none" onchange="previewFile(this)">
                        <input type="text" name="contenu" id="input-message" class="form-control"
                               placeholder="<?= t('Écrivez votre message...') ?>" autocomplete="off">
                        <button type="submit" class="btn btn-ecein-primary" id="btn-envoyer" style="border-radius:0 var(--radius-sm) var(--radius-sm) 0">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </div>
                </form>
                <div id="file-preview-container" class="mt-2 text-muted small" style="display:none;">
                    <i class="bi bi-file-earmark-text me-1"></i> <span id="file-preview-name"></span>
                    <i class="bi bi-x-circle ms-2" style="cursor:pointer;color:var(--rose)" onclick="clearFile()"></i>
                </div>
            </div>

            <?php else: ?>
            <div class="d-flex flex-column align-items-center justify-content-center h-100 p-4" style="color:var(--text-3)">
                <i class="bi bi-chat-dots-fill fs-1 mb-3" style="opacity:.25"></i>
                <h5 class="fw-semibold" style="color:var(--text)"><?= t('Vos messages') ?></h5>
                <p class="text-center small"><?= t('Sélectionnez une conversation ou démarrez-en une nouvelle') ?></p>
                <button class="btn btn-ecein-primary" data-bs-toggle="modal" data-bs-target="#modalNouvelleConv">
                    <i class="bi bi-plus-lg me-2"></i><?= t('Nouvelle conversation') ?>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Nouvelle conversation -->
<div class="modal fade" id="modalNouvelleConv" tabindex="-1" data-bs-backdrop="false" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="position:relative;z-index:1055;">
            <div class="modal-header"><h5 class="modal-title fw-bold"><?= t('Nouvelle conversation') ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" style="position:relative;z-index:1056;">
                <label for="search-amis-modal" class="visually-hidden"><?= t('Rechercher parmi tous les utilisateurs ECE In') ?></label>
                <input type="search" class="form-control mb-3" id="search-amis-modal" placeholder="<?= t('Rechercher parmi tous les utilisateurs ECE In...') ?>" oninput="filtrerAmis(this.value)">
                <div class="liste-amis-modal" style="max-height:350px;overflow-y:auto;position:relative;z-index:1057;">
                    <?php foreach ($tousUtilisateurs as $utilisateur): ?>
                    <a href="messagerie.php?contact=<?= $utilisateur['id'] ?>"
                       class="d-flex align-items-center gap-3 p-2 rounded-3 text-decoration-none ami-item hover-bg mb-1"
                       style="color:var(--text);position:relative;z-index:1058;pointer-events:auto;"
                       data-prenom="<?= h(strtolower($utilisateur['prenom'])) ?>"
                       data-nom="<?= h(strtolower($utilisateur['nom'])) ?>"
                       data-nom-complet="<?= h(strtolower($utilisateur['prenom'] . ' ' . $utilisateur['nom'])) ?>"
                       onclick="event.stopPropagation();">
                        <img src="<?= h($utilisateur['photo']) ?>" alt="" class="rounded-3" width="40" height="40" style="object-fit:cover">
                        <div>
                            <div class="fw-semibold small"><?= h($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?></div>
                            <div style="font-size:.72rem;color:var(--text-3)"><?= h($utilisateur['titre'] ?? '') ?></div>
                        </div>
                        <i class="bi bi-arrow-right ms-auto" style="color:var(--text-3)"></i>
                    </a>
                    <?php endforeach; ?>
                    <?php if (empty($tousUtilisateurs)): ?>
                    <p class="text-center small" style="color:var(--text-3)"><?= t('Aucun utilisateur disponible') ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= t('Annuler') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nouveau groupe -->
<div class="modal fade" id="modalNouveauGroupe" tabindex="-1" data-bs-backdrop="false" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="position:relative;z-index:1055;">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><?= t('Nouveau groupe') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="position:relative;z-index:1056;">
                <div class="mb-3">
                    <label class="form-label small fw-semibold"><?= t('Nom du groupe') ?></label>
                    <input type="text" id="nom-groupe" class="form-control" placeholder="<?= t('Ex: Groupe d\'étude') ?>" maxlength="200">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold"><?= t('Participants') ?></label>
                    <input type="search" id="search-participants-groupe" class="form-control mb-2" placeholder="<?= t('Rechercher parmi vos connexions...') ?>" oninput="filtrerAmisGroupe(this.value)">
                    <div id="participants-selectionnes" class="mb-2" style="min-height:40px;">
                        <small class="text-muted"><?= t('Aucun participant sélectionné') ?></small>
                    </div>
                </div>
                <div class="liste-amis-groupe" style="max-height:300px;overflow-y:auto;position:relative;z-index:1057;">
                    <?php foreach ($amis as $ami): ?>
                    <div class="d-flex align-items-center gap-3 p-2 rounded-3 ami-item-groupe hover-bg mb-1 participant-selectable"
                         style="color:var(--text);position:relative;z-index:1058;pointer-events:auto;cursor:pointer;"
                         data-ami-id="<?= $ami['id'] ?>"
                         data-ami-nom="<?= h($ami['prenom'] . ' ' . $ami['nom']) ?>"
                         data-ami-photo="<?= h($ami['photo']) ?>">
                        <img src="<?= h($ami['photo']) ?>" alt="" class="rounded-3" width="40" height="40" style="object-fit:cover">
                        <div class="flex-grow-1">
                            <div class="fw-semibold small"><?= h($ami['prenom'] . ' ' . $ami['nom']) ?></div>
                            <div style="font-size:.72rem;color:var(--text-3)"><?= h($ami['titre'] ?? '') ?></div>
                        </div>
                        <i class="bi bi-circle participant-check" id="check-<?= $ami['id'] ?>" style="color:var(--text-3);font-size:1.2rem;"></i>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($amis)): ?>
                    <p class="text-center small" style="color:var(--text-3)"><?= t('Aucune connexion disponible') ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= t('Annuler') ?></button>
                <button type="button" class="btn btn-ecein-primary" onclick="creerGroupe()"><?= t('Créer le groupe') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Modifier Groupe -->
<?php if ($conversationActif && $conversationActif['est_groupe']): ?>
<div class="modal fade" id="modalModifierGroupe" tabindex="-1" data-bs-backdrop="false" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="position:relative;z-index:1055;">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><?= t('Modifier le groupe') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 text-center">
                    <label class="form-label small fw-semibold d-block mb-2"><?= t('Photo du groupe') ?></label>
                    <div class="position-relative d-inline-block">
                        <img id="preview-groupe-photo" src="<?= !empty($conversationActif['photo']) ? h($conversationActif['photo']) : 'assets/images/default_avatar.svg' ?>" 
                             alt="<?= t('Photo du groupe') ?>" class="rounded-circle" width="100" height="100" style="object-fit:cover;cursor:pointer;"
                             onclick="document.getElementById('input-groupe-photo').click()">
                        <div class="position-absolute" style="bottom:0;right:0;background:var(--accent);border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;"
                             onclick="document.getElementById('input-groupe-photo').click()">
                            <i class="bi bi-camera-fill" style="color:white;font-size:0.9rem;"></i>
                        </div>
                        <input type="file" id="input-groupe-photo" accept="image/*" style="display:none;" onchange="previewGroupePhoto(this)">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold"><?= t('Nom du groupe') ?></label>
                    <input type="text" id="nom-groupe-modifier" class="form-control" 
                           value="<?= h($conversationActif['nom'] ?? '') ?>" 
                           placeholder="<?= t('Nom du groupe') ?>" maxlength="200">
                </div>
            </div>
            <div class="modal-footer border-0" style="position:relative;z-index:1056;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="pointer-events:auto;"><?= t('Annuler') ?></button>
                <button type="button" class="btn btn-ecein-primary" onclick="sauvegarderModificationsGroupe()" style="pointer-events:auto;"><?= t('Enregistrer') ?></button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Overlay Appel -->
<div id="call-overlay" class="call-overlay" style="display:none">
    <div class="call-overlay-bg"></div>
    <div class="call-content">
        <!-- Zone pour les vidéos des participants -->
        <div id="call-participants-container" class="call-participants-container">
            <!-- Les participants seront ajoutés dynamiquement ici -->
        </div>
        
        <!-- Vidéo distante principale (pour compatibilité) -->
        <video id="remote-video" autoplay playsinline style="display:none;"></video>
        <video id="local-video" autoplay playsinline muted class="local-video"></video>
        
        <!-- Info d'appel (affichée quand pas de vidéo) -->
        <div id="call-info" class="call-info">
            <img id="call-avatar" src="assets/images/default_avatar.svg" alt="" class="call-avatar">
            <h4 id="call-name" class="call-name"></h4>
            <p id="call-status" class="call-status"></p>
            <p id="call-timer" class="call-timer" style="display:none">00:00</p>
        </div>
        <div class="call-controls">
            <div id="call-controls-active" style="display:none">
                <button class="call-btn call-btn-muted" onclick="toggleMute()" id="btn-mute" title="<?= t('Micro') ?>"><i class="bi bi-mic-fill"></i></button>
                <button class="call-btn call-btn-muted" onclick="toggleCamera()" id="btn-camera" title="<?= t('Caméra') ?>" style="display:none"><i class="bi bi-camera-video-fill"></i></button>
                <button class="call-btn call-btn-light" onclick="ouvrirModalAjouterParticipant()" title="<?= t('Ajouter une personne') ?>" id="btn-add-participant" style="display:none;"><i class="bi bi-person-plus-fill"></i></button>
                <button class="call-btn call-btn-danger" onclick="terminerAppel()" title="<?= t('Raccrocher') ?>"><i class="bi bi-telephone-x-fill"></i></button>
            </div>
            <div id="call-controls-incoming" style="display:none">
                <button class="call-btn call-btn-success call-btn-ring" onclick="accepterAppel()" title="<?= t('Décrocher') ?>"><i class="bi bi-telephone-fill"></i></button>
                <button class="call-btn call-btn-danger" onclick="refuserAppel()" title="<?= t('Refuser') ?>"><i class="bi bi-telephone-x-fill"></i></button>
            </div>
            <div id="call-controls-outgoing" style="display:none">
                <button class="call-btn call-btn-light" onclick="ouvrirModalAjouterParticipant()" title="<?= t('Ajouter une personne') ?>" id="btn-add-participant-outgoing" style="display:none;"><i class="bi bi-person-plus-fill"></i></button>
                <button class="call-btn call-btn-danger" onclick="annulerAppel()" title="<?= t('Annuler') ?>"><i class="bi bi-telephone-x-fill"></i></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajouter un participant à l'appel -->
<div class="modal fade" id="modalAjouterParticipant" tabindex="-1" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold"><?= t('Ajouter une personne à l\'appel') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="search-participant" class="visually-hidden"><?= t('Rechercher un contact') ?></label>
                    <input type="text" class="form-control" id="search-participant" placeholder="<?= t('Rechercher un contact...') ?>">
                </div>
                <div id="liste-contacts-appel" style="max-height: 300px; overflow-y: auto;">
                    <!-- Liste des contacts sera chargée ici -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const chatEl = document.getElementById('chat-messages');
if (chatEl) chatEl.scrollTop = chatEl.scrollHeight;

// Liste des contacts disponibles pour les appels de groupe
const CONTACTS_APPEL = <?= json_encode($contactsAppel) ?>;

// --- CONFIGURATION EMOJIS ---
document.addEventListener('DOMContentLoaded', () => {
    const btnEmoji = document.querySelector('#btn-emoji');
    const inputMsg = document.querySelector('#input-message');
    const emojiContainer = document.querySelector('#emoji-picker-container');
    const picker = document.querySelector('emoji-picker');
    
    if (btnEmoji && inputMsg && picker) {
        // Toggle the picker when the button is clicked
        btnEmoji.addEventListener('click', (e) => {
            e.preventDefault();
            emojiContainer.style.display = emojiContainer.style.display === 'none' ? 'block' : 'none';
        });

        // Insert emoji when selected
        picker.addEventListener('emoji-click', event => {
            inputMsg.value += event.detail.unicode;
            inputMsg.focus();
        });
        
        // Hide picker when clicking outside
        document.addEventListener('click', event => {
            if (!emojiContainer.contains(event.target) && event.target !== btnEmoji && !btnEmoji.contains(event.target)) {
                emojiContainer.style.display = 'none';
            }
        });
    }
});

const CONV_ID = <?= $convActiveId ?>;
const USER_ID = <?= $userId ?>;
const USER_PHOTO = <?= json_encode($userCourant['photo'] ?? 'assets/images/default_avatar.svg') ?>;
<?php if ($conversationActif && $conversationActif['est_groupe']): ?>
const CONTACT_ID = 0;
const CONTACT_NAME = <?= json_encode($conversationActif['nom'] ?? t('Groupe')) ?>;
const CONTACT_PHOTO = 'assets/images/default_avatar.svg';
const IS_GROUP = true;
<?php elseif ($contactActif): ?>
const CONTACT_ID = <?= $contactActif['id'] ?? 0 ?>;
const CONTACT_NAME = <?= json_encode(($contactActif['prenom'] ?? '') . ' ' . ($contactActif['nom'] ?? '')) ?>;
const CONTACT_PHOTO = <?= json_encode($contactActif['photo'] ?? 'assets/images/default_avatar.svg') ?>;
const IS_GROUP = false;
<?php else: ?>
const CONTACT_ID = 0;
const CONTACT_NAME = '';
const CONTACT_PHOTO = 'assets/images/default_avatar.svg';
const IS_GROUP = false;
<?php endif; ?>

function filtrerConversations(q) {
    console.log('=== filtrerConversations appelé avec:', q);
    if (!q) q = '';
    var searchTerm = q.toLowerCase().trim();
    console.log('Terme de recherche:', searchTerm);
    
    var liste = document.querySelector('.liste-conversations');
    if (!liste) {
        console.error('❌ Liste de conversations introuvable');
        return;
    }
    console.log('✅ Liste trouvée');
    
    var items = liste.querySelectorAll('.conversation-item');
    console.log('Nombre de conversations:', items.length);
    
    if (!items || items.length === 0) {
        console.log('Aucune conversation trouvée');
        return;
    }
    
    var visibleCount = 0;
    
    if (searchTerm === '') {
        console.log('Recherche vide - afficher toutes les conversations');
        for (var j = 0; j < items.length; j++) {
            items[j].style.display = '';
        }
        var noResults = liste.querySelector('.no-results-conv');
        if (noResults) noResults.remove();
        return;
    }
    
    // Parcourir toutes les conversations
    for (var i = 0; i < items.length; i++) {
        var item = items[i];
        var nom = '';
        
        // Essayer d'abord avec l'attribut data-nom
        var dataNom = item.getAttribute('data-nom');
        console.log('Conversation', i, '- data-nom:', dataNom);
        
        if (dataNom && dataNom.trim() !== '') {
            nom = dataNom.trim().toLowerCase();
        } else {
            // Sinon, extraire le nom depuis le texte visible
            var nameElement = item.querySelector('.fw-semibold, .fw-bold');
            if (nameElement) {
                nom = nameElement.textContent.trim().toLowerCase();
                // Enlever les numéros entre parenthèses comme "(3)"
                nom = nom.replace(/\s*\(\d+\)\s*/g, '').trim();
                console.log('Nom extrait depuis texte:', nom);
            } else {
                console.log('Aucun élément de nom trouvé');
            }
        }
        
        // Rechercher UNIQUEMENT dans le nom du contact/groupe
        var matches = nom && nom.includes(searchTerm);
        console.log('Match pour "' + nom + '" avec "' + searchTerm + '":', matches);
        
        if (matches) {
            item.style.display = '';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    }
    
    console.log('✅ Résultat: ' + visibleCount + ' conversations visibles sur ' + items.length);
    
    // Afficher un message si aucun résultat
    var existingNoResults = liste.querySelector('.no-results-conv');
    if (existingNoResults) existingNoResults.remove();
    
    if (visibleCount === 0 && items.length > 0) {
        var noResultsDiv = document.createElement('div');
        noResultsDiv.className = 'text-center p-4 small no-results-conv';
        noResultsDiv.style.color = 'var(--text-3)';
        noResultsDiv.innerHTML = '<i class="bi bi-search fs-4 d-block mb-2" style="opacity:.25"></i>' + t('Aucune conversation trouvée');
        liste.appendChild(noResultsDiv);
    }
}

function filtrerAmis(q) {
    if (!q) q = '';
    var searchTerm = q.toLowerCase().trim();
    var $liste = $('.liste-amis-modal');
    var $items = $('.ami-item');
    var visibleCount = 0;
    
    if (searchTerm === '') {
        // Afficher tous les éléments si la recherche est vide
        $items.show();
        $liste.find('.no-results').remove();
        return;
    }
    
    $items.each(function() {
        var $item = $(this);
        var prenom = ($item.attr('data-prenom') || '').toLowerCase();
        var nom = ($item.attr('data-nom') || '').toLowerCase();
        var nomComplet = ($item.attr('data-nom-complet') || '').toLowerCase();
        var textContent = $item.text().toLowerCase();
        
        // Rechercher dans le prénom, nom, nom complet ou le texte complet
        var matches = prenom.includes(searchTerm) || 
                     nom.includes(searchTerm) || 
                     nomComplet.includes(searchTerm) ||
                     textContent.includes(searchTerm);
        
        if (matches) {
            $item.show();
            visibleCount++;
        } else {
            $item.hide();
        }
    });
    
    // Afficher un message si aucun résultat
    $liste.find('.no-results').remove();
    if (visibleCount === 0) {
        $liste.append('<p class="text-center small no-results" style="color:var(--text-3);padding:20px;">' + t('Aucun résultat trouvé') + '</p>');
    }
}

// Gestion des participants pour le groupe
window.participantsGroupe = [];

function filtrerAmisGroupe(q) {
    if (!q) q = '';
    var searchTerm = q.toLowerCase();
    document.querySelectorAll('.ami-item-groupe').forEach(function(el) {
        var text = el.textContent.toLowerCase();
        el.style.display = text.includes(searchTerm) ? '' : 'none';
    });
}

window.toggleParticipantGroupe = function(id, nom, photo) {
    console.log('toggleParticipantGroupe appelé avec:', id, nom, photo);
    
    if (!window.participantsGroupe) window.participantsGroupe = [];
    
    var idNum = parseInt(id);
    var index = window.participantsGroupe.findIndex(function(p) { 
        return parseInt(p.id) === idNum; 
    });
    var checkIcon = document.getElementById('check-' + id);
    
    console.log('Index trouvé:', index, 'pour id:', idNum);
    
    if (index === -1) {
        // Ajouter le participant
        window.participantsGroupe.push({
            id: idNum, 
            nom: String(nom || ''), 
            photo: String(photo || 'assets/images/default_avatar.svg')
        });
        console.log('Participant ajouté. Total:', window.participantsGroupe.length);
        if (checkIcon) {
            checkIcon.className = 'bi bi-check-circle-fill';
            checkIcon.style.color = 'var(--accent)';
        }
    } else {
        // Retirer le participant
        window.participantsGroupe.splice(index, 1);
        console.log('Participant retiré. Total:', window.participantsGroupe.length);
        if (checkIcon) {
            checkIcon.className = 'bi bi-circle';
            checkIcon.style.color = 'var(--text-3)';
        }
    }
    
    afficherParticipantsSelectionnes();
};

function afficherParticipantsSelectionnes() {
    var container = document.getElementById('participants-selectionnes');
    if (!container) {
        console.log('Container participants-selectionnes introuvable');
        return;
    }
    
    if (!window.participantsGroupe || window.participantsGroupe.length === 0) {
        container.innerHTML = '<small class="text-muted">' + t('Aucun participant sélectionné') + '</small>';
        return;
    }
    
    var html = '<div class="d-flex flex-wrap gap-2 align-items-center">';
    html += '<small class="text-muted me-2">' + t('Participants') + ' (' + window.participantsGroupe.length + '):</small>';
    window.participantsGroupe.forEach(function(p) {
        var nomEscaped = String(p.nom).replace(/'/g, "&#39;").replace(/"/g, "&quot;");
        var photoEscaped = String(p.photo || 'assets/images/default_avatar.svg').replace(/'/g, "&#39;").replace(/"/g, "&quot;");
        html += '<div class="d-flex align-items-center gap-1 bg-light rounded-pill px-2 py-1" style="font-size:.75rem;">';
        html += '<img src="' + photoEscaped + '" alt="" class="rounded-circle" width="20" height="20" style="object-fit:cover">';
        html += '<span>' + nomEscaped + '</span>';
        html += '<i class="bi bi-x-circle ms-1" style="cursor:pointer;font-size:.7rem;" onclick="window.toggleParticipantGroupe(' + p.id + ', ' + JSON.stringify(p.nom) + ', ' + JSON.stringify(p.photo) + ')"></i>';
        html += '</div>';
    });
    html += '</div>';
    container.innerHTML = html;
}

function creerGroupe() {
    var nomGroupe = document.getElementById('nom-groupe');
    if (!nomGroupe) {
        alert(t('Erreur : champ nom introuvable.'));
        return;
    }
    
    var nom = nomGroupe.value.trim();
    if (!nom) {
        alert(t('Veuillez entrer un nom pour le groupe.'));
        return;
    }
    
    if (!window.participantsGroupe || window.participantsGroupe.length === 0) {
        alert(t('Veuillez sélectionner au moins un participant.'));
        return;
    }
    
    var participantsIds = window.participantsGroupe.map(function(p) { return parseInt(p.id); });
    
    $.post('api/conversations.php', {
        action: 'creer_groupe',
        nom: nom,
        participants: participantsIds
    }, function(res) {
        if (res.succes) {
            // Fermer la modal
            var modalEl = document.getElementById('modalNouveauGroupe');
            if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                var modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
            
            // Rediriger vers la conversation du groupe
            window.location.href = 'messagerie.php?conv=' + res.conversation_id;
        } else {
            alert(res.message || t('Erreur lors de la création du groupe.'));
        }
    }).fail(function() {
        alert(t('Erreur lors de la création du groupe.'));
    });
}


function escapeHtml(text) {
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// S'assurer que la modal est cliquable et nettoyer les overlays
document.addEventListener('DOMContentLoaded', function() {
    // Nettoyer les overlays orphelins au chargement
    var backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(function(backdrop) {
        if (backdrop.parentNode) {
            backdrop.remove();
        }
    });
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    var modalNouvelleConv = document.getElementById('modalNouvelleConv');
    if (modalNouvelleConv) {
        // Quand la modal s'ouvre, s'assurer que le contenu est cliquable
        // Réinitialiser la modal de groupe quand elle s'ouvre
        var modalGroupe = document.getElementById('modalNouveauGroupe');
        if (modalGroupe) {
            modalGroupe.addEventListener('shown.bs.modal', function() {
                // Réinitialiser les participants
                participantsGroupe = [];
                afficherParticipantsSelectionnes();
                
                // Réinitialiser le nom
                var nomInput = document.getElementById('nom-groupe');
                if (nomInput) nomInput.value = '';
                
                // Réinitialiser les checkboxes
                document.querySelectorAll('.ami-item-groupe i[id^="check-"]').forEach(function(icon) {
                    icon.className = 'bi bi-circle';
                    icon.style.color = 'var(--text-3)';
                });
                
                // Réinitialiser la recherche
                var searchInput = document.getElementById('search-participants-groupe');
                if (searchInput) {
                    searchInput.value = '';
                    filtrerAmisGroupe('');
                }
            });
        }
        
        modalNouvelleConv.addEventListener('shown.bs.modal', function() {
            // Supprimer tous les backdrops Bootstrap
            var backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(function(backdrop) {
                backdrop.remove();
            });
            
            // Forcer les z-index
            modalNouvelleConv.style.zIndex = '1055';
            modalNouvelleConv.classList.add('show');
            
            var modalDialog = modalNouvelleConv.querySelector('.modal-dialog');
            if (modalDialog) {
                modalDialog.style.zIndex = '1056';
            }
            var modalContent = modalNouvelleConv.querySelector('.modal-content');
            if (modalContent) {
                modalContent.style.pointerEvents = 'auto';
                modalContent.style.zIndex = '1057';
            }
            var modalBody = modalNouvelleConv.querySelector('.modal-body');
            if (modalBody) {
                modalBody.style.pointerEvents = 'auto';
                modalBody.style.zIndex = '1058';
            }
            // S'assurer que tous les liens sont cliquables
            var links = modalNouvelleConv.querySelectorAll('a.ami-item');
            links.forEach(function(link) {
                link.style.pointerEvents = 'auto';
                link.style.zIndex = '1059';
                link.style.position = 'relative';
                link.style.cursor = 'pointer';
            });
        });
        
        // Nettoyer quand la modal se ferme
        modalNouvelleConv.addEventListener('hidden.bs.modal', function() {
            setTimeout(function() {
                var backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(function(backdrop) {
                    if (backdrop.parentNode) {
                        backdrop.remove();
                    }
                });
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }, 100);
        });
    }
    
    // Initialiser la modal de groupe
    var modalGroupe = document.getElementById('modalNouveauGroupe');
    if (modalGroupe) {
        modalGroupe.addEventListener('shown.bs.modal', function() {
            console.log('Modal groupe ouverte');
            
            // Réinitialiser les participants
            window.participantsGroupe = [];
            afficherParticipantsSelectionnes();
            
            // Réinitialiser le nom
            var nomInput = document.getElementById('nom-groupe');
            if (nomInput) nomInput.value = '';
            
            // Réinitialiser les checkboxes
            document.querySelectorAll('.participant-check').forEach(function(icon) {
                icon.className = 'bi bi-circle participant-check';
                icon.style.color = 'var(--text-3)';
            });
            
            // Réinitialiser la recherche
            var searchInput = document.getElementById('search-participants-groupe');
            if (searchInput) {
                searchInput.value = '';
                filtrerAmisGroupe('');
            }
            
            // Attacher les event listeners aux participants - méthode robuste
            setTimeout(function() {
                document.querySelectorAll('.participant-selectable').forEach(function(el) {
                    // Supprimer tous les anciens listeners en clonant l'élément
                    var parent = el.parentNode;
                    if (!parent) return;
                    
                    var newEl = el.cloneNode(true);
                    parent.replaceChild(newEl, el);
                    
                    // Ajouter le listener sur le nouvel élément
                    newEl.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        var id = parseInt(this.getAttribute('data-ami-id'));
                        var nom = this.getAttribute('data-ami-nom') || '';
                        var photo = this.getAttribute('data-ami-photo') || 'assets/images/default_avatar.svg';
                        
                        console.log('Clic détecté sur participant:', id, nom);
                        if (window.toggleParticipantGroupe) {
                            window.toggleParticipantGroupe(id, nom, photo);
                        } else {
                            console.error('toggleParticipantGroupe n\'est pas défini!');
                        }
                    });
                });
            }, 100);
        });
    }
});

function previewFile(input) {
    if (input.files && input.files[0]) {
        document.getElementById('file-preview-name').textContent = input.files[0].name;
        document.getElementById('file-preview-container').style.display = 'block';
    }
}

function clearFile() {
    let input = document.getElementById('input-fichier');
    input.value = '';
    document.getElementById('file-preview-container').style.display = 'none';
}

// Fonctions pour modifier le groupe
var groupePhotoFile = null;

function ouvrirModalModifierGroupe() {
    var modalEl = document.getElementById('modalModifierGroupe');
    if (!modalEl) {
        console.error('Modal modalModifierGroupe introuvable');
        return;
    }
    
    // Supprimer les backdrops existants
    var backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(function(backdrop) {
        backdrop.remove();
    });
    
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        var modal = new bootstrap.Modal(modalEl, {
            backdrop: false,
            keyboard: true
        });
        
        modal.show();
        
        // Forcer les z-index après l'ouverture
        setTimeout(function() {
            modalEl.style.zIndex = '1055';
            var dialog = modalEl.querySelector('.modal-dialog');
            if (dialog) dialog.style.zIndex = '1056';
            var content = modalEl.querySelector('.modal-content');
            if (content) content.style.zIndex = '1057';
            var body = modalEl.querySelector('.modal-body');
            if (body) body.style.zIndex = '1058';
            var footer = modalEl.querySelector('.modal-footer');
            if (footer) footer.style.zIndex = '1058';
        }, 50);
    } else {
        // Fallback
        $(modalEl).modal({
            backdrop: false,
            keyboard: true
        });
        $(modalEl).modal('show');
    }
}

function previewGroupePhoto(input) {
    if (input.files && input.files[0]) {
        groupePhotoFile = input.files[0];
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-groupe-photo').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function sauvegarderModificationsGroupe() {
    var nomGroupe = document.getElementById('nom-groupe-modifier').value.trim();
    var conversationId = <?= ($conversationActif && $conversationActif['est_groupe']) ? $convActiveId : 0 ?>;
    
    if (!conversationId) {
        alert(t('Erreur : ID de conversation manquant.'));
        return;
    }
    
    // Si une photo a été sélectionnée, l'uploader d'abord
    if (groupePhotoFile) {
        var formData = new FormData();
        formData.append('file', groupePhotoFile);
        formData.append('type', 'groupe');
        
        $.ajax({
            url: 'api/upload.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.succes && res.url) {
                    modifierGroupe(conversationId, nomGroupe, res.url);
                } else {
                    alert(res.message || t('Erreur lors de l\'upload de la photo.'));
                }
            },
            error: function() {
                alert(t('Erreur lors de l\'upload de la photo.'));
            }
        });
    } else {
        modifierGroupe(conversationId, nomGroupe, null);
    }
}

function modifierGroupe(conversationId, nom, photo) {
    var data = {
        action: 'modifier_groupe',
        conversation_id: conversationId,
        nom: nom
    };
    
    if (photo !== null) {
        data.photo = photo;
    }
    
    $.post('api/conversations.php', data, function(res) {
        if (res.succes) {
            // Fermer la modal
            var modalEl = document.getElementById('modalModifierGroupe');
            if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                var modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
            
            // Recharger la page pour voir les modifications
            window.location.reload();
        } else {
            alert(res.message || t('Erreur lors de la modification du groupe.'));
        }
    }).fail(function() {
        alert(t('Erreur lors de la modification du groupe.'));
    });
}

// Fonction pour lancer un appel de groupe
function lancerAppelGroupe(type) {
    var conversationId = <?= ($conversationActif && $conversationActif['est_groupe']) ? $convActiveId : 0 ?>;
    
    if (!conversationId) {
        alert(t('Erreur : ID de conversation manquant.'));
        return;
    }
    
    // Attendre que messagerie.js soit chargé
    if (typeof window.lancerAppelGroupeDirect !== 'undefined') {
        window.lancerAppelGroupeDirect(type, conversationId);
    } else {
        // Si la fonction n'est pas encore disponible, attendre un peu
        setTimeout(function() {
            if (typeof window.lancerAppelGroupeDirect !== 'undefined') {
                window.lancerAppelGroupeDirect(type, conversationId);
            } else {
                // Fallback : appeler directement l'API
                $.post('api/calls.php', {
                    action: 'initier',
                    conversation_id: conversationId,
                    est_groupe: '1',
                    type: type
                }, function(res) {
                    if (res.succes) {
                        alert(t('Appel de groupe lancé ! Les participants sont en train d\'être appelés.'));
                        // Recharger pour voir les appels
                        window.location.reload();
                    } else {
                        alert(res.message || t('Erreur lors du lancement de l\'appel de groupe.'));
                    }
                }).fail(function() {
                    alert(t('Erreur lors du lancement de l\'appel de groupe.'));
                });
            }
        }, 500);
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
