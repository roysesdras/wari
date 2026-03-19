<?php
session_start();
require '../config/db.php';

/* ══════════════════════════════════════════════════════════════════
   CONNEXION INTÉGRÉE
══════════════════════════════════════════════════════════════════ */
define('ADMIN_PASSWORD', '@wariFinance-2026'); // ← Remplace par ton mot de passe

$auth_error = '';

/* Déconnexion */
if (isset($_POST['logout'])) {
    unset($_SESSION['wari_avis_auth']);
    header('Location: admin_avis.php');
    exit;
}

/* Soumission du formulaire de connexion */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password']) && !isset($_POST['action']) && !isset($_POST['logout'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['wari_avis_auth'] = true;
        header('Location: admin_avis.php');
        exit;
    }
    $auth_error = 'Mot de passe incorrect.';
}

$logged_in = !empty($_SESSION['wari_avis_auth']);

/* ══════════════════════════════════════════════════════════════════
   ACTIONS AJAX (approuver / rejeter / supprimer)
   — seulement si connecté
══════════════════════════════════════════════════════════════════ */
if ($logged_in && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    header('Content-Type: application/json');
    $id = (int)$_POST['id'];
    try {
        switch ($_POST['action']) {
            case 'approuver':
                $pdo->prepare("UPDATE avis SET visible = 1 WHERE id = ?")->execute([$id]);
                echo json_encode(['ok' => true, 'msg' => 'Avis publié.']);
                break;
            case 'rejeter':
                $pdo->prepare("UPDATE avis SET visible = 0 WHERE id = ?")->execute([$id]);
                echo json_encode(['ok' => true, 'msg' => 'Avis masqué.']);
                break;
            case 'modifier':
                $nom   = trim(strip_tags($_POST['nom']   ?? ''));
                $ville = trim(strip_tags($_POST['ville'] ?? ''));
                $texte = trim(strip_tags($_POST['texte'] ?? ''));
                $note  = (int)($_POST['note'] ?? 0);
                if (mb_strlen($nom) < 2 || mb_strlen($texte) < 5 || $note < 1 || $note > 5) {
                    echo json_encode(['ok' => false, 'msg' => 'Données invalides.']);
                    break;
                }
                $pdo->prepare("UPDATE avis SET nom=?, ville=?, texte=?, note=? WHERE id=?")
                    ->execute([$nom, $ville ?: null, $texte, $note, $id]);
                echo json_encode(['ok' => true, 'msg' => 'Avis modifié.']);
                break;
            case 'modifier_approuver':
                $nom   = trim(strip_tags($_POST['nom']   ?? ''));
                $ville = trim(strip_tags($_POST['ville'] ?? ''));
                $texte = trim(strip_tags($_POST['texte'] ?? ''));
                $note  = (int)($_POST['note'] ?? 0);
                if (mb_strlen($nom) < 2 || mb_strlen($texte) < 5 || $note < 1 || $note > 5) {
                    echo json_encode(['ok' => false, 'msg' => 'Données invalides.']);
                    break;
                }
                $pdo->prepare("UPDATE avis SET nom=?, ville=?, texte=?, note=?, visible=1 WHERE id=?")
                    ->execute([$nom, $ville ?: null, $texte, $note, $id]);
                echo json_encode(['ok' => true, 'msg' => 'Avis modifié et publié.']);
                break;
            case 'supprimer':
                $pdo->prepare("DELETE FROM avis WHERE id = ?")->execute([$id]);
                echo json_encode(['ok' => true, 'msg' => 'Avis supprimé.']);
                break;
            default:
                echo json_encode(['ok' => false, 'msg' => 'Action inconnue.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['ok' => false, 'msg' => 'Erreur DB.']);
    }
    exit;
}

/* ══════════════════════════════════════════════════════════════════
   DONNÉES (seulement si connecté)
══════════════════════════════════════════════════════════════════ */
$avis_list = [];
$counts    = ['attente' => 0, 'publies' => 0, 'tous' => 0];

if ($logged_in) {
    $filtre = $_GET['filtre'] ?? 'attente';
    $where  = match ($filtre) {
        'publies' => 'WHERE visible = 1',
        'attente' => 'WHERE visible = 0',
        default   => ''
    };

    $counts = $pdo->query("
        SELECT SUM(visible=0) as attente, SUM(visible=1) as publies, COUNT(*) as tous
        FROM avis
    ")->fetch(PDO::FETCH_ASSOC);

    $avis_list = $pdo->query("
        SELECT id, nom, ville, note, texte, visible, created_at
        FROM avis $where ORDER BY created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $filtre = 'attente';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modération avis — Wari</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #010202;
            --bg2: #0c1520;
            --surface: #1a2a3a;
            --surface2: #1e3040;
            --border: #243447;
            --border2: #2d4155;
            --teal: #2dd4bf;
            --teal-lt: #5eead4;
            --amber: #fbbf24;
            --rose: #f87171;
            --text: #e2e8f0;
            --muted: #7a93aa;
            --muted2: #4e6478;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* ══ PAGE CONNEXION ══════════════════════════════════════════ */
        .login-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .login-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 380px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, .5);
        }

        .login-logo {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #94a3b8, var(--teal));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block;
            text-align: center;
            margin-bottom: .3rem;
        }

        .login-sub {
            text-align: center;
            font-size: .82rem;
            color: var(--muted2);
            border-left: 3px solid var(--teal);
            padding-left: .6rem;
            margin: 0 auto 2rem;
            width: fit-content;
        }

        .login-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #f1f5f9;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .login-field {
            margin-bottom: 1.2rem;
        }

        .login-field label {
            display: block;
            font-size: .82rem;
            font-weight: 500;
            color: var(--muted);
            margin-bottom: .4rem;
        }

        .login-field input {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border2);
            border-radius: 10px;
            padding: .75rem 1rem;
            color: var(--text);
            font-family: inherit;
            font-size: .95rem;
            transition: border-color .2s, box-shadow .2s;
        }

        .login-field input:focus {
            outline: none;
            border-color: rgba(45, 212, 191, .5);
            box-shadow: 0 0 0 3px rgba(45, 212, 191, .08);
        }

        .login-error {
            background: rgba(248, 113, 113, .1);
            border: 1px solid rgba(248, 113, 113, .3);
            border-radius: 10px;
            padding: .7rem 1rem;
            font-size: .85rem;
            color: #fca5a5;
            margin-bottom: 1.2rem;
            text-align: center;
        }

        .btn-login {
            width: 100%;
            background: var(--teal);
            color: #0f172a;
            font-weight: 700;
            font-size: .95rem;
            padding: .85rem;
            border-radius: 40px;
            border: none;
            cursor: pointer;
            transition: background .2s, transform .15s;
            box-shadow: 0 8px 20px rgba(45, 212, 191, .2);
        }

        .btn-login:hover {
            background: var(--teal-lt);
            transform: translateY(-2px);
        }

        /* ══ PAGE MODÉRATION ═════════════════════════════════════════ */
        .admin-wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2rem 1.25rem;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .page-logo {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #94a3b8, var(--teal));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
        }

        .page-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #f1f5f9;
        }

        .page-sub {
            font-size: .82rem;
            color: var(--muted);
        }

        .header-right {
            display: flex;
            gap: .6rem;
            align-items: center;
        }

        .btn-small {
            font-size: .82rem;
            color: var(--muted);
            text-decoration: none;
            border: 1px solid var(--border2);
            border-radius: 20px;
            padding: .35rem .9rem;
            cursor: pointer;
            background: transparent;
            transition: color .15s, border-color .15s;
        }

        .btn-small:hover {
            color: var(--rose);
            border-color: var(--rose);
        }

        /* Onglets */
        .tabs {
            display: flex;
            gap: .5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .tab {
            padding: .45rem 1.1rem;
            border-radius: 20px;
            border: 1px solid var(--border2);
            font-size: .85rem;
            cursor: pointer;
            text-decoration: none;
            color: var(--muted);
            background: transparent;
            transition: all .15s;
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .tab:hover {
            border-color: var(--teal);
            color: var(--teal);
        }

        .tab.active {
            background: var(--surface2);
            color: #f1f5f9;
            border-color: var(--border);
        }

        .tab-count {
            background: var(--bg2);
            border-radius: 10px;
            padding: .05rem .45rem;
            font-size: .75rem;
            color: var(--muted);
        }

        .tab.active .tab-count {
            background: var(--border);
            color: var(--text);
        }

        .tab-count.urgent {
            background: rgba(248, 113, 113, .2);
            color: #fca5a5;
        }

        /* Grille cartes */
        .avis-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.2rem;
        }

        .avis-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 1.4rem;
            display: flex;
            flex-direction: column;
            gap: .9rem;
        }

        .avis-card.pending {
            border-left: 3px solid var(--amber);
        }

        .avis-card.published {
            border-left: 3px solid var(--teal);
        }

        .card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .stars {
            color: var(--amber);
            font-size: .9rem;
        }

        .card-date {
            font-size: .72rem;
            color: var(--muted2);
        }

        .status-badge {
            font-size: .72rem;
            font-weight: 600;
            border-radius: 20px;
            padding: .15rem .6rem;
        }

        .status-badge.pending {
            background: rgba(251, 191, 36, .12);
            color: var(--amber);
        }

        .status-badge.published {
            background: rgba(45, 212, 191, .12);
            color: var(--teal);
        }

        .card-author {
            font-size: .9rem;
            font-weight: 600;
            color: #f1f5f9;
        }

        .card-ville {
            font-size: .78rem;
            color: var(--muted);
            margin-left: .3rem;
            font-weight: 400;
        }

        .card-text {
            font-size: .87rem;
            color: #c8d8e8;
            line-height: 1.6;
            font-style: italic;
            background: var(--bg2);
            border-radius: 10px;
            padding: .75rem 1rem;
            flex: 1;
        }

        .card-actions {
            display: flex;
            gap: .5rem;
        }

        .btn-action {
            flex: 1;
            padding: 0.2rem 0.2rem;
            border-radius: 10px;
            border: 1px solid transparent;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .25rem;
        }

        .btn-approuver {
            background: rgba(45, 212, 191, .12);
            color: var(--teal);
            border-color: rgba(45, 212, 191, .25);
        }

        .btn-approuver:hover {
            background: rgba(45, 212, 191, .22);
        }

        .btn-rejeter {
            background: rgba(251, 191, 36, .1);
            color: var(--amber);
            border-color: rgba(251, 191, 36, .2);
        }

        .btn-rejeter:hover {
            background: rgba(251, 191, 36, .2);
        }

        .btn-supprimer {
            background: rgba(248, 113, 113, .1);
            color: var(--rose);
            border-color: rgba(248, 113, 113, .2);
        }

        .btn-supprimer:hover {
            background: rgba(248, 113, 113, .2);
        }

        .btn-edit {
            background: rgba(56, 189, 248, .1);
            color: #38bdf8;
            border-color: rgba(56, 189, 248, .2);
        }

        .btn-edit:hover {
            background: rgba(56, 189, 248, .2);
        }

        /* Modale édition */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .75);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-box {
            background: var(--surface);
            border: 1px solid var(--border2);
            border-radius: 20px;
            padding: 2rem;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, .5);
        }

        .modal-title {
            font-size: 1rem;
            font-weight: 600;
            color: #f1f5f9;
            margin-bottom: 1.2rem;
        }

        .modal-field {
            margin-bottom: 1rem;
        }

        .modal-field label {
            display: block;
            font-size: .78rem;
            color: var(--muted);
            margin-bottom: .35rem;
            font-weight: 500;
        }

        .modal-field input,
        .modal-field textarea,
        .modal-field select {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border2);
            border-radius: 10px;
            padding: .7rem .9rem;
            color: var(--text);
            font-family: inherit;
            font-size: .9rem;
            transition: border-color .2s;
        }

        .modal-field input:focus,
        .modal-field textarea:focus {
            outline: none;
            border-color: rgba(45, 212, 191, .5);
        }

        .modal-field textarea {
            resize: vertical;
            min-height: 110px;
            line-height: 1.55;
        }

        .modal-actions {
            display: flex;
            gap: .6rem;
            margin-top: 1.4rem;
            flex-wrap: wrap;
        }

        .modal-actions button {
            flex: 1;
            min-width: 100px;
            padding: .65rem .8rem;
            border-radius: 10px;
            border: 1px solid transparent;
            font-size: .82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s;
        }

        .btn-save {
            background: rgba(56, 189, 248, .12);
            color: #38bdf8;
            border-color: rgba(56, 189, 248, .25);
        }

        .btn-save:hover {
            background: rgba(56, 189, 248, .22);
        }

        .btn-save-pub {
            background: rgba(45, 212, 191, .12);
            color: var(--teal);
            border-color: rgba(45, 212, 191, .25);
        }

        .btn-save-pub:hover {
            background: rgba(45, 212, 191, .22);
        }

        .btn-cancel {
            background: var(--surface2);
            color: var(--muted);
            border-color: var(--border2);
        }

        .btn-cancel:hover {
            color: var(--text);
        }

        /* Toast */
        #toast {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            background: var(--surface2);
            border: 1px solid var(--border2);
            border-radius: 12px;
            padding: .75rem 1.2rem;
            font-size: .88rem;
            color: var(--text);
            box-shadow: 0 8px 24px rgba(0, 0, 0, .4);
            transform: translateY(20px);
            opacity: 0;
            transition: all .25s;
            pointer-events: none;
            z-index: 999;
        }

        #toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        #toast.ok {
            border-color: rgba(45, 212, 191, .4);
            color: var(--teal);
        }

        #toast.err {
            border-color: rgba(248, 113, 113, .4);
            color: var(--rose);
        }

        /* État vide */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--muted);
            grid-column: 1/-1;
        }

        .empty-state div {
            font-size: 2rem;
            margin-bottom: .8rem;
            opacity: .4;
        }

        @media(max-width:480px) {
            .avis-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <?php if (!$logged_in): ?>
        <!-- ══ ÉCRAN CONNEXION ══════════════════════════════════════════════ -->
        <div class="login-wrap">
            <div class="login-card">
                <span class="login-logo">WARI</span>
                <p class="login-sub">modération des avis</p>
                <p class="login-title">Accès administrateur</p>

                <?php if ($auth_error): ?>
                    <div class="login-error">🔒 <?= htmlspecialchars($auth_error) ?></div>
                <?php endif; ?>

                <form method="POST" action="admin_avis.php">
                    <div class="login-field">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password"
                            placeholder="••••••••" autofocus autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn-login">Connexion →</button>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- ══ ÉCRAN MODÉRATION ═════════════════════════════════════════════ -->
        <div class="admin-wrap">

            <div class="page-header">
                <div>
                    <a class="page-logo" href="../accueil.php">WARI</a>
                    <div class="page-title" style="margin-top:.2rem">Modération des avis</div>
                    <div class="page-sub">Approuvez les avis avant publication sur l'accueil</div>
                </div>
                <div class="header-right">
                    <a href="../admin.php" class="btn-small">← Dashboard</a>
                    <form method="POST" style="display:inline">
                        <button type="submit" name="logout" value="1" class="btn-small">Déconnexion</button>
                    </form>
                </div>
            </div>

            <div class="tabs">
                <a href="?filtre=attente" class="tab <?= $filtre === 'attente' ? 'active' : '' ?>">
                    En attente
                    <span class="tab-count <?= $counts['attente'] > 0 ? 'urgent' : '' ?>">
                        <?= (int)$counts['attente'] ?>
                    </span>
                </a>
                <a href="?filtre=publies" class="tab <?= $filtre === 'publies' ? 'active' : '' ?>">
                    Publiés <span class="tab-count"><?= (int)$counts['publies'] ?></span>
                </a>
                <a href="?filtre=tous" class="tab <?= $filtre === 'tous' ? 'active' : '' ?>">
                    Tous <span class="tab-count"><?= (int)$counts['tous'] ?></span>
                </a>
            </div>

            <div class="avis-grid">
                <?php if (empty($avis_list)): ?>
                    <div class="empty-state">
                        <div>✓</div>
                        <p>Aucun avis dans cette catégorie.</p>
                    </div>

                    <?php else: foreach ($avis_list as $a):
                        $pending = $a['visible'] == 0;
                        $stars   = str_repeat('★', $a['note']) . str_repeat('☆', 5 - $a['note']);
                        $date    = date('d/m/Y à H:i', strtotime($a['created_at']));
                    ?>
                        <div class="avis-card <?= $pending ? 'pending' : 'published' ?>" id="card-<?= $a['id'] ?>">

                            <div class="card-top">
                                <span class="stars"><?= $stars ?></span>
                                <span class="card-date"><?= $date ?></span>
                                <span class="status-badge <?= $pending ? 'pending' : 'published' ?>">
                                    <?= $pending ? '⏳ En attente' : '✓ Publié' ?>
                                </span>
                            </div>

                            <div>
                                <span class="card-author"><?= htmlspecialchars($a['nom']) ?></span>
                                <?php if ($a['ville']): ?>
                                    <span class="card-ville">· <?= htmlspecialchars($a['ville']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="card-text">"<?= nl2br(htmlspecialchars($a['texte'])) ?>"</div>

                            <div class="card-actions">
                                <button class="btn-action btn-edit" onclick="openEdit(<?= $a['id'] ?>, <?= htmlspecialchars(json_encode([
                                                                                                            'nom'   => $a['nom'],
                                                                                                            'ville' => $a['ville'] ?? '',
                                                                                                            'note'  => $a['note'],
                                                                                                            'texte' => $a['texte'],
                                                                                                        ]), ENT_QUOTES) ?>)">✏️ </button>
                                <?php if ($pending): ?>
                                    <button class="btn-action btn-approuver" onclick="doAction(<?= $a['id'] ?>,'approuver')">Approuver</button>
                                <?php else: ?>
                                    <button class="btn-action btn-rejeter" onclick="doAction(<?= $a['id'] ?>,'rejeter')">Masquer</button>
                                <?php endif; ?>
                                <button class="btn-action btn-supprimer" onclick="doAction(<?= $a['id'] ?>,'supprimer')">supprimer</button>
                            </div>

                        </div>
                <?php endforeach;
                endif; ?>
            </div>

        </div>

        <div id="toast"></div>

        <!-- Modale édition -->
        <div class="modal-overlay" id="editModal">
            <div class="modal-box">
                <div class="modal-title">✏️ Modifier l'avis</div>
                <input type="hidden" id="edit-id">
                <div class="modal-field">
                    <label>Nom</label>
                    <input type="text" id="edit-nom" maxlength="80">
                </div>
                <div class="modal-field">
                    <label>Ville <span style="font-weight:400;color:var(--muted2)">(optionnel)</span></label>
                    <input type="text" id="edit-ville" maxlength="80">
                </div>
                <div class="modal-field">
                    <label>Note</label>
                    <select id="edit-note">
                        <option value="5">★★★★★ — Excellent</option>
                        <option value="4">★★★★☆ — Bien</option>
                        <option value="3">★★★☆☆ — Correct</option>
                        <option value="2">★★☆☆☆ — Bof</option>
                        <option value="1">★☆☆☆☆ — Pas satisfait</option>
                    </select>
                </div>
                <div class="modal-field">
                    <label>Texte de l'avis</label>
                    <textarea id="edit-texte" maxlength="1000"></textarea>
                </div>
                <div class="modal-actions">
                    <button class="btn-cancel" onclick="closeEdit()">Annuler</button>
                    <button class="btn-save" onclick="saveEdit('modifier')">💾 Sauvegarder</button>
                    <button class="btn-save-pub" onclick="saveEdit('modifier_approuver')">✓ Sauvegarder & Publier</button>
                </div>
            </div>
        </div>

        <script>
            function doAction(id, act) {
                if (act === 'supprimer' && !confirm('Supprimer définitivement cet avis ?')) return;
                var fd = new FormData();
                fd.append('action', act);
                fd.append('id', id);
                fetch('admin_avis.php', {
                        method: 'POST',
                        body: fd
                    })
                    .then(r => r.json())
                    .then(data => {
                        toast(data.msg, data.ok ? 'ok' : 'err');
                        if (data.ok) {
                            if (act === 'supprimer') {
                                var c = document.getElementById('card-' + id);
                                c.style.transition = 'opacity .3s, transform .3s';
                                c.style.opacity = '0';
                                c.style.transform = 'scale(.95)';
                                setTimeout(() => c.remove(), 300);
                            } else {
                                setTimeout(() => location.reload(), 800);
                            }
                        }
                    })
                    .catch(() => toast('Erreur réseau.', 'err'));
            }

            function openEdit(id, data) {
                document.getElementById('edit-id').value = id;
                document.getElementById('edit-nom').value = data.nom;
                document.getElementById('edit-ville').value = data.ville || '';
                document.getElementById('edit-note').value = data.note;
                document.getElementById('edit-texte').value = data.texte;
                document.getElementById('editModal').classList.add('show');
            }

            function closeEdit() {
                document.getElementById('editModal').classList.remove('show');
            }

            function saveEdit(action) {
                var id = document.getElementById('edit-id').value;
                var nom = document.getElementById('edit-nom').value.trim();
                var ville = document.getElementById('edit-ville').value.trim();
                var note = document.getElementById('edit-note').value;
                var texte = document.getElementById('edit-texte').value.trim();

                if (nom.length < 2) {
                    toast('Nom trop court.', 'err');
                    return;
                }
                if (texte.length < 5) {
                    toast('Texte trop court.', 'err');
                    return;
                }

                var fd = new FormData();
                fd.append('action', action);
                fd.append('id', id);
                fd.append('nom', nom);
                fd.append('ville', ville);
                fd.append('note', note);
                fd.append('texte', texte);

                fetch('admin_avis.php', {
                        method: 'POST',
                        body: fd
                    })
                    .then(r => r.json())
                    .then(data => {
                        toast(data.msg, data.ok ? 'ok' : 'err');
                        if (data.ok) {
                            closeEdit();
                            setTimeout(() => location.reload(), 800);
                        }
                    })
                    .catch(() => toast('Erreur réseau.', 'err'));
            }

            function toast(msg, type) {
                var t = document.getElementById('toast');
                t.textContent = msg;
                t.className = 'show ' + (type || '');
                clearTimeout(t._t);
                t._t = setTimeout(() => t.className = '', 3000);
            }
        </script>

    <?php endif; ?>
</body>

</html>