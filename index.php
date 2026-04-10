<?php
require_once __DIR__ . '/wari_monitoring.php';  // ← TOUJOURS EN PREMIER
// Configuration session 90 jours avant tout output
require 'config/session_config.php'; // Charge la config 90 jours
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
require 'config/db.php'; // <--- INDISPENSABLE : Pour que $pdo fonctionne !


if (!isset($_SESSION['user_id'])) {
    header('Location: config/auth.php');
    exit();
}
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">

    <!-- Open Graph (Facebook, WhatsApp, LinkedIn) -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Wari-Finance - Gère ton argent sans stress">
    <meta property="og:description" content="Budget, objectifs, conseils simples pour maîtriser tes finances au quotidien. Application gratuite.">
    <meta property="og:url" content="https://wari.digiroys.com/accueil/">
    <meta property="og:image" content="https://wari.digiroys.com/assets/wari_og_1.png">
    <meta property="og:locale" content="fr_FR">

    <link rel="icon" type="image/png" href="./assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="./assets/warifinance3d.png">

    <link rel="stylesheet" href="./assets/styles.css?v=68">

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
                <span id="liveClock" class="text-[9px] font-bold tracking-tight mt-0.5 text-transparent bg-clip-text bg-gradient-to-r from-amber-400/80 to-yellow-600/60"></span>
            </div>

            <!-- ✅ BOUTON HISTORIQUE -->
            <button onclick="openHistoryModal()"
                class="flex items-center justify-center rounded-2xl active:scale-95 transition-all duration-300 group">
                <svg width="46" height="46" fill="currentColor" class="h-10 w-10 text-slate-500 group-hover:text-amber-400 transition-colors" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M4 12a9 9 0 1 1 9 9c-2.49 0-4.73-1.01-6.36-2.64l1.42-1.42A6.944 6.944 0 0 0 13 19c3.87 0 7-3.13 7-7s-3.13-7-7-7-7 3.13-7 7h3l-4 3.99L1 12h3Zm8 1V8h1.5v4.15l3.52 2.09-.77 1.28L12 13Z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </header>

        <!-- Jauge de Santé Financière -->
        <section class="glass-card p-5 mb-4 shine-effect">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-[11px] uppercase tracking-widest text-emerald-400 font-bold mb-1">Santé financière</h3>
                    <div class="flex items-baseline gap-1">
                        <span id="gaugePercent" class="text-2xl font-black text-white">Stable</span>
                        <!-- <span class="text-xs font-bold text-emerald-500">+0.99%</span> -->
                    </div>
                </div>
                <div class="text-right bg-slate-900/40 px-3 py-2 rounded-2xl border border-white/5">
                    <span id="disciplineScore" class="text-2xl font-black text-white leading-none">--</span>
                    <p class="text-[7px] uppercase text-slate-500 font-bold tracking-widest mt-1">Discipline / 10</p>
                </div>
            </div>

            <div class="w-full h-2 bg-slate-950/60 rounded-full overflow-hidden border border-white/5 mb-4">
                <div id="gaugeBar"
                    class="h-full bg-gradient-to-r from-emerald-600 to-emerald-400 rounded-full transition-all duration-700"
                    style="width: 0%">
                </div>
            </div>

            <div class="flex items-start gap-2 pt-3 border-t border-white/5">
                <!-- <div class="w-8 h-8 rounded-full bg-amber-500/10 flex items-center justify-center shrink-0">
                    <span class="text-xs">🤵‍♂️</span>
                </div> -->
                <p id="aiCoachMessage" class="text-[11px] text-slate-300 leading-snug italic">
                    Belle progression ! Ton épargne couvre maintenant 3 mois de besoins.
                </p>
            </div>
        </section>

        <div class="grid grid-cols-2 gap-3 mb-4">
            <!-- BANQUE : Effet Blur pour se concentrer sur l'actif de croissance -->
            <div class="glass-card p-4 cursor-pointer active:scale-95 transition-all group" 
                 onclick="const el = this.querySelector('#bankAmount'); el.classList.toggle('blur-[6px]'); el.classList.toggle('opacity-30'); el.classList.toggle('opacity-100');">
                <div class="flex justify-between items-start mb-1">
                    <p class="text-[8px] uppercase tracking-widest text-slate-500 font-black">Banque (Réserves)</p>
                    <span class="text-[10px] opacity-0 group-hover:opacity-100 transition-opacity">👁️</span>
                </div>
                <p id="bankAmount" class="text-lg font-black text-white blur-[6px] opacity-30 transition-all duration-500 select-none">0 F</p>
                <p class="text-[7px] text-slate-600 mt-1 uppercase tracking-wider leading-tight">Liberté de Sécurité</p>
            </div>

            <!-- POCHE : Reste en clair pour les dépenses quotidiennes -->
            <div class="glass-card p-4">
                <p class="text-[8px] uppercase tracking-widest text-slate-500 mb-1 font-black">Poche (Dispo)</p>
                <p id="cashAmount" class="text-lg font-black text-emerald-400">0 F</p>
                <p class="text-[7px] text-slate-600 mt-1 uppercase tracking-wider leading-tight text-left">Dispo (Vie + Imprévus)</p>
            </div>
        </div>

        <!-- Section insertion du montant a repartire -->
        <div class="glass-card gold-border p-4 mb-4 shadow-2xl relative">
            <div class="flex justify-between items-center mb-3">
                <label class="block text-[11px] uppercase tracking-[0.2em] text-yellow-500 font-bold">Montant à répartir</label>
                <select id="currencySelector" onchange="render()" class="bg-slate-800 text-yellow-500 text-xs font-bold rounded px-1 py-1 outline-none">
                    <option value="F">CFA</option>
                    <option value="$">USD ($)</option>
                    <option value="€">EUR (€)</option>
                </select>
            </div>
            <div class="flex items-end border-b-2 border-slate-700 pb-2 focus-within:border-emerald-500 transition-colors">
                <input type="number" id="mainAmount" placeholder="0"
                    class="bg-transparent text-4xl w-full font-extrabold outline-none text-white">
                <span id="currentSymbol" class="text-xl font-bold text-slate-500 ml-2">F</span>
            </div>
        </div>

        <!-- Section de contrôle de l'édition -->
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xs font-bold uppercase tracking-widest text-slate-500">Répartition</h3>
            <button id="lockBtn" onclick="toggleEditMode()"
                class="flex items-center gap-1 px-2 py-1 rounded-full bg-slate-900 border border-slate-700 transition-all active:scale-95 shadow-lg">
                <!-- <span>🔒</span> -->
                <span class="text-[11px] font-black uppercase tracking-[0.1em] text-slate-400">Lecture</span>
            </button>
        </div>
        <!-- Conteneur des catégories -->
        <div id="categoryContainer" class="grid grid-cols-2 gap-3 mb-6">
        </div>

        <!-- Section Juge barre de % -->
        <div id="statusIndicator" class="mt-4 flex items-center justify-center space-x-2 p-4 rounded-2xl transition-all duration-500">
            <div id="statusIcon"></div>
            <span id="statusText" class="font-bold text-sm uppercase tracking-wider"></span>
        </div>

        <!-- Section Versement a la banque (capital investir)-->
        <div id="projectVault" class="mt-6 glass-card p-4 relative overflow-hidden group border-none shadow-2xl">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/5 rounded-full blur-3xl group-hover:bg-emerald-500/15 transition-all duration-1000"></div>

            <div class="flex items-start justify-between mb-6 relative z-10">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="text-[11px] uppercase tracking-[0.1em] text-emerald-400 font-black">Liberté d'Action</h3>
                    </div>
                    <p class="text-[11px] text-slate-500 font-medium">Plantée à chaque répartition</p>
                </div>
                <div class="text-right">
                    <div class="flex items-baseline justify-end gap-1">
                        <span id="totalProjectSaved" class="text-3xl font-black text-white tracking-tighter drop-shadow-sm">0</span>
                        <!-- <span id="capitalCurrency" class="text-xs font-bold text-emerald-500/80 uppercase">F</span> -->
                    </div>
                    <!-- Miniature Patrimoine (Fortune Totale) -->
                    <div class="flex items-center justify-end gap-1 mt-1 opacity-90 transition-all duration-500">
                        <span class="text-[8px] uppercase tracking-[0.2em] text-slate-500 font-black">Liberté :</span>
                        <span id="totalGlobalAmount" class="text-[10px] font-black text-emerald-400">0</span>
                    </div>
                </div>
            </div>

            <div class="relative mb-6">
                <div class="flex justify-between items-center mb-1.5 px-0.5">
                    <!-- <span id="vaultPercentLabel" class="text-[9px] font-black text-emerald-500/80 tracking-widest uppercase">0% Atteint</span> -->
                    <span id="vaultGoalAmountDisplay" class="text-[9px] font-bold text-emerald-500/80 tracking-widest uppercase">Objectif: --</span>
                </div>
                <div class="relative w-full h-2 bg-slate-900/60 rounded-full p-[2px] border border-white/5 shadow-inner">
                    <div id="vaultProgress" class="h-full bg-gradient-to-r from-emerald-600 via-emerald-400 to-teal-300 rounded-full shadow-[0_0_12px_rgba(16,185,129,0.3)] transition-all duration-1000 ease-out" style="width: 0%"></div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mt-4">
                <div class="bg-slate-900/40 px-3 py-2 rounded-2xl border border-slate-800/50 flex flex-col justify-center">
                    <p class="text-[8px] uppercase tracking-widest text-slate-500 font-bold mb-1">Cible</p>
                    <div class="flex items-center justify-between gap-2">
                        <p id="vaultGoalLabel" class="text-[11px] text-white font-black truncate">Définir</p>
                        <div class="flex gap-2">
                            <button onclick="openGoalModal()" class="text-emerald-500 hover:scale-110 transition-transform text-[11px]">✏️</button>
                            <button id="deleteGoalBtn" onclick="deleteGoal()" class="text-red-400/70 hover:text-red-500 transition-colors text-[11px] hidden">✕</button>
                        </div>
                    </div>
                </div>

                <div class="bg-emerald-500/5 px-3 py-2 rounded-2xl border border-emerald-500/10 flex items-center gap-2">
                    <div class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></div>
                    <span class="text-[8px] text-emerald-400/80 uppercase tracking-widest font-black leading-tight">Capital<br>Sécurisé</span>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-800/40">
                <div class="flex justify-between items-center mb-3">
                    <p class="text-[8px] uppercase tracking-[0.2em] text-slate-500 font-bold flex items-center gap-2">Historique</p>
                    <button onclick="window.toggleVaultHistory()" id="toggleHistBtn" class="text-[9px] text-slate-400 font-black uppercase tracking-widest">Détails</button>
                </div>
                <div id="vaultHistory" class="space-y-1.5 max-h-12 overflow-hidden transition-all duration-500">
                    <p class="text-[11px] text-slate-600 italic text-center py-2">Aucun mouvement</p>
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

        <!-- Section Bouttons -->
        <div class="fixed bottom-4 left-0 right-0 max-w-md mx-auto flex justify-center items-center gap-2 z-[110]">
    
            <a href="https://wari.digiroys.com/academy/"target="_blank" 
            class="w-10 h-10 bg-slate-900 rounded-full flex items-center justify-center border border-slate-700 text-slate-300 active:scale-95 transition-all hover:text-indigo-400 shadow-lg shadow-slate-900/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M12 14l9-5-9-5-9 5 9 5z" />
                    <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                </svg>
            </a>

            <button onclick="saveBudget()"
                class="px-6 py-3 bg-slate-900 rounded-full font-bold text-xs uppercase tracking-wider text-slate-300 border border-slate-700 active:scale-95 transition-all hover:text-blue-400 flex items-center gap-2 shadow-lg shadow-slate-900/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Mettre a jour</span>
            </button>
        </div>

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
        <div class="glass-card w-full max-w-sm p-4 border border-slate-700 shadow-2xl">

            <div class="flex items-center justify-between mb-4">
                <h3 class="text-amber-400 font-bold uppercase tracking-widest text-sm">Historique Mensuel</h3>

                <select onchange="loadMonthlyHistory(this.value)"
                    class="bg-slate-800 text-slate-300 text-[11px] border border-slate-700 rounded-lg px-2 py-1">
                    <option value="3">3 mois</option>
                    <option value="6" selected>6 mois</option>
                    <option value="12">12 mois</option>
                </select>

                <button onclick="closeHistoryModal()" class="text-slate-500 hover:text-white transition-colors text-lg">✕</button>
            </div>

            <div id="historyContent" class="space-y-3 max-h-[60vh] overflow-y-auto custom-scrollbar">
                <p class="text-slate-500 text-[11px] italic text-center py-4">Chargement...</p>
            </div>
        </div>
    </div>

    <!-- Bouton depense -->
    <button onclick="openExpenseModal()"
        class="fixed bottom-20 right-6 w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-900 rounded-full flex items-center justify-center text-white active:scale-95 hover:scale-110 transition-all duration-300 z-50 group">

        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 transition-transform group-hover:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>

        <div class="absolute inset-0 rounded-full bg-gradient-to-t from-transparent to-white/20 pointer-events-none"></div>
    </button>

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
                subscribeUserToPush(); // Ton script VAPID existant
                document.getElementById('push-modal').remove();
            });
        }

        function closeWariModal() {
            // On cache le modal et on s'en souvient pour 24h pour ne pas être "lourd"
            localStorage.setItem('wari_push_modal_closed', Date.now());
            document.getElementById('push-modal').remove();
        }


        async function subscribeUserToPush() {
            // 1. Vérifier si le navigateur supporte les notifications
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                console.warn('Push non supporté sur ce navigateur.');
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

            // Dans ta fonction subscribeUserToPush()
            } catch (error) {
                console.error('❌ Erreur lors de la souscription :', error);

                // On vérifie si c'est un refus de permission
                if (Notification.permission === 'denied') {
                    showNotificationHelp(); // On appelle notre nouveau guide
                } else {
                    alert("Oups ! Une petite erreur technique. Réessaie dans un instant, Champion.");
                }
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
                        <div style="font-size:40px; margin-bottom:15px;">⚙️</div>
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

    <script src="./assets/main.js?v=68"></script>
</body>

</html>