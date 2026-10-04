<?php
/**
 * ECE In - Page Emplois (Version Alt)
 */
require_once __DIR__ . '/config/config.php';
requireConnexion();

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$pageTitle = t('Emplois & Stages');

$filtreType      = $_GET['type'] ?? '';
$filtreRecherche = trim($_GET['q'] ?? '');

$conditions = ['e.actif = 1'];
$params = [];
if ($filtreType) { $conditions[] = 'e.type = ?'; $params[] = $filtreType; }
if ($filtreRecherche) { $conditions[] = '(e.titre LIKE ? OR e.entreprise LIKE ? OR e.description LIKE ?)'; $params[] = "%$filtreRecherche%"; $params[] = "%$filtreRecherche%"; $params[] = "%$filtreRecherche%"; }

$where = implode(' AND ', $conditions);
$stmtEmplois = $pdo->prepare("SELECT e.*, u.nom, u.prenom, u.photo FROM emplois e JOIN utilisateurs u ON u.id = e.publie_par WHERE $where ORDER BY e.date_publication DESC");
$stmtEmplois->execute($params);
$emplois = $stmtEmplois->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'publier_emploi') {
        $pdo->prepare("
            INSERT INTO emplois (publie_par, titre, entreprise, description, type, lieu, salaire, date_debut, date_limite, lien_candidature, contact_email)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $userId, trim($_POST['titre'] ?? ''), trim($_POST['entreprise'] ?? ''), trim($_POST['description'] ?? ''),
            $_POST['type_emploi'] ?? 'stage', trim($_POST['lieu'] ?? ''), trim($_POST['salaire'] ?? ''),
            !empty($_POST['date_debut']) ? $_POST['date_debut'] : null, !empty($_POST['date_limite']) ? $_POST['date_limite'] : null,
            trim($_POST['lien_candidature'] ?? ''), trim($_POST['contact_email'] ?? ''),
        ]);
        redirect('emplois.php');
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h5 class="fw-bold mb-1"><i class="bi bi-briefcase me-2" style="color:var(--accent)"></i><?= t('Emplois & Opportunités') ?></h5>
        <p class="mb-0 small" style="color:var(--text-3)"><?= t('Stages, alternances, CDI, CDD : trouvez votre prochaine opportunité.') ?></p>
    </div>
    <?php if (estAdmin()): ?>
    <button class="btn btn-ecein-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalPublierEmploi">
        <i class="bi bi-plus-lg me-1"></i><?= t('Publier une offre') ?>
    </button>
    <?php endif; ?>
</div>

