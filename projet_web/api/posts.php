<?php
/**
 * ECE In - API AJAX : Gestion des publications
 * 
 * Cette API gère tous les aspects des publications (posts) :
 * - Publication de posts (texte, photo, vidéo, CV, événement)
 * - Modification de posts
 * - Suppression de posts
 * - Réactions aux posts (like, etc.)
 * - Commentaires sur les posts
 * - Chargement des commentaires
 * 
 * Sécurité : Seuls les auteurs peuvent modifier/supprimer leurs posts
 */
require_once __DIR__ . '/../config/config.php';
requireConnexion(); // Vérifie que l'utilisateur est connecté

header('Content-Type: application/json'); // Réponse en JSON

$pdo    = getDB(); // Connexion à la base de données
$userId = $_SESSION['utilisateur_id']; // ID de l'utilisateur connecté
$action = $_POST['action'] ?? $_GET['action'] ?? ''; // Action à effectuer

// ===================== PUBLIER UN POST =====================
/**
 * Crée une nouvelle publication
 * 
 * Paramètres POST :
 * - type : Type de publication ('statut', 'photo', 'video', 'cv', 'evenement')
 * - contenu : Texte de la publication (obligatoire sauf si fichier)
 * - visibilite : Visibilité ('public', 'amis', 'prive')
 * - lieu : Lieu associé (optionnel)
 * - humeur : Humeur de l'utilisateur (optionnel)
 * - fichier : Fichier à uploader (image, vidéo, CV selon le type)
 * 
 * Processus :
 * 1. Validation du type de publication
 * 2. Validation du contenu (texte ou fichier requis)
 * 3. Upload du fichier si présent (vérification type/taille)
 * 4. Insertion dans la base de données
 * 5. Retour du post créé avec toutes ses infos
 */
if ($action === 'publier') {
    $type       = $_POST['type'] ?? 'statut';
    $contenu    = trim($_POST['contenu'] ?? '');
    $visibilite = $_POST['visibilite'] ?? 'public';
    $lieu       = trim($_POST['lieu'] ?? '');
    $humeur     = trim($_POST['humeur'] ?? '');
    $fichier    = null;

    // Validation du type de publication
    // Les types valides sont : statut (texte), photo, video, cv, evenement
    $typesValides = ['statut', 'photo', 'video', 'cv', 'evenement'];
    if (!in_array($type, $typesValides)) {
        echo json_encode(['succes' => false, 'message' => 'Type de publication invalide.']);
        exit();
    }

    // Validation : le post doit avoir du contenu texte OU un fichier
    if (empty($contenu) && !isset($_FILES['fichier'])) {
        echo json_encode(['succes' => false, 'message' => 'Le contenu ne peut pas être vide.']);
        exit();
    }

    // GESTION DE L'UPLOAD DE FICHIER
    // Si un fichier est fourni, on le valide et on le sauvegarde dans le dossier approprié
    if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === 0) {
        $file = $_FILES['fichier'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Déterminer le dossier de destination selon le type MIME et la taille du fichier
        // Chaque type de fichier a ses propres limites de taille (définies dans config.php)
        if (in_array($file['type'], PHOTO_TYPES) && $file['size'] <= MAX_PHOTO_SIZE) {
            $dossier = 'photos'; // Images pour les posts photo
        } elseif (in_array($file['type'], VIDEO_TYPES) && $file['size'] <= MAX_VIDEO_SIZE) {
            $dossier = 'videos'; // Vidéos pour les posts vidéo
        } elseif (in_array($file['type'], CV_TYPES) && $file['size'] <= MAX_CV_SIZE) {
            $dossier = 'cvs'; // Documents PDF pour les CV
        } else {
            echo json_encode(['succes' => false, 'message' => 'Type ou taille de fichier non autorisé.']);
            exit();
        }

        // Générer un nom de fichier unique pour éviter les conflits
        $nomFichier = uniqid('file_', true) . '.' . $ext;
        $destination = UPLOAD_PATH . $dossier . '/' . $nomFichier;

        // Déplacer le fichier uploadé vers le dossier de destination
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $fichier = 'uploads/' . $dossier . '/' . $nomFichier; // Chemin relatif pour la base de données
        }
    }

    // INSERTION DE LA PUBLICATION DANS LA BASE DE DONNÉES
    $stmt = $pdo->prepare("
        INSERT INTO publications (utilisateur_id, type, contenu, fichier, lieu, humeur, visibilite)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $type, $contenu, $fichier, $lieu, $humeur, $visibilite]);
    $postId = $pdo->lastInsertId();

    // Récupérer le post créé avec les infos utilisateur
    $stmtGet = $pdo->prepare("
        SELECT p.*, u.nom, u.prenom, u.photo AS avatar, u.pseudo, u.titre AS user_titre
        FROM publications p
        JOIN utilisateurs u ON u.id = p.utilisateur_id
        WHERE p.id = ?
    ");
    $stmtGet->execute([$postId]);
    $post = $stmtGet->fetch();

    echo json_encode([
        'succes'  => true,
        'post_id' => $postId,
        'post'    => $post,
        'message' => 'Publication créée avec succès !',
    ]);
    exit();
}

