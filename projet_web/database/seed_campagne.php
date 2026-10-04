<?php
/**
 * ECE In - Données de démo pour la campagne "Sternritters' Taxi"
 *
 * Crée des comptes fictifs (agences, ONG, particuliers) qui débattent des robotaxis,
 * avec posts, commentaires, réactions et connexions, pour un fil d'actu crédible.
 *
 * Utilisation : php database/seed_campagne.php
 * Mot de passe de tous les comptes créés : Demo2026!
 * Le script ignore les comptes déjà existants (on peut le relancer sans doublons de comptes).
 */

require_once __DIR__ . '/../config/config.php';
$pdo = getDB();

$comptes = [
    // pseudo => [prenom, nom, titre, biographie, localisation]
    'urbanmove'      => ['UrbanMove', 'Paris', 'Urban Mobility Operator · Paris',
                         'We run shuttles, buses and on-demand rides across Paris. Building the mobility of tomorrow, one route at a time. 🚍', 'Paris, France'],
    'saferoads'      => ['SafeRoads', 'Alliance', 'NGO · Road Safety',
                         'Fewer deaths on our roads. We support any technology that saves lives, if it is proven safe by independent data.', 'Brussels, Belgium'],
    'driversunited'  => ['Drivers', 'United', 'Union of Taxi & VTC Drivers',
                         'We represent thousands of professional drivers. Not against technology, but against being left behind.', 'Paris, France'],
    'datawatch'      => ['DataWatch', 'Europe', 'NGO · Privacy & Digital Rights',
                         'Your data, your rights. We watch how companies and governments use the data they collect about you. 🔍', 'Berlin, Germany'],
    'mobilitylab'    => ['MobilityLab', 'ECE', 'Student Association · ECE Paris',
                         'Talks, workshops and debates about the future of transport. Open to all ECE students. 🚗⚡', 'Paris, France'],
    'camille.laurent'=> ['Camille', 'Laurent', 'Taxi driver · Paris · 15 years on the road',
                         'I know every street of Paris. Coffee, jazz radio, and a lot of stories from the back seat.', 'Paris, France'],
    'lucas.martin'   => ['Lucas', 'Martin', 'Engineering student @ ECE Paris · AI & embedded systems',
                         'Passionate about AI, robotics and autonomous systems. Looking for an internship in 2027.', 'Paris, France'],
    'ines.benali'    => ['Inès', 'Benali', 'Urban planner · Smart cities',
                         'Designing cities for people, not just for cars.', 'Lyon, France'],
    'thomas.nguyen'  => ['Thomas', 'Nguyen', 'Software engineer · Autonomous systems',
                         '8 years building perception and planning software for self-driving vehicles.', 'Munich, Germany'],
    'sarah.kone'     => ['Sarah', 'Koné', 'Mum of 2 · Daily commuter',
                         'Juggling work, school runs and the RER every day. 🙃', 'Saint-Denis, France'],
];

$ids = [];
$hash = password_hash('Demo2026!', PASSWORD_BCRYPT);
$check = $pdo->prepare("SELECT id FROM utilisateurs WHERE pseudo = ?");
$insert = $pdo->prepare("INSERT INTO utilisateurs (pseudo, email, mot_de_passe, prenom, nom, titre, biographie, localisation, date_inscription)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW() - INTERVAL 30 DAY)");
foreach ($comptes as $pseudo => [$prenom, $nom, $titre, $bio, $lieu]) {
    $check->execute([$pseudo]);
    if ($id = $check->fetchColumn()) { $ids[$pseudo] = (int) $id; continue; }
    $insert->execute([$pseudo, "$pseudo@ecein.demo", $hash, $prenom, $nom, $titre, $bio, $lieu]);
    $id = (int) $pdo->lastInsertId();
    $pdo->prepare("UPDATE utilisateurs SET photo = ? WHERE id = ?")->execute([genererAvatarInitiales($id, $prenom, $nom), $id]);
    $ids[$pseudo] = $id;
    echo "Compte créé : $pseudo (#$id)\n";
}
$check->execute(['sternritter']);
$ids['sternritter'] = (int) $check->fetchColumn();

