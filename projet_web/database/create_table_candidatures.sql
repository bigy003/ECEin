-- ============================================
-- Création de la table candidatures
-- ============================================
-- 
-- Cette table stocke les candidatures des utilisateurs
-- aux offres d'emploi et de bénévolat
-- ============================================

CREATE TABLE IF NOT EXISTS `candidatures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(11) NOT NULL,
  `emploi_id` int(11) NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `cv_fichier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_candidature` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('en_attente','acceptee','refusee') COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `emploi_id` (`emploi_id`),
  CONSTRAINT `candidatures_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `candidatures_ibfk_2` FOREIGN KEY (`emploi_id`) REFERENCES `emplois` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
