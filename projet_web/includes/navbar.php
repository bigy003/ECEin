<?php
/**
 * ECE In - Sidebar Navigation (Version Alt)
 */
$pageActive = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- Backdrop mobile -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebar()"></div>

<!-- ===== SIDEBAR ===== -->
<?php
// Déterminer le chemin de base en fonction du dossier courant pour les liens et images de la navbar
$basePath = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../' : '';
?>
<aside class="sidebar-nav" id="sidebarNav">
    <!-- Logo -->
    <div class="sidebar-logo">
        <a href="<?= $basePath ?>index.php" class="d-flex align-items-center gap-2 text-decoration-none">
            <img src="<?= $basePath ?>assets/images/logo.png" alt="Logo" class="logo-mark">
            <div>
                <div class="logo-text">ECE<span>In</span></div>
                <span class="logo-sub"><?= t('Réseau ECE Paris') ?></span>
            </div>
        </a>
    </div>

    <!-- Nav principale -->
    <div class="sidebar-section">
        <div class="sidebar-section-title">Menu</div>
        <a href="<?= $basePath ?>index.php" class="side-link <?= $pageActive === 'index' ? 'active' : '' ?>">
            <i class="bi bi-house-door"></i> <?= t('Accueil') ?>
        </a>
        <a href="<?= $basePath ?>reseau.php" class="side-link <?= $pageActive === 'reseau' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> <?= t('Mon Réseau') ?>
        </a>
        <a href="<?= $basePath ?>profil.php" class="side-link <?= $pageActive === 'profil' ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i> <?= t('Mon Profil') ?>
        </a>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-section-title">Communication</div>
        <a href="<?= $basePath ?>notifications.php" class="side-link <?= $pageActive === 'notifications' ? 'active' : '' ?>">
            <i class="bi bi-bell"></i> Notifications
            <?php if (isset($nbNotifs) && $nbNotifs > 0): ?>
            <span class="badge-count"><?= $nbNotifs ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= $basePath ?>messagerie.php" class="side-link <?= $pageActive === 'messagerie' ? 'active' : '' ?>">
            <i class="bi bi-chat-square-text"></i> <?= t('Messagerie') ?>
            <?php if (isset($nbMessages) && $nbMessages > 0): ?>
            <span class="badge-count"><?= $nbMessages ?></span>
            <?php endif; ?>
        </a>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-section-title"><?= t('Découvrir') ?></div>
        <a href="<?= $basePath ?>emplois.php" class="side-link <?= $pageActive === 'emplois' ? 'active' : '' ?>">
            <i class="bi bi-briefcase"></i> <?= t('Emplois & Stages') ?>
        </a>
        <a href="<?= $basePath ?>favoris.php" class="side-link <?= $pageActive === 'favoris' ? 'active' : '' ?>">
            <i class="bi bi-bookmark-fill"></i> <?= t('Mes Favoris') ?>
        </a>
        <a href="<?= $basePath ?>recherche.php" class="side-link <?= $pageActive === 'recherche' ? 'active' : '' ?>">
            <i class="bi bi-search"></i> <?= t('Recherche') ?>
        </a>
        <?php if (isset($userCourant)): ?>
        <a href="<?= $basePath ?>profil.php?onglet=cv" class="side-link">
            <i class="bi bi-file-earmark-text"></i> <?= t('Mon CV') ?>
        </a>
        <a href="<?= $basePath ?>profil.php?onglet=albums" class="side-link">
            <i class="bi bi-images"></i> <?= t('Albums Photos') ?>
        </a>
        <?php endif; ?>
    </div>

    <?php if (estAdmin()): ?>
    <div class="sidebar-section">
        <div class="sidebar-section-title">Admin</div>
        <a href="<?= $basePath ?>admin/index.php" class="side-link" style="color:var(--rose)">
            <i class="bi bi-shield-lock"></i> Administration
        </a>
    </div>
    <?php endif; ?>

    <!-- Profil en bas -->
    <?php if (estConnecte() && isset($userCourant)): ?>
    <div class="sidebar-profile dropup">
        <div class="sidebar-profile-inner" data-bs-toggle="dropdown" data-bs-auto-close="outside">
            <img src="<?= $basePath ?><?= h($userCourant['photo']) ?>" alt="">
            <div>
                <div class="sidebar-profile-name"><?= h($userCourant['prenom'] . ' ' . $userCourant['nom']) ?></div>
                <div class="sidebar-profile-role">@<?= h($userCourant['pseudo']) ?></div>
            </div>
            <i class="bi bi-chevron-expand ms-auto" style="color:var(--text-3);font-size:.75rem"></i>
        </div>
        <ul class="dropdown-menu dropdown-menu-start mb-2 shadow" style="min-width:220px; z-index: 1050; bottom: 100% !important; top: auto !important; position: absolute;">
            <li><a class="dropdown-item" href="<?= $basePath ?>profil.php"><i class="bi bi-person me-2"></i><?= t('Mon profil') ?></a></li>
            <li><a class="dropdown-item" href="<?= $basePath ?>profil.php?onglet=parametres"><i class="bi bi-gear me-2"></i><?= t('Paramètres') ?></a></li>
            <li>
                <a class="dropdown-item d-flex justify-content-between align-items-center" href="#" data-bs-toggle="collapse" data-bs-target="#themeSubmenu" aria-expanded="false" onclick="event.stopPropagation();">
                    <span><i class="bi bi-palette me-2"></i><?= t('Thème') ?></span>
                    <i class="bi bi-chevron-down ms-2" style="font-size: .7rem;"></i>
                </a>
                <div class="collapse" id="themeSubmenu" style="background: var(--bg-3); border-radius: var(--radius-sm); margin: 0.2rem 0.5rem; overflow: hidden;">
                    <a class="dropdown-item small py-2" href="#" onclick="changerTheme('bleu'); return false;"><i class="bi bi-circle-fill me-2" style="color: #007179;"></i><?= t('Bleu (Défaut)') ?></a>
                    <a class="dropdown-item small py-2" href="#" onclick="changerTheme('rouge'); return false;"><i class="bi bi-circle-fill me-2" style="color: #dc2626;"></i><?= t('Rouge') ?></a>
                    <a class="dropdown-item small py-2" href="#" onclick="changerTheme('jaune'); return false;"><i class="bi bi-circle-fill me-2" style="color: #d97706;"></i><?= t('Jaune orangé') ?></a>
                    <a class="dropdown-item small py-2" href="#" onclick="changerTheme('violet'); return false;"><i class="bi bi-circle-fill me-2" style="color: #7c3aed;"></i><?= t('Violet') ?></a>
                </div>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" style="color:var(--rose)" href="<?= $basePath ?>logout.php"><i class="bi bi-box-arrow-left me-2"></i><?= t('Déconnexion') ?></a></li>
        </ul>
    </div>
    <?php endif; ?>
</aside>

<!-- ===== MAIN WRAPPER ===== -->
<div class="main-content">
    <!-- Page Header -->
    <header class="page-header">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <span class="page-title"><?= h($pageTitle ?? 'ECE In') ?></span>
        </div>
        <div class="search-bar-top position-relative d-none d-md-block">
            <form action="<?= $basePath ?>recherche.php" method="GET">
                <i class="bi bi-search"></i>
                <input type="search" name="q" class="form-control"
                       placeholder="<?= t('Rechercher...') ?>"
                       value="<?= isset($_GET['q']) ? h($_GET['q']) : '' ?>">
            </form>
        </div>
        <!-- Choix de la langue (FR / EN) -->
        <div class="lang-switch" role="group" aria-label="<?= t('Langue') ?>">
            <a href="<?= h(urlLangue('fr')) ?>" class="<?= LANG === 'fr' ? 'active' : '' ?>" title="Français">FR</a>
            <a href="<?= h(urlLangue('en')) ?>" class="<?= LANG === 'en' ? 'active' : '' ?>" title="English">EN</a>
        </div>
    </header>

    <div class="page-body">
