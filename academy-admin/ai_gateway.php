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
        Tu dois impérativement retourner UN SEUL OBJET JSON (pas une liste) avec les clés :
        - 'titre' (accrocheur et pro)
        - 'description' (2-3 phrases impactantes)
        - 'niveau' (debutant, intermediaire ou avance)
        - 'duree_minutes' (estimation)
        - 'lecons' (tableau d'objets avec 'titre' et 'type' [texte ou video])";

        $system = "Tu es l'expert pédagogique de Wari Academy. 
        Tes cours doivent transformer des notions financières complexes en conseils pratiques pour les entrepreneurs et particuliers africains. 
        Utilise le ton Wari : direct, expert, et ancré dans la réalité locale.";

        echo $ai->generate($prompt, $system);
        break;

    case 'write_lesson':
        $titreLecon = $_POST['titre_lecon'] ?? '';
        $coursContext = $_POST['cours_context'] ?? '';

        if (!$titreLecon) {
            echo json_encode(['error' => 'Titre de leçon manquant']);
            break;
        }

        $prompt = "Rédige le contenu complet de la leçon intitulée : '$titreLecon'. 
        Contexte du cours : $coursContext.
        Retourne un JSON avec une clé 'contenu' contenant du HTML propre.
        Utilise : <h2> pour les sections, <p> pour le texte, <ul><li> pour les listes.
        Ajoute au moins un encadré 'Astuce' ou '💡 Conseil Wari' en utilisant ce code HTML :
        <div class='bg-slate-800 border-l-4 border-gold-500 p-4 my-4'><div class='text-gold-500 font-bold mb-1'>💡 CONSEIL WARI</div>Le texte du conseil...</div>";

        $system = "Tu es le rédacteur principal de Wari Academy. Tes leçons sont captivantes et utilisent des scénarios réels (ex: épargner pour un projet, gérer les dépenses du foyer, optimiser les profits d'une petite boutique).";

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
        
        $prompt = "Analyse ces données financières Wari : $financialData. 
        Tu dois impérativement retourner un objet JSON avec EXACTEMENT ces clés :
        - 'message' : Ton conseil Wari habituel (2 phrases max, ton direct, motivant et imagé).
        - 'prediction' : Une analyse de la fin de mois (ex: 'À ce rythme, ton cash finit le 22').
        - 'dette_conseil' : Si des dettes existent, dis laquelle rembourser en priorité.
        - 'academy_reco' : Le titre d'un sujet de cours utile (ex: 'Maîtriser son épargne').
        - 'alerte_rouge' : Un message de 5 mots max si une catégorie est critique (sinon vide).";

        $system = "Tu es le Coach Wari, expert en souveraineté financière en Afrique. 
        Tu es le 'Grand Frère' qui aide à construire l'avenir brique par brique. 
        Tu es rigoureux sur la discipline mais toujours encourageant. Appelle l'utilisateur 'Champion·ne'.";

        echo $ai->generate($prompt, $system);
        break;

    default:
        echo json_encode(['error' => 'Action inconnue']);
        break;
}

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
