-- ============================================
-- Insertion d'offres de bénévolat
-- ============================================
-- 
-- Ce fichier insère des offres de bénévolat variées
-- dans la table `emplois` avec le type 'benevole'
--
-- IMPORTANT : Assurez-vous qu'il y a au moins un utilisateur
-- avec l'ID 16 (ou modifiez les IDs dans publie_par)
-- ============================================

INSERT INTO `emplois` (`publie_par`, `titre`, `entreprise`, `description`, `type`, `lieu`, `salaire`, `date_debut`, `date_limite`, `lien_candidature`, `contact_email`, `date_publication`, `actif`) VALUES

-- Mission 1 : Accompagnement scolaire
(16, 'Accompagnement scolaire auprès d\'enfants défavorisés', 'Association Solidarité Éducation', 'Rejoignez notre équipe de bénévoles pour accompagner des enfants de 8 à 12 ans dans leur scolarité. Mission : 2h par semaine, aide aux devoirs, soutien en mathématiques et français. Formation fournie. Impact réel sur l\'avenir des enfants !', 'benevole', 'Paris 19e', '2h/semaine, engagement 6 mois minimum', '2026-04-01', '2026-03-25', 'https://solidarite-education.fr/benevolat', 'benevolat@solidarite-education.fr', NOW(), 1),

-- Mission 2 : Distribution alimentaire
(16, 'Distribution alimentaire - Banque Alimentaire', 'Banque Alimentaire de Paris', 'Aidez-nous à distribuer des denrées alimentaires aux personnes en situation de précarité. Tâches : tri, conditionnement et distribution. Horaires flexibles : samedi matin ou mercredi après-midi. Mission enrichissante et solidaire !', 'benevole', 'Paris 12e', '3h/semaine, horaires flexibles', '2026-03-20', '2026-03-15', 'https://banquealimentaire.org/benevolat', 'contact@banquealimentaire-paris.fr', NOW(), 1),

-- Mission 3 : Accompagnement personnes âgées
(16, 'Visites et accompagnement de personnes âgées isolées', 'Association Les Petits Frères des Pauvres', 'Offrez votre temps et votre écoute aux personnes âgées isolées. Visites à domicile, sorties culturelles, aide aux courses. Engagement : 1 à 2 fois par mois. Formation et suivi assurés. Une expérience humaine inoubliable.', 'benevole', 'Paris 11e', '1-2 fois/mois, engagement 1 an', '2026-04-15', '2026-04-01', 'https://petitsfreres.paris.fr/benevolat', 'benevolat@petitsfreres-paris.fr', NOW(), 1),

-- Mission 4 : Protection de l'environnement
(16, 'Bénévole environnement - Nettoyage et sensibilisation', 'Green Paris Initiative', 'Participez à nos actions de protection de l\'environnement : nettoyage de parcs, sensibilisation du public, organisation d\'événements éco-responsables. Mission : 1 samedi par mois. Pour un Paris plus vert !', 'benevole', 'Paris (plusieurs arrondissements)', '1 samedi/mois, engagement 6 mois', '2026-03-25', '2026-03-20', 'https://greenparis.fr/participer', 'contact@greenparis.fr', NOW(), 1),

-- Mission 5 : Aide aux réfugiés
(16, 'Accompagnement et cours de français pour réfugiés', 'Association Accueil Réfugiés Paris', 'Aidez des réfugiés à s\'intégrer en France : cours de français (FLE), aide administrative, accompagnement dans les démarches. Mission : 3h par semaine. Formation linguistique et interculturelle fournie.', 'benevole', 'Paris 10e', '3h/semaine, engagement 6 mois', '2026-04-10', '2026-04-01', 'https://accueil-refugies-paris.fr/benevolat', 'benevolat@accueil-refugies-paris.fr', NOW(), 1),