// ===================== POSTS =====================
// [auteur, il y a X heures, contenu, commentaires [auteur, contenu], réactions [auteur => type]]
$posts = [
    ['saferoads', 140,
     "Every year, around 1.19 million people die on the world's roads (WHO). Most crashes involve human error: distraction, speed, alcohol, fatigue.\n\nAutonomous vehicles could change that. But only if they are proven safe with independent data, not just company press releases.\n\nSafety first, hype second. 🛑 #RoadSafety #AutonomousVehicles",
     [['thomas.nguyen', "Agree 100%. Independent safety data is key, and honestly engineers want it too."],
      ['lucas.martin', "The stats on human error are crazy. Machines don't text while driving 😅"]],
     ['thomas.nguyen' => 'soutien', 'lucas.martin' => 'jaime', 'sarah.kone' => 'interessant', 'datawatch' => 'soutien', 'sternritter' => 'interessant']],

    ['driversunited', 118,
     "We are not against technology. We are against being left behind.\n\nThousands of taxi and VTC drivers in Paris feed their families with this job. Before a single robotaxi hits the road, we want a real plan: training, a transition fund, new jobs in fleet supervision.\n\nWho is talking to us? #DriversUnited #JustTransition",
     [['camille.laurent', "Thank you for speaking for us. Nobody asks our opinion."],
      ['ines.benali', "A transition fund paid by the operators would be a good start."]],
     ['camille.laurent' => 'soutien', 'ines.benali' => 'soutien', 'sarah.kone' => 'jaime', 'sternritter' => 'interessant']],

    ['urbanmove', 96,
     "Big news 🚀 Next spring we will test driverless shuttles on two routes in the 13th arrondissement.\n\nA safety operator will stay on board for the first months, and every incident will be reported publicly.\n\nQuestions about the pilot? Our team answers in the comments 👇 #FutureOfMobility #Paris",
     [['camille.laurent', "And what happens to the drivers on these routes?"],
      ['urbanmove', "Fair question Camille. Our drivers will be trained to become remote supervisors. We'll share all the details soon."],
      ['datawatch', "Will passengers be filmed inside the shuttle? Please publish your data policy before the launch."]],
     ['lucas.martin' => 'bravo', 'thomas.nguyen' => 'interessant', 'mobilitylab' => 'bravo', 'ines.benali' => 'interessant']],

    ['datawatch', 74,
     "A robotaxi can carry around 30 cameras, plus LiDAR and radar. They record the street 24/7: pedestrians, faces, licence plates.\n\nWhere does this footage go? How long is it stored? Who can access it?\n\nUnder GDPR you have rights. Ask the operators. 🔍 #Privacy #GDPR",
     [['sarah.kone', "I had no idea. 30 cameras?! 😳"],
      ['thomas.nguyen', "Most companies blur faces and plates, but you're right: it should be mandatory, not optional."]],
     ['sarah.kone' => 'interessant', 'driversunited' => 'soutien', 'ines.benali' => 'jaime', 'sternritter' => 'soutien']],

    ['camille.laurent', 52,
     "15 years driving a taxi in Paris. I know every shortcut, every pothole, every regular customer.\n\nLast week a client asked me: \"So, when are you getting replaced by a robot?\" He was joking. I didn't laugh. 😕",
     [['sarah.kone', "Courage Camille ❤️"],
      ['lucas.martin', "Honestly I think we will need humans for a long time, especially in Paris traffic 😅"],
      ['driversunited', "You are not alone Camille. We are fighting for a fair transition."]],
     ['sarah.kone' => 'soutien', 'driversunited' => 'soutien', 'ines.benali' => 'soutien', 'lucas.martin' => 'jaime', 'mobilitylab' => 'soutien']],

    ['lucas.martin', 36,
     "Just finished my project on sensor fusion for autonomous cars 🤓\n\nMixing LiDAR + camera + radar data is crazy hard: each sensor is wrong in a different way. Camera hates the night, LiDAR hates the rain, radar sees ghosts.\n\nNext step: making the AI explain its decisions. #AI #AutonomousDriving #ECE",
     [['thomas.nguyen', "Welcome to the club 😄 Explainability is the next big challenge, good choice."],
      ['mobilitylab', "Come and present it at our next event!"]],
     ['thomas.nguyen' => 'bravo', 'mobilitylab' => 'bravo', 'urbanmove' => 'jaime']],

    ['ines.benali', 24,
     "Everyone talks about robotaxis like they will solve traffic. They won't, not if each robotaxi drives empty half of the time.\n\nThe real question: do we design them to replace private cars, or to replace public transport? Those are two very different cities. 🏙️",
     [['urbanmove', "Our goal is to connect with the metro and buses, not to compete with them. But you're right to push on this."],
      ['saferoads', "Important point. Fewer cars on the road also means fewer crashes."]],
     ['saferoads' => 'interessant', 'datawatch' => 'jaime', 'thomas.nguyen' => 'interessant', 'sternritter' => 'interessant']],

    ['thomas.nguyen', 14,
     "People often ask me if self-driving cars are safe.\n\nHonest answer: in good weather, on well-mapped streets, they are already very good. The hard part is the \"long tail\": a police officer directing traffic by hand, a mattress on the road, snow hiding the lines.\n\nThat's where most of our work goes.",
     [['lucas.martin', "The long tail is exactly what makes this field so interesting."],
      ['sarah.kone', "Thanks for being honest, that's rare 🙏"]],
     ['lucas.martin' => 'jaime', 'saferoads' => 'soutien', 'sarah.kone' => 'jaime', 'urbanmove' => 'interessant']],

    ['mobilitylab', 8,
     "📅 Thursday 6pm: \"Robotaxis: hype or revolution?\"\n\nA talk + open debate with an engineer, an urban planner and a taxi driver. Free and open to all ECE students.\n\nPizza provided 🍕 See you there! #MobilityLab #ECE",
     [['lucas.martin', "I'll be there!"],
      ['camille.laurent', "I'm the taxi driver 😉 See you Thursday."]],
     ['lucas.martin' => 'jaime', 'camille.laurent' => 'jaime', 'ines.benali' => 'jaime', 'sternritter' => 'bravo']],

    ['sarah.kone', 3,
     "Ok, honest question. Would you put your kids alone in a car with nobody driving? 🤔\n\nI'm not against progress. But I need more than \"trust us, it's safe\".",
     [['thomas.nguyen', "Totally fair. Trust has to be earned with data, not marketing."],
      ['saferoads', "This is exactly why we ask for independent tests."]],
     ['saferoads' => 'soutien', 'datawatch' => 'soutien', 'camille.laurent' => 'jaime', 'ines.benali' => 'interessant']],
];

