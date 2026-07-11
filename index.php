<?php
require_once __DIR__ . '/wari_monitoring.php';  // ← TOUJOURS EN PREMIER
// Configuration session 90 jours avant tout output
require 'config/session_config.php'; // Charge la config 90 jours
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
require 'config/db.php'; // <--- INDISPENSABLE : Pour que $pdo fonctionne !
require_once __DIR__ . '/classes/Academy.php';
require_once __DIR__ . '/classes/Vecu.php';

require_once __DIR__ . '/config/session_check.php';

$academy = new Academy($pdo);
$unfinishedCoursesCount = $academy->getUnfinishedCoursesCount($_SESSION['user_id']);

$vecu = new Vecu($pdo);
$unreadVecuCount = $vecu->getUnreadCount($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wari-Finance | Gestion Budget & Objectifs Financiers</title>
    <meta name="description" content="Avec Wari, chaque franc a un rôle. Planifie, contrôle et fais grandir ton argent directement depuis ton téléphone.">

    <meta name="keywords" content="Wari Finance, gestion budget, épargne, finance personnelle, Afrique, licence pro">
    <meta name="author" content="Digiroys">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Quicksand', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Open Graph (Facebook, WhatsApp, LinkedIn) -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Wari-Finance - Gère ton argent sans stress">
    <meta property="og:description" content="Budget, objectifs, conseils simples pour maîtriser tes finances au quotidien. Application gratuite.">
    <meta property="og:url" content="https://wari.digiroys.com/accueil/">
    <meta property="og:image" content="https://wari.digiroys.com/assets/wari_og_1.png">
    <meta property="og:locale" content="fr_FR">

    <link rel="icon" type="image/png" href="./assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="./assets/warifinance3d.png">

    <link rel="stylesheet" href="./assets/styles.css?v=133">

    <link rel="manifest" href="manifest.json">
    <meta id="metaThemeColor" name="theme-color" content="#000000">

    <script src="https://stats.digiroys.com/tracker.js" data-key="key_wari_789"></script>
    <script>
        <?php if (isset($_SESSION['user_email'])): ?>
            // On identifie l'utilisateur pour TOUTES ses actions sur le dashboard
            DigiStats.identify("<?= $_SESSION['user_email'] ?>");
        <?php endif; ?>
    </script>

    <script>
        // Initialisation immédiate du thème
        const savedTheme = localStorage.getItem('wari_theme') || 'dark';
        if (savedTheme === 'light') {
            document.documentElement.classList.add('light-mode');
            document.addEventListener('DOMContentLoaded', () => {
                document.body.classList.add('light-mode');
                const metaThemeColor = document.getElementById('metaThemeColor');
                if (metaThemeColor) metaThemeColor.setAttribute('content', '#f1f5f9');
            });
        }

        function toggleTheme() {
            const isLight = document.body.classList.toggle('light-mode');
            document.documentElement.classList.toggle('light-mode', isLight);
            localStorage.setItem('wari_theme', isLight ? 'light' : 'dark');
            const metaThemeColor = document.getElementById('metaThemeColor');
            if (metaThemeColor) {
                metaThemeColor.setAttribute('content', isLight ? '#f1f5f9' : '#000000');
            }
            updateThemeButton();
        }

        function updateThemeButton() {
            const isLight = document.documentElement.classList.contains('light-mode');
            const metaThemeColor = document.getElementById('metaThemeColor');
            if (metaThemeColor) {
                metaThemeColor.setAttribute('content', isLight ? '#f1f5f9' : '#000000');
            }
            const themeIcon = document.getElementById('themeIcon');
            const themeToggleBtn = document.getElementById('themeToggleBtn');
            const sunIcon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>`;
            const moonIcon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`;
            
            if (themeIcon) {
                themeIcon.innerHTML = isLight ? sunIcon : moonIcon;
                themeIcon.className = isLight ? "text-slate-800" : "text-slate-400";
            }
            if (themeToggleBtn) {
                themeToggleBtn.className = isLight 
                    ? "w-10 h-10 rounded-2xl flex items-center justify-center active:scale-95 transition-all duration-300 bg-slate-200 hover:bg-slate-300 border border-slate-300/50"
                    : "w-10 h-10 rounded-2xl flex items-center justify-center active:scale-95 transition-all duration-300 bg-white/5 hover:bg-white/10 border border-white/5";
            }

            // Mettre à jour les boutons dans les modals
            const modalIcons = document.querySelectorAll('.modalThemeIcon');
            modalIcons.forEach(icon => {
                icon.innerHTML = isLight ? sunIcon : moonIcon;
                icon.className = isLight ? "modalThemeIcon text-slate-800" : "modalThemeIcon text-slate-400";
            });

            const modalButtons = document.querySelectorAll('.modalThemeToggleBtn');
            modalButtons.forEach(btn => {
                btn.className = isLight
                    ? "modalThemeToggleBtn w-9 h-9 flex items-center justify-center rounded-xl active:scale-95 transition-all duration-300 bg-slate-200 hover:bg-slate-300 border border-slate-300/50"
                    : "modalThemeToggleBtn w-9 h-9 flex items-center justify-center rounded-xl active:scale-95 transition-all duration-300 bg-white/5 hover:bg-white/10 border border-white/5";
            });
        }

        document.addEventListener('DOMContentLoaded', updateThemeButton);
    </script>

</head>

<body class="p-3 pb-20">

    <div class="max-w-md mx-auto">
        <header class="flex items-center justify-between mb-4">
            <div class="flex flex-col">
                <h1 class="text-3xl font-black tracking-tighter text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-yellow-600">
                    WARI - Finance
                </h1>
                <p class="text-[8px] font-bold uppercase tracking-[0.3em] text-transparent bg-clip-text bg-gradient-to-r from-slate-400 to-slate-600">Discipline | Liberté | Suivis</p>
                <div class="flex items-center gap-2 mt-0.5">
                    <span id="liveClock" class="text-[9px] font-bold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-amber-400/80 to-yellow-600/60"></span>
                    <span id="offlineBadge" class="hidden text-[8px] font-black uppercase tracking-widest bg-red-500/20 text-red-400 px-1.5 py-0.5 rounded-sm border border-red-500/30">Hors Ligne ⚡</span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <!-- Bouton Guide -->
                <a href="guide/index.php" title="Guide d'utilisation"
                    class="w-10 h-10 rounded-2xl flex items-center justify-center active:scale-95 transition-all duration-300 bg-white/5 hover:bg-amber-500/10 border border-white/5 hover:border-amber-500/30">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-amber-500"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                </a>

                <!-- Bouton de bascule de thème -->
                <button id="themeToggleBtn" onclick="toggleTheme()" title="Changer le thème"
                    class="w-10 h-10 rounded-2xl flex items-center justify-center active:scale-95 transition-all duration-300 bg-white/5 hover:bg-white/10 border border-white/5">
                    <span id="themeIcon" class="text-slate-400"></span>
                </button>

                <!-- ✅ BOUTON DÉCONNEXION / SORTIR -->
                <a href="config/logout.php" title="Se déconnecter"
                    class="w-10 h-10 rounded-2xl flex items-center justify-center active:scale-95 transition-all duration-300 group bg-white/5 hover:bg-red-500/10 border border-white/5 hover:border-red-500/20 shadow-md">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" class="text-slate-400 group-hover:text-red-400 transition-colors" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17 16L21 12M21 12L17 8M21 12H9M13 16V17C13 18.1046 12.1046 19 11 19H5C3.89543 19 3 18.1046 3 17V7C3 5.89543 3.89543 5 5 5H11C12.1046 5 13 5.89543 13 7V8" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            </div>
        </header>

        <!-- Sélecteur de Portefeuille : Perso / Pro -->
        <div class="flex justify-center mb-4">
            <div class="bg-slate-900/80 p-0.5 rounded-2xl border border-white/5 flex gap-1 shadow-inner select-none">
                <button id="wallet-btn-perso" onclick="switchWallet('perso')" class="px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all duration-300 bg-gradient-to-r from-yellow-400 to-yellow-600 text-slate-950 shadow-md">
                    Personnel
                </button>
                <button id="wallet-btn-pro" onclick="switchWallet('pro')" class="px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all duration-300 text-slate-400 hover:text-white flex items-center gap-1.5">
                    Professionnel
                    <?php if (!isset($_SESSION['is_premium']) || !$_SESSION['is_premium']): ?>
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-amber-500"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    <?php endif; ?>
                </button>
            </div>
        </div>

        <!-- Jauge de Santé Financière -->
        <section id="gauge-section" class="glass-card p-3 mb-4">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="text-[11px] uppercase tracking-widest text-emerald-400 font-bold">Santé financière</h3>
                        <?php if (isset($_SESSION['is_premium']) && $_SESSION['is_premium']): ?>
                            <span class="text-[7.5px] font-black uppercase tracking-widest bg-amber-500/20 text-amber-400 border border-amber-500/30 px-1.5 py-0.5 rounded-full select-none animate-pulse">Pro</span>
                        <?php endif; ?>
                        <div id="radarStatusContainer" class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-slate-900/50 border border-white/5 cursor-pointer active:scale-95 transition-all" onclick="subscribeUserToPush(true)">
                            <div id="radarDot" class="w-1.5 h-1.5 rounded-full bg-slate-600"></div>
                            <span id="radarText" class="text-[7px] font-black uppercase tracking-widest text-slate-500">Radar OFF</span>
                        </div>
                    </div>
                </div>
                
                <!-- Bouton d'exportation PDF (Premium) -->
                <button onclick="exportFinancialReport()" class="flex items-center gap-1 px-2.5 py-1 rounded-xl bg-slate-900 hover:bg-slate-800 border border-white/5 hover:border-amber-500/30 text-slate-400 hover:text-white text-[9px] font-black uppercase tracking-wider transition-all duration-300 active:scale-95 shadow-md">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-amber-500">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Bilan
                </button>
            </div>

            

            <?php if (isset($_SESSION['is_premium']) && $_SESSION['is_premium']): ?>
            <!-- Tabs pour choisir le type de graphique -->
            <div class="flex justify-center gap-1.5 mb-3 bg-slate-900/60 p-1 rounded-xl border border-white/5">
                <button onclick="switchPremiumChart('trend')" id="btnChartTrend" class="flex-1 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-wider text-white bg-slate-800 transition-all select-none">
                    Evolution
                </button>
                <button onclick="switchPremiumChart('savings')" id="btnChartSavings" class="flex-1 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-wider text-slate-400 hover:text-white transition-all select-none">
                    Epargne %
                </button>
                <button onclick="switchPremiumChart('donut')" id="btnChartDonut" class="flex-1 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-wider text-slate-400 hover:text-white transition-all select-none">
                    Repartition
                </button>
            </div>
            <?php endif; ?>

            <!-- Zone du Graphique Évolution -->
            <div class="relative w-full h-[140px] p-1 flex items-center justify-center mb-2">
                <div id="chartLoader" class="absolute text-slate-500 text-[10px] italic">Chargement du graphique...</div>
                <svg id="trendChartSvg" class="w-full h-full opacity-0 transition-opacity duration-500" viewBox="0 0 400 140"></svg>
            </div>
            
            <!-- Légende du Graphique -->
            <div id="chartLegend" class="flex justify-center gap-4 select-none">
                <div class="flex items-center gap-1.5 text-[8.5px] font-bold text-slate-400 uppercase tracking-wider">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_6px_rgba(16,185,129,0.5)]"></span>
                    Revenus
                </div>
                <div class="flex items-center gap-1.5 text-[8.5px] font-bold text-slate-400 uppercase tracking-wider">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 shadow-[0_0_6px_rgba(239,68,68,0.5)]"></span>
                    Dépenses
                </div>
            </div>
        </section>

        <div id="bankPocketSection" class="grid grid-cols-2 gap-3 mb-4">
            <!-- BANQUE : Effet Blur pour se concentrer sur l'actif de croissance -->
            <div class="glass-card p-3 cursor-pointer active:scale-95 transition-all group" 
                 onclick="const el = this.querySelector('#bankAmount'); el.classList.toggle('blur-[6px]'); el.classList.toggle('opacity-30'); el.classList.toggle('opacity-100');">
                <div class="flex justify-between items-start mb-1">
                    <div class="flex items-center gap-1.5">
                        <p class="text-[8px] uppercase tracking-widest text-slate-500 font-black">Banque (Réserves)</p>
                        <button onclick="event.stopPropagation(); openHelpModal('coffre-fort')" class="w-3.5 h-3.5 rounded-full border border-slate-600 text-slate-500 flex items-center justify-center text-[7px] font-bold hover:bg-slate-800 hover:text-white transition-colors" title="C'est quoi ?">?</button>
                    </div>
                    <span class="opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </span>
                </div>
                <p id="bankAmount" class="text-lg font-black text-white blur-[6px] opacity-30 transition-all duration-500 select-none">0 F</p>
                <p class="text-[7px] text-slate-600 mt-1 uppercase tracking-wider leading-tight">Liberté de Sécurité</p>
            </div>

            <!-- POCHE : Reste en clair pour les dépenses quotidiennes -->
            <div class="glass-card p-3">
                <div class="flex items-center gap-1.5 mb-1">
                    <p class="text-[8px] uppercase tracking-widest text-slate-500 font-black">Poche (Dispo)</p>
                    <button onclick="openHelpModal('train-de-vie')" class="w-3.5 h-3.5 rounded-full border border-slate-600 text-slate-500 flex items-center justify-center text-[7px] font-bold hover:bg-slate-800 hover:text-white transition-colors" title="C'est quoi ?">?</button>
                </div>
                <p id="cashAmount" class="text-lg font-black text-emerald-400">0 F</p>
                <p class="text-[7px] text-slate-600 mt-1 uppercase tracking-wider leading-tight text-left">Dispo (Vie + Imprévus)</p>
            </div>
        </div>

        <!-- Section insertion du montant a repartire -->
        <div class="glass-card gold-border p-3 mb-4 shadow-2xl relative">
            <div class="flex justify-between items-center mb-3">
                <label class="block text-[11px] uppercase tracking-[0.2em] text-yellow-500 font-bold">Montant à répartir</label>
                <select id="currencySelector" onchange="render()" class="bg-slate-800 text-yellow-500 text-xs font-bold rounded px-1 py-1 outline-none">
                    <option value="F">CFA</option>
                    <option value="$">USD ($)</option>
                    <option value="€">EUR (€)</option>
                </select>
            </div>
            <div class="flex items-end border-b-2 border-slate-700 pb-2 focus-within:border-emerald-500 transition-colors">
                <!-- Champs cachés pour capturer et bloquer l'autofill Google/Samsung Pass -->
                <input type="text" style="display:none;" autocomplete="new-password">
                <input type="password" style="display:none;" autocomplete="new-password">
                
                <input type="number" id="mainAmount" name="amount" placeholder="0" onfocus="this.select()"
                    autocomplete="off" inputmode="numeric" pattern="[0-9]*" autocorrect="off" spellcheck="false"
                    class="bg-transparent text-4xl w-full font-extrabold outline-none text-white">
                <span id="currentSymbol" class="text-xl font-bold text-slate-500 ml-2">F</span>
            </div>
        </div>

        <!-- Section de contrôle de l'édition -->
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xs font-bold uppercase tracking-widest text-slate-500">Répartition</h3>
            
            <div class="flex items-center gap-2">
                <button id="lockBtn" onclick="toggleEditMode()"
                    class="flex items-center gap-1 px-3 py-1 rounded-full bg-slate-900 border border-slate-700 transition-all active:scale-95 shadow-lg">
                    <span class="text-[11px] font-black uppercase tracking-[0.1em] text-slate-400">Lecture</span>
                </button>
                
                <button id="focusModeBtn" onclick="toggleFocusMode()" 
                    class="flex items-center gap-1 px-3 py-1 rounded-full bg-slate-800 border border-slate-700 transition-all active:scale-95 shadow-lg">
                    <span id="focusIcon"></span>
                    <span id="focusText" class="text-slate-400 text-[11px] font-bold uppercase tracking-wider">FOCUS</span>
                </button>
            </div>
        </div>

        <!-- Conteneur des catégories -->
        <div id="categoryContainer" class="grid grid-cols-2 gap-3 mb-6">
        </div>

        <!-- Section Juge barre de % -->
        <div id="statusIndicator" class="mt-4 flex items-center justify-center space-x-2 p-3 rounded-2xl transition-all duration-500">
            <div id="statusIcon"></div>
            <span id="statusText" class="font-bold text-sm uppercase tracking-wider"></span>
        </div>

        <!-- Section Versement a la banque (capital investir)-->
        <div id="projectVault" class="mt-4 glass-card p-3 relative overflow-hidden group border-none shadow-2xl">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/5 rounded-full blur-3xl group-hover:bg-emerald-500/15 transition-all duration-1000"></div>

            <div class="flex items-center justify-between mb-3 relative z-10">
                <div>
                    <h3 class="text-[10px] uppercase tracking-[0.1em] text-emerald-400 font-black leading-none">Liberté d'Action</h3>
                    <p class="text-[9px] text-slate-500 font-medium mt-0.5">Plantée à chaque répartition</p>
                </div>
                <div class="text-right">
                    <div class="flex items-baseline justify-end gap-1 leading-none">
                        <span id="totalProjectSaved" class="text-2xl font-black text-white tracking-tighter">0</span>
                    </div>
                    <div class="flex items-center justify-end gap-1 opacity-90">
                        <span class="text-[7px] uppercase tracking-[0.1em] text-slate-500 font-black">Liberté :</span>
                        <span id="totalGlobalAmount" class="text-[9px] font-black text-emerald-400">0</span>
                    </div>
                </div>
            </div>

            <div class="relative mb-3">
                <div class="flex justify-between items-center mb-1 px-0.5">
                    <span id="vaultGoalAmountDisplay" class="text-[8px] font-bold text-emerald-500/80 tracking-widest uppercase">Objectif: --</span>
                </div>
                <div class="relative w-full h-1.5 bg-slate-900/60 rounded-full p-[1px] border border-white/5 shadow-inner">
                    <div id="vaultProgress" class="h-full bg-gradient-to-r from-emerald-600 via-emerald-400 to-teal-300 rounded-full transition-all duration-1000" style="width: 0%"></div>
                </div>
            </div>

            <div class="bg-slate-900/40 px-3 py-1.5 rounded-xl border border-slate-800/50 flex items-center justify-between gap-3">
                <div class="flex flex-col">
                    <p class="text-[7px] uppercase tracking-widest text-slate-500 font-bold">Cible</p>
                    <p id="vaultGoalLabel" class="text-[10px] text-white font-black truncate max-w-[100px]">Définir</p>
                </div>
                <div class="flex gap-2">
                    <button onclick="openGoalModal()" class="text-emerald-500 active:scale-90 transition-transform">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </button>
                    <button id="deleteGoalBtn" onclick="deleteGoal()" class="text-red-400/70 text-[10px] hidden">✕</button>
                </div>
            </div>

            <div class="mt-3 pt-2 border-t border-slate-800/40">
                <div class="flex justify-between items-center mb-1">
                    <p class="text-[7px] uppercase tracking-[0.2em] text-slate-500 font-bold">Historique</p>
                    <button onclick="window.toggleVaultHistory()" id="toggleHistBtn" class="text-[8px] text-slate-400 font-black uppercase tracking-widest">Détails</button>
                </div>
                <div id="vaultHistory" class="max-h-8 overflow-hidden transition-all duration-500">
                    <p class="text-[8px] text-slate-600 italic text-center py-1">Aucun mouvement</p>
                </div>
            </div>
        </div>

        <script>
            let isHistoryExpanded = false;

            window.toggleVaultHistory = function() {
                const container = document.getElementById("vaultHistory");
                isHistoryExpanded = !isHistoryExpanded;

                if (isHistoryExpanded) {
                    container.style.maxHeight = "400px"; // On déplie
                    container.style.overflowY = "auto";
                } else {
                    container.style.maxHeight = "96px"; // On réduit (environ 3 lignes)
                    container.style.overflowY = "hidden";
                    container.scrollTop = 0;
                }
            };
        </script>

        <?php if (isset($_SESSION['is_premium']) && $_SESSION['is_premium']): ?>
        <!-- Section Défis d'Épargne -->
        <div id="challengesSection" class="mt-4 glass-card p-3 shadow-2xl relative">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-[11px] uppercase tracking-[0.1em] text-indigo-400 font-bold">Défis d'Épargne</h3>
                <span id="completedChallengesCountDisplay" class="text-[9px] font-black uppercase tracking-widest bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-2.5 py-0.5 rounded-full select-none">
                    0 réussi
                </span>
            </div>
            <div id="challengesContent" class="space-y-4">
                <!-- Rendu dynamique par JS -->
                <p class="text-slate-500 text-[11px] italic text-center">Chargement des défis...</p>
            </div>
        </div>
        <?php else: ?>
        <!-- Version Lock Premium pour les défis -->
        <div class="mt-4 glass-card p-3 shadow-2xl relative overflow-hidden border border-indigo-500/20">
            <div class="absolute -right-10 -top-10 w-24 h-24 bg-indigo-500/10 rounded-full blur-2xl"></div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-[11px] uppercase tracking-[0.1em] text-slate-400 font-bold">Défis d'Épargne</h3>
                <span class="text-[7.5px] font-black uppercase tracking-widest bg-amber-500/20 text-amber-400 border border-amber-500/30 px-1.5 py-0.5 rounded-full">PREMIUM</span>
            </div>
            <div class="text-center py-4 relative z-10">
                <svg class="mx-auto text-slate-500 mb-2" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <p class="text-white font-bold text-xs mb-1">Défis d'Épargne Interactifs</p>
                <p class="text-slate-400 text-[9px] max-w-[220px] mx-auto mb-3">Activez Wari Premium pour relever des défis ludiques d'épargne et booster votre discipline financière.</p>
                <a href="https://wari.digiroys.com/paid/index.php" class="inline-block text-[9px] bg-amber-500 text-slate-950 px-4 py-1.5 rounded-xl font-black uppercase tracking-wider hover:bg-amber-400 transition-all">S'abonner maintenant</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Section Simulateur d'Investissement Premium -->
        <?php if (isset($_SESSION['is_premium']) && $_SESSION['is_premium']): ?>
        <div id="simulatorSection" class="mt-4 glass-card p-3 shadow-2xl relative border border-amber-500/10">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-[11px] uppercase tracking-[0.1em] text-amber-400 font-bold flex items-center gap-1.5">
                    <i class="ri-line-chart-line"></i> Simulateur d'Investissement UEMOA
                </h3>
                <span class="text-[8px] font-black uppercase tracking-widest bg-amber-500/20 text-amber-300 border border-amber-500/30 px-2.5 py-0.5 rounded-full select-none">PRO</span>
            </div>
            <p class="text-slate-400 text-[9.5px] leading-tight mb-3">
                Calculez l'évolution de votre épargne placée sur les supports réels d'Afrique de l'Ouest (Bons du Trésor, DAT, Bourse BRVM) grâce aux intérêts composés.
            </p>
            <button onclick="openSimulatorModal()" class="w-full py-2.5 bg-amber-500/20 text-amber-400 border border-amber-500/30 rounded-xl font-extrabold text-[10px] uppercase tracking-wider hover:bg-amber-500/30 transition-all flex items-center justify-center gap-1.5">
                Lancer une simulation
            </button>
        </div>
        <?php else: ?>
        <!-- Version Lock Premium pour le simulateur -->
        <div class="mt-4 glass-card p-3 shadow-2xl relative overflow-hidden border border-amber-500/10">
            <div class="absolute -right-10 -top-10 w-24 h-24 bg-amber-500/10 rounded-full blur-2xl"></div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-[11px] uppercase tracking-[0.1em] text-slate-400 font-bold flex items-center gap-1.5">
                    <i class="ri-line-chart-line"></i> Simulateur d'Investissement
                </h3>
                <span class="text-[7.5px] font-black uppercase tracking-widest bg-amber-500/20 text-amber-400 border border-amber-500/30 px-1.5 py-0.5 rounded-full">PREMIUM</span>
            </div>
            <div class="text-center py-4 relative z-10">
                <svg class="mx-auto text-slate-500 mb-2" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <p class="text-white font-bold text-xs mb-1">Simulations financières UEMOA</p>
                <p class="text-slate-400 text-[9px] max-w-[220px] mx-auto mb-3">Estimez vos intérêts composés sur les obligations d'État, les DAT et l'épargne Mobile Money locale.</p>
                <a href="https://wari.digiroys.com/paid/index.php" class="inline-block text-[9px] bg-amber-500 text-slate-950 px-4 py-1.5 rounded-xl font-black uppercase tracking-wider hover:bg-amber-400 transition-all">Débloquer le Simulateur</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Dette Section -->
        <div id="debtSection" class="mt-4 glass-card p-3 shadow-2xl relative">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-1.5">
                    <h3 class="text-[11px] uppercase tracking-[0.1em] text-red-400 font-bold">Carnet de Dettes</h3>
                    <button onclick="openHelpModal('dette')" class="w-4 h-4 rounded-full border border-red-500/50 text-red-400 flex items-center justify-center text-[9px] font-bold hover:bg-red-500/20 transition-colors" title="C'est quoi ?">?</button>
                </div>
                <div class="flex items-center gap-1.5">
                    <?php if (isset($_SESSION['is_premium']) && $_SESSION['is_premium']): ?>
                    <button onclick="openSnowballModal()" class="text-[11px] bg-amber-500/20 text-amber-400 px-2 py-1 rounded-full border border-amber-500/30 font-bold hover:bg-amber-500/35 active:scale-95 transition-all flex items-center gap-1">
                        Plan Pro
                    </button>
                    <?php endif; ?>
                    <button onclick="openDebtModal()" class="text-[11px] bg-red-500/20 text-red-400 px-2 py-1 rounded-full border border-red-500/30 font-bold hover:bg-red-500/40 transition-all">
                        + Add
                    </button>
                </div>
            </div>  

            <div id="debtList" class="space-y-3">
                <p class="text-slate-500 text-[11px] italic text-center">Aucune dette ou créance en cours.</p>
            </div>
        </div>

        <!-- Modal pour ajouter une dette -->
        <div id="debtModal" class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm hidden items-center justify-center p-4 z-[110]">
            <div class="glass-card w-full max-w-sm p-4 border border-slate-700 shadow-2xl">
                <h3 id="debtModalTitle" class="text-red-400 font-bold mb-4 uppercase tracking-widest text-sm">Ajouter une note</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1">TYPE</label>
                        <select id="debtType" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white outline-none">
                            <option value="loan">On me doit (Créance)</option>
                            <option value="debt">Je dois (Dette)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1">NOM DE LA PERSONNE</label>
                        <input type="text" id="debtPerson" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white outline-none" placeholder="Ex: Moussa">
                    </div>
                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1 uppercase">
                            Montant (<span class="currencyLabel">F</span>)
                        </label>
                        <input type="number" id="debtAmount" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white outline-none" placeholder="0">
                    </div>

                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1 uppercase">Date d'échéance (optionnel)</label>
                        <input type="date" id="debtDueDate"
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white outline-none">
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button onclick="closeDebtModal()" class="flex-1 py-3 bg-slate-800 text-slate-400 rounded-xl font-bold text-sm">Annuler</button>
                        <button onclick="submitDebt()" class="flex-1 py-3 bg-red-600 text-white rounded-xl font-bold text-sm">Enregistrer</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de remboursement -->
        <div id="payModal" class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm hidden items-center justify-center p-4 z-[120]">
            <div class="glass-card w-full max-w-sm p-4 border border-slate-700 shadow-2xl">
                <h3 class="text-emerald-400 font-bold mb-1 uppercase text-sm">Remboursement</h3>
                <p id="payModalTarget" class="text-slate-400 text-[11px] mb-4"></p>

                <input type="hidden" id="payDebtId">
                <input type="hidden" id="payDebtType">

                <div class="space-y-4">
                    <div>
                        <label id="paySourceEnvelopeLabel" class="block text-[11px] text-slate-400 mb-1 uppercase">Prélever depuis l'enveloppe</label>
                        <select id="paySourceEnvelope" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white outline-none focus:border-emerald-500 mb-4">
                            <!-- Rempli en JS -->
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1 uppercase">
                            Montant du versement (<span class="currencyLabel">F</span>)
                        </label>
                        <input type="number" id="payPartAmount" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white outline-none focus:border-emerald-500" placeholder="0">
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button onclick="closePayModal()" class="flex-1 py-3 bg-slate-800 text-slate-400 rounded-xl font-bold text-sm">Annuler</button>
                        <button onclick="submitPartialPay()" class="flex-1 py-3 bg-emerald-600 text-white rounded-xl font-bold text-sm">Confirmer</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Premium Planificateur Anti-Dette (Boule de Neige) -->
        <div id="debtSnowballModal" class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm hidden items-center justify-center p-4 z-[120]">
            <div class="glass-card w-full max-w-md p-4 border border-slate-700 shadow-2xl max-h-[85vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-700/60 pb-3 mb-3">
                    <h3 class="text-amber-400 font-bold uppercase tracking-wider text-sm flex items-center gap-1.5">
                        <i class="ri-rocket-2-line"></i> Planificateur Anti-Dette Pro
                    </h3>
                    <button onclick="closeSnowballModal()" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
                </div>

                <!-- Bilan Net -->
                <div class="grid grid-cols-3 gap-2 mb-4 bg-slate-800/40 p-2.5 rounded-xl border border-slate-700/50">
                    <div class="text-center">
                        <p class="text-[9px] uppercase tracking-wider text-slate-500 font-bold">Mes Dettes</p>
                        <p id="sbTotalDebts" class="text-xs font-black text-red-400">0 F</p>
                    </div>
                    <div class="text-center border-x border-slate-700/60">
                        <p class="text-[9px] uppercase tracking-wider text-slate-500 font-bold">Mes Créances</p>
                        <p id="sbTotalLoans" class="text-xs font-black text-emerald-400">0 F</p>
                    </div>
                    <div class="text-center">
                        <p class="text-[9px] uppercase tracking-wider text-slate-500 font-bold">Situation Net</p>
                        <p id="sbNetSituation" class="text-xs font-black text-white">0 F</p>
                    </div>
                </div>

                <!-- Entrée de mensualité -->
                <div class="mb-4">
                    <label class="block text-[10px] uppercase tracking-wider text-slate-400 mb-1 font-bold">
                        Capacité de remboursement mensuelle (<span class="currencyLabel">F</span>)
                    </label>
                    <input type="number" id="sbMonthlyCap" oninput="calculateSnowball()"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl p-2.5 text-white outline-none focus:border-amber-500 font-bold"
                        placeholder="Ex: 20000" value="20000">
                    <p class="text-[9px] text-slate-500 mt-1.5 italic leading-tight">
                        La méthode <b>Boule de Neige</b> trie tes dettes de la plus petite à la plus grande pour les éliminer une par une en reportant la force de remboursement.
                    </p>
                </div>

                <!-- Contenu Dynamique -->
                <div class="space-y-4">
                    <!-- Feuille de route -->
                    <div>
                        <h4 class="text-[10px] uppercase tracking-wider text-amber-500 font-bold mb-2 flex items-center gap-1">
                            <i class="ri-list-ordered"></i> Ordre de Libération (Dettes)
                        </h4>
                        <div id="sbDebtTimeline" class="space-y-2 max-h-[220px] overflow-y-auto pr-1">
                            <!-- Rendu dynamique JS -->
                        </div>
                    </div>

                    <!-- Créances actives -->
                    <div>
                        <h4 class="text-[10px] uppercase tracking-wider text-emerald-400 font-bold mb-2 flex items-center gap-1">
                            <i class="ri-hand-coin-line"></i> Créances à encaisser (Opportunités)
                        </h4>
                        <div id="sbLoanTimeline" class="space-y-2 max-h-[120px] overflow-y-auto pr-1">
                            <!-- Rendu dynamique JS -->
                        </div>
                    </div>
                </div>

                <!-- Bilan final / Date de liberté -->
                <div class="mt-4 pt-3 border-t border-slate-700/60 text-center">
                    <p class="text-[10px] text-slate-400">Libération totale estimée :</p>
                    <p id="sbFinalDate" class="text-lg font-black text-amber-400 uppercase tracking-widest mt-1">DANS X MOIS</p>
                </div>
            </div>
        </div>

        <!-- Modal Premium Simulateur d'Investissement UEMOA -->
        <div id="simulatorModal" onclick="closeSimulatorModal()" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-end md:items-center justify-center z-[120]">
            <div onclick="event.stopPropagation()" class="glass-card w-full max-w-2xl h-full md:h-[85vh] md:max-h-[750px] p-4 pt-safe md:p-6 border-t border-x md:border border-slate-800 rounded-none md:rounded-[2rem] shadow-2xl flex flex-col animate-slide-up" style="scrollbar-width: none; -ms-overflow-style: none;">
                <style>
                    #simulatorModal .glass-card::-webkit-scrollbar {
                        display: none;
                    }
                </style>
                <div class="flex items-center justify-between border-b border-slate-700/60 pb-3 mb-3 shrink-0">
                    <h3 class="text-amber-400 font-bold uppercase tracking-wider text-sm flex items-center gap-1.5">
                        <i class="ri-calculator-line"></i> Simulateur d'Investissement UEMOA
                    </h3>
                    <button onclick="closeSimulatorModal()" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
                </div>

                <div class="space-y-4 overflow-y-auto flex-1 pr-1 pb-28 custom-scrollbar" style="scrollbar-width: none; -ms-overflow-style: none;">
                    <style>
                        #simulatorModal .space-y-4::-webkit-scrollbar {
                            display: none;
                        }
                    </style>
                    <!-- Sélection du Placement -->
                    <div>
                        <label class="block text-[10px] uppercase tracking-wider text-slate-400 mb-1 font-bold">Où veux-tu placer ton argent ? (Placements courants)</label>
                        <select id="simPlacementType" onchange="onSimulatorPlacementChange()" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-2.5 text-white outline-none focus:border-amber-500 font-medium">
                            <option value="momo" data-rate="3.5">Épargne Mobile Money Spéciale (Bonus de 3.5% par an)</option>
                            <option value="dat" data-rate="5.5">Coffre bloqué en banque/microfinance (Bonus de 5.5% par an)</option>
                            <option value="bonds" data-rate="6.25" selected>Prêter de l'argent à l'État (Bonus de 6.25% par an)</option>
                            <option value="brvm" data-rate="8.0">Achat de parts d'entreprises - BRVM (Bonus moyen de 8.0% par an)</option>
                        </select>
                    </div>

                    <!-- Ligne Montant & Taux d'intérêt -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] uppercase tracking-wider text-slate-400 mb-1 font-bold">Somme que tu mets chaque mois (<span class="currencyLabel">F</span>)</label>
                            <input type="number" id="simMonthlyAmount" oninput="runSimulation()"
                                class="w-full bg-slate-800 border border-slate-700 rounded-xl p-2.5 text-white outline-none focus:border-amber-500 font-bold text-center"
                                placeholder="Ex: 25000" value="20000">
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase tracking-wider text-slate-400 mb-1 font-bold">Pourcentage de bonus par an (%)</label>
                            <input type="number" step="0.05" id="simAnnualRate" oninput="runSimulation()"
                                class="w-full bg-slate-800 border border-slate-700 rounded-xl p-2.5 text-white outline-none focus:border-amber-500 font-bold text-center text-amber-400"
                                placeholder="Ex: 6.25" value="6.25">
                        </div>
                    </div>

                    <!-- Durée -->
                    <div>
                        <label class="block text-[10px] uppercase tracking-wider text-slate-400 mb-1 font-bold">Pendant combien d'années (de 1 à 10 ans)</label>
                        <div class="flex items-center gap-2">
                            <input type="range" id="simYearsRange" min="1" max="10" value="5" oninput="document.getElementById('simYearsText').innerText = this.value + ' an(s)'; runSimulation();" class="flex-1 accent-amber-500">
                            <span id="simYearsText" class="w-16 text-center font-bold text-xs text-white bg-slate-800 border border-slate-700 py-1.5 px-2.5 rounded-lg select-none">5 an(s)</span>
                        </div>
                    </div>

                    <!-- Enveloppes de Résultats -->
                    <div class="grid grid-cols-3 gap-2 bg-slate-800/40 p-2.5 rounded-xl border border-slate-700/50">
                        <div class="text-center">
                            <p class="text-[8.5px] uppercase tracking-wider text-slate-500 font-bold">Ton argent versé</p>
                            <p id="simTotalDeposits" class="text-xs font-black text-white">0 F</p>
                        </div>
                        <div class="text-center border-x border-slate-700/60">
                            <p class="text-[8.5px] uppercase tracking-wider text-slate-500 font-bold">Cadeau de la banque (Intérêts)</p>
                            <p id="simTotalInterest" class="text-xs font-black text-emerald-400">+0 F</p>
                        </div>
                        <div class="text-center">
                            <p class="text-[8.5px] uppercase tracking-wider text-slate-500 font-bold">Somme finale</p>
                            <p id="simTotalFinal" class="text-xs font-black text-amber-400">0 F</p>
                        </div>
                    </div>

                    <!-- Graphique en colonnes stylé nativement -->
                    <div>
                        <h4 class="text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-2.5 flex items-center gap-1">
                            <i class="ri-bar-chart-fill"></i> Évolution de ton trésor d'année en année
                        </h4>
                        <!-- Graphique -->
                        <div class="bg-slate-900/50 border border-slate-800 p-3 rounded-2xl">
                            <div id="simChartContainer" class="flex items-end justify-between h-36 gap-1 px-1">
                                <!-- Généré dynamiquement en JS (barres verticales) -->
                            </div>
                        </div>
                        <!-- Légende -->
                        <div class="flex justify-center gap-4 mt-2 text-[9px] font-bold text-slate-400">
                            <div class="flex items-center gap-1">
                                <span class="w-2.5 h-2.5 bg-slate-700 rounded-sm"></span> Ton propre argent
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="w-2.5 h-2.5 bg-emerald-500 rounded-sm"></span> Cadeau de la banque
                            </div>
                        </div>
                    </div>

                    <!-- Message d'éducation financière -->
                    <div class="bg-amber-950/10 border border-amber-900/20 p-2.5 rounded-xl text-[9px] text-amber-400/90 leading-relaxed italic">
                        <i class="ri-information-line"></i> Ce calcul te montre comment ton argent grandit si tu mets de côté la même somme chaque mois sans y toucher. Le bonus s'ajoute chaque année sur l'ancien montant.
                    </div>
                </div>
            </div>
        </div>

        <!-- Barre de Navigation Fixe en Bas -->
        <nav class="fixed bottom-0 left-0 right-0 z-[120] bg-[#0B141A]/95 backdrop-blur-lg shadow-[0_-10px_30px_rgba(0,0,0,0.6)] py-0 px-4 pb-safe">
            <div class="max-w-md mx-auto flex items-center justify-between relative">
                
                <!-- 1. Academy -->
                <div class="relative flex-1 flex justify-center">
                    <div id="license-message" 
                        class="absolute bottom-full mb-3 w-44 p-3 bg-slate-950 border border-amber-500/30 rounded-xl shadow-2xl opacity-0 translate-y-2 transition-all duration-500 ease-out z-50 pointer-events-none text-center">
                        <p class="text-[10px] font-semibold text-slate-200 leading-tight">
                           <span class="text-amber-500 font-bold uppercase tracking-wider">Exclusivité</span><br>
                            <?= $unfinishedCoursesCount > 0 ? "Tu as <b>$unfinishedCoursesCount</b> cours en attente !" : "Boostez votre éducation financière." ?>
                        </p>
                        <div class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-slate-950 border-r border-b border-amber-500/30 rotate-45"></div>
                    </div>

                    <a href="https://wari.digiroys.com/academy/" onclick="trackLicenseBuy()" title="Academy"
                        class="w-10 h-10 rounded-xl flex flex-col items-center justify-center text-slate-400 hover:text-indigo-400 active:scale-95 transition-all">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1.5 9 12 3l10.5 6L12 15 1.5 9Z"></path>
                            <path d="M5.25 11.25v6L12 21l6.75-3.75v-6"></path>
                            <path d="M22.5 17.25V9"></path>
                            <path d="M12 15v6"></path>
                        </svg>
                        <span class="text-[8px] font-bold uppercase tracking-widest mt-0.5">Academy</span>
                        <?php if ($unfinishedCoursesCount > 0): ?>
                            <span class="absolute top-0 right-4 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[8px] font-black text-white ring-2 ring-slate-950 animate-pulse">
                                <?= $unfinishedCoursesCount ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>

                <!-- 2. Mettre à jour (Sync) -->
                <div class="flex-1 flex justify-center">
                    <button onclick="saveBudget()" title="Mettre à jour"
                        class="w-10 h-10 rounded-xl flex flex-col items-center justify-center text-slate-400 hover:text-emerald-400 active:scale-95 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span class="text-[8px] font-bold uppercase tracking-widest mt-0.5">Sync</span>
                    </button>
                </div>

                <!-- 3. Bouton Ajout Dépense (+) au centre -->
                <div class="flex-1 flex justify-center -translate-y-4">
                    <button onclick="openExpenseModal()" title="Ajouter Dépense"
                        class="w-12 h-12 rounded-full bg-gradient-to-br from-yellow-400 to-amber-600 text-black active:scale-95 hover:scale-105 transition-all flex items-center justify-center font-bold shadow-lg shadow-amber-500/20 border-4 border-[#0B141A]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                    </button>
                </div>

                <!-- 4. Historique -->
                <div class="flex-1 flex justify-center">
                    <button onclick="openHistoryModal()" title="Historique"
                        class="w-10 h-10 rounded-xl flex flex-col items-center justify-center text-slate-400 hover:text-amber-400 active:scale-95 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-[8px] font-bold uppercase tracking-widest mt-0.5">Historique</span>
                    </button>
                </div>

                <!-- 5. Vécu -->
                <div class="relative flex-1 flex justify-center">
                    <a href="https://wari.digiroys.com/vecu/" title="Vécu"
                        class="w-10 h-10 rounded-xl flex flex-col items-center justify-center text-slate-400 hover:text-cyan-400 active:scale-95 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="m14.728 22.609-2.66-5.379-2.105-2.69a3.414 3.414 0 0 1-.475-1.737V6.75h.735a1.885 1.885 0 0 1 1.886 1.885v8.595"></path>
                            <path d="M5.996 13.737v-3.493S7.743 6.75 9.49 6.75"></path>
                            <path d="m17.348 12.863-3.098-2.035"></path>
                            <path d="m7.994 22.423 2.507-3.673"></path>
                            <path d="M12.108 5a1.747 1.747 0 1 0 0-3.492 1.747 1.747 0 0 0 0 3.493Z"></path>
                        </svg>
                        <span class="text-[8px] font-bold uppercase tracking-widest mt-0.5">Vécu</span>
                        <?php if ($unreadVecuCount > 0): ?>
                            <span class="absolute top-0 right-4 flex h-4 w-4 items-center justify-center rounded-full bg-emerald-600 text-[8px] font-black text-white ring-2 ring-slate-950 animate-bounce">
                                <?= $unreadVecuCount ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </nav>

        <script>
            window.addEventListener('load', () => {
                const msgBulle = document.getElementById('license-message');
                const lastDisplay = localStorage.getItem('wari_academy_nudge');
                const now = new Date().getTime();
                
                // Délai de 21 jours (21 jours * 24h * 60m * 60s * 1000ms)
                const delay = 21 * 24 * 60 * 60 * 1000;

                if (!lastDisplay || (now - parseInt(lastDisplay)) > delay) {
                    setTimeout(() => {
                        if (msgBulle) {
                            // Apparition
                            msgBulle.classList.remove('opacity-0', 'translate-y-2');
                            msgBulle.classList.add('opacity-100', 'translate-y-0');
                            
                            // On enregistre l'affichage
                            localStorage.setItem('wari_academy_nudge', now.toString());

                            // Disparition après 5 secondes (lecture rapide)
                            setTimeout(() => closeLicenseMsg(), 5000);
                        }
                    }, 4000); 
                }
            });

            function closeLicenseMsg() {
                const msgBulle = document.getElementById('license-message');
                if (msgBulle) {
                    msgBulle.classList.replace('opacity-100', 'opacity-0');
                    msgBulle.classList.add('translate-y-2');
                }
            }

            function trackLicenseBuy() {
                closeLicenseMsg();
                if (window.DigiStats && typeof window.DigiStats.track === 'function') {
                    window.DigiStats.track('click_academy_access', { platform: 'web' });
                }
            }
        </script>

        <!-- Bouton Installation PWA -->
        <div id="installBtn" onclick="triggerInstall()" class="hidden mt-6 group cursor-pointer">
            <div class="glass border-amber-500/20 bg-amber-500/5 p-4 rounded-2xl flex items-center justify-between hover:bg-amber-500/10 transition-all active:scale-95 border border-dashed">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-amber-500 rounded-xl flex items-center justify-center text-black shadow-lg shadow-amber-500/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-[11px] uppercase tracking-widest text-amber-500 font-black">Expérience Mobile</h4>
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
    </div>

    <!-- MODAL OBJECTIF -->
    <div id="goalModal" class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm hidden items-center justify-center p-4 z-[130]">
        <div class="glass-card w-full max-w-sm p-4 border border-slate-700 shadow-2xl">
            <h3 class="text-emerald-400 font-bold uppercase tracking-widest text-sm mb-4">Définir un objectif</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1 uppercase">Nom de l'objectif</label>
                    <input type="text" id="goalLabel" placeholder="Ex: MacBook Pro, Terrain..."
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1 uppercase">Montant cible</label>
                    <input type="number" id="goalAmount" placeholder="0"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white outline-none focus:border-emerald-500">
                </div>
                <div class="flex gap-2 pt-2">
                    <button onclick="closeGoalModal()" class="flex-1 py-3 bg-slate-800 text-slate-400 rounded-xl font-bold text-sm">Annuler</button>
                    <button onclick="saveGoal()" class="flex-1 py-3 bg-emerald-600 text-white rounded-xl font-bold text-sm">Valider</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ MODAL HISTORIQUE -->
    <div id="historyModal" onclick="closeHistoryModal()" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-end md:items-center justify-center z-[130]">
        <div onclick="event.stopPropagation()" class="glass-card w-full max-w-md h-full md:h-[85vh] md:max-h-[750px] p-2 pt-safe md:p-6 border-t border-x md:border border-slate-800 rounded-none md:rounded-[2rem] shadow-2xl flex flex-col animate-slide-up">

            <div class="flex items-center justify-between mb-4 shrink-0">
                <h3 class="text-amber-400 font-bold uppercase tracking-widest text-xs">Historique</h3>

                <div class="flex items-center gap-2">
                    <select onchange="loadMonthlyHistory(this.value)"
                        class="bg-slate-800 text-slate-300 text-[11px] border border-slate-700 rounded-lg px-2 py-1">
                        <option value="3">3 mois</option>
                        <option value="6" selected>6 mois</option>
                        <option value="12">12 mois</option>
                    </select>

                    <button onclick="toggleTheme()" class="modalThemeToggleBtn w-9 h-9 flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover:text-white active:scale-95 transition-all" title="Changer le thème">
                        <span class="modalThemeIcon text-slate-400"></span>
                    </button>

                    <button onclick="closeHistoryModal()" class="w-9 h-9 flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover:text-white active:scale-95 transition-all" title="Fermer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div id="historyContent" class="space-y-4 overflow-y-auto pb-28 custom-scrollbar flex-1 pr-1">
                <p class="text-slate-500 text-[12px] italic text-center py-4">Chargement...</p>
            </div>
        </div>
    </div>

    <!-- Bulle de prévisualisation flottante du Coach AI -->
    <div id="coachBubble" class="fixed bottom-[136px] right-6 z-50 max-w-[210px] bg-slate-950/95 backdrop-blur-md border border-amber-500/30 text-amber-50 px-3 py-2 rounded-2xl rounded-br-none shadow-2xl shadow-black/80 text-[11px] font-semibold leading-relaxed flex items-start gap-2 opacity-0 pointer-events-none transition-all duration-500 translate-y-2 select-none">
        <span class="flex-1">Salut, je suis ton coach financier. Et si on discutait un peu ?</span>
        <button onclick="dismissCoachBubble(event)" class="text-slate-400 hover:text-white p-0.5 active:scale-95 transition-colors font-bold text-xs leading-none">✕</button>
    </div>

    <!-- Bouton Coach AI → page dédiée /coach -->
    <a id="coachButton" href="/coach/"
        class="fixed bottom-20 right-6 w-12 h-12 bg-gradient-to-br from-amber-400 via-amber-500 to-orange-600 rounded-full flex items-center justify-center text-slate-950 active:scale-95 hover:scale-110 transition-all duration-300 z-50 group border border-white/10 touch-none">

        <!-- Custom coach/AI SVG Icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-950" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 9h.01M16 9h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>

        <div class="absolute inset-0 rounded-full bg-gradient-to-t from-transparent to-white/25 pointer-events-none"></div>
    </a>

    <div id="expenseModal" class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm hidden items-center justify-center p-4 z-[100]">
        <div class="glass-card w-full max-w-sm p-4 border border-slate-700 shadow-2xl">
            <h3 class="text-yellow-500 font-bold mb-4 uppercase tracking-widest text-sm">⚡ Dépense Flash</h3>

            <div class="space-y-5 p-1">
                <div>
                    <label class="block text-[11px] uppercase tracking-[0.15em] text-slate-500 mb-2 font-black">
                        Montant à déduire (<span class="currencyLabel">F</span>)
                    </label>
                    <input type="number" id="expAmount"
                        class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl p-4 text-white text-xl font-black outline-none focus:border-emerald-500/50 focus:ring-4 focus:ring-emerald-500/10 transition-all placeholder:text-slate-700"
                        placeholder="0">
                </div>

                <div>
                    <label class="block text-[11px] uppercase tracking-[0.15em] text-slate-500 mb-2 font-black">
                        Motif de la dépense
                    </label>
                    <input type="text" id="expNote"
                        class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl p-4 text-white text-sm outline-none focus:border-emerald-500/50 focus:ring-4 focus:ring-emerald-500/10 transition-all placeholder:text-slate-700"
                        placeholder="Ex: Achat Ordinateur, Loyer, Resto...">
                </div>

                <div>
                    <label class="block text-[11px] uppercase tracking-[0.15em] text-slate-500 mb-2 font-black">
                        Catégorie cible
                    </label>
                    <select id="expCategory"
                        class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl p-4 text-white text-sm outline-none focus:border-emerald-500/50 appearance-none cursor-pointer tracking-wide">
                    </select>
                </div>

                <div class="flex gap-3 pt-4">
                    <button onclick="closeExpenseModal()"
                        class="flex-1 py-4 bg-slate-800/50 hover:bg-slate-800 text-slate-400 rounded-2xl font-black text-[11px] uppercase tracking-widest transition-all">
                        Annuler
                    </button>
                    <button onclick="submitExpense()"
                        class="flex-1 py-4 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl font-black text-[11px] uppercase tracking-widest shadow-lg shadow-emerald-900/40 active:scale-[0.98] transition-all">
                        Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js');
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
        <?php
        $userId = $_SESSION['user_id'];

        // 1. Récupérer le budget personnel et professionnel, et les infos de feedback
        $stmt = $pdo->prepare("SELECT budget_data, budget_data_pro, last_budget_at, feedback_status, last_feedback_prompt_at, date_inscription FROM wari_users WHERE id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch();
        $budgetRaw = (!empty($userData['budget_data'])) ? $userData['budget_data'] : 'null';
        $budgetRawPro = (!empty($userData['budget_data_pro'])) ? $userData['budget_data_pro'] : 'null';

        $defaultProBudget = json_encode([
            "currency" => "F",
            "categories" => [
                ["id" => 101, "icon" => '<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" class="text-blue-400"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>', "name" => "Stock & Matériel", "amount" => 0, "balance" => 0, "percent" => 40],
                ["id" => 102, "icon" => '<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-400"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>', "name" => "Bénéfice Réinvesti", "amount" => 0, "balance" => 0, "percent" => 30],
                ["id" => 103, "icon" => '<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" class="text-amber-400"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>', "name" => "Frais de Fonctionnement", "amount" => 0, "balance" => 0, "percent" => 20],
                ["id" => 104, "icon" => '<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" class="text-red-400"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>', "name" => "Marketing & Publicité", "amount" => 0, "balance" => 0, "percent" => 10]
            ],
            "projectCapital" => 0,
            "vaultTransactions" => []
        ]);
        if ($budgetRawPro === 'null') {
            $budgetRawPro = $defaultProBudget;
        }

        // AJOUTER CE BLOC APRÈS $budgetRaw = ...
        if ($budgetRaw !== 'null') {
            $budgetData = json_decode($budgetRaw, true);
            $lastMonth = isset($budgetData['lastSavedMonth']) ? $budgetData['lastSavedMonth'] : null;
            $currentMonth = date('Y-m');

            if ($lastMonth && $lastMonth !== $currentMonth) {
                // Récupérer les dépenses du mois qui vient de se terminer ($lastMonth) pour calculer le report
                $stmtPrevExp = $pdo->prepare("
                    SELECT category_id, SUM(amount) as total 
                    FROM wari_expenses 
                    WHERE user_id = ? AND wallet_type = 'perso'
                    AND DATE_FORMAT(date_expense, '%Y-%m') = ?
                    GROUP BY category_id
                ");
                $stmtPrevExp->execute([$userId, $lastMonth]);
                $prevExpenses = [];
                foreach ($stmtPrevExp->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $prevExpenses[$row['category_id']] = (int)$row['total'];
                }

                if (isset($budgetData['categories'])) {
                    foreach ($budgetData['categories'] as &$cat) {
                        $catId = $cat['id'];
                        $isProjet = isset($cat['name']) && (strpos(strtolower($cat['name']), 'projet') !== false);
                        if (!$isProjet) {
                            $spent = isset($prevExpenses[$catId]) ? $prevExpenses[$catId] : 0;
                            $cat['balance'] = max(0, (isset($cat['balance']) ? (int)$cat['balance'] : 0) - $spent);
                        }
                    }
                }
                $budgetData['hasDepositedToday'] = false;
                $budgetData['lastSavedMonth'] = $currentMonth;

                $newBudgetRaw = json_encode($budgetData);
                $stmtUpdate = $pdo->prepare("UPDATE wari_users SET budget_data = ? WHERE id = ?");
                $stmtUpdate->execute([$newBudgetRaw, $userId]);
                $budgetRaw = $newBudgetRaw;
            }
        }

        // Rollover pour le budget pro
        if ($budgetRawPro !== 'null') {
            $budgetDataPro = json_decode($budgetRawPro, true);
            $lastMonthPro = isset($budgetDataPro['lastSavedMonth']) ? $budgetDataPro['lastSavedMonth'] : null;
            $currentMonth = date('Y-m');

            if ($lastMonthPro && $lastMonthPro !== $currentMonth) {
                $stmtPrevExpPro = $pdo->prepare("
                    SELECT category_id, SUM(amount) as total 
                    FROM wari_expenses 
                    WHERE user_id = ? AND wallet_type = 'pro'
                    AND DATE_FORMAT(date_expense, '%Y-%m') = ?
                    GROUP BY category_id
                ");
                $stmtPrevExpPro->execute([$userId, $lastMonthPro]);
                $prevExpensesPro = [];
                foreach ($stmtPrevExpPro->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $prevExpensesPro[$row['category_id']] = (int)$row['total'];
                }

                if (isset($budgetDataPro['categories'])) {
                    foreach ($budgetDataPro['categories'] as &$cat) {
                        $catId = $cat['id'];
                        $isProjet = isset($cat['name']) && (strpos(strtolower($cat['name']), 'projet') !== false);
                        if (!$isProjet) {
                            $spent = isset($prevExpensesPro[$catId]) ? $prevExpensesPro[$catId] : 0;
                            $cat['balance'] = max(0, (isset($cat['balance']) ? (int)$cat['balance'] : 0) - $spent);
                        }
                    }
                }
                $budgetDataPro['hasDepositedToday'] = false;
                $budgetDataPro['lastSavedMonth'] = $currentMonth;

                $newBudgetRawPro = json_encode($budgetDataPro);
                $stmtUpdatePro = $pdo->prepare("UPDATE wari_users SET budget_data_pro = ? WHERE id = ?");
                $stmtUpdatePro->execute([$newBudgetRawPro, $userId]);
                $budgetRawPro = $newBudgetRawPro;
            }
        }

        // 2. RÉCUPÉRER LES DÉPENSES DU MOIS ACTUEL (MARS 2026) POUR CHAQUE WALLET
        $stmtExpPerso = $pdo->prepare("
            SELECT category_id, SUM(amount) as total 
            FROM wari_expenses 
            WHERE user_id = ? AND wallet_type = 'perso'
            AND MONTH(date_expense) = MONTH(CURRENT_DATE()) 
            AND YEAR(date_expense) = YEAR(CURRENT_DATE())
            GROUP BY category_id
        ");
        $stmtExpPerso->execute([$userId]);
        $expensesPerso = $stmtExpPerso->fetchAll(PDO::FETCH_KEY_PAIR);

        $stmtExpPro = $pdo->prepare("
            SELECT category_id, SUM(amount) as total 
            FROM wari_expenses 
            WHERE user_id = ? AND wallet_type = 'pro'
            AND MONTH(date_expense) = MONTH(CURRENT_DATE()) 
            AND YEAR(date_expense) = YEAR(CURRENT_DATE())
            GROUP BY category_id
        ");
        $stmtExpPro->execute([$userId]);
        $expensesPro = $stmtExpPro->fetchAll(PDO::FETCH_KEY_PAIR);

        // 3. Récupérer les dettes
        $stmtDebts = $pdo->prepare("
            SELECT id, person_name, amount, type 
            FROM wari_debts 
            WHERE user_id = ? AND status = 'pending' 
            ORDER BY created_at DESC
        ");
        $stmtDebts->execute([$userId]);
        $debts = $stmtDebts->fetchAll(PDO::FETCH_ASSOC);

        // 4. Récupérer le défi d'épargne actif
        $stmtActiveChallenge = $pdo->prepare("
            SELECT id, user_id, challenge_type, base_amount, target_amount, current_amount, status, metadata 
            FROM wari_savings_challenges 
            WHERE user_id = ? AND status = 'active'
        ");
        $stmtActiveChallenge->execute([$userId]);
        $activeChallenge = $stmtActiveChallenge->fetch(PDO::FETCH_ASSOC);

        // Récupérer le nombre de défis complétés
        $stmtCompletedCount = $pdo->prepare("
            SELECT COUNT(*) 
            FROM wari_savings_challenges 
            WHERE user_id = ? AND status = 'completed'
        ");
        $stmtCompletedCount->execute([$userId]);
        $completedCount = $stmtCompletedCount->fetchColumn();

        // Envoi au JS
        echo "const dbDataPerso = " . $budgetRaw . ";\n";
        echo "const dbDataPro = " . $budgetRawPro . ";\n";
        echo "let dbData = dbDataPerso;\n";
        echo "const currentExpensesPerso = " . json_encode($expensesPerso) . ";\n";
        echo "const currentExpensesPro = " . json_encode($expensesPro) . ";\n";
        echo "let currentExpenses = currentExpensesPerso;\n";
        echo "const dbDebts = " . json_encode($debts) . ";\n";
        echo "let dbActiveChallenge = " . ($activeChallenge ? json_encode($activeChallenge) : 'null') . ";\n";
        echo "let dbCompletedChallengesCount = " . intval($completedCount) . ";\n";
        echo "const dbIsPremium = " . ((isset($_SESSION['is_premium']) && $_SESSION['is_premium']) ? 'true' : 'false') . ";\n";
        ?>
    </script>

    <script>
        // Horloge locale automatique
        function startLiveClock() {
            const el = document.getElementById("liveClock");
            if (!el) return;

            function tick() {
                const now = new Date();

                // Jour et mois en français selon le pays de l'utilisateur
                const day = now.toLocaleDateString(navigator.language, {
                    day: "numeric",
                    month: "long",
                    year: "numeric" // ✅ Année ajoutée
                });
                const time = now.toLocaleTimeString(navigator.language, {
                    hour: "2-digit",
                    minute: "2-digit",
                    second: "2-digit"
                });

                el.innerText = `${day} | ${time}`;
            }

            tick(); // Affichage immédiat
            setInterval(tick, 1000); // Mise à jour chaque seconde
        }

        startLiveClock();
    </script>

    <script>
        // On attend que la page soit prête
        document.addEventListener('DOMContentLoaded', () => {
            const lastClosed = localStorage.getItem('wari_push_modal_closed');
            const isDenied = Notification.permission === 'denied';
            const isDefault = Notification.permission === 'default';

            // 24 heures en millisecondes
            const twentyFourHours = 24 * 60 * 60 * 1000;
            const now = Date.now();

            // On affiche si : 
            // 1. Pas de permission accordée
            // 2. ET (Jamais fermé OU fermé il y a plus de 24h)
            if ((isDefault || isDenied) && (!lastClosed || (now - parseInt(lastClosed) > twentyFourHours))) {
    setTimeout(showWariPushModal, 3000);
}
        });

        function showWariPushModal() {
            if (document.getElementById('push-modal')) return; // Si le modal existe déjà, on ne fait rien
            const modalHtml = `
                <div id="push-modal" style="position:fixed; inset:0; background:#080b10; z-index:9999; display:flex; align-items:center; justify-content:center; padding:20px; backdrop-filter: blur(11px);">
                    <div style="background:#0d1117; border:1px solid #f5a623; border-radius:30px; padding:40px; text-align:center; max-width:400px; box-shadow: 0 20px 50px rgba(0,0,0,0.5);">
                        <h2 style="color:#fff; font-weight:900; letter-spacing:-1px; margin-bottom:11px; text-transform:uppercase;">RADAR DÉSACTIVÉ</h2>
                        <p style="color:#556070; font-size:14px; line-height:1.6; margin-bottom:30px;">
                            Champion&middot;ne, ton système d'alerte est éteint. 
                            Sans tes notifications, tu navigues à vue et ton budget risque de déraper.
                        </p>
                        <button id="activate-push" style="background:#f5a623; color:#000; border:none; padding:18px 30px; border-radius:15px; font-weight:800; cursor:pointer; width:100%; font-size:14px; text-transform:uppercase; transition: transform 0.2s;">
                            ACTIVER MON RADAR
                        </button>
                        <button onclick="closeWariModal()" style="background:transparent; border:none; margin-top:20px; color:#556070; font-size:11px; cursor:pointer; text-decoration:underline; text-transform:uppercase; letter-spacing:1px;">
                            Je préfère rester dans le noir
                        </button>
                    </div>
                </div>`;

            document.body.insertAdjacentHTML('beforeend', modalHtml);

            document.getElementById('activate-push').addEventListener('click', function() {
                subscribeUserToPush(true); // Manuel
                document.getElementById('push-modal').remove();
            });
        }

        function closeWariModal() {
            // On cache le modal et on s'en souvient pour 24h pour ne pas être "lourd"
            localStorage.setItem('wari_push_modal_closed', Date.now());
            document.getElementById('push-modal').remove();
        }


        async function subscribeUserToPush(isManual = false) {
            // Détection du mode PWA sur iOS (requis par Apple pour les pushs)
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
            if (isIOS && !isStandalone) {
                if (isManual) {
                    alert("Pour activer le radar sur iPhone, vous devez d'abord ajouter l'application à votre écran d'accueil :\n1. Cliquez sur l'icône de partage (carré avec une flèche vers le haut).\n2. Sélectionnez 'Sur l'écran d'accueil'.\n3. Ouvrez Wari depuis votre écran d'accueil et réessayez.");
                }
                updateRadarUI('unsupported');
                return;
            }

            // 1. Vérifier si le navigateur supporte les notifications
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                console.warn('Push non supporté sur ce navigateur.');
                updateRadarUI('unsupported');
                if (isManual) {
                    alert("Les notifications Push ne sont pas prises en charge par ce navigateur ou cette configuration.");
                }
                return;
            }

            try {
                const registration = await navigator.serviceWorker.ready;

                // 2. Ta clé VAPID Publique (celle que tu as dans ton PHP)
                const vapidPublicKey = 'BH9WpcuMhkSEOjnwf8KVZfDTv9Ps6nGaQ9RQ77e4D15ywgPmO7wNgTlldejjFjyWCp3PoBYareDXjlFBTdpzm40';
                const convertedVapidKey = urlBase64ToUint8Array(vapidPublicKey);

                // 3. Demander la souscription au navigateur
                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: convertedVapidKey
                });

                // 4. Envoyer les données à ton serveur (save_subscription.php)
                const response = await fetch('./config/save_subscription.php', {
                    method: 'POST',
                    body: JSON.stringify(subscription),
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error("Erreur serveur lors de l'enregistrement (" + response.status + ")");
                }

                console.log('✅ Radar activé avec succès !');
                updateRadarUI('active');

            } catch (error) {
                console.error('❌ Erreur lors de la souscription :', error);
                updateRadarUI('inactive');

                if (isManual) {
                    // On vérifie si c'est un refus de permission
                    if (Notification.permission === 'denied') {
                        showNotificationHelp(); // On appelle notre nouveau guide
                    } else {
                        alert("Oups ! Une petite erreur technique : " + error.message + "\nRéessaie dans un instant, Champion.");
                    }
                }
            }
        }

        function updateRadarUI(status) {
            const dot = document.getElementById('radarDot');
            const text = document.getElementById('radarText');
            const container = document.getElementById('radarStatusContainer');
            if (!dot || !text) return;

            if (status === 'active' || Notification.permission === 'granted') {
                // On vérifie quand même si on a une souscription réelle si possible
                // Mais pour l'UI, permission granted + call success = actif
                dot.className = "w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.6)] animate-pulse";
                text.innerText = "Radar ON";
                text.className = "text-[7px] font-black uppercase tracking-widest text-emerald-400";
                container.onclick = null; // Désactiver le clic si déjà actif
                container.style.cursor = 'default';
            } else if (status === 'unsupported') {
                dot.className = "w-1.5 h-1.5 rounded-full bg-red-500/30";
                text.innerText = "Radar HS";
                text.className = "text-[7px] font-black uppercase tracking-widest text-slate-600";
            } else {
                dot.className = "w-1.5 h-1.5 rounded-full bg-slate-600";
                text.innerText = "Radar OFF";
                text.className = "text-[7px] font-black uppercase tracking-widest text-slate-500";
                container.onclick = () => subscribeUserToPush(true);
                container.style.cursor = 'pointer';
            }
        }

        // Vérification initiale du statut
        if ('Notification' in window) {
            if (Notification.permission === 'granted') {
                setTimeout(() => updateRadarUI('active'), 1000);
            }
        }

        // Fonction utilitaire indispensable pour VAPID
        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }


        function showNotificationHelp() {
            const helpHtml = `
                <div id="help-modal" style="position:fixed; inset:0; background:rgba(8,11,16,0.95); z-index:10000; display:flex; align-items:center; justify-content:center; padding:20px; backdrop-filter: blur(11px);">
                    <div style="background:#0d1117; border:2px solid #f5a623; border-radius:30px; padding:30px; text-align:center; max-width:450px; box-shadow: 0 0 40px rgba(245,166,35,0.15);">
                        <div style="margin-bottom:15px; display:flex; justify-content:center;">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#f5a623" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </div>
                        <h2 style="color:#fff; font-weight:900; margin-bottom:15px; text-transform:uppercase;">RÉGLAGES DU RADAR</h2>
                        <p style="color:#94a3b8; font-size:13px; line-height:1.6; margin-bottom:25px; text-align:left;">
                            Champion·ne, ton radar est bloqué par ton système. Pour l'activer :<br><br>
                            <strong>Sur Android :</strong> Reste appuyé sur l'icône Wari > Infos > Notifications > Autoriser.<br><br>
                            <strong>Sur iPhone :</strong> Réglages > Notifications > Wari > Autoriser.
                        </p>
                        <button onclick="document.getElementById('help-modal').remove()" style="background:#f5a623; color:#000; border:none; padding:18px; border-radius:15px; font-weight:900; cursor:pointer; width:100%; text-transform:uppercase;">J'ai compris !</button>
                    </div>
                </div>`;
            document.body.insertAdjacentHTML('beforeend', helpHtml);
        }
    </script>

    <!-- Backdrop Flouteur pour le Chat Coach -->
    <div id="coachChatBackdrop" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-[140] hidden opacity-0 transition-opacity duration-300 cursor-pointer" onclick="closeCoachChat()"></div>

    <!-- Tiroir / Drawer Chat Coach AI -->
    <div id="coachChatModal" class="fixed inset-x-0 bottom-0 z-[150] h-[80vh] md:h-[600px] max-w-md mx-auto bg-slate-950/98 backdrop-blur-xl border border-white/10 rounded-t-[2rem] shadow-2xl flex flex-col translate-y-full transition-transform duration-500 ease-out">
        <!-- Barre de glissement supérieure tactile -->
        <div class="w-12 h-1 bg-white/20 rounded-full mx-auto my-3 cursor-pointer" onclick="closeCoachChat()"></div>
        
        <!-- En-tête -->
        <div class="px-5 pb-3 flex justify-between items-center border-b border-white/5">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-amber-400 via-amber-500 to-orange-600 flex items-center justify-center text-slate-950 shadow-md shadow-amber-500/20">
                        <!-- Coach mini SVG Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-950" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 9h.01M16 9h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 border-2 border-slate-950 rounded-full"></span>
                </div>
                <div>
                    <h3 class="text-sm font-black text-white leading-tight">Coach Wari</h3>
                    <p class="text-[9px] text-amber-500 font-bold uppercase tracking-widest">Conseiller Financier</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <button onclick="toggleTheme()" class="modalThemeToggleBtn w-8 h-8 flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover:text-white active:scale-95 transition-all" title="Changer le thème">
                    <span class="modalThemeIcon text-slate-400"></span>
                </button>
                <button onclick="closeCoachChat()" class="p-2 rounded-xl bg-white/5 text-slate-400 hover:text-white active:scale-95 transition-all" title="Fermer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Zone des Messages -->
        <div id="coachChatMessages" class="flex-1 overflow-y-auto px-5 py-4 space-y-3 custom-scrollbar flex flex-col">
        </div>

        <!-- Puces de Suggestions -->
        <div class="px-5 py-2 overflow-x-auto whitespace-nowrap scrollbar-none flex gap-2 border-t border-white/5">
            <button onclick="sendCoachSuggestion('Comment puis-je optimiser mon budget ce mois-ci ?')" class="px-3 py-1.5 rounded-full bg-slate-900 border border-white/5 text-slate-300 text-[10px] font-semibold hover:border-indigo-500/30 hover:text-white active:scale-95 transition-all">
                💡 Optimiser mon budget
            </button>
            <button onclick="sendCoachSuggestion('Fais-moi une analyse complète de ma situation financière actuelle.')" class="px-3 py-1.5 rounded-full bg-slate-900 border border-white/5 text-slate-300 text-[10px] font-semibold hover:border-indigo-500/30 hover:text-white active:scale-95 transition-all">
                📊 Analyse complète
            </button>
            <button onclick="sendCoachSuggestion('Quel est ton meilleur conseil de discipline financière aujourd\'hui ?')" class="px-3 py-1.5 rounded-full bg-slate-900 border border-white/5 text-slate-300 text-[10px] font-semibold hover:border-indigo-500/30 hover:text-white active:scale-95 transition-all">
                🥋 Conseil discipline
            </button>
            <button onclick="sendCoachSuggestion('Comment gérer mes dettes et les rembourser plus vite ?')" class="px-3 py-1.5 rounded-full bg-slate-900 border border-white/5 text-slate-300 text-[10px] font-semibold hover:border-indigo-500/30 hover:text-white active:scale-95 transition-all">
                💸 Gérer mes dettes
            </button>
        </div>
        
        <!-- Zone d'écriture -->
        <div class="px-4 py-3 bg-slate-950 border-t border-white/5 flex gap-2 items-center">
            <input type="text" id="coachChatInput" placeholder="Pose ta question financière..." 
                class="bg-slate-900 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-500/50 flex-1"
                onkeypress="handleCoachChatKeypress(event)">
            
            <button onclick="submitCoachChat()" id="coachChatSendBtn"
                class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-400 via-amber-500 to-orange-600 text-slate-950 flex items-center justify-center active:scale-95 transition-all hover:scale-105 shadow-md shadow-amber-500/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Script de gestion du Chat Coach AI -->
    <script>
        let coachChatHistory = [];

        // Gestion de l'affichage stratégique de la bulle d'appel à l'action
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                const bubble = document.getElementById('coachBubble');
                const isDismissed = localStorage.getItem('coach_bubble_dismissed') === 'true';
                const modal = document.getElementById('coachChatModal');
                const isChatOpen = modal && modal.classList.contains('translate-y-0');
                
                if (bubble && !isDismissed && !isChatOpen) {
                    bubble.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-2');
                    bubble.classList.add('opacity-100', 'translate-y-0');
                }
            }, 3000); // S'affiche après 3 secondes
        });

        window.dismissCoachBubble = function(event) {
            if (event) event.stopPropagation(); // Évite d'ouvrir le chat
            const bubble = document.getElementById('coachBubble');
            if (bubble) {
                bubble.classList.remove('opacity-100', 'translate-y-0');
                bubble.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
                localStorage.setItem('coach_bubble_dismissed', 'true');
            }
        };

        // Fonction pour ajuster la position du modal et de la zone scrollable quand le clavier mobile s'ouvre
        function adjustModalForKeyboard() {
            const modal = document.getElementById('coachChatModal');
            if (!modal) return;
            
            // Si le modal est affiché (classe translate-y-0 présente)
            if (modal.classList.contains('translate-y-0')) {
                if (window.visualViewport) {
                    const vvHeight = window.visualViewport.height;
                    const keyboardHeight = window.innerHeight - vvHeight;
                    
                    if (keyboardHeight > 80) { // Seuil pour détecter un clavier virtuel
                        // Garder bottom à 0px (le navigateur aligne déjà fixed bottom-0 sur le clavier sur mobile)
                        modal.style.bottom = '0px';
                        // Ajuster la hauteur pour tenir précisément dans la zone visible
                        modal.style.height = `${vvHeight}px`;
                    } else {
                        // Clavier fermé
                        modal.style.bottom = '0px';
                        modal.style.height = ''; // Revient à 80vh ou h-[600px]
                    }
                }
                
                // Défilement automatique vers le bas
                const messagesContainer = document.getElementById('coachChatMessages');
                if (messagesContainer) {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            }
        }

        // Enregistrement des écouteurs sur le visualViewport
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', adjustModalForKeyboard);
            window.visualViewport.addEventListener('scroll', adjustModalForKeyboard);
        }

        window.openCoachChat = function() {
            const modal = document.getElementById('coachChatModal');
            const backdrop = document.getElementById('coachChatBackdrop');
            if (!modal || !backdrop) return;

            // Bloquer le défilement de l'arrière-plan
            document.body.style.overflow = 'hidden';

            // Masque la bulle si elle est visible
            const bubble = document.getElementById('coachBubble');
            if (bubble) {
                bubble.classList.remove('opacity-100', 'translate-y-0');
                bubble.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
            }

            backdrop.classList.remove('hidden');
            setTimeout(() => {
                backdrop.classList.add('opacity-100');
            }, 10);

            modal.classList.remove('translate-y-full');
            modal.classList.add('translate-y-0');
            
            // Applique l'ajustement immédiatement au cas où
            setTimeout(adjustModalForKeyboard, 50);

            setTimeout(() => {
                document.getElementById('coachChatInput')?.focus();
            }, 300);
        };

        window.closeCoachChat = function() {
            const modal = document.getElementById('coachChatModal');
            const backdrop = document.getElementById('coachChatBackdrop');
            if (!modal || !backdrop) return;

            // Rétablir le défilement de l'arrière-plan
            document.body.style.overflow = '';

            modal.classList.remove('translate-y-0');
            modal.classList.add('translate-y-full');

            // Réinitialise les styles
            modal.style.bottom = '0px';
            modal.style.height = '';

            backdrop.classList.remove('opacity-100');
            setTimeout(() => {
                backdrop.classList.add('hidden');
            }, 300);
        };

        window.sendCoachSuggestion = function(text) {
            const input = document.getElementById('coachChatInput');
            if (input) {
                input.value = text;
                submitCoachChat();
            }
        };

        window.handleCoachChatKeypress = function(e) {
            if (e.key === 'Enter') {
                submitCoachChat();
            }
        };

        window.submitCoachChat = async function() {
            const input = document.getElementById('coachChatInput');
            const sendBtn = document.getElementById('coachChatSendBtn');
            const messagesContainer = document.getElementById('coachChatMessages');
            
            if (!input || !messagesContainer || !sendBtn) return;
            
            const text = input.value.trim();
            if (!text) return;
            
            input.value = '';
            input.disabled = true;
            sendBtn.disabled = true;
            
            appendChatMessage(text, 'user');
            
            const thinkingId = 'thinking_' + Date.now();
            appendChatThinking(thinkingId);
            
            const financialContext = getFinancialStatusContext();
            
            try {
                const formData = new FormData();
                formData.append('action', 'coach_chat');
                formData.append('message', text);
                formData.append('data', JSON.stringify(financialContext));
                formData.append('history', JSON.stringify(coachChatHistory));
                
                const res = await fetch('academy-admin/ai_gateway.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await res.json();
                
                const thinkingEl = document.getElementById(thinkingId);
                if (thinkingEl) thinkingEl.remove();
                
                if (result.response) {
                    appendChatMessage(result.response, 'coach');
                    
                    coachChatHistory.push({ role: 'user', content: text });
                    coachChatHistory.push({ role: 'model', content: result.response });
                    if (coachChatHistory.length > 20) {
                        coachChatHistory.shift();
                        coachChatHistory.shift();
                    }
                } else {
                    appendChatMessage("Désolé Champion·ne, j'ai rencontré un petit problème réseau. Reste concentré sur tes objectifs !", 'coach');
                }
            } catch (err) {
                console.error("Coach chat error:", err);
                const thinkingEl = document.getElementById(thinkingId);
                if (thinkingEl) thinkingEl.remove();
                appendChatMessage("Aïe, impossible de me connecter pour l'instant. Garde ta discipline budgétaire, c'est le plus important !", 'coach');
            } finally {
                input.disabled = false;
                sendBtn.disabled = false;
                
                // Ré-ajuste au cas où
                setTimeout(adjustModalForKeyboard, 50);
                input.focus();
            }
        };

        function appendChatMessage(text, sender) {
            const container = document.getElementById('coachChatMessages');
            if (!container) return;
            
            const msgDiv = document.createElement('div');
            if (sender === 'user') {
                msgDiv.className = "bg-amber-500 text-black self-end rounded-2xl rounded-tr-none px-3.5 py-2.5 text-xs font-semibold max-w-[85%] ml-auto shadow-sm";
            } else {
                msgDiv.className = "bg-slate-900 border border-white/5 text-slate-100 self-start rounded-2xl rounded-tl-none px-4 py-3 text-xs max-w-[85%] mr-auto leading-relaxed shadow-sm";
            }
            
            const formattedText = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            msgDiv.innerHTML = formattedText.replace(/\n/g, '<br>');
            
            container.appendChild(msgDiv);
            container.scrollTop = container.scrollHeight;
        }

        function appendChatThinking(id) {
            const container = document.getElementById('coachChatMessages');
            if (!container) return;
            
            const msgDiv = document.createElement('div');
            msgDiv.id = id;
            msgDiv.className = "bg-slate-900 border border-white/5 text-slate-400 self-start rounded-2xl rounded-tl-none px-4 py-3 text-xs max-w-[85%] mr-auto flex items-center gap-1 shadow-sm";
            msgDiv.innerHTML = `
                <span class="animate-bounce" style="animation-delay: 0.1s">•</span>
                <span class="animate-bounce" style="animation-delay: 0.2s">•</span>
                <span class="animate-bounce" style="animation-delay: 0.3s">•</span>
            `;
            
            container.appendChild(msgDiv);
            container.scrollTop = container.scrollHeight;
        }

        function getFinancialStatusContext() {
            const currency = document.getElementById("currencySelector")?.value || "F";
            const now = new Date();
            const year = now.getFullYear();
            const month = now.getMonth();
            const day = now.getDate();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysLeft = daysInMonth - day + 1;

            const cashAmountEl = document.getElementById("cashAmount");
            const cashValue = parseInt(cashAmountEl?.innerText.replace(/[^0-9]/g, "")) || 0;
            const budgetQuotidien = Math.round(cashValue / daysLeft);

            const totalDettes = (typeof dbDebts !== "undefined" ? dbDebts : [])
              .reduce((acc, d) => acc + (parseInt(d.amount) || 0), 0);

            const catSummary = categories
              .map(c => `- ${c.name}: ${currentExpenses[c.id] || 0} ${currency} dépensés sur ${c.name.toLowerCase().includes("projet") ? projectCapital : c.balance || 0} ${currency} prévus`)
              .join("\n");

            const debtsSummary = (typeof dbDebts !== "undefined" ? dbDebts : [])
              .map(d => `- ${d.type === 'loan' ? 'Prêt à' : 'Dette envers'} ${d.person_name} : ${parseInt(d.amount).toLocaleString()} ${currency}`)
              .join("\n") || "Aucune dette active.";

            // Récupération de l'objectif d'épargne projet ciblé par l'utilisateur
            const goalStr = localStorage.getItem("wari_vault_goal");
            let goalAmount = 0;
            let goalLabel = "";
            if (goalStr) {
                try {
                    const goal = JSON.parse(goalStr);
                    if (goal) {
                        goalAmount = parseInt(goal.amount) || 0;
                        goalLabel = goal.label || goal.name || "";
                    }
                } catch(e) {}
            }

            return {
              cash_restant: cashValue,
              jours_restants: daysLeft,
              budget_quotidien_conseille: budgetQuotidien,
              categories_details: catSummary,
              dettes_details: debtsSummary,
              total_dettes: totalDettes,
              capital_projet: projectCapital,
              devise: currency,
              objectif_projet_montant: goalAmount,
              objectif_projet_label: goalLabel
            };
        }
    </script>

    <script>
        (function() {
            const btn = document.getElementById('coachButton');
            if (!btn) return;
            
            let isDragging = false;
            let startX, startY, initialLeft, initialTop;
            let dragDistance = 0;
            
            function onDragStart(clientX, clientY) {
                isDragging = true;
                startX = clientX;
                startY = clientY;
                
                const rect = btn.getBoundingClientRect();
                initialLeft = rect.left;
                initialTop = rect.top;
                
                btn.style.bottom = 'auto';
                btn.style.right = 'auto';
                btn.style.left = initialLeft + 'px';
                btn.style.top = initialTop + 'px';
                
                dragDistance = 0;
            }
            
            function onDragMove(clientX, clientY) {
                if (!isDragging) return;
                const dx = clientX - startX;
                const dy = clientY - startY;
                
                dragDistance += Math.abs(dx) + Math.abs(dy);
                
                const newLeft = Math.max(10, Math.min(window.innerWidth - 60, initialLeft + dx));
                const newTop = Math.max(10, Math.min(window.innerHeight - 60, initialTop + dy));
                
                btn.style.left = newLeft + 'px';
                btn.style.top = newTop + 'px';
            }
            
            function onDragEnd(e) {
                if (!isDragging) return;
                isDragging = false;
                
                if (dragDistance > 10) {
                    e.preventDefault();
                    btn.addEventListener('click', preventClick, { capture: true });
                    setTimeout(() => {
                        btn.removeEventListener('click', preventClick, { capture: true });
                    }, 50);
                }
            }
            
            function preventClick(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            // Touch Events
            btn.addEventListener('touchstart', (e) => {
                const touch = e.touches[0];
                onDragStart(touch.clientX, touch.clientY);
            }, { passive: true });
            
            btn.addEventListener('touchmove', (e) => {
                const touch = e.touches[0];
                onDragMove(touch.clientX, touch.clientY);
            }, { passive: true });
            
            btn.addEventListener('touchend', onDragEnd);
            
            // Mouse Events
            btn.addEventListener('mousedown', (e) => {
                onDragStart(e.clientX, e.clientY);
                e.preventDefault();
            });
            
            document.addEventListener('mousemove', (e) => {
                onDragMove(e.clientX, e.clientY);
            });
            
            document.addEventListener('mouseup', onDragEnd);
        })();
    </script>

    <!-- MODAL DE PRÉSENTATION WARI PREMIUM -->
    <div id="premiumIntroModal" class="fixed inset-0 bg-slate-950/90 backdrop-blur-sm hidden items-center justify-center p-4 z-[170]">
        <div class="glass-card w-full max-w-sm p-6 border border-amber-500/30 shadow-2xl relative flex flex-col text-center" onclick="event.stopPropagation()">
            
            <!-- Indicateurs de progression -->
            <div class="flex justify-center gap-1.5 mb-4 select-none shrink-0">
                <div class="premium-intro-dot w-2 h-2 rounded-full bg-amber-500 transition-colors" data-step="1"></div>
                <div class="premium-intro-dot w-2 h-2 rounded-full bg-slate-700 transition-colors" data-step="2"></div>
                <div class="premium-intro-dot w-2 h-2 rounded-full bg-slate-700 transition-colors" data-step="3"></div>
                <div class="premium-intro-dot w-2 h-2 rounded-full bg-slate-700 transition-colors" data-step="4"></div>
            </div>

            <!-- Contenu de la diapositive -->
            <div id="premiumIntroContent" class="flex-1 flex flex-col justify-center min-h-[160px] mb-6">
                <!-- Rempli par JavaScript -->
            </div>

            <!-- Zone de boutons -->
            <div id="premiumIntroButtons" class="shrink-0 flex flex-col gap-2">
                <!-- Rempli par JavaScript -->
            </div>
        </div>
    </div>

    <!-- MODAL PREMIUM CÉRÉMONIE FIN DE MOIS -->
    <div id="endOfMonthModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center p-4 z-[160]">
        <div class="glass-card w-full max-w-md p-5 border border-slate-700 shadow-2xl relative flex flex-col max-h-[90vh]" onclick="event.stopPropagation()">
            <div class="text-center shrink-0 pb-3 border-b border-slate-700/60">
                <div class="w-12 h-12 mx-auto bg-amber-500/10 text-amber-400 rounded-full flex items-center justify-center mb-2 border border-amber-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-white font-bold text-lg">Bilan de fin de mois</h3>
                <p class="text-slate-400 text-[11px] mt-1 leading-normal">
                    Félicitations ! Le mois dernier s'est terminé et tu as réussi à préserver de l'argent. Choisis ce que tu souhaites en faire pour démarrer ce nouveau mois.
                </p>
            </div>

            <!-- Liste des catégories éligibles -->
            <div id="eomCategoryList" class="my-4 space-y-3 overflow-y-auto pr-1 flex-1 custom-scrollbar">
                <!-- Rempli dynamiquement en JS -->
            </div>

            <div class="shrink-0 pt-3 border-t border-slate-700/60">
                <button onclick="submitEndOfMonth()" class="w-full py-3 bg-gradient-to-r from-amber-500 to-yellow-600 text-black rounded-xl font-bold text-sm shadow-md active:scale-95 transition-all">
                    Valider mes choix
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL AIDE CONTEXTUELLE -->
    <div id="helpModal" class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm hidden items-center justify-center p-4 z-[140]" onclick="closeHelpModal()">
        <div class="glass-card w-full max-w-sm p-5 border border-slate-700 shadow-2xl relative" onclick="event.stopPropagation()">
            <button onclick="closeHelpModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white">✕</button>
            <div id="helpIcon" class="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center mb-3 text-amber-500"></div>
            <h3 id="helpTitle" class="text-white font-bold text-lg mb-2">Titre</h3>
            <p id="helpDesc" class="text-slate-400 text-sm mb-5 leading-relaxed">Description courte.</p>
            <a href="guide/index.php" class="block w-full py-2.5 bg-amber-500 text-slate-950 text-center rounded-xl font-bold text-sm hover:bg-amber-400 transition-colors">
                Lire le guide complet
            </a>
        </div>
    </div>

    <!-- MODAL ONBOARDING (BIENVENUE) -->
    <div id="onboardingModal" class="fixed inset-0 bg-slate-950/95 backdrop-blur-md hidden items-center justify-center p-4 z-[150]">
        <div class="glass-card w-full max-w-md p-6 border border-amber-500/20 shadow-2xl relative text-center">
            <div class="w-16 h-16 mx-auto bg-gradient-to-br from-amber-400 to-yellow-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-amber-500/20">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            </div>
            <h2 class="text-2xl font-bold text-white mb-2 font-serif">Bienvenue sur Wari</h2>
            <p class="text-slate-400 text-sm mb-6 leading-relaxed">
                Plus qu'une application, Wari est une méthode pour reprendre le contrôle de tes finances. Apprends à maîtriser ton <strong class="text-emerald-400">Coffre-fort</strong> et ton <strong class="text-amber-500">Train de vie</strong>.
            </p>
            <div class="flex flex-col gap-3">
                <a href="guide/index.php" class="block w-full py-3 bg-gradient-to-r from-amber-500 to-yellow-600 text-black rounded-xl font-bold text-sm shadow-md active:scale-95 transition-all">
                    Découvrir comment ça marche (Guide)
                </a>
                <button onclick="closeOnboarding()" class="block w-full py-3 bg-slate-800 text-slate-300 rounded-xl font-bold text-sm hover:bg-slate-700 active:scale-95 transition-all">
                    J'ai compris, fermer
                </button>
            </div>
        </div>
    </div>

    <script src="./assets/main.js?v=133"></script>
    <script>
        // Logique Onboarding
        document.addEventListener('DOMContentLoaded', () => {
            if (!localStorage.getItem('wari_onboarding_seen')) {
                const onboardingModal = document.getElementById('onboardingModal');
                if (onboardingModal) {
                    onboardingModal.classList.remove('hidden');
                    onboardingModal.classList.add('flex');
                }
            }
        });

        function closeOnboarding() {
            localStorage.setItem('wari_onboarding_seen', 'true');
            const onboardingModal = document.getElementById('onboardingModal');
            if (onboardingModal) {
                onboardingModal.classList.add('hidden');
                onboardingModal.classList.remove('flex');
            }
        }

        // Logique Aide Contextuelle (Tooltips)
        const helpData = {
            'coffre-fort': {
                title: 'Banque (Coffre-Fort)',
                icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>',
                desc: 'Ton épargne intouchable. L\'argent qui entre ici ne peut pas être dépensé directement. C\'est ce qui te garantit la sécurité et tes investissements futurs.'
            },
            'train-de-vie': {
                title: 'Poche (Train de vie)',
                icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"></path><path d="M4 6v12c0 1.1.9 2 2 2h14v-4"></path><path d="M18 12a2 2 0 0 0-2 2c0 1.1.9 2 2 2h4v-4h-4z"></path></svg>',
                desc: 'C\'est l\'argent que tu as le droit de dépenser au quotidien. L\'objectif est que ce solde atteigne rigoureusement 0 avant ton prochain revenu.'
            },
            'dette': {
                title: 'Le carnet de dettes',
                icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>',
                desc: 'Renseigner vos prêts et vos emprunts, tout ce que vous prêtez et tout ce qu\'on vous prête, votre budget n\'en sera que plus sain. '
            }
        };

        function openHelpModal(type) {
            const data = helpData[type];
            if (!data) return;
            document.getElementById('helpTitle').innerText = data.title;
            document.getElementById('helpDesc').innerText = data.desc;
            document.getElementById('helpIcon').innerHTML = data.icon;
            
            const modal = document.getElementById('helpModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeHelpModal() {
            const modal = document.getElementById('helpModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
    <?php if (isset($_SESSION['recharge_success'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                showToastMessage("Abonnement prolongé avec succès ! 🚀", "success");
            });
        </script>
        <?php unset($_SESSION['recharge_success']); ?>
    <?php endif; ?>

    <?php
    // Logique PHP pour décider de l'affichage du modal Défis
    $showChallengesModal = false;
    if (isset($userData['feedback_status']) && (int)$userData['feedback_status'] === 0) {
        $dateInscription = $userData['date_inscription'] ?? null;
        $lastPrompt = $userData['last_feedback_prompt_at'] ?? null;
        
        if ($dateInscription) {
            $diffInscription = time() - strtotime($dateInscription);
            $daysInscription = $diffInscription / (24 * 3600);
            
            if ($daysInscription >= 3) {
                if ($lastPrompt === null) {
                    $showChallengesModal = true;
                } else {
                    $diffPrompt = time() - strtotime($lastPrompt);
                    $daysPrompt = $diffPrompt / (24 * 3600);
                    if ($daysPrompt >= 14) {
                        $showChallengesModal = true;
                    }
                }
            }
        }
    }
    ?>

    <?php if ($showChallengesModal): ?>
    <!-- MODAL DÉFIS UTILISATEURS -->
    <div id="challengesModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 z-[145]">
        <div class="w-full max-w-sm rounded-3xl p-5 border shadow-2xl relative transition-all duration-300 bg-slate-900 border-slate-800 text-slate-100 modal-challenges-container"
             style="font-family: 'Quicksand', sans-serif;">
            
            <button onclick="dismissChallengesModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white transition-colors text-base font-bold">✕</button>
            
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center mb-3">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#C9A84C" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            </div>
            
            <h3 class="font-extrabold text-xl mb-1 tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-yellow-600">Partagez vos défis</h3>
            
            <p class="text-xs mb-4 text-slate-400 leading-relaxed font-medium">
                Quel défi ou difficulté rencontrez-vous dans l'utilisation de Wari ?
            </p>
            
            <form id="challengesForm" onsubmit="submitChallenge(event)">
                <div class="grid grid-cols-2 gap-2 mb-4">
                    <label class="category-chip-label">
                        <input type="radio" name="challenge_category" value="repartition" checked class="hidden">
                        <span class="category-chip">Répartition</span>
                    </label>
                    <label class="category-chip-label">
                        <input type="radio" name="challenge_category" value="coach" class="hidden">
                        <span class="category-chip">Coach IA</span>
                    </label>
                    <label class="category-chip-label">
                        <input type="radio" name="challenge_category" value="academy" class="hidden">
                        <span class="category-chip">Académie</span>
                    </label>
                    <label class="category-chip-label">
                        <input type="radio" name="challenge_category" value="vecu" class="hidden">
                        <span class="category-chip">Vécu</span>
                    </label>
                    <label class="category-chip-label col-span-2">
                        <input type="radio" name="challenge_category" value="autre" class="hidden">
                        <span class="category-chip">Autre</span>
                    </label>
                </div>
                
                <div class="relative mb-4">
                    <textarea id="challenge_message" name="message" rows="3" maxlength="500" required
                              class="w-full text-sm rounded-xl p-3 bg-slate-950 border border-slate-800 text-white placeholder-slate-700 focus:outline-none focus:border-amber-500/50 transition-colors resize-none textarea-challenges"
                              placeholder="Décrivez votre problème ou suggestion ici..."
                              oninput="updateMessageCount()"></textarea>
                    <span id="charCount" class="absolute bottom-2 right-3 text-[9px] text-slate-600 font-bold char-count-span">0 / 500</span>
                </div>
                
                <button type="submit" id="btnSubmitChallenge"
                        class="w-full py-3 bg-gradient-to-r from-yellow-400 to-yellow-600 text-slate-950 rounded-xl font-bold text-sm shadow-md active:scale-95 transition-all flex items-center justify-center gap-1.5 hover:from-yellow-300 hover:to-yellow-500">
                    <span>Envoyer mon avis</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </button>
            </form>
        </div>
    </div>
    
    <style>
        /* Styles adaptatifs pour le modal Défis */
        .modal-challenges-container {
            transition: background-color 0.3s, border-color 0.3s, color 0.3s;
        }
        
        .textarea-challenges {
            transition: background-color 0.3s, border-color 0.3s, color 0.3s;
        }
        
        /* Compatibilité mode clair basée sur la classe .light-mode sur le document */
        .light-mode .modal-challenges-container {
            background-color: #ffffff !important;
            border-color: rgba(15, 23, 42, 0.08) !important;
            color: #0f172a !important;
        }
        
        .light-mode .textarea-challenges {
            background-color: #f8fafc !important;
            border-color: rgba(15, 23, 42, 0.08) !important;
            color: #0f172a !important;
            placeholder-color: #94a3b8 !important;
        }
        
        .light-mode .char-count-span {
            color: #94a3b8 !important;
        }
        
        .category-chip-label {
            cursor: pointer;
            display: block;
        }
        
        .category-chip {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            background-color: #0a0a0a;
            border: 1px solid rgba(255, 255, 255, 0.03);
            color: #94a3b8;
            transition: all 0.2s ease;
            user-select: none;
        }
        
        .light-mode .category-chip {
            background-color: #f1f5f9;
            border-color: rgba(15, 23, 42, 0.04);
            color: #475569;
        }
        
        .category-chip-label input[type="radio"]:checked + .category-chip {
            background-color: rgba(201, 168, 76, 0.15) !important;
            border-color: #C9A84C !important;
            color: #C9A84C !important;
        }
        
        .light-mode .category-chip-label input[type="radio"]:checked + .category-chip {
            background-color: rgba(201, 168, 76, 0.1) !important;
            border-color: #C9A84C !important;
            color: #785a1a !important;
        }
    </style>
    
    <script>
        function updateMessageCount() {
            const textarea = document.getElementById('challenge_message');
            const countSpan = document.getElementById('charCount');
            if (textarea && countSpan) {
                countSpan.innerText = `${textarea.value.length} / 500`;
                
                // Auto-grow height logique
                textarea.style.height = 'auto';
                textarea.style.height = Math.min(textarea.scrollHeight, 180) + 'px';
                textarea.style.overflowY = textarea.scrollHeight > 180 ? 'auto' : 'hidden';
            }
        }
        
        async function dismissChallengesModal() {
            try {
                await fetch('config/save_user_challenge.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'dismiss' })
                });
            } catch (e) {
                console.error("Erreur lors de la fermeture du popup:", e);
            }
            
            const modal = document.getElementById('challengesModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }
        
        async function submitChallenge(event) {
            event.preventDefault();
            const form = document.getElementById('challengesForm');
            const categoryInput = form.querySelector('input[name="challenge_category"]:checked');
            const messageInput = document.getElementById('challenge_message');
            const btn = document.getElementById('btnSubmitChallenge');
            
            if (!categoryInput || !messageInput || !messageInput.value.trim()) return;
            
            const originalBtnHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<span class="inline-block w-4 h-4 border-2 border-slate-950 border-t-transparent rounded-full animate-spin"></span> Envoi...`;
            
            try {
                const response = await fetch('config/save_user_challenge.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'submit',
                        category: categoryInput.value,
                        message: messageInput.value.trim()
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    btn.className = "w-full py-3 bg-emerald-500 text-slate-950 rounded-xl font-bold text-xs shadow-md transition-all flex items-center justify-center gap-1.5";
                    btn.innerHTML = `✓ Merci pour votre retour !`;
                    
                    setTimeout(() => {
                        const modal = document.getElementById('challengesModal');
                        if (modal) {
                            modal.classList.add('hidden');
                            modal.classList.remove('flex');
                        }
                    }, 1500);
                } else {
                    alert(result.error || "Une erreur est survenue.");
                    btn.disabled = false;
                    btn.innerHTML = originalBtnHtml;
                }
            } catch (e) {
                console.error(e);
                alert("Impossible de se connecter au serveur.");
                btn.disabled = false;
                btn.innerHTML = originalBtnHtml;
            }
        }
    </script>
    <?php endif; ?>
</body>

</html>