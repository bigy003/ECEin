<?php
/**
 * ECE In - Page d'Accueil (Version Alt - Sidebar Layout)
 * 
 * Cette page affiche :
 * - Le fil d'actualité avec les publications (public, amis, ou propres)
 * - Les événements à venir (carrousel)
 * - Les suggestions de connexions (personnes à connaître)
 * - Les offres d'emploi récentes (sidebar)
 * - Un formulaire pour créer de nouvelles publications
 * 
 * Sécurité : Seules les publications visibles par l'utilisateur sont affichées
 * - Publications publiques : visibles par tous
 * - Publications "amis" : visibles uniquement par les amis acceptés
 * - Publications privées : visibles uniquement par l'auteur
 */
require_once __DIR__ . '/config/config.php';
requireConnexion(); // Vérifie que l'utilisateur est connecté

$pdo         = getDB(); // Connexion à la base de données
$userId      = $_SESSION['utilisateur_id']; // ID de l'utilisateur connecté
$userCourant = getUtilisateurConnecte(); // Récupère les infos complètes de l'utilisateur
$pageTitle   = 'Accueil'; // Titre de la page
$pageScript  = 'posts.js'; // Script JavaScript à charger (gestion des publications)

// ===================== RÉCUPÉRER LES PUBLICATIONS =====================
/**
 * Récupère les publications à afficher dans le fil d'actualité
 * 
 * Critères d'affichage (OR) :
 * 1. Publications publiques (visibilite = 'public')
 * 2. Publications de l'utilisateur lui-même
 * 3. Publications "amis" où l'auteur est un ami accepté de l'utilisateur
 * 
 * Pour chaque publication, on récupère :
 * - Les infos de base (contenu, type, date, etc.)
 * - Les infos de l'auteur (nom, prénom, photo, pseudo, titre)
 * - Le nombre de réactions et commentaires
 * - La réaction de l'utilisateur actuel (s'il en a une)
 * - Les infos de la publication originale (si c'est un partage)
 * 
 * Limite : 30 publications les plus récentes
 */
