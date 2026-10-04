-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : mer. 11 mars 2026 à 04:58
-- Version du serveur : 5.7.24
-- Version de PHP : 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `reseausocial`
--

-- --------------------------------------------------------

--
-- Structure de la table `albums`
--

CREATE TABLE `albums` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `nom` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `couverture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `albums`
--

INSERT INTO `albums` (`id`, `utilisateur_id`, `nom`, `description`, `couverture`, `date_creation`) VALUES
(1, 9, 'Lamine Yamal', 'Cet album présente plusieurs photo de mon joueur préféré', 'uploads/photos/file_69b0be9b67c878.20568935.jpg', '2026-03-11 02:08:47'),
(2, 14, 'Test', 'ceci est un test', 'uploads/photos/file_69b0c8a2762dc9.80136042.jpeg', '2026-03-11 02:43:45');

-- --------------------------------------------------------

--
-- Structure de la table `album_photos`
--

CREATE TABLE `album_photos` (
  `album_id` int(11) NOT NULL,
  `publication_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `album_photos`
--

INSERT INTO `album_photos` (`album_id`, `publication_id`) VALUES
(1, 1),
(1, 2),
(2, 6);

-- --------------------------------------------------------

--
-- Structure de la table `appels`
--

CREATE TABLE `appels` (
  `id` int(11) NOT NULL,
  `appelant_id` int(11) NOT NULL,
  `receveur_id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `type` enum('audio','video') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'audio',
  `statut` enum('sonnerie','en_cours','termine','refuse','manque') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sonnerie',
  `date_debut` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_fin` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `appels`
--

INSERT INTO `appels` (`id`, `appelant_id`, `receveur_id`, `conversation_id`, `type`, `statut`, `date_debut`, `date_fin`) VALUES
(1, 9, 10, 3, 'audio', 'termine', '2026-03-09 17:28:04', '2026-03-09 17:28:06'),
(2, 10, 14, 5, 'audio', 'termine', '2026-03-10 22:08:18', '2026-03-10 22:08:22'),
(3, 10, 9, 5, 'audio', 'manque', '2026-03-10 22:08:20', '2026-03-10 22:08:33'),
(4, 10, 13, 7, 'audio', 'termine', '2026-03-10 22:08:33', '2026-03-10 22:08:37'),
(5, 10, 14, 5, 'audio', 'termine', '2026-03-10 22:08:46', '2026-03-10 22:08:50'),
(6, 10, 14, 5, 'audio', 'termine', '2026-03-10 22:14:40', '2026-03-10 22:14:44'),
(7, 9, 10, 8, 'audio', 'termine', '2026-03-10 22:17:08', '2026-03-10 22:17:10'),
(8, 9, 14, 8, 'audio', 'manque', '2026-03-10 22:17:08', '2026-03-10 22:21:39'),
(9, 9, 10, 3, 'audio', 'termine', '2026-03-10 22:21:39', '2026-03-10 22:21:48'),
(10, 9, 11, 3, 'audio', 'manque', '2026-03-10 22:21:42', '2026-03-10 22:21:52'),
(11, 9, 10, 3, 'audio', 'termine', '2026-03-10 22:21:52', '2026-03-10 22:22:01'),
(12, 9, 10, 3, 'audio', 'termine', '2026-03-10 22:23:02', '2026-03-10 22:23:08'),
(13, 9, 13, 3, 'audio', 'manque', '2026-03-10 22:23:07', '2026-03-10 22:23:11'),
(14, 9, 10, 8, 'audio', 'termine', '2026-03-10 22:23:11', '2026-03-10 22:23:13'),
(15, 9, 14, 8, 'audio', 'manque', '2026-03-10 22:23:11', '2026-03-10 22:23:19'),
(16, 9, 10, 8, 'audio', 'termine', '2026-03-10 22:23:19', '2026-03-10 22:23:25'),
(17, 9, 14, 8, 'audio', 'manque', '2026-03-10 22:23:19', '2026-03-10 22:24:07'),
(18, 9, 10, 8, 'audio', 'termine', '2026-03-10 22:24:07', '2026-03-10 22:24:08'),
(19, 9, 14, 8, 'audio', 'manque', '2026-03-10 22:24:07', '2026-03-10 22:25:28'),
(20, 9, 10, 8, 'audio', 'termine', '2026-03-10 22:25:28', '2026-03-10 22:25:30'),
(21, 9, 10, 3, 'audio', 'termine', '2026-03-10 22:25:37', '2026-03-10 22:25:44'),
(22, 9, 13, 3, 'audio', 'manque', '2026-03-10 22:25:40', '2026-03-10 22:25:47'),
(23, 9, 10, 3, 'audio', 'termine', '2026-03-10 22:25:47', '2026-03-10 22:25:51'),
(24, 9, 11, 3, 'audio', 'sonnerie', '2026-03-10 22:25:49', NULL),
(25, 16, 9, 12, 'audio', 'termine', '2026-03-10 22:29:10', '2026-03-10 22:29:12'),
(26, 16, 13, 12, 'audio', 'manque', '2026-03-10 22:29:10', '2026-03-10 22:29:18'),
(27, 16, 11, 10, 'audio', 'termine', '2026-03-10 22:29:18', '2026-03-10 22:29:27'),
(28, 16, 9, 10, 'audio', 'manque', '2026-03-10 22:29:22', '2026-03-10 22:32:42'),
(29, 16, 13, 10, 'audio', 'manque', '2026-03-10 22:29:25', '2026-03-10 22:32:42'),
(30, 16, 9, 13, 'audio', 'termine', '2026-03-10 22:32:42', '2026-03-10 22:32:44'),
(31, 16, 11, 13, 'audio', 'manque', '2026-03-10 22:32:42', '2026-03-11 00:30:09'),
(32, 16, 13, 13, 'audio', 'manque', '2026-03-10 22:32:42', '2026-03-11 00:30:09'),
(33, 16, 11, 10, 'audio', 'termine', '2026-03-11 00:30:09', '2026-03-11 00:30:14'),
(34, 16, 9, 10, 'audio', 'sonnerie', '2026-03-11 00:30:13', NULL),
(35, 13, 16, 11, 'audio', 'termine', '2026-03-11 03:48:38', '2026-03-11 03:48:47'),
(36, 13, 9, 11, 'audio', 'manque', '2026-03-11 03:48:41', '2026-03-11 03:49:19'),
(37, 13, 16, 11, 'audio', 'termine', '2026-03-11 03:49:19', '2026-03-11 03:49:26'),
(38, 13, 9, 11, 'audio', 'sonnerie', '2026-03-11 03:49:22', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `appel_participants`
--

CREATE TABLE `appel_participants` (
  `id` int(11) NOT NULL,
  `appel_id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `statut` enum('invite','en_cours','termine','refuse') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'invite',
  `date_ajout` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `commentaires`
--

CREATE TABLE `commentaires` (
  `id` int(11) NOT NULL,
  `publication_id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `contenu` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_commentaire` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commentaires`
--

INSERT INTO `commentaires` (`id`, `publication_id`, `utilisateur_id`, `contenu`, `date_commentaire`) VALUES
(1, 1, 10, 'Jsuis d\'accord avec toi', '2026-03-09 19:23:07'),
(2, 3, 11, 'Cet anime restera a jamais dans le top 3', '2026-03-11 02:32:45'),
(3, 2, 11, 'évidemment chef!!!!!!!', '2026-03-11 02:37:52');

-- --------------------------------------------------------

--
-- Structure de la table `competences`
--

CREATE TABLE `competences` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `niveau` enum('debutant','intermediaire','avance','expert') COLLATE utf8mb4_unicode_ci DEFAULT 'intermediaire',
  `ordre` int(11) DEFAULT '0',
  `date_ajout` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `competences`
--

INSERT INTO `competences` (`id`, `utilisateur_id`, `nom`, `niveau`, `ordre`, `date_ajout`) VALUES
(1, 14, 'Java', 'avance', 1, '2026-03-11 02:47:20'),
(2, 14, 'HTML', 'intermediaire', 2, '2026-03-11 02:48:55');

-- --------------------------------------------------------

--
-- Structure de la table `connexions`
--

CREATE TABLE `connexions` (
  `id` int(11) NOT NULL,
  `demandeur_id` int(11) NOT NULL,
  `destinataire_id` int(11) NOT NULL,
  `statut` enum('en_attente','accepte','refuse') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `date_demande` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_reponse` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `connexions`
--

INSERT INTO `connexions` (`id`, `demandeur_id`, `destinataire_id`, `statut`, `date_demande`, `date_reponse`) VALUES
(10, 10, 9, 'accepte', '2026-03-09 12:13:08', '2026-03-09 12:13:24'),
(11, 9, 11, 'accepte', '2026-03-09 12:28:57', '2026-03-09 12:29:39'),
(12, 14, 10, 'accepte', '2026-03-09 17:49:06', '2026-03-09 17:49:38'),
(13, 9, 14, 'en_attente', '2026-03-09 19:09:52', NULL),
(14, 9, 13, 'accepte', '2026-03-10 22:20:43', '2026-03-10 22:21:02'),
(15, 16, 13, 'accepte', '2026-03-10 22:26:16', '2026-03-10 22:26:43'),
(16, 16, 9, 'accepte', '2026-03-10 22:26:23', '2026-03-10 22:28:00'),
(17, 16, 11, 'accepte', '2026-03-10 22:26:28', '2026-03-10 22:27:30');

-- --------------------------------------------------------

--
-- Structure de la table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `nom` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_groupe` tinyint(1) DEFAULT '0',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `conversations`
--

INSERT INTO `conversations` (`id`, `nom`, `photo`, `est_groupe`, `date_creation`) VALUES
(1, NULL, NULL, 0, '2026-03-03 14:44:33'),
(2, NULL, NULL, 0, '2026-03-09 10:50:18'),
(3, NULL, NULL, 0, '2026-03-09 12:13:31'),
(4, NULL, NULL, 0, '2026-03-09 12:29:52'),
(5, NULL, NULL, 0, '2026-03-09 17:54:30'),
(6, NULL, NULL, 0, '2026-03-10 21:58:05'),
(7, NULL, NULL, 0, '2026-03-10 21:59:06'),
(8, 'INGE5 Bientôt', 'uploads/groupes/groupe_1773177354_69b08a0aec28e.jpg', 1, '2026-03-10 22:15:42'),
(9, NULL, NULL, 0, '2026-03-10 22:17:27'),
(10, NULL, NULL, 0, '2026-03-10 22:28:30'),
(11, NULL, NULL, 0, '2026-03-10 22:28:37'),
(12, 'Test_admin', NULL, 1, '2026-03-10 22:29:03'),
(13, 'autre', 'uploads/groupes/groupe_1773178433_69b08e4130eee.jpg', 1, '2026-03-10 22:32:34'),
(14, NULL, NULL, 0, '2026-03-11 03:44:39');

-- --------------------------------------------------------

--
-- Structure de la table `conversation_participants`
--

CREATE TABLE `conversation_participants` (
  `conversation_id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `date_ajout` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `conversation_participants`
--

INSERT INTO `conversation_participants` (`conversation_id`, `utilisateur_id`, `date_ajout`) VALUES
(3, 9, '2026-03-09 12:13:31'),
(3, 10, '2026-03-09 12:13:31'),
(4, 9, '2026-03-09 12:29:52'),
(4, 11, '2026-03-09 12:29:52'),
(5, 10, '2026-03-09 17:54:30'),
(5, 14, '2026-03-09 17:54:30'),
(6, 10, '2026-03-10 21:58:05'),
(6, 11, '2026-03-10 21:58:05'),
(7, 10, '2026-03-10 21:59:06'),
(7, 13, '2026-03-10 21:59:06'),
(8, 9, '2026-03-10 22:15:42'),
(8, 10, '2026-03-10 22:15:42'),
(8, 14, '2026-03-10 22:15:42'),
(9, 9, '2026-03-10 22:17:27'),
(9, 16, '2026-03-10 22:17:27'),
(10, 11, '2026-03-10 22:28:30'),
(10, 16, '2026-03-10 22:28:30'),
(11, 13, '2026-03-10 22:28:37'),
(11, 16, '2026-03-10 22:28:37'),
(12, 9, '2026-03-10 22:29:03'),
(12, 13, '2026-03-10 22:29:03'),
(12, 16, '2026-03-10 22:29:03'),
(13, 9, '2026-03-10 22:32:35'),
(13, 11, '2026-03-10 22:32:35'),
(13, 13, '2026-03-10 22:32:35'),
(13, 16, '2026-03-10 22:32:34'),
(14, 9, '2026-03-11 03:44:39'),
(14, 13, '2026-03-11 03:44:39');

-- --------------------------------------------------------

--
-- Structure de la table `emplois`
--

CREATE TABLE `emplois` (
  `id` int(11) NOT NULL,
  `publie_par` int(11) NOT NULL,
  `titre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entreprise` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('cdi','cdd','stage','alternance','freelance','benevole') COLLATE utf8mb4_unicode_ci NOT NULL,
  `lieu` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salaire` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_debut` date DEFAULT NULL,
  `date_limite` date DEFAULT NULL,
  `lien_candidature` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_publication` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actif` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `emplois`
--

INSERT INTO `emplois` (`id`, `publie_par`, `titre`, `entreprise`, `description`, `type`, `lieu`, `salaire`, `date_debut`, `date_limite`, `lien_candidature`, `contact_email`, `date_publication`, `actif`) VALUES
(2, 16, 'Développeur Full-Stack (Stage)', 'TechCorp Paris', 'Nous recherchons un stagiaire développeur full-stack pour rejoindre notre équipe de développement. Vous travaillerez sur des projets web modernes utilisant React, Node.js et MongoDB. Environnement dynamique et formateur.', 'stage', 'Paris 15e', '1200€/mois', '2026-04-01', '2026-03-25', 'https://techcorp.fr/candidature', 'rh@techcorp.fr', '2026-03-10 22:39:19', 1),
(3, 16, 'Stage Développeur Mobile iOS/Android', 'AppMobile Solutions', 'Stage de 6 mois pour développer des applications mobiles natives. Vous maîtrisez Swift/Kotlin et souhaitez apprendre les meilleures pratiques du développement mobile. Équipe jeune et bienveillante.', 'stage', 'Paris 11e', '1100€/mois', '2026-05-01', '2026-04-15', 'https://appmobile.fr/recrutement', 'recrutement@appmobile.fr', '2026-03-10 22:39:19', 1),
(4, 16, 'Stage Data Science & Machine Learning', 'DataLab Analytics', 'Rejoignez notre équipe de data scientists pour travailler sur des projets d\'intelligence artificielle. Python, TensorFlow, analyse de données. Stage de 6 mois avec possibilité d\'embauche.', 'stage', 'Paris 9e', '1300€/mois', '2026-04-15', '2026-04-01', 'https://datalab.fr/careers', 'jobs@datalab.fr', '2026-03-10 22:39:19', 1),
(5, 16, 'Stage Développeur Backend', 'CloudServices Pro', 'Développement d\'APIs REST, microservices, bases de données. Stack : Java/Spring Boot ou Node.js. Stage de 4 à 6 mois dans une startup en forte croissance.', 'stage', 'Paris 2e', '1150€/mois', '2026-06-01', '2026-05-15', 'https://cloudservices.fr/jobs', 'hr@cloudservices.fr', '2026-03-10 22:39:19', 1),
(6, 16, 'Stage DevOps & Cloud', 'InfraTech', 'Découvrez le monde du DevOps : Docker, Kubernetes, CI/CD, AWS. Stage de 6 mois pour apprendre les outils modernes d\'infrastructure cloud. Formation interne fournie.', 'stage', 'Paris 13e', '1250€/mois', '2026-05-01', '2026-04-20', 'https://infratech.fr/recrutement', 'contact@infratech.fr', '2026-03-10 22:39:19', 1),
(7, 16, 'Alternance Développeur Full-Stack', 'StartupTech', 'Alternance de 12 à 24 mois en développement web. Stack moderne : React, TypeScript, Node.js, PostgreSQL. Rythme : 3 semaines entreprise / 1 semaine école. Ambiance startup !', 'alternance', 'Paris 10e', '1500€/mois', '2026-09-01', '2026-06-30', 'https://startuptech.fr/alternance', 'alternance@startuptech.fr', '2026-03-10 22:39:19', 1),
(8, 16, 'Alternance Data Engineer', 'BigData Corp', 'Formation en alternance sur 2 ans. Traitement de données massives, pipelines ETL, Spark, Hadoop. Rythme : 2 semaines entreprise / 2 semaines école. Excellente opportunité de carrière.', 'alternance', 'Paris 8e', '1600€/mois', '2026-09-01', '2026-07-15', 'https://bigdatacorp.fr/careers', 'recrutement@bigdatacorp.fr', '2026-03-10 22:39:19', 1),
(9, 16, 'Alternance Cybersécurité', 'SecureNet', 'Apprenez la cybersécurité en alternance : tests de pénétration, audit de sécurité, protection des systèmes. Formation certifiante incluse. Rythme flexible selon votre école.', 'alternance', 'Paris 16e', '1550€/mois', '2026-09-01', '2026-07-01', 'https://securenet.fr/alternance', 'jobs@securenet.fr', '2026-03-10 22:39:19', 1),
(10, 16, 'Alternance Développeur Blockchain', 'BlockChain Solutions', 'Développement d\'applications blockchain, smart contracts, Web3. Technologies : Solidity, Ethereum, Web3.js. Alternance de 12 mois minimum. Innovation garantie !', 'alternance', 'Paris 1er', '1450€/mois', '2026-09-01', '2026-06-15', 'https://blockchainsolutions.fr/jobs', 'hr@blockchainsolutions.fr', '2026-03-10 22:39:19', 1),
(11, 16, 'Développeur Full-Stack Senior', 'Enterprise Solutions', 'Poste CDI pour développeur expérimenté. Leadership technique, architecture logicielle, mentoring junior. Stack : .NET, Angular, Azure. Package attractif + avantages.', 'cdi', 'Paris 17e', '45000-55000€/an', '2026-05-01', '2026-04-15', 'https://enterprise-solutions.fr/careers', 'recrutement@enterprise-solutions.fr', '2026-03-10 22:39:19', 1),
(12, 16, 'Ingénieur DevOps', 'CloudPlatform Inc', 'CDI pour ingénieur DevOps expérimenté. Gestion infrastructure cloud, automatisation, monitoring. Technologies : AWS, Terraform, Kubernetes. Télétravail partiel possible.', 'cdi', 'Paris 12e', '50000-60000€/an', '2026-06-01', '2026-05-01', 'https://cloudplatform.fr/jobs', 'jobs@cloudplatform.fr', '2026-03-10 22:39:19', 1),
(13, 16, 'Data Scientist', 'AI Innovations', 'CDI pour data scientist passionné par l\'IA. Développement de modèles ML, analyse prédictive, NLP. Python, TensorFlow, PyTorch. Environnement de recherche appliquée.', 'cdi', 'Paris 5e', '48000-58000€/an', '2026-05-15', '2026-04-30', 'https://ai-innovations.fr/careers', 'careers@ai-innovations.fr', '2026-03-10 22:39:19', 1),
(14, 16, 'Développeur Frontend (CDD 12 mois)', 'WebAgency Pro', 'CDD de 12 mois renouvelable. Développement d\'interfaces utilisateur modernes. React, Vue.js, TypeScript. Projets variés pour clients prestigieux.', 'cdd', 'Paris 3e', '35000-42000€/an', '2026-04-01', '2026-03-20', 'https://webagency-pro.fr/recrutement', 'contact@webagency-pro.fr', '2026-03-10 22:39:19', 1),
(15, 16, 'Développeur Backend Python (CDD 6 mois)', 'PythonDev Studio', 'CDD de 6 mois pour projet spécifique. Développement API REST, Django, FastAPI. Possibilité de CDI à l\'issue du CDD. Équipe technique de haut niveau.', 'cdd', 'Paris 6e', '38000€/an', '2026-04-15', '2026-04-01', 'https://pythondev.fr/jobs', 'jobs@pythondev.fr', '2026-03-10 22:39:19', 1),
(16, 16, 'Développeur WordPress Freelance', 'Digital Agency', 'Mission freelance pour développement de sites WordPress sur mesure. Durée : 3-6 mois. Télétravail possible. Tarif : 300-400€/jour selon expérience.', 'freelance', 'Paris / Télétravail', '300-400€/jour', '2026-04-01', '2026-03-25', 'https://digital-agency.fr/freelance', 'freelance@digital-agency.fr', '2026-03-10 22:39:19', 1),
(17, 16, 'Consultant Cloud AWS Freelance', 'Cloud Consulting', 'Mission freelance pour migration vers AWS. Durée : 2-4 mois. Expertise AWS requise. Télétravail possible. Tarif : 500-600€/jour.', 'freelance', 'Paris / Télétravail', '500-600€/jour', '2026-05-01', '2026-04-15', 'https://cloud-consulting.fr/missions', 'missions@cloud-consulting.fr', '2026-03-10 22:39:19', 1),
(18, 16, 'Stage UX/UI Designer', 'DesignStudio', 'Stage de 6 mois en design d\'interfaces utilisateur. Figma, Adobe XD, prototypage. Vous travaillerez sur des projets clients réels. Portfolio requis.', 'stage', 'Paris 4e', '1000€/mois', '2026-05-01', '2026-04-20', 'https://designstudio.fr/recrutement', 'recrutement@designstudio.fr', '2026-03-10 22:39:19', 1),
(19, 16, 'Stage Testeur QA / QA Engineer', 'Quality Assurance Pro', 'Stage en assurance qualité logicielle. Tests manuels et automatisés, Selenium, Cypress. Apprentissage des méthodologies Agile. Stage de 6 mois.', 'stage', 'Paris 14e', '1100€/mois', '2026-06-01', '2026-05-15', 'https://qa-pro.fr/careers', 'jobs@qa-pro.fr', '2026-03-10 22:39:19', 1),
(20, 16, 'Stage Développeur Game', 'GameStudio Paris', 'Stage en développement de jeux vidéo. Unity, C#, game design. Projet de jeu mobile ou PC. Passion pour le gaming requise ! Stage de 4-6 mois.', 'stage', 'Paris 20e', '1050€/mois', '2026-05-15', '2026-05-01', 'https://gamestudio-paris.fr/recrutement', 'recrutement@gamestudio-paris.fr', '2026-03-10 22:39:19', 1),
(21, 16, 'Stage Développeur IoT', 'IoT Solutions', 'Stage en développement d\'objets connectés. Arduino, Raspberry Pi, protocoles IoT. Projets innovants dans la domotique et l\'industrie 4.0. Stage de 6 mois.', 'stage', 'Paris 19e', '1200€/mois', '2026-06-01', '2026-05-20', 'https://iot-solutions.fr/jobs', 'contact@iot-solutions.fr', '2026-03-10 22:39:19', 1),
(22, 16, 'Alternance Product Manager', 'ProductTech', 'Alternance en product management. Définition de produits, roadmap, analyse utilisateur. Rythme : 3 semaines entreprise / 1 semaine école. Formation interne.', 'alternance', 'Paris 7e', '1400€/mois', '2026-09-01', '2026-07-01', 'https://producttech.fr/alternance', 'alternance@producttech.fr', '2026-03-10 22:39:19', 1),
(23, 16, 'Alternance Consultant IT', 'IT Consulting Group', 'Alternance en conseil informatique. Analyse de besoins clients, architecture de solutions. Rythme flexible. Excellente formation commerciale et technique.', 'alternance', 'Paris 8e', '1500€/mois', '2026-09-01', '2026-07-15', 'https://it-consulting.fr/careers', 'recrutement@it-consulting.fr', '2026-03-10 22:39:19', 1);

-- --------------------------------------------------------

--
-- Structure de la table `evenements`
--

CREATE TABLE `evenements` (
  `id` int(11) NOT NULL,
  `organisateur_id` int(11) NOT NULL,
  `titre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `lieu` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lien` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_officiel` tinyint(1) DEFAULT '0',
  `date_publication` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `evenements`
--

INSERT INTO `evenements` (`id`, `organisateur_id`, `titre`, `description`, `lieu`, `date_debut`, `date_fin`, `image`, `lien`, `est_officiel`, `date_publication`) VALUES
(1, 16, 'Portes Ouvertes ECE Paris 2026', 'Venez découvrir l\'ECE Paris ! Visite des locaux, rencontres avec les étudiants et les enseignants, présentation des formations et des spécialités. Inscription gratuite.', 'ECE Paris - 37 Quai de Grenelle, 75015 Paris', '2026-03-15 10:00:00', '2026-03-15 17:00:00', NULL, 'https://ece.fr/portes-ouvertes', 1, '2026-03-10 22:42:13'),
(2, 16, 'Forum Emploi ECE 2026', 'Forum annuel emploi et alternance. Plus de 50 entreprises partenaires présentes pour rencontrer les étudiants ECE. Opportunités de stages, alternances et CDI.', 'ECE Paris - Grand Amphithéâtre', '2026-03-22 09:00:00', '2026-03-22 18:00:00', NULL, 'https://ece.fr/forum-emploi', 1, '2026-03-10 22:42:13'),
(3, 16, 'Journée Innovation & Tech ECE', 'Conférences et démonstrations sur les dernières innovations technologiques. IA, blockchain, IoT, cybersécurité. Ouvert à tous les étudiants.', 'ECE Paris - Campus principal', '2026-04-05 09:00:00', '2026-04-05 17:00:00', NULL, 'https://ece.fr/innovation-tech', 1, '2026-03-10 22:42:13'),
(4, 16, 'Gala de l\'ECE Paris 2026', 'Soirée de gala annuelle de l\'ECE Paris. Cocktail, dîner, animations et remise des diplômes. Tenue de soirée requise.', 'Palais des Congrès, Paris', '2026-05-20 19:00:00', '2026-05-20 23:30:00', NULL, 'https://ece.fr/gala', 1, '2026-03-10 22:42:13'),
(5, 16, 'Semaine Internationale ECE', 'Événement dédié à l\'international : conférences, stands pays, échanges avec les universités partenaires. Découvrez les opportunités à l\'étranger !', 'ECE Paris - Campus', '2026-04-12 09:00:00', '2026-04-16 17:00:00', NULL, 'https://ece.fr/international', 1, '2026-03-10 22:42:13'),
(6, 16, 'Conférence : Intelligence Artificielle et Éthique', 'Conférence sur les enjeux éthiques de l\'IA animée par des experts du domaine. Débat et échanges avec le public.', 'ECE Paris - Salle de conférence A', '2026-03-18 14:00:00', '2026-03-18 17:00:00', NULL, 'https://ece.fr/conf-ia-ethique', 0, '2026-03-10 22:42:13'),
(7, 16, 'Workshop : Développement Web Moderne', 'Atelier pratique sur React, TypeScript et les dernières tendances du développement web. Apportez votre ordinateur portable.', 'ECE Paris - Salle informatique', '2026-03-25 14:00:00', '2026-03-25 18:00:00', NULL, 'https://ece.fr/workshop-web', 0, '2026-03-10 22:42:13'),
(8, 16, 'Meetup : Startup & Entrepreneuriat', 'Rencontre avec des entrepreneurs ECE. Partage d\'expériences, conseils pour lancer votre startup. Networking et apéritif.', 'ECE Paris - Espace coworking', '2026-04-02 18:00:00', '2026-04-02 20:30:00', NULL, 'https://ece.fr/meetup-startup', 0, '2026-03-10 22:42:13'),
(9, 16, 'Hackathon ECE 2026', 'Hackathon de 48h sur le thème \"Tech for Good\". Formez votre équipe et développez une solution innovante. Prix à gagner !', 'ECE Paris - Campus', '2026-04-08 09:00:00', '2026-04-10 18:00:00', NULL, 'https://ece.fr/hackathon', 1, '2026-03-10 22:42:13'),
(10, 16, 'Conférence : Cybersécurité & Protection des Données', 'Expert en cybersécurité partage les bonnes pratiques pour protéger vos données et celles de votre entreprise. RGPD et sécurité cloud.', 'ECE Paris - Amphithéâtre B', '2026-03-28 15:00:00', '2026-03-28 17:30:00', NULL, 'https://ece.fr/conf-cybersecurite', 0, '2026-03-10 22:42:13'),
(11, 16, 'Soirée Networking ECE Alumni', 'Rencontre entre étudiants et anciens élèves ECE. Échanges sur les parcours professionnels, conseils carrière. Cocktail offert.', 'Bar Le Comptoir, Paris 15e', '2026-03-20 19:00:00', '2026-03-20 22:00:00', NULL, NULL, 0, '2026-03-10 22:42:13'),
(12, 16, 'Tournoi E-Sport ECE', 'Tournoi de jeux vidéo entre étudiants. Valorant, League of Legends, Rocket League. Inscriptions par équipe. Lots à gagner !', 'ECE Paris - Salle gaming', '2026-04-01 14:00:00', '2026-04-01 20:00:00', NULL, 'https://ece.fr/esport', 0, '2026-03-10 22:42:13'),
(13, 16, 'Atelier : Préparation aux Entretiens', 'Workshop pour préparer vos entretiens d\'embauche et de stage. Simulation d\'entretiens, CV, lettre de motivation. Animé par le service carrière.', 'ECE Paris - Salle de formation', '2026-03-30 14:00:00', '2026-03-30 16:30:00', NULL, 'https://ece.fr/atelier-entretiens', 0, '2026-03-10 22:42:13'),
(14, 16, 'Conférence : Blockchain & Web3', 'Découvrez les applications de la blockchain et du Web3. Smart contracts, NFT, DeFi. Présentation par des experts du secteur.', 'ECE Paris - Salle de conférence', '2026-04-10 16:00:00', '2026-04-10 18:00:00', NULL, 'https://ece.fr/conf-blockchain', 0, '2026-03-10 22:42:13'),
(15, 16, 'Workshop : Data Science avec Python', 'Initiation à la data science : manipulation de données avec Pandas, visualisation avec Matplotlib, introduction au machine learning.', 'ECE Paris - Salle informatique', '2026-04-15 14:00:00', '2026-04-15 17:00:00', NULL, 'https://ece.fr/workshop-datascience', 0, '2026-03-10 22:42:13'),
(16, 16, 'Viva Technology 2026', 'Le plus grand événement tech d\'Europe ! Startups, innovations, conférences. Billets étudiants à prix réduit. Transport organisé depuis l\'ECE.', 'Paris Expo Porte de Versailles', '2026-05-15 09:00:00', '2026-05-17 18:00:00', NULL, 'https://vivatechnology.com', 0, '2026-03-10 22:42:13'),
(17, 16, 'Paris Web 2026', 'Conférence web pour développeurs et designers. Talks sur les dernières technologies web, UX/UI, accessibilité. Early bird jusqu\'au 1er avril.', 'Cité des Sciences, Paris', '2026-06-10 09:00:00', '2026-06-11 18:00:00', NULL, 'https://paris-web.fr', 0, '2026-03-10 22:42:13'),
(18, 16, 'Devoxx France 2026', 'Conférence Java et développement logiciel. Plus de 200 talks, workshops, keynotes. Réduction étudiante disponible.', 'Palais des Congrès, Paris', '2026-04-17 08:30:00', '2026-04-19 18:00:00', NULL, 'https://devoxx.fr', 0, '2026-03-10 22:42:13'),
(19, 16, 'Salon de l\'Alternance Paris', 'Salon dédié à l\'alternance et à l\'apprentissage. Rencontrez des entreprises recrutant en alternance. Entrée gratuite.', 'Parc des Expositions, Paris', '2026-05-05 10:00:00', '2026-05-06 18:00:00', NULL, 'https://salon-alternance.fr', 0, '2026-03-10 22:42:13'),
(20, 16, 'Startup Weekend Paris', 'Weekend intensif pour créer une startup en 54h. Idéation, prototypage, pitch. Équipes formées sur place. Nourriture incluse.', 'Station F, Paris', '2026-04-22 18:00:00', '2026-04-24 20:00:00', NULL, 'https://startupweekend.org/paris', 0, '2026-03-10 22:42:13'),
(21, 16, 'Soirée Cinéma ECE', 'Projection de film suivie d\'un débat. Thème : \"Tech et Société\". Popcorn offert !', 'ECE Paris - Salle polyvalente', '2026-03-22 19:00:00', '2026-03-22 22:00:00', NULL, NULL, 0, '2026-03-10 22:42:13'),
(22, 16, 'Tournoi de Football ECE', 'Tournoi de football entre promotions. Inscriptions par équipe de 7. Trophée et médailles pour les gagnants !', 'Stade de l\'ECE, Paris', '2026-04-06 14:00:00', '2026-04-06 18:00:00', NULL, 'https://ece.fr/football', 0, '2026-03-10 22:42:13'),
(23, 16, 'Journée Portes Ouvertes Masters', 'Découvrez les masters de l\'ECE : Big Data, Cybersécurité, IoT, Finance. Rencontres avec les responsables de formation.', 'ECE Paris - Campus', '2026-05-10 10:00:00', '2026-05-10 16:00:00', NULL, 'https://ece.fr/masters', 1, '2026-03-10 22:42:13'),
(24, 16, 'Conférence : Green Tech & Développement Durable', 'Comment la tech peut contribuer au développement durable. Exemples concrets, solutions innovantes. Conférence suivie d\'un débat.', 'ECE Paris - Amphithéâtre principal', '2026-04-20 15:00:00', '2026-04-20 17:30:00', NULL, 'https://ece.fr/greentech', 0, '2026-03-10 22:42:13'),
(25, 16, 'Workshop : Git & Collaboration', 'Apprenez Git, GitHub, et les bonnes pratiques de collaboration en équipe. Workflow, branches, pull requests. Atelier pratique.', 'ECE Paris - Salle informatique', '2026-03-27 14:00:00', '2026-03-27 17:00:00', NULL, 'https://ece.fr/workshop-git', 0, '2026-03-10 22:42:13');

-- --------------------------------------------------------

--
-- Structure de la table `formations`
--

CREATE TABLE `formations` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `etablissement` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `diplome` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `domaine` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  `en_cours` tinyint(1) DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `lieu` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `formations`
--

INSERT INTO `formations` (`id`, `utilisateur_id`, `etablissement`, `diplome`, `domaine`, `date_debut`, `date_fin`, `en_cours`, `description`, `lieu`) VALUES
(1, 9, 'Sorbonne Université', 'Licence', 'Informatique', '2021-01-18', '2021-09-11', 0, 'Développement web', 'Paris'),
(2, 14, 'ECE Paris', 'Master en Ingénierie Informatique', 'Informatique et Systèmes d\'Information', '2020-09-01', '2024-06-30', 0, 'Formation complète en développement web, bases de données, et architecture logicielle.', NULL),
(3, 14, 'Lycée Louis-le-Grand', 'Baccalauréat Scientifique', 'Sciences', '2017-09-01', '2020-06-30', 0, 'Spécialité Mathématiques et Sciences de l\'Ingénieur', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `expediteur_id` int(11) NOT NULL,
  `contenu` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `fichier_joint` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_supprime` tinyint(1) DEFAULT '0',
  `est_modifie` tinyint(1) DEFAULT '0',
  `lu` tinyint(1) DEFAULT '0',
  `distribue` tinyint(1) DEFAULT '0',
  `date_envoi` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `expediteur_id`, `contenu`, `fichier_joint`, `est_supprime`, `est_modifie`, `lu`, `distribue`, `date_envoi`) VALUES
(2, 3, 9, 'Yo bg c\'est comment ???', NULL, 0, 0, 1, 0, '2026-03-09 12:13:41'),
(3, 3, 10, 'C\'est comment avec Camelia ??', NULL, 0, 0, 1, 0, '2026-03-09 12:14:38'),
(4, 4, 9, 'T\'aurai pas des snap asiat par hasard ?? tkt c pour un pote 💀💀', NULL, 0, 0, 1, 0, '2026-03-09 12:44:04'),
(5, 4, 11, 'Montre tes pied avant ☠️☠️ c aussi pour un pote', NULL, 0, 0, 1, 0, '2026-03-09 12:45:59'),
(6, 3, 9, '', 'assets/uploads/messages/1773073640_Inosuke.jpg', 0, 0, 1, 0, '2026-03-09 17:27:20'),
(7, 4, 9, 'Naa abuse passssss', NULL, 0, 1, 1, 0, '2026-03-09 17:35:40'),
(8, 5, 14, 'Alors comme ça tu fais des voc jusqu\'à 6h hein', NULL, 0, 1, 1, 0, '2026-03-09 17:54:54'),
(9, 5, 14, '🙈', NULL, 0, 0, 1, 0, '2026-03-09 17:56:03'),
(10, 3, 9, 'Yamal est le GOATTTTTTT plus fort que Dembele', 'assets/uploads/messages/1773080140_yamal.webp', 0, 0, 1, 0, '2026-03-09 19:15:40'),
(11, 5, 10, 'Lache ça mec', NULL, 0, 0, 0, 0, '2026-03-09 19:16:37'),
(12, 7, 10, 'hh', NULL, 0, 0, 1, 1, '2026-03-10 22:08:30'),
(13, 8, 10, 'ddd', NULL, 0, 0, 1, 1, '2026-03-10 22:15:58'),
(14, 9, 16, 'hh', NULL, 0, 0, 1, 1, '2026-03-10 22:28:27'),
(15, 10, 16, '🫨', NULL, 0, 0, 1, 1, '2026-03-10 22:28:35'),
(16, 11, 16, 'hhhhh', NULL, 0, 0, 1, 1, '2026-03-10 22:28:42'),
(17, 13, 11, 'njj', NULL, 0, 0, 1, 1, '2026-03-10 22:35:00'),
(18, 9, 9, 'YYY', NULL, 0, 0, 0, 1, '2026-03-11 01:03:33'),
(19, 11, 13, 'NN', NULL, 0, 0, 0, 1, '2026-03-11 03:40:58'),
(20, 12, 13, 'JJ', NULL, 0, 0, 1, 1, '2026-03-11 03:41:01'),
(21, 14, 13, 'qsdsfghjklk', NULL, 0, 0, 1, 1, '2026-03-11 03:44:45'),
(22, 3, 9, 'OUI JE SAIS', NULL, 0, 0, 1, 1, '2026-03-11 03:46:09');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `expediteur_id` int(11) DEFAULT NULL,
  `type` enum('demande_ami','ami_accepte','reaction','commentaire','partage','evenement','emploi','systeme') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `lien` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lue` tinyint(1) DEFAULT '0',
  `date_notification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `utilisateur_id`, `expediteur_id`, `type`, `message`, `lien`, `lue`, `date_notification`) VALUES
(14, 9, 10, 'demande_ami', 'Yvon Fwala souhaite se connecter avec vous.', 'reseau.php#section-demandes', 1, '2026-03-09 12:13:08'),
(15, 10, 9, 'ami_accepte', 'Nassim Nadifi a accepté votre invitation.', 'reseau.php', 1, '2026-03-09 12:13:24'),
(16, 11, 9, 'demande_ami', 'Nassim Nadifi souhaite se connecter avec vous.', 'reseau.php#section-demandes', 1, '2026-03-09 12:28:57'),
(17, 9, 11, 'ami_accepte', 'Jacque Wu a accepté votre invitation.', 'reseau.php', 1, '2026-03-09 12:29:39'),
(18, 10, 14, 'demande_ami', 'Hider Snacel souhaite se connecter avec vous.', 'reseau.php#section-demandes', 1, '2026-03-09 17:49:06'),
(19, 14, 10, 'ami_accepte', 'Yvon Fwala a accepté votre invitation.', 'reseau.php', 1, '2026-03-09 17:49:38'),
(20, 14, 9, 'demande_ami', 'Nassim Nadifi souhaite se connecter avec vous.', 'reseau.php#section-demandes', 1, '2026-03-09 19:09:52'),
(21, 9, 10, 'reaction', 'Yvon Fwala a réagi à votre publication.', 'index.php#post-1', 1, '2026-03-09 19:23:06'),
(22, 9, 10, 'commentaire', 'Yvon Fwala a commenté votre publication.', 'index.php#post-1', 1, '2026-03-09 19:23:07'),
(23, 13, 9, 'demande_ami', 'Nassim Nadifi souhaite se connecter avec vous.', 'reseau.php#section-demandes', 1, '2026-03-10 22:20:43'),
(24, 9, 13, 'ami_accepte', 'Nikola Vucic a accepté votre invitation.', 'reseau.php', 1, '2026-03-10 22:21:02'),
(25, 13, 16, 'demande_ami', 'Admin admin souhaite se connecter avec vous.', 'reseau.php#section-demandes', 1, '2026-03-10 22:26:16'),
(26, 9, 16, 'demande_ami', 'Admin admin souhaite se connecter avec vous.', 'reseau.php#section-demandes', 1, '2026-03-10 22:26:23'),
(27, 11, 16, 'demande_ami', 'Admin admin souhaite se connecter avec vous.', 'reseau.php#section-demandes', 1, '2026-03-10 22:26:28'),
(28, 16, 13, 'ami_accepte', 'Nikola Vucic a accepté votre invitation.', 'reseau.php', 1, '2026-03-10 22:26:43'),
(29, 16, 11, 'ami_accepte', 'Jacque Wu a accepté votre invitation.', 'reseau.php', 1, '2026-03-10 22:27:30'),
(30, 16, 9, 'ami_accepte', 'Nassim Nadifi a accepté votre invitation.', 'reseau.php', 1, '2026-03-10 22:28:00'),
(31, 10, 11, 'reaction', 'Jacque Wu a réagi à votre publication.', 'index.php#post-3', 1, '2026-03-11 02:32:07'),
(32, 10, 11, 'commentaire', 'Jacque Wu a commenté votre publication.', 'index.php#post-3', 1, '2026-03-11 02:32:45'),
(33, 9, 11, 'commentaire', 'Jacque Wu a commenté votre publication.', 'index.php#post-2', 1, '2026-03-11 02:37:52'),
(34, 9, 11, 'reaction', 'Jacque Wu a réagi à votre publication.', 'index.php#post-2', 1, '2026-03-11 02:38:07');

-- --------------------------------------------------------

--
-- Structure de la table `projets`
--

CREATE TABLE `projets` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `titre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `en_cours` tinyint(1) DEFAULT '0',
  `lien` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `projets`
--

INSERT INTO `projets` (`id`, `utilisateur_id`, `titre`, `description`, `date_debut`, `date_fin`, `en_cours`, `lien`, `type`) VALUES
(1, 9, 'Expunks', 'Développement d\'une version du jeu Exapunks en 2D', '2026-03-03', '2026-03-11', 0, '', 'Projet académique'),
(2, 14, 'Application E-Commerce', 'Développement d\'une plateforme e-commerce complète avec gestion de panier, paiement en ligne et administration.', NULL, NULL, 0, 'https://github.com/hsnacel/ecommerce', 'Projet Web'),
(3, 14, 'API REST pour Gestion de Tâches', 'Création d\'une API REST sécurisée avec authentification JWT pour la gestion de tâches collaboratives.', NULL, NULL, 0, 'https://github.com/hidersnacel/task-api', 'Projet Backend'),
(4, 14, 'Application Mobile React Native', 'Développement d\'une application mobile cross-platform pour la gestion de budget personnel.', NULL, NULL, 0, 'https://github.com/hidersnacel/budget-app', 'Projet Mobile');

-- --------------------------------------------------------

--
-- Structure de la table `publications`
--

CREATE TABLE `publications` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `type` enum('statut','photo','video','cv','evenement','partage') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'statut',
  `contenu` text COLLATE utf8mb4_unicode_ci,
  `fichier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lieu` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `humeur` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visibilite` enum('public','amis','prive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public',
  `publication_originale_id` int(11) DEFAULT NULL,
  `date_publication` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `publications`
--

INSERT INTO `publications` (`id`, `utilisateur_id`, `type`, `contenu`, `fichier`, `lieu`, `humeur`, `visibilite`, `publication_originale_id`, `date_publication`, `date_modification`) VALUES
(1, 9, 'photo', 'J\'ai jamais été autant ému de voir un joueur sur le terrain !!🤩🤩🤩 Meilleur joueur du mondeeee', 'uploads/photos/file_69af0fe333b340.83993945.webp', '', '', 'public', NULL, '2026-03-09 19:22:27', '2026-03-09 21:45:44'),
(2, 9, 'photo', 'Les gars je viens de me réveiller mai je me dois de vous partager ça.\r\nLe prochain ballon d\'or ça sera lui 🤪🤪', 'uploads/photos/file_69b0be9b67c878.20568935.jpg', '', '', 'public', NULL, '2026-03-11 02:00:11', NULL),
(3, 10, 'photo', 'ça fais des années mais pour moi FMAB reste dans le top 3😼😼', 'uploads/photos/file_69b0c3ddd20103.60931103.jpg', 'Paris', '', 'public', NULL, '2026-03-11 02:22:37', NULL),
(4, 10, 'video', 'Je viens de tomber sur cette vidéo, comment Kawhi vient de cook OG🤩🤩', 'uploads/videos/file_69b0c4b86881b5.02323761.mp4', 'Lyon, France', '', 'public', NULL, '2026-03-11 02:26:16', NULL),
(5, 11, 'video', 'J\'étais au salon de la technologie la semaine dernière et j\'ai pu voir les dernières nouveauté de Tesla et c\'était incroyable!!!!!🤠🤠', 'uploads/videos/file_69b0c5f158a203.18520454.mp4', 'Nice', '', 'public', NULL, '2026-03-11 02:31:29', NULL),
(6, 14, 'photo', 'Pour des gens qui critiquaient au début je vois que ça a vite changé d\'avis....', 'uploads/photos/file_69b0c8a2762dc9.80136042.jpeg', 'Londres', '', 'public', NULL, '2026-03-11 02:42:58', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `reactions`
--

CREATE TABLE `reactions` (
  `id` int(11) NOT NULL,
  `publication_id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `type` enum('jaime','bravo','soutien','interessant') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'jaime',
  `date_reaction` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `reactions`
--

INSERT INTO `reactions` (`id`, `publication_id`, `utilisateur_id`, `type`, `date_reaction`) VALUES
(1, 1, 10, 'jaime', '2026-03-09 19:23:06'),
(3, 1, 9, 'jaime', '2026-03-11 01:08:43'),
(4, 5, 11, 'jaime', '2026-03-11 02:32:05'),
(5, 3, 11, 'jaime', '2026-03-11 02:32:07'),
(6, 2, 11, 'jaime', '2026-03-11 02:38:07');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL,
  `pseudo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mot_de_passe` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('auteur','administrateur') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'auteur',
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'assets/images/default_avatar.svg',
  `image_fond` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'assets/images/default_bg.jpg',
  `titre` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `biographie` text COLLATE utf8mb4_unicode_ci,
  `localisation` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_web` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `date_inscription` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `derniere_connexion` datetime DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `pseudo`, `email`, `mot_de_passe`, `nom`, `prenom`, `role`, `photo`, `image_fond`, `titre`, `biographie`, `localisation`, `site_web`, `telephone`, `date_naissance`, `date_inscription`, `derniere_connexion`, `actif`) VALUES
(9, 'nassimnadifi', 'nassim.nadifi@edu.ece.fr', '$2y$10$pn1Ex5OsiQNbAPnLoY7mB.VogfjEyPIIT.XJ/07L2rMun0vtTFFzu', 'Nadifi', 'Nassim', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', '', 'Tout pour les babies', 'Seoul, Corée du Sud', '', '+33 7 65 76 88 09', NULL, '2026-03-09 12:04:02', '2026-03-11 03:45:28', 1),
(10, 'yvonfwala', 'yvon.fwala@edu.ece.fr', '$2y$10$uQyTK654/Dsqsm7yvr1qkuEHxavqF/CDHEoR5UX8GFS.hde/3CBG.', 'Fwala', 'Yvon', 'auteur', 'uploads/avatars/avatar_10_1773078902.jpeg', 'assets/images/default_bg.jpg', NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-09 12:12:32', '2026-03-11 03:46:20', 1),
(11, 'jwu', 'jacque.wu@edu.ece.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Wu', 'Jacque', 'auteur', 'uploads/avatars/avatar_11_1773078815.jpeg', 'assets/images/default_bg.jpg', 'Étudiant ING2', 'Passionné de tech et de développement web.', NULL, NULL, NULL, NULL, '2026-03-09 12:23:54', '2026-03-11 02:27:19', 1),
(12, 'yhamouch', 'yacine.hamouch@edu.ece.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hamouch', 'Yacine', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'Étudiant ING2', 'Toujours prêt pour coder de nouveaux projets.', NULL, NULL, NULL, NULL, '2026-03-09 12:23:54', NULL, 1),
(13, 'nvucic', 'nikola.vucic@edu.ece.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Vucic', 'Nikola', 'administrateur', 'uploads/avatars/avatar_13_1773078878.jpeg', 'assets/images/default_bg.jpg', 'Ancien délégué', 'J\'aime les pieds', '', '', '', NULL, '2026-03-09 12:23:54', '2026-03-11 03:46:46', 1),
(14, 'hsnacel', 'hider.snacel@edu.ece.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Snacel', 'Hider', 'auteur', 'uploads/avatars/avatar_14_1773078785.jpeg', 'assets/images/default_bg.jpg', 'Développeur Full-Stack Senior', 'Développeur passionné avec 5 ans d\'expérience en développement web. Spécialisé en PHP, JavaScript et bases de données. Toujours à la recherche de nouveaux défis techniques.', 'Paris, France', 'https://hider.dev', '+33 6 12 34 56 78', NULL, '2026-03-09 12:23:54', '2026-03-11 02:42:04', 1),
(15, 'yyoung', 'young.young@edu.ece.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Young', 'Young', 'auteur', 'uploads/avatars/avatar_15_1773078852.jpeg', 'assets/images/default_bg.jpg', 'Étudiant ING3', 'Nothing to say', '', '', '', NULL, '2026-03-09 12:23:54', '2026-03-09 18:53:58', 1),
(16, 'adminadmin', 'admin.admin@edu.ece.fr', '$2y$10$PXsn9yENxMwoA5/emRksMuR1fGsV1UdOyfufUg4W0UfWQOL0U8z4u', 'admin', 'Admin', 'administrateur', 'uploads/avatars/avatar_16_1773078925.png', 'assets/images/default_bg.jpg', NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-09 15:25:58', '2026-03-11 03:18:20', 1),
(17, 'john.smith', 'john.smith@mit.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Smith', 'John', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'Computer Science Student', 'MIT student passionate about artificial intelligence and machine learning. Working on neural networks research.', 'Boston, MA, USA', NULL, NULL, '2003-09-12', '2026-03-11 00:33:29', NULL, 1),
(18, 'emily.johnson', 'emily.johnson@stanford.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Johnson', 'Emily', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'Software Engineering Student', 'Stanford student specializing in full-stack development. Love React, Node.js, and building scalable applications.', 'Palo Alto, CA, USA', NULL, NULL, '2004-01-25', '2026-03-11 00:33:29', NULL, 1),
(19, 'michael.brown', 'michael.brown@berkeley.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Brown', 'Michael', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'Data Science Student', 'UC Berkeley student focused on data analytics and big data. Python and R enthusiast.', 'Berkeley, CA, USA', NULL, NULL, '2003-06-18', '2026-03-11 00:33:29', NULL, 1),
(20, 'sarah.davis', 'sarah.davis@harvard.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Davis', 'Sarah', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'Cybersecurity Student', 'Harvard student studying cybersecurity and ethical hacking. Interested in network security and cryptography.', 'Cambridge, MA, USA', NULL, NULL, '2003-11-08', '2026-03-11 00:33:29', NULL, 1),
(21, 'james.wilson', 'james.wilson@nyu.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Wilson', 'James', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'Web Development Student', 'NYU student passionate about modern web technologies. Building projects with React, Vue, and Angular.', 'New York, NY, USA', NULL, NULL, '2004-03-22', '2026-03-11 00:33:29', NULL, 1),
(22, 'olivia.miller', 'olivia.miller@cmu.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Miller', 'Olivia', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'UI/UX Design Student', 'Carnegie Mellon student specializing in user experience design. Creating beautiful and functional interfaces.', 'Pittsburgh, PA, USA', NULL, NULL, '2003-07-30', '2026-03-11 00:33:29', NULL, 1),
(23, 'william.taylor', 'william.taylor@caltech.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Taylor', 'William', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'Computer Science Student', 'Caltech student interested in quantum computing and advanced algorithms. Research in computational theory.', 'Pasadena, CA, USA', NULL, NULL, '2003-05-14', '2026-03-11 00:33:29', NULL, 1),
(24, 'sophia.martinez', 'sophia.martinez@columbia.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Martinez', 'Sophia', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'Software Engineering Student', 'Columbia University student. Passionate about mobile app development and iOS programming.', 'New York, NY, USA', NULL, NULL, '2004-02-09', '2026-03-11 00:33:29', NULL, 1),
(25, 'daniel.anderson', 'daniel.anderson@utexas.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anderson', 'Daniel', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'Computer Science Student', 'UT Austin student. Love open source contributions and Linux system administration.', 'Austin, TX, USA', NULL, NULL, '2003-10-17', '2026-03-11 00:33:29', NULL, 1),
(26, 'isabella.thomas', 'isabella.thomas@umich.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Thomas', 'Isabella', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'Data Engineering Student', 'University of Michigan student. Specializing in big data pipelines and distributed systems.', 'Ann Arbor, MI, USA', NULL, NULL, '2003-12-23', '2026-03-11 00:33:29', NULL, 1),
(27, 'robert.taylor', 'robert.taylor@techcorp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Taylor', 'Robert', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'Senior Software Engineer at Google', '10+ years of experience in software development. Expert in distributed systems and cloud computing. Based in Silicon Valley.', 'Mountain View, CA, USA', 'https://roberttaylor.dev', '+1 (650) 555-0123', '1988-04-15', '2026-03-11 00:33:29', NULL, 1),
(28, 'jennifer.anderson', 'jennifer.anderson@startup.io', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anderson', 'Jennifer', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'Product Manager at Microsoft', 'Product manager with expertise in AI and cloud services. Passionate about building products that make a difference.', 'Seattle, WA, USA', 'https://jenniferanderson.io', '+1 (206) 555-0456', '1991-08-28', '2026-03-11 00:33:29', NULL, 1),
(29, 'david.thomas', 'david.thomas@amazon.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Thomas', 'David', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'DevOps Engineer at Amazon', 'DevOps specialist focused on AWS, Kubernetes, and CI/CD pipelines. Helping teams deploy faster and more reliably.', 'Seattle, WA, USA', NULL, '+1 (206) 555-0789', '1989-12-03', '2026-03-11 00:33:29', NULL, 1),
(30, 'lisa.martinez', 'lisa.martinez@apple.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Martinez', 'Lisa', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'iOS Developer at Apple', 'iOS developer creating amazing mobile experiences. Swift and SwiftUI enthusiast. Love clean code and beautiful design.', 'Cupertino, CA, USA', 'https://lisamartinez.dev', '+1 (408) 555-0321', '1992-05-17', '2026-03-11 00:33:29', NULL, 1),
(31, 'chris.lee', 'chris.lee@meta.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lee', 'Chris', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'Full-Stack Developer at Meta', 'Full-stack developer working on social media platforms. React, GraphQL, and React Native expert.', 'Menlo Park, CA, USA', NULL, '+1 (650) 555-0654', '1990-10-09', '2026-03-11 00:33:29', NULL, 1),
(32, 'amanda.white', 'amanda.white@netflix.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'White', 'Amanda', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'Data Engineer at Netflix', 'Data engineer building scalable data pipelines. Working with big data technologies and streaming platforms.', 'Los Gatos, CA, USA', 'https://amandawhite.tech', '+1 (408) 555-0987', '1993-02-26', '2026-03-11 00:33:29', NULL, 1),
(33, 'kevin.harris', 'kevin.harris@tesla.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Harris', 'Kevin', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'Software Engineer at Tesla', 'Working on autonomous driving systems and vehicle software. Passionate about AI and robotics.', 'Palo Alto, CA, USA', NULL, '+1 (650) 555-0112', '1991-07-19', '2026-03-11 00:33:29', NULL, 1),
(34, 'rachel.clark', 'rachel.clark@airbnb.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Clark', 'Rachel', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'Senior Frontend Developer at Airbnb', 'Frontend developer specializing in React and TypeScript. Building beautiful user experiences for travelers worldwide.', 'San Francisco, CA, USA', 'https://rachelclark.dev', '+1 (415) 555-0234', '1990-03-11', '2026-03-11 00:33:29', NULL, 1),
(35, 'matthew.lewis', 'matthew.lewis@uber.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lewis', 'Matthew', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'Backend Engineer at Uber', 'Backend engineer working on ride-sharing infrastructure. Expert in microservices and distributed systems.', 'San Francisco, CA, USA', NULL, '+1 (415) 555-0345', '1989-09-25', '2026-03-11 00:33:29', NULL, 1),
(36, 'laura.walker', 'laura.walker@twitter.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Walker', 'Laura', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'Security Engineer at Twitter', 'Cybersecurity specialist focused on platform security and threat detection. Protecting millions of users daily.', 'San Francisco, CA, USA', 'https://laurawalker.security', '+1 (415) 555-0456', '1992-11-07', '2026-03-11 00:33:29', NULL, 1),
(37, 'ryan.rodriguez', 'ryan.rodriguez@startup.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rodriguez', 'Ryan', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'Founder & CEO of TechStart', 'Serial entrepreneur building the next generation of SaaS products. Y Combinator alumni. Always looking for talented developers.', 'San Francisco, CA, USA', 'https://techstart.io', '+1 (415) 555-0111', '1987-09-14', '2026-03-11 00:33:29', NULL, 1),
(38, 'jessica.garcia', 'jessica.garcia@innovate.tech', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Garcia', 'Jessica', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'CTO & Co-Founder', 'Tech lead and co-founder of an AI startup. Former Google engineer. Passionate about machine learning and innovation.', 'Austin, TX, USA', 'https://innovate.tech', '+1 (512) 555-0222', '1989-06-21', '2026-03-11 00:33:29', NULL, 1),
(39, 'brian.young', 'brian.young@venture.capital', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Young', 'Brian', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'Founder & CEO of CloudScale', 'Building cloud infrastructure solutions for enterprises. Former AWS engineer. Passionate about scalability and performance.', 'Seattle, WA, USA', 'https://cloudscale.io', '+1 (206) 555-0333', '1986-04-03', '2026-03-11 00:33:29', NULL, 1),
(40, 'nicole.king', 'nicole.king@fintech.startup', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'King', 'Nicole', 'auteur', 'assets/images/default_avatar.svg', 'assets/images/default_bg.jpg', 'Co-Founder & CPO', 'Co-founder of a fintech startup. Former product manager at Stripe. Revolutionizing digital payments.', 'New York, NY, USA', 'https://fintech.startup', '+1 (212) 555-0444', '1990-08-15', '2026-03-11 00:33:29', NULL, 1);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `albums`
--
ALTER TABLE `albums`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `album_photos`
--
ALTER TABLE `album_photos`
  ADD PRIMARY KEY (`album_id`,`publication_id`),
  ADD KEY `publication_id` (`publication_id`);

--
-- Index pour la table `appels`
--
ALTER TABLE `appels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `idx_receveur_statut` (`receveur_id`,`statut`),
  ADD KEY `idx_appelant` (`appelant_id`);

--
-- Index pour la table `appel_participants`
--
ALTER TABLE `appel_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_appel_participant` (`appel_id`,`utilisateur_id`),
  ADD KEY `appel_id` (`appel_id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `commentaires`
--
ALTER TABLE `commentaires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`),
  ADD KEY `idx_publication` (`publication_id`);

--
-- Index pour la table `competences`
--
ALTER TABLE `competences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `connexions`
--
ALTER TABLE `connexions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_connexion` (`demandeur_id`,`destinataire_id`),
  ADD KEY `idx_demandeur` (`demandeur_id`),
  ADD KEY `idx_destinataire` (`destinataire_id`);

--
-- Index pour la table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD PRIMARY KEY (`conversation_id`,`utilisateur_id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `emplois`
--
ALTER TABLE `emplois`
  ADD PRIMARY KEY (`id`),
  ADD KEY `publie_par` (`publie_par`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_date` (`date_publication`);

--
-- Index pour la table `evenements`
--
ALTER TABLE `evenements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `organisateur_id` (`organisateur_id`),
  ADD KEY `idx_date` (`date_debut`);

--
-- Index pour la table `formations`
--
ALTER TABLE `formations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_utilisateur` (`utilisateur_id`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conversation` (`conversation_id`),
  ADD KEY `idx_expediteur` (`expediteur_id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expediteur_id` (`expediteur_id`),
  ADD KEY `idx_utilisateur` (`utilisateur_id`);

--
-- Index pour la table `projets`
--
ALTER TABLE `projets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_utilisateur` (`utilisateur_id`);

--
-- Index pour la table `publications`
--
ALTER TABLE `publications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `publication_originale_id` (`publication_originale_id`),
  ADD KEY `idx_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_date` (`date_publication`);

--
-- Index pour la table `reactions`
--
ALTER TABLE `reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reaction` (`publication_id`,`utilisateur_id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pseudo` (`pseudo`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_pseudo` (`pseudo`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `albums`
--
ALTER TABLE `albums`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `appels`
--
ALTER TABLE `appels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT pour la table `appel_participants`
--
ALTER TABLE `appel_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `commentaires`
--
ALTER TABLE `commentaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `competences`
--
ALTER TABLE `competences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `connexions`
--
ALTER TABLE `connexions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT pour la table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `emplois`
--
ALTER TABLE `emplois`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT pour la table `evenements`
--
ALTER TABLE `evenements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT pour la table `formations`
--
ALTER TABLE `formations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT pour la table `projets`
--
ALTER TABLE `projets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `publications`
--
ALTER TABLE `publications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `reactions`
--
ALTER TABLE `reactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `albums`
--
ALTER TABLE `albums`
  ADD CONSTRAINT `albums_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `album_photos`
--
ALTER TABLE `album_photos`
  ADD CONSTRAINT `album_photos_ibfk_1` FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `album_photos_ibfk_2` FOREIGN KEY (`publication_id`) REFERENCES `publications` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `appels`
--
ALTER TABLE `appels`
  ADD CONSTRAINT `appels_ibfk_1` FOREIGN KEY (`appelant_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appels_ibfk_2` FOREIGN KEY (`receveur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appels_ibfk_3` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `appel_participants`
--
ALTER TABLE `appel_participants`
  ADD CONSTRAINT `appel_participants_ibfk_1` FOREIGN KEY (`appel_id`) REFERENCES `appels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appel_participants_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `commentaires`
--
ALTER TABLE `commentaires`
  ADD CONSTRAINT `commentaires_ibfk_1` FOREIGN KEY (`publication_id`) REFERENCES `publications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `commentaires_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `competences`
--
ALTER TABLE `competences`
  ADD CONSTRAINT `competences_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `connexions`
--
ALTER TABLE `connexions`
  ADD CONSTRAINT `connexions_ibfk_1` FOREIGN KEY (`demandeur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `connexions_ibfk_2` FOREIGN KEY (`destinataire_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD CONSTRAINT `conversation_participants_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversation_participants_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `emplois`
--
ALTER TABLE `emplois`
  ADD CONSTRAINT `emplois_ibfk_1` FOREIGN KEY (`publie_par`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `evenements`
--
ALTER TABLE `evenements`
  ADD CONSTRAINT `evenements_ibfk_1` FOREIGN KEY (`organisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `formations`
--
ALTER TABLE `formations`
  ADD CONSTRAINT `formations_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`expediteur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`expediteur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `projets`
--
ALTER TABLE `projets`
  ADD CONSTRAINT `projets_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `publications`
--
ALTER TABLE `publications`
  ADD CONSTRAINT `publications_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `publications_ibfk_2` FOREIGN KEY (`publication_originale_id`) REFERENCES `publications` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `reactions`
--
ALTER TABLE `reactions`
  ADD CONSTRAINT `reactions_ibfk_1` FOREIGN KEY (`publication_id`) REFERENCES `publications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reactions_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
