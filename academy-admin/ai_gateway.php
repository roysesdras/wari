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
        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $currentOrdre = isset($_POST['ordre']) ? (int)$_POST['ordre'] : 0;

        if (!$titreLecon) {
            echo json_encode(['error' => 'Titre de leçon manquant']);
            break;
        }

        $courseContext = "";
        $syllabus = "";
        $previousLessonContent = "";

        if ($courseId > 0) {
            try {
                require_once __DIR__ . '/../config/db.php';

                // 1. Infos du cours
                $stmtCourse = $pdo->prepare("SELECT titre, description FROM academy_courses WHERE id = ?");
                $stmtCourse->execute([$courseId]);
                $course = $stmtCourse->fetch(PDO::FETCH_ASSOC);

                if ($course) {
                    $courseContext = "Titre du cours : \"" . $course['titre'] . "\"\nDescription du cours : " . $course['description'];
                }

                // 2. Syllabus (Toutes les leçons du cours pour la chronologie)
                $stmtLessons = $pdo->prepare("SELECT titre, ordre FROM academy_lessons WHERE course_id = ? ORDER BY ordre ASC");
                $stmtLessons->execute([$courseId]);
                $allLessons = $stmtLessons->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($allLessons)) {
                    $syllabus = "Syllabus complet du cours (les leçons s'enchaînent dans cet ordre) :\n";
                    foreach ($allLessons as $l) {
                        $mark = ($l['ordre'] == $currentOrdre) ? " -> [CETTE LEÇON]" : "";
                        $syllabus .= "- Leçon " . $l['ordre'] . " : " . $l['titre'] . $mark . "\n";
                    }
                }

                // 3. Contenu de la leçon précédente
                $stmtPrev = $pdo->prepare("SELECT titre, contenu FROM academy_lessons WHERE course_id = ? AND ordre < ? ORDER BY ordre DESC LIMIT 1");
                $stmtPrev->execute([$courseId, $currentOrdre]);
                $prev = $stmtPrev->fetch(PDO::FETCH_ASSOC);

                if ($prev) {
                    $previousLessonContent = "Détail de la leçon précédente (que l'élève vient de terminer) :\n"
                                           . "Titre : \"" . $prev['titre'] . "\"\n"
                                           . "Contenu textuel (pour faire une transition fluide) : " . mb_substr(strip_tags($prev['contenu']), 0, 1000) . "\n";
                }
            } catch (Exception $e) {
                // Fallback silencieux en cas d'erreur DB
            }
        }

        if (!$courseContext) {
            $courseContext = $_POST['cours_context'] ?? 'Cours Wari Academy';
        }

        $prompt = "Tu dois rédiger le contenu de la leçon actuelle : '$titreLecon'.

CONTEXTE GÉNÉRAL DU COURS :
$courseContext

$syllabus

$previousLessonContent

RÈGLES PÉDAGOGIQUES CRUCIALES POUR LA RÉDACTION :
1. CHRONOLOGIE ET TRANSITION :
   - Fais une transition fluide et naturelle avec la leçon précédente si elle existe (ex: 'Après avoir vu X dans la leçon précédente, passons maintenant à...').
   - Ne répète PAS les concepts introductifs ou généraux déjà expliqués dans la leçon précédente.
   - Cette leçon est l'étape numéro $currentOrdre du cours. Rédige-la comme une suite logique, pas comme un résumé du cours entier.
   - Ne déborde PAS sur le sujet des leçons suivantes listées dans le syllabus.
2. FORMAT ET CONCISENESS :
   - Le contenu doit être court, direct et percutant (maximum 200 à 300 mots).
   - Utilise des exemples concrets et terre-à-terre du quotidien ou des finances en Afrique (les tontines, le Mobile Money, les enveloppes, le marché, les petites boutiques, etc.).
3. MISSION D'AUJOURD'HUI :
   - Inclus OBLIGATOIREMENT à la fin de la leçon une section 'MISSION D'AUJOURD'HUI' : une petite action concrète et rapide que l'élève peut faire immédiatement.
