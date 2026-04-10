<?php
// /var/www/html/academy-admin/login.php

// Chargement du .env
$envFile = '../wari-admin/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0 || !strpos($line, '=')) continue;
        list($name, $value) = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        $_ENV[$name] = $value;
    }
}

if (session_status() === PHP_SESSION_NONE) session_start();

// Déjà connecté → dashboard
if (isset($_SESSION['academy_user'])) {
    header('Location: /academy-admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Récupération des comptes depuis le .env
    $usersJson = $_ENV['ACADEMY_ADMIN_USERS'] ?? '{}';
    $users     = json_decode($usersJson, true) ?? [];

    if (isset($users[$username]) && $users[$username] === $password) {
        $_SESSION['academy_user'] = $username;
        $_SESSION['academy_login_at'] = time();
        header('Location: /academy-admin/index.php');
        exit;
    } else {
        $error = 'Identifiants incorrects. Réessaie.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wari Academy | Connexion Admin</title>

    <link rel="icon" type="image/png" href="../assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="../assets/warifinance3d.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --or: #C9A84C;
            --or-dark: #8B6914;
            --encre: #0F0A02;
            --creme: #FAF5E9;
            --creme2: #F0E8D0;
            --blanc: #FFFFFF;
            --gris: #7A6E60;
            --rouge: #C62828;
            --font-titre: 'Playfair Display', serif;
            --font-corps: 'DM Sans', sans-serif;
            --tr: .22s cubic-bezier(.4, 0, .2, 1);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-corps);
            background: var(--encre);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Motif de fond */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(201, 168, 76, .08) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(201, 168, 76, .05) 0%, transparent 50%),
                repeating-linear-gradient(45deg, transparent, transparent 40px,
                    rgba(201, 168, 76, .02) 40px, rgba(201, 168, 76, .02) 41px);
            pointer-events: none;
        }

        /* Cercles décoratifs */
        .deco {
            position: fixed;
            border-radius: 50%;
            border: 1px solid rgba(201, 168, 76, .07);
            pointer-events: none;
        }

        .deco-1 {
            width: 500px;
            height: 500px;
            top: -150px;
            right: -150px;
        }

        .deco-2 {
            width: 300px;
            height: 300px;
            bottom: -80px;
            left: -80px;
            border-color: rgba(201, 168, 76, .05);
        }

        /* Card login */
        .card {
            background: rgba(255, 255, 255, .03);
            border: 1px solid rgba(201, 168, 76, .15);
            border-radius: 24px;
            padding: 52px 48px;
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
        }

        /* Top barre or */
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 40px;
            right: 40px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--or), transparent);
            border-radius: 999px;
        }

        .card-logo {
            text-align: center;
            margin-bottom: 36px;
        }

        .card-logo-title {
            font-family: var(--font-titre);
            font-size: 1.6rem;
            font-weight: 900;
            color: var(--or);
            display: block;
            letter-spacing: .02em;
        }

        .card-logo-sub {
            font-size: .72rem;
            color: rgba(255, 255, 255, .25);
            letter-spacing: .14em;
            text-transform: uppercase;
            margin-top: 4px;
            display: block;
        }

        .card-logo-badge {
            display: inline-block;
            margin-top: 12px;
            background: rgba(201, 168, 76, .1);
            border: 1px solid rgba(201, 168, 76, .2);
            color: rgba(201, 168, 76, .7);
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 999px;
        }

        .card h2 {
            font-family: var(--font-titre);
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--blanc);
            margin-bottom: 6px;
        }

        .card p {
            font-size: .82rem;
            color: rgba(255, 255, 255, .35);
            margin-bottom: 28px;
            line-height: 1.5;
        }

        /* Champs */
        .field {
            margin-bottom: 16px;
        }

        .field label {
            display: block;
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .35);
            margin-bottom: 7px;
        }

        .field input {
            width: 100%;
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(201, 168, 76, .15);
            border-radius: 10px;
            padding: 12px 16px;
            font-family: var(--font-corps);
            font-size: .9rem;
            color: var(--blanc);
            outline: none;
            transition: border-color var(--tr), background var(--tr);
        }

        .field input::placeholder {
            color: rgba(255, 255, 255, .2);
        }

        .field input:focus {
            border-color: rgba(201, 168, 76, .5);
            background: rgba(201, 168, 76, .05);
        }

        /* Erreur */
        .error-box {
            background: rgba(198, 40, 40, .12);
            border: 1px solid rgba(198, 40, 40, .3);
            border-radius: 10px;
            padding: 11px 14px;
            margin-bottom: 20px;
            font-size: .82rem;
            color: #EF9A9A;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Bouton */
        .btn-submit {
            width: 100%;
            background: var(--or);
            color: var(--encre);
            font-family: var(--font-corps);
            font-size: .9rem;
            font-weight: 700;
            padding: 14px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all var(--tr);
            margin-top: 8px;
            letter-spacing: .03em;
        }

        .btn-submit:hover {
            background: #F0D080;
            transform: translateY(-1px);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* Lien retour */
        .back-link {
            text-align: center;
            margin-top: 24px;
        }

        .back-link a {
            font-size: .78rem;
            color: rgba(255, 255, 255, .2);
            text-decoration: none;
            transition: color var(--tr);
        }

        .back-link a:hover {
            color: var(--or);
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            animation: fadeUp .5s ease both;
        }
    </style>
</head>

<body>

    <div class="deco deco-1"></div>
    <div class="deco deco-2"></div>

    <div class="card">

        <div class="card-logo">
            <span class="card-logo-title">Wari Academy</span>
            <span class="card-logo-sub">Administration</span>
            <span class="card-logo-badge">Accès restreint</span>
        </div>

        <h2>Connexion</h2>
        <p>Espace réservé aux administrateurs de Wari Academy.</p>

        <?php if ($error): ?>
            <div class="error-box flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="field">
                <label>Identifiant</label>
                <input
                    type="text"
                    name="username"
                    placeholder="Ton identifiant"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    autocomplete="username"
                    required>
            </div>
            <div class="field">
                <label>Mot de passe</label>
                <input
                    type="password"
                    name="password"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    required>
            </div>
            <button type="submit" class="btn-submit">
                Se connecter →
            </button>
        </form>

        <div class="back-link">
            <a href="https://wari.digiroys.com/accueil/">← Retour à Wari</a>
        </div>

    </div>

</body>

</html>