// ===================== MODIFIER UN POST =====================
/**
 * Modifie une publication existante
 * 
 * Paramètres POST :
 * - post_id : ID du post à modifier
 * - contenu : Nouveau contenu texte
 * - visibilite : Nouvelle visibilité
 * - lieu : Nouveau lieu
 * - humeur : Nouvelle humeur
 * 
 * Sécurité : Seul l'auteur du post peut le modifier
 * 
 * Processus :
 * 1. Vérifier que le post existe et appartient à l'utilisateur
 * 2. Mettre à jour les champs modifiés
 * 3. Retourner le post mis à jour
 */
if ($action === 'modifier') {
    $postId = (int) ($_POST['post_id'] ?? 0);
    $contenu = trim($_POST['contenu'] ?? '');
    $visibilite = $_POST['visibilite'] ?? 'public';
    $lieu = trim($_POST['lieu'] ?? '');
    $humeur = trim($_POST['humeur'] ?? '');

    // SÉCURITÉ : Vérifier que le post existe et appartient à l'utilisateur
    // Seul l'auteur peut modifier son propre post
    $stmt = $pdo->prepare("SELECT utilisateur_id, fichier FROM publications WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();

    if (!$post) {
        echo json_encode(['succes' => false, 'message' => 'Publication introuvable.']);
        exit();
    }

    // Vérifier propriétaire ou admin
    if ($post['utilisateur_id'] != $userId && !estAdmin()) {
        echo json_encode(['succes' => false, 'message' => 'Accès refusé.']);
        exit();
    }

    if (empty($contenu) && !$post['fichier']) {
        echo json_encode(['succes' => false, 'message' => 'Le contenu ne peut pas être vide.']);
        exit();
    }

    // Gérer l'upload d'un nouveau fichier (optionnel)
    $fichier = $post['fichier']; // Garder l'ancien fichier par défaut
    if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === 0) {
        $file = $_FILES['fichier'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Déterminer le dossier selon le type
        if (in_array($file['type'], PHOTO_TYPES) && $file['size'] <= MAX_PHOTO_SIZE) {
            $dossier = 'photos';
        } elseif (in_array($file['type'], VIDEO_TYPES) && $file['size'] <= MAX_VIDEO_SIZE) {
            $dossier = 'videos';
        } elseif (in_array($file['type'], CV_TYPES) && $file['size'] <= MAX_CV_SIZE) {
            $dossier = 'cvs';
        } else {
            echo json_encode(['succes' => false, 'message' => 'Type ou taille de fichier non autorisé.']);
            exit();
        }

        // Supprimer l'ancien fichier si existe
        if ($post['fichier'] && file_exists(BASE_PATH . '/' . $post['fichier'])) {
            unlink(BASE_PATH . '/' . $post['fichier']);
        }

        $nomFichier = uniqid('file_', true) . '.' . $ext;
        $destination = UPLOAD_PATH . $dossier . '/' . $nomFichier;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $fichier = 'uploads/' . $dossier . '/' . $nomFichier;
        }
    }

    // Mettre à jour la publication
    $stmt = $pdo->prepare("
        UPDATE publications 
        SET contenu = ?, visibilite = ?, lieu = ?, humeur = ?, fichier = ?, date_modification = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$contenu, $visibilite, $lieu, $humeur, $fichier, $postId]);

    // Récupérer le post modifié avec les infos utilisateur
    $stmtGet = $pdo->prepare("
        SELECT p.*, u.nom, u.prenom, u.photo AS avatar, u.pseudo, u.titre AS user_titre
        FROM publications p
        JOIN utilisateurs u ON u.id = p.utilisateur_id
        WHERE p.id = ?
    ");
    $stmtGet->execute([$postId]);
    $postModifie = $stmtGet->fetch();

    echo json_encode([
        'succes'  => true,
        'post'    => $postModifie,
        'message' => 'Publication modifiée avec succès !',
    ]);
    exit();
}