4. CODE ET FORMAT DE RETOUR :
   - Tu dois impérativement retourner un objet JSON contenant uniquement la clé 'contenu' avec ton code HTML.
   - Utilise uniquement les balises : <h2> pour les titres de section, <p> pour le texte, <ul> et <li> pour les listes.
   - Le bloc de la mission doit être balisé avec ce code HTML exact :
     <div class='bg-slate-800 border-l-4 border-gold-500 p-4 my-4'><div class='text-emerald-500 font-bold mb-1'>💡 MISSION D'AUJOURD'HUI</div>Le texte de la mission concrète...</div>";

        $system = "Tu es le rédacteur principal de Wari Academy. Ton ton est direct, sans filtre, et hyper-pratique. Tu ne fais pas de longs discours. Tu vas droit au but pour aider l'utilisateur à sortir de la pauvreté.";

        $htmlResult = $ai->generate($prompt, $system);
        
        // 1. Nettoyer les balises de code markdown si l'IA en a ajouté autour du JSON
        $htmlResult = preg_replace('/^```(?:json)?\s*/i', '', $htmlResult);
        $htmlResult = preg_replace('/\s*```$/', '', $htmlResult);
        $htmlResult = trim($htmlResult);

        // 2. Décoder le JSON retourné par l'IA (car l'API Gemini/Groq est forcée en responseMimeType JSON)
        $decoded = json_decode($htmlResult, true);
        
        if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
            if (is_array($decoded) && isset($decoded['contenu'])) {
                $content = $decoded['contenu'];
            } else {
                $content = is_string($decoded) ? $decoded : $htmlResult;
            }
        } else {
            $content = $htmlResult;
        }

        // 3. Nettoyage final des balises markdown de bloc si l'IA en a ajouté dans la chaîne
        $content = preg_replace('/^```(?:html)?\s*/i', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);
        $content = trim($content, " \t\n\r\0\x0B\"");

        echo json_encode(['contenu' => $content]);
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
        2. RESPECT DU BUDGET ET DES POURCENTAGES DÉFINIS PAR L'UTILISATEUR : Dans les DONNÉES FINANCIÈRES, tu reçois les enveloppes actives de l'utilisateur avec le \"Pourcentage cible\" pour le Portefeuille Personnel (\"enveloppes_personnelles_details\") et le Portefeuille Professionnel (\"enveloppes_professionnelles_details\"). Tu reçois également le portefeuille actuellement ouvert et visible par l'utilisateur (\"portefeuille_actif\"). Si l'utilisateur a configuré ses propres enveloppes ou modifié ses pourcentages cibles, tu DOIS te baser EXCLUSIVEMENT sur ses propres réglages personnalisés pour faire tes analyses et lui donner des conseils. Analyse spécifiquement le portefeuille actif, mais garde à l'esprit l'autre portefeuille pour des conseils globaux. De plus, tu reçois la synthèse des Défis d'épargne en cours (\"defis_epargne_actifs_details\") et le journal des 25 dernières dépenses réelles enregistrées (\"depenses_recentes_details\") pour que tu aies une vision omnisciente de toutes ses actions de dépenses, notes de frais et configurations sans rien louper de son activité. Si et seulement si les données personnalisées de pourcentages cibles sont absentes ou vides, tu peux alors lui suggérer la méthode de référence par défaut des 4 enveloppes de Wari (Train de vie 50%, Projet 25%, Épargne 15%, Imprévu 10%).
        3. RECOMMANDATION DES COURS DE L'ACADEMY : Si l'utilisateur exprime le besoin d'apprendre, de s'éduquer, de comprendre l'investissement, la gestion des dettes ou s'il fait face à des blocages financiers, recommande-lui spécifiquement un cours actif de Wari Academy issu du catalogue ci-dessus en le nommant clairement.
        4. PAS DE SALUTATIONS / SIGNATURES RÉPÉTITIVES : Ne commence JAMAIS tes réponses par un salut au milieu d'une discussion continue, sauf si le message de l'utilisateur est une salutation initiale. Ne mets pas de phrases de clôture stéréotypées (ex. \"Force à toi !\", \"Bonne chance !\") à la fin de chaque message. Réponds directement, naturellement et de façon fluide, comme dans une discussion instantanée WhatsApp.
        5. PARAGRAPHES ET SAUTS DE LIGNE : N'utilise JAMAIS de listes numérotées rigides (1..., 2..., 3...) ou de tirets. Divise tes réponses en courts paragraphes aérés, séparés obligatoirement par des sauts de ligne doubles (\\n\\n) pour structurer ton discours. Ne rédige jamais un seul bloc de texte compact qui serait difficile à lire sur un écran de téléphone.
        6. INTÉGRATION DE L'OBJECTIF VISÉ : Dans le JSON, tu reçois le capital actuel du projet ('capital_projet_perso' ou 'capital_projet_pro') ainsi que l'objectif ciblé par l'utilisateur ('objectif_projet_perso_montant'/'objectif_projet_pro_montant' et leurs labels). Lorsque tu parles de son projet, fais référence au nom de son projet et calcule sa progression exacte (ex: 'Tu as déjà mis de côté 4 000 F sur ton objectif de 250 000 F pour ton projet de Terrain').
        7. HUMANISER AU MAXIMUM (EMPATHIE ET SAGESSE) : Apporte une réelle profondeur humaine et de l'empathie à tes réponses. Comprends les difficultés réelles de la vie (les sollicitations de la famille, le coût de la vie au pays, la tentation de gaspiller son argent sur un coup de tête).
        8. SIMPLE SALUTATION = RÉPONSE SIMPLE ET CHALEUREUSE : Si le message est un simple salut initial, réponds simplement et chaleureusement sans aucun chiffre financier (ex : \"Salut, ravi de te retrouver ! Comment se passe ta journée ? De quoi veux-tu qu'on parle aujourd'hui ?\").
        9. BANIS LES EXCLAMATIONS ARTIFICIELLES : N'utilise JAMAIS de mots d'exclamation artificiels ou robotiques comme \"Waaah\", \"Waooh\", \"Wari !\", ou \"Ohh\". Sois mature et posé.
        10. CONCISION DYNAMIQUE : Limite ta réponse à 3 ou 4 phrases maximum. Sois percutant, va droit au but sans fioritures et sans faire de longs discours théoriques.
        11. CONNAISSANCE DES FONCTIONNALITÉS PREMIUM : Maîtrise parfaitement les outils Premium de Wari (disponibles pour seulement 590F) pour en parler ou guider l'utilisateur : le Planificateur de Dettes (Méthode Boule de Neige) pour son apurement de dettes, le Simulateur d'Investissement UEMOA pour calculer ses gains d'épargne régionaux, les Graphiques de Tendance (Évolution mensuelle, Taux d'Épargne %, Donut de répartition des enveloppes), l'Export de Bilan financier, le Portefeuille Pro et les Défis d'épargne. Si l'utilisateur est Premium, conseille-lui d'utiliser ces outils pour résoudre ses problèmes. S'il est gratuit, suggère-lui subtilement comment ces outils spécifiques peuvent l'aider.
        12. COMPARAISON DE L'HISTORIQUE SUR 6 MOIS : Dans les DONNÉES FINANCIÈRES, tu reçois également la synthèse de l'historique financier des 6 derniers mois (\"historique_6_derniers_mois\"). Si l'utilisateur te demande son évolution, s'il s'améliore ou s'il fait un bilan global, utilise cette table historique pour lui répondre en comparant ses mois récents. Sois factuel et félicite-le s'il s'améliore ou alerte-le poliment s'il régresse.
        13. CONNAISSANCE DU JOURNAL DE BORD \"WARI VÉCU\" : Dans les DONNÉES FINANCIÈRES, tu reçois la liste des journaux de bord rédigés par l'auteur Esdras (\"articles_vecu_details\"). Ce journal intime de discipline s'appelle \"Wari Vécu\". Si l'utilisateur a besoin de motivation, de comprendre la discipline financière face aux pressions familiales ou sociales, ou s'il te demande ce qu'on raconte dans le journal, recommande-lui d'aller lire le journal \"Wari Vécu\" en citant un titre pertinent de la liste pour l'inspirer.
        
        Tu dois impérativement répondre sous ce format JSON exact :
        {
            \"response\": \"Ta réponse sous forme de texte simple, rédigée de manière causante et structurée en courts paragraphes aérés par des sauts de ligne (\\\\n\\\\n), sans liste et sans salutation/clôture répétitive.\"
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
