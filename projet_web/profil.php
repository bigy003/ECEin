<?php
/**
 * ECE In - Page Profil (Version Alt - Hero Overlay)
 */
require_once __DIR__ . '/config/config.php';
requireConnexion();

$pdo         = getDB();
$userId      = $_SESSION['utilisateur_id'];
$userCourant = getUtilisateurConnecte();
$onglet      = $_GET['onglet'] ?? 'profil';
$pageTitle   = t('Mon Profil');

$stmtForm = $pdo->prepare("SELECT * FROM formations WHERE utilisateur_id = ? ORDER BY date_debut DESC");
$stmtForm->execute([$userId]);
$formations = $stmtForm->fetchAll();

$stmtProj = $pdo->prepare("SELECT * FROM projets WHERE utilisateur_id = ? ORDER BY date_debut DESC");
$stmtProj->execute([$userId]);
$projets = $stmtProj->fetchAll();

$stmtPosts = $pdo->prepare("
    SELECT p.*,
        u.nom, u.prenom, u.photo AS avatar, u.pseudo, u.titre AS user_titre,
        (SELECT COUNT(*) FROM reactions r WHERE r.publication_id = p.id) AS nb_reactions,
        (SELECT COUNT(*) FROM commentaires c WHERE c.publication_id = p.id) AS nb_commentaires,
        (SELECT type FROM reactions r2 WHERE r2.publication_id = p.id AND r2.utilisateur_id = ?) AS ma_reaction
    FROM publications p
    JOIN utilisateurs u ON u.id = p.utilisateur_id
    WHERE p.utilisateur_id = ?
    ORDER BY p.date_publication DESC
");
$stmtPosts->execute([$userId, $userId]);
$publications = $stmtPosts->fetchAll();

$stmtAlbums = $pdo->prepare("
    SELECT a.*,
        (SELECT COUNT(*) FROM album_photos ap WHERE ap.album_id = a.id) AS nb_photos
    FROM albums a
    WHERE a.utilisateur_id = ?
    ORDER BY a.date_creation DESC
");
$stmtAlbums->execute([$userId]);
$albums = $stmtAlbums->fetchAll();

// ===== Traitement POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profil') {
        $champs = ['titre', 'biographie', 'localisation', 'site_web', 'telephone'];
        $values = [];
        $params = [];
        foreach ($champs as $champ) {
            if (isset($_POST[$champ])) {
                $values[] = "$champ = ?";
                $params[] = trim($_POST[$champ]);
            }
        }
        if (!empty($values)) {
            $params[] = $userId;
            $pdo->prepare("UPDATE utilisateurs SET " . implode(', ', $values) . " WHERE id = ?")
                ->execute($params);
        }
        header('Location: profil.php');
        exit();
    }

    if ($action === 'upload_avatar' && isset($_FILES['avatar'])) {
        $file = $_FILES['avatar'];
        if ($file['error'] === 0 && in_array($file['type'], PHOTO_TYPES) && $file['size'] <= MAX_PHOTO_SIZE) {
            $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
            $nom  = 'avatar_' . $userId . '_' . time() . '.' . $ext;
            $dest = UPLOAD_PATH . 'avatars/' . $nom;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $chemin = 'uploads/avatars/' . $nom;
                $pdo->prepare("UPDATE utilisateurs SET photo = ? WHERE id = ?")
                    ->execute([$chemin, $userId]);
            }
        }
        header('Location: profil.php');
        exit();
    }

    if ($action === 'ajouter_formation') {
        $pdo->prepare("
            INSERT INTO formations (utilisateur_id, etablissement, diplome, domaine, date_debut, date_fin, en_cours, description, lieu)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $userId,
            trim($_POST['etablissement'] ?? ''),
            trim($_POST['diplome'] ?? ''),
            trim($_POST['domaine'] ?? ''),
            $_POST['date_debut'] ?? null,
            !empty($_POST['date_fin']) ? $_POST['date_fin'] : null,
            isset($_POST['en_cours']) ? 1 : 0,
            trim($_POST['description'] ?? ''),
            trim($_POST['lieu'] ?? ''),
        ]);
        header('Location: profil.php?onglet=formations');
        exit();
    }

    if ($action === 'ajouter_projet') {
        $pdo->prepare("
            INSERT INTO projets (utilisateur_id, titre, description, date_debut, date_fin, en_cours, lien, type)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $userId,
            trim($_POST['titre'] ?? ''),
            trim($_POST['description'] ?? ''),
            !empty($_POST['date_debut']) ? $_POST['date_debut'] : null,
            !empty($_POST['date_fin']) ? $_POST['date_fin'] : null,
            isset($_POST['en_cours']) ? 1 : 0,
            trim($_POST['lien'] ?? ''),
            trim($_POST['type_projet'] ?? ''),
        ]);
        header('Location: profil.php?onglet=projets');
        exit();
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
$userCourant = getUtilisateurConnecte();
?>

<!-- ===== HERO BANNER ===== -->
<div class="profil-banner mb-4">
    <div class="profil-bg-large"
         style="background-image:url('<?= h($userCourant['image_fond']) ?>')"
         id="bg-image">
        <label class="btn btn-sm" style="cursor:pointer;background:rgba(0,0,0,.5);color:#fff;border-radius:var(--radius-sm)"
               title="<?= t('Changer la photo de fond') ?>">
            <i class="bi bi-camera me-1"></i><?= t('Fond') ?>
            <form id="form-bg" method="POST" action="api/upload.php" enctype="multipart/form-data" class="d-none">
                <input type="hidden" name="action" value="upload_bg">
                <input type="file" name="background" accept="image/*" onchange="this.form.submit()">
            </form>
        </label>
    </div>
    <div class="profil-banner-overlay"></div>
    <div class="profil-banner-info">
        <div class="position-relative">
            <img src="<?= h($userCourant['photo']) ?>" alt="Avatar" class="profil-avatar" id="avatar-img">
            <label class="btn btn-sm position-absolute bottom-0 end-0"
                   style="width:28px;height:28px;padding:0;cursor:pointer;background:var(--accent);color:#000;border-radius:8px;display:flex;align-items:center;justify-content:center"
                   title="<?= t('Modifier la photo') ?>">
                <i class="bi bi-camera-fill" style="font-size:.7rem"></i>
                <form method="POST" enctype="multipart/form-data" class="d-none">
                    <input type="hidden" name="action" value="upload_avatar">
                    <input type="file" name="avatar" accept="image/*" onchange="this.form.submit()">
                </form>
            </label>
        </div>
        <div>
            <h4 class="fw-bold mb-0 text-white"><?= h($userCourant['prenom'] . ' ' . $userCourant['nom']) ?></h4>
            <p class="mb-0" style="color:rgba(255,255,255,.7);font-size:.85rem"><?= h($userCourant['titre'] ?? t('Membre ECE In')) ?></p>
        </div>
    </div>
</div>

<!-- Action buttons -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <button class="btn btn-ecein-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditProfil">
        <i class="bi bi-pencil me-1"></i><?= t('Modifier le profil') ?>
    </button>
    <a href="profil.php?onglet=cv" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-file-earmark-person me-1"></i><?= t('Mon CV') ?>
    </a>
</div>

<!-- ===== TAB NAV (pills instead of tabs) ===== -->
<ul class="nav nav-pills gap-1 mb-4 flex-nowrap overflow-auto pb-1">
    <li class="nav-item"><a class="nav-link <?= $onglet === 'profil' ? 'active' : '' ?>" href="?onglet=profil"><i class="bi bi-person me-1"></i><?= t('Profil') ?></a></li>
    <li class="nav-item"><a class="nav-link <?= $onglet === 'publications' ? 'active' : '' ?>" href="?onglet=publications"><i class="bi bi-grid me-1"></i>Posts</a></li>
    <li class="nav-item"><a class="nav-link <?= $onglet === 'formations' ? 'active' : '' ?>" href="?onglet=formations"><i class="bi bi-mortarboard me-1"></i><?= t('Formations') ?></a></li>
    <li class="nav-item"><a class="nav-link <?= $onglet === 'projets' ? 'active' : '' ?>" href="?onglet=projets"><i class="bi bi-kanban me-1"></i><?= t('Projets') ?></a></li>
    <li class="nav-item"><a class="nav-link <?= $onglet === 'albums' ? 'active' : '' ?>" href="?onglet=albums"><i class="bi bi-images me-1"></i>Albums</a></li>
    <li class="nav-item"><a class="nav-link <?= $onglet === 'cv' ? 'active' : '' ?>" href="?onglet=cv"><i class="bi bi-file-earmark-pdf me-1"></i>CV</a></li>
    <li class="nav-item"><a class="nav-link <?= $onglet === 'parametres' ? 'active' : '' ?>" href="?onglet=parametres"><i class="bi bi-gear me-1"></i><?= t('Paramètres') ?></a></li>
</ul>

<!-- ===== CONTENU ONGLETS ===== -->

