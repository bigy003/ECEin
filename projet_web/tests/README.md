# Tests Unitaires - ECE In

Ce dossier contient les tests unitaires pour le projet ECE In.

## Installation

### Option 1 : Avec Composer (recommandé si Composer est installé)

```bash
composer install
```

Cela installera PHPUnit et ses dépendances.

### Option 2 : Sans Composer (PHPUnit déjà installé)

PHPUnit est déjà installé dans `tools/phpunit.phar`. Utilisez les wrappers fournis :

```powershell
.\phpunit.ps1 --version
```

### Vérifier l'installation

**Avec Composer :**
```bash
./vendor/bin/phpunit --version
```

**Sans Composer :**
```powershell
.\phpunit.ps1 --version
```

## Exécution des tests

### Exécuter tous les tests

**Avec Composer :**
```bash
composer test
```
ou directement :
```bash
./vendor/bin/phpunit
```

**Sans Composer :**
```powershell
.\run_tests.ps1
```
ou directement :
```powershell
php tools\phpunit.phar
```

### Exécuter un fichier de test spécifique

**Avec Composer :**
```bash
./vendor/bin/phpunit tests/Unit/FunctionsTest.php
```

**Sans Composer (recommandé) :**
```powershell
.\phpunit.ps1 tests/Unit/FunctionsTest.php
```

**Ou avec le script run_tests.ps1 :**
```powershell
.\run_tests.ps1 tests/Unit/FunctionsTest.php
```

### Exécuter une méthode de test spécifique

**Avec Composer :**
```bash
./vendor/bin/phpunit --filter testHEscapeHtml
```

**Sans Composer :**
```powershell
.\phpunit.ps1 --filter testHEscapeHtml
```

### Exécuter avec couverture de code

**Avec Composer :**
```bash
./vendor/bin/phpunit --coverage-html coverage
```

**Sans Composer :**
```powershell
.\phpunit.ps1 --coverage-html coverage
```

**Note importante :** N'utilisez pas directement `php tools\phpunit.phar` car PHP ne chargera pas le bon php.ini. Utilisez toujours `.\phpunit.ps1` ou `.\run_tests.ps1`.

Le rapport de couverture sera généré dans le dossier `coverage/`.

## Structure des tests

```
tests/
├── bootstrap.php          # Initialisation avant les tests
├── Unit/                  # Tests unitaires
│   ├── FunctionsTest.php  # Tests des fonctions utilitaires
│   ├── SecurityTest.php  # Tests de sécurité
│   └── DateFormatTest.php # Tests de formatage de dates
└── README.md             # Ce fichier
```

## Types de tests

### Tests unitaires (Unit)

Les tests unitaires testent des fonctions isolées sans dépendances externes :
- Fonctions utilitaires (`h()`, `estConnecte()`, etc.)
- Fonctions de formatage (`formatDateFr()`, `tempsEcoule()`)
- Validation et sécurité

### Tests d'intégration (à venir)

Les tests d'intégration testeront :
- Les APIs avec la base de données
- Les interactions entre composants
- Les workflows complets

## Écriture de nouveaux tests

### Exemple de test

```php
<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MonTest extends TestCase
{
    public function testMaFonction(): void
    {
        $resultat = maFonction('input');
        $this->assertEquals('expected', $resultat);
    }
}
```

### Assertions courantes

- `assertEquals($expected, $actual)` - Vérifie l'égalité
- `assertTrue($condition)` - Vérifie que c'est vrai
- `assertFalse($condition)` - Vérifie que c'est faux
- `assertStringContainsString($needle, $haystack)` - Vérifie qu'une chaîne contient une sous-chaîne
- `assertIsString($value)` - Vérifie le type
- `assertMatchesRegularExpression($pattern, $string)` - Vérifie une regex

## Configuration

La configuration PHPUnit se trouve dans `phpunit.xml` à la racine du projet.

## Notes importantes

1. **Session** : Les tests manipulent `$_SESSION`, pensez à nettoyer entre les tests
2. **Base de données** : Les tests unitaires n'utilisent pas la vraie base de données
3. **Isolation** : Chaque test doit être indépendant et ne pas dépendre d'autres tests

## Résolution de problèmes

### Erreur : "mbstring extension is not available"

**Important :** Utilisez toujours les wrappers fournis :
- `.\phpunit.ps1` (recommandé)
- `.\run_tests.ps1`
- `phpunit.bat` (pour CMD)

N'utilisez **jamais** `php tools\phpunit.phar` directement car PHP ne chargera pas le bon php.ini.

### Erreur : "Class not found"

Vérifiez que vous avez bien exécuté `composer install` (si vous utilisez Composer).

### Erreur : "Session already started"

Les tests gèrent automatiquement la session, mais si vous avez des problèmes, vérifiez `bootstrap.php`.

### Tests qui échouent

Vérifiez que :
1. Les constantes sont bien définies
2. Les fonctions testées sont bien incluses
3. Les mocks/stubs sont correctement configurés

## Contribution

Lors de l'ajout de nouvelles fonctionnalités, pensez à :
1. Écrire les tests en premier (TDD) ou en même temps
2. Maintenir une couverture de code élevée (>80%)
3. Tester les cas limites et les erreurs
4. Documenter les tests complexes
