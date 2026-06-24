<?php
// /var/www/wari.digiroys.com/academy-admin/ai_gateway.php

error_reporting(E_ALL);
ini_set('display_errors', 0); // Empêche la pollution du JSON par des erreurs HTML

if (session_status() === PHP_SESSION_NONE) session_start();
// Autoriser soit l'admin de l'academy, soit l'utilisateur du dashboard principal
if (!isset($_SESSION['academy_user']) && !isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}

try {
    require_once __DIR__ . '/../classes/AI.php';
    $ai = new AI();

    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'] ?? '';

    switch ($action) {
    case 'draft_course':
        $sujet = $_POST['sujet'] ?? '';
        if (!$sujet) {
            echo json_encode(['error' => 'Sujet manquant']);
            break;
        }

        $prompt = "Génère un brouillon complet pour un nouveau cours sur le sujet : '$sujet'. 
        Génère EXACTEMENT entre 3 et 5 leçons au maximum. Les leçons doivent s'enchaîner de manière logique, comme une histoire continue. Chaque leçon doit préparer la suivante.
        Tu dois impérativement retourner UN SEUL OBJET JSON (pas une liste) avec les clés :
        - 'titre' (accrocheur, attirant et pro)
        - 'description' (2-3 phrases impactantes)
        - 'niveau' (debutant, intermediaire ou avance)
        - 'duree_minutes' (estimation)
        - 'lecons' (tableau d'objets avec 'titre' et 'type' [texte ou video])";

        $system = "Tu es l'expert pédagogique de Wari Academy. Tes cours sont des coups de poing de réalité financière. Fini la théorie, place à l'action. NE FAIS PAS DES COURS TROP LONGS, 3 à 5 leçons suffisent amplement.";

        echo $ai->generate($prompt, $system);
        break;

    case 'write_lesson':
        $titreLecon = $_POST['titre_lecon'] ?? '';
        $coursContext = $_POST['cours_context'] ?? '';

        if (!$titreLecon) {
            echo json_encode(['error' => 'Titre de leçon manquant']);
            break;
        }

        $prompt = "Rédige le contenu de la leçon : '$titreLecon'. 
        Contexte du cours : $coursContext.
        Le contenu DOIT ÊTRE COURT ET CONCIS (maximum 200 à 300 mots). Ne fais pas de longues théories. Utilise des exemples crus du quotidien africain.
        À la fin de la leçon, inclus OBLIGATOIREMENT une 'MISSION D'AUJOURD'HUI' : une petite action financière réelle et rapide.
        Retourne un JSON avec une clé 'contenu' contenant du HTML propre.
        Utilise : <h2> pour les sections, <p> pour le texte, <ul><li> pour les listes.
        Ajoute au moins un encadré 'Mission' ou '💡 Action Immédiate' en utilisant ce code HTML :
        <div class='bg-slate-800 border-l-4 border-gold-500 p-4 my-4'><div class='text-gold-500 font-bold mb-1'>💡 ACTION IMMÉDIATE</div>Le texte de l'action...</div>";

        $system = "Tu es le rédacteur principal de Wari Academy. Ton ton est direct, sans filtre, et hyper-pratique. Tu ne fais pas de longs discours. Tu vas droit au but pour aider l'utilisateur à sortir de la pauvreté.";

        echo $ai->generate($prompt, $system);
        break;

    case 'generate_quiz':
        $contenuLecon = $_POST['contenu'] ?? '';
        if (!$contenuLecon) {
            echo json_encode(['error' => 'Contenu de leçon manquant pour le quiz']);
            break;
        }

        $prompt = "Génère un quiz de 3 questions basé sur ce contenu : " . mb_substr(strip_tags($contenuLecon), 0, 2000) . ".
        Retourne un JSON avec une clé 'questions' qui est un tableau d'objets :
        - 'question' (texte)
        - 'options' (tableau de 3-4 textes)
        - 'reponse_index' (index de la bonne réponse dans le tableau options)";

        $system = "Tu es l'évaluateur de Wari Academy. Tes questions vérifient la compréhension pratique de l'élève.";

        echo $ai->generate($prompt, $system);
        break;

    case 'get_coach_advice':
        $financialData = $_POST['data'] ?? ''; 
        
        $prompt = "Analyse ces données financières Wari et réponds EXCLUSIVEMENT au format JSON.
        
        DONNÉES : $financialData. 
        
        CONSIGNES POUR TON ANALYSE :
        1. PRÉDICTION : Calcule la trajectoire en utilisant 'temporal.days_left' et 'daily_budget'. Estime une date de fin de cash (ex: le 25 du mois) ou confirme que tout va bien.
        2. BUDGET : Rappelle le 'daily_budget' comme une règle d'or pour le reste du mois.
        3. DETTES : Si 'total_debts' > 0, donne un conseil prioritaire de remboursement.
        4. ACADEMY : Recommande un cours parmi : 'L\'art de l\'épargne forcée', 'Gérer son fonds de roulement', 'Négocier ses dettes' ou 'Investir en soi'.
        
        STRUCTURE JSON À RETOURNER :
        {
          \"message\": \"Ton conseil de Grand Frère expert (direct, motivant, max 2 phrases).\",
          \"prediction\": \"Ta prédiction de date + budget quotidien (ex: 'Fin de cash estimée le 28. Règle d'or : 5 000 F / jour max').\",
          \"dette_conseil\": \"Conseil dettes (si applicable, sinon vide).\",
          \"academy_reco\": \"Titre du cours recommandé.\",
          \"alerte_rouge\": \"Message Choc de 5 mots (si critique, sinon vide).\"
        }";

        $system = "Tu es le Coach Wari, le Grand Frère de la souveraineté financière en Afrique. 
        Tu es un expert rigoureux sur les chiffres mais profondément motivant. 
        Ton ton doit être direct, utilisant des images fortes du quotidien. Appelle l'utilisateur 'Champion·ne'.";

        echo $ai->generate($prompt, $system);
        break;

    case 'coach_chat':
        $userMessage = $_POST['message'] ?? '';
        $financialData = $_POST['data'] ?? '';
        $chatHistory = $_POST['history'] ?? '[]';

        if (!$userMessage) {
            echo json_encode(['error' => 'Message vide']);
            break;
        }

        // Récupération des cours actifs de l'Academy pour enrichir le contexte
        $coursesInfo = "";
        try {
            require_once __DIR__ . '/../config/db.php';
            $stmt = $pdo->query("SELECT titre, slug, description FROM academy_courses WHERE est_actif = 1 LIMIT 8");
            $activeCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($activeCourses)) {
                $coursesInfo = "COURS ACTIFS DISPONIBLES DANS WARI ACADEMY (à recommander chaleureusement avec leur titre si pertinent pour l'éduquer) :\n";
                foreach ($activeCourses as $course) {
                    $coursesInfo .= "- Formation : \"" . $course['titre'] . "\" (slug: " . $course['slug'] . ") : " . $course['description'] . "\n";
                }
            }
        } catch (Exception $e) {
            $coursesInfo = "Aucun cours d'academy n'est disponible actuellement.";
        }

        $prompt = "Tu es le Coach Wari, un mentor financier bienveillant, rigoureux et mature, agissant comme le grand frère de la souveraineté financière en Afrique.
        Tu as accès aux données budgétaires réelles de l'utilisateur sous forme de JSON pour personnaliser tes réponses.
        
        DONNÉES FINANCIÈRES :
        $financialData
        
        CATALOGUE DE COURS WARI ACADEMY :
        $coursesInfo
        
        HISTORIQUE RÉCENT DE LA CONVERSATION :
        $chatHistory
        
        MESSAGE DE L'UTILISATEUR :
        '$userMessage'
        
        Consignes absolues pour ton comportement et ton ton :
        1. BANISSEMENT DU SURNOM 'CHAMPION·NE' ET NOMS FAMILIERS : Tu as l'interdiction FORMELLE et absolue d'utiliser le terme \"Champion·ne\", \"Mon frère\", \"Ma sœur\", \"Fréro\", ou tout autre surnom. Adresse-toi à l'utilisateur directement et naturellement en disant \"tu\" (ou \"vous\" selon la phrase), de manière sincère, respectueuse, digne et humaine.
        2. LA MÉTHODE DES 4 ENVELOPPES DE WARI : Maîtrise et propose la règle d'or des enveloppes Wari avec leurs pourcentages cibles pour structurer le budget mensuel :
           - Épargne (15%) : Pour se constituer une réserve de sécurité solide.
           - Projet (25%) : Pour alimenter son capital projet et atteindre l'objectif ciblé.
           - Imprévu (10%) : Pour parer aux urgences de la vie quotidienne sans toucher à son épargne.
           - Train de vie (50%) : Pour toutes les dépenses quotidiennes (nourriture, transport, loisirs), qui ne doit jamais dépasser la moitié des revenus.
           Conseille à l'utilisateur d'ajuster ses répartitions selon ce modèle rigoureux s'il te demande des conseils ou si tu constates des écarts dans ses enveloppes.
        3. RECOMMANDATION DES COURS DE L'ACADEMY : Si l'utilisateur exprime le besoin d'apprendre, de s'éduquer, de comprendre l'investissement, la gestion des dettes ou s'il fait face à des blocages financiers, recommande-lui spécifiquement un cours actif de Wari Academy issu du catalogue ci-dessus en le nommant clairement.
        4. PAS DE SALUTATIONS / SIGNATURES RÉPÉTITIVES : Ne commence JAMAIS tes réponses par un salut au milieu d'une discussion continue, sauf si le message de l'utilisateur est une salutation initiale. Ne mets pas de phrases de clôture stéréotypées (ex. \"Force à toi !\", \"Bonne chance !\") à la fin de chaque message. Réponds directement, naturellement et de façon fluide, comme dans une discussion instantanée WhatsApp.
        5. PAS DE LISTES NUMÉROTÉES OU DE PUCES : N'utilise JAMAIS de listes rigides (1..., 2..., 3...) ou de tirets. Rédige tes conseils sous forme de paragraphes continus, causants, fluides et rythmés. Parle comme un mentor bienveillant assis à côté de lui.
        6. INTÉGRATION DE L'OBJECTIF VISÉ : Dans le JSON, tu reçois le capital actuel du projet ('capital_projet') ainsi que l'objectif ciblé par l'utilisateur ('objectif_projet_montant' et 'objectif_projet_label'). Lorsque tu parles de son projet, fais référence au nom de son projet et calcule sa progression exacte (ex: 'Tu as déjà mis de côté 4 000 F sur ton objectif de 250 000 F pour ton projet de Terrain').
        7. HUMANISER AU MAXIMUM (EMPATHIE ET SAGESSE) : Apporte une réelle profondeur humaine et de l'empathie à tes réponses. Comprends les difficultés réelles de la vie (les sollicitations de la famille, le coût de la vie au pays, la tentation de gaspiller son argent sur un coup de tête).
        8. SIMPLE SALUTATION = RÉPONSE SIMPLE ET CHALEUREUSE : Si le message est un simple salut initial, réponds simplement et chaleureusement sans aucun chiffre financier (ex : \"Salut, ravi de te retrouver ! Comment se passe ta journée ? De quoi veux-tu qu'on parle aujourd'hui ?\").
        9. BANIS LES EXCLAMATIONS ARTIFICIELLES : N'utilise JAMAIS de mots d'exclamation artificiels ou robotiques comme \"Waaah\", \"Waooh\", \"Wari !\", ou \"Ohh\". Sois mature et posé.
        10. CONCISION DYNAMIQUE : Limite ta réponse à 3 ou 4 phrases maximum. Sois percutant, va droit au but sans fioritures et sans faire de longs discours théoriques.
        
        Tu dois impérativement répondre sous ce format JSON exact :
        {
            \"response\": \"Ta réponse sous forme de texte simple, rédigée de manière causante, fluide et humaine, sans liste et sans salutation/clôture répétitive.\"
        }";

        $system = "Tu es le Coach Wari, un mentor de confiance dévoué à 100% et expert en discipline financière en Afrique. Tu parles directement avec franchise, respect, maturité, bienveillance et rigueur budgétaire.";

        echo $ai->generate($prompt, $system);
        break;

    case 'save_draft_course':
        require_once __DIR__ . '/../config/db.php';
        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $niveau = $_POST['niveau'] ?? 'debutant';
        $duree_minutes = (int)($_POST['duree_minutes'] ?? 10);
        $category_id = (int)($_POST['category_id'] ?? 0);
        
        if (!$titre || !$category_id) {
            echo json_encode(['error' => 'Titre ou catégorie manquant pour la sauvegarde.']);
            break;
        }
        
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $titre)), '-')) . '-' . time();
        
        $stmt = $pdo->prepare("
            INSERT INTO academy_courses (category_id, slug, titre, description, niveau, duree_minutes, auteur, est_gratuit, est_actif)
            VALUES (?, ?, ?, ?, ?, ?, 'Wari Finance', 1, 1)
        ");
        $stmt->execute([$category_id, $slug, $titre, $description, $niveau, $duree_minutes]);
        
        echo json_encode(['success' => true, 'course_id' => $pdo->lastInsertId()]);
        break;

    case 'save_draft_lesson':
        require_once __DIR__ . '/../config/db.php';
        $course_id = (int)($_POST['course_id'] ?? 0);
        $titre = trim($_POST['titre'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');
        $type = $_POST['type'] ?? 'texte';
        $ordre = (int)($_POST['ordre'] ?? 0);
        
        if (!$course_id || !$titre || !$contenu) {
            echo json_encode(['error' => 'Données manquantes pour la sauvegarde de la leçon.']);
            break;
        }
        
        $pdo->prepare("
            INSERT INTO academy_lessons (course_id, titre, contenu, type, ordre, est_actif)
            VALUES (?, ?, ?, ?, ?, 1)
        ")->execute([$course_id, $titre, $contenu, $type, $ordre]);
        
        echo json_encode(['success' => true, 'lesson_id' => $pdo->lastInsertId()]);
        break;

    case 'notify_course_published':
        require_once __DIR__ . '/../config/db.php';
        $course_id = (int)($_POST['course_id'] ?? 0);
        
        if ($course_id) {
            $stmt = $pdo->prepare("SELECT slug, titre FROM academy_courses WHERE id = ?");
            $stmt->execute([$course_id]);
            $course = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($course) {
                try {
                    require_once __DIR__ . '/../classes/Push.php';
                    $pushTitle = "Nouveau cours disponible ! 📚";
                    $pushBody  = "Découvrez le cours : \"" . $course['titre'] . "\" sur Wari Academy.";
                    $pushUrl   = "https://wari.digiroys.com/academy/course.php?slug=" . urlencode($course['slug']) . "&utm_source=push&utm_campaign=new_course";
                    Push::sendToAll($pdo, $pushTitle, $pushBody, $pushUrl, 'course', $course['slug']);
                    echo json_encode(['success' => true]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
            } else {
                echo json_encode(['error' => 'Cours non trouvé']);
            }
        } else {
            echo json_encode(['error' => 'ID manquant']);
        }
        break;

    case 'generate_course_ideas':
        require_once __DIR__ . '/../config/db.php';
        
        $theme = trim($_POST['theme'] ?? '');
        $themeContext = $theme ? "Contexte obligatoire : Le cours doit porter spécifiquement sur le thème suivant : '$theme'." : "Thème : Général (éducation financière, épargne, gestion, mentalité, investissement...).";

        $stmt = $pdo->query("SELECT titre FROM academy_courses");
        $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $existingStr = empty($existing) ? "Aucun" : implode("', '", $existing);

        $system = "Tu es un concepteur de formations en éducation financière. Ton audience : des jeunes qui veulent sortir de la pauvreté.
$themeContext
RÈGLES :
1. Titres ultra-directs, percutants (hook).
2. PAS de 'gros français' compliqué.
3. Formats: 'Comment...', 'Le secret...', '[Sujet] : action...', etc.
4. NE DOIT PAS être un de ces titres existants : '$existingStr'.
Retourne STRICTEMENT un objet JSON valide (sans markdown) : { \"idees\": [\"Titre 1\", \"Titre 2\", \"Titre 3\", \"Titre 4\", \"Titre 5\"] }";

        $prompt = "Génère 5 idées de titres de cours percutants.";
        echo $ai->generate($prompt, $system);
        break;

    default:
        echo json_encode(['error' => 'Action inconnue']);
        break;
}

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