// ===================== SUPPRIMER UN POST =====================
if ($action === 'supprimer') {
    $postId = (int) ($_POST['post_id'] ?? 0);

    $stmt = $pdo->prepare("SELECT utilisateur_id, fichier FROM publications WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();

    if (!$post) {
        echo json_encode(['succes' => false, 'message' => 'Publication introuvable.']);
        exit();
    }

    // Vérifier propriétaire ou admin
    if ($post['utilisateur_id'] != $userId && !estAdmin()) {
        echo json_encode(['succes' => false, 'message' => 'Accès refusé.']);
        exit();
    }

    // Supprimer le fichier associé
    if ($post['fichier'] && file_exists(BASE_PATH . '/' . $post['fichier'])) {
        unlink(BASE_PATH . '/' . $post['fichier']);
    }

    $pdo->prepare("DELETE FROM publications WHERE id = ?")->execute([$postId]);

    echo json_encode(['succes' => true, 'message' => 'Publication supprimée.']);
    exit();
}

// ===================== RÉAGIR =====================
if ($action === 'reagir') {
    $postId     = (int) ($_POST['post_id'] ?? 0);
    $typeReaction = $_POST['type'] ?? 'jaime';

    $typesReactions = ['jaime', 'bravo', 'soutien', 'interessant'];
    if (!in_array($typeReaction, $typesReactions)) {
        $typeReaction = 'jaime';
    }

    // Vérifier si déjà réagi
    $stmtCheck = $pdo->prepare("SELECT id, type FROM reactions WHERE publication_id = ? AND utilisateur_id = ?");
    $stmtCheck->execute([$postId, $userId]);
    $existante = $stmtCheck->fetch();

    if ($existante) {
        if ($existante['type'] === $typeReaction) {
            // Supprimer la réaction (toggle off)
            $pdo->prepare("DELETE FROM reactions WHERE id = ?")->execute([$existante['id']]);
            $etat = 'retire';
        } else {
            // Changer la réaction
            $pdo->prepare("UPDATE reactions SET type = ? WHERE id = ?")
                ->execute([$typeReaction, $existante['id']]);
            $etat = 'modifie';
        }
    } else {
        // Nouvelle réaction
        $pdo->prepare("INSERT INTO reactions (publication_id, utilisateur_id, type) VALUES (?, ?, ?)")
            ->execute([$postId, $userId, $typeReaction]);
        $etat = 'ajoute';

        // Notifier le propriétaire (sauf si c'est soi-même)
        $stmtOwner = $pdo->prepare("SELECT utilisateur_id FROM publications WHERE id = ?");
        $stmtOwner->execute([$postId]);
        $ownerId = $stmtOwner->fetchColumn();
        if ($ownerId && $ownerId != $userId) {
            $user = getUtilisateurConnecte();
            creerNotification(
                $ownerId, $userId, 'reaction',
                $user['prenom'] . ' ' . $user['nom'] . ' a réagi à votre publication.',
                'index.php#post-' . $postId
            );
        }
    }

    // Compter les réactions
    $nbReactions = $pdo->prepare("SELECT COUNT(*) FROM reactions WHERE publication_id = ?");
    $nbReactions->execute([$postId]);
    $count = $nbReactions->fetchColumn();

    echo json_encode([
        'succes'      => true,
        'etat'        => $etat,
        'nb_reactions' => $count,
        'message'     => 'Réaction enregistrée.',
    ]);
    exit();
}

// ===================== COMMENTER =====================
if ($action === 'commenter') {
    $postId  = (int) ($_POST['post_id'] ?? 0);
    $contenu = trim($_POST['contenu'] ?? '');

    if (empty($contenu)) {
        echo json_encode(['succes' => false, 'message' => 'Le commentaire ne peut pas être vide.']);
        exit();
    }

    $pdo->prepare("INSERT INTO commentaires (publication_id, utilisateur_id, contenu) VALUES (?, ?, ?)")
        ->execute([$postId, $userId, $contenu]);
    $commId = $pdo->lastInsertId();

    // Notifier le propriétaire
    $stmtOwner = $pdo->prepare("SELECT utilisateur_id FROM publications WHERE id = ?");
    $stmtOwner->execute([$postId]);
    $ownerId = $stmtOwner->fetchColumn();
    if ($ownerId && $ownerId != $userId) {
        $user = getUtilisateurConnecte();
        creerNotification(
            $ownerId, $userId, 'commentaire',
            $user['prenom'] . ' ' . $user['nom'] . ' a commenté votre publication.',
            'index.php#post-' . $postId
        );
    }

    // Retourner le commentaire
    $user = getUtilisateurConnecte();
    echo json_encode([
        'succes' => true,
        'commentaire' => [
            'id'               => $commId,
            'contenu'          => $contenu,
            'prenom'           => $user['prenom'],
            'nom'              => $user['nom'],
            'avatar'           => $user['photo'],
            'date_commentaire' => date('Y-m-d H:i:s'),
            'utilisateur_id'   => $userId,
            'peut_modifier'    => true, // Nouveau commentaire, peut être modifié
        ],
    ]);
    exit();
}

// ===================== CHARGER COMMENTAIRES =====================
if ($action === 'charger_commentaires') {
    $postId = (int) ($_GET['post_id'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT c.*, u.nom, u.prenom, u.photo AS avatar,
               (TIMESTAMPDIFF(SECOND, c.date_commentaire, NOW()) <= 30 AND c.utilisateur_id = ?) AS peut_modifier
        FROM commentaires c
        JOIN utilisateurs u ON u.id = c.utilisateur_id
        WHERE c.publication_id = ?
        ORDER BY c.date_commentaire ASC
    ");
    $stmt->execute([$userId, $postId]);
    $commentaires = $stmt->fetchAll();

    echo json_encode(['succes' => true, 'commentaires' => $commentaires]);
    exit();
}

// ===================== MODIFIER UN COMMENTAIRE =====================
/**
 * Modifie un commentaire existant
 * 
 * Paramètres POST :
 * - commentaire_id : ID du commentaire à modifier
 * - contenu : Nouveau contenu du commentaire
 * 
 * Sécurité :
 * - Seul l'auteur du commentaire peut le modifier
 * - Le commentaire ne peut être modifié que dans les 30 secondes suivant sa publication
 * 
 * Processus :
 * 1. Vérifier que le commentaire existe et appartient à l'utilisateur
 * 2. Vérifier que le délai de 30 secondes n'est pas dépassé
 * 3. Mettre à jour le contenu du commentaire
 * 4. Retourner le commentaire modifié
 */
if ($action === 'modifier_commentaire') {
    $commentaireId = (int) ($_POST['commentaire_id'] ?? 0);
    $contenu = trim($_POST['contenu'] ?? '');

    if (empty($contenu)) {
        echo json_encode(['succes' => false, 'message' => 'Le commentaire ne peut pas être vide.']);
        exit();
    }

    if (!$commentaireId) {
        echo json_encode(['succes' => false, 'message' => 'Commentaire introuvable.']);
        exit();
    }

    // Vérifier que le commentaire existe et appartient à l'utilisateur
    $stmt = $pdo->prepare("
        SELECT id, utilisateur_id, date_commentaire, 
               TIMESTAMPDIFF(SECOND, date_commentaire, NOW()) AS secondes_ecoulees
        FROM commentaires 
        WHERE id = ?
    ");
    $stmt->execute([$commentaireId]);
    $commentaire = $stmt->fetch();

    if (!$commentaire) {
        echo json_encode(['succes' => false, 'message' => 'Commentaire introuvable.']);
        exit();
    }

    // Vérifier que le commentaire appartient à l'utilisateur
    if ($commentaire['utilisateur_id'] != $userId) {
        echo json_encode(['succes' => false, 'message' => 'Vous n\'êtes pas autorisé à modifier ce commentaire.']);
        exit();
    }

    // Vérifier que le délai de 30 secondes n'est pas dépassé
    if ($commentaire['secondes_ecoulees'] > 30) {
        echo json_encode(['succes' => false, 'message' => 'Le délai de modification (30 secondes) est dépassé.']);
        exit();
    }

    // Mettre à jour le commentaire
    $stmt = $pdo->prepare("UPDATE commentaires SET contenu = ? WHERE id = ?");
    $stmt->execute([$contenu, $commentaireId]);

    // Récupérer le commentaire modifié avec les infos utilisateur
    $stmtGet = $pdo->prepare("
        SELECT c.*, u.nom, u.prenom, u.photo AS avatar
        FROM commentaires c
        JOIN utilisateurs u ON u.id = c.utilisateur_id
        WHERE c.id = ?
    ");
    $stmtGet->execute([$commentaireId]);
    $commentaireModifie = $stmtGet->fetch();

    echo json_encode([
        'succes' => true,
        'commentaire' => $commentaireModifie,
        'message' => 'Commentaire modifié avec succès.',
    ]);
    exit();
}

// ===================== PARTAGER =====================
if ($action === 'partager') {
    $postId  = (int) ($_POST['post_id'] ?? 0);
    $contenu = trim($_POST['contenu'] ?? '');

    $stmt = $pdo->prepare("
        INSERT INTO publications (utilisateur_id, type, contenu, visibilite, publication_originale_id)
        VALUES (?, 'partage', ?, 'public', ?)
    ");
    $stmt->execute([$userId, $contenu, $postId]);

    // Notifier le propriétaire original
    $stmtOwner = $pdo->prepare("SELECT utilisateur_id FROM publications WHERE id = ?");
    $stmtOwner->execute([$postId]);
    $ownerId = $stmtOwner->fetchColumn();
    if ($ownerId && $ownerId != $userId) {
        $user = getUtilisateurConnecte();
        creerNotification(
            $ownerId, $userId, 'partage',
            $user['prenom'] . ' ' . $user['nom'] . ' a partagé votre publication.',
            'index.php'
        );
    }

    echo json_encode(['succes' => true, 'message' => 'Publication partagée !']);
    exit();
}

echo json_encode(['succes' => false, 'message' => 'Action non reconnue.']);
