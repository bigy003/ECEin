-- Script pour insérer plusieurs offres d'emploi
-- Base de données : reseausocial

USE reseausocial;

-- Récupérer l'ID d'un administrateur ou utilisateur pour publier_par
SET @admin_id = (SELECT id FROM utilisateurs WHERE role = 'administrateur' LIMIT 1);
SET @user_id = (SELECT id FROM utilisateurs WHERE id != @admin_id LIMIT 1);
SET @publie_par = COALESCE(@admin_id, @user_id, 1);

-- Insérer plusieurs offres d'emploi
INSERT INTO emplois (publie_par, titre, entreprise, description, type, lieu, salaire, date_debut, date_limite, lien_candidature, contact_email, actif) VALUES

-- Stages
(@publie_par, 'Développeur Full-Stack (Stage)', 'TechCorp Paris', 'Nous recherchons un stagiaire développeur full-stack pour rejoindre notre équipe de développement. Vous travaillerez sur des projets web modernes utilisant React, Node.js et MongoDB. Environnement dynamique et formateur.', 'stage', 'Paris 15e', '1200€/mois', '2026-04-01', '2026-03-25', 'https://techcorp.fr/candidature', 'rh@techcorp.fr', 1),

(@publie_par, 'Stage Développeur Mobile iOS/Android', 'AppMobile Solutions', 'Stage de 6 mois pour développer des applications mobiles natives. Vous maîtrisez Swift/Kotlin et souhaitez apprendre les meilleures pratiques du développement mobile. Équipe jeune et bienveillante.', 'stage', 'Paris 11e', '1100€/mois', '2026-05-01', '2026-04-15', 'https://appmobile.fr/recrutement', 'recrutement@appmobile.fr', 1),

(@publie_par, 'Stage Data Science & Machine Learning', 'DataLab Analytics', 'Rejoignez notre équipe de data scientists pour travailler sur des projets d\'intelligence artificielle. Python, TensorFlow, analyse de données. Stage de 6 mois avec possibilité d\'embauche.', 'stage', 'Paris 9e', '1300€/mois', '2026-04-15', '2026-04-01', 'https://datalab.fr/careers', 'jobs@datalab.fr', 1),

(@publie_par, 'Stage Développeur Backend', 'CloudServices Pro', 'Développement d\'APIs REST, microservices, bases de données. Stack : Java/Spring Boot ou Node.js. Stage de 4 à 6 mois dans une startup en forte croissance.', 'stage', 'Paris 2e', '1150€/mois', '2026-06-01', '2026-05-15', 'https://cloudservices.fr/jobs', 'hr@cloudservices.fr', 1),

(@publie_par, 'Stage DevOps & Cloud', 'InfraTech', 'Découvrez le monde du DevOps : Docker, Kubernetes, CI/CD, AWS. Stage de 6 mois pour apprendre les outils modernes d\'infrastructure cloud. Formation interne fournie.', 'stage', 'Paris 13e', '1250€/mois', '2026-05-01', '2026-04-20', 'https://infratech.fr/recrutement', 'contact@infratech.fr', 1),

-- Alternances
(@publie_par, 'Alternance Développeur Full-Stack', 'StartupTech', 'Alternance de 12 à 24 mois en développement web. Stack moderne : React, TypeScript, Node.js, PostgreSQL. Rythme : 3 semaines entreprise / 1 semaine école. Ambiance startup !', 'alternance', 'Paris 10e', '1500€/mois', '2026-09-01', '2026-06-30', 'https://startuptech.fr/alternance', 'alternance@startuptech.fr', 1),

(@publie_par, 'Alternance Data Engineer', 'BigData Corp', 'Formation en alternance sur 2 ans. Traitement de données massives, pipelines ETL, Spark, Hadoop. Rythme : 2 semaines entreprise / 2 semaines école. Excellente opportunité de carrière.', 'alternance', 'Paris 8e', '1600€/mois', '2026-09-01', '2026-07-15', 'https://bigdatacorp.fr/careers', 'recrutement@bigdatacorp.fr', 1),

(@publie_par, 'Alternance Cybersécurité', 'SecureNet', 'Apprenez la cybersécurité en alternance : tests de pénétration, audit de sécurité, protection des systèmes. Formation certifiante incluse. Rythme flexible selon votre école.', 'alternance', 'Paris 16e', '1550€/mois', '2026-09-01', '2026-07-01', 'https://securenet.fr/alternance', 'jobs@securenet.fr', 1),

(@publie_par, 'Alternance Développeur Blockchain', 'BlockChain Solutions', 'Développement d\'applications blockchain, smart contracts, Web3. Technologies : Solidity, Ethereum, Web3.js. Alternance de 12 mois minimum. Innovation garantie !', 'alternance', 'Paris 1er', '1450€/mois', '2026-09-01', '2026-06-15', 'https://blockchainsolutions.fr/jobs', 'hr@blockchainsolutions.fr', 1),

-- CDI
(@publie_par, 'Développeur Full-Stack Senior', 'Enterprise Solutions', 'Poste CDI pour développeur expérimenté. Leadership technique, architecture logicielle, mentoring junior. Stack : .NET, Angular, Azure. Package attractif + avantages.', 'cdi', 'Paris 17e', '45000-55000€/an', '2026-05-01', '2026-04-15', 'https://enterprise-solutions.fr/careers', 'recrutement@enterprise-solutions.fr', 1),

