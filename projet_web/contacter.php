<?php
/**
 * ECE In - Page Contacter le Recruteur
 * 
 * Affiche les informations de contact et permet d'ouvrir Outlook
 */
require_once __DIR__ . '/config/config.php';
requireConnexion();

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$userCourant = getUtilisateurConnecte();
$emploiId = (int) ($_GET['id'] ?? 0);
$pageTitle = 'Contacter le recruteur';

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

if (!$emploi || !$emploi['contact_email']) {
    redirect('emplois.php');
}

// Préparer l'email
$sujet = 'Candidature - ' . $emploi['titre'];
$corps = "Bonjour,\n\n" .
         "Je suis intéressé(e) par votre offre : " . $emploi['titre'] . "\n" .
         "Entreprise : " . $emploi['entreprise'] . "\n\n" .
         "Je souhaiterais obtenir plus d'informations à ce sujet.\n\n" .
         "Cordialement,";

// Encoder pour mailto:
$mailto = 'mailto:' . urlencode($emploi['contact_email']) . 
          '?subject=' . urlencode($sujet) . 
          '&body=' . urlencode($corps);

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
                        <i class="bi bi-envelope me-2" style="color:var(--accent)"></i>Contacter le recruteur
                    </h5>
                    <p class="mb-0 small" style="color:var(--text-3)">
                        Envoyez un email au recruteur
                    </p>
                </div>
                <a href="emplois.php" class="btn btn-sm btn-light">
                    <i class="bi bi-arrow-left me-1"></i>Retour
                </a>
            </div>

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
                    </div>
                </div>
            </div>

            <!-- Informations de contact -->
            <div class="glass p-4">
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-person me-2" style="color:var(--accent)"></i>
                    Informations de contact
                </h6>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Email du recruteur</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="email-contact" 
                               value="<?= h($emploi['contact_email']) ?>" readonly>
                        <button class="btn btn-outline-primary" onclick="copierEmail()">
                            <i class="bi bi-clipboard me-1"></i>Copier
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Sujet de l'email</label>
                    <input type="text" class="form-control" id="sujet-email" 
                           value="<?= h($sujet) ?>" readonly>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Message (pré-rempli)</label>
                    <textarea class="form-control" rows="8" id="corps-email" readonly><?= h($corps) ?></textarea>
                </div>

                <div class="d-flex gap-2">
                    <a href="<?= h($mailto) ?>" class="btn btn-ecein-primary" id="btn-outlook">
                        <i class="bi bi-envelope me-2"></i>Ouvrir dans Outlook / Client email
                    </a>
                    <button class="btn btn-outline-primary" onclick="copierTout()">
                        <i class="bi bi-clipboard me-1"></i>Copier les informations
                    </button>
                </div>

                <div class="alert alert-info mt-3 mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Note :</strong> Si Outlook ne s'ouvre pas automatiquement, copiez les informations ci-dessus et créez un email manuellement dans Outlook.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copierEmail() {
    const email = document.getElementById('email-contact');
    email.select();
    document.execCommand('copy');
    
    // Afficher un message de confirmation
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check me-1"></i>Copié !';
    btn.classList.add('btn-success');
    btn.classList.remove('btn-outline-primary');
    
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-primary');
    }, 2000);
}

function copierTout() {
    const email = document.getElementById('email-contact').value;
    const sujet = document.getElementById('sujet-email').value;
    const corps = document.getElementById('corps-email').value;
    
    const texte = `Email : ${email}\n\nSujet : ${sujet}\n\nMessage :\n${corps}`;
    
    navigator.clipboard.writeText(texte).then(() => {
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check me-1"></i>Copié !';
        btn.classList.add('btn-success');
        btn.classList.remove('btn-outline-primary');
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-primary');
        }, 2000);
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