-- Mission 6 : Protection des animaux
(16, 'Bénévole refuge animalier - Soins et adoption', 'Refuge Animalier de Paris', 'Rejoignez notre équipe pour prendre soin des animaux abandonnés : soins quotidiens, promenades, socialisation, aide aux adoptions. Mission : 4h par semaine. Passion pour les animaux requise !', 'benevole', 'Paris 20e', '4h/semaine, horaires flexibles', '2026-04-05', '2026-03-28', 'https://refuge-animalier-paris.fr/benevolat', 'benevolat@refuge-animalier-paris.fr', NOW(), 1),

-- Mission 7 : Handicap et inclusion
(16, 'Accompagnement personnes en situation de handicap', 'Association Handi-Sport Paris', 'Aidez des personnes en situation de handicap à pratiquer des activités sportives et culturelles. Accompagnement, encadrement, soutien. Mission : 2h par semaine. Formation adaptée fournie.', 'benevole', 'Paris 15e', '2h/semaine, engagement 1 an', '2026-04-20', '2026-04-10', 'https://handisport-paris.fr/benevolat', 'contact@handisport-paris.fr', NOW(), 1),

-- Mission 8 : Culture et patrimoine
(16, 'Guide bénévole musée - Médiation culturelle', 'Musée des Arts et Métiers', 'Devenez guide bénévole dans notre musée ! Accueil des visiteurs, médiation culturelle, animations. Mission : 1 dimanche par mois. Formation en histoire des techniques fournie. Passion pour la culture requise.', 'benevole', 'Paris 3e', '1 dimanche/mois, engagement 1 an', '2026-05-01', '2026-04-20', 'https://arts-et-metiers.net/benevolat', 'benevolat@arts-et-metiers.net', NOW(), 1),

-- Mission 9 : Santé et prévention
(16, 'Bénévole prévention santé - Stands d\'information', 'Association Santé Publique Paris', 'Participez à nos actions de prévention santé : stands d\'information, dépistages, sensibilisation. Mission : 1 samedi par mois. Formation en santé publique fournie. Pour une meilleure santé pour tous !', 'benevole', 'Paris (événements ponctuels)', '1 samedi/mois, engagement 6 mois', '2026-04-15', '2026-04-05', 'https://sante-publique-paris.fr/benevolat', 'contact@sante-publique-paris.fr', NOW(), 1),

-- Mission 10 : Numérique et inclusion
(16, 'Formation numérique pour seniors', 'Association Numérique Solidaire', 'Aidez des seniors à maîtriser le numérique : utilisation d\'un smartphone, navigation internet, démarches en ligne. Mission : 2h par semaine. Patience et pédagogie requises !', 'benevole', 'Paris 13e', '2h/semaine, engagement 6 mois', '2026-04-10', '2026-03-30', 'https://numerique-solidaire.fr/benevolat', 'benevolat@numerique-solidaire.fr', NOW(), 1),

-- Mission 11 : Aide aux sans-abri
(16, 'Maraude de nuit - Aide aux sans-abri', 'Association Solidarité Nocturne', 'Rejoignez nos maraudes de nuit pour aller à la rencontre des personnes sans-abri. Distribution de repas chauds, écoute, orientation vers les structures d\'aide. Mission : 1 soirée par mois. Engagement humain fort.', 'benevole', 'Paris (itinérant)', '1 soirée/mois, engagement 6 mois', '2026-04-01', '2026-03-25', 'https://solidarite-nocturne.fr/benevolat', 'contact@solidarite-nocturne.fr', NOW(), 1),

-- Mission 12 : Éducation et jeunesse
(16, 'Mentorat jeunes en difficulté', 'Association Mentorat Jeunesse', 'Devenez mentor pour un jeune de 15-18 ans en difficulté scolaire ou sociale. Accompagnement personnalisé, aide à l\'orientation, soutien moral. Mission : 2h toutes les 2 semaines. Formation en mentorat fournie.', 'benevole', 'Paris (plusieurs arrondissements)', '2h/2 semaines, engagement 1 an', '2026-04-15', '2026-04-05', 'https://mentorat-jeunesse.fr/benevolat', 'benevolat@mentorat-jeunesse.fr', NOW(), 1);