$stmtPosts = $pdo->prepare("
    SELECT p.*, u.nom, u.prenom, u.photo AS avatar, u.pseudo, u.titre AS user_titre,
           (SELECT COUNT(*) FROM reactions r WHERE r.publication_id = p.id) AS nb_reactions,
           (SELECT COUNT(*) FROM commentaires c WHERE c.publication_id = p.id) AS nb_commentaires,
           (SELECT type FROM reactions r2 WHERE r2.publication_id = p.id AND r2.utilisateur_id = :uid) AS ma_reaction,
           po.contenu AS original_contenu, po.fichier AS original_fichier,
           uo.prenom AS original_prenom, uo.nom AS original_nom
    FROM publications p
    JOIN utilisateurs u ON u.id = p.utilisateur_id
    LEFT JOIN publications po ON po.id = p.publication_originale_id
    LEFT JOIN utilisateurs uo ON uo.id = po.utilisateur_id
    WHERE p.visibilite = 'public'
       OR p.utilisateur_id = :uid2
       OR (p.visibilite = 'amis' AND p.utilisateur_id IN (
            SELECT CASE WHEN demandeur_id = :uid3 THEN destinataire_id ELSE demandeur_id END
            FROM connexions
            WHERE (demandeur_id = :uid4 OR destinataire_id = :uid5) AND statut = 'accepte'
       ))
    ORDER BY p.date_publication DESC
    LIMIT 30
");
$stmtPosts->execute([
    ':uid'  => $userId,
    ':uid2' => $userId,
    ':uid3' => $userId,
    ':uid4' => $userId,
    ':uid5' => $userId,
]);
$publications = $stmtPosts->fetchAll();

// ===================== ÉVÉNEMENTS À VENIR =====================
/**
 * Récupère les événements à venir
 * 
 * Critères :
 * - Date de début dans le futur (>= NOW())
 * - Tri : événements officiels en premier, puis par date croissante
 * 
 * Limite : 5 événements maximum (augmenté pour afficher plus d'événements)
 */
$stmtEvt = $pdo->prepare("
    SELECT e.*, u.nom, u.prenom
    FROM evenements e
    JOIN utilisateurs u ON u.id = e.organisateur_id
    WHERE e.date_debut >= NOW()
    ORDER BY e.est_officiel DESC, e.date_debut ASC
    LIMIT 5
");
$stmtEvt->execute();
$evenements = $stmtEvt->fetchAll();

// ===================== SUGGESTIONS DE CONNEXIONS =====================
/**
 * Récupère des suggestions d'utilisateurs à connecter
 * 
 * Critères :
 * - Utilisateurs différents de l'utilisateur actuel
 * - Utilisateurs actifs uniquement
 * - Utilisateurs avec qui l'utilisateur n'a pas encore de connexion
 *   (ni en attente, ni acceptée, ni refusée)
 * 
 * Tri : aléatoire (RAND()) pour varier les suggestions
 * Limite : 5 suggestions maximum
 */
$stmtSugg = $pdo->prepare("
    SELECT u.id, u.nom, u.prenom, u.pseudo, u.photo, u.titre
    FROM utilisateurs u
    WHERE u.id != :uid
      AND u.actif = 1
      AND u.id NOT IN (
          SELECT CASE WHEN demandeur_id = :uid2 THEN destinataire_id ELSE demandeur_id END
          FROM connexions
          WHERE (demandeur_id = :uid3 OR destinataire_id = :uid4)
      )
    ORDER BY RAND()
    LIMIT 5
");
$stmtSugg->execute([':uid' => $userId, ':uid2' => $userId, ':uid3' => $userId, ':uid4' => $userId]);
$suggestions = $stmtSugg->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<!-- ===== ACCUEIL : BENTO GRID ===== -->

<!-- Greeting + quick stats -->
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Bonjour, <?= h($userCourant['prenom']) ?></h4>
        <p class="mb-0" style="color:var(--text-3);font-size:.85rem">Voici ce qu'il se passe dans votre réseau aujourd'hui.</p>
    </div>
    <button class="btn btn-ecein-primary" data-bs-toggle="modal" data-bs-target="#modalPublier">
        <i class="bi bi-plus-lg me-1"></i>Publier
    </button>
</div>

<!-- Bento top row : événements + suggestions -->
<div class="row g-3 mb-4">
    <?php if (!empty($evenements)): ?>
    <div class="col-lg-7">
        <div class="glass p-0 overflow-hidden h-100">
            <div class="p-3 pb-2 d-flex align-items-center justify-content-between" style="border-bottom:1px solid var(--glass-border)">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-calendar-event" style="color:var(--accent)"></i>
                    <span class="fw-semibold" style="font-size:.85rem">Événements à venir</span>
                </div>
                <?php if (estAdmin()): ?>
                <button class="btn btn-sm btn-ecein-primary" data-bs-toggle="modal" data-bs-target="#modalPublierEvenement">
                    <i class="bi bi-plus-lg me-1"></i>Publier
                </button>
                <?php endif; ?>
            </div>
            <div id="carouselEvenements" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php foreach ($evenements as $idx => $evt): ?>
                    <div class="carousel-item <?= $idx === 0 ? 'active' : '' ?>">
                        <div class="p-4" style="cursor:pointer" 
                             data-evenement='<?= htmlspecialchars(json_encode($evt), ENT_QUOTES, 'UTF-8') ?>'
                             onclick="afficherDetailsEvenement(JSON.parse(this.getAttribute('data-evenement')))">
                            <div class="d-flex gap-3 align-items-start">
                                <div class="evenement-date-badge text-center flex-shrink-0">
                                    <div class="evt-mois"><?= strtoupper(date('M', strtotime($evt['date_debut']))) ?></div>
                                    <div class="evt-jour"><?= date('d', strtotime($evt['date_debut'])) ?></div>
                                </div>
                                <div class="flex-grow-1">
                                    <?php if ($evt['est_officiel']): ?>
                                    <span class="badge bg-ecein mb-1" style="font-size:.65rem">Officiel ECE</span>
                                    <?php endif; ?>
                                    <h6 class="fw-bold mb-1"><?= h($evt['titre']) ?></h6>
                                    <p class="mb-1 small" style="color:var(--text-3)">
                                        <i class="bi bi-geo-alt me-1"></i><?= h($evt['lieu'] ?? 'Lieu non précisé') ?>
                                    </p>
                                    <p class="small mb-0" style="color:var(--text-2)"><?= h(substr($evt['description'] ?? '', 0, 120)) ?>...</p>
                                    <p class="small mt-2 mb-0" style="color:var(--accent)">
                                        <i class="bi bi-arrow-right me-1"></i>Cliquer pour voir plus
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($evenements) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#carouselEvenements" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#carouselEvenements" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="<?= !empty($evenements) ? 'col-lg-5' : 'col-12' ?>">
        <?php if (!empty($suggestions)): ?>
        <div class="glass p-0 h-100">
            <div class="p-3 pb-2 d-flex align-items-center gap-2" style="border-bottom:1px solid var(--glass-border)">
                <i class="bi bi-person-plus" style="color:var(--teal)"></i>
                <span class="fw-semibold" style="font-size:.85rem">Personnes à connaître</span>
            </div>
            <div class="p-3 pt-2">
                <?php foreach ($suggestions as $sugg): ?>
                <div class="d-flex align-items-center gap-2 py-2 hover-bg rounded-3 px-2">
                    <a href="utilisateur.php?id=<?= $sugg['id'] ?>">
                        <img src="<?= h($sugg['photo']) ?>" alt=""
                             class="rounded-3" width="40" height="40" style="object-fit:cover">
                    </a>
                    <div class="flex-grow-1 min-w-0">
                        <a href="utilisateur.php?id=<?= $sugg['id'] ?>" class="text-decoration-none" style="color:var(--text)">
                            <div class="fw-semibold small text-truncate"><?= h($sugg['prenom'] . ' ' . $sugg['nom']) ?></div>
                        </a>
                        <div style="font-size:.72rem;color:var(--text-3)"><?= h(substr($sugg['titre'] ?? 'Membre ECE', 0, 35)) ?></div>
                    </div>
                    <button class="btn btn-sm btn-outline-primary rounded-pill px-2"
                            onclick="envoyerDemande(<?= $sugg['id'] ?>, this)">
                        <i class="bi bi-person-plus" style="font-size:.8rem"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Zone de publication -->
<div class="glass p-3 mb-4">
    <div class="d-flex gap-3 align-items-center">
        <img src="<?= h($userCourant['photo']) ?>" alt=""
             class="rounded-3" width="44" height="44" style="object-fit:cover;border:2px solid var(--accent)">
        <button class="btn btn-light flex-grow-1 text-start px-4" style="border-radius:var(--radius)"
                data-bs-toggle="modal" data-bs-target="#modalPublier">
            <span style="color:var(--text-3)">Quoi de neuf, <?= h($userCourant['prenom']) ?> ?</span>
        </button>
    </div>
    <div class="d-flex justify-content-around mt-3 pt-2" style="border-top:1px solid var(--glass-border)">
        <button class="btn btn-sm btn-light d-flex align-items-center gap-2"
                data-bs-toggle="modal" data-bs-target="#modalPublier" data-type="photo">
            <i class="bi bi-image" style="color:var(--emerald)"></i>
            <span class="d-none d-sm-inline small">Photo</span>
        </button>
        <button class="btn btn-sm btn-light d-flex align-items-center gap-2"
                data-bs-toggle="modal" data-bs-target="#modalPublier" data-type="video">
            <i class="bi bi-camera-video" style="color:var(--rose)"></i>
            <span class="d-none d-sm-inline small">Vidéo</span>
        </button>
        <button class="btn btn-sm btn-light d-flex align-items-center gap-2"
                data-bs-toggle="modal" data-bs-target="#modalPublier" data-type="evenement">
            <i class="bi bi-calendar-plus" style="color:var(--accent)"></i>
            <span class="d-none d-sm-inline small">Événement</span>
        </button>
        <button class="btn btn-sm btn-light d-flex align-items-center gap-2"
                data-bs-toggle="modal" data-bs-target="#modalPublier" data-type="cv">
            <i class="bi bi-file-person" style="color:var(--sky)"></i>
            <span class="d-none d-sm-inline small">CV</span>
        </button>
    </div>
</div>

<!-- Fil de publications -->
<div class="row g-3">
    <div class="col-lg-8">
        <div id="fil-publications">
            <?php if (empty($publications)): ?>
            <div class="glass empty-state">
                <i class="bi bi-chat-square-dots"></i>
                <p>Aucune publication pour l'instant. Commencez à partager !</p>
            </div>
            <?php endif; ?>

            <?php foreach ($publications as $post): ?>
            <?php include __DIR__ . '/includes/post_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Sidebar emplois -->
    <div class="col-lg-4 d-none d-lg-block">
        <div class="glass p-0" style="position:sticky;top:70px">
            <div class="p-3 pb-2 d-flex align-items-center gap-2" style="border-bottom:1px solid var(--glass-border)">
                <i class="bi bi-briefcase" style="color:var(--accent)"></i>
                <span class="fw-semibold" style="font-size:.85rem">Offres récentes</span>
            </div>
            <div class="p-3 pt-2">
                <?php
                $stmtEmplois = $pdo->prepare("SELECT e.*, u.nom, u.prenom FROM emplois e LEFT JOIN utilisateurs u ON u.id = e.publie_par WHERE e.actif = 1 ORDER BY e.date_publication DESC LIMIT 4");
                $stmtEmplois->execute();
                foreach ($stmtEmplois->fetchAll() as $emploi): ?>
                <div class="d-block text-decoration-none py-2 hover-bg rounded-3 px-2" 
                     style="cursor:pointer"
                     data-emploi='<?= htmlspecialchars(json_encode($emploi), ENT_QUOTES, 'UTF-8') ?>'
                     onclick="afficherDetailsEmploi(JSON.parse(this.getAttribute('data-emploi')))">
                    <div class="fw-semibold small" style="color:var(--text)"><?= h($emploi['titre']) ?></div>
                    <div style="font-size:.72rem;color:var(--text-3)"><?= h($emploi['entreprise']) ?></div>
                    <span class="badge bg-secondary mt-1" style="font-size:.6rem"><?= h(ucfirst($emploi['type'])) ?></span>
                    <div style="font-size:.7rem;color:var(--accent);margin-top:4px">
                        <i class="bi bi-arrow-right me-1"></i>Cliquer pour voir plus
                    </div>
                </div>
                <?php endforeach; ?>
                <a href="emplois.php" class="btn btn-sm btn-light w-100 mt-2">Toutes les offres <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL : Publier ===== -->
<div class="modal fade" id="modalPublier" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Créer une publication</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPublier" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="publier">
                    <input type="hidden" name="type" id="post-type" value="statut">

                    <div class="btn-group mb-3 w-100" role="group">
                        <input type="radio" class="btn-check" name="type_post" id="type-statut" value="statut" checked>
                        <label class="btn btn-outline-secondary" for="type-statut"><i class="bi bi-pencil me-1"></i>Statut</label>
                        <input type="radio" class="btn-check" name="type_post" id="type-photo" value="photo">
                        <label class="btn btn-outline-secondary" for="type-photo"><i class="bi bi-image me-1"></i>Photo</label>
                        <input type="radio" class="btn-check" name="type_post" id="type-video" value="video">
                        <label class="btn btn-outline-secondary" for="type-video"><i class="bi bi-camera-video me-1"></i>Vidéo</label>
                        <input type="radio" class="btn-check" name="type_post" id="type-cv" value="cv">
                        <label class="btn btn-outline-secondary" for="type-cv"><i class="bi bi-file-person me-1"></i>CV</label>
                    </div>

                    <div class="d-flex gap-3 mb-3">
                        <img src="<?= h($userCourant['photo']) ?>" alt="" class="rounded-3" width="44" height="44" style="object-fit:cover">
                        <div class="flex-grow-1">
                            <div class="fw-semibold small mb-1"><?= h($userCourant['prenom'] . ' ' . $userCourant['nom']) ?></div>
                            <select name="visibilite" class="form-select form-select-sm d-inline-block w-auto">
                                <option value="public">Public</option>
                                <option value="amis">Amis</option>
                                <option value="prive">Privé</option>
                            </select>
                        </div>
                    </div>

                    <div class="position-relative">
                        <textarea name="contenu" id="input-publier-message" class="form-control border-0" rows="4"
                                  placeholder="Que souhaitez-vous partager ?" style="resize:none;font-size:1rem;background:var(--bg-3)"></textarea>
                        
                        <!-- Emoji Picker Button -->
                        <div class="position-absolute bottom-0 end-0 p-2">
                            <button type="button" class="btn btn-sm btn-light rounded-circle" id="btn-emoji-publier" title="Ajouter un emoji">
                                <i class="bi bi-emoji-smile text-muted"></i>
                            </button>
                        </div>
                        
                        <!-- Emoji Picker Container -->
                        <div id="emoji-picker-container-publier" style="display: none; position: absolute; bottom: 100%; right: 0; z-index: 1060; margin-bottom: 5px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); border-radius: 8px; background: white; overflow: hidden;">
                            <emoji-picker></emoji-picker>
                        </div>
                    </div>

                    <div id="zone-upload" class="mt-3" style="display:none">
                        <div class="upload-zone text-center p-4" id="dropzone">
                            <i class="bi bi-cloud-upload fs-2" style="color:var(--text-3)"></i>
                            <p class="mt-2 small" style="color:var(--text-3)">Glissez-déposez votre fichier ici ou</p>
                            <label class="btn btn-sm btn-outline-primary">
                                Choisir un fichier
                                <input type="file" name="fichier" id="input-fichier" class="d-none">
                            </label>
                            <div id="preview-fichier" class="mt-3 position-relative"></div>
                        </div>
                    </div>

                    <div class="row g-2 mt-3">
                        <div class="col-sm-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" name="lieu" class="form-control" placeholder="Lieu">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-emoji-smile"></i></span>
                                <input type="text" name="humeur" class="form-control" placeholder="Ce que vous ressentez...">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="btn-publier" class="btn btn-ecein-primary px-4">
                        <i class="bi bi-send me-2"></i>Publier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Déterminer le chemin de base en fonction du dossier courant pour les scripts et images du footer
$basePath = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../' : '';
?>
<script>
// --- CONFIGURATION EMOJIS (MODAL PUBLICATION) ---
document.addEventListener('DOMContentLoaded', () => {
    const btnEmojiPublier = document.querySelector('#btn-emoji-publier');
    const inputMsgPublier = document.querySelector('#input-publier-message');
    const emojiContainerPublier = document.querySelector('#emoji-picker-container-publier');
    // We assume emoji-picker web component is loaded in footer
    const pickerPublier = emojiContainerPublier ? emojiContainerPublier.querySelector('emoji-picker') : null;
    
    if (btnEmojiPublier && inputMsgPublier && pickerPublier) {
        // Toggle the picker when the button is clicked
        btnEmojiPublier.addEventListener('click', (e) => {
            e.preventDefault();
            emojiContainerPublier.style.display = emojiContainerPublier.style.display === 'none' ? 'block' : 'none';
        });

        // Insert emoji when selected
        pickerPublier.addEventListener('emoji-click', event => {
            // Insert at cursor position or end if no cursor
            const start = inputMsgPublier.selectionStart;
            const end = inputMsgPublier.selectionEnd;
            const text = inputMsgPublier.value;
            inputMsgPublier.value = text.substring(0, start) + event.detail.unicode + text.substring(end);
            
            // Move cursor after emoji
            const newPos = start + event.detail.unicode.length;
            inputMsgPublier.setSelectionRange(newPos, newPos);
            inputMsgPublier.focus();
        });
        
        // Hide picker when clicking outside
        document.addEventListener('click', event => {
            if (!emojiContainerPublier.contains(event.target) && event.target !== btnEmojiPublier && !btnEmojiPublier.contains(event.target)) {
                emojiContainerPublier.style.display = 'none';
            }
        });
    }
});
</script>

<!-- Modal Publier Événement (Admin uniquement) -->
<?php if (estAdmin()): ?>
<div class="modal fade" id="modalPublierEvenement" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-calendar-event me-2"></i>Publier un événement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPublierEvenement">
                <input type="hidden" name="action" value="creer">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Titre de l'événement *</label>
                            <input type="text" name="titre" class="form-control" required placeholder="Ex: Conférence sur l'IA">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date de début *</label>
                            <input type="datetime-local" name="date_debut" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date de fin</label>
                            <input type="datetime-local" name="date_fin" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Lieu</label>
                            <input type="text" name="lieu" class="form-control" placeholder="Ex: ECE Paris - Salle de conférence">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Décrivez l'événement..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Lien (optionnel)</label>
                            <input type="url" name="lien" class="form-control" placeholder="https://...">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="est_officiel" value="1" id="check-officiel">
                                <label class="form-check-label" for="check-officiel">
                                    Événement officiel ECE
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-ecein-primary" id="btn-submit-evenement">
                        <i class="bi bi-send me-2"></i>Publier l'événement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Gestion du formulaire de publication d'événement
$('#formPublierEvenement').on('submit', function(e) {
    e.preventDefault();
    const formData = $(this).serialize();
    const $btn = $('#btn-submit-evenement');
    const $btnOriginal = $btn.html();
    
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Publication...');
    
    $.post('api/evenements.php', formData, function(res) {
        if (res.succes) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalPublierEvenement'));
            if (modal) modal.hide();
            afficherToast(res.message, 'success');
            // Recharger la page après un court délai pour afficher le nouvel événement
            setTimeout(function() {
                location.reload();
            }, 500);
        } else {
            afficherToast(res.message, 'danger');
            $btn.prop('disabled', false).html($btnOriginal);
        }
    }).fail(function(xhr, status, error) {
        console.error('Erreur AJAX:', {status, error, xhr});
        afficherToast('Erreur lors de la publication de l\'événement.', 'danger');
        $btn.prop('disabled', false).html($btnOriginal);
    });
    
    return false;
});

// Réinitialiser le formulaire quand le modal se ferme
$('#modalPublierEvenement').on('hidden.bs.modal', function() {
    $('#formPublierEvenement')[0].reset();
    $('#btn-submit-evenement').prop('disabled', false).html('<i class="bi bi-send me-2"></i>Publier l\'événement');
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
