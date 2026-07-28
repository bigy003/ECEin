<?php
/**
 * ECE In - En-tête HTML (Version Alt - Sidebar Layout)
 */
require_once __DIR__ . '/../config/config.php';

$jourSpecial = getJourSpecial();
$themeClass  = $jourSpecial['theme'] ? 'theme-' . $jourSpecial['theme'] : '';

if (estConnecte()) {
    $nbNotifs   = getNbNotificationsNonLues();
    $nbMessages = getNbMessagesNonLus();
    $userCourant = getUtilisateurConnecte();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= SITE_SLOGAN ?>">
    <meta name="theme-color" content="#111118">
    <title><?= isset($pageTitle) ? h($pageTitle) . ' - ' : '' ?><?= SITE_NOM ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <?php
    // Déterminer le chemin de base en fonction du dossier courant
    $basePath = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../' : '';
    ?>
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/style.css">

    <?php if ($themeClass): ?>
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/themes/<?= $jourSpecial['theme'] ?>.css">
    <?php endif; ?>
</head>
<body class="<?= $themeClass ?>">
<script>
    // Appliquer le thème sauvegardé dès le chargement pour éviter le clignotement
    window.currentUserIdTheme = '<?= isset($_SESSION['utilisateur_id']) ? $_SESSION['utilisateur_id'] : 'guest' ?>';
    window.currentUserId = <?= isset($_SESSION['utilisateur_id']) ? (int)$_SESSION['utilisateur_id'] : 'null' ?>;
    const themeKey = 'ecein_theme_' + window.currentUserIdTheme;
    const savedTheme = localStorage.getItem(themeKey);
    if (savedTheme && savedTheme !== 'bleu') {
        document.body.classList.add('theme-' + savedTheme);
    }
</script>

<?php if ($jourSpecial['message']): ?>
<div class="alerte-jour-special alert alert-dismissible fade show mb-0" role="alert">
    <div class="text-center fw-bold"><?= $jourSpecial['message'] ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
