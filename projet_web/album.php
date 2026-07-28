<?php
/**
 * ECE In - Page Album : Voir un album et ses photos
 */
require_once __DIR__ . '/config/config.php';
requireConnexion();

$pdo         = getDB();
$userId      = $_SESSION['utilisateur_id'];
$userCourant = getUtilisateurConnecte();
$albumId     = (int) ($_GET['id'] ?? 0);
$pageTitle   = 'Album';

if (!$albumId) {
    redirect('profil.php?onglet=albums');
}

// Récupérer l'album
$stmt = $pdo->prepare("SELECT * FROM albums WHERE id = ? AND utilisateur_id = ?");
$stmt->execute([$albumId, $userId]);
$album = $stmt->fetch();

if (!$album) {
    redirect('profil.php?onglet=albums');
}

// Récupérer les photos de l'album
$stmtPhotos = $pdo->prepare("
    SELECT p.*, u.nom, u.prenom, u.photo AS avatar, u.pseudo
    FROM publications p
    JOIN album_photos ap ON ap.publication_id = p.id
    JOIN utilisateurs u ON u.id = p.utilisateur_id
    WHERE ap.album_id = ? AND p.type = 'photo' AND p.fichier IS NOT NULL
    ORDER BY p.date_publication DESC
");
$stmtPhotos->execute([$albumId]);
$photos = $stmtPhotos->fetchAll();

// Les photos disponibles seront chargées via AJAX dans le modal

$pageTitle = h($album['nom']);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="profil.php?onglet=albums" class="btn btn-sm btn-light mb-2">
            <i class="bi bi-arrow-left me-1"></i>Retour aux albums
        </a>
        <h4 class="fw-bold mb-1"><?= h($album['nom']) ?></h4>
        <?php if ($album['description']): ?>
        <p class="mb-0" style="color:var(--text-3)"><?= h($album['description']) ?></p>
        <?php endif; ?>
        <p class="small mb-0" style="color:var(--text-3)">
            <i class="bi bi-calendar me-1"></i>Créé le <?= formatDateFr($album['date_creation']) ?>
            <span class="ms-3"><i class="bi bi-images me-1"></i><?= count($photos) ?> photo<?= count($photos) > 1 ? 's' : '' ?></span>
        </p>
    </div>
    <div>
        <button class="btn btn-ecein-primary" onclick="ouvrirModalAjouterPhoto(<?= $albumId ?>)">
            <i class="bi bi-plus-lg me-1"></i>Ajouter des photos
        </button>
    </div>
</div>

<?php if (empty($photos)): ?>
<div class="glass empty-state">
    <i class="bi bi-images"></i>
    <p>Cet album est vide. Ajoutez des photos !</p>
    <button class="btn btn-ecein-primary mt-2" onclick="ouvrirModalAjouterPhoto(<?= $albumId ?>)">
        <i class="bi bi-plus-lg me-1"></i>Ajouter des photos
    </button>
</div>
<?php else: ?>
<div class="row g-3" id="photos-album">
    <?php foreach ($photos as $photo): ?>
    <div class="col-sm-6 col-md-4 col-lg-3" id="photo-<?= $photo['id'] ?>">
        <div class="glass p-0 overflow-hidden position-relative">
            <img src="<?= h($photo['fichier']) ?>" alt=""
                 class="w-100" style="height:200px;object-fit:cover;cursor:pointer"
                 onclick="ouvrirMedia('<?= h($photo['fichier']) ?>')">
            <div class="position-absolute top-0 end-0 m-2">
                <div class="btn-group-vertical">
                    <?php if ($album['couverture'] !== $photo['fichier']): ?>
                    <button class="btn btn-sm btn-light" onclick="definirCouverture(<?= $albumId ?>, <?= $photo['id'] ?>)" title="Définir comme couverture">
                        <i class="bi bi-star"></i>
                    </button>
                    <?php else: ?>
                    <button class="btn btn-sm btn-ecein-primary" title="Photo de couverture">
                        <i class="bi bi-star-fill"></i>
                    </button>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-light" onclick="retirerPhoto(<?= $albumId ?>, <?= $photo['id'] ?>)" title="Retirer de l'album">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            <?php if ($photo['contenu']): ?>
            <div class="p-2">
                <p class="small mb-0 text-truncate" style="color:var(--text-3)"><?= h($photo['contenu']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal Ajouter des photos -->
<div class="modal fade" id="modalAjouterPhoto" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Ajouter des photos à l'album</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="album-id-ajout" value="">
                <div class="row g-2" id="liste-photos-disponibles">
                    <div class="col-12 text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-ecein-primary" id="btn-ajouter-photos" onclick="ajouterPhotosSelectionnees()" style="display:none">
                    <i class="bi bi-plus-lg me-1"></i>Ajouter <span id="nb-photos-selectionnees">0</span> photo(s)
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let albumIdAjout = null;
let photosSelectionnees = [];

function ouvrirModalAjouterPhoto(albumId) {
    albumIdAjout = albumId;
    photosSelectionnees = [];
    document.getElementById('album-id-ajout').value = albumId;
    mettreAJourBoutonAjouter();
    
    // Afficher le spinner
    document.getElementById('liste-photos-disponibles').innerHTML = '<div class="col-12 text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div></div>';
    
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
            container.innerHTML = '<div class="col-12 text-center py-4"><i class="bi bi-images fs-1" style="color:var(--text-3);opacity:.25"></i><p class="mt-2">Aucune photo disponible à ajouter.</p><p class="small text-muted">Publiez d\'abord des photos depuis la page d\'accueil.</p></div>';
        }
    }).fail(function() {
        document.getElementById('liste-photos-disponibles').innerHTML = '<div class="col-12 text-center py-4"><p class="text-danger">Erreur lors du chargement des photos.</p></div>';
    });
    
    const modal = new bootstrap.Modal(document.getElementById('modalAjouterPhoto'));
    modal.show();
}

