# Guide Rapide - Tests Unitaires

## ✅ Installation terminée !

Tous les tests sont prêts à être utilisés.

## Commandes principales

### Exécuter tous les tests
```powershell
.\phpunit.ps1
# ou
.\run_tests.ps1
```

### Exécuter un fichier de test spécifique
```powershell
.\phpunit.ps1 tests/Unit/FunctionsTest.php
.\phpunit.ps1 tests/Unit/SecurityTest.php
.\phpunit.ps1 tests/Unit/DateFormatTest.php
```

### Exécuter un test spécifique
```powershell
.\phpunit.ps1 --filter testHEscapeHtml
.\phpunit.ps1 --filter testEstConnecte
```

### Voir la liste des tests
```powershell
.\phpunit.ps1 --list-tests
```

### Mode verbeux (voir les détails)
```powershell
.\phpunit.ps1 --verbose
```

## Résultats attendus

Quand tout fonctionne, vous devriez voir :
```
OK (17 tests, 122 assertions)
```

## Couverture de code (optionnel)

Pour générer un rapport de couverture de code, vous devez installer Xdebug ou PCOV.

**Avertissement actuel :** "No code coverage driver available"
- Ce n'est **pas une erreur**, juste un avertissement
- Les tests fonctionnent parfaitement sans cela
- La couverture de code est utile pour voir quelles parties du code sont testées

### Installer Xdebug (si vous voulez la couverture)

1. Téléchargez Xdebug pour PHP 8.3.1 Windows : https://xdebug.org/download
2. Placez `php_xdebug.dll` dans `C:\MAMP\bin\php\php8.3.1\ext\`
3. Ajoutez dans `php.ini` :
   ```ini
   zend_extension=php_xdebug.dll
   xdebug.mode=coverage
   ```
4. Redémarrez MAMP

## Structure des tests

```
tests/
├── Unit/
│   ├── FunctionsTest.php    # Tests des fonctions utilitaires
│   ├── SecurityTest.php     # Tests de sécurité
│   └── DateFormatTest.php   # Tests de formatage de dates
└── bootstrap.php            # Initialisation
```

## Ajouter de nouveaux tests

1. Créez un nouveau fichier dans `tests/Unit/`
2. Étendez `PHPUnit\Framework\TestCase`
3. Écrivez vos méthodes de test (commencent par `test`)
4. Exécutez : `.\phpunit.ps1 tests/Unit/VotreTest.php`

### Exemple

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

## Résolution de problèmes

### "mbstring extension is not available"
- Les wrappers (`phpunit.ps1`, `run_tests.ps1`) gèrent cela automatiquement
- N'utilisez **jamais** `php tools\phpunit.phar` directement

### Tests qui échouent
- Vérifiez que les fonctions testées existent dans `config/config.php`
- Vérifiez que `bootstrap.php` charge bien les fichiers nécessaires

### Erreurs de syntaxe
- Vérifiez que vous utilisez PHP 8.1+ (MAMP PHP 8.3.1 fonctionne)

## Statistiques actuelles

- ✅ **17 tests** au total
- ✅ **122 assertions** 
- ✅ **3 fichiers de test**
- ✅ **0 échec**

Tous les tests passent ! 🎉