$insPost = $pdo->prepare("INSERT INTO publications (utilisateur_id, type, contenu, visibilite, date_publication) VALUES (?, 'statut', ?, 'public', NOW() - INTERVAL ? HOUR)");
$insCom  = $pdo->prepare("INSERT INTO commentaires (publication_id, utilisateur_id, contenu, date_commentaire) VALUES (?, ?, ?, NOW() - INTERVAL ? MINUTE)");
$insReac = $pdo->prepare("INSERT INTO reactions (publication_id, utilisateur_id, type, date_reaction) VALUES (?, ?, ?, NOW() - INTERVAL ? MINUTE)");
$dejaPoste = $pdo->prepare("SELECT COUNT(*) FROM publications WHERE utilisateur_id = ? AND contenu = ?");

foreach ($posts as [$auteur, $heures, $contenu, $coms, $reacs]) {
    $dejaPoste->execute([$ids[$auteur], $contenu]);
    if ($dejaPoste->fetchColumn()) continue;
    $insPost->execute([$ids[$auteur], $contenu, $heures]);
    $postId = (int) $pdo->lastInsertId();
    // Les commentaires arrivent dans l'ordre, après le post
    $minutes = $heures * 60 - 20;
    foreach ($coms as [$qui, $texte]) {
        $insCom->execute([$postId, $ids[$qui], $texte, max($minutes, 1)]);
        $minutes -= rand(15, 90);
    }
    foreach ($reacs as $qui => $type) {
        if (!empty($ids[$qui])) $insReac->execute([$postId, $ids[$qui], $type, max($heures * 60 - rand(5, 120), 1)]);
    }
    echo "Post créé : $auteur\n";
}

