-- Script pour insérer plusieurs événements à venir
-- Base de données : reseausocial

USE reseausocial;

-- Récupérer l'ID d'un administrateur ou utilisateur pour organisateur_id
SET @admin_id = (SELECT id FROM utilisateurs WHERE role = 'administrateur' LIMIT 1);
SET @user_id = (SELECT id FROM utilisateurs WHERE id != @admin_id LIMIT 1);
SET @organisateur_id = COALESCE(@admin_id, @user_id, 1);

-- Insérer plusieurs événements à venir
INSERT INTO evenements (organisateur_id, titre, description, lieu, date_debut, date_fin, lien, est_officiel) VALUES

-- Événements officiels ECE
(@organisateur_id, 'Portes Ouvertes ECE Paris 2026', 'Venez découvrir l\'ECE Paris ! Visite des locaux, rencontres avec les étudiants et les enseignants, présentation des formations et des spécialités. Inscription gratuite.', 'ECE Paris - 37 Quai de Grenelle, 75015 Paris', '2026-03-15 10:00:00', '2026-03-15 17:00:00', 'https://ece.fr/portes-ouvertes', 1),

(@organisateur_id, 'Forum Emploi ECE 2026', 'Forum annuel emploi et alternance. Plus de 50 entreprises partenaires présentes pour rencontrer les étudiants ECE. Opportunités de stages, alternances et CDI.', 'ECE Paris - Grand Amphithéâtre', '2026-03-22 09:00:00', '2026-03-22 18:00:00', 'https://ece.fr/forum-emploi', 1),

(@organisateur_id, 'Journée Innovation & Tech ECE', 'Conférences et démonstrations sur les dernières innovations technologiques. IA, blockchain, IoT, cybersécurité. Ouvert à tous les étudiants.', 'ECE Paris - Campus principal', '2026-04-05 09:00:00', '2026-04-05 17:00:00', 'https://ece.fr/innovation-tech', 1),

(@organisateur_id, 'Gala de l\'ECE Paris 2026', 'Soirée de gala annuelle de l\'ECE Paris. Cocktail, dîner, animations et remise des diplômes. Tenue de soirée requise.', 'Palais des Congrès, Paris', '2026-05-20 19:00:00', '2026-05-20 23:30:00', 'https://ece.fr/gala', 1),

(@organisateur_id, 'Semaine Internationale ECE', 'Événement dédié à l\'international : conférences, stands pays, échanges avec les universités partenaires. Découvrez les opportunités à l\'étranger !', 'ECE Paris - Campus', '2026-04-12 09:00:00', '2026-04-16 17:00:00', 'https://ece.fr/international', 1),

-- Conférences tech
(@organisateur_id, 'Conférence : Intelligence Artificielle et Éthique', 'Conférence sur les enjeux éthiques de l\'IA animée par des experts du domaine. Débat et échanges avec le public.', 'ECE Paris - Salle de conférence A', '2026-03-18 14:00:00', '2026-03-18 17:00:00', 'https://ece.fr/conf-ia-ethique', 0),

(@organisateur_id, 'Workshop : Développement Web Moderne', 'Atelier pratique sur React, TypeScript et les dernières tendances du développement web. Apportez votre ordinateur portable.', 'ECE Paris - Salle informatique', '2026-03-25 14:00:00', '2026-03-25 18:00:00', 'https://ece.fr/workshop-web', 0),

(@organisateur_id, 'Meetup : Startup & Entrepreneuriat', 'Rencontre avec des entrepreneurs ECE. Partage d\'expériences, conseils pour lancer votre startup. Networking et apéritif.', 'ECE Paris - Espace coworking', '2026-04-02 18:00:00', '2026-04-02 20:30:00', 'https://ece.fr/meetup-startup', 0),

(@organisateur_id, 'Hackathon ECE 2026', 'Hackathon de 48h sur le thème "Tech for Good". Formez votre équipe et développez une solution innovante. Prix à gagner !', 'ECE Paris - Campus', '2026-04-08 09:00:00', '2026-04-10 18:00:00', 'https://ece.fr/hackathon', 1),

(@organisateur_id, 'Conférence : Cybersécurité & Protection des Données', 'Expert en cybersécurité partage les bonnes pratiques pour protéger vos données et celles de votre entreprise. RGPD et sécurité cloud.', 'ECE Paris - Amphithéâtre B', '2026-03-28 15:00:00', '2026-03-28 17:30:00', 'https://ece.fr/conf-cybersecurite', 0),

-- Événements étudiants
(@organisateur_id, 'Soirée Networking ECE Alumni', 'Rencontre entre étudiants et anciens élèves ECE. Échanges sur les parcours professionnels, conseils carrière. Cocktail offert.', 'Bar Le Comptoir, Paris 15e', '2026-03-20 19:00:00', '2026-03-20 22:00:00', NULL, 0),

