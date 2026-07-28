<?php
/**
 * ECE In - Page de Candidature
 * 
 * Permet aux utilisateurs de candidater à une offre d'emploi ou de bénévolat
 */
require_once __DIR__ . '/config/config.php';
requireConnexion();

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$userCourant = getUtilisateurConnecte();
$emploiId = (int) ($_GET['id'] ?? 0);
$pageTitle = 'Candidater';

if (!$emploiId) {
    redirect('emplois.php');
}

// Récupérer l'offre d'emploi
$stmt = $pdo->prepare("
    SELECT e.*, u.nom, u.prenom, u.photo 
    FROM emplois e 
    JOIN utilisateurs u ON u.id = e.publie_par 
    WHERE e.id = ? AND e.actif = 1
");
$stmt->execute([$emploiId]);
$emploi = $stmt->fetch();

if (!$emploi) {
    redirect('emplois.php');
}

// Vérifier si l'utilisateur a déjà candidaté
$stmtCandidature = $pdo->prepare("
    SELECT id FROM candidatures 
    WHERE utilisateur_id = ? AND emploi_id = ?
");
$stmtCandidature->execute([$userId, $emploiId]);
$candidatureExistante = $stmtCandidature->fetch();

// Traitement du formulaire
$succes = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'soumettre_candidature') {
    $message = trim($_POST['message'] ?? '');
    $cv = $_FILES['cv'] ?? null;
    
    if (empty($message)) {
        $erreur = 'Veuillez rédiger un message de motivation.';
    } elseif ($candidatureExistante) {
        $erreur = 'Vous avez déjà candidaté à cette offre.';
    } else {
        // Gérer l'upload du CV si fourni
        $cvPath = null;
        if ($cv && $cv['error'] === 0) {
            $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $maxSize = 5 * 1024 * 1024; // 5 MB
            
            if (in_array($cv['type'], $allowedTypes) && $cv['size'] <= $maxSize) {
                $ext = pathinfo($cv['name'], PATHINFO_EXTENSION);
                $nom = 'cv_' . $userId . '_' . time() . '.' . $ext;
                $dest = UPLOAD_PATH . 'cvs/' . $nom;
                
                if (!is_dir(UPLOAD_PATH . 'cvs/')) {
                    mkdir(UPLOAD_PATH . 'cvs/', 0755, true);
                }
                
                if (move_uploaded_file($cv['tmp_name'], $dest)) {
                    $cvPath = 'uploads/cvs/' . $nom;
                }
            }
        }
        
        // Insérer la candidature
        try {
            $pdo->prepare("
                INSERT INTO candidatures (utilisateur_id, emploi_id, message, cv_fichier, date_candidature)
                VALUES (?, ?, ?, ?, NOW())
            ")->execute([$userId, $emploiId, $message, $cvPath]);
            
            $succes = 'Votre candidature a été envoyée avec succès !';
            
            // Redirection après 2 secondes
            header("refresh:2;url=emplois.php");
        } catch (PDOException $e) {
            $erreur = 'Erreur lors de l\'envoi de la candidature. Veuillez réessayer.';
        }
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- En-tête -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-1">
                        <i class="bi bi-send me-2" style="color:var(--accent)"></i>Candidater
                    </h5>
                    <p class="mb-0 small" style="color:var(--text-3)">
                        Postuler à cette offre
                    </p>
                </div>
                <a href="emplois.php" class="btn btn-sm btn-light">
                    <i class="bi bi-arrow-left me-1"></i>Retour
                </a>
            </div>

            <!-- Messages d'erreur/succès -->
            <?php if ($erreur): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i><?= h($erreur) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($succes): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?= h($succes) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($candidatureExistante && !$succes): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle me-2"></i>Vous avez déjà candidaté à cette offre.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Détails de l'offre -->
            <div class="glass p-4 mb-4">
                <div class="d-flex gap-3 align-items-start mb-3">
                    <div class="flex-shrink-0 rounded-3 d-flex align-items-center justify-content-center" 
                         style="width:60px;height:60px;background:var(--accent-light)">
                        <i class="bi bi-briefcase" style="color:var(--accent);font-size:1.5rem"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="fw-bold mb-1"><?= h($emploi['titre']) ?></h6>
                        <div class="fw-semibold mb-2" style="color:var(--accent)">
                            <?= h($emploi['entreprise']) ?>
                        </div>
                        <div class="d-flex flex-wrap gap-3 small" style="color:var(--text-3)">
                            <?php if ($emploi['lieu']): ?>
                            <span><i class="bi bi-geo-alt me-1"></i><?= h($emploi['lieu']) ?></span>
                            <?php endif; ?>
                            <?php
                            $badgeColors = [
                                'stage' => 'bg-info', 
                                'alternance' => 'bg-warning', 
                                'cdi' => 'bg-success', 
                                'cdd' => 'bg-secondary', 
                                'freelance' => 'bg-purple', 
                                'benevole' => 'bg-danger'
                            ];
                            $badgeClass = $badgeColors[$emploi['type']] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?= $badgeClass ?>" style="font-size:.7rem">
                                <?= h(ucfirst($emploi['type'])) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulaire de candidature -->
            <?php if (!$candidatureExistante || $succes): ?>
            <div class="glass p-4">
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-file-earmark-text me-2" style="color:var(--accent)"></i>
                    Formulaire de candidature
                </h6>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="soumettre_candidature">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Votre profil
                        </label>
                        <div class="d-flex gap-3 align-items-center p-3 rounded-3" style="background:var(--bg-3)">
                            <img src="<?= h($userCourant['photo']) ?>" alt="" 
                                 class="rounded-3" width="50" height="50" style="object-fit:cover">
                            <div>
                                <div class="fw-semibold">
                                    <?= h($userCourant['prenom'] . ' ' . $userCourant['nom']) ?>
                                </div>
                                <div class="small" style="color:var(--text-3)">
                                    <?= h($userCourant['email']) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Message de motivation *
                        </label>
                        <textarea name="message" class="form-control" rows="8" required 
                                  placeholder="Rédigez votre message de motivation. Présentez-vous, expliquez pourquoi vous êtes intéressé(e) par cette offre et ce que vous pouvez apporter..."><?= h($_POST['message'] ?? '') ?></textarea>
                        <div class="form-text">
                            Minimum 100 caractères recommandé
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            CV / Lettre de motivation (optionnel)
                        </label>
                        <input type="file" name="cv" class="form-control" 
                               accept=".pdf,.doc,.docx">
                        <div class="form-text">
                            Formats acceptés : PDF, DOC, DOCX (max 5 MB)
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-ecein-primary">
                            <i class="bi bi-send me-2"></i>Envoyer ma candidature
                        </button>
                        <a href="emplois.php" class="btn btn-light">
                            Annuler
                        </a>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