// ===================== CONNEXIONS (amis) =====================
$liens = [
    ['sternritter', 'urbanmove'], ['sternritter', 'saferoads'], ['sternritter', 'datawatch'], ['sternritter', 'mobilitylab'],
    ['sternritter', 'thomas.nguyen'], ['sternritter', 'lucas.martin'], ['sternritter', 'ines.benali'],
    ['camille.laurent', 'driversunited'], ['camille.laurent', 'sarah.kone'], ['lucas.martin', 'thomas.nguyen'],
    ['lucas.martin', 'mobilitylab'], ['ines.benali', 'urbanmove'], ['saferoads', 'datawatch'], ['thomas.nguyen', 'saferoads'],
];
$existe = $pdo->prepare("SELECT COUNT(*) FROM connexions WHERE (demandeur_id = ? AND destinataire_id = ?) OR (demandeur_id = ? AND destinataire_id = ?)");
$insLien = $pdo->prepare("INSERT INTO connexions (demandeur_id, destinataire_id, statut, date_demande, date_reponse) VALUES (?, ?, 'accepte', NOW() - INTERVAL 20 DAY, NOW() - INTERVAL 19 DAY)");
foreach ($liens as [$a, $b]) {
    $existe->execute([$ids[$a], $ids[$b], $ids[$b], $ids[$a]]);
    if (!$existe->fetchColumn()) $insLien->execute([$ids[$a], $ids[$b]]);
}

