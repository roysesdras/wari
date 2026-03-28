<?php
// /var/www/html/academy/pdf_download.php

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: https://wari.digiroys.com/login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Academy.php';

$academy = new Academy($pdo);
$user_id = $_SESSION['user_id'];
$pdf_id  = (int)($_GET['id'] ?? 0);
$success = isset($_GET['success']); // Vient-on d'un achat réussi ?

if (!$pdf_id) {
    header('Location: /academy/');
    exit;
}

// Récupération du PDF
$stmt = $pdo->prepare("
    SELECT p.*, co.titre AS course_titre, co.slug AS course_slug,
           c.titre AS cat_titre, c.icone AS cat_icone
    FROM academy_pdfs p
    JOIN academy_courses co ON co.id = p.course_id
    JOIN academy_categories c ON c.id = co.category_id
    WHERE p.id = ? AND p.est_actif = 1
");
$stmt->execute([$pdf_id]);
$pdf = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pdf) {
    header('Location: /academy/');
    exit;
}

// Vérification accès : gratuit OU acheté
$hasAccess = $pdf['est_gratuit'] || $academy->hasUserBoughtPdf($user_id, $pdf_id);

if (!$hasAccess) {
    header('Location: /academy/pdf_achat.php?id=' . $pdf_id);
    exit;
}

// ── Action de téléchargement direct
if (isset($_GET['download'])) {
    $filePath = '/var/www/html' . $pdf['fichier_path'];

    if (!file_exists($filePath)) {
        die("Fichier introuvable. Contacte le support.");
    }

    // Incrémenter le compteur de téléchargements
    $pdo->prepare("UPDATE academy_pdfs SET nb_telechargements = nb_telechargements + 1 WHERE id = ?")
        ->execute([$pdf_id]);

    // Headers de téléchargement
    $ext      = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'pdf'  => 'application/pdf',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xls'  => 'application/vnd.ms-excel',
        'zip'  => 'application/zip',
    ];
    $mime     = $mimeTypes[$ext] ?? 'application/octet-stream';
    $filename = 'WariAcademy_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $pdf['titre']) . '.' . $ext;

    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: private, no-cache');
    header('X-Content-Type-Options: nosniff');

    readfile($filePath);
    exit;
}

// Nb de téléchargements de l'utilisateur pour ce PDF
$nbDlUser = $pdo->prepare("
    SELECT nb_telechargements FROM academy_pdfs WHERE id = ?
");
$nbDlUser->execute([$pdf_id]);
$totalDl = (int)$nbDlUser->fetch(PDO::FETCH_ASSOC)['nb_telechargements'];

// Extension du fichier pour affichage
$ext = strtolower(pathinfo($pdf['fichier_path'], PATHINFO_EXTENSION));
$extLabels = ['pdf' => '📄 PDF', 'xlsx' => '📊 Excel', 'xls' => '📊 Excel', 'zip' => '📦 ZIP'];
$extLabel  = $extLabels[$ext] ?? '📄 Fichier';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pdf['titre']) ?> — Wari Academy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --or:      #C9A84C;
            --or-dark: #8B6914;
            --encre:   #0F0A02;
            --creme:   #FAF5E9;
            --tr: .22s cubic-bezier(.4,0,.2,1);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--encre);
            min-height: 100vh;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 40px 16px;
            position: relative; overflow-x: hidden;
        }
        body::before {
            content: '';
            position: fixed; inset: 0;
            background:
                radial-gradient(ellipse at 30% 40%, rgba(201,168,76,.08) 0%, transparent 50%),
                radial-gradient(ellipse at 70% 70%, rgba(201,168,76,.05) 0%, transparent 50%),
                repeating-linear-gradient(45deg, transparent, transparent 40px,
                    rgba(201,168,76,.015) 40px, rgba(201,168,76,.015) 41px);
            pointer-events: none;
        }

        /* ── CARD ── */
        .card {
            width: 100%; max-width: 520px;
            position: relative; z-index: 1;
        }

        /* Succès banner */
        .success-banner {
            background: rgba(34,197,94,.1);
            border: 1px solid rgba(34,197,94,.25);
            border-radius: 14px;
            padding: 14px 20px;
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 20px;
        }
        .success-banner-icon { font-size: 1.4rem; }
        .success-banner-text { font-size: .85rem; color: #4ade80; font-weight: 600; }
        .success-banner-sub  { font-size: .75rem; color: rgba(74,222,128,.6); margin-top: 2px; }

        /* Main card */
        .download-card {
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(201,168,76,.15);
            border-radius: 24px;
            overflow: hidden;
            position: relative;
        }
        .download-card::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, transparent, var(--or), transparent);
        }

        /* En-tête */
        .dl-header {
            padding: 32px;
            text-align: center;
            background: linear-gradient(180deg, rgba(201,168,76,.06) 0%, transparent 100%);
            border-bottom: 1px solid rgba(201,168,76,.08);
        }
        .dl-file-icon {
            width: 72px; height: 72px; border-radius: 20px;
            background: rgba(201,168,76,.12);
            border: 1px solid rgba(201,168,76,.25);
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; margin: 0 auto 16px;
        }
        .dl-cat {
            font-size: .68rem; font-weight: 700;
            letter-spacing: .12em; text-transform: uppercase;
            color: var(--or); margin-bottom: 8px;
        }
        .dl-titre {
            font-size: 1.15rem; font-weight: 800;
            color: #fff; line-height: 1.3; margin-bottom: 6px;
        }
        .dl-auteur {
            font-size: .78rem; color: rgba(255,255,255,.3);
        }

        /* Méta infos */
        .dl-meta {
            display: flex; justify-content: center; gap: 24px;
            margin-top: 18px; flex-wrap: wrap;
        }
        .dl-meta-item {
            text-align: center;
        }
        .dl-meta-val {
            font-size: 1.1rem; font-weight: 800;
            color: var(--or); display: block; line-height: 1;
        }
        .dl-meta-lbl {
            font-size: .65rem; color: rgba(255,255,255,.25);
            text-transform: uppercase; letter-spacing: .08em;
            margin-top: 3px; display: block;
        }

        /* Body */
        .dl-body { padding: 28px 32px; }

        /* Bouton de téléchargement */
        .btn-download {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%;
            background: var(--or);
            color: var(--encre);
            font-family: 'Poppins', sans-serif;
            font-size: .95rem; font-weight: 800;
            padding: 16px;
            border-radius: 14px;
            text-decoration: none;
            transition: all var(--tr);
            margin-bottom: 16px;
        }
        .btn-download:hover {
            background: #F0D080;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(201,168,76,.25);
        }
        .btn-download:active { transform: translateY(0); }

        /* Infos accès */
        .access-info {
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 12px;
            padding: 14px 16px;
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 20px;
        }
        .access-info-icon { font-size: 1.2rem; }
        .access-info-text {
            font-size: .78rem; color: rgba(255,255,255,.5); line-height: 1.5;
        }
        .access-info-text strong { color: rgba(255,255,255,.8); }

        /* Cours lié */
        .course-link {
            display: flex; align-items: center; gap: 12px;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 12px;
            padding: 14px 16px;
            text-decoration: none;
            transition: all var(--tr);
        }
        .course-link:hover {
            border-color: rgba(201,168,76,.25);
            background: rgba(201,168,76,.05);
        }
        .course-link-icon { font-size: 1.3rem; }
        .course-link-info {}
        .course-link-label {
            font-size: .68rem; color: rgba(255,255,255,.3);
            text-transform: uppercase; letter-spacing: .08em; margin-bottom: 3px;
        }
        .course-link-titre {
            font-size: .85rem; font-weight: 600; color: #e2e8f0;
        }
        .course-link-arrow {
            margin-left: auto; color: rgba(255,255,255,.2);
            font-size: 1rem;
        }

        /* Footer card */
        .dl-footer {
            padding: 16px 32px;
            border-top: 1px solid rgba(255,255,255,.04);
            display: flex; align-items: center; justify-content: center; gap: 6px;
            font-size: .72rem; color: rgba(255,255,255,.2);
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .card { animation: fadeUp .4s ease both; }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50%       { transform: scale(1.05); }
        }
        .btn-download { animation: pulse 2.5s ease-in-out 1s 3; }

        @media (max-width: 480px) {
            .dl-body { padding: 22px 20px; }
            .dl-header { padding: 24px 20px; }
            .dl-footer { padding: 14px 20px; }
        }
    </style>
