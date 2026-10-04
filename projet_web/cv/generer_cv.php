<?php
/**
 * ECE In - Génération automatique du CV (Version Alt - Warm Dark)
 * Formats supportés : html, pdf (via mPDF si disponible), xml (export)
 */
require_once __DIR__ . '/../config/config.php';
requireConnexion();

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$format  = $_GET['format'] ?? 'html';
$preview = isset($_GET['preview']);

$stmtUser = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch();

$stmtForm = $pdo->prepare("SELECT * FROM formations WHERE utilisateur_id = ? ORDER BY date_debut DESC");
$stmtForm->execute([$userId]);
$formations = $stmtForm->fetchAll();

$stmtProj = $pdo->prepare("SELECT * FROM projets WHERE utilisateur_id = ? ORDER BY date_debut DESC");
$stmtProj->execute([$userId]);
$projets = $stmtProj->fetchAll();

// Récupérer les compétences de l'utilisateur depuis la base de données
$stmtCompetences = $pdo->prepare("
    SELECT nom, niveau
    FROM competences
    WHERE utilisateur_id = ?
    ORDER BY ordre ASC, date_ajout ASC
");
$stmtCompetences->execute([$userId]);
$competences = $stmtCompetences->fetchAll(PDO::FETCH_COLUMN, 0); // Récupérer uniquement les noms

// ===================== EXPORT XML =====================
if ($format === 'xml') {
    header('Content-Type: application/xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="cv_' . $user['pseudo'] . '.xml"');

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo "<cv>\n";
    echo "  <informations_personnelles>\n";
    echo "    <prenom>" . htmlspecialchars($user['prenom']) . "</prenom>\n";
    echo "    <nom>" . htmlspecialchars($user['nom']) . "</nom>\n";
    echo "    <email>" . htmlspecialchars($user['email']) . "</email>\n";
    echo "    <telephone>" . htmlspecialchars($user['telephone'] ?? '') . "</telephone>\n";
    echo "    <localisation>" . htmlspecialchars($user['localisation'] ?? '') . "</localisation>\n";
    echo "    <site_web>" . htmlspecialchars($user['site_web'] ?? '') . "</site_web>\n";
    echo "    <titre>" . htmlspecialchars($user['titre'] ?? '') . "</titre>\n";
    echo "    <biographie>" . htmlspecialchars($user['biographie'] ?? '') . "</biographie>\n";
    echo "  </informations_personnelles>\n";

    echo "  <formations>\n";
    foreach ($formations as $f) {
        echo "    <formation>\n";
        echo "      <etablissement>" . htmlspecialchars($f['etablissement']) . "</etablissement>\n";
        echo "      <diplome>" . htmlspecialchars($f['diplome']) . "</diplome>\n";
        echo "      <domaine>" . htmlspecialchars($f['domaine'] ?? '') . "</domaine>\n";
        echo "      <date_debut>" . htmlspecialchars($f['date_debut']) . "</date_debut>\n";
        echo "      <date_fin>" . htmlspecialchars($f['date_fin'] ?? '') . "</date_fin>\n";
        echo "      <en_cours>" . ($f['en_cours'] ? 'true' : 'false') . "</en_cours>\n";
        echo "      <description>" . htmlspecialchars($f['description'] ?? '') . "</description>\n";
        echo "    </formation>\n";
    }
    echo "  </formations>\n";

    echo "  <projets>\n";
    foreach ($projets as $p) {
        echo "    <projet>\n";
        echo "      <titre>" . htmlspecialchars($p['titre']) . "</titre>\n";
        echo "      <description>" . htmlspecialchars($p['description'] ?? '') . "</description>\n";
        echo "      <type>" . htmlspecialchars($p['type'] ?? '') . "</type>\n";
        echo "      <lien>" . htmlspecialchars($p['lien'] ?? '') . "</lien>\n";
        echo "    </projet>\n";
    }
    echo "  </projets>\n";

    echo "  <competences>\n";
    foreach ($competences as $comp) {
        echo "    <competence>" . htmlspecialchars($comp) . "</competence>\n";
    }
    echo "  </competences>\n";

    echo "</cv>\n";
    exit();
}

// ===================== GÉNÉRATION HTML / PDF =====================

$isPDF = ($format === 'pdf');

if (!$preview && !$isPDF) {
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="cv_' . $user['pseudo'] . '.html"');
}

$moisFr = [
    1 => 'jan', 2 => 'fév', 3 => 'mar', 4 => 'avr', 5 => 'mai', 6 => 'juin',
    7 => 'juil', 8 => 'août', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'déc',
];

function formatCVDate($date) {
    global $moisFr;
    if (!$date) return '';
    if (LANG === 'en') return date('M Y', strtotime($date));
    return $moisFr[(int)date('n', strtotime($date))] . ' ' . date('Y', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="<?= LANG ?>">
<head>
    <meta charset="UTF-8">
    <title>CV · <?= h($user['prenom'] . ' ' . $user['nom']) ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Poppins', 'Segoe UI', Calibri, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #eae8e4;
            background: #111118;
        }
        .cv-wrapper { max-width: 800px; margin: 0 auto; padding: 30px; }

        .cv-header {
            display: flex;
            align-items: flex-start;
            gap: 24px;
            padding-bottom: 20px;
            border-bottom: 3px solid;
            border-image: linear-gradient(135deg, #02B8C5, #007179) 1;
            margin-bottom: 24px;
        }
        .cv-photo {
            width: 90px; height: 90px; border-radius: 14px;
            object-fit: cover; border: 3px solid #02B8C5;
            box-shadow: 0 0 15px rgba(2, 184, 197, .25);
        }
        .cv-photo-placeholder {
            width: 90px; height: 90px; border-radius: 14px;
            background: linear-gradient(135deg, #02B8C5, #007179);
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; color: #fff; font-weight: 900;
            border: 3px solid #02B8C5; flex-shrink: 0;
            box-shadow: 0 0 15px rgba(2, 184, 197, .25);
        }
        .cv-nom { font-size: 22px; font-weight: 700; color: #eae8e4; margin-bottom: 2px; }
        .cv-titre { font-size: 14px; color: #02B8C5; font-weight: 600; margin-bottom: 8px; }
        .cv-contacts { display: flex; flex-wrap: wrap; gap: 12px; font-size: 11px; color: #b0adb5; }
        .cv-contact-item { display: flex; align-items: center; gap: 4px; }

        .cv-section { margin-bottom: 20px; }
        .cv-section-title {
            font-size: 13px; font-weight: 700; color: #02B8C5;
            text-transform: uppercase; letter-spacing: 0.5px;
            border-bottom: 1.5px solid rgba(2, 184, 197, .3);
            padding-bottom: 4px; margin-bottom: 12px;
        }

        .cv-item { display: flex; gap: 16px; margin-bottom: 14px; }
        .cv-item-dates { width: 110px; min-width: 110px; font-size: 10.5px; color: #6e6b78; text-align: right; padding-top: 2px; }
        .cv-item-content { flex: 1; }
        .cv-item-title { font-weight: 700; font-size: 12.5px; color: #eae8e4; }
        .cv-item-subtitle { font-weight: 600; font-size: 11.5px; color: #02B8C5; }
        .cv-item-desc { font-size: 11px; color: #b0adb5; margin-top: 3px; line-height: 1.4; }
        .cv-item-desc a { color: #02B8C5; }

        .competences-grid { display: flex; flex-wrap: wrap; gap: 8px; }
        .competence-badge {
            background: rgba(2, 184, 197, .1); color: #02B8C5;
            border: 1px solid rgba(2, 184, 197, .3);
            padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500;
        }

        .cv-bio { font-size: 11.5px; color: #b0adb5; line-height: 1.6; font-style: italic; }

        .cv-footer {
            text-align: center; font-size: 10px; color: #6e6b78;
            border-top: 1px solid rgba(2, 184, 197, .25);
            padding-top: 12px; margin-top: 24px;
        }

        @media print {
            .no-print { display: none !important; }
            body { background: white; color: #222; }
            .cv-nom { color: #1b1b26; }
            .cv-titre { color: #02B8C5; }
            .cv-section-title { color: #02B8C5; border-bottom-color: #ddd; }
            .cv-item-title { color: #222; }
            .cv-item-subtitle { color: #02B8C5; }
            .cv-item-desc { color: #555; }
            .cv-contacts { color: #555; }
            .cv-bio { color: #444; }
            .competence-badge { background: #e0f7fa; color: #007179; border-color: #02B8C5; }
            .cv-footer { color: #aaa; border-top-color: #eee; }
            .cv-photo { border-color: #02B8C5; box-shadow: none; }
            .cv-photo-placeholder { background: #e0f7fa; color: #007179; border-color: #02B8C5; box-shadow: none; }
        }

        .btn-print {
            display: inline-flex; align-items: center; gap: 6px;
            background: linear-gradient(135deg, #02B8C5, #007179);
            color: #fff; border: none; padding: 8px 16px;
            border-radius: 20px; cursor: pointer; font-size: 13px;
            font-weight: 600; margin-bottom: 16px;
        }
        .btn-print:hover { filter: brightness(1.1); }
    </style>
</head>
<body>
<div class="cv-wrapper">

    <?php if ($preview): ?>
    <div class="no-print" style="margin-bottom:16px">
        <button class="btn-print" onclick="window.print()">
            🖨 <?= t('Imprimer / Enregistrer en PDF') ?>
        </button>
        <a href="generer_cv.php?format=xml"
           style="margin-left:8px;font-size:12px;color:#02B8C5">⬇ <?= t('Exporter en XML') ?></a>
    </div>
    <?php endif; ?>

    <div class="cv-header">
        <?php
        // Toujours afficher la photo de profil (même si c'est la photo par défaut)
        // La photo de profil est stockée dans $user['photo']
        if (!empty($user['photo'])):
            // Construire le chemin relatif depuis le dossier cv/
            $photoPath = '../' . $user['photo'];
            // Vérifier que le fichier existe
            $photoSrc = BASE_PATH . '/' . $user['photo'];
            if (file_exists($photoSrc)):
        ?>
        <img src="<?= h($photoPath) ?>" alt="<?= t('Photo de profil') ?>" class="cv-photo">
        <?php else: ?>
        <div class="cv-photo-placeholder">
            <?= strtoupper(mb_substr($user['prenom'], 0, 1) . mb_substr($user['nom'], 0, 1)) ?>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div class="cv-photo-placeholder">
            <?= strtoupper(mb_substr($user['prenom'], 0, 1) . mb_substr($user['nom'], 0, 1)) ?>
        </div>
        <?php endif; ?>

        <div style="flex:1">
            <div class="cv-nom"><?= h($user['prenom'] . ' ' . $user['nom']) ?></div>
            <?php if ($user['titre']): ?>
            <div class="cv-titre"><?= h($user['titre']) ?></div>
            <?php endif; ?>
            <div class="cv-contacts">
                <?php if ($user['email']): ?>
                <div class="cv-contact-item">✉ <?= h($user['email']) ?></div>
                <?php endif; ?>
                <?php if ($user['telephone']): ?>
                <div class="cv-contact-item">📱 <?= h($user['telephone']) ?></div>
                <?php endif; ?>
                <?php if ($user['localisation']): ?>
                <div class="cv-contact-item">📍 <?= h($user['localisation']) ?></div>
                <?php endif; ?>
                <?php if ($user['site_web']): ?>
                <div class="cv-contact-item">🔗 <?= h($user['site_web']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($user['biographie']): ?>
    <div class="cv-section">
        <div class="cv-section-title"><?= t('Profil') ?></div>
        <p class="cv-bio"><?= nl2br(h($user['biographie'])) ?></p>
    </div>
    <?php endif; ?>

    <?php if (!empty($formations)): ?>
    <div class="cv-section">
        <div class="cv-section-title"><?= t('Formation') ?></div>
        <?php foreach ($formations as $f): ?>
        <div class="cv-item">
            <div class="cv-item-dates">
                <?= formatCVDate($f['date_debut']) ?> –
                <?= $f['en_cours'] ? t('Présent') : formatCVDate($f['date_fin']) ?>
            </div>
            <div class="cv-item-content">
                <div class="cv-item-title"><?= h($f['diplome']) ?></div>
                <div class="cv-item-subtitle"><?= h($f['etablissement']) ?><?= $f['lieu'] ? ', ' . h($f['lieu']) : '' ?></div>
                <?php if ($f['domaine']): ?>
                <div class="cv-item-desc"><?= h($f['domaine']) ?></div>
                <?php endif; ?>
                <?php if ($f['description']): ?>
                <div class="cv-item-desc"><?= nl2br(h($f['description'])) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($projets)): ?>
    <div class="cv-section">
        <div class="cv-section-title"><?= t('Projets') ?></div>
        <?php foreach ($projets as $p): ?>
        <div class="cv-item">
            <div class="cv-item-dates">
                <?= $p['date_debut'] ? formatCVDate($p['date_debut']) : '' ?>
                <?php if ($p['date_fin'] || $p['en_cours']): ?>
                – <?= $p['en_cours'] ? t('Présent') : formatCVDate($p['date_fin']) ?>
                <?php endif; ?>
            </div>
            <div class="cv-item-content">
                <div class="cv-item-title"><?= h($p['titre']) ?></div>
                <?php if ($p['type']): ?>
                <div class="cv-item-subtitle"><?= h(t($p['type'])) ?></div>
                <?php endif; ?>
                <?php if ($p['description']): ?>
                <div class="cv-item-desc"><?= nl2br(h($p['description'])) ?></div>
                <?php endif; ?>
                <?php if ($p['lien']): ?>
                <div class="cv-item-desc">🔗 <a href="<?= h($p['lien']) ?>"><?= h($p['lien']) ?></a></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($competences)): ?>
    <div class="cv-section">
        <div class="cv-section-title"><?= t('Compétences') ?></div>
        <div class="competences-grid">
            <?php foreach ($competences as $comp): ?>
            <span class="competence-badge"><?= h($comp) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="cv-footer">
        <?= t('CV généré via ECE In') ?> · <?= date('d/m/Y') ?>
    </div>

</div>
</body>
</html>
