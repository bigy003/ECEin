<?php
/**
 * ECE In - Panel d'administration (Version Alt)
 */
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$pdo       = getDB();
$pageTitle = t('Administration');

$stats = [];
$stats['utilisateurs'] = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE actif = 1")->fetchColumn();
$stats['publications']  = $pdo->query("SELECT COUNT(*) FROM publications")->fetchColumn();
$stats['connexions']    = $pdo->query("SELECT COUNT(*) FROM connexions WHERE statut = 'accepte'")->fetchColumn();
$stats['emplois']       = $pdo->query("SELECT COUNT(*) FROM emplois WHERE actif = 1")->fetchColumn();

$stmtUsers = $pdo->query("SELECT * FROM utilisateurs ORDER BY date_inscription DESC LIMIT 10");
$utilisateurs = $stmtUsers->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'ajouter_utilisateur') {
        $hash = password_hash($_POST['mot_de_passe'], PASSWORD_BCRYPT);
        try {
            $pdo->prepare("INSERT INTO utilisateurs (pseudo, email, mot_de_passe, nom, prenom, role) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([trim($_POST['pseudo']), trim($_POST['email']), $hash, trim($_POST['nom']), trim($_POST['prenom']), $_POST['role'] ?? 'auteur']);
            $nouvelId = (int) $pdo->lastInsertId();
            $pdo->prepare("UPDATE utilisateurs SET photo = ? WHERE id = ?")
                ->execute([genererAvatarInitiales($nouvelId, trim($_POST['prenom']), trim($_POST['nom'])), $nouvelId]);
            $succes = t('Utilisateur créé avec succès.');
        } catch (PDOException $e) {
            $erreur = t('Erreur : pseudo ou email déjà utilisé.');
        }
    }

    if ($action === 'supprimer_utilisateur') {
        $uid = (int) $_POST['utilisateur_id'];
        if ($uid !== $_SESSION['utilisateur_id']) {
            $pdo->prepare("UPDATE utilisateurs SET actif = 0 WHERE id = ?")->execute([$uid]);
            $succes = t('Utilisateur désactivé.');
        } else { $erreur = t('Vous ne pouvez pas vous supprimer vous-même.'); }
    }

    if ($action === 'activer_utilisateur') {
        $uid = (int) $_POST['utilisateur_id'];
        $pdo->prepare("UPDATE utilisateurs SET actif = 1 WHERE id = ?")->execute([$uid]);
        $succes = t('Utilisateur activé.');
    }

    if ($action === 'modifier_role') {
        $uid = (int) $_POST['utilisateur_id']; $role = $_POST['role'] ?? 'auteur';
        if ($uid !== $_SESSION['utilisateur_id']) {
            $pdo->prepare("UPDATE utilisateurs SET role = ? WHERE id = ?")->execute([$role, $uid]);
            $succes = t('Rôle modifié.');
        }
    }

    redirect('index.php');
}

include __DIR__ . '/../includes/header.php';

// Ajouter le CSS spécifique admin de manière dynamique pour éviter les erreurs de chemin
$adminCssPath = '../assets/css/admin.css';
echo '<link rel="stylesheet" href="' . $adminCssPath . '?v=' . time() . '">';

include __DIR__ . '/../includes/navbar.php';

$filtreActif = $_GET['actif'] ?? 'tous';
$filtreRole  = $_GET['role'] ?? 'tous';

$cond = []; $params = [];
if ($filtreActif === 'actif')   { $cond[] = 'actif = 1'; }
if ($filtreActif === 'inactif') { $cond[] = 'actif = 0'; }
if ($filtreRole !== 'tous')     { $cond[] = 'role = ?'; $params[] = $filtreRole; }

$where = $cond ? 'WHERE ' . implode(' AND ', $cond) : '';
$stmtUsers = $pdo->prepare("SELECT * FROM utilisateurs $where ORDER BY date_inscription DESC");
$stmtUsers->execute($params);
$utilisateurs = $stmtUsers->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-1"><i class="bi bi-shield-fill me-2" style="color:var(--rose)"></i><?= t('Panel d\'administration') ?></h5>
        <p class="mb-0 small" style="color:var(--text-3)"><?= t('Gestion des utilisateurs et du contenu ECE In') ?></p>
    </div>
    <a href="../index.php" class="btn btn-sm btn-light"><i class="bi bi-arrow-left me-1"></i><?= t('Retour au site') ?></a>
