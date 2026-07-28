-- ============================================
-- Création de la table favoris_emplois
-- ============================================
-- 
-- Cette table stocke les offres d'emploi sauvegardées
-- par les utilisateurs (favoris/bookmarks)
-- ============================================

CREATE TABLE IF NOT EXISTS `favoris_emplois` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(11) NOT NULL,
  `emploi_id` int(11) NOT NULL,
  `date_ajout` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_favori` (`utilisateur_id`, `emploi_id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `emploi_id` (`emploi_id`),
  CONSTRAINT `favoris_emplois_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `favoris_emplois_ibfk_2` FOREIGN KEY (`emploi_id`) REFERENCES `emplois` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