(@organisateur_id, 'Tournoi E-Sport ECE', 'Tournoi de jeux vidéo entre étudiants. Valorant, League of Legends, Rocket League. Inscriptions par équipe. Lots à gagner !', 'ECE Paris - Salle gaming', '2026-04-01 14:00:00', '2026-04-01 20:00:00', 'https://ece.fr/esport', 0),

(@organisateur_id, 'Atelier : Préparation aux Entretiens', 'Workshop pour préparer vos entretiens d\'embauche et de stage. Simulation d\'entretiens, CV, lettre de motivation. Animé par le service carrière.', 'ECE Paris - Salle de formation', '2026-03-30 14:00:00', '2026-03-30 16:30:00', 'https://ece.fr/atelier-entretiens', 0),

(@organisateur_id, 'Conférence : Blockchain & Web3', 'Découvrez les applications de la blockchain et du Web3. Smart contracts, NFT, DeFi. Présentation par des experts du secteur.', 'ECE Paris - Salle de conférence', '2026-04-10 16:00:00', '2026-04-10 18:00:00', 'https://ece.fr/conf-blockchain', 0),

(@organisateur_id, 'Workshop : Data Science avec Python', 'Initiation à la data science : manipulation de données avec Pandas, visualisation avec Matplotlib, introduction au machine learning.', 'ECE Paris - Salle informatique', '2026-04-15 14:00:00', '2026-04-15 17:00:00', 'https://ece.fr/workshop-datascience', 0),

-- Événements externes
(@organisateur_id, 'Viva Technology 2026', 'Le plus grand événement tech d\'Europe ! Startups, innovations, conférences. Billets étudiants à prix réduit. Transport organisé depuis l\'ECE.', 'Paris Expo Porte de Versailles', '2026-05-15 09:00:00', '2026-05-17 18:00:00', 'https://vivatechnology.com', 0),

(@organisateur_id, 'Paris Web 2026', 'Conférence web pour développeurs et designers. Talks sur les dernières technologies web, UX/UI, accessibilité. Early bird jusqu\'au 1er avril.', 'Cité des Sciences, Paris', '2026-06-10 09:00:00', '2026-06-11 18:00:00', 'https://paris-web.fr', 0),

(@organisateur_id, 'Devoxx France 2026', 'Conférence Java et développement logiciel. Plus de 200 talks, workshops, keynotes. Réduction étudiante disponible.', 'Palais des Congrès, Paris', '2026-04-17 08:30:00', '2026-04-19 18:00:00', 'https://devoxx.fr', 0),

(@organisateur_id, 'Salon de l\'Alternance Paris', 'Salon dédié à l\'alternance et à l\'apprentissage. Rencontrez des entreprises recrutant en alternance. Entrée gratuite.', 'Parc des Expositions, Paris', '2026-05-05 10:00:00', '2026-05-06 18:00:00', 'https://salon-alternance.fr', 0),

(@organisateur_id, 'Startup Weekend Paris', 'Weekend intensif pour créer une startup en 54h. Idéation, prototypage, pitch. Équipes formées sur place. Nourriture incluse.', 'Station F, Paris', '2026-04-22 18:00:00', '2026-04-24 20:00:00', 'https://startupweekend.org/paris', 0),

-- Événements culturels et sociaux
(@organisateur_id, 'Soirée Cinéma ECE', 'Projection de film suivie d\'un débat. Thème : "Tech et Société". Popcorn offert !', 'ECE Paris - Salle polyvalente', '2026-03-22 19:00:00', '2026-03-22 22:00:00', NULL, 0),

(@organisateur_id, 'Tournoi de Football ECE', 'Tournoi de football entre promotions. Inscriptions par équipe de 7. Trophée et médailles pour les gagnants !', 'Stade de l\'ECE, Paris', '2026-04-06 14:00:00', '2026-04-06 18:00:00', 'https://ece.fr/football', 0),

(@organisateur_id, 'Journée Portes Ouvertes Masters', 'Découvrez les masters de l\'ECE : Big Data, Cybersécurité, IoT, Finance. Rencontres avec les responsables de formation.', 'ECE Paris - Campus', '2026-05-10 10:00:00', '2026-05-10 16:00:00', 'https://ece.fr/masters', 1),

(@organisateur_id, 'Conférence : Green Tech & Développement Durable', 'Comment la tech peut contribuer au développement durable. Exemples concrets, solutions innovantes. Conférence suivie d\'un débat.', 'ECE Paris - Amphithéâtre principal', '2026-04-20 15:00:00', '2026-04-20 17:30:00', 'https://ece.fr/greentech', 0),

(@organisateur_id, 'Workshop : Git & Collaboration', 'Apprenez Git, GitHub, et les bonnes pratiques de collaboration en équipe. Workflow, branches, pull requests. Atelier pratique.', 'ECE Paris - Salle informatique', '2026-03-27 14:00:00', '2026-03-27 17:00:00', 'https://ece.fr/workshop-git', 0);

-- Afficher le nombre d'événements insérés
SELECT CONCAT('✅ ', COUNT(*), ' événements insérés avec succès !') AS resultat FROM evenements WHERE date_debut >= NOW();