</div>

<?php if (isset($succes)): ?>
<div class="alert alert-success py-2 small"><i class="bi bi-check-circle me-2"></i><?= h($succes) ?></div>
<?php endif; ?>
<?php if (isset($erreur)): ?>
<div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-2"></i><?= h($erreur) ?></div>
<?php endif; ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <?php
    $statItems = [
        ['label' => t('Utilisateurs'), 'val' => $stats['utilisateurs'], 'icon' => 'bi-people-fill', 'color' => 'var(--accent)'],
        ['label' => t('Publications'), 'val' => $stats['publications'], 'icon' => 'bi-file-post-fill', 'color' => 'var(--emerald)'],
        ['label' => t('Connexions'),   'val' => $stats['connexions'],   'icon' => 'bi-diagram-3-fill', 'color' => 'var(--sky)'],
        ['label' => t('Offres'),       'val' => $stats['emplois'],      'icon' => 'bi-briefcase-fill',  'color' => 'var(--rose)'],
    ];
    foreach ($statItems as $s): ?>
    <div class="col-sm-6 col-lg-3">
        <div class="admin-stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fs-2 fw-bold"><?= $s['val'] ?></div>
                    <div style="font-size:.78rem;color:var(--text-3);text-transform:uppercase;letter-spacing:0.5px;font-weight:600;"><?= $s['label'] ?></div>
                </div>
                <i class="bi <?= $s['icon'] ?> fs-1" style="color:<?= $s['color'] ?>;opacity:.2"></i>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Gestion utilisateurs -->
