<?php
require '../config/db.php'; // Ton fichier de connexion PDO
require_once __DIR__ . '/../wari_monitoring.php';  // ← TOUJOURS EN PREMIER

/* ──────────────────────────────────────────────────────────────────
   TRAITEMENT DU FORMULAIRE
────────────────────────────────────────────────────────────────── */
$success = false;
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* Nettoyage des entrées */
    $nom   = trim(strip_tags($_POST['nom']   ?? ''));
    $ville = trim(strip_tags($_POST['ville'] ?? ''));
    $note  = (int)($_POST['note'] ?? 0);
    $texte = trim(strip_tags($_POST['texte'] ?? ''));
    $token = $_POST['csrf_token'] ?? '';

    /* Validation CSRF */
    session_start();
    if (!isset($_SESSION['csrf']) || $token !== $_SESSION['csrf']) {
        $errors[] = 'Requête invalide. Veuillez réessayer.';
    }

    /* Validations métier */
    if (mb_strlen($nom) < 2)              $errors[] = 'Votre prénom est requis.';
    if (mb_strlen($nom) > 80)             $errors[] = 'Prénom trop long (80 car. max).';
    if ($note < 1 || $note > 5)           $errors[] = 'Veuillez choisir une note de 1 à 5.';
    if (mb_strlen($texte) < 20)           $errors[] = 'Votre avis doit faire au moins 20 caractères.';
    if (mb_strlen($texte) > 1000)         $errors[] = 'Avis trop long (1000 car. max).';

    /* Anti-spam simple : honeypot */
    if (!empty($_POST['website'])) {
        $errors[] = 'Requête suspecte.';
    }

    /* Enregistrement */
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO avis (nom, ville, note, texte, visible)
                VALUES (:nom, :ville, :note, :texte, 0)
            ");
            $stmt->execute([
                ':nom'   => $nom,
                ':ville' => $ville ?: null,
                ':note'  => $note,
                ':texte' => $texte,
            ]);
            $success = true;
            unset($_SESSION['csrf']); // Invalider le token utilisé
        } catch (PDOException $e) {
            $errors[] = 'Une erreur est survenue. Veuillez réessayer.';
        }
    }
}