<div class="row g-4">
    <!-- Filtres -->
    <div class="col-lg-3 d-none d-lg-block">
        <div class="glass p-4 mb-3" style="position:sticky;top:70px">
            <h6 class="fw-bold mb-3" style="font-size:.82rem"><i class="bi bi-funnel me-2" style="color:var(--accent)"></i><?= t('Filtres') ?></h6>
            <form method="GET" action="emplois.php">
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.78rem"><?= t('Mots-clés') ?></label>
                    <input type="search" name="q" class="form-control form-control-sm" placeholder="<?= t('Titre, entreprise...') ?>" value="<?= h($filtreRecherche) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.78rem"><?= t('Type de contrat') ?></label>
                    <?php
                    $types = ['' => t('Tous'), 'stage' => t('Stage'), 'alternance' => t('Alternance'), 'cdi' => t('CDI'), 'cdd' => t('CDD'), 'freelance' => t('Freelance'), 'benevole' => t('Bénévolat')];
                    foreach ($types as $val => $label): ?>
                    <div class="form-check">
                        <input type="radio" class="form-check-input" name="type" id="type-<?= $val ?: 'all' ?>" value="<?= $val ?>" <?= $filtreType === $val ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="type-<?= $val ?: 'all' ?>"><?= $label ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn btn-ecein-primary btn-sm w-100"><i class="bi bi-search me-1"></i><?= t('Rechercher') ?></button>
                <?php if ($filtreType || $filtreRecherche): ?>
                <a href="emplois.php" class="btn btn-light btn-sm w-100 mt-2"><i class="bi bi-x me-1"></i><?= t('Réinitialiser') ?></a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Offres -->
    <div class="col-lg-9">
        <form class="d-lg-none mb-3" method="GET" action="emplois.php">
            <div class="input-group">
                <input type="search" name="q" class="form-control" placeholder="<?= t('Rechercher...') ?>" value="<?= h($filtreRecherche) ?>">
                <button type="submit" class="btn btn-ecein-primary"><i class="bi bi-search"></i></button>
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <p class="mb-0 small" style="color:var(--text-3)">
                <strong style="color:var(--text)"><?= count($emplois) ?></strong> <?= count($emplois) > 1 ? t('offres trouvées') : t('offre trouvée') ?>
                <?= $filtreType ? t('pour') . ' <strong>' . h($types[$filtreType] ?? $filtreType) . '</strong>' : '' ?>
            </p>
        </div>

        <?php if (empty($emplois)): ?>
        <div class="glass empty-state">
            <i class="bi bi-briefcase"></i>
            <p><?= t('Aucune offre ne correspond à votre recherche.') ?></p>
            <a href="emplois.php" class="btn btn-outline-primary btn-sm"><?= t('Voir toutes les offres') ?></a>
        </div>
        <?php else: ?>
        <?php foreach ($emplois as $emploi): ?>
        <div class="glass p-4 mb-3 emploi-card" id="emploi-<?= $emploi['id'] ?>" 
             style="cursor:pointer"
             data-emploi='<?= htmlspecialchars(json_encode($emploi), ENT_QUOTES, 'UTF-8') ?>'
             onclick="afficherDetailsEmploi(JSON.parse(this.getAttribute('data-emploi')))">
            <div class="d-flex gap-3 align-items-start">
                <div class="flex-shrink-0 rounded-3 d-flex align-items-center justify-content-center" style="width:50px;height:50px;background:var(--accent-light)">
                    <i class="bi bi-building" style="color:var(--accent);font-size:1.2rem"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <h6 class="fw-bold mb-0 small"><?= h($emploi['titre']) ?></h6>
                            <div class="fw-semibold" style="color:var(--accent);font-size:.82rem"><?= h($emploi['entreprise']) ?></div>
                        </div>
                        <?php
                        $badgeColors = ['stage' => 'bg-info', 'alternance' => 'bg-warning', 'cdi' => 'bg-success', 'cdd' => 'bg-secondary', 'freelance' => 'bg-purple', 'benevole' => 'bg-secondary'];
                        $badgeClass = $badgeColors[$emploi['type']] ?? 'bg-secondary';
                        ?>
                        <span class="badge <?= $badgeClass ?>" style="font-size:.65rem"><?= h($types[$emploi['type']] ?? ucfirst($emploi['type'])) ?></span>
                    </div>

                    <div class="d-flex flex-wrap gap-3 my-2 small" style="color:var(--text-3)">
                        <?php if ($emploi['lieu']): ?><span><i class="bi bi-geo-alt me-1"></i><?= h($emploi['lieu']) ?></span><?php endif; ?>
                        <?php if ($emploi['salaire']): ?><span><i class="bi bi-cash me-1"></i><?= h($emploi['salaire']) ?></span><?php endif; ?>
                        <?php if ($emploi['date_debut']): ?><span><i class="bi bi-calendar me-1"></i><?= t('Dès le') ?> <?= formatDateFr($emploi['date_debut']) ?></span><?php endif; ?>
                        <?php if ($emploi['date_limite']): ?><span style="color:var(--rose)"><i class="bi bi-clock me-1"></i><?= t('Limite :') ?> <?= formatDateFr($emploi['date_limite']) ?></span><?php endif; ?>
                    </div>

                    <p class="small mb-3" style="color:var(--text-2)"><?= nl2br(h(substr($emploi['description'], 0, 200))) ?>...</p>
                    
                    <p class="small mb-3" style="color:var(--accent)">
                        <i class="bi bi-arrow-right me-1"></i><?= t('Cliquer pour voir tous les détails') ?>
                    </p>

                    <div class="d-flex flex-wrap gap-2 align-items-center" onclick="event.stopPropagation()">
                        <a href="candidater.php?id=<?= $emploi['id'] ?>" class="btn btn-sm btn-ecein-primary"><i class="bi bi-send me-1"></i><?= t('Candidater') ?></a>
                        <?php if ($emploi['contact_email']): ?>
                        <a href="contacter.php?id=<?= $emploi['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-envelope me-1"></i><?= t('Contacter') ?></a>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-light btn-favori" 
                                data-emploi-id="<?= $emploi['id'] ?>" 
                                onclick="toggleFavori(<?= $emploi['id'] ?>, this)" 
                                title="<?= t('Sauvegarder') ?>">
                            <i class="bi bi-bookmark"></i>
                        </button>
                        <span class="ms-auto" style="font-size:.7rem;color:var(--text-3)">
                            <?= h($emploi['prenom'] . ' ' . $emploi['nom']) ?> · <?= tempsEcoule($emploi['date_publication']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Publier offre -->
<div class="modal fade" id="modalPublierEmploi" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold"><i class="bi bi-briefcase me-2"></i><?= t('Publier une offre') ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <input type="hidden" name="action" value="publier_emploi">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8"><label class="form-label fw-semibold"><?= t('Titre du poste *') ?></label><input type="text" name="titre" class="form-control" required placeholder="<?= t('Développeur Full-Stack (Stage)') ?>"></div>
                        <div class="col-md-4"><label class="form-label fw-semibold"><?= t('Type *') ?></label>
                            <select name="type_emploi" class="form-select" required>
                                <option value="stage"><?= t('Stage') ?></option><option value="alternance"><?= t('Alternance') ?></option><option value="cdi">CDI</option>
                                <option value="cdd">CDD</option><option value="freelance">Freelance</option><option value="benevole"><?= t('Bénévolat') ?></option>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label fw-semibold"><?= t('Entreprise *') ?></label><input type="text" name="entreprise" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label fw-semibold"><?= t('Lieu') ?></label><input type="text" name="lieu" class="form-control" placeholder="Paris, France"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold"><?= t('Salaire') ?></label><input type="text" name="salaire" class="form-control" placeholder="<?= t('1200€/mois') ?>"></div>
                        <div class="col-md-3"><label class="form-label fw-semibold"><?= t('Date début') ?></label><input type="date" name="date_debut" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label fw-semibold"><?= t('Date limite') ?></label><input type="date" name="date_limite" class="form-control"></div>
                        <div class="col-12"><label class="form-label fw-semibold"><?= t('Description *') ?></label><textarea name="description" class="form-control" rows="5" required placeholder="<?= t('Décrivez le poste...') ?>"></textarea></div>
                        <div class="col-md-6"><label class="form-label fw-semibold"><?= t('Lien candidature') ?></label><input type="url" name="lien_candidature" class="form-control" placeholder="https://..."></div>
                        <div class="col-md-6"><label class="form-label fw-semibold"><?= t('Email contact') ?></label><input type="email" name="contact_email" class="form-control" placeholder="<?= t('rh@entreprise.fr') ?>"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= t('Annuler') ?></button>
                    <button type="submit" class="btn btn-ecein-primary"><i class="bi bi-send me-2"></i><?= t('Publier l\'offre') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Vérifier l'état des favoris au chargement
document.addEventListener('DOMContentLoaded', function() {
    const boutonsFavoris = document.querySelectorAll('.btn-favori');
    boutonsFavoris.forEach(btn => {
        const emploiId = btn.getAttribute('data-emploi-id');
        verifierFavori(emploiId, btn);
    });
});

// Vérifier si une offre est en favoris
function verifierFavori(emploiId, bouton) {
    fetch(`api/favoris_emplois.php?action=verifier&emploi_id=${emploiId}`)
        .then(response => response.json())
        .then(data => {
            if (data.succes && data.est_favori) {
                bouton.classList.add('active');
                bouton.querySelector('i').classList.remove('bi-bookmark');
                bouton.querySelector('i').classList.add('bi-bookmark-fill');
            }
        })
        .catch(error => console.error('Erreur:', error));
}

// Ajouter ou retirer un favori
function toggleFavori(emploiId, bouton) {
    event.stopPropagation();
    
    const estActif = bouton.classList.contains('active');
    const action = estActif ? 'retirer' : 'ajouter';
    
    fetch('api/favoris_emplois.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=${action}&emploi_id=${emploiId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.succes) {
            if (action === 'ajouter') {
                bouton.classList.add('active');
                bouton.querySelector('i').classList.remove('bi-bookmark');
                bouton.querySelector('i').classList.add('bi-bookmark-fill');
                afficherToast(t('Offre ajoutée aux favoris'), 'success');
            } else {
                bouton.classList.remove('active');
                bouton.querySelector('i').classList.remove('bi-bookmark-fill');
                bouton.querySelector('i').classList.add('bi-bookmark');
                afficherToast(t('Offre retirée des favoris'), 'info');
            }
        } else {
            afficherToast(data.message || t('Erreur'), 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        afficherToast(t('Erreur lors de la sauvegarde'), 'error');
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
