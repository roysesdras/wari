<?php
// /var/www/html/classes/Academy.php

class Academy
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // ============================================================
    // CATEGORIES
    // ============================================================

    /**
     * Récupère toutes les catégories actives
     */
    // ✅ APRÈS — avec GROUP BY
    public function getCategories()
    {
        return $this->pdo->query("
        SELECT c.*,
            COUNT(DISTINCT co.id) as nb_cours
        FROM academy_categories c
        LEFT JOIN academy_courses co ON co.category_id = c.id AND co.est_actif = 1
        WHERE c.est_actif = 1
        GROUP BY c.id          
        ORDER BY c.ordre ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère une catégorie par son slug
     */
    public function getCategoryBySlug($slug)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM academy_categories
            WHERE slug = ? AND est_actif = 1
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ============================================================
    // COURS
    // ============================================================

    /**
     * Récupère tous les cours d'une catégorie
     */
    public function getCoursesByCategory($category_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT co.*,
                COUNT(DISTINCT l.id) as nb_lecons
            FROM academy_courses co
            LEFT JOIN academy_lessons l ON l.course_id = co.id AND l.est_actif = 1
            WHERE co.category_id = ? AND co.est_actif = 1
            ORDER BY co.ordre ASC
        ");
        $stmt->execute([$category_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un cours par son slug
     */
    public function getCourseBySlug($slug)
    {
        $stmt = $this->pdo->prepare("
            SELECT co.*, c.titre as category_titre, c.slug as category_slug
            FROM academy_courses co
            JOIN academy_categories c ON c.id = co.category_id
            WHERE co.slug = ? AND co.est_actif = 1
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un cours par son ID
     */
    public function getCourseById($course_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT co.*, c.titre as category_titre
            FROM academy_courses co
            JOIN academy_categories c ON c.id = co.category_id
            WHERE co.id = ? AND co.est_actif = 1
        ");
        $stmt->execute([$course_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ============================================================
    // LEÇONS
    // ============================================================

    /**
     * Récupère toutes les leçons d'un cours
     */
    public function getLessonsByCourse($course_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM academy_lessons
            WHERE course_id = ? AND est_actif = 1
            ORDER BY ordre ASC
        ");
        $stmt->execute([$course_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère une leçon par son ID
     */
    public function getLessonById($lesson_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT l.*, co.titre as course_titre, co.slug as course_slug
            FROM academy_lessons l
            JOIN academy_courses co ON co.id = l.course_id
            WHERE l.id = ? AND l.est_actif = 1
        ");
        $stmt->execute([$lesson_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère la leçon suivante dans un cours
     */
    public function getNextLesson($course_id, $current_ordre)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM academy_lessons
            WHERE course_id = ? AND ordre > ? AND est_actif = 1
            ORDER BY ordre ASC
            LIMIT 1
        ");
        $stmt->execute([$course_id, $current_ordre]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère la leçon précédente dans un cours
     */
    public function getPrevLesson($course_id, $current_ordre)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM academy_lessons
            WHERE course_id = ? AND ordre < ? AND est_actif = 1
            ORDER BY ordre DESC
            LIMIT 1
        ");
        $stmt->execute([$course_id, $current_ordre]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ============================================================
    // PROGRESSION UTILISATEUR
    // ============================================================

    /**
     * Marque une leçon comme complétée pour un utilisateur
     */
    public function markLessonComplete($user_id, $lesson_id, $course_id)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO academy_progress (user_id, lesson_id, course_id, est_complete, complete_le)
            VALUES (?, ?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE
                est_complete = 1,
                complete_le  = IF(est_complete = 0, NOW(), complete_le)
        ");
        return $stmt->execute([$user_id, $lesson_id, $course_id]);
    }

    /**
     * Vérifie si une leçon est complétée par un utilisateur
     */
    public function isLessonComplete($user_id, $lesson_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT est_complete FROM academy_progress
            WHERE user_id = ? AND lesson_id = ?
        ");
        $stmt->execute([$user_id, $lesson_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (bool)$row['est_complete'] : false;
    }

    /**
     * Calcule le pourcentage de progression d'un utilisateur dans un cours
     */
    public function getCourseProgress($user_id, $course_id)
    {
        // Total des leçons du cours
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total FROM academy_lessons
            WHERE course_id = ? AND est_actif = 1
        ");
        $stmt->execute([$course_id]);
        $total = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        if ($total === 0) return 0;

        // Leçons complétées par l'utilisateur
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as done FROM academy_progress
            WHERE user_id = ? AND course_id = ? AND est_complete = 1
        ");
        $stmt->execute([$user_id, $course_id]);
        $done = (int)$stmt->fetch(PDO::FETCH_ASSOC)['done'];

        return round(($done / $total) * 100);
    }

    /**
     * Récupère tous les cours avec la progression d'un utilisateur
     */
    public function getAllCoursesWithProgress($user_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT co.*,
                c.titre as category_titre,
                c.couleur as category_couleur,
                c.icone as category_icone,
                COUNT(DISTINCT l.id) as nb_lecons,
                COUNT(DISTINCT CASE WHEN p.est_complete = 1 THEN p.lesson_id END) as lecons_completes
            FROM academy_courses co
            JOIN academy_categories c ON c.id = co.category_id
            LEFT JOIN academy_lessons l ON l.course_id = co.id AND l.est_actif = 1
            LEFT JOIN academy_progress p ON p.course_id = co.id AND p.user_id = ?
            WHERE co.est_actif = 1 AND c.est_actif = 1
            GROUP BY co.id
            ORDER BY c.ordre ASC, co.ordre ASC
        ");
        $stmt->execute([$user_id]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcul du pourcentage pour chaque cours
        foreach ($courses as &$course) {
            $total = (int)$course['nb_lecons'];
            $done  = (int)$course['lecons_completes'];
            $course['progression'] = $total > 0 ? round(($done / $total) * 100) : 0;
        }

        return $courses;
    }

    // ============================================================
    // PDF PAYANTS
    // ============================================================

    /**
     * Récupère les PDF liés à un cours
     */
    public function getPdfsByCourse($course_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM academy_pdfs
            WHERE course_id = ? AND est_actif = 1
            ORDER BY id ASC
        ");
        $stmt->execute([$course_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifie si un utilisateur a déjà acheté un PDF
     */
    public function hasUserBoughtPdf($user_id, $pdf_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT id FROM academy_pdf_achats
            WHERE user_id = ? AND pdf_id = ? AND statut = 'paye'
        ");
        $stmt->execute([$user_id, $pdf_id]);
        return (bool)$stmt->fetch();
    }

    /**
     * Enregistre un achat de PDF
     */
    public function savePdfAchat($user_id, $pdf_id, $montant, $reference = null)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO academy_pdf_achats (user_id, pdf_id, montant, statut, reference)
            VALUES (?, ?, ?, 'paye', ?)
        ");
        return $stmt->execute([$user_id, $pdf_id, $montant, $reference]);
    }

    // ============================================================
    // STATS ADMIN
    // ============================================================

    /**
     * Statistiques globales pour le tableau de bord admin
     */
    public function getAdminStats()
    {
        return [
            'total_apprenants' => $this->pdo->query("
                SELECT COUNT(DISTINCT user_id) as n FROM academy_progress
            ")->fetch(PDO::FETCH_ASSOC)['n'],

            'total_completions' => $this->pdo->query("
                SELECT COUNT(*) as n FROM academy_progress WHERE est_complete = 1
            ")->fetch(PDO::FETCH_ASSOC)['n'],

            'total_cours' => $this->pdo->query("
                SELECT COUNT(*) as n FROM academy_courses WHERE est_actif = 1
            ")->fetch(PDO::FETCH_ASSOC)['n'],

            'total_revenus' => $this->pdo->query("
                SELECT COALESCE(SUM(montant), 0) as n FROM academy_pdf_achats WHERE statut = 'paye'
            ")->fetch(PDO::FETCH_ASSOC)['n'],
        ];
    }

    /**
     * Cours les plus suivis
     */
    // ✅ APRÈS — cast direct en int dans la requête
    public function getTopCourses($limit = 5)
    {
        $limit = (int)$limit; // sécurisé car forcé en entier
        $stmt = $this->pdo->query("
        SELECT co.titre, co.slug,
            COUNT(DISTINCT p.user_id) as nb_apprenants,
            ROUND(
                COUNT(CASE WHEN p.est_complete = 1 THEN 1 END) * 100.0 /
                NULLIF(COUNT(p.id), 0)
            ) as taux_completion
        FROM academy_courses co
        LEFT JOIN academy_progress p ON p.course_id = co.id
        WHERE co.est_actif = 1
        GROUP BY co.id
        ORDER BY nb_apprenants DESC
        LIMIT {$limit}
    ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
