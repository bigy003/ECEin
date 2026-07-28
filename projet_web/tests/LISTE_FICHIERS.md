# Liste des Fichiers de Tests Unitaires

## 📁 Structure des fichiers

```
projet_web/
├── phpunit.xml                    # Configuration PHPUnit
├── phpunit.ps1                    # Wrapper PowerShell (à utiliser)
├── phpunit.bat                    # Wrapper CMD
├── run_tests.ps1                  # Script d'exécution alternatif
├── tools/
│   └── phpunit.phar              # PHPUnit téléchargé
└── tests/
    ├── bootstrap.php              # Initialisation avant les tests
    ├── README.md                  # Documentation complète
    ├── QUICK_START.md             # Guide rapide
    └── Unit/
        ├── FunctionsTest.php     # Tests des fonctions utilitaires
        ├── SecurityTest.php       # Tests de sécurité
        └── DateFormatTest.php     # Tests de formatage de dates
```

---

## 📄 Détail des fichiers

### 1. Configuration

#### `phpunit.xml`
**Rôle :** Configuration principale de PHPUnit
- Définit où se trouvent les tests (`tests/Unit/`)
- Configure le bootstrap (initialisation)
- Définit les options d'exécution (couleurs, cache, etc.)
- Configure la couverture de code (quels fichiers analyser)

**À quoi ça sert :** Permet à PHPUnit de savoir comment exécuter les tests

---

### 2. Scripts d'exécution

#### `phpunit.ps1`
**Rôle :** Wrapper PowerShell principal pour exécuter les tests
- Utilise le bon PHP de MAMP
- Charge le bon `php.ini` (avec toutes les extensions)
- Appelle PHPUnit avec les bons paramètres

**À quoi ça sert :** Commande principale pour lancer les tests
```powershell
.\phpunit.ps1                    # Tous les tests
.\phpunit.ps1 --filter testHEscapeHtml  # Un test spécifique
```

#### `phpunit.bat`
**Rôle :** Wrapper pour l'invite de commandes Windows (CMD)
- Même fonction que `phpunit.ps1` mais pour CMD

**À quoi ça sert :** Alternative si vous n'utilisez pas PowerShell

#### `run_tests.ps1`
**Rôle :** Script d'exécution alternatif
- Fonctionne comme `phpunit.ps1`
- Peut être utilisé si vous préférez ce nom

**À quoi ça sert :** Alternative à `phpunit.ps1`

---

### 3. PHPUnit lui-même

#### `tools/phpunit.phar`
**Rôle :** PHPUnit téléchargé (fichier PHAR)
- C'est PHPUnit lui-même (version 10.5.63)
- Ne pas utiliser directement (utiliser les wrappers)

**À quoi ça sert :** C'est le framework de test qui exécute vos tests

---

### 4. Initialisation

#### `tests/bootstrap.php`
**Rôle :** Fichier exécuté avant chaque test
- Définit les constantes nécessaires
- Initialise la session PHP
- Inclut les fichiers de configuration
- Prépare l'environnement de test

**À quoi ça sert :** Assure que l'environnement est prêt avant chaque test

---

### 5. Tests unitaires

#### `tests/Unit/FunctionsTest.php`
**Rôle :** Tests des fonctions utilitaires de base

**Fonctions testées :**
- `h()` - Échappement HTML (protection XSS)
- `estConnecte()` - Vérifie si un utilisateur est connecté
- `estAdmin()` - Vérifie si un utilisateur est administrateur
- `formatDateFr()` - Formate une date en français
- `tempsEcoule()` - Calcule le temps écoulé depuis une date

**À quoi ça sert :** 
- Vérifie que les fonctions de base fonctionnent correctement
- S'assure que l'échappement HTML protège contre XSS
- Teste le formatage des dates en français
- Garantit que la gestion des sessions fonctionne

**Exemples de tests :**
- Vérifie que `<script>` devient `&lt;script&gt;`
- Vérifie que les dates s'affichent en français (15 mars 2024)
- Vérifie que le temps écoulé est correct (il y a 2 jours)

---

#### `tests/Unit/SecurityTest.php`
**Rôle :** Tests de sécurité et protection

**Fonctions testées :**
- Protection XSS (Cross-Site Scripting)
- Validation des sessions
- Protection contre les injections SQL
- Validation des types de données

**À quoi ça sert :**
- Vérifie que le site est protégé contre les attaques XSS
- Teste différents vecteurs d'attaque courants
- S'assure que les sessions sont correctement validées
- Vérifie que les rôles utilisateur sont bien gérés

**Exemples de tests :**
- Teste que `<script>alert("XSS")</script>` est échappé
- Vérifie que les sessions utilisateur/admin fonctionnent
- Teste la protection contre les injections SQL

---

#### `tests/Unit/DateFormatTest.php`
**Rôle :** Tests détaillés pour le formatage de dates

**Fonctions testées :**
- `formatDateFr()` - Formatage complet avec tous les mois
- `tempsEcoule()` - Calcul du temps écoulé pour différentes périodes

**À quoi ça sert :**
- Vérifie que tous les mois s'affichent correctement en français
- Teste différentes périodes (heures, jours, semaines, mois)
- S'assure que le formatage est cohérent
- Teste les cas limites (dates futures, dates passées)

**Exemples de tests :**
- Vérifie que janvier, février, mars... s'affichent correctement
- Teste que "il y a 1 heure" affiche l'heure (14:30)
- Teste que "il y a 3 jours" affiche "3 j"
- Teste que les dates anciennes affichent la date complète

---

### 6. Documentation

#### `tests/README.md`
**Rôle :** Documentation complète des tests
- Comment installer
- Comment exécuter
- Comment écrire de nouveaux tests
- Résolution de problèmes

**À quoi ça sert :** Guide de référence pour comprendre et utiliser les tests

#### `tests/QUICK_START.md`
**Rôle :** Guide rapide pour démarrer
- Commandes principales
- Exemples rapides
- Statistiques des tests

**À quoi ça sert :** Démarrage rapide sans lire toute la documentation

---

## 🎯 Résumé : À quoi servent ces tests ?

### Protection et sécurité
- ✅ Vérifient que le site est protégé contre les attaques XSS
- ✅ Testent la validation des sessions et des rôles
- ✅ S'assurent que les données utilisateur sont correctement échappées

### Fonctionnalités de base
- ✅ Vérifient que les fonctions utilitaires fonctionnent
- ✅ Testent le formatage des dates en français
- ✅ Garantissent que le calcul du temps écoulé est correct

### Qualité du code
- ✅ Détectent les régressions (si vous cassez quelque chose)
- ✅ Documentent le comportement attendu des fonctions
- ✅ Permettent de refactoriser en toute confiance

### Statistiques actuelles
- **17 tests** au total
- **122 assertions** (vérifications)
- **3 fichiers de test**
- **0 échec** ✅

---

## 🚀 Comment utiliser

```powershell
# Exécuter tous les tests
.\phpunit.ps1

# Exécuter un fichier spécifique
.\phpunit.ps1 tests/Unit/FunctionsTest.php

# Exécuter un test spécifique
.\phpunit.ps1 --filter testHEscapeHtml

# Voir la liste des tests
.\phpunit.ps1 --list-tests
```

---

## 📝 Pour ajouter de nouveaux tests

1. Créez un nouveau fichier dans `tests/Unit/`
2. Étendez `PHPUnit\Framework\TestCase`
3. Écrivez vos méthodes de test (commencent par `test`)
4. Exécutez : `.\phpunit.ps1 tests/Unit/VotreTest.php`