(@publie_par, 'Ingénieur DevOps', 'CloudPlatform Inc', 'CDI pour ingénieur DevOps expérimenté. Gestion infrastructure cloud, automatisation, monitoring. Technologies : AWS, Terraform, Kubernetes. Télétravail partiel possible.', 'cdi', 'Paris 12e', '50000-60000€/an', '2026-06-01', '2026-05-01', 'https://cloudplatform.fr/jobs', 'jobs@cloudplatform.fr', 1),

(@publie_par, 'Data Scientist', 'AI Innovations', 'CDI pour data scientist passionné par l\'IA. Développement de modèles ML, analyse prédictive, NLP. Python, TensorFlow, PyTorch. Environnement de recherche appliquée.', 'cdi', 'Paris 5e', '48000-58000€/an', '2026-05-15', '2026-04-30', 'https://ai-innovations.fr/careers', 'careers@ai-innovations.fr', 1),

-- CDD
(@publie_par, 'Développeur Frontend (CDD 12 mois)', 'WebAgency Pro', 'CDD de 12 mois renouvelable. Développement d\'interfaces utilisateur modernes. React, Vue.js, TypeScript. Projets variés pour clients prestigieux.', 'cdd', 'Paris 3e', '35000-42000€/an', '2026-04-01', '2026-03-20', 'https://webagency-pro.fr/recrutement', 'contact@webagency-pro.fr', 1),

(@publie_par, 'Développeur Backend Python (CDD 6 mois)', 'PythonDev Studio', 'CDD de 6 mois pour projet spécifique. Développement API REST, Django, FastAPI. Possibilité de CDI à l\'issue du CDD. Équipe technique de haut niveau.', 'cdd', 'Paris 6e', '38000€/an', '2026-04-15', '2026-04-01', 'https://pythondev.fr/jobs', 'jobs@pythondev.fr', 1),

-- Freelance
(@publie_par, 'Développeur WordPress Freelance', 'Digital Agency', 'Mission freelance pour développement de sites WordPress sur mesure. Durée : 3-6 mois. Télétravail possible. Tarif : 300-400€/jour selon expérience.', 'freelance', 'Paris / Télétravail', '300-400€/jour', '2026-04-01', '2026-03-25', 'https://digital-agency.fr/freelance', 'freelance@digital-agency.fr', 1),

(@publie_par, 'Consultant Cloud AWS Freelance', 'Cloud Consulting', 'Mission freelance pour migration vers AWS. Durée : 2-4 mois. Expertise AWS requise. Télétravail possible. Tarif : 500-600€/jour.', 'freelance', 'Paris / Télétravail', '500-600€/jour', '2026-05-01', '2026-04-15', 'https://cloud-consulting.fr/missions', 'missions@cloud-consulting.fr', 1),

-- Stages supplémentaires
(@publie_par, 'Stage UX/UI Designer', 'DesignStudio', 'Stage de 6 mois en design d\'interfaces utilisateur. Figma, Adobe XD, prototypage. Vous travaillerez sur des projets clients réels. Portfolio requis.', 'stage', 'Paris 4e', '1000€/mois', '2026-05-01', '2026-04-20', 'https://designstudio.fr/recrutement', 'recrutement@designstudio.fr', 1),

(@publie_par, 'Stage Testeur QA / QA Engineer', 'Quality Assurance Pro', 'Stage en assurance qualité logicielle. Tests manuels et automatisés, Selenium, Cypress. Apprentissage des méthodologies Agile. Stage de 6 mois.', 'stage', 'Paris 14e', '1100€/mois', '2026-06-01', '2026-05-15', 'https://qa-pro.fr/careers', 'jobs@qa-pro.fr', 1),

(@publie_par, 'Stage Développeur Game', 'GameStudio Paris', 'Stage en développement de jeux vidéo. Unity, C#, game design. Projet de jeu mobile ou PC. Passion pour le gaming requise ! Stage de 4-6 mois.', 'stage', 'Paris 20e', '1050€/mois', '2026-05-15', '2026-05-01', 'https://gamestudio-paris.fr/recrutement', 'recrutement@gamestudio-paris.fr', 1),

(@publie_par, 'Stage Développeur IoT', 'IoT Solutions', 'Stage en développement d\'objets connectés. Arduino, Raspberry Pi, protocoles IoT. Projets innovants dans la domotique et l\'industrie 4.0. Stage de 6 mois.', 'stage', 'Paris 19e', '1200€/mois', '2026-06-01', '2026-05-20', 'https://iot-solutions.fr/jobs', 'contact@iot-solutions.fr', 1),

-- Alternances supplémentaires
(@publie_par, 'Alternance Product Manager', 'ProductTech', 'Alternance en product management. Définition de produits, roadmap, analyse utilisateur. Rythme : 3 semaines entreprise / 1 semaine école. Formation interne.', 'alternance', 'Paris 7e', '1400€/mois', '2026-09-01', '2026-07-01', 'https://producttech.fr/alternance', 'alternance@producttech.fr', 1),

(@publie_par, 'Alternance Consultant IT', 'IT Consulting Group', 'Alternance en conseil informatique. Analyse de besoins clients, architecture de solutions. Rythme flexible. Excellente formation commerciale et technique.', 'alternance', 'Paris 8e', '1500€/mois', '2026-09-01', '2026-07-15', 'https://it-consulting.fr/careers', 'recrutement@it-consulting.fr', 1);

-- Afficher le nombre d'offres insérées
SELECT CONCAT('✅ ', COUNT(*), ' offres d\'emploi insérées avec succès !') AS resultat FROM emplois WHERE actif = 1;