// ===================== OFFRES D'EMPLOI (en anglais, thème mobilité) =====================
// [publié par, titre, entreprise, type, lieu, salaire, début (+jours), limite (+jours), description, publié il y a X jours]
$offres = [
    ['urbanmove', 'Robotaxi Safety Engineer (Internship)', 'UrbanMove Paris', 'stage', 'Paris 13e, France', '€1,400/month', 90, 45,
     "Join the team running our driverless shuttle pilot. You will analyse safety reports, review incident logs and help define the safety rules for each route. Python and a strong interest in road safety are a plus. 6-month internship.", 2],
    ['urbanmove', 'Remote Fleet Supervisor', 'UrbanMove Paris', 'cdi', 'Paris, France', '€34,000/year', 60, 40,
     "Supervise a fleet of autonomous shuttles from our control centre and take over when a vehicle needs help. Ideal for former taxi or bus drivers: full training provided, no coding required.", 3],
    ['urbanmove', 'Data Analyst, Mobility Pilot', 'UrbanMove Paris', 'cdd', 'Paris, France', '€38,000/year', 45, 30,
     "12-month contract. Measure the real impact of our pilot: ridership, waiting times, incidents, empty kilometres. SQL, Python and data visualisation required.", 5],
    ['thomas.nguyen', 'Perception Engineer, LiDAR & Camera Fusion', 'AutoNova Labs', 'cdi', 'Munich, Germany', '€62,000/year', 75, 50,
     "Design the algorithms that merge LiDAR, camera and radar data so our vehicles understand the street around them. C++, ROS 2 and computer vision experience expected.", 4],
    ['thomas.nguyen', 'Machine Learning Intern, Edge Cases', 'AutoNova Labs', 'stage', 'Munich, Germany (hybrid)', '€1,600/month', 100, 60,
     "Help us find and label the rare situations that confuse self-driving cars: road works, hand signals, unusual objects. Python, PyTorch, curiosity.", 6],
    ['mobilitylab', 'Embedded Systems Apprentice, Vehicle Software', 'Voltaria Mobility', 'alternance', 'Lyon, France', 'According to the apprenticeship scale', 30, 20,
     "2-year work-study programme. Develop and test embedded software for electric autonomous minibuses. C, real-time systems, CAN bus.", 7],
    ['mobilitylab', 'V2X Communication Engineer (Internship)', 'CityFlow Lab', 'stage', 'Paris La Défense, France', '€1,300/month', 120, 70,
     "Work on vehicle-to-everything communication between cars, traffic lights and the city network. Security of the messages is a key part of the project.", 8],
    ['datawatch', 'Cybersecurity Analyst, Connected Vehicles', 'SecureDrive', 'cdi', 'Rennes, France', '€48,000/year', 60, 35,
     "Protect connected and autonomous fleets against remote attacks. Penetration testing, threat modelling, incident response. Automotive experience is a plus.", 9],
    ['datawatch', 'Privacy & GDPR Officer, Mobility Data', 'DataWatch Europe', 'cdd', 'Berlin, Germany (remote friendly)', '€45,000/year', 40, 25,
     "18-month contract. Audit how mobility companies collect, store and share street footage and passenger data. Legal background in data protection required.", 10],
    ['sternritter', 'Science Communication Intern, Emerging Tech', 'Sternritter Agency', 'stage', 'Paris, France', '€1,200/month', 50, 30,
     "Help us explain complex technologies to the public without hiding the risks. Social media content, infographics, short videos. Engineering students welcome!", 1],
    ['ines.benali', 'Urban Mobility Planner', 'Métropole de Lyon Mobility Office', 'cdi', 'Lyon, France', '€42,000/year', 70, 45,
     "Plan how autonomous shuttles connect with metro and bus lines. You will work with engineers, city officials and residents.", 11],
    ['urbanmove', 'UX Designer, Passenger App', 'UrbanMove Paris', 'freelance', 'Remote', '€450/day', 20, 15,
     "3-month mission. Design the app that tells passengers what the driverless shuttle is doing and why. Accessibility and trust are the priorities.", 12],
    ['saferoads', 'Road Safety Awareness Volunteer', 'SafeRoads Alliance', 'benevole', 'Brussels & Paris', 'Volunteer', 15, 30,
     "Run workshops in schools about road safety and new technologies. 2 to 4 hours per month, training provided.", 4],
    ['datawatch', 'Digital Rights Volunteer, Street Camera Mapping', 'DataWatch Europe', 'benevole', 'Remote', 'Volunteer', 10, 40,
     "Help us map where cameras record public spaces and inform citizens about their rights under GDPR. Flexible hours.", 6],
    ['driversunited', 'Career Transition Mentor (Volunteer)', 'Drivers United', 'benevole', 'Paris, France', 'Volunteer', 20, 35,
     "Mentor professional drivers who want to train for new jobs such as fleet supervision or vehicle maintenance. One meeting per week.", 3],
    ['mobilitylab', 'Battery Systems Intern, Electric Robotaxis', 'Voltaria Mobility', 'stage', 'Grenoble, France', '€1,350/month', 110, 65,
     "Analyse battery performance data from a fleet of electric robotaxis and propose improvements for charging and lifespan.", 9],
];
$dejaOffre = $pdo->prepare("SELECT COUNT(*) FROM emplois WHERE titre = ? AND entreprise = ?");
$insOffre = $pdo->prepare("INSERT INTO emplois (publie_par, titre, entreprise, description, type, lieu, salaire, date_debut, date_limite, lien_candidature, contact_email, date_publication)
                           VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE() + INTERVAL ? DAY, CURDATE() + INTERVAL ? DAY, ?, ?, NOW() - INTERVAL ? DAY)");
foreach ($offres as [$qui, $titre, $entreprise, $type, $lieu, $salaire, $debut, $limite, $desc, $ilYa]) {
    $dejaOffre->execute([$titre, $entreprise]);
    if ($dejaOffre->fetchColumn()) continue;
    $domaine = strtolower(preg_replace('/[^a-z]/i', '', explode(' ', $entreprise)[0])) . '.example';
    $insOffre->execute([$ids[$qui], $titre, $entreprise, $desc, $type, $lieu, $salaire, $debut, $limite,
                        "https://$domaine/careers", "careers@$domaine", $ilYa]);
    echo "Offre créée : $titre\n";
}

