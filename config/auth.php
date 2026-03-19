<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wari - Accès Privé</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">

    <link rel="icon" type="image/png" href="../assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="../assets/warifinance3d.png">

    <link rel="stylesheet" href="../assets/styles.css?v=41">
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#0f172a">

    <script src="https://stats.digiroys.com/tracker.js" data-key="key_wari_789"></script>

    <script>
        // Si l'utilisateur est connecté en PHP
        <?php if (isset($_SESSION['user_email'])): ?>
            DigiStats.identify("<?= $_SESSION['user_email'] ?>");
        <?php endif; ?>
    </script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .glass {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        input:focus {
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.2);
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>

<body class="bg-[#0f172a] text-slate-200 min-h-screen flex items-center justify-center p-4">

    <div class="fixed top-0 left-0 w-full h-full overflow-hidden pointer-events-none z-0">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-amber-600/10 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-blue-600/10 rounded-full blur-[120px]"></div>
    </div>

    <div class="w-full max-w-[420px] z-10">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-black text-white tracking-tighter">WARI <span class="text-amber-500 text-sm">FINANCE</span></h1>
            <p class="text-slate-400 text-xs mt-2 uppercase tracking-[0.2em]">Discipline & Progrès</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-400 text-sm font-bold text-center animate-bounce">
                ✅ Votre accès est activé ! Connectez-vous.
            </div>
        <?php endif; ?>

        <div class="glass rounded-[2rem] p-4 shadow-2xl">

            <div class="flex bg-slate-900/50 p-1 rounded-xl mb-8 border border-slate-800">
                <button id="tab-login" onclick="switchTab('login')" class="flex-1 py-2 text-sm font-bold rounded-lg transition-all duration-300 bg-amber-500 text-black shadow-lg">Connexion</button>
                <button id="tab-register" onclick="switchTab('register')" class="flex-1 py-2 text-sm font-bold text-slate-400 rounded-lg transition-all duration-300">Activer</button>
            </div>

            <form id="form-login" action="process_auth.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="login">

                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-bold mb-2 ml-1">Email</label>
                    <input type="email" name="email" placeholder="nom@exemple.com" required
                        class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder:text-slate-600 outline-none focus:border-amber-500/50 transition-all">
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-bold mb-2 ml-1">Mot de passe</label>
                    <input type="password" name="password" placeholder="••••••••" required
                        class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder:text-slate-600 outline-none focus:border-amber-500/50 transition-all">
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-amber-500 to-orange-600 py-4 rounded-xl text-black font-black text-xs uppercase tracking-widest shadow-lg shadow-amber-500/20 active:scale-95 transition-all mt-4">
                    Se Connecter
                </button>
            </form>

            <form id="form-register" action="process_auth.php" method="POST" class="space-y-4 hidden">
                <input type="hidden" name="action" value="register">

                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-bold mb-2 ml-1">Email</label>
                    <input type="email" name="email" placeholder="votre@email.com" required
                        class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder:text-slate-600 outline-none focus:border-amber-500/50 transition-all">
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-bold mb-2 ml-1">Mot de passe</label>
                    <input type="password" name="password" placeholder="Minimum 6 caractères" required
                        class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder:text-slate-600 outline-none focus:border-amber-500/50 transition-all">
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-amber-500 font-bold mb-2 ml-1">N° de Commande (Vérification)</label>
                    <input type="text" name="commande_id" placeholder="Ex: 10984523..." required
                        class="w-full bg-slate-900/50 border border-amber-500/30 rounded-xl px-4 py-3 text-white placeholder:text-slate-600 outline-none focus:border-amber-500 transition-all">
                </div>

                <div class="bg-amber-500/5 p-4 rounded-xl border border-amber-500/10">
                    <p class="text-[9px] text-amber-500/80 leading-relaxed uppercase tracking-tighter">
                        📍 <strong>Où trouver le numéro ?</strong><br>
                        • Sur votre reçu DigitalDownloads (Ref: 109...)<br>
                        • Dans le SMS Mobile Money reçu après achat.
                    </p>
                </div>

                <button type="submit" class="w-full bg-amber-500 py-4 rounded-xl text-black font-black text-xs uppercase tracking-widest active:scale-95 transition-all">
                    Vérifier & Activer
                </button>
            </form>
        </div>

        <div id="installBtn" onclick="triggerInstall()" class="hidden mt-6 group cursor-pointer">
            <div class="glass border-amber-500/20 bg-amber-500/5 p-4 rounded-2xl flex items-center justify-between hover:bg-amber-500/10 transition-all active:scale-95 border border-dashed">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-amber-500 rounded-xl flex items-center justify-center text-black shadow-lg shadow-amber-500/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-[10px] uppercase tracking-widest text-amber-500 font-black">Expérience Mobile</h4>
                        <p class="text-white font-bold text-xs">Installer l'application Wari</p>
                    </div>
                </div>
                <div class="text-amber-500 animate-bounce">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
            </div>
        </div>

        <p class="text-center mt-8 text-slate-500 text-[10px] uppercase tracking-widest">
            &copy; <script>
                document.write(new Date().getFullYear())
            </script> Wari Finance • Tous droits réservés
        </p>
    </div>

    <script>
        function switchTab(type) {
            const loginForm = document.getElementById('form-login');
            const regForm = document.getElementById('form-register');
            const loginBtn = document.getElementById('tab-login');
            const regBtn = document.getElementById('tab-register');

            if (type === 'login') {
                loginForm.classList.remove('hidden');
                regForm.classList.add('hidden');
                loginBtn.className = "flex-1 py-2 text-sm font-bold rounded-lg transition-all duration-300 bg-amber-500 text-black shadow-lg";
                regBtn.className = "flex-1 py-2 text-sm font-bold text-slate-400 rounded-lg transition-all duration-300";
            } else {
                loginForm.classList.add('hidden');
                regForm.classList.remove('hidden');
                regBtn.className = "flex-1 py-2 text-sm font-bold rounded-lg transition-all duration-300 bg-amber-500 text-black shadow-lg";
                loginBtn.className = "flex-1 py-2 text-sm font-bold text-slate-400 rounded-lg transition-all duration-300";
            }
        }
    </script>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('../sw.js');
        }
    </script>

    <script>
        let deferredPrompt;
        const installBtn = document.getElementById('installBtn');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            // On affiche le bouton seulement si l'app peut être installée
            if (installBtn) installBtn.classList.remove('hidden');
        });

        window.triggerInstall = async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const {
                    outcome
                } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    console.log('Wari installé !');
                    if (installBtn) installBtn.classList.add('hidden');
                }
                deferredPrompt = null;
            } else {
                alert("Pour installer : cliquez sur les 3 points du navigateur puis 'Installer l'application'");
            }
        };
    </script>

    <script>
        // On attend que le tracker soit chargé, puis on envoie l'event
        window.addEventListener('load', function() {
            if (typeof DigiStats !== 'undefined') {
                DigiStats.track('login', {
                    user_role: 'client', // Optionnel: tu peux préciser le type d'utilisateur
                    method: 'email' // Optionnel: via Google, Facebook ou Email
                });
            }
        });
    </script>

    <script src="../assets/main.js?v=41"></script>
</body>

</html>