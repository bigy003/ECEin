# Base de données ECE In

## Installation

1. Créez la base de données en important le fichier SQL :
```bash
mysql -u root -p < reseausocial.sql
```

2. Ou depuis phpMyAdmin :
   - Créer une base `reseausocial`
   - Importer `reseausocial.sql`

## Identifiants de test

| Rôle | Pseudo | Email | Mot de passe |
|------|--------|-------|--------------|
| Admin | `admindmin` | `admin.admin@ece.fr` | `admin123` |
| Auteur | `jw` | `jacque.wu@edu.ece.fr` | `password` |
| Auteur | `hsnacel` | `hider.snacel@edu.ece.fr` | `password` |

> **Note** : Les mots de passe dans la BD sont hashés avec bcrypt. Le mot de passe `password` correspond au hash `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi`.

## Ajouter des offres d'emploi

Pour insérer plusieurs offres d'emploi en une seule fois :
1. Ouvrez phpMyAdmin
2. Sélectionnez la base `reseausocial`
3. Cliquez sur l'onglet "SQL"
4. Copiez-collez le contenu du fichier `insert_offres_emploi.sql`
5. Cliquez sur "Exécuter"

Le script insère **20 offres d'emploi** variées (stages, alternances, CDI, CDD, freelance) dans différents domaines : développement web, mobile, data science, DevOps, cybersécurité, etc.

## Ajouter des offres de bénévolat

Pour insérer plusieurs offres de bénévolat en une seule fois :
1. Ouvrez phpMyAdmin
2. Sélectionnez la base `reseausocial`
3. Cliquez sur l'onglet "SQL"
4. Copiez-collez le contenu du fichier `insert_offres_benevolat.sql`
5. Cliquez sur "Exécuter"

Le script insère **12 offres de bénévolat** variées dans différents domaines : accompagnement scolaire, distribution alimentaire, personnes âgées, environnement, réfugiés, protection animale, handicap, culture, santé, numérique, sans-abri, mentorat.

## Ajouter des événements à venir

Pour insérer plusieurs événements à venir en une seule fois :
1. Ouvrez phpMyAdmin
2. Sélectionnez la base `reseausocial`
3. Cliquez sur l'onglet "SQL"
4. Copiez-collez le contenu du fichier `insert_evenements.sql`
5. Cliquez sur "Exécuter"

Le script insère **25 événements** variés à venir : portes ouvertes ECE, forum emploi, conférences tech, workshops, hackathons, meetups, événements étudiants, salons externes, etc. Tous les événements ont des dates dans le futur.

## Ajouter des utilisateurs

Pour insérer plusieurs utilisateurs (français, américains et internationaux) en une seule fois :
1. Ouvrez phpMyAdmin
2. Sélectionnez la base `reseausocial`
3. Cliquez sur l'onglet "SQL"
4. Copiez-collez le contenu du fichier `insert_utilisateurs.sql`
5. Cliquez sur "Exécuter"

Le script insère **24 utilisateurs américains** :
- **10 étudiants** : MIT, Stanford, UC Berkeley, Harvard, NYU, Carnegie Mellon, Caltech, Columbia, UT Austin, University of Michigan
- **10 professionnels** : Google, Microsoft, Amazon, Apple, Meta, Netflix, Tesla, Airbnb, Uber, Twitter
- **4 entrepreneurs** : basés à San Francisco, Austin, Seattle, New York

**Mot de passe pour tous les nouveaux utilisateurs :** `password123`

**Exemples de connexion :**
- Pseudo : `john.smith` / Email : `john.smith@mit.edu` / Mot de passe : `password123`
- Pseudo : `emily.johnson` / Email : `emily.johnson@stanford.edu` / Mot de passe : `password123`
- Pseudo : `marie.dupont` / Email : `marie.dupont@edu.ece.fr` / Mot de passe : `password123`

## Créer la table candidatures

Pour activer la fonctionnalité de candidature aux offres d'emploi :
1. Ouvrez phpMyAdmin
2. Sélectionnez la base `reseausocial`
3. Cliquez sur l'onglet "SQL"
4. Copiez-collez le contenu du fichier `create_table_candidatures.sql`
5. Cliquez sur "Exécuter"

Cette table permet aux utilisateurs de candidater directement depuis le site avec un message de motivation et un CV optionnel.

## Créer la table favoris_emplois

Pour activer la fonctionnalité de favoris (sauvegarder des offres) :
1. Ouvrez phpMyAdmin
2. Sélectionnez la base `reseausocial`
3. Cliquez sur l'onglet "SQL"
4. Copiez-collez le contenu du fichier `create_table_favoris_emplois.sql`
5. Cliquez sur "Exécuter"

Cette table permet aux utilisateurs de sauvegarder des offres d'emploi et de bénévolat pour les retrouver facilement plus tard. Les favoris sont accessibles via le menu "Mes Favoris".

## Configuration

Modifier [config/database.php](../config/database.php) :
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'reseausocial');
define('DB_USER', 'root');
define('DB_PASS', 'root');  // Par défaut sous MAMP
```
