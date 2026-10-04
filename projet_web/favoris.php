<?php
/**
 * ECE In - Page Mes Favoris
 * 
 * Affiche toutes les offres d'emploi sauvegardées par l'utilisateur
 */
require_once __DIR__ . '/config/config.php';
requireConnexion();

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$pageTitle = t('Mes Favoris');

// Récupérer les offres en favoris
$stmtFavoris = $pdo->prepare("
    SELECT e.*, u.nom, u.prenom, u.photo, f.date_ajout
    FROM favoris_emplois f
    JOIN emplois e ON e.id = f.emploi_id
    JOIN utilisateurs u ON u.id = e.publie_par
    WHERE f.utilisateur_id = ? AND e.actif = 1
    ORDER BY f.date_ajout DESC
");
$stmtFavoris->execute([$userId]);
$favoris = $stmtFavoris->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h5 class="fw-bold mb-1">
            <i class="bi bi-bookmark-fill me-2" style="color:var(--accent)"></i><?= t('Mes Favoris') ?>
        </h5>
        <p class="mb-0 small" style="color:var(--text-3)">
            <?= t('Offres d\'emploi et de bénévolat sauvegardées') ?>
        </p>
    </div>
    <a href="emplois.php" class="btn btn-sm btn-light">
        <i class="bi bi-arrow-left me-1"></i><?= t('Retour aux offres') ?>
    </a>
</div>

<?php if (empty($favoris)): ?>
<div class="glass empty-state">
    <i class="bi bi-bookmark" style="font-size:3rem;color:var(--text-3)"></i>
    <p><?= t('Aucune offre sauvegardée pour le moment.') ?></p>
    <a href="emplois.php" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-search me-1"></i><?= t('Parcourir les offres') ?>
    </a>
</div>
<?php else: ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="mb-0 small" style="color:var(--text-3)">
        <strong style="color:var(--text)"><?= count($favoris) ?></strong> <?= count($favoris) > 1 ? t('offres sauvegardées') : t('offre sauvegardée') ?>
    </p>
</div>

<?php foreach ($favoris as $emploi): ?>
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
                $badgeColors = ['stage' => 'bg-info', 'alternance' => 'bg-warning', 'cdi' => 'bg-success', 'cdd' => 'bg-secondary', 'freelance' => 'bg-purple', 'benevole' => 'bg-danger'];
                $badgeClass = $badgeColors[$emploi['type']] ?? 'bg-secondary';
                ?>
                <span class="badge <?= $badgeClass ?>" style="font-size:.65rem"><?= h(libelleTypeEmploi($emploi['type'])) ?></span>
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
                <button class="btn btn-sm btn-light btn-favori active" 
                        data-emploi-id="<?= $emploi['id'] ?>" 
                        onclick="toggleFavori(<?= $emploi['id'] ?>, this)" 
                        title="<?= t('Retirer des favoris') ?>">
                    <i class="bi bi-bookmark-fill"></i>
                </button>
                <span class="ms-auto" style="font-size:.7rem;color:var(--text-3)">
                    <?= t('Sauvegardé le') ?> <?= formatDateFr($emploi['date_ajout']) ?>
                </span>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<script>
// Fonction pour ajouter ou retirer un favori (réutilisée depuis emplois.php)
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
            if (action === 'retirer') {
                // Retirer visuellement la carte
                const carte = bouton.closest('.emploi-card');
                if (carte) {
                    carte.style.transition = 'opacity 0.3s';
                    carte.style.opacity = '0';
                    setTimeout(() => {
                        carte.remove();
                        // Vérifier s'il reste des favoris
                        const favorisRestants = document.querySelectorAll('.emploi-card');
                        if (favorisRestants.length === 0) {
                            location.reload(); // Recharger pour afficher le message vide
                        }
                    }, 300);
                }
                afficherToast(t('Offre retirée des favoris'), 'info');
            } else {
                bouton.classList.add('active');
                bouton.querySelector('i').classList.remove('bi-bookmark');
                bouton.querySelector('i').classList.add('bi-bookmark-fill');
                afficherToast(t('Offre ajoutée aux favoris'), 'success');
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
