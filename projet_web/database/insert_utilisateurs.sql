-- Script SQL pour ajouter des utilisateurs américains
-- Mot de passe par défaut pour tous : "password123" (hashé avec bcrypt)
-- Pour se connecter, utilisez le pseudo et le mot de passe "password123"

-- Hash du mot de passe "password123" généré avec password_hash('password123', PASSWORD_BCRYPT)
SET @password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- ===================== UTILISATEURS AMÉRICAINS =====================

-- Étudiants américains (avec emails .edu)
INSERT INTO `utilisateurs` (`pseudo`, `email`, `mot_de_passe`, `nom`, `prenom`, `role`, `titre`, `biographie`, `localisation`, `date_naissance`, `date_inscription`) VALUES
('john.smith', 'john.smith@mit.edu', @password_hash, 'Smith', 'John', 'auteur', 'Computer Science Student', 'MIT student passionate about artificial intelligence and machine learning. Working on neural networks research.', 'Boston, MA, USA', '2003-09-12', NOW()),
('emily.johnson', 'emily.johnson@stanford.edu', @password_hash, 'Johnson', 'Emily', 'auteur', 'Software Engineering Student', 'Stanford student specializing in full-stack development. Love React, Node.js, and building scalable applications.', 'Palo Alto, CA, USA', '2004-01-25', NOW()),
('michael.brown', 'michael.brown@berkeley.edu', @password_hash, 'Brown', 'Michael', 'auteur', 'Data Science Student', 'UC Berkeley student focused on data analytics and big data. Python and R enthusiast.', 'Berkeley, CA, USA', '2003-06-18', NOW()),
('sarah.davis', 'sarah.davis@harvard.edu', @password_hash, 'Davis', 'Sarah', 'auteur', 'Cybersecurity Student', 'Harvard student studying cybersecurity and ethical hacking. Interested in network security and cryptography.', 'Cambridge, MA, USA', '2003-11-08', NOW()),
('james.wilson', 'james.wilson@nyu.edu', @password_hash, 'Wilson', 'James', 'auteur', 'Web Development Student', 'NYU student passionate about modern web technologies. Building projects with React, Vue, and Angular.', 'New York, NY, USA', '2004-03-22', NOW()),
('olivia.miller', 'olivia.miller@cmu.edu', @password_hash, 'Miller', 'Olivia', 'auteur', 'UI/UX Design Student', 'Carnegie Mellon student specializing in user experience design. Creating beautiful and functional interfaces.', 'Pittsburgh, PA, USA', '2003-07-30', NOW()),
('william.taylor', 'william.taylor@caltech.edu', @password_hash, 'Taylor', 'William', 'auteur', 'Computer Science Student', 'Caltech student interested in quantum computing and advanced algorithms. Research in computational theory.', 'Pasadena, CA, USA', '2003-05-14', NOW()),
('sophia.martinez', 'sophia.martinez@columbia.edu', @password_hash, 'Martinez', 'Sophia', 'auteur', 'Software Engineering Student', 'Columbia University student. Passionate about mobile app development and iOS programming.', 'New York, NY, USA', '2004-02-09', NOW()),
('daniel.anderson', 'daniel.anderson@utexas.edu', @password_hash, 'Anderson', 'Daniel', 'auteur', 'Computer Science Student', 'UT Austin student. Love open source contributions and Linux system administration.', 'Austin, TX, USA', '2003-10-17', NOW()),
('isabella.thomas', 'isabella.thomas@umich.edu', @password_hash, 'Thomas', 'Isabella', 'auteur', 'Data Engineering Student', 'University of Michigan student. Specializing in big data pipelines and distributed systems.', 'Ann Arbor, MI, USA', '2003-12-23', NOW());