/* Génération token CSRF */
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laisser un avis — Wari</title>
    <meta name="description" content="Partagez votre expérience avec Wari, l'application de gestion budgétaire.">
    <!-- Pas d'indexation sur la page formulaire -->
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg: #010202;
            --surface: #1a2a3a;
            --surface2: #1e3040;
            --border: #243447;
            --border2: #2d4155;
            --gold: #F5A623;
            --gold-dk: #d4921f;
            --gold-lt: #ffbe3d;
            --text: #e2e8f0;
            --muted: #7a93aa;
            --muted2: #4e6478;
            --amber: #fbbf24;
            --danger: #f87171;
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
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 0.5rem 0.5rem 2rem;
            -webkit-font-smoothing: antialiased;
        }

        /* ── En-tête ── */
        .page-logo {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--gold-dk), var(--gold), var(--gold-lt));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.3rem;
            text-decoration: none;
            display: inline-block;
        }

        .page-tagline {
            font-size: .85rem;
            color: var(--muted2);
            border-left: 3px solid var(--gold);
            padding-left: .75rem;
            margin-bottom: 2.5rem;
        }

        /* ── Carte formulaire ── */
        .form-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 15px;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
        }

        .form-card h1 {
            font-size: 1.6rem;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: .5rem;
            letter-spacing: -0.02em;
        }

        .form-card .subtitle {
            font-size: .9rem;
            color: var(--muted);
            margin-bottom: 2rem;
            line-height: 1.5;
        }

        /* ── Champs ── */
        .field {
            margin-bottom: 1.4rem;
        }

        label {
            display: block;
            font-size: .85rem;
            font-weight: 500;
            color: var(--muted);
            margin-bottom: .45rem;
        }

        label .req {
            color: var(--gold);
            margin-left: 2px;
        }

        input[type="text"],
        textarea {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border2);
            border-radius: 12px;
            padding: .8rem 1rem;
            color: var(--text);
            font-family: inherit;
            font-size: .95rem;
            line-height: 1.5;
            transition: border-color .2s, box-shadow .2s;
            resize: vertical;
        }

        input[type="text"]::placeholder,
        textarea::placeholder {
            color: var(--muted2);
        }

        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: rgba(45, 212, 191, .5);
            box-shadow: 0 0 0 3px rgba(45, 212, 191, .08);
        }

        /* ── Notation étoiles ── */
        .star-picker {
            display: flex;
            gap: .3rem;
            margin-top: .1rem;
        }

        .star-picker input[type="radio"] {
            display: none;
        }

        .star-picker label {
            font-size: 2rem;
            color: var(--border2);
            cursor: pointer;
            margin-bottom: 0;
            transition: color .15s, transform .1s;
            user-select: none;
        }

        .star-picker label:hover,
        .star-picker label:hover~label {
            color: var(--amber);
        }

        /* Inverser l'ordre pour le hack CSS */
        .star-picker {
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        .star-picker input:checked~label,
        .star-picker label:hover,
        .star-picker input:checked~label~label {
            color: var(--amber);
        }

        /* Note sélectionnée */
        .star-picker input:checked+label {
            transform: scale(1.15);
        }

        .note-label {
            font-size: .8rem;
            color: var(--muted);
            margin-top: .4rem;
            min-height: 1.2em;
            transition: color .2s;
        }

        /* ── Compteur textarea ── */
        .field-count {
            display: flex;
            justify-content: flex-end;
            font-size: .75rem;
            color: var(--muted2);
            margin-top: .3rem;
        }

        .field-count.warn {
            color: var(--danger);
        }

        /* ── Messages ── */
        .alert {
            border-radius: 12px;
            padding: .9rem 1.1rem;
            margin-bottom: 1.5rem;
            font-size: .9rem;
            line-height: 1.5;
        }

        .alert-error {
            background: rgba(248, 113, 113, .1);
            border: 1px solid rgba(248, 113, 113, .3);
            color: #fca5a5;
        }

        .alert-error ul {
            padding-left: 1.2rem;
            margin-top: .3rem;
        }

        .alert-success {
            background: rgba(45, 212, 191, .1);
            border: 1px solid rgba(45, 212, 191, .3);
            color: var(--gold);
        }

        /* ── Bouton submit ── */
        .btn-submit {
            width: 100%;
            background: var(--gold);
            color: #0f172a;
            font-weight: 700;
            font-size: 1rem;
            padding: .9rem;
            border-radius: 40px;
            border: none;
            cursor: pointer;
            transition: background .2s, transform .15s;
            box-shadow: 0 8px 20px rgba(245, 166, 35, 0.2);
            margin-top: .5rem;
        }

        .btn-submit:hover {
            background: var(--gold-lt);
            transform: translateY(-2px);
        }

        .btn-submit:disabled {
            opacity: .6;
            cursor: not-allowed;
            transform: none;
        }

        /* ── Lien retour ── */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            margin-top: 1.5rem;
            font-size: .85rem;
            color: var(--muted);
            text-decoration: none;
            transition: color .15s;
        }

        .back-link:hover {
            color: var(--gold);
        }

        /* ── Page succès ── */
        .success-block {
            text-align: center;
            padding: 1rem 0;
        }

        .success-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(45, 212, 191, .1);
            border: 2px solid rgba(45, 212, 191, .3);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
        }

        .success-block h2 {
            font-size: 1.4rem;
            color: #f1f5f9;
            margin-bottom: .8rem;
        }

        .success-block p {
            color: var(--muted);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        /* ── Honeypot (invisible) ── */
        .hp {
            display: none;
            visibility: hidden;
            position: absolute;
        }

        /* ── Responsive ── */
        @media (min-width: 640px) {
            .form-card {
                padding: 1rem 1rem;
            }
        }
    </style>
</head>

<body>

    <a class="page-logo" href="accueil.php">WARI-Finance</a>
    <p class="page-tagline">Partagez votre expérience</p>

    <div class="form-card">

        <?php if ($success): ?>
            <!-- ── ÉTAT SUCCÈS ── -->
            <div class="success-block">
                <div class="success-icon">✓</div>
                <h2>Merci pour votre avis !</h2>
                <p>Votre témoignage a bien été reçu.<br>
                    Il sera publié après vérification, généralement sous 24 h.</p>
                <a href="index.php" class="btn-submit" style="display:inline-block;text-decoration:none;width:auto;padding:.9rem 2rem;">
                    ← Retour à l'accueil
                </a>
            </div>

        <?php else: ?>
            <!-- ── FORMULAIRE ── -->
            <h1>Votre avis compte</h1>
            <p class="subtitle">
                Dites-nous comment Wari a changé votre rapport à l'argent.<br>
                Votre témoignage aide d'autres personnes à se lancer.
            </p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error" role="alert">
                    <strong>Veuillez corriger les erreurs suivantes :</strong>
                    <ul>
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="laisser-avis.php" id="avis-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

                <!-- Honeypot anti-bot -->
                <div class="hp" aria-hidden="true">
                    <label for="website">Ne pas remplir</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <!-- Nom -->
                <div class="field">
                    <label for="nom">Votre prénom <span class="req">*</span></label>
                    <input type="text" id="nom" name="nom"
                        value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                        placeholder="Ex : Adjovi" maxlength="80" required
                        autocomplete="given-name">
                </div>

                <!-- Ville -->
                <div class="field">
                    <label for="ville">Votre ville <span style="color:var(--muted2);font-weight:400">(optionnel)</span></label>
                    <input type="text" id="ville" name="ville"
                        value="<?= htmlspecialchars($_POST['ville'] ?? '') ?>"
                        placeholder="Ex : Cotonou" maxlength="80"
                        autocomplete="address-level2">
                </div>

                <!-- Note étoiles -->
                <div class="field">
                    <label>Votre note <span class="req">*</span></label>
                    <div class="star-picker" id="star-picker" role="group" aria-label="Note de 1 à 5 étoiles">
                        <?php
                        $selected_note = (int)($_POST['note'] ?? 0);
                        $labels = ['', 'Pas satisfait', 'Bof', 'Correct', 'Bien', 'Excellent !'];
                        for ($s = 5; $s >= 1; $s--):
                        ?>
                            <input type="radio" id="star<?= $s ?>" name="note" value="<?= $s ?>"
                                <?= $selected_note === $s ? 'checked' : '' ?>
                                required>
                            <label for="star<?= $s ?>" title="<?= $labels[$s] ?>">★</label>
                        <?php endfor; ?>
                    </div>
                    <div class="note-label" id="note-label">
                        <?= $selected_note > 0 ? $labels[$selected_note] : 'Cliquez pour noter' ?>
                    </div>
                </div>

                <!-- Avis -->
                <div class="field">
                    <label for="texte">Votre avis <span class="req">*</span></label>
                    <textarea id="texte" name="texte" rows="5"
                        placeholder="Comment Wari a-t-il changé votre gestion financière ? Qu'est-ce que vous appréciez le plus ?"
                        maxlength="1000" required><?= htmlspecialchars($_POST['texte'] ?? '') ?></textarea>
                    <div class="field-count" id="char-count">0 / 1000</div>
                </div>

                <button type="submit" class="btn-submit" id="btn-submit">
                    Envoyer mon avis →
                </button>
            </form>

            <a href="index.php" class="back-link">← Retour à l'accueil</a>

        <?php endif; ?>
    </div>

    <script>
        (function() {
            /* ── Labels des étoiles ── */
            var noteLabels = {
                1: 'Pas satisfait',
                2: 'Bof',
                3: 'Correct',
                4: 'Bien',
                5: 'Excellent !'
            };
            var noteInputs = document.querySelectorAll('input[name="note"]');
            var noteLabel = document.getElementById('note-label');

            noteInputs.forEach(function(input) {
                input.addEventListener('change', function() {
                    if (noteLabel) noteLabel.textContent = noteLabels[this.value] || '';
                });
            });

            /* ── Compteur textarea ── */
            var textarea = document.getElementById('texte');
            var charCount = document.getElementById('char-count');
            if (textarea && charCount) {
                function updateCount() {
                    var n = textarea.value.length;
                    charCount.textContent = n + ' / 1000';
                    charCount.classList.toggle('warn', n > 900);
                }
                textarea.addEventListener('input', updateCount);
                updateCount();
            }

            /* ── Validation front légère avant submit ── */
            var form = document.getElementById('avis-form');
            var btnSubmit = document.getElementById('btn-submit');
            if (form) {
                form.addEventListener('submit', function(e) {
                    var nom = document.getElementById('nom').value.trim();
                    var texte = document.getElementById('texte').value.trim();
                    var note = document.querySelector('input[name="note"]:checked');
                    var ok = true;

                    if (nom.length < 2) {
                        ok = false;
                        document.getElementById('nom').focus();
                    }
                    if (!note) {
                        ok = false;
                    }
                    if (texte.length < 20) {
                        ok = false;
                    }

                    if (!ok) {
                        e.preventDefault();
                        return;
                    }
                    btnSubmit.disabled = true;
                    btnSubmit.textContent = 'Envoi en cours…';
                });
            }
        })();
    </script>

</body>

</html>