// ===================== ÉVÉNEMENTS À VENIR (en anglais) =====================
// [organisateur, titre, lieu, date de début, durée en heures, officiel ECE ?, description]
$jeudi = date('Y-m-d', strtotime('next thursday'));
$evenements = [
    ['mobilitylab', 'Robotaxis: hype or revolution?', 'ECE Paris, Room E-102', "$jeudi 18:00", 2, 1,
     "A talk and open debate with an engineer, an urban planner and a taxi driver. Free and open to all ECE students. Pizza provided!"],
    ['urbanmove', 'Driverless Shuttle Open Day', 'UrbanMove Depot, Paris 13e', date('Y-m-d', strtotime('+13 days')) . ' 10:00', 6, 0,
     "Visit our depot, sit in a driverless shuttle and ask our safety team anything. Family friendly."],
    ['saferoads', 'Road Safety & Autonomous Vehicles Conference', 'Brussels Expo, Belgium', date('Y-m-d', strtotime('+20 days')) . ' 09:00', 8, 0,
     "Researchers, regulators and NGOs discuss how to prove that autonomous vehicles are safe before they scale."],
    ['datawatch', 'Workshop: Your Data in a Smart City', 'Online (Zoom)', date('Y-m-d', strtotime('+9 days')) . ' 19:00', 2, 0,
     "Who records you in the street, and what can you do about it? A practical workshop about GDPR and mobility data."],
    ['mobilitylab', 'Hackathon: Safe Streets 2030', 'ECE Paris, Eiffel Building', date('Y-m-d', strtotime('+27 days')) . ' 09:00', 48, 1,
     "48 hours to build a prototype that makes streets safer for pedestrians, cyclists and passengers. Teams of 3 to 5."],
    ['thomas.nguyen', 'Meetup: Perception for Self-Driving Cars', 'Station F, Paris', date('Y-m-d', strtotime('+16 days')) . ' 18:30', 3, 0,
     "Engineers share how cars see the world: LiDAR, cameras, radar and the hardest edge cases."],
    ['driversunited', 'Town Hall: The Future of Driving Jobs', 'Bourse du Travail, Paris', date('Y-m-d', strtotime('+11 days')) . ' 17:00', 3, 0,
     "Drivers, operators and policymakers meet to discuss training, transition funds and new jobs."],
    ['mobilitylab', 'ECE Career Fair: Mobility & Transport', 'ECE Paris, Main Hall', date('Y-m-d', strtotime('+34 days')) . ' 10:00', 7, 1,
     "Meet companies hiring in autonomous systems, electric vehicles and smart cities. Bring your CV!"],
    ['ines.benali', 'Webinar: Designing Cities Around Shared Mobility', 'Online', date('Y-m-d', strtotime('+23 days')) . ' 12:30', 1, 0,
     "How should streets, parking and public transport change when robotaxis arrive? A one-hour webinar for students and professionals."],
    ['sternritter', "Campaign Launch: Sternritters' Taxi", 'ECE Paris, Amphitheatre', date('Y-m-d', strtotime('+6 days')) . ' 14:00', 1, 0,
     "Our agency presents its campaign about robotaxis at scale: the promise, the risks and what we should ask of policymakers."],
];
$dejaEvt = $pdo->prepare("SELECT COUNT(*) FROM evenements WHERE titre = ?");
$insEvt = $pdo->prepare("INSERT INTO evenements (organisateur_id, titre, description, lieu, date_debut, date_fin, est_officiel)
                         VALUES (?, ?, ?, ?, ?, ?, ?)");
foreach ($evenements as [$qui, $titre, $lieu, $debut, $duree, $officiel, $desc]) {
    $dejaEvt->execute([$titre]);
    if ($dejaEvt->fetchColumn()) continue;
    $fin = date('Y-m-d H:i:s', strtotime($debut) + $duree * 3600);
    $insEvt->execute([$ids[$qui], $titre, $desc, $lieu, $debut, $fin, $officiel]);
    echo "Événement créé : $titre\n";
}

echo "Terminé.\n";
