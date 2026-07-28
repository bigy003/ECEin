<?php
/**
 * ECE In - Page de connexion / inscription (Version Alt - Centered Glass)
 * 
 * Cette page gère :
 * - La connexion des utilisateurs existants (pseudo/email + mot de passe)
 * - L'inscription de nouveaux utilisateurs (avec validation des données)
 * - La redirection automatique si l'utilisateur est déjà connecté
 * 
 * Sécurité :
 * - Validation des emails (uniquement @edu.ece.fr ou @ece.fr)
 * - Hashage des mots de passe avec bcrypt
 * - Protection XSS avec la fonction h()
 * - Vérification de l'unicité du pseudo et email
 */
require_once __DIR__ . '/config/config.php';

// Si l'utilisateur est déjà connecté, rediriger vers la page d'accueil
if (estConnecte()) {
    redirect('index.php');
}

// Variables pour gérer les messages d'erreur/succès et le mode d'affichage
$erreur    = ''; // Message d'erreur à afficher
$succes    = ''; // Message de succès à afficher
$mode      = $_GET['mode'] ?? 'connexion'; // Mode : 'connexion' ou 'inscription'

// ===================== TRAITEMENT CONNEXION =====================
/**
 * Traite la soumission du formulaire de connexion
 * 
 * Processus :
 * 1. Récupération du pseudo/email et mot de passe
 * 2. Validation : au moins un des deux (pseudo ou email) doit être fourni
 * 3. Recherche de l'utilisateur dans la base (pseudo OU email, utilisateur actif)
 * 4. Vérification du mot de passe avec password_verify()
 * 5. Création de la session avec les infos utilisateur
 * 6. Mise à jour de la date de dernière connexion
 * 7. Redirection vers la page d'accueil
 * 
 * Sécurité : Utilise password_verify() pour comparer le mot de passe hashé
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'connexion') {
    // Récupération des données du formulaire
    $pseudo = trim($_POST['pseudo'] ?? '');
    $email  = trim($_POST['email']  ?? '');
    $pdo    = getDB();

    // Validation : au moins un des deux (pseudo ou email) doit être fourni
    if (empty($pseudo) && empty($email)) {
        $erreur = 'Veuillez saisir votre pseudo ou votre adresse email.';
    } else {
        // Recherche de l'utilisateur dans la base de données
        // On cherche par pseudo OU email, et l'utilisateur doit être actif
        $stmt = $pdo->prepare("
            SELECT * FROM utilisateurs
            WHERE (pseudo = ? OR email = ?) AND actif = 1
            LIMIT 1
        ");
        // Si pseudo est vide, on utilise email pour les deux paramètres (et vice versa)
        $stmt->execute([$pseudo ?: $email, $email ?: $pseudo]);
        $utilisateur = $stmt->fetch();

        if (!$utilisateur) {
            $erreur = 'Pseudo ou email introuvable.';
        } elseif (!password_verify($_POST['mot_de_passe'] ?? '', $utilisateur['mot_de_passe'])) {
            // SÉCURITÉ : Vérification du mot de passe avec password_verify()
            // Cette fonction compare le mot de passe en clair avec le hash stocké en base
            $erreur = 'Mot de passe incorrect.';
        } else {
            // Connexion réussie : création de la session
            $_SESSION['utilisateur_id'] = $utilisateur['id'];
            $_SESSION['pseudo']         = $utilisateur['pseudo'];
            $_SESSION['role']           = $utilisateur['role'];
            
            // Mise à jour de la date de dernière connexion
            $pdo->prepare("UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?")
                ->execute([$utilisateur['id']]);
            
            // Redirection vers la page d'accueil
            redirect('index.php');
        }
    }
}

// ===================== TRAITEMENT INSCRIPTION =====================
/**
 * Traite la soumission du formulaire d'inscription
 * 
 * Processus de validation :
 * 1. Vérification que tous les champs obligatoires sont remplis
 * 2. Validation du format email (FILTER_VALIDATE_EMAIL)
 * 3. Vérification que l'email est @edu.ece.fr ou @ece.fr (restriction ECE)
 * 4. Vérification de la longueur du mot de passe (minimum 8 caractères)
 * 5. Vérification que les deux mots de passe correspondent
 * 6. Validation du format du pseudo (lettres, chiffres, tirets, underscores, 3-50 caractères)
 * 7. Vérification de l'unicité du pseudo et email
 * 8. Hashage du mot de passe avec bcrypt
 * 9. Insertion dans la base de données
 * 
 * Sécurité :
 * - Hashage du mot de passe avec PASSWORD_BCRYPT
 * - Validation stricte des formats (email, pseudo)
 * - Vérification de l'unicité pour éviter les doublons
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'inscription') {
    // Récupération des données du formulaire
    $nom    = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $pseudo = trim($_POST['pseudo'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $mdp    = $_POST['mot_de_passe'] ?? '';
    $mdp2   = $_POST['mot_de_passe2'] ?? '';
    $pdo    = getDB();

    // VALIDATION ÉTAPE PAR ÉTAPE
    
    // 1. Vérification des champs obligatoires
    if (empty($nom) || empty($prenom) || empty($pseudo) || empty($email) || empty($mdp)) {
        $erreur = 'Tous les champs sont obligatoires.';
    }
    // 2. Validation du format email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Adresse email invalide.';
    }
    // 3. Vérification que l'email est du domaine ECE (sécurité : restriction aux étudiants/professeurs ECE)
    elseif (!str_ends_with($email, '@edu.ece.fr') && !str_ends_with($email, '@ece.fr')) {
        $erreur = 'Seules les adresses @edu.ece.fr ou @ece.fr sont autorisées.';
    }
    // 4. Vérification de la longueur minimale du mot de passe
    elseif (strlen($mdp) < 8) {
        $erreur = 'Le mot de passe doit contenir au moins 8 caractères.';
    }
    // 5. Vérification que les deux mots de passe correspondent
    elseif ($mdp !== $mdp2) {
        $erreur = 'Les mots de passe ne correspondent pas.';
    }
    // 6. Validation du format du pseudo (lettres, chiffres, tirets, underscores, 3-50 caractères)
    elseif (!preg_match('/^[a-z0-9_\-]{3,50}$/i', $pseudo)) {
        $erreur = 'Le pseudo ne peut contenir que des lettres, chiffres, tirets et underscores (3-50 caractères).';
    }
    // 7. Vérification de l'unicité du pseudo et email
    else {
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE pseudo = ? OR email = ?");
        $stmt->execute([$pseudo, $email]);
        if ($stmt->fetch()) {
            $erreur = 'Ce pseudo ou email est déjà utilisé.';
        } else {
            // SÉCURITÉ : Hashage du mot de passe avec bcrypt avant insertion
            // PASSWORD_BCRYPT génère un hash sécurisé avec un salt automatique
            $hash = password_hash($mdp, PASSWORD_BCRYPT);
            
            // Insertion du nouvel utilisateur dans la base de données
            // date_inscription est automatiquement définie à NOW()
            $pdo->prepare("
                INSERT INTO utilisateurs (pseudo, email, mot_de_passe, nom, prenom, date_inscription)
                VALUES (?, ?, ?, ?, ?, NOW())
            ")->execute([$pseudo, $email, $hash, $nom, $prenom]);
            
            // Inscription réussie : afficher un message de succès et basculer vers le mode connexion
            $succes = 'Compte créé ! Vous pouvez vous connecter.';
            $mode = 'connexion';
        }
    }
}

$pageTitle = 'Connexion';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#111118">
    <title>Connexion - <?= SITE_NOM ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="login-page">
    <div class="login-bg"></div>
    <div class="login-bg-grid"></div>

    <div class="login-card" style="animation: fadeUp .6s ease-out">
        <!-- Logo -->
        <div class="login-logo-row">
            <img src="assets/images/logo.png" alt="Logo ECE In" class="logo-mark" style="width:44px;height:44px;">
            <div>
                <div class="logo-text" style="font-size:1.35rem">ECE<span>In</span></div>
            </div>
        </div>
        <p class="login-tagline"><?= SITE_SLOGAN ?></p>

        <!-- Tabs -->
        <div class="d-flex gap-2 mb-4">
            <button class="btn flex-fill <?= $mode === 'connexion' ? 'btn-ecein-primary' : 'btn-light' ?>"
                    id="tab-connexion" onclick="switchMode('connexion')">
                Connexion
            </button>
            <button class="btn flex-fill <?= $mode === 'inscription' ? 'btn-ecein-primary' : 'btn-light' ?>"
                    id="tab-inscription" onclick="switchMode('inscription')">
                S'inscrire
            </button>
        </div>

        <!-- Alertes -->
        <?php if ($erreur): ?>
        <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-2"></i><?= h($erreur) ?></div>
        <?php endif; ?>
        <?php if ($succes): ?>
        <div class="alert alert-success py-2 small"><i class="bi bi-check-circle me-2"></i><?= h($succes) ?></div>
        <?php endif; ?>

        <!-- ===== FORM CONNEXION ===== -->
        <div id="form-connexion" <?= $mode !== 'connexion' ? 'style="display:none"' : '' ?>>
            <form method="POST" action="login.php" novalidate>
                <input type="hidden" name="action" value="connexion">

                <div class="mb-3">
                    <label for="pseudo" class="form-label">Pseudo ou Email ECE</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-at" style="color:var(--accent)"></i></span>
                        <input type="text" class="form-control" id="pseudo" name="pseudo"
                               placeholder="pseudo ou email@edu.ece.fr"
                               value="<?= h($_POST['pseudo'] ?? '') ?>" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="mot_de_passe" class="form-label">Mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-key" style="color:var(--accent)"></i></span>
                        <input type="password" class="form-control" id="mot_de_passe"
                               name="mot_de_passe" placeholder="••••••••" required>
                        <button class="btn btn-light" type="button" onclick="togglePassword('mot_de_passe')">
                            <i class="bi bi-eye" id="eye-mot_de_passe"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-ecein-primary w-100 py-2 fw-semibold">
                    Se connecter <i class="bi bi-arrow-right ms-1"></i>
                </button>

                <div class="text-center mt-3">
                    <small style="color:var(--text-3)">
                        Test : <strong style="color:var(--accent)">adminadmin</strong> / <strong style="color:var(--accent)">admin123</strong>
                    </small>
                </div>
            </form>
        </div>

        <!-- ===== FORM INSCRIPTION ===== -->
        <div id="form-inscription" <?= $mode !== 'inscription' ? 'style="display:none"' : '' ?>>
            <form method="POST" action="login.php?mode=inscription" novalidate>
                <input type="hidden" name="action" value="inscription">

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Prénom</label>
                        <input type="text" class="form-control" name="prenom"
                               placeholder="Hider" value="<?= h($_POST['prenom'] ?? '') ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Nom</label>
                        <input type="text" class="form-control" name="nom"
                               placeholder="Snacel" value="<?= h($_POST['nom'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Pseudo</label>
                    <div class="input-group">
                        <span class="input-group-text" style="color:var(--accent)">@</span>
                        <input type="text" class="form-control" name="pseudo"
                               placeholder="hsnacel" value="<?= h($_POST['pseudo'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email ECE</label>
                    <input type="email" class="form-control" name="email"
                           placeholder="hider.snacel@edu.ece.fr" value="<?= h($_POST['email'] ?? '') ?>" required>
                    <div class="form-text">@edu.ece.fr ou @ece.fr uniquement</div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" name="mot_de_passe"
                               placeholder="Min. 8 car." required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Confirmation</label>
                        <input type="password" class="form-control" name="mot_de_passe2"
                               placeholder="Répéter" required>
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="cgu" required>
                    <label class="form-check-label small" for="cgu" style="color:var(--text-3)">
                        J'accepte les <a href="#">conditions d'utilisation</a>
                    </label>
                </div>

                <button type="submit" class="btn btn-ecein-primary w-100 py-2 fw-semibold">
                    Créer mon compte <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function switchMode(mode) {
    document.getElementById('form-connexion').style.display = mode === 'connexion' ? 'block' : 'none';
    document.getElementById('form-inscription').style.display = mode === 'inscription' ? 'block' : 'none';
    const tc = document.getElementById('tab-connexion');
    const ti = document.getElementById('tab-inscription');
    tc.className = 'btn flex-fill ' + (mode === 'connexion' ? 'btn-ecein-primary' : 'btn-light');
    ti.className = 'btn flex-fill ' + (mode === 'inscription' ? 'btn-ecein-primary' : 'btn-light');
}
function togglePassword(id) {
    const i = document.getElementById(id);
    const e = document.getElementById('eye-' + id);
    if (i.type === 'password') { i.type = 'text'; e.classList.replace('bi-eye', 'bi-eye-slash'); }
    else { i.type = 'password'; e.classList.replace('bi-eye-slash', 'bi-eye'); }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