<div class="glass p-0 mb-4" style="overflow:hidden;">
    <div class="p-3 d-flex justify-content-between align-items-center" style="border-bottom:1px solid var(--glass-border);background:var(--bg-2)">
        <span class="fw-bold" style="font-size:.95rem"><i class="bi bi-people-fill me-2" style="color:var(--accent)"></i><?= t('Gestion des utilisateurs') ?></span>
        <button class="btn btn-sm btn-ecein-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAjouterUser">
            <i class="bi bi-person-plus-fill me-1"></i><?= t('Ajouter un utilisateur') ?>
        </button>
    </div>

    <div class="p-3 admin-filters" style="border-bottom:1px solid var(--glass-border);background:var(--surface)">
        <div class="d-flex flex-wrap gap-2">
            <a href="?actif=tous" class="btn btn-sm <?= $filtreActif === 'tous' ? 'btn-ecein-primary' : 'btn-light' ?>"><?= t('Tous les statuts') ?></a>
            <a href="?actif=actif" class="btn btn-sm <?= $filtreActif === 'actif' ? 'btn-success' : 'btn-light' ?>"><i class="bi bi-check-circle me-1"></i><?= t('Actifs') ?></a>
            <a href="?actif=inactif" class="btn btn-sm <?= $filtreActif === 'inactif' ? 'btn-outline-danger' : 'btn-light' ?>"><i class="bi bi-x-circle me-1"></i><?= t('Inactifs') ?></a>
            <span class="mx-2 text-muted" style="border-left:1px solid var(--glass-border);"></span>
            <a href="?role=administrateur" class="btn btn-sm <?= $filtreRole === 'administrateur' ? 'btn-outline-danger' : 'btn-light' ?>"><i class="bi bi-shield-lock me-1"></i>Admins</a>
            <a href="?role=auteur" class="btn btn-sm <?= $filtreRole === 'auteur' ? 'btn-outline-primary' : 'btn-light' ?>"><i class="bi bi-person me-1"></i><?= t('Auteurs') ?></a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th><?= t('Utilisateur') ?></th>
                    <th>Email</th>
                    <th><?= t('Rôle') ?></th>
                    <th><?= t('Inscription') ?></th>
                    <th><?= t('Statut') ?></th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($utilisateurs as $u): ?>
                <tr class="<?= !$u['actif'] ? 'row-inactive' : '' ?>">
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <img src="../<?= h($u['photo']) ?>" alt="" class="rounded-circle shadow-sm" width="40" height="40" style="object-fit:cover;border:2px solid var(--surface)">
                            <div>
                                <div class="fw-bold" style="color:var(--text)"><?= h($u['prenom'] . ' ' . $u['nom']) ?></div>
                                <div style="font-size:.75rem;color:var(--text-3)">@<?= h($u['pseudo']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="small" style="color:var(--text-2)"><i class="bi bi-envelope me-1 text-muted"></i> <?= h($u['email']) ?></td>
                    <td>
                        <?php if ($u['role'] === 'administrateur'): ?>
                        <span class="admin-badge-role badge-role-admin"><i class="bi bi-shield-fill me-1"></i>Admin</span>
                        <?php else: ?>
                        <span class="admin-badge-role badge-role-auteur"><i class="bi bi-person-fill me-1"></i><?= t('Auteur') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted"><i class="bi bi-calendar3 me-1"></i> <?= formatDateFr($u['date_inscription']) ?></td>
                    <td>
                        <?php if ($u['actif']): ?><span class="badge bg-success rounded-pill px-2" style="font-size:.65rem;font-weight:500;"><?= t('Actif') ?></span>
                        <?php else: ?><span class="badge bg-danger rounded-pill px-2" style="font-size:.65rem;font-weight:500;"><?= t('Inactif') ?></span><?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="../utilisateur.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-light border" title="<?= t('Voir le profil public') ?>"><i class="bi bi-eye"></i></a>
                            <?php if ($u['id'] !== $_SESSION['utilisateur_id']): ?>
                            <form method="POST" class="d-inline" style="min-width: 120px;">
                                <input type="hidden" name="action" value="modifier_role">
                                <input type="hidden" name="utilisateur_id" value="<?= $u['id'] ?>">
                                <div class="input-group input-group-sm">
                                    <select name="role" class="form-select" onchange="this.form.submit()" style="font-size:0.75rem;border-radius:var(--radius-sm) 0 0 var(--radius-sm)">
                                        <option value="auteur" <?= $u['role'] === 'auteur' ? 'selected' : '' ?>><?= t('Auteur') ?></option>
                                        <option value="administrateur" <?= $u['role'] === 'administrateur' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                    <span class="input-group-text bg-light"><i class="bi bi-pencil-square" style="font-size:0.7rem"></i></span>
                                </div>
                            </form>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="<?= $u['actif'] ? 'supprimer_utilisateur' : 'activer_utilisateur' ?>">
                                <input type="hidden" name="utilisateur_id" value="<?= $u['id'] ?>">
                                <?php if ($u['actif']): ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= t('Désactiver l\'utilisateur') ?>" onclick="return confirm(<?= h(json_encode(t('Désactiver ce compte ? L\'utilisateur ne pourra plus se connecter.'))) ?>)">
                                        <i class="bi bi-person-x-fill"></i>
                                    </button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="<?= t('Réactiver l\'utilisateur') ?>" onclick="return confirm(<?= h(json_encode(t('Réactiver ce compte ?'))) ?>)">
                                        <i class="bi bi-person-check-fill"></i>
                                    </button>
                                <?php endif; ?>
                            </form>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border py-2" style="font-size:.7rem;font-weight:normal"><?= t('Vous-même') ?></span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Ajouter utilisateur -->
<div class="modal fade admin-modal" id="modalAjouterUser" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold"><?= t('Ajouter un utilisateur') ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <input type="hidden" name="action" value="ajouter_utilisateur">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-6"><label class="form-label fw-semibold"><?= t('Prénom *') ?></label><input type="text" name="prenom" class="form-control" required></div>
                        <div class="col-6"><label class="form-label fw-semibold"><?= t('Nom *') ?></label><input type="text" name="nom" class="form-control" required></div>
                        <div class="col-12"><label class="form-label fw-semibold"><?= t('Pseudo *') ?></label><input type="text" name="pseudo" class="form-control" required></div>
                        <div class="col-12"><label class="form-label fw-semibold"><?= t('Email ECE *') ?></label><input type="email" name="email" class="form-control" required placeholder="<?= t('prenom.nom@edu.ece.fr') ?>"></div>
                        <div class="col-12"><label class="form-label fw-semibold"><?= t('Mot de passe *') ?></label><input type="password" name="mot_de_passe" class="form-control" required minlength="8"></div>
                        <div class="col-12"><label class="form-label fw-semibold"><?= t('Rôle') ?></label>
                            <select name="role" class="form-select"><option value="auteur"><?= t('Auteur') ?></option><option value="administrateur"><?= t('Administrateur') ?></option></select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= t('Annuler') ?></button>
                    <button type="submit" class="btn btn-ecein-primary"><?= t('Créer l\'utilisateur') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