-- Professionnels américains
INSERT INTO `utilisateurs` (`pseudo`, `email`, `mot_de_passe`, `nom`, `prenom`, `role`, `titre`, `biographie`, `localisation`, `site_web`, `telephone`, `date_naissance`, `date_inscription`) VALUES
('robert.taylor', 'robert.taylor@techcorp.com', @password_hash, 'Taylor', 'Robert', 'auteur', 'Senior Software Engineer at Google', '10+ years of experience in software development. Expert in distributed systems and cloud computing. Based in Silicon Valley.', 'Mountain View, CA, USA', 'https://roberttaylor.dev', '+1 (650) 555-0123', '1988-04-15', NOW()),
('jennifer.anderson', 'jennifer.anderson@startup.io', @password_hash, 'Anderson', 'Jennifer', 'auteur', 'Product Manager at Microsoft', 'Product manager with expertise in AI and cloud services. Passionate about building products that make a difference.', 'Seattle, WA, USA', 'https://jenniferanderson.io', '+1 (206) 555-0456', '1991-08-28', NOW()),
('david.thomas', 'david.thomas@amazon.com', @password_hash, 'Thomas', 'David', 'auteur', 'DevOps Engineer at Amazon', 'DevOps specialist focused on AWS, Kubernetes, and CI/CD pipelines. Helping teams deploy faster and more reliably.', 'Seattle, WA, USA', NULL, '+1 (206) 555-0789', '1989-12-03', NOW()),
('lisa.martinez', 'lisa.martinez@apple.com', @password_hash, 'Martinez', 'Lisa', 'auteur', 'iOS Developer at Apple', 'iOS developer creating amazing mobile experiences. Swift and SwiftUI enthusiast. Love clean code and beautiful design.', 'Cupertino, CA, USA', 'https://lisamartinez.dev', '+1 (408) 555-0321', '1992-05-17', NOW()),
('chris.lee', 'chris.lee@meta.com', @password_hash, 'Lee', 'Chris', 'auteur', 'Full-Stack Developer at Meta', 'Full-stack developer working on social media platforms. React, GraphQL, and React Native expert.', 'Menlo Park, CA, USA', NULL, '+1 (650) 555-0654', '1990-10-09', NOW()),
('amanda.white', 'amanda.white@netflix.com', @password_hash, 'White', 'Amanda', 'auteur', 'Data Engineer at Netflix', 'Data engineer building scalable data pipelines. Working with big data technologies and streaming platforms.', 'Los Gatos, CA, USA', 'https://amandawhite.tech', '+1 (408) 555-0987', '1993-02-26', NOW()),
('kevin.harris', 'kevin.harris@tesla.com', @password_hash, 'Harris', 'Kevin', 'auteur', 'Software Engineer at Tesla', 'Working on autonomous driving systems and vehicle software. Passionate about AI and robotics.', 'Palo Alto, CA, USA', NULL, '+1 (650) 555-0112', '1991-07-19', NOW()),
('rachel.clark', 'rachel.clark@airbnb.com', @password_hash, 'Clark', 'Rachel', 'auteur', 'Senior Frontend Developer at Airbnb', 'Frontend developer specializing in React and TypeScript. Building beautiful user experiences for travelers worldwide.', 'San Francisco, CA, USA', 'https://rachelclark.dev', '+1 (415) 555-0234', '1990-03-11', NOW()),
('matthew.lewis', 'matthew.lewis@uber.com', @password_hash, 'Lewis', 'Matthew', 'auteur', 'Backend Engineer at Uber', 'Backend engineer working on ride-sharing infrastructure. Expert in microservices and distributed systems.', 'San Francisco, CA, USA', NULL, '+1 (415) 555-0345', '1989-09-25', NOW()),
('laura.walker', 'laura.walker@twitter.com', @password_hash, 'Walker', 'Laura', 'auteur', 'Security Engineer at Twitter', 'Cybersecurity specialist focused on platform security and threat detection. Protecting millions of users daily.', 'San Francisco, CA, USA', 'https://laurawalker.security', '+1 (415) 555-0456', '1992-11-07', NOW());

-- Entrepreneurs américains
INSERT INTO `utilisateurs` (`pseudo`, `email`, `mot_de_passe`, `nom`, `prenom`, `role`, `titre`, `biographie`, `localisation`, `site_web`, `telephone`, `date_naissance`, `date_inscription`) VALUES
('ryan.rodriguez', 'ryan.rodriguez@startup.com', @password_hash, 'Rodriguez', 'Ryan', 'auteur', 'Founder & CEO of TechStart', 'Serial entrepreneur building the next generation of SaaS products. Y Combinator alumni. Always looking for talented developers.', 'San Francisco, CA, USA', 'https://techstart.io', '+1 (415) 555-0111', '1987-09-14', NOW()),
('jessica.garcia', 'jessica.garcia@innovate.tech', @password_hash, 'Garcia', 'Jessica', 'auteur', 'CTO & Co-Founder', 'Tech lead and co-founder of an AI startup. Former Google engineer. Passionate about machine learning and innovation.', 'Austin, TX, USA', 'https://innovate.tech', '+1 (512) 555-0222', '1989-06-21', NOW()),
('brian.young', 'brian.young@venture.capital', @password_hash, 'Young', 'Brian', 'auteur', 'Founder & CEO of CloudScale', 'Building cloud infrastructure solutions for enterprises. Former AWS engineer. Passionate about scalability and performance.', 'Seattle, WA, USA', 'https://cloudscale.io', '+1 (206) 555-0333', '1986-04-03', NOW()),
('nicole.king', 'nicole.king@fintech.startup', @password_hash, 'King', 'Nicole', 'auteur', 'Co-Founder & CPO', 'Co-founder of a fintech startup. Former product manager at Stripe. Revolutionizing digital payments.', 'New York, NY, USA', 'https://fintech.startup', '+1 (212) 555-0444', '1990-08-15', NOW());

-- ===================== RÉSUMÉ =====================
-- Total : 24 utilisateurs américains
-- - 10 étudiants (MIT, Stanford, UC Berkeley, Harvard, NYU, CMU, Caltech, Columbia, UT Austin, UMich)
-- - 10 professionnels (Google, Microsoft, Amazon, Apple, Meta, Netflix, Tesla, Airbnb, Uber, Twitter)
-- - 4 entrepreneurs (San Francisco, Austin, Seattle, New York)
--
-- Mot de passe pour tous : "password123"
-- Tous les utilisateurs sont actifs par défaut (actif = 1)
--
-- Localisations : Californie (Silicon Valley, San Francisco, Palo Alto, etc.), 
--                 New York, Seattle, Austin, Boston, Pittsburgh, etc.