</head>
<body>

<div class="card">

    <!-- Bannière succès si on vient d'un achat -->
    <?php if ($success): ?>
    <div class="success-banner">
        <span class="success-banner-icon">🎉</span>
        <div>
            <p class="success-banner-text">Paiement confirmé !</p>
            <p class="success-banner-sub">Ton guide est prêt à être téléchargé.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Card principale -->
    <div class="download-card">

        <!-- En-tête -->
        <div class="dl-header">
            <div class="dl-file-icon">
                <?= ['pdf' => '📄', 'xlsx' => '📊', 'xls' => '📊', 'zip' => '📦'][$ext] ?? '📄' ?>
            </div>
            <p class="dl-cat"><?= $pdf['cat_icone'] ?> <?= htmlspecialchars($pdf['cat_titre']) ?></p>
            <p class="dl-titre"><?= htmlspecialchars($pdf['titre']) ?></p>
            <p class="dl-auteur">✍️ <?= htmlspecialchars($pdf['auteur']) ?></p>

            <div class="dl-meta">
                <div class="dl-meta-item">
                    <span class="dl-meta-val"><?= strtoupper($ext) ?></span>
                    <span class="dl-meta-lbl">Format</span>
                </div>
                <div class="dl-meta-item">
                    <span class="dl-meta-val"><?= number_format($totalDl) ?></span>
                    <span class="dl-meta-lbl">Téléchargements</span>
                </div>
                <?php if (!$pdf['est_gratuit']): ?>
                <div class="dl-meta-item">
                    <span class="dl-meta-val">✓</span>
                    <span class="dl-meta-lbl">Acheté</span>
                </div>
                <?php else: ?>
                <div class="dl-meta-item">
                    <span class="dl-meta-val">🆓</span>
                    <span class="dl-meta-lbl">Gratuit</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Body -->
        <div class="dl-body">

            <!-- Bouton télécharger -->
            <a href="/academy/pdf_download.php?id=<?= $pdf_id ?>&download=1"
               class="btn-download">
                ⬇️ Télécharger le guide
            </a>

            <!-- Info accès illimité -->
            <div class="access-info">
                <span class="access-info-icon">♾️</span>
                <p class="access-info-text">
                    <strong>Accès illimité.</strong>
                    Tu peux télécharger ce guide autant de fois que tu veux
                    depuis cette page.
                </p>
            </div>

            <!-- Lien vers le cours -->
            <a href="/academy/course.php?slug=<?= urlencode($pdf['course_slug']) ?>"
               class="course-link">
                <span class="course-link-icon">📚</span>
                <div class="course-link-info">
                    <p class="course-link-label">Cours lié</p>
                    <p class="course-link-titre"><?= htmlspecialchars($pdf['course_titre']) ?></p>
                </div>
                <span class="course-link-arrow">→</span>
            </a>

        </div>

        <!-- Footer -->
        <div class="dl-footer">
            🔒 Fichier sécurisé · Wari Academy
        </div>

    </div>

</div>

</body>
</html>