<?php if ($onglet === 'profil'): ?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="glass p-4 mb-3">
            <h6 class="fw-bold mb-3" style="font-size:.85rem"><?= t('Informations') ?></h6>
            <?php if ($userCourant['biographie']): ?>
            <p class="small" style="color:var(--text-2)"><?= nl2br(h($userCourant['biographie'])) ?></p>
            <hr style="border-color:var(--glass-border)">
            <?php endif; ?>
            <ul class="list-unstyled small mb-0" style="color:var(--text-2)">
                <?php if ($userCourant['localisation']): ?>
                <li class="mb-2"><i class="bi bi-geo-alt me-2" style="color:var(--accent)"></i><?= h($userCourant['localisation']) ?></li>
                <?php endif; ?>
                <?php if ($userCourant['site_web']): ?>
                <li class="mb-2"><i class="bi bi-link-45deg me-2" style="color:var(--accent)"></i>
                    <a href="<?= h($userCourant['site_web']) ?>" target="_blank"><?= h($userCourant['site_web']) ?></a>
                </li>
                <?php endif; ?>
                <?php if ($userCourant['telephone']): ?>
                <li class="mb-2"><i class="bi bi-telephone me-2" style="color:var(--accent)"></i><?= h($userCourant['telephone']) ?></li>
                <?php endif; ?>
                <li class="mb-2"><i class="bi bi-envelope me-2" style="color:var(--accent)"></i><?= h($userCourant['email']) ?></li>
                <li><i class="bi bi-calendar me-2" style="color:var(--accent)"></i><?= t('Membre depuis') ?> <?= formatDateFr($userCourant['date_inscription']) ?></li>
            </ul>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="glass p-4 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0" style="font-size:.85rem"><i class="bi bi-mortarboard me-2" style="color:var(--accent)"></i><?= t('Formations') ?></h6>
                <a href="?onglet=formations" class="btn btn-sm btn-light"><?= t('Tout voir') ?></a>
            </div>
            <?php if (empty($formations)): ?>
            <p class="small" style="color:var(--text-3)"><?= t('Aucune formation renseignée.') ?></p>
            <?php else: ?>
            <?php foreach (array_slice($formations, 0, 2) as $f): ?>
            <div class="d-flex gap-3 mb-3">
                <div style="width:40px;height:40px;border-radius:10px;background:var(--accent-light);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi bi-building" style="color:var(--accent)"></i>
                </div>
                <div>
                    <div class="fw-semibold small"><?= h($f['diplome']) ?></div>
                    <div style="font-size:.78rem;color:var(--text-3)"><?= h($f['etablissement']) ?></div>
                    <div style="font-size:.72rem;color:var(--text-3)">
                        <?= formatDateFr($f['date_debut']) ?> –
                        <?= $f['en_cours'] ? t('Présent') : ($f['date_fin'] ? formatDateFr($f['date_fin']) : '') ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="glass p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0" style="font-size:.85rem"><i class="bi bi-kanban me-2" style="color:var(--teal)"></i><?= t('Projets') ?></h6>
                <a href="?onglet=projets" class="btn btn-sm btn-light"><?= t('Tout voir') ?></a>
            </div>
            <?php if (empty($projets)): ?>
            <p class="small" style="color:var(--text-3)"><?= t('Aucun projet renseigné.') ?></p>
            <?php else: ?>
            <?php foreach (array_slice($projets, 0, 2) as $p): ?>
            <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="fw-semibold small"><?= h($p['titre']) ?></div>
                    <?php if ($p['en_cours']): ?><span class="badge bg-success" style="font-size:.6rem"><?= t('En cours') ?></span><?php endif; ?>
                </div>
                <p class="small mb-0" style="color:var(--text-3)"><?= h(substr($p['description'] ?? '', 0, 150)) ?></p>
                <?php if ($p['lien']): ?>
                <a href="<?= h($p['lien']) ?>" target="_blank" class="small"><i class="bi bi-box-arrow-up-right me-1"></i><?= t('Voir') ?></a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php elseif ($onglet === 'formations'): ?>
