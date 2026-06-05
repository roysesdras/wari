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

if (!isset($_SESSION['user_id'])) {
    header('Location: config/auth.php');
    exit();
}

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

    <link rel="stylesheet" href="./assets/styles.css?v=81">

    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0B141A;">

    <script src="https://stats.digiroys.com/tracker.js" data-key="key_wari_789"></script>
    <script>
        <?php if (isset($_SESSION['user_email'])): ?>
            // On identifie l'utilisateur pour TOUTES ses actions sur le dashboard
            DigiStats.identify("<?= $_SESSION['user_email'] ?>");
        <?php endif; ?>
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

            <!-- ✅ BOUTON DÉCONNEXION / SORTIR -->
            <a href="config/logout.php" title="Se déconnecter"
                class="w-10 h-10 rounded-2xl flex items-center justify-center active:scale-95 transition-all duration-300 group bg-white/5 hover:bg-red-500/10 border border-white/5 hover:border-red-500/20 shadow-md">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" class="text-slate-400 group-hover:text-red-400 transition-colors" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17 16L21 12M21 12L17 8M21 12H9M13 16V17C13 18.1046 12.1046 19 11 19H5C3.89543 19 3 18.1046 3 17V7C3 5.89543 3.89543 5 5 5H11C12.1046 5 13 5.89543 13 7V8" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        </header>

        <!-- Jauge de Santé Financière -->
        <section id="gauge-section" class="glass-card p-3 mb-4">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="text-[11px] uppercase tracking-widest text-emerald-400 font-bold">Santé financière</h3>
                        <div id="radarStatusContainer" class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-slate-900/50 border border-white/5 cursor-pointer active:scale-95 transition-all" onclick="subscribeUserToPush(true)">
                            <div id="radarDot" class="w-1.5 h-1.5 rounded-full bg-slate-600"></div>
                            <span id="radarText" class="text-[7px] font-black uppercase tracking-widest text-slate-500">Radar OFF</span>
                        </div>
                    </div>
                    
                </div>
                
            </div>

            

            <!-- Zone du Graphique Évolution -->
            <div class="relative w-full h-[140px] p-1 flex items-center justify-center mb-2">
                <div id="chartLoader" class="absolute text-slate-500 text-[10px] italic">Chargement du graphique...</div>
                <svg id="trendChartSvg" class="w-full h-full opacity-0 transition-opacity duration-500" viewBox="0 0 400 140"></svg>
            </div>
            
            <!-- Légende du Graphique -->
            <div class="flex justify-center gap-4 select-none">
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
                    <p class="text-[8px] uppercase tracking-widest text-slate-500 font-black">Banque (Réserves)</p>
                    <span class="opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </span>
                </div>
                <p id="bankAmount" class="text-lg font-black text-white blur-[6px] opacity-30 transition-all duration-500 select-none">0 F</p>
                <p class="text-[7px] text-slate-600 mt-1 uppercase tracking-wider leading-tight">Liberté de Sécurité</p>
            </div>

            <!-- POCHE : Reste en clair pour les dépenses quotidiennes -->
            <div class="glass-card p-3">
                <p class="text-[8px] uppercase tracking-widest text-slate-500 mb-1 font-black">Poche (Dispo)</p>
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
                <input type="number" id="mainAmount" placeholder="0" onfocus="this.select()"
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

        <!-- Dette Section -->
        <div id="debtSection" class="mt-4 glass-card p-3 shadow-2xl relative">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-[11px] uppercase tracking-[0.1em] text-red-400 font-bold">Carnet de Dettes</h3>
                <button onclick="openDebtModal()" class="text-[11px] bg-red-500/20 text-red-400 px-3 py-1 rounded-full border border-red-500/30 font-bold hover:bg-red-500/40 transition-all">
                    + Ajouter
                </button>
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
    <div id="historyModal" class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm hidden items-center justify-center p-4 z-[130]">
        <div class="glass-card w-full max-w-sm p-4 border border-slate-700 shadow-2xl flex flex-col max-h-[85vh]">

            <div class="flex items-center justify-between mb-4 shrink-0">
                <h3 class="text-amber-400 font-bold uppercase tracking-widest text-xs">Tableau de Bord</h3>

                <select onchange="loadMonthlyHistory(this.value)"
                    class="bg-slate-800 text-slate-300 text-[11px] border border-slate-700 rounded-lg px-2 py-1">
                    <option value="3">3 mois</option>
                    <option value="6" selected>6 mois</option>
                    <option value="12">12 mois</option>
                </select>

                <button onclick="closeHistoryModal()" class="text-slate-500 hover:text-white transition-colors text-lg">✕</button>
            </div>

            <div id="historyContent" class="space-y-4 overflow-y-auto custom-scrollbar flex-1 pr-1">
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
    <a id="coachButton" href="/coach/" target="_blank"
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

        // 1. Récupérer le budget
        $stmt = $pdo->prepare("SELECT budget_data, last_budget_at FROM wari_users WHERE id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch();
        $budgetRaw = (!empty($userData['budget_data'])) ? $userData['budget_data'] : 'null';

        // AJOUTER CE BLOC APRÈS $budgetRaw = ...
        if ($budgetRaw !== 'null') {
            $budgetData = json_decode($budgetRaw, true);
            $lastMonth = isset($budgetData['lastSavedMonth']) ? $budgetData['lastSavedMonth'] : null;
            $currentMonth = date('Y-m');

            if ($lastMonth && $lastMonth !== $currentMonth) {
                if (isset($budgetData['categories'])) {
                    foreach ($budgetData['categories'] as &$cat) {
                        $cat['balance'] = 0;
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

        // 2. RÉCUPÉRER LES DÉPENSES DU MOIS ACTUEL (MARS 2026)
        // Cette requête est beaucoup plus fiable car elle ne dépend pas de 'last_budget_at'
        $stmtExp = $pdo->prepare("
            SELECT category_id, SUM(amount) as total 
            FROM wari_expenses 
            WHERE user_id = ? 
            AND MONTH(date_expense) = MONTH(CURRENT_DATE()) 
            AND YEAR(date_expense) = YEAR(CURRENT_DATE())
            GROUP BY category_id
        ");
        $stmtExp->execute([$userId]);
        $expenses = $stmtExp->fetchAll(PDO::FETCH_KEY_PAIR);

        // 3. Récupérer les dettes
        $stmtDebts = $pdo->prepare("
            SELECT id, person_name, amount, type 
            FROM wari_debts 
            WHERE user_id = ? AND status = 'pending' 
            ORDER BY created_at DESC
        ");
        $stmtDebts->execute([$userId]);
        $debts = $stmtDebts->fetchAll(PDO::FETCH_ASSOC);

        // Envoi au JS
        echo "const dbData = " . $budgetRaw . ";\n";
        echo "let currentExpenses = " . json_encode($expenses) . ";\n";
        echo "const dbDebts = " . json_encode($debts) . ";\n";
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
            // 1. Vérifier si le navigateur supporte les notifications
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                console.warn('Push non supporté sur ce navigateur.');
                updateRadarUI('unsupported');
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
                await fetch('./config/save_subscription.php', {
                    method: 'POST',
                    body: JSON.stringify(subscription),
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

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
                        alert("Oups ! Une petite erreur technique. Réessaie dans un instant, Champion.");
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
            
            <button onclick="closeCoachChat()" class="p-2 rounded-xl bg-white/5 text-slate-400 hover:text-white active:scale-95 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
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

    <script src="./assets/main.js?v=81"></script>
</body>

</html>