function ajouterPhotoAlbum(albumId, publicationId, element) {
    if (!albumId) return;
    
    const badge = document.getElementById('badge-' + publicationId);
    if (photosSelectionnees.includes(publicationId)) {
        // Retirer
        photosSelectionnees = photosSelectionnees.filter(id => id !== publicationId);
        badge.style.display = 'none';
        element.style.opacity = '1';
    } else {
        // Ajouter
        photosSelectionnees.push(publicationId);
        badge.style.display = 'block';
        element.style.opacity = '0.7';
    }
    // Mettre à jour le bouton "Ajouter"
    mettreAJourBoutonAjouter();
}

function mettreAJourBoutonAjouter() {
    const btn = document.getElementById('btn-ajouter-photos');
    const nb = document.getElementById('nb-photos-selectionnees');
    if (photosSelectionnees.length > 0) {
        btn.style.display = 'block';
        nb.textContent = photosSelectionnees.length;
    } else {
        btn.style.display = 'none';
    }
}

function ajouterPhotosSelectionnees() {
    if (photosSelectionnees.length === 0) return;
    
    const albumId = document.getElementById('album-id-ajout').value;
    if (!albumId) return;
    
    const btn = document.getElementById('btn-ajouter-photos');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Ajout en cours...';
    
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
                    afficherToast(ajoutes + ' photo(s) ajoutée(s) à l\'album.', 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    afficherToast('Aucune photo n\'a pu être ajoutée.', 'danger');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-plus-lg me-1"></i>Ajouter <span id="nb-photos-selectionnees">' + total + '</span> photo(s)';
                }
            }
        }).fail(function() {
            erreurs++;
            if (ajoutes + erreurs === total) {
                if (ajoutes > 0) {
                    afficherToast(ajoutes + ' photo(s) ajoutée(s), ' + erreurs + ' erreur(s).', 'warning');
                    setTimeout(() => location.reload(), 500);
                } else {
                    afficherToast('Erreur lors de l\'ajout des photos.', 'danger');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-plus-lg me-1"></i>Ajouter <span id="nb-photos-selectionnees">' + total + '</span> photo(s)';
                }
            }
        });
    });
}

function retirerPhoto(albumId, publicationId) {
    if (!confirm('Retirer cette photo de l\'album ?')) return;
    
    $.post('api/albums.php', {
        action: 'retirer_photo',
        album_id: albumId,
        publication_id: publicationId
    }, function(res) {
        if (res.succes) {
            $('#photo-' + publicationId).fadeOut(300, function() { $(this).remove(); });
            afficherToast(res.message, 'success');
            // Mettre à jour le compteur
            const nbPhotos = $('#photos-album > div').length - 1;
            if (nbPhotos === 0) {
                location.reload();
            }
        } else {
            afficherToast(res.message, 'danger');
        }
    });
}

function definirCouverture(albumId, publicationId) {
    $.post('api/albums.php', {
        action: 'definir_couverture',
        album_id: albumId,
        publication_id: publicationId
    }, function(res) {
        if (res.succes) {
            afficherToast(res.message, 'success');
            setTimeout(() => location.reload(), 500);
        } else {
            afficherToast(res.message, 'danger');
        }
    });
}

// Réinitialiser la sélection quand on ferme le modal
document.getElementById('modalAjouterPhoto').addEventListener('hidden.bs.modal', function() {
    photosSelectionnees = [];
    albumIdAjout = null;
    mettreAJourBoutonAjouter();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