<div class="glass p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-mortarboard me-2" style="color:var(--accent)"></i><?= t('Mes Formations') ?></h6>
        <button class="btn btn-sm btn-ecein-primary" data-bs-toggle="modal" data-bs-target="#modalFormation">
            <i class="bi bi-plus-lg me-1"></i><?= t('Ajouter') ?>
        </button>
    </div>
    <?php if (empty($formations)): ?>
    <div class="empty-state">
        <i class="bi bi-mortarboard"></i>
        <p><?= t('Aucune formation renseignée. Ajoutez votre parcours académique !') ?></p>
    </div>
    <?php else: ?>
    <div class="timeline">
        <?php foreach ($formations as $f): ?>
        <div class="timeline-item">
            <div class="timeline-marker" style="background:var(--accent)"></div>
            <div class="timeline-content">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-0 small"><?= h($f['diplome']) ?></h6>
                        <div class="fw-semibold" style="font-size:.78rem;color:var(--accent)"><?= h($f['etablissement']) ?></div>
                        <?php if ($f['domaine']): ?>
                        <div style="font-size:.75rem;color:var(--text-3)"><?= h($f['domaine']) ?></div>
                        <?php endif; ?>
                        <div style="font-size:.72rem;color:var(--text-3)">
                            <?= formatDateFr($f['date_debut']) ?> –
                            <?= $f['en_cours'] ? '<span class="badge bg-success" style="font-size:.6rem">' . t('Présent') . '</span>' : ($f['date_fin'] ? formatDateFr($f['date_fin']) : '') ?>
                            <?php if ($f['lieu']): ?> · <?= h($f['lieu']) ?><?php endif; ?>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-light" onclick="supprimerFormation(<?= $f['id'] ?>, this)" title="<?= t('Supprimer') ?>">
                        <i class="bi bi-trash" style="color:var(--rose)"></i>
                    </button>
                </div>
                <?php if ($f['description']): ?>
                <p class="small mt-2 mb-0" style="color:var(--text-3)"><?= nl2br(h($f['description'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Formation -->
<div class="modal fade" id="modalFormation" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold"><?= t('Ajouter une formation') ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <input type="hidden" name="action" value="ajouter_formation">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label fw-semibold"><?= t('Établissement *') ?></label><input type="text" name="etablissement" class="form-control" required placeholder="ECE Paris..."></div>
                    <div class="mb-3"><label class="form-label fw-semibold"><?= t('Diplôme *') ?></label><input type="text" name="diplome" class="form-control" required placeholder="<?= t('Master, Licence...') ?>"></div>
                    <div class="mb-3"><label class="form-label fw-semibold"><?= t('Domaine') ?></label><input type="text" name="domaine" class="form-control" placeholder="<?= t('Informatique...') ?>"></div>
                    <div class="mb-3"><label class="form-label fw-semibold"><?= t('Lieu') ?></label><input type="text" name="lieu" class="form-control" placeholder="Paris..."></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label fw-semibold"><?= t('Date début *') ?></label><input type="date" name="date_debut" class="form-control" required></div>
                        <div class="col-6"><label class="form-label fw-semibold"><?= t('Date fin') ?></label><input type="date" name="date_fin" class="form-control" id="date_fin_form"></div>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" name="en_cours" id="en_cours"
                               onchange="document.getElementById('date_fin_form').disabled = this.checked">
                        <label class="form-check-label" for="en_cours"><?= t('Formation en cours') ?></label>
                    </div>
                    <div class="mb-3"><label class="form-label fw-semibold"><?= t('Description') ?></label><textarea name="description" class="form-control" rows="3" placeholder="<?= t('Spécialités...') ?>"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= t('Annuler') ?></button>
                    <button type="submit" class="btn btn-ecein-primary"><?= t('Enregistrer') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php elseif ($onglet === 'projets'): ?>
<div class="glass p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-kanban me-2" style="color:var(--teal)"></i><?= t('Mes Projets') ?></h6>
        <button class="btn btn-sm btn-ecein-primary" data-bs-toggle="modal" data-bs-target="#modalProjet">
            <i class="bi bi-plus-lg me-1"></i><?= t('Ajouter') ?>
        </button>
    </div>
    <?php if (empty($projets)): ?>
    <div class="empty-state">
        <i class="bi bi-kanban"></i>
        <p><?= t('Aucun projet renseigné. Ajoutez vos réalisations !') ?></p>
    </div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($projets as $p): ?>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <h6 class="fw-bold mb-0 small"><?= h($p['titre']) ?></h6>
                        <?php if ($p['en_cours']): ?><span class="badge bg-success" style="font-size:.6rem"><?= t('En cours') ?></span><?php endif; ?>
                    </div>
                    <?php if ($p['type']): ?>
                    <span class="badge bg-secondary mb-2" style="font-size:.6rem"><?= h(t($p['type'])) ?></span>
                    <?php endif; ?>
                    <p class="small" style="color:var(--text-3)"><?= nl2br(h($p['description'] ?? '')) ?></p>
                    <?php if ($p['date_debut']): ?>
                    <div style="font-size:.72rem;color:var(--text-3)">
                        <?= formatDateFr($p['date_debut']) ?>
                        <?= $p['date_fin'] ? ' – ' . formatDateFr($p['date_fin']) : '' ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($p['lien']): ?>
                    <a href="<?= h($p['lien']) ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                        <i class="bi bi-box-arrow-up-right me-1"></i><?= t('Voir') ?>
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-end">
                    <button class="btn btn-sm btn-outline-danger" onclick="supprimerProjet(<?= $p['id'] ?>, this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Projet -->
<div class="modal fade" id="modalProjet" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold"><?= t('Ajouter un projet') ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <input type="hidden" name="action" value="ajouter_projet">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label fw-semibold"><?= t('Titre *') ?></label><input type="text" name="titre" class="form-control" required placeholder="<?= t('Nom du projet') ?>"></div>
                    <div class="mb-3"><label class="form-label fw-semibold"><?= t('Type') ?></label>
                        <select name="type_projet" class="form-select">
                            <option value=""><?= t('Sélectionner...') ?></option>
                            <option value="Projet académique"><?= t('Projet académique') ?></option>
                            <option value="Projet Erasmus"><?= t('Projet Erasmus') ?></option>
                            <option value="Projet entreprise"><?= t('Projet entreprise') ?></option>
                            <option value="Projet personnel"><?= t('Projet personnel') ?></option>
                            <option value="Association étudiante"><?= t('Association étudiante') ?></option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label fw-semibold"><?= t('Description') ?></label><textarea name="description" class="form-control" rows="3"></textarea></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label fw-semibold"><?= t('Date début') ?></label><input type="date" name="date_debut" class="form-control"></div>
                        <div class="col-6"><label class="form-label fw-semibold"><?= t('Date fin') ?></label><input type="date" name="date_fin" class="form-control"></div>
                    </div>
                    <div class="form-check mb-3"><input type="checkbox" class="form-check-input" name="en_cours" id="proj_en_cours"><label class="form-check-label" for="proj_en_cours"><?= t('Projet en cours') ?></label></div>
                    <div class="mb-3"><label class="form-label fw-semibold"><?= t('Lien') ?></label><input type="url" name="lien" class="form-control" placeholder="https://..."></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= t('Annuler') ?></button>
                    <button type="submit" class="btn btn-ecein-primary"><?= t('Enregistrer') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php elseif ($onglet === 'cv'): ?>
<?php
// Récupérer les compétences de l'utilisateur
$stmtCompetences = $pdo->prepare("
    SELECT id, nom, niveau, ordre, date_ajout
    FROM competences
    WHERE utilisateur_id = ?
    ORDER BY ordre ASC, date_ajout ASC
");
$stmtCompetences->execute([$userId]);
$competences = $stmtCompetences->fetchAll();
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="glass p-4">
            <h6 class="fw-bold mb-3" style="font-size:.85rem"><i class="bi bi-file-earmark-person me-2" style="color:var(--accent)"></i><?= t('Générer mon CV') ?></h6>
            <p class="small" style="color:var(--text-3)"><?= t('CV généré automatiquement à partir de vos données de profil.') ?></p>
            <div class="d-grid gap-2">
                <a href="cv/generer_cv.php?format=html" target="_blank" class="btn btn-ecein-primary"><i class="bi bi-file-earmark-code me-2"></i><?= t('CV en HTML') ?></a>
                <a href="cv/generer_cv.php?format=pdf" target="_blank" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf me-2"></i><?= t('CV en PDF') ?></a>
                <a href="cv/generer_cv.php?format=xml" target="_blank" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-code me-2"></i><?= t('Exporter XML') ?></a>
            </div>
            <hr style="border-color:var(--glass-border)">
            <h6 class="fw-bold mb-2" style="font-size:.82rem"><?= t('Importer depuis XML') ?></h6>
            <form method="POST" action="api/import_cv.php" enctype="multipart/form-data">
                <div class="mb-2"><input type="file" name="cv_xml" class="form-control form-control-sm" accept=".xml"></div>
                <button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-upload me-1"></i><?= t('Importer') ?></button>
            </form>
        </div>
        
        <!-- Gestion des compétences -->
        <div class="glass p-4 mt-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0" style="font-size:.85rem"><i class="bi bi-star me-2" style="color:var(--accent)"></i><?= t('Mes Compétences') ?></h6>
                <button class="btn btn-sm btn-ecein-primary" data-bs-toggle="modal" data-bs-target="#modalCompetence">
                    <i class="bi bi-plus-lg me-1"></i><?= t('Ajouter') ?>
                </button>
            </div>
            <div id="liste-competences">
                <?php if (empty($competences)): ?>
                <p class="small text-muted text-center mb-0"><?= t('Aucune compétence. Ajoutez-en une !') ?></p>
                <?php else: ?>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($competences as $comp): ?>
                    <div class="d-flex align-items-center justify-content-between glass p-3 rounded-3" id="competence-<?= $comp['id'] ?>" style="border:1px solid var(--glass-border)">
                        <div class="d-flex align-items-center gap-2 flex-grow-1">
                            <span class="fw-semibold" style="font-size:.9rem"><?= h($comp['nom']) ?></span>
                            <span class="badge bg-ecein-primary" style="font-size:.7rem">
                                <?php
                                $niveaux = ['debutant' => t('Débutant'), 'intermediaire' => t('Intermédiaire'), 'avance' => t('Avancé'), 'expert' => t('Expert')];
                                echo $niveaux[$comp['niveau']] ?? t('Intermédiaire');
                                ?>
                            </span>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-light" 
                                    onclick="ouvrirModalModifierCompetence(<?= $comp['id'] ?>, '<?= h(addslashes($comp['nom'])) ?>', '<?= $comp['niveau'] ?>')"
                                    title="<?= t('Modifier') ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-light" 
                                    onclick="supprimerCompetence(<?= $comp['id'] ?>)"
                                    title="<?= t('Supprimer') ?>"
                                    style="color:var(--rose)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="glass p-0 overflow-hidden">
            <div class="p-3" style="border-bottom:1px solid var(--glass-border)">
                <span class="fw-bold" style="font-size:.85rem"><i class="bi bi-eye me-2" style="color:var(--accent)"></i><?= t('Aperçu du CV') ?></span>
            </div>
            <div id="cv-apercu">
                <iframe src="cv/generer_cv.php?format=html&preview=1"
                        class="w-100 border-0" style="height:600px;background:var(--bg)" title="<?= t('Aperçu du CV') ?>"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Modal Compétence (Ajout/Modification) -->
<div class="modal fade" id="modalCompetence" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalCompetenceTitle"><?= t('Ajouter une compétence') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCompetence" method="POST">
                <input type="hidden" name="action" id="competence-action" value="ajouter">
                <input type="hidden" name="competence_id" id="competence-id" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= t('Nom de la compétence *') ?></label>
                        <input type="text" name="nom" id="competence-nom" class="form-control" required placeholder="<?= t('Ex: PHP, JavaScript, MySQL...') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= t('Niveau') ?></label>
                        <select name="niveau" id="competence-niveau" class="form-select">
                            <option value="debutant"><?= t('Débutant') ?></option>
                            <option value="intermediaire" selected><?= t('Intermédiaire') ?></option>
                            <option value="avance"><?= t('Avancé') ?></option>
                            <option value="expert"><?= t('Expert') ?></option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= t('Annuler') ?></button>
                    <button type="button" class="btn btn-ecein-primary" id="btn-submit-competence" onclick="soumettreFormulaireCompetence()"><?= t('Ajouter') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Gestion du formulaire de compétence
function soumettreFormulaireCompetence() {
    const formData = $('#formCompetence').serialize();
    const $btn = $('#btn-submit-competence');
    const $btnOriginal = $btn.html();
    
    // Validation côté client
    const nom = $('#competence-nom').val().trim();
    if (!nom) {
        afficherToast(t('Le nom de la compétence est requis.'), 'warning');
        return;
    }
    
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>' + t('Enregistrement...'));
    
    $.post('api/competences.php', formData, function(res) {
        if (res.succes) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalCompetence'));
            if (modal) modal.hide();
            afficherToast(res.message, 'success');
            // Recharger après un court délai pour laisser le temps au toast de s'afficher
            setTimeout(function() {
                location.reload();
            }, 500);
        } else {
            afficherToast(res.message, 'danger');
            $btn.prop('disabled', false).html($btnOriginal);
        }
    }).fail(function(xhr, status, error) {
        console.error('Erreur AJAX:', {status, error, xhr});
        afficherToast(t('Erreur lors de l\'enregistrement.'), 'danger');
        $btn.prop('disabled', false).html($btnOriginal);
    });
    
    return false;
}

// Empêcher la soumission classique du formulaire
$(document).ready(function() {
    $('#formCompetence').on('submit', function(e) {
        e.preventDefault();
        soumettreFormulaireCompetence();
        return false;
    });
});

function ouvrirModalModifierCompetence(competenceId, nom, niveau) {
    document.getElementById('modalCompetenceTitle').innerText = t('Modifier la compétence');
    document.getElementById('competence-action').value = 'modifier';
    document.getElementById('competence-id').value = competenceId;
    document.getElementById('competence-nom').value = nom;
    document.getElementById('competence-niveau').value = niveau;
    document.getElementById('btn-submit-competence').innerText = t('Enregistrer');
    const modal = new bootstrap.Modal(document.getElementById('modalCompetence'));
    modal.show();
}

function supprimerCompetence(competenceId) {
    if (confirm(t('Êtes-vous sûr de vouloir supprimer cette compétence ?'))) {
        const $competence = $('#competence-' + competenceId);
        $competence.fadeOut(300, function() {
            $.post('api/competences.php', {
                action: 'supprimer',
                competence_id: competenceId
            }, function(res) {
                if (res.succes) {
                    afficherToast(res.message, 'success');
                    // Recharger après un court délai pour laisser le temps au toast de s'afficher
                    setTimeout(function() {
                        location.reload();
                    }, 500);
                } else {
                    afficherToast(res.message, 'danger');
                    $competence.fadeIn(300); // Réafficher si erreur
                }
            }).fail(function() {
                afficherToast(t('Erreur lors de la suppression.'), 'danger');
                $competence.fadeIn(300); // Réafficher si erreur
            });
        });
    }
}

// Réinitialiser le modal quand il se ferme
$('#modalCompetence').on('hidden.bs.modal', function() {
    document.getElementById('modalCompetenceTitle').innerText = t('Ajouter une compétence');
    document.getElementById('competence-action').value = 'ajouter';
    document.getElementById('competence-id').value = '';
    document.getElementById('competence-nom').value = '';
    document.getElementById('competence-niveau').value = 'intermediaire';
    document.getElementById('btn-submit-competence').innerText = t('Ajouter');
});
</script>

<?php elseif ($onglet === 'albums'): ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="fw-bold mb-0"><?= t('Mes Albums Photos') ?></h6>
    <button class="btn btn-sm btn-ecein-primary" data-bs-toggle="modal" data-bs-target="#modalAlbum">
        <i class="bi bi-plus-lg me-1"></i><?= t('Créer un album') ?>
    </button>
</div>
<?php if (empty($albums)): ?>
<div class="glass empty-state">
    <i class="bi bi-images"></i>
    <p><?= t('Aucun album photo. Créez votre premier album !') ?></p>
</div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($albums as $album): ?>
    <div class="col-sm-6 col-md-4">
        <div class="glass p-0 overflow-hidden h-100 position-relative">
            <a href="album.php?id=<?= $album['id'] ?>" class="text-decoration-none" style="color:inherit">
                <div style="height:150px;background:var(--bg-3);background-image:url('<?= h($album['couverture'] ?? '') ?>');background-size:cover;background-position:center;cursor:pointer">
                    <?php if (!$album['couverture']): ?>
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <i class="bi bi-images fs-1" style="color:var(--text-3);opacity:.25"></i>
                    </div>
                    <?php endif; ?>
                    <div class="position-absolute top-0 end-0 m-2">
                        <span class="badge bg-ecein-primary"><?= $album['nb_photos'] ?? 0 ?> <?= ($album['nb_photos'] ?? 0) > 1 ? t('photos') : t('photo') ?></span>
                    </div>
                </div>
                <div class="p-3">
                    <h6 class="fw-bold mb-1 small"><?= h($album['nom']) ?></h6>
                    <p class="small mb-0" style="color:var(--text-3)"><?= h($album['description'] ?? '') ?></p>
                    <p style="font-size:.7rem;color:var(--text-3)"><?= formatDateFr($album['date_creation']) ?></p>
                </div>
            </a>
            <div class="p-2 d-flex gap-1" style="border-top:1px solid var(--glass-border)">
                <button class="btn btn-sm btn-outline-primary flex-fill" onclick="ouvrirModalAjouterPhoto(<?= $album['id'] ?>)">
                    <i class="bi bi-plus"></i> <?= t('Ajouter') ?>
                </button>
                <button class="btn btn-sm btn-outline-secondary" onclick="ouvrirModalModifierAlbum(<?= $album['id'] ?>, '<?= h(addslashes($album['nom'])) ?>', '<?= h(addslashes($album['description'] ?? '')) ?>')" title="<?= t('Modifier') ?>">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="supprimerAlbum(<?= $album['id'] ?>)" title="<?= t('Supprimer') ?>">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php elseif ($onglet === 'publications'): ?>
<div class="row g-3">
    <?php if (empty($publications)): ?>
    <div class="col-12">
        <div class="glass empty-state">
            <i class="bi bi-chat-square-dots"></i>
            <p><?= t('Aucune publication pour l\'instant.') ?></p>
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($publications as $post): ?>
    <div class="col-12">
        <?php include __DIR__ . '/includes/post_card.php'; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php elseif ($onglet === 'parametres'): ?>
<div class="glass p-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-gear me-2" style="color:var(--accent)"></i><?= t('Paramètres du compte') ?></h6>
    <form method="POST" id="formParametres">
        <input type="hidden" name="action" value="update_profil">
        <div class="row g-3">
            <div class="col-md-12"><label class="form-label fw-semibold"><?= t('Titre professionnel') ?></label><input type="text" name="titre" class="form-control" value="<?= h($userCourant['titre'] ?? '') ?>" placeholder="<?= t('Étudiant ING2 · Développeur Full-Stack') ?>"></div>
            <div class="col-md-12"><label class="form-label fw-semibold"><?= t('Biographie') ?></label><textarea name="biographie" class="form-control" rows="4"><?= h($userCourant['biographie'] ?? '') ?></textarea></div>
            <div class="col-md-6"><label class="form-label fw-semibold"><?= t('Localisation') ?></label><input type="text" name="localisation" class="form-control" value="<?= h($userCourant['localisation'] ?? '') ?>" placeholder="Paris, France"></div>
            <div class="col-md-6"><label class="form-label fw-semibold"><?= t('Téléphone') ?></label><input type="tel" name="telephone" class="form-control" value="<?= h($userCourant['telephone'] ?? '') ?>"></div>
            <div class="col-md-12"><label class="form-label fw-semibold"><?= t('Site web / Portfolio') ?></label><input type="url" name="site_web" class="form-control" value="<?= h($userCourant['site_web'] ?? '') ?>" placeholder="https://monportfolio.fr"></div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-ecein-primary"><i class="bi bi-save me-2"></i><?= t('Enregistrer les modifications') ?></button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- ===== MODAL : Éditer le profil ===== -->
<div class="modal fade" id="modalEditProfil" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold"><?= t('Modifier le profil') ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <input type="hidden" name="action" value="update_profil">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label fw-semibold"><?= t('Titre professionnel') ?></label><input type="text" name="titre" class="form-control" value="<?= h($userCourant['titre'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label fw-semibold"><?= t('Biographie') ?></label><textarea name="biographie" class="form-control" rows="3"><?= h($userCourant['biographie'] ?? '') ?></textarea></div>
                    <div class="mb-3"><label class="form-label fw-semibold"><?= t('Localisation') ?></label><input type="text" name="localisation" class="form-control" value="<?= h($userCourant['localisation'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label fw-semibold"><?= t('Site web') ?></label><input type="url" name="site_web" class="form-control" value="<?= h($userCourant['site_web'] ?? '') ?>"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= t('Annuler') ?></button>
                    <button type="submit" class="btn btn-ecein-primary"><?= t('Enregistrer') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Album -->
<div class="modal fade" id="modalAlbum" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold"><?= t('Créer un album') ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="api/albums.php">
                <input type="hidden" name="action" value="creer_album">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label fw-semibold"><?= t('Nom de l\'album *') ?></label><input type="text" name="nom" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label fw-semibold"><?= t('Description') ?></label><textarea name="description" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= t('Annuler') ?></button>
                    <button type="submit" class="btn btn-ecein-primary"><?= t('Créer') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modifier Album -->
<div class="modal fade" id="modalModifierAlbum" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold"><?= t('Modifier l\'album') ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="api/albums.php">
                <input type="hidden" name="action" value="modifier_album">
                <input type="hidden" name="album_id" id="modifier-album-id" value="">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label fw-semibold"><?= t('Nom de l\'album *') ?></label><input type="text" name="nom" id="modifier-album-nom" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label fw-semibold"><?= t('Description') ?></label><textarea name="description" id="modifier-album-description" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= t('Annuler') ?></button>
                    <button type="submit" class="btn btn-ecein-primary"><?= t('Enregistrer') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ajouter des photos -->
<div class="modal fade" id="modalAjouterPhoto" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><?= t('Ajouter des photos à l\'album') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="album-id-ajout" value="">
                <div id="liste-photos-disponibles" class="row g-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= t('Annuler') ?></button>
                <button type="button" class="btn btn-ecein-primary" id="btn-ajouter-photos" onclick="ajouterPhotosSelectionnees()" style="display:none">
                    <i class="bi bi-plus-lg me-1"></i><?= t('Ajouter') ?> <span id="nb-photos-selectionnees">0</span> <?= t('photo(s)') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function ouvrirModalAjouterPhoto(albumId) {
    photosSelectionnees = [];
    document.getElementById('album-id-ajout').value = albumId;
    mettreAJourBoutonAjouter();
    
    // Charger les photos disponibles via AJAX
    $.get('api/albums.php', { action: 'photos_disponibles', album_id: albumId }, function(res) {
        const container = document.getElementById('liste-photos-disponibles');
        if (res.succes && res.photos && res.photos.length > 0) {
            let html = '';
            res.photos.forEach(function(photo) {
                html += '<div class="col-6 col-md-4"><div class="position-relative"><img src="' + photo.fichier + '" alt="" class="w-100 rounded" style="height:120px;object-fit:cover;cursor:pointer" onclick="ajouterPhotoAlbum(' + albumId + ', ' + photo.id + ', this)"><div class="position-absolute top-0 end-0 m-1"><div class="badge bg-success" style="display:none" id="badge-' + photo.id + '"><i class="bi bi-check"></i></div></div></div></div>';
            });
            container.innerHTML = html;
        } else {
            container.innerHTML = '<div class="col-12 text-center py-4"><i class="bi bi-images fs-1" style="color:var(--text-3);opacity:.25"></i><p class="mt-2">' + t('Aucune photo disponible à ajouter.') + '</p><p class="small text-muted">' + t('Publiez d\'abord des photos depuis la page d\'accueil.') + '</p></div>';
        }
    });
    
    const modal = new bootstrap.Modal(document.getElementById('modalAjouterPhoto'));
    modal.show();
}

let photosSelectionnees = [];

function ajouterPhotoAlbum(albumId, publicationId, element) {
    const badge = document.getElementById('badge-' + publicationId);
    if (photosSelectionnees.includes(publicationId)) {
        photosSelectionnees = photosSelectionnees.filter(id => id !== publicationId);
        badge.style.display = 'none';
        element.style.opacity = '1';
    } else {
        photosSelectionnees.push(publicationId);
        badge.style.display = 'block';
        element.style.opacity = '0.7';
    }
    // Mettre à jour le bouton "Ajouter"
    mettreAJourBoutonAjouter();
}

function mettreAJourBoutonAjouter() {
    const $btn = $('#btn-ajouter-photos');
    const $nb = $('#nb-photos-selectionnees');
    if (photosSelectionnees.length > 0) {
        $btn.show();
        $nb.text(photosSelectionnees.length);
    } else {
        $btn.hide();
    }
}

function ajouterPhotosSelectionnees() {
    if (photosSelectionnees.length === 0) return;
    
    const albumId = document.getElementById('album-id-ajout').value;
    if (!albumId) return;
    
    const $btn = $('#btn-ajouter-photos');
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>' + t('Ajout en cours...'));
    
    let ajoutes = 0;
    let erreurs = 0;
    const total = photosSelectionnees.length;
    
    photosSelectionnees.forEach(function(publicationId) {
        $.post('api/albums.php', {
            action: 'ajouter_photo',
            album_id: albumId,
            publication_id: publicationId
        }, function(res) {
            ajoutes++;
            if (ajoutes + erreurs === total) {
                if (ajoutes > 0) {
                    afficherToast(t("%d photo(s) ajoutée(s) à l'album.", ajoutes), 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    afficherToast(t('Aucune photo n\'a pu être ajoutée.'), 'danger');
                    $btn.prop('disabled', false).html('<i class="bi bi-plus-lg me-1"></i>' + t('Ajouter') + ' <span id="nb-photos-selectionnees">' + total + '</span> ' + t('photo(s)'));
                }
            }
        }).fail(function() {
            erreurs++;
            if (ajoutes + erreurs === total) {
                if (ajoutes > 0) {
                    afficherToast(t('%d photo(s) ajoutée(s), %d erreur(s).', ajoutes, erreurs), 'warning');
                    setTimeout(() => location.reload(), 500);
                } else {
                    afficherToast(t('Erreur lors de l\'ajout des photos.'), 'danger');
                    $btn.prop('disabled', false).html('<i class="bi bi-plus-lg me-1"></i>' + t('Ajouter') + ' <span id="nb-photos-selectionnees">' + total + '</span> ' + t('photo(s)'));
                }
            }
        });
    });
}

function ouvrirModalModifierAlbum(albumId, nom, description) {
    document.getElementById('modifier-album-id').value = albumId;
    document.getElementById('modifier-album-nom').value = nom;
    document.getElementById('modifier-album-description').value = description;
    const modal = new bootstrap.Modal(document.getElementById('modalModifierAlbum'));
    modal.show();
}

function supprimerAlbum(albumId) {
    if (!confirm(t('Supprimer cet album ? Toutes les photos seront retirées de l\'album (mais pas supprimées).'))) return;
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'api/albums.php';
    form.innerHTML = '<input type="hidden" name="action" value="supprimer_album"><input type="hidden" name="album_id" value="' + albumId + '">';
    document.body.appendChild(form);
    form.submit();
}

// Réinitialiser la sélection quand on ferme le modal
document.getElementById('modalAjouterPhoto').addEventListener('hidden.bs.modal', function() {
    photosSelectionnees = [];
    mettreAJourBoutonAjouter();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
