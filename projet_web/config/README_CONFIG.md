# Configuration de la base de données

## 🔄 Fonctionnement automatique

La configuration détecte automatiquement l'environnement :

### En local (MAMP)
- **Aucune variable d'environnement** → Utilise les valeurs par défaut
- `DB_HOST` = `localhost`
- `DB_PORT` = `3306`
- `DB_NAME` = `reseausocial`
- `DB_USER` = `root`
- `DB_PASS` = `root`

**✅ Aucune configuration nécessaire !**

### Sur Railway (production)
- **Variables d'environnement définies** → Utilise les valeurs Railway
- `DB_HOST` = `MYSQL_HOST` (fourni par Railway)
- `DB_PORT` = `MYSQL_PORT` (fourni par Railway)
- `DB_NAME` = `MYSQL_DATABASE` (fourni par Railway)
- `DB_USER` = `MYSQL_USER` (fourni par Railway)
- `DB_PASS` = `MYSQL_PASSWORD` (fourni par Railway)

**✅ Configuration automatique !**

## 📝 Comment ça marche ?

Le code utilise l'opérateur `?:` (null coalescing) :

```php
define('DB_HOST', getenv('MYSQL_HOST') ?: getenv('MYSQLHOST') ?: 'localhost');
```

**Logique :**
1. Essaie `getenv('MYSQL_HOST')` → Si existe, utilise cette valeur
2. Sinon, essaie `getenv('MYSQLHOST')` → Si existe, utilise cette valeur
3. Sinon, utilise `'localhost'` → Valeur par défaut pour le développement local

## 🧪 Tester en local

**Rien à changer !** Votre configuration locale fonctionne comme avant :

1. Démarrez MAMP
2. Ouvrez `http://localhost/projet_web_2026/projet_web/`
3. Tout fonctionne normalement

## 🔧 Personnaliser les valeurs locales

Si vous avez besoin de valeurs différentes en local, modifiez les valeurs par défaut dans `config/database.php` :

```php
// Exemple : changer le nom de la base en local
define('DB_NAME', getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE') ?: 'ma_base_locale');
```

## ✅ Vérification

Pour vérifier quelle configuration est utilisée, ajoutez temporairement dans une page PHP :

```php
<?php
require_once __DIR__ . '/config/config.php';
echo "DB_HOST: " . DB_HOST . "<br>";
echo "DB_NAME: " . DB_NAME . "<br>";
echo "DB_USER: " . DB_USER . "<br>";
?>
```

- **En local** : Vous verrez `localhost`, `reseausocial`, `root`
- **Sur Railway** : Vous verrez les valeurs Railway

## 🚀 Déploiement

Lors du déploiement sur Railway :
1. Railway crée automatiquement les variables d'environnement
2. Votre code les détecte automatiquement
3. Aucune modification nécessaire !

### Sur un hébergement mutualisé (000webhost, etc.)

Ces hébergeurs ne permettent généralement pas de définir des variables
d'environnement. On utilise donc un fichier de config locale, non versionné :

1. Copiez `config/database.local.example.php` en `config/database.local.php`
2. Remplacez les valeurs par celles de votre panneau (section "MySQL Databases") :
   `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
3. Uploadez `config/database.local.php` sur le serveur (FTP / File Manager)

`database.local.php` est listé dans `.gitignore` : il ne sera jamais poussé sur
GitHub, vos identifiants de production restent privés même si le repo est public.

## 📌 Résumé

| Environnement | Détection | Valeurs utilisées |
|---------------|-----------|-------------------|
| **Local (MAMP)** | ❌ Aucune variable, pas de `database.local.php` | Valeurs par défaut (`localhost`, `root`, etc.) |
| **Railway** | ✅ Variables d'environnement automatiques | Variables Railway (`MYSQL_HOST`, etc.) |
| **000webhost / mutualisé** | ✅ Présence de `config/database.local.php` | Valeurs du fichier local |

**Vous n'avez rien à faire de plus, ça fonctionne automatiquement !** 🎉
