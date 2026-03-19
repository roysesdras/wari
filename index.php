<?php
session_start();
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
    <title>Wari - Finance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">

    <link rel="icon" type="image/png" href="./assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="./assets/warifinance3d.png">

    <link rel="stylesheet" href="./assets/styles.css?v=42">

    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0f172a">

    <script src="https://stats.digiroys.com/tracker.js" data-key="key_wari_789"></script>
    <script>
        <?php if (isset($_SESSION['user_email'])): ?>
            // On identifie l'utilisateur pour TOUTES ses actions sur le dashboard
            DigiStats.identify("<?= $_SESSION['user_email'] ?>");
        <?php endif; ?>
    </script>

</head>

<body class="p-4 pb-20">

    <div class="max-w-md mx-auto">
        <header class="flex items-center justify-between mt-2 mb-8">
            <div class="flex flex-col">
                <h1 class="text-3xl font-black tracking-tighter text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-yellow-600">
                    WARI - Finance
                </h1>
                <p class="text-[8px] font-bold uppercase tracking-[0.3em] text-transparent bg-clip-text bg-gradient-to-r from-slate-400 to-slate-600">Budget & Liberté</p>
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

        <!-- Section insertion du montant a repartire -->
        <div class="glass-card gold-border p-4 mb-8 shadow-2xl relative">
            <div class="flex justify-between items-center mb-3">
                <label class="block text-[10px] uppercase tracking-[0.2em] text-yellow-500 font-bold">Montant à répartir</label>
                <select id="currencySelector" onchange="render()" class="bg-slate-800 text-yellow-500 text-xs font-bold border border-slate-700 rounded px-2 py-1 outline-none focus:border-yellow-500">
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

        <!-- Section categorie des de repartition -->
        <div class="mb-6 flex items-center justify-between bg-yellow-600/10 border border-yellow-600/20 p-3 rounded-2xl">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-yellow-500/20 flex items-center justify-center text-xl">🌟</div>
                <div>
                    <h4 class="text-[10px] uppercase tracking-widest text-yellow-500 font-bold">Stratégie Active</h4>
                    <p class="text-white font-bold text-xs">Configuration "Mon Wari" (15/50/25/10)</p>
                </div>
            </div>
            <span class="text-[8px] bg-yellow-500 text-black px-2 py-1 rounded-md font-black uppercase shadow-lg shadow-yellow-500/20">Optimal</span>
        </div>

        <!-- Section de contrôle de l'édition -->
        <div class="flex justify-between items-center mb-4 px-2">
            <h3 class="text-xs font-bold uppercase tracking-widest text-slate-500">Répartition</h3>
            <button id="lockBtn" onclick="toggleEditMode()" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-800 border border-slate-700 transition-all active:scale-95">
                <span>🔒</span>
                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Lecture</span>
            </button>
        </div>

        <!-- Conteneur des catégories -->
        <div id="categoryContainer" class="space-y-4">
        </div>

        <!-- Section Juge barre de % -->
        <div id="statusIndicator" class="flex items-center justify-center space-x-2 p-4 rounded-2xl transition-all duration-500">
            <div id="statusIcon"></div>
            <span id="statusText" class="font-bold text-sm uppercase tracking-wider"></span>
        </div>

        <!-- Section Versement a la banque (capital investir)-->
        <div id="projectVault" class="mt-8 glass-card p-3 border-t-2 border-emerald-500/50 shadow-[0_20px_50px_rgba(16,185,129,0.1)] relative overflow-hidden group">
            <div class="absolute -right-10 -top-10 w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl group-hover:bg-emerald-500/20 transition-all duration-700"></div>

            <div class="flex items-center justify-between mb-4 relative z-10">
                <div>
                    <h3 class="text-[11px] uppercase tracking-[0.25em] text-emerald-400 font-black mb-1">🚀 Capital Investir</h3>
                    <p class="text-[10px] text-slate-500 font-medium leading-tight">Trésor accumulé pour tes projets</p>
                </div>
                <div class="text-right">
                    <span id="totalProjectSaved" class="text-3xl font-black text-white tracking-tighter transition-all duration-500 drop-shadow-lg">
                        0 <span id="capitalCurrency">F</span>
                    </span>
                </div>
            </div>

            <div class="relative w-full h-1.5 bg-slate-800/50 rounded-full overflow-hidden mb-6">
                <div id="vaultProgress" class="h-full bg-gradient-to-r from-emerald-600 to-emerald-400 shadow-[0_0_15px_rgba(16,185,129,0.4)] transition-all duration-1000 ease-out" style="width: 0%"></div>
            </div>

            <div class="h-[1px] w-full bg-gradient-to-r from-transparent via-slate-700/50 to-transparent mb-6"></div>

            <div class="w-full py-2 bg-emerald-500/5 border border-emerald-500/20 rounded-2xl 
                text-emerald-400 text-[10px] uppercase tracking-[0.2em] font-black 
                flex items-center justify-center gap-2">
                <!-- <span class="text-base">💎</span> -->
                <span>Capital sécurisé à chaque répartition</span>
            </div>

            <!-- OBJECTIF DU COFFRE -->
            <div class="mt-4 flex items-center justify-between bg-slate-900/40 px-2 py-2 rounded-xl border border-slate-700/30">
                <div>
                    <p class="text-[9px] uppercase tracking-widest text-slate-500 font-bold">Objectif</p>
                    <p id="vaultGoalLabel" class="text-[11px] text-white font-black">—</p>
                </div>

                <div class="flex items-center gap-2">
                    <span id="vaultGoalAmount" class="text-[10px] text-emerald-400 font-black"></span>
                    <button onclick="openGoalModal()"
                        class="w-7 h-7 rounded-full bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 text-sm flex items-center justify-center hover:bg-emerald-500/40 transition-all">
                        ✏️
                    </button>
                    <button onclick="deleteGoal()" id="deleteGoalBtn"
                        class="w-7 h-7 rounded-full bg-red-500/20 border border-red-500/30 text-red-400 text-sm hidden items-center justify-center hover:bg-red-500/40 transition-all">
                        ✕
                    </button>
                </div>
            </div>

            <div class="mt-8 border-t border-slate-800/50 pt-5">
                <div class="flex justify-between items-center mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px]">📊</span>
                        <p class="text-[9px] uppercase tracking-[0.15em] text-slate-500 font-bold">Historiques</p>
                    </div>
                    <button onclick="window.toggleVaultHistory()" id="toggleHistBtn"
                        class="text-[9px] text-emerald-500 font-black hover:text-emerald-400 transition-colors tracking-widest uppercase">
                        Détails
                    </button>
                </div>

                <div id="vaultHistory" class="space-y-2 max-h-28 overflow-hidden custom-scrollbar">
                    <p class="text-[11px] text-slate-600 italic text-center py-4">Tes premiers pas vers la fortune apparaîtront ici...</p>
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
        </div>

        <!-- Jauge de Santé Financière -->
        <div id="healthGauge" class="mt-4 glass-card p-3 border-l-4 border-emerald-500/50 shadow-xl">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-[10px] uppercase tracking-widest text-emerald-400 font-bold">💡 Santé financière</h3>
                <span id="gaugePercent" class="text-emerald-400 font-black text-sm">—</span>
            </div>

            <!-- Barre principale -->
            <div class="w-full h-3 bg-slate-950/60 rounded-full overflow-hidden border border-white/5 mb-4">
                <div id="gaugeBar" class="h-full rounded-full transition-all duration-700 ease-out" style="width:0%"></div>
            </div>

            <!-- Banque / Cash -->
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="bg-slate-800/40 rounded-xl p-3 border border-slate-700/30">
                    <p class="text-[9px] uppercase tracking-widest text-slate-500 mb-1">🔒 À sécuriser en banque</p>
                    <p id="bankAmount" class="text-white font-black text-sm">0 F</p>
                    <p id="bankSpent" class="text-[9px] text-red-400 mt-0.5"></p>
                    <p class="text-[8px] text-slate-600 mt-1 uppercase tracking-wider">Épargne + Capital Projet</p>
                </div>
                <div class="bg-slate-800/40 rounded-xl p-3 border border-slate-700/30">
                    <p class="text-[9px] uppercase tracking-widest text-slate-500 mb-1">💵 Poche / MoMo</p>
                    <p id="cashAmount" class="text-emerald-400 font-black text-sm">0 F</p>
                    <p id="cashSpent" class="text-[9px] text-red-400 mt-0.5"></p>
                    <p class="text-[8px] text-slate-600 mt-1 uppercase tracking-wider">Train de vie + Imprévus</p>
                </div>
            </div>

            <!-- Message coach contextuel -->
            <div id="gaugeAlert" class="text-[10px] text-center py-2 px-3 rounded-lg font-bold"></div>
        </div>

        <!-- Dette Section -->
        <div id="debtSection" class="mt-4 glass-card p-3 border-t-2 border-red-500 shadow-2xl relative">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-[10px] uppercase tracking-[0.2em] text-red-400 font-bold">📖 Carnet de Dettes</h3>
                <button onclick="openDebtModal()" class="text-[10px] bg-red-500/20 text-red-400 px-3 py-1 rounded-full border border-red-500/30 font-bold hover:bg-red-500/40 transition-all">
                    + Ajouter
                </button>
            </div>

            <div id="debtList" class="space-y-3">
                <p class="text-slate-500 text-[10px] italic text-center">Aucune dette ou créance en cours.</p>
            </div>
        </div>

        <!-- Section Coach Intelligence Financière -->
        <div id="reportSection" class="mt-4 mb-4 glass-card p-3 border-t-2 border-yellow-500 shadow-2xl bg-gradient-to-b from-slate-800/50 to-transparent">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-[10px] uppercase tracking-[0.2em] text-yellow-500 font-bold">📊 Intelligence Financière</h3>
                    <p class="text-[9px] text-slate-400">Analyse de votre comportement ce mois</p>
                </div>
                <div class="text-right">
                    <div id="disciplineScore" class="text-3xl font-black text-white">--</div>
                    <p class="text-[8px] uppercase text-slate-500 font-bold tracking-widest">Score / 10</p>
                </div>
            </div>

            <div id="reportMessage" class="bg-slate-900/50 rounded-xl p-4 border border-slate-700/30 mb-4">
                <p class="text-xs text-slate-300 leading-relaxed italic" id="aiCoachMessage">
                    "Analyse en cours..."
                </p>
            </div>

            <div class="space-y-3">
                <div class="flex justify-between items-center text-[10px] font-bold uppercase">
                    <span class="text-slate-400">Respect Budget</span>
                    <span id="budgetSuccessText" class="text-emerald-400">0%</span>
                </div>
                <div class="w-full h-1 bg-slate-800 rounded-full overflow-hidden">
                    <div id="budgetSuccessBar" class="h-full bg-emerald-500 transition-all duration-1000" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <!-- Modal pour ajouter une dette -->
        <div id="debtModal" class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm hidden items-center justify-center p-4 z-[110]">
            <div class="glass-card w-full max-w-sm p-4 border border-slate-700 shadow-2xl">
                <h3 id="debtModalTitle" class="text-red-400 font-bold mb-4 uppercase tracking-widest text-sm">Ajouter une note</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] text-slate-400 mb-1">TYPE</label>
                        <select id="debtType" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white outline-none">
                            <option value="loan">On me doit (Créance)</option>
                            <option value="debt">Je dois (Dette)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] text-slate-400 mb-1">NOM DE LA PERSONNE</label>
                        <input type="text" id="debtPerson" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white outline-none" placeholder="Ex: Moussa">
                    </div>
                    <div>
                        <label class="block text-[10px] text-slate-400 mb-1 uppercase">
                            Montant (<span class="currencyLabel">F</span>)
                        </label>
                        <input type="number" id="debtAmount" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white outline-none" placeholder="0">
                    </div>

                    <div>
                        <label class="block text-[10px] text-slate-400 mb-1 uppercase">Date d'échéance (optionnel)</label>
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
                <p id="payModalTarget" class="text-slate-400 text-[10px] mb-4"></p>

                <input type="hidden" id="payDebtId">
                <input type="hidden" id="payDebtType">

                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] text-slate-400 mb-1 uppercase">
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
        <div class="fixed bottom-2 left-0 right-0 max-w-md mx-auto flex gap-3 z-[110]">

            <!-- <a href="config/logout.php"
                class="w-16 flex items-center justify-center bg-slate-900 border border-slate-700 rounded-2xl active:scale-95 transition-all duration-300 group">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-500 group-hover:text-red-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </a> -->

            <!-- BOUTON SAUVEGARDE -->
            <button onclick="saveBudget()"
                class="relative flex-1 overflow-hidden py-4 bg-slate-900 border border-slate-700 rounded-2xl font-black text-[10px] uppercase tracking-[0.3em] text-slate-300 active:scale-95 transition-all duration-300 group">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-500/10 to-purple-500/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative flex items-center justify-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 group-hover:text-blue-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span class="group-hover:text-white">Mettre à jour</span>
                </div>
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
    </div>

    <!-- MODAL OBJECTIF -->
    <div id="goalModal" class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm hidden items-center justify-center p-4 z-[130]">
        <div class="glass-card w-full max-w-sm p-4 border border-slate-700 shadow-2xl">
            <h3 class="text-emerald-400 font-bold uppercase tracking-widest text-sm mb-4">🎯 Définir un objectif</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] text-slate-400 mb-1 uppercase">Nom de l'objectif</label>
                    <input type="text" id="goalLabel" placeholder="Ex: MacBook Pro, Terrain..."
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-[10px] text-slate-400 mb-1 uppercase">Montant cible</label>
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
                <h3 class="text-amber-400 font-bold uppercase tracking-widest text-sm">📊 Historique Mensuel</h3>

                <select onchange="loadMonthlyHistory(this.value)"
                    class="bg-slate-800 text-slate-300 text-[10px] border border-slate-700 rounded-lg px-2 py-1">
                    <option value="3">3 mois</option>
                    <option value="6" selected>6 mois</option>
                    <option value="12">12 mois</option>
                </select>

                <button onclick="closeHistoryModal()" class="text-slate-500 hover:text-white transition-colors text-lg">✕</button>
            </div>

            <div id="historyContent" class="space-y-3 max-h-[60vh] overflow-y-auto custom-scrollbar">
                <p class="text-slate-500 text-[10px] italic text-center py-4">Chargement...</p>
            </div>
        </div>
    </div>

    <!-- Bouton Message WhatsApp -->
    <?php // include 'bouton_whatssap.php'; 
    ?>

    <!-- Bouton depense -->
    <button onclick="openExpenseModal()"
        class="fixed bottom-20 right-6 w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-600 rounded-full flex items-center justify-center text-white active:scale-95 hover:scale-110 transition-all duration-300 z-50 group">

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
                    <label class="block text-[10px] uppercase tracking-[0.15em] text-slate-500 mb-2 font-black">
                        💰 Montant à déduire (<span class="currencyLabel">F</span>)
                    </label>
                    <input type="number" id="expAmount"
                        class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl p-4 text-white text-xl font-black outline-none focus:border-emerald-500/50 focus:ring-4 focus:ring-emerald-500/10 transition-all placeholder:text-slate-700"
                        placeholder="0">
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-[0.15em] text-slate-500 mb-2 font-black">
                        📝 Motif de la dépense
                    </label>
                    <input type="text" id="expNote"
                        class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl p-4 text-white text-sm outline-none focus:border-emerald-500/50 focus:ring-4 focus:ring-emerald-500/10 transition-all placeholder:text-slate-700"
                        placeholder="Ex: Achat Ordinateur, Loyer, Resto...">
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-[0.15em] text-slate-500 mb-2 font-black">
                        📁 Catégorie cible
                    </label>
                    <select id="expCategory"
                        class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl p-4 text-white text-sm outline-none focus:border-emerald-500/50 appearance-none cursor-pointer tracking-wide">
                    </select>
                </div>

                <div class="flex gap-3 pt-4">
                    <button onclick="closeExpenseModal()"
                        class="flex-1 py-4 bg-slate-800/50 hover:bg-slate-800 text-slate-400 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all">
                        Annuler
                    </button>
                    <button onclick="submitExpense()"
                        class="flex-1 py-4 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-emerald-900/40 active:scale-[0.98] transition-all">
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
                    minute: "2-digit"
                });

                el.innerText = `${day} — ${time}`;
            }

            tick(); // Affichage immédiat
            setInterval(tick, 1000); // Mise à jour chaque seconde
        }

        startLiveClock();
    </script>

    <script src="./assets/main.js?v=42"></script>
</body>

</html>