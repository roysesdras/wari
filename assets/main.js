// ─── CONFIGURATION INITIALE ────────────────────────────────────────────────

const SVG_ICONS = {
  rocket: `<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-400"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"></path><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"></path><path d="M9 12H4s.55-3.03 2-5c1.62-2.2 5-3 5-3"></path><path d="M12 15v5s3.03-.55 5-2c2.2-1.62 3-5 3-3"></path></svg>`,
  piggy: `<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" class="text-blue-400"><path d="M19 5c-1.5 0-2.8 1.4-3 2-3.5-1.5-11-.3-11 5 0 1.8 0 3 2 4.5V20h4v-2h3v2h4v-4c1-.5 1.7-1 2-2h2v-4h-2c0-1-.5-1.5-1-2h0V5z"></path><path d="M2 9v1c0 1.1.9 2 2 2h1"></path><path d="M16 11h0"></path></svg>`,
  alert: `<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" class="text-red-400"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"></path><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>`,
  home: `<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" class="text-amber-400"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>`,
  lock: `<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>`,
  lockOpen: `<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg>`,
  target: `<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="6"></circle><circle cx="12" cy="12" r="2"></circle></svg>`,
  money: `<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>`
};

let categories = [
  { id: 3, name: "Projet", percent: 25, icon: SVG_ICONS.rocket, balance: 0 },
  { id: 1, name: "Épargne", percent: 15, icon: SVG_ICONS.piggy, balance: 0 },
  { id: 4, name: "Imprévu", percent: 10, icon: SVG_ICONS.alert, balance: 0 },
  { id: 2, name: "Train de vie", percent: 50, icon: SVG_ICONS.home, balance: 0 },
];

let projectCapital = 0;
let isEditMode = false;
let isInitialLoad = true;
let vaultTransactions = [];

const mainInput = document.getElementById("mainAmount");
const container = document.getElementById("categoryContainer");

// Vérifier si nous sommes sur la bonne page avant d'exécuter le code
// Ligne 19-23 remplacées par :
if (mainInput && container) {
  // ... tout votre code principal ici ...
} else {
  console.log(
    "Wari-Finance: Éléments principaux non trouvés - arrêt du script",
  );
}

// ─── RENDER ────────────────────────────────────────────────────────────────

function render(isSimulation = false) {
  // Vérification de sécurité supplémentaire
  if (!mainInput) {
    console.warn("Wari-Finance: mainInput non disponible");
    return;
  }

  const rawValue = mainInput.value.trim();
  const total = parseFloat(rawValue) || 0;

  const currencyElement = document.getElementById("currencySelector");
  const currency = currencyElement ? currencyElement.value : "F";

  const symbolEl = document.getElementById("currentSymbol");
  if (symbolEl) symbolEl.innerText = currency;

  const aDuSolde =
    categories.some((cat) => (cat.balance || 0) > 0) || projectCapital > 0;

  // Bouton coffre — affichage informatif uniquement
  try {
    const btnVault = document.querySelector(
      'button[onclick*="addToProjectVault"]',
    );
    if (btnVault) {
      btnVault.disabled = true;
      btnVault.classList.remove("opacity-50", "grayscale");
      btnVault.innerHTML = `Capital sécurisé à chaque répartition`;
    }
  } catch (e) {
    console.error("Erreur affichage coffre:", e);
  }

  if (rawValue === "" && !aDuSolde) {
    container.innerHTML = `<div class="text-center p-10 text-slate-500 text-sm italic">Entrez un montant pour commencer...</div>`;
    const bankEl = document.getElementById("bankAmount");
    const cashEl = document.getElementById("cashAmount");
    if (bankEl) bankEl.innerText = `0 ${currency}`;
    if (cashEl) cashEl.innerText = `0 ${currency}`;
    updateStatus(0);
    return;
  }

  // ── Calculs par catégorie ──────────────────────────────────────────────
  let currentTotalPercent = 0;
  let calculatedTotalAmount = 0;
  let results = [];

  categories.forEach((cat) => {
    currentTotalPercent += cat.percent;
    const partSimulation = Math.round((total * cat.percent) / 100);
    const cumulExistant = cat.balance || 0;
    const montantTotalVisuel = cumulExistant + partSimulation;
    results.push({ ...cat, amount: montantTotalVisuel });
    calculatedTotalAmount += partSimulation;
  });

  const difference = total - calculatedTotalAmount;
  if (difference !== 0 && results.length > 0) {
    const biggestCat = results.reduce((p, c) =>
      p.percent > c.percent ? p : c,
    );
    biggestCat.amount += difference;
  }

  // ── Sous-titres des cartes (version texte, sans emojis) ─────────────────
  const catSubtitles = {
    projet: "L'argent que tu investis dans ton futur",
    epargne: "Ta sécurité financière, à ne pas toucher",
    imprevu: "Le bouclier pour les coups durs",
    train: "Ce qui reste pour vivre au quotidien"
  };

  function getCatSubtitle(name) {
    const lowerName = name.toLowerCase();
    if (lowerName.includes("projet")) return catSubtitles.projet;
    if (lowerName.includes("épargne")) return catSubtitles.epargne;
    if (lowerName.includes("imprévu")) return catSubtitles.imprevu;
    if (lowerName.includes("train")) return catSubtitles.train;
    return "";
  }

  // ── Affichage des cartes ───────────────────────────────────────────────
  container.innerHTML = "";

  results.forEach((cat) => {
    const isProjet = cat.name.toLowerCase().includes("projet");
    const spent =
      typeof currentExpenses !== "undefined" && currentExpenses[cat.id]
        ? parseInt(currentExpenses[cat.id])
        : 0;

    const currentBalance = parseFloat(cat.balance) || 0;
    const currentPercent = parseFloat(cat.percent) || 0;
    const amountToDistribute = total;

    const montantAjoute = Math.round(
      amountToDistribute * (currentPercent / 100),
    );

    // Pour le Projet, on ne soustrait plus spent ici car projectCapital est déjà NET
    // Mais on ajoute le montantAjoute pour que le Coffre réagisse en temps réel
    const totalPrevisionnel = isProjet
      ? (parseFloat(projectCapital) || 0) + spent + montantAjoute
      : currentBalance + montantAjoute;

    const remaining = Math.max(0, totalPrevisionnel - spent);
    const progress =
      totalPrevisionnel > 0
        ? Math.min(100, (spent / totalPrevisionnel) * 100)
        : 0;

    const card = document.createElement("div");
    card.className = `glass-card p-2.5 flex flex-col transition-all duration-300`;

    card.innerHTML = `
        <div class="flex items-center justify-between mb-3">
            <div class="h-8 w-8 flex items-center justify-center bg-white/5 rounded-xl border border-white/5 shadow-inner">
              ${cat.icon.replace('width="24" height="24"', 'width="18" height="18"')}
            </div>
            <div class="flex items-center justify-center bg-slate-900/80 px-2 py-0.5 rounded-lg border border-white/5 gap-[2px]">
              <input type="number" value="${currentPercent}" 
                  ${isEditMode ? "" : "disabled"}
                  oninput="updatePercent(${cat.id}, this.value)"
                  class="w-[20px] bg-transparent text-[10px] font-black outline-none text-right p-0 m-0 ${isEditMode ? "text-amber-400" : "text-slate-400"}">
              <span class="text-[10px] font-bold text-slate-600">%</span>
          </div>
        </div>

        <div class="mb-2">
            <div class="flex items-center justify-between mb-0.5">
                <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                    ${cat.name}
                </h4>
                <button class="info-btn w-4 h-4 rounded-full bg-white/5 text-[8px] text-slate-400 flex items-center justify-center leading-none"
                        data-cat="${cat.name}"
                        onclick="event.stopPropagation(); showCatInfo('${cat.name}')"
            style="font-family: monospace; font-weight: bold;">
                    i
                </button>
            </div>

            <span class="text-[7px] text-slate-500 block leading-tight mt-0.5">
                ${getCatSubtitle(cat.name)}
            </span>

            <div class="text-[8px] font-bold text-slate-600 flex items-center gap-1 mb-1">
                <span class="opacity-70 uppercase tracking-tighter">Cumul/mois:</span>
                <span class="font-black text-slate-500">${totalPrevisionnel.toLocaleString()} ${currency}</span>
            </div>

            <div class="flex items-end justify-between py-0.5">
                <div class="text-2xl font-black text-white leading-none ${remaining <= 0 ? 'text-red-500 animate-pulse' : ''}">
                    ${remaining.toLocaleString()}
                    <span class="text-[10px] text-slate-600 font-normal uppercase">${currency}</span>
                </div>
                
                ${montantAjoute > 0 ? `
                <div class="text-[9px] font-bold text-emerald-500 bg-emerald-500/10 px-1.5 py-0.5 rounded-full border border-emerald-500/20">
                    +${montantAjoute.toLocaleString()}
                </div>
                ` : ''}
            </div>
        </div>

        <div class="mt-auto pt-1">
            <div class="flex justify-end items-center mb-1">
                <span class="text-[8px] font-black ${progress > 90 ? "text-red-500" : "text-slate-500"}">${Math.round(progress)}% utilisé</span>
            </div>
            <div class="w-full h-1 bg-slate-950/60 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 transition-all duration-700" 
                     style="width: ${progress}%">
                </div>
            </div>
        </div>

        ${isEditMode
        ? `
            <div class="mt-4 pt-2 border-t border-white/5">
                <input type="range" min="0" max="100" value="${currentPercent}" 
                    oninput="updatePercent(${cat.id}, this.value)"
                    class="w-full h-1 accent-amber-500 cursor-pointer">
            </div>
        `
        : ""
      }
    `;

    container.appendChild(card);
  }); // ← FIN results.forEach

  // ── Calcul Banque / Cash ───────────────────────────────────────────────
  let bank = 0,
    cash = 0;
  let bankSpent = 0,
    cashSpent = 0;
  let totalAlloue = 0,
    totalDepense = 0;

  results.forEach((c) => {
    const spent =
      typeof currentExpenses !== "undefined" && currentExpenses[c.id]
        ? parseInt(currentExpenses[c.id])
        : 0;

    const isProjet = c.name.toLowerCase().includes("projet");
    const isEpargne = c.name.toLowerCase().includes("épargne") || c.id === 1;

    const soldeReel = isProjet
      ? Math.max(0, projectCapital)
      : Math.max(0, c.amount - spent);

    totalAlloue += isProjet ? projectCapital : c.amount;
    totalDepense += spent;

    // LOGIQUE DE RÉPARTITION DANS LES CARTES DU HAUT
    if (isEpargne) {
      // 1. BANQUE (Réserves) : Uniquement l'Épargne Pure (Floutée)
      bank += soldeReel;
      bankSpent += spent;
    } else if (!isProjet) {
      // 2. POCHE (Dispo) : Tout le reste (Train de vie, Imprévus...) SAUF le Projet
      cash += soldeReel;
      cashSpent += spent;
    }
    // Note : Le projet n'est ajouté ni à 'bank' ni à 'cash' ici,
    // car il est géré exclusivement par updateVaultDisplay
  });

  const bankEl = document.getElementById("bankAmount");
  const cashEl = document.getElementById("cashAmount");
  const bankSpentEl = document.getElementById("bankSpent");
  const cashSpentEl = document.getElementById("cashSpent");

  if (bankEl) bankEl.innerText = bank.toLocaleString() + " " + currency;
  if (cashEl) cashEl.innerText = cash.toLocaleString() + " " + currency;
  if (bankSpentEl)
    bankSpentEl.innerText =
      bankSpent > 0 ? `▼ ${bankSpent.toLocaleString()} dépensés` : "";
  if (cashSpentEl)
    cashSpentEl.innerText =
      cashSpent > 0 ? `▼ ${cashSpent.toLocaleString()} dépensés` : "";


  // On calcule l'épargne actuelle dans les cartes (Montant - Dépenses)
  const totalEpargneCartes = results.reduce((acc, cat) => {
    const name = cat.name.toLowerCase();
    if (name.includes("épargne") && !name.includes("projet")) {
      const spent = typeof currentExpenses !== "undefined" ? (currentExpenses[cat.id] || 0) : 0;
      return acc + (cat.amount - spent);
    }
    return acc;
  }, 0);

  // PUISSANCE RÉELLE = Capital Projet + Soldes restants de TOUTES les catégories (y compris Train de Vie)
  // Cela représente tout l'argent disponible pour finir le mois.
  const puissanceFinanciereTotale = results.reduce((acc, c) => {
    const isProjet = c.name.toLowerCase().includes("projet");
    const spent = typeof currentExpenses !== "undefined" ? (currentExpenses[c.id] || 0) : 0;
    const budgetDisponible = isProjet
      ? (parseFloat(projectCapital) || 0)
      : (c.amount - spent);
    return acc + Math.max(0, budgetDisponible);
  }, 0);


  // ── Jauge de santé ────────────────────────────────────────────────────
  const gaugeBar = document.getElementById("gaugeBar");
  const gaugePercent = document.getElementById("gaugePercent");
  const gaugeAlert = document.getElementById("gaugeAlert");

  if (gaugePercent && puissanceFinanciereTotale > 0) {
    // On calcule l'intégrité du patrimoine face aux denses totales
    const pctIntact = Math.round(
      (puissanceFinanciereTotale / (puissanceFinanciereTotale + totalDepense)) * 100
    );

    const pctDepense = 100 - pctIntact;
    gaugePercent.innerText = pctIntact + "% intact";

    let barColor, alertStyle, alertMsg;

    if (pctDepense <= 30) {
      barColor = "text-emerald-400";
      alertStyle =
        "bg-emerald-500/10 text-emerald-400 border border-emerald-500/20";
      alertMsg = "Excellente discipline — ton budget est bien préservé.";
    } else if (pctDepense <= 60) {
      barColor = "text-amber-500";
      alertStyle = "bg-amber-500/10 text-amber-400 border border-amber-500/20";
      alertMsg = "Mi-parcours — surveille tes sorties cash.";
    } else if (pctDepense <= 85) {
      barColor = "text-orange-500";
      alertStyle =
        "bg-orange-500/10 text-orange-400 border border-orange-500/20";
      alertMsg = "Budget entamé — ralentis tes dépenses.";
    } else {
      barColor = "text-red-500";
      alertStyle = "bg-red-500/10 text-red-400 border border-red-500/20";
      alertMsg = "ALERTE — Il ne reste presque rien. Stop les dépenses.";
    }

    gaugePercent.className = `font-black text-sm ${barColor}`;
    
    if (gaugeBar) {
      gaugeBar.className = `h-full rounded-full transition-all duration-700 ease-out ${barColor.replace("text-", "bg-")}`;
      gaugeBar.style.width = `${pctIntact}%`;
    }

    if (gaugeAlert) {
      gaugeAlert.className = `text-[10px] text-center py-1.5 px-2 rounded-lg font-bold ${alertStyle}`;
      gaugeAlert.innerText = alertMsg;
    }

    gaugePercent.innerText = pctIntact + "% intact";
  }

  // ── CALCUL DES SOMMES POUR LE COFFRE ───────────────────────────────

  // 1. Épargne simple (cartes)
  const totalEpargneSeule = results.reduce((acc, cat) => {
    const name = cat.name.toLowerCase();
    if (name.includes("épargne") && !name.includes("projet")) {
      const spent = typeof currentExpenses !== "undefined" ? (currentExpenses[cat.id] || 0) : 0;
      return acc + (cat.amount - spent);
    }
    return acc;
  }, 0);

  // 2. Projet dynamique (Capital + répartition en cours)
  const totalProjetDynamique = results.reduce((acc, cat) => {
    if (cat.name.toLowerCase().includes("projet")) {
      const spent = typeof currentExpenses !== "undefined" ? (currentExpenses[cat.id] || 0) : 0;
      // On recalcule le montant exact affiché sur la carte Projet
      const montantAjoute = Math.round(total * (cat.percent / 100));
      return acc + (parseFloat(projectCapital) || 0) + montantAjoute;
    }
    return acc;
  }, 0);

  // On appelle le coffre en lui passant ces valeurs
  updateVaultDisplay(totalEpargneSeule, totalProjetDynamique);
  // ─── PREDICTION FIN DE MOIS ─────────────────────────────────────────
  // updateDailyPrediction();

  updateStatus(currentTotalPercent);
  if (!isSimulation && typeof generateFinancialReport === "function") generateFinancialReport();
}


// ─── COFFRE ────────────────────────────────────────────────────────────────

function updateVaultDisplay(totalSaved = 0, dynamicProject = null) {
  const projectEl = document.getElementById("totalProjectSaved");
  const progressBar = document.getElementById("vaultProgress");
  const goalAmountEl = document.getElementById("vaultGoalAmountDisplay");
  const goalLabelEl = document.getElementById("vaultGoalLabel");
  const deleteBtn = document.getElementById("deleteGoalBtn");
  const currency = document.getElementById("currencySelector")?.value || "F";

  if (projectEl) {
    // 1. CALCUL DES SOMMES
    // Si dynamicProject est fourni, c'est le montant "Miroir" de la carte Projet
    const totalProject = dynamicProject !== null ? dynamicProject : (parseFloat(projectCapital) || 0);
    const totalGlobal = totalProject + (parseFloat(totalSaved) || 0);

    // Animation de couleur lors du changement (basée sur le Capital Projet)
    const currentDisplayed = parseInt(projectEl.innerText.replace(/[^0-9]/g, "")) || 0;

    // Affichage principal : SOMME PROJET UNIQUEMENT
    projectEl.innerText = `${totalProject.toLocaleString()} ${currency}`;

    // Affichage miniature : PATRIMOINE (Somme Totale)
    const globalEl = document.getElementById("totalGlobalAmount");
    if (globalEl) {
      globalEl.innerText = `${totalGlobal.toLocaleString()} ${currency}`;
    }

    if (totalProject > currentDisplayed) {
      projectEl.classList.add("text-emerald-400", "scale-105");
      setTimeout(() => projectEl.classList.remove("scale-105"), 300);
    } else if (totalProject < currentDisplayed) {
      projectEl.classList.add("text-red-400");
    }

    // 2. RÉCUPÉRATION DE L'OBJECTIF (Goal)
    const savedGoal = JSON.parse(localStorage.getItem("wari_vault_goal") || "null");

    if (savedGoal) {
      const goalValue = parseFloat(savedGoal.amount) || 1000000;

      // Mise à jour des textes de la cible
      if (goalLabelEl) goalLabelEl.innerText = savedGoal.name || "Projet";
      if (goalAmountEl) goalAmountEl.innerText = `Objectif: ${goalValue.toLocaleString()} ${currency}`;

      // Calcul et mise à jour de la jauge
      if (progressBar) {
        const progress = Math.min((totalGlobal / goalValue) * 100, 100);
        progressBar.style.width = `${progress}%`;
      }

      // Affichage du bouton supprimer
      if (deleteBtn) deleteBtn.classList.remove("hidden");
    } else {
      // État par défaut si aucun objectif n'est défini
      if (goalLabelEl) goalLabelEl.innerText = "Définir";
      if (goalAmountEl) goalAmountEl.innerText = "Objectif: --";
      if (progressBar) progressBar.style.width = "0%";
      if (deleteBtn) deleteBtn.classList.add("hidden");
    }

    // Nettoyage des couleurs d'animation
    setTimeout(() => {
      projectEl.classList.remove("text-emerald-400", "text-red-400");
    }, 2000);
  }
}

// ─── PREDICTION FIN DE MOIS ────────────────────────────────────────────────

// function updateDailyPrediction() {
//   const predictionEl = document.getElementById("dailyPrediction");
//   if (!predictionEl) return;

//   const currency = document.getElementById("currencySelector")?.value || "F";

//   // Récupérer le cash disponible (Train de vie + Imprévu)
//   const cashAmountEl = document.getElementById("cashAmount");
//   let cashLeft = 0;
//   if (cashAmountEl) {
//     cashLeft = parseInt(cashAmountEl.innerText.replace(/[^0-9]/g, "")) || 0;
//   }

//   // Calculer les jours restants dans le mois
//   const now = new Date();
//   const year = now.getFullYear();
//   const month = now.getMonth();
//   const currentDay = now.getDate();
//   const daysInMonth = new Date(year, month + 1, 0).getDate();
//   const daysLeft = daysInMonth - currentDay + 1; // +1 pour inclure aujourd'hui

//   if (daysLeft <= 0 || cashLeft <= 0) {
//     predictionEl.innerHTML = `<span class="text-slate-500 text-[10px]">Saisis tes revenus pour voir ta prévision</span>`;
//     return;
//   }

//   const dailyBudget = Math.round(cashLeft / daysLeft);

//   // Prédiction simple : si l'utilisateur garde ce rythme
//   const predictedEndBalance = cashLeft - (dailyBudget * daysLeft);

//   let html = '';
//   let colorClass = '';

//   if (predictedEndBalance >= 0) {
//     colorClass = "text-emerald-400";
//     html = `📊 Prévision : <strong class="${colorClass}">+${predictedEndBalance.toLocaleString()} ${currency}</strong> en fin de mois<br>
//             <span class="text-[9px] text-slate-400">Soit ${dailyBudget.toLocaleString()} ${currency}/jour</span>`;
//   } else {
//     colorClass = "text-red-400";
//     const neededCut = Math.ceil(Math.abs(predictedEndBalance) / daysLeft);
//     html = `⚠️ Alerte : <strong class="${colorClass}">−${Math.abs(predictedEndBalance).toLocaleString()} ${currency}</strong> prévus<br>
//             <span class="text-[9px] text-amber-400">Réduis de ${neededCut} ${currency}/jour</span>`;
//   }

//   predictionEl.innerHTML = html;
// }


// Neutralisé — le versement est automatique dans saveBudget()
window.addToProjectVault = function () {
  return;
};

window.resetVault = function () {
  if (confirm("Voulez-vous réinitialiser votre capital accumulé à 0 ?")) {
    projectCapital = 0;
    saveBudget(true); // silent = true → pas d'alerte montant requis
    render();
  }
};

// ─── UTILITAIRES ───────────────────────────────────────────────────────────

window.updatePercent = function (id, val) {
  const cat = categories.find((c) => c.id === id);
  if (cat) {
    // 1. On sécurise la valeur (0 si vide)
    cat.percent = parseFloat(val) || 0;

    // 2. ON FORCE LE CALCUL IMMEDIAT du montant pour cette catégorie
    // On récupère le montant de l'input principal en direct
    const totalInput = document.getElementById("mainAmount");
    const total = parseFloat(totalInput.value) || 0;

    // On met à jour la propriété .amount de la catégorie pour le rendu
    cat.amount = Math.round((total * cat.percent) / 100);

    // 3. ON RELANCE LE RENDU (pour que les chiffres changent sur la carte)
    render(true);
    notifyUnsavedChanges();
  }
};

window.updateName = function (id, val) {
  const cat = categories.find((c) => c.id === id);
  if (cat) {
    cat.name = val;
    notifyUnsavedChanges();
  }
};

function notifyUnsavedChanges() {
  const saveBtn = document.querySelector('button[onclick="saveBudget()"]');
  if (saveBtn) {
    saveBtn.classList.remove(
      "bg-slate-800",
      "text-slate-300",
      "border-slate-700",
    );
    saveBtn.classList.add(
      "bg-blue-600/20",
      "text-blue-400",
      "border-blue-500/50",
      "animate-pulse",
      "shadow-[0_0_20px_rgba(59,130,246,0.3)]",
    );
    saveBtn.querySelector("span").innerText = "Valider les modifs ?";
  }
}

function updateStatus(total) {
  const status = document.getElementById("statusIndicator");
  const text = document.getElementById("statusText");
  const baseClass =
    "mt-4 flex items-center justify-center p-3 rounded-2xl transition-all duration-300 ";

  if (total === 100) {
    status.className = baseClass + "bg-emerald-500/10 text-emerald-500";
    text.innerHTML = `<span class="mr-2"></span> 100% - Ton argent est dompté !`;
  } else if (total === 0) {
    status.className = baseClass + "bg-slate-800/50 text-slate-500";
    text.innerHTML = `WARI-FINANCE : Prêt pour le calcul`;
  } else {
    const isOver = total > 100;
    status.className =
      baseClass +
      "bg-orange-500/10 border-orange-500/30 text-orange-500 animate-pulse";
    text.innerHTML = isOver
      ? `Oups ! ${total}% ? Tu dépenses plus que tu n'as.`
      : `${total}% répartis... Continue jusqu'à 100%.`;
  }
}

// ─── SAUVEGARDE ────────────────────────────────────────────────────────────

window.saveBudget = function (silent = false) {
  console.log("Démarrage de la sauvegarde...");

  const totalAAjouter = parseFloat(mainInput.value) || 0;
  const currentCurrency =
    document.getElementById("currencySelector")?.value || "F";

  // ─── SUGGESTION AUTO DES POURCENTAGES (1ère fois uniquement) ───
  const hasReceivedSuggestion = localStorage.getItem("wari_percent_suggested");

  if (!hasReceivedSuggestion && totalAAjouter > 0) {
    let suggested = { projet: 25, epargne: 15, imprevu: 10, train: 50 }; // valeurs par défaut

    if (totalAAjouter < 1500) {
      suggested = { projet: 10, epargne: 10, imprevu: 5, train: 75 };
    } else if (totalAAjouter < 3000) {
      suggested = { projet: 20, epargne: 15, imprevu: 10, train: 55 };
    } else {
      suggested = { projet: 30, epargne: 20, imprevu: 10, train: 40 };
    }

    // Appliquer les suggestions
    categories.forEach(cat => {
      const name = cat.name.toLowerCase();
      if (name.includes("projet")) cat.percent = suggested.projet;
      else if (name.includes("épargne")) cat.percent = suggested.epargne;
      else if (name.includes("imprévu")) cat.percent = suggested.imprevu;
      else if (name.includes("train")) cat.percent = suggested.train;
    });

    localStorage.setItem("wari_percent_suggested", "true");

    // Afficher une confirmation visuelle
    showToastMessage(`Pour ${totalAAjouter} F, j'ai ajusté les pourcentages. Modifie-les si besoin.`, "info");
  }


  if (totalAAjouter <= 0 && !silent) {
    alert("Veuillez entrer un montant avant de valider.");
    return;
  }

  categories = categories.map((cat) => {
    const partNouvelle = Math.round((totalAAjouter * cat.percent) / 100);
    const isProjet = cat.name.toLowerCase().includes("projet");

    if (isProjet) {
      if (partNouvelle > 0) {
        projectCapital += partNouvelle;
        addVaultTransaction(
          "in",
          partNouvelle,
          "Versement",
        );
        console.log(`Coffre alimenté : +${partNouvelle} ${currentCurrency}`);
      }
      return { ...cat, balance: 0 };
    }

    console.log(
      `Nouveau solde pour ${cat.name} : ${(cat.balance || 0) + partNouvelle}`,
    );
    return { ...cat, balance: (cat.balance || 0) + partNouvelle };
  });

  const dataToSave = {
    categories: categories,
    projectCapital: projectCapital,
    currency: currentCurrency,
    vaultTransactions: vaultTransactions,
    lastSavedMonth: new Date().toISOString().slice(0, 7),
  };

  localStorage.setItem("wari_budget_data", JSON.stringify(dataToSave));
  console.log("Données sauvegardées dans le LocalStorage");

  mainInput.value = "";
  render();
  console.log("Rendu rafraîchi");

  // 1. Sauvegarde des données générales
  fetch("config/save_data.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(dataToSave),
  })
    .then(response => {
      if (!response.ok) throw new Error(`Status: ${response.status}`);
      return response.json();
    })
    .then(() => {
      console.log("✅ Synchro serveur réussie");
      loadTrendChart();
    })
    .catch((err) => {
      console.error("❌ Erreur critique save_data :", err.message);
      if (!navigator.onLine || err.message.includes('Failed to fetch') || err.message.includes('NetworkError')) {
        if (typeof window.queueOfflineAction === 'function') {
          window.queueOfflineAction("config/save_data.php", dataToSave);
        }
      }
    });

  // 2. Ajout de la distribution
  if (totalAAjouter > 0) {
    fetch("config/add_distribution.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ amount: totalAAjouter }),
    })
      .then(response => {
        if (!response.ok) throw new Error(`Status: ${response.status}`);
        return response.json();
      })
      .then(() => {
        console.log("✅ Distribution synchronisée");
        loadTrendChart();
      })
      .catch((err) => console.error("❌ Erreur synchro distribution :", err.message));
  }
};

// ─── CHARGEMENT ────────────────────────────────────────────────────────────

function loadBudget() {
  // Vérifier si les éléments nécessaires sont présents
  if (!mainInput || !container) {
    console.log(
      "Wari-Finance: Page non principale détectée - chargement annulé",
    );
    return;
  }

  let data = null;

  if (typeof dbData !== "undefined" && dbData !== null) {
    data = typeof dbData === "string" ? JSON.parse(dbData) : dbData;
    console.log("Chargement depuis MySQL...");
  } else {
    const saved = localStorage.getItem("wari_budget_data");
    if (saved) {
      data = JSON.parse(saved);
      console.log("Chargement depuis LocalStorage...");
    }
  }

  if (data) {
    const currentMonth = new Date().toISOString().slice(0, 7);
    const lastSavedMonth = data.lastSavedMonth || null;

    // ── On charge les variables AVANT d'ajouter le séparateur ────────────
    if (data.categories) {
      categories = data.categories.map(cat => {
        // Migration émojis -> SVG
        if (cat.icon === "🚀") cat.icon = SVG_ICONS.rocket;
        if (cat.icon === "💰") cat.icon = SVG_ICONS.piggy;
        if (cat.icon === "🆘") cat.icon = SVG_ICONS.alert;
        if (cat.icon === "🏠") cat.icon = SVG_ICONS.home;
        return cat;
      });
    }
    projectCapital = data.projectCapital || 0;
    vaultTransactions = data.vaultTransactions || [];

    if (data.currency) {
      const selector = document.getElementById("currencySelector");
      if (selector) selector.value = data.currency;
    }

    // ── Reset au nouveau mois ─────────────────────────────────────────────
    if (lastSavedMonth && lastSavedMonth !== currentMonth) {
      console.log("Nouveau mois détecté — reset des balances");

      // // Séparateur ajouté APRÈS le chargement de vaultTransactions
      // vaultTransactions.unshift({
      //   date: lastSavedMonth,
      //   type: "separator",
      //   label: `── Clôture ${lastSavedMonth} ──`,
      //   amount: 0,
      // });

      setTimeout(() => {
        alert(
          "NOUVEAU MOIS, NOUVEL OBJECTIF !\n\nFélicitations Champion·ne, tes compteurs sont remis à zéro. Le coffre, lui, continue de grandir !",
        );
      }, 1000);

      categories = categories.map((cat) => ({ ...cat, balance: 0 }));
      data.lastSavedMonth = currentMonth;
      data.vaultTransactions = vaultTransactions;
      data.categories = categories;
      localStorage.setItem("wari_budget_data", JSON.stringify(data));
    }

    // Bannière d'explication au premier chargement (1 fois seulement)
    const hasSeenOnboarding = localStorage.getItem("wari_onboarding_seen_v2");
    if (!hasSeenOnboarding && !data) { // data = pas encore de budget sauvegardé
      setTimeout(() => {
        const banner = document.createElement("div");
        banner.id = "wari-onboarding-banner";
        banner.className = "fixed top-4 left-4 right-4 bg-slate-900/95 border border-blue-500/50 rounded-2xl p-3 z-50 backdrop-blur-md shadow-xl";
        banner.innerHTML = `
        <div class="flex justify-between items-start">
          <div class="text-[11px] text-slate-200 leading-relaxed">
            <strong class="text-blue-400">4 categories</strong><br/>
            Projet (avenir) · Epargne (securite) · Imprevu (urgences) · Train de vie (quotidien)
          </div>
          <button onclick="this.parentElement.parentElement.remove()" class="text-white/60 text-lg leading-none ml-2">✕</button>
        </div>
      `;
        document.body.appendChild(banner);

        // Disparait automatiquement après 6 secondes
        setTimeout(() => {
          const b = document.getElementById("wari-onboarding-banner");
          if (b) b.remove();
        }, 6000);

        localStorage.setItem("wari_onboarding_seen_v2", "true");
      }, 1000);
    }

    render();
    console.log("Interface Wari mise à jour.");
  } else {
    render();
  }
}

// ─── DÉMARRAGE ─────────────────────────────────────────────────────────────

// Exécuter uniquement si nous sommes sur la page principale
if (mainInput && container) {
  loadBudget();
  loadVaultHistory();
  updateGoalDisplay();
  loadTrendChart();
}

setTimeout(() => {
  isInitialLoad = false;
  console.log("Wari-Finance est prêt.");
}, 500);

// ─── HISTORIQUE COFFRE ─────────────────────────────────────────────────────

function loadVaultHistory() {
  fetch("config/get_vault_history.php")
    .then((res) => {
      const contentType = res.headers.get("content-type");

      // 1. Si la réponse n'est pas OK OU si ce n'est pas du JSON (ex: redirection HTML)
      if (!res.ok || (contentType && !contentType.includes("application/json"))) {
        if (res.status === 403 || (contentType && contentType.includes("text/html"))) {
          console.warn("Session expirée ou redirection détectée.");
          window.location.href = "config/auth.php";
          throw new Error("Session expirée (Réponse HTML reçue)");
        }
        throw new Error(`Erreur serveur: ${res.status}`);
      }

      return res.json();
    })
    .then((data) => {
      if (data && data.success && data.history) {
        const withSeparators = [];
        let lastMonth = null;

        data.history.forEach((tx) => {
          const moisTx = tx.date ? tx.date.split(" ")[1] : null;

          if (lastMonth && moisTx && moisTx !== lastMonth) {
            withSeparators.push({
              type: "separator",
              label: `── ${lastMonth} ──`,
              amount: 0,
              date: "",
            });
          }

          withSeparators.push(tx);
          lastMonth = moisTx;
        });

        vaultTransactions = withSeparators;
        renderVaultHistory();
      }
    })
    .catch((err) => {
      // On affiche uniquement le message d'erreur propre
      console.error("Erreur lors du chargement du coffre :", err.message);
    });
}

window.addVaultTransaction = function (type, amount, label) {
  const newTx = {
    date: new Date().toLocaleDateString("fr-FR", {
      day: "2-digit",
      month: "short",
    }),
    type: type,
    amount: amount,
    label: label,
  };

  vaultTransactions.unshift(newTx);
  if (vaultTransactions.length > 20) vaultTransactions.pop();
  renderVaultHistory();

  fetch("config/add_vault_transaction.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ type, amount, label }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (!data.success) console.error("Erreur synchro coffre:", data.error);
    });
};

function renderVaultHistory() {
  const container = document.getElementById("vaultHistory");
  const currency = document.getElementById("currencySelector")?.value || "F";
  if (!container) return;

  if (vaultTransactions.length === 0) {
    container.innerHTML = `<p class="text-[11px] text-slate-600 italic text-center">En attente de ton premier investissement...</p>`;
    return;
  }

  container.innerHTML = vaultTransactions
    .map((tx) => {
      if (tx.type === "separator") {
        return `
          <div class="flex items-center gap-2 py-1">
            <div class="flex-1 h-[1px] bg-slate-700/50"></div>
            <span class="text-[9px] text-slate-600 font-bold uppercase tracking-widest">${tx.label}</span>
            <div class="flex-1 h-[1px] bg-slate-700/50"></div>
          </div>
        `;
      }
      return `
        <div class="flex justify-between items-center py-2 px-3 bg-slate-900/20 rounded-lg border border-white/5 shadow-sm">
          <div class="flex flex-col">
            <span class="text-[11px] text-slate-200 font-semibold">${tx.label}</span>
            <span class="text-[8px] text-slate-500 font-medium">${tx.date}</span>
          </div>
          <span class="text-[11px] font-black ${tx.type === "in" ? "text-emerald-400" : "text-red-400"}">
            ${tx.type === "in" ? "+" : "−"} ${tx.amount.toLocaleString()} ${currency}
          </span>
        </div>
      `;
    })
    .join("");
}

// ─── DETTES ────────────────────────────────────────────────────────────────

function renderDebts() {
  const debtList = document.getElementById("debtList");
  const currency = document.getElementById("currencySelector")?.value || "F";

  if (!dbDebts || dbDebts.length === 0) {
    debtList.innerHTML = `<p class="text-slate-500 text-[11px] italic text-center uppercase tracking-widest">Paix totale : Aucune dette en cours.</p>`;
    return;
  }

  debtList.innerHTML = dbDebts
    .map(
      (debt) => `
    <div class="flex items-center justify-between bg-slate-800/50 p-2 rounded-xl transition-all">
      <div>
        <p class="text-[9px] font-black ${debt.type === "loan" ? "text-emerald-400" : "text-red-400"} uppercase tracking-wider">
          ${debt.type === "loan" ? "On me doit" : "Je dois"}
        </p>
        <p class="text-white font-bold text-sm">${debt.person_name}</p>
      </div>
      <div class="flex items-center gap-3">
        <span class="text-white font-black text-sm">${parseInt(debt.amount).toLocaleString()} ${currency}</span>
        <button onclick="openPayModal(${debt.id}, '${debt.person_name}', ${debt.amount}, '${debt.type}')"
                class="w-8 h-8 rounded-full bg-slate-700/50 flex items-center justify-center hover:bg-emerald-600 hover:scale-110 active:scale-95 transition-all shadow-lg">
          ${SVG_ICONS.money}
        </button>
      </div>
    </div>
  `,
    )
    .join("");
}

document.addEventListener("DOMContentLoaded", renderDebts);

// ─── MODÈLE ────────────────────────────────────────────────────────────────

function applyModel(modelKey) {
  const models = {
    wari: [
      { id: 3, name: "Projet", percent: 25, icon: SVG_ICONS.rocket },
      { id: 1, name: "Épargne", percent: 15, icon: SVG_ICONS.piggy },
      { id: 4, name: "Imprévu", percent: 10, icon: SVG_ICONS.alert },
      { id: 2, name: "Train de vie", percent: 50, icon: SVG_ICONS.home },
    ],
  };
  if (confirm("Appliquer ce modèle ?")) {
    categories = JSON.parse(JSON.stringify(models[modelKey]));
    render();
    saveBudget(true);
  }
}

mainInput.addEventListener("input", () => render(true));

document.addEventListener("DOMContentLoaded", () => {
  // 1. Initialisation de ton état de chargement
  isInitialLoad = false;

  // 2. Lancement des notes de mise à jour (V55)
  // On met un petit délai pour ne pas agresser l'utilisateur dès la première seconde
  checkReleaseNotes();

  // 3. Tes autres vérifications (Radar, etc.)
  const lastClosed = localStorage.getItem("wari_push_modal_closed");
  // ... reste de ton code existant ...
});

// ─── DÉPENSES ──────────────────────────────────────────────────────────────

window.openExpenseModal = function () {
  const modal = document.getElementById("expenseModal");
  const select = document.getElementById("expCategory");
  const currency = document.getElementById("currencySelector")?.value || "F";

  document
    .querySelectorAll(".currencyLabel")
    .forEach((el) => (el.innerText = currency));

  select.innerHTML = categories
    .map((cat) => `<option value="${cat.id}">${cat.name}</option>`)
    .join("");

  modal.classList.remove("hidden");
  modal.classList.add("flex");
};

window.submitExpense = function () {
  const amountInput = document.getElementById("expAmount");
  const noteInput = document.getElementById("expNote");
  const catId = document.getElementById("expCategory").value;
  const amount = parseInt(amountInput.value);
  const note = noteInput?.value || "Dépense";
  const currency = document.getElementById("currencySelector")?.value || "F";

  if (!amount || amount <= 0) return alert("Montant invalide");

  // --- MISE À JOUR OPTIMISTE ---
  if (!currentExpenses[catId]) currentExpenses[catId] = 0;
  currentExpenses[catId] = parseInt(currentExpenses[catId]) + amount;

  const cat = categories.find((c) => c.id == catId);
  if (cat && cat.name.toLowerCase().includes("projet")) {
    projectCapital = Math.max(0, projectCapital - amount);
    addVaultTransaction("out", amount, note);
    saveBudget(true); // gère sa propre file d'attente
  }

  closeExpenseModal();
  amountInput.value = "";
  if (noteInput) noteInput.value = "";
  render();

  const bodyData = { amount, category_id: catId, description: note };

  if (!navigator.onLine) {
    if (typeof window.queueOfflineAction === 'function') {
      window.queueOfflineAction("config/add_expense.php", bodyData);
    }
    return;
  }

  fetch("config/add_expense.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(bodyData),
  })
    .then((res) => res.json())
    .then((data) => {
      if (!data.success) {
        console.error("Erreur serveur :", data.error);
      } else {
        loadTrendChart();
      }
    })
    .catch((error) => {
      console.warn("Erreur réseau dépense, mise en file d'attente.");
      if (typeof window.queueOfflineAction === 'function') {
        window.queueOfflineAction("config/add_expense.php", bodyData);
      }
    });
};

window.closeExpenseModal = function () {
  const modal = document.getElementById("expenseModal");
  modal.classList.add("hidden");
  modal.classList.remove("flex");
};

// ─── DETTES MODALS ─────────────────────────────────────────────────────────

window.openDebtModal = () => {
  const currency = document.getElementById("currencySelector")?.value || "F";
  document
    .querySelectorAll(".currencyLabel")
    .forEach((el) => (el.innerText = currency));
  document.getElementById("debtModal").classList.replace("hidden", "flex");
};

window.closeDebtModal = () => {
  document.getElementById("debtPerson").value = "";
  document.getElementById("debtAmount").value = "";
  document.getElementById("debtDueDate").value = "";
  document.getElementById("debtModal").classList.replace("flex", "hidden");
};

window.submitDebt = async () => {
  const person = document.getElementById("debtPerson").value;
  const amount = document.getElementById("debtAmount").value;
  const type = document.getElementById("debtType").value;
  const currency = document.getElementById("currencySelector")?.value || "F";

  if (!person || !amount) return alert("Remplissez tous les champs");

  const msg =
    type === "loan"
      ? `Tu confirmes avoir prêté ${amount} ${currency} à ${person} ?`
      : `Tu confirmes devoir ${amount} ${currency} à ${person} ? Sois rigoureux sur le remboursement.`;

  if (!confirm(msg)) return;

  const bodyData = { person, amount, type };

  try {
    if (!navigator.onLine) throw new Error("Offline");
    const res = await fetch("config/add_debt.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(bodyData),
    });
    const data = await res.json();
    if (data.success) {
      window.location.href = window.location.href.split("?")[0] + "?t=" + Date.now();
    }
  } catch (error) {
    if (typeof window.queueOfflineAction === 'function') {
      window.queueOfflineAction("config/add_debt.php", bodyData);
      alert("Dette enregistrée hors ligne ! Elle sera synchronisée plus tard.");
      closeDebtModal();
    }
  }
};

// markAsPaid remplacé par submitPartialPay — neutralisé pour éviter tout crash
window.markAsPaid = async (id) => {
  console.warn("markAsPaid() est obsolète — utiliser submitPartialPay()");
};

window.openPayModal = (id, name, currentAmount, type) => {
  const currency = document.getElementById("currencySelector")?.value || "F";
  document.querySelectorAll(".currencyLabel").forEach((el) => {
    el.innerText = currency;
  });

  document.getElementById("payDebtId").value = id;
  document.getElementById("payDebtType").value = type;

  const action = type === "loan" ? "Récupération de" : "Paiement à";
  document.getElementById("payModalTarget").innerText =
    `${action} ${name} (Reste : ${currentAmount.toLocaleString()} ${currency})`;

  document.getElementById("payModal").classList.replace("hidden", "flex");
};

window.closePayModal = () =>
  document.getElementById("payModal").classList.replace("flex", "hidden");

window.submitPartialPay = async () => {
  const id = document.getElementById("payDebtId").value;
  const amount = document.getElementById("payPartAmount").value;
  const type = document.getElementById("payDebtType").value;
  const currency = document.getElementById("currencySelector")?.value || "F";

  if (!amount || amount <= 0) {
    alert("Veuillez saisir un montant valide.");
    return;
  }

  const actionLabel = type === "loan" ? "reçu" : "remboursé";
  const confirmMsg = `Tu confirmes avoir ${actionLabel} ${amount} ${currency} ?\nChaque petit pas compte pour ta liberté financière.`;
  if (!confirm(confirmMsg)) return;

  const bodyData = { id, amount, type };

  try {
    if (!navigator.onLine) throw new Error("Offline");
    const res = await fetch("config/partial_pay.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(bodyData),
    });
    const data = await res.json();
    if (data.success) {
      window.location.href = window.location.pathname + "?updated=" + Date.now();
    } else {
      alert("Erreur lors de l'enregistrement. Réessaie.");
    }
  } catch (error) {
    if (typeof window.queueOfflineAction === 'function') {
      window.queueOfflineAction("config/partial_pay.php", bodyData);
      alert("Paiement enregistré hors ligne ! Il sera synchronisé plus tard.");
      closePayModal();
    }
  }
};

// ─── COACH ─────────────────────────────────────────────────────────────────

function generateFinancialReport() {
  if (!categories || categories.length === 0) return;

  const scoreElement = document.getElementById("disciplineScore");
  const coachMessageElement = document.getElementById("aiCoachMessage");
  const currency = document.getElementById("currencySelector")?.value || "F";

  if (!scoreElement) return;

  const totalCats = categories.length;
  let respectedCats = 0;
  let totalOverspent = 0;
  let savingSacrificed = false;

  categories.forEach((cat) => {
    const spent = currentExpenses[cat.id] || 0;
    const name = cat.name.toLowerCase();
    const isProjet = name.includes("projet");
    const planned = isProjet ? projectCapital : cat.balance || 0;

    if (planned === 0 && !isProjet) return;

    if (spent <= planned) {
      respectedCats++;
    } else {
      totalOverspent += spent - planned;
      if (
        name.includes("épargne") ||
        name.includes("projet") ||
        name.includes("investissement")
      ) {
        savingSacrificed = true;
      }
    }
  });

  let finalScore = Math.round((respectedCats / totalCats) * 10);
  if (savingSacrificed) finalScore = Math.max(0, finalScore - 4);

  scoreElement.innerText = `${finalScore}/10`;
  scoreElement.className =
    "text-xl font-black transition-all duration-500 " +
    (finalScore >= 8
      ? "text-emerald-400"
      : finalScore >= 5
        ? "text-yellow-500"
        : "text-red-500");

  if (!coachMessageElement) return;

  const aSolde =
    categories.some((c) => (c.balance || 0) > 0) || projectCapital > 0;

  if (!aSolde) {
    coachMessageElement.innerHTML = `<span class="italic text-slate-500">Enregistre tes revenus pour activer le coaching Wari personnalisé.</span>`;
    return;
  }

  // ── CALCUL DU CONTEXTE TEMPOREL ─────────────────────────────────────
  const now = new Date();
  const year = now.getFullYear();
  const month = now.getMonth();
  const day = now.getDate();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const daysLeft = daysInMonth - day + 1; // On compte aujourd'hui

  // On récupère le cash disponible (Poche)
  const cashAmountEl = document.getElementById("cashAmount");
  const cashValue = parseInt(cashAmountEl?.innerText.replace(/[^0-9]/g, "")) || 0;
  const budgetQuotidien = Math.round(cashValue / daysLeft);

  // On récupère les dettes
  const totalDettes = (typeof dbDebts !== "undefined" ? dbDebts : [])
    .reduce((acc, d) => acc + (parseInt(d.amount) || 0), 0);

  // On prépare les données pour l'IA
  const summary = categories
    .map(
      (c) =>
        `${c.name}: ${currentExpenses[c.id] || 0} dépensés / ${c.name.toLowerCase().includes("projet") ? projectCapital : c.balance || 0} prévus`,
    )
    .join(", ");

  const statusData = {
    score: finalScore,
    overspent: totalOverspent,
    summary: summary,
    currency: currency,
    has_sacrificed_saving: savingSacrificed,
    temporal: {
      current_day: day,
      days_in_month: daysInMonth,
      days_left: daysLeft
    },
    daily_budget: budgetQuotidien,
    total_debts: totalDettes
  };

  fetchAiCoachAdvice(statusData);
}

// Nouvelle fonction pour appeler Gemini
let aiCoachAdviceTimeout = null;
async function fetchAiCoachAdvice(data) {
  const coachMessageElement = document.getElementById("aiCoachMessage");
  const gaugeAlert = document.getElementById("gaugeAlert");
  if (!coachMessageElement) return;

  if (aiCoachAdviceTimeout) {
    clearTimeout(aiCoachAdviceTimeout);
  }

  aiCoachAdviceTimeout = setTimeout(async () => {
    try {
      const formData = new FormData();
      formData.append('action', 'get_coach_advice');
      formData.append('data', JSON.stringify(data));

      const res = await fetch('academy-admin/ai_gateway.php', {
        method: 'POST',
        body: formData
      });
      const result = await res.json();

      if (result && result.message) {
        // Affichage du message principal (Ton Wari)
        coachMessageElement.innerHTML = `
          <div class="space-y-1">
            <p class="text-slate-200">"${result.message}"</p>
            ${result.prediction ? `<p class="text-[11px] text-amber-400/80 font-bold">${result.prediction}</p>` : ''}
            ${result.dette_conseil ? `<p class="text-[11px] text-blue-400/80 font-bold">${result.dette_conseil}</p>` : ''}
            ${result.academy_reco ? `<p class="text-[11px] text-emerald-400/80 font-bold">Je te recommande ce cours : "${result.academy_reco}"</p>` : ''}
          </div>
        `;

        // Mise à jour de l'alerte de la jauge si l'IA détecte un danger
        if (result.alerte_rouge && gaugeAlert) {
          gaugeAlert.innerHTML = `${result.alerte_rouge}`;
          gaugeAlert.classList.add('animate-bounce');
        }
      } else {
        // Fallback message si l'API retourne une erreur (rate limits, etc.)
        coachMessageElement.innerHTML = `
          <div class="space-y-1">
            <p class="text-slate-300 italic">"Reste concentré sur tes objectifs ! Chaque franc économisé aujourd'hui construit ta liberté de demain. N'oublie pas de planifier tes enveloppes."</p>
          </div>
        `;
      }
    } catch (e) {
      console.error("Erreur Coach Wari:", e);
      // Fallback message si exception réseau/serveur
      coachMessageElement.innerHTML = `
        <div class="space-y-1">
          <p class="text-slate-300 italic">"Reste concentré sur tes objectifs ! Chaque franc économisé aujourd'hui construit ta liberté de demain. N'oublie pas de planifier tes enveloppes."</p>
        </div>
      `;
    }
  }, 1000); // 1-second debounce
}

// ─── MODE ÉDITION ──────────────────────────────────────────────────────────

window.toggleEditMode = function () {
  isEditMode = !isEditMode;
  const btn = document.getElementById("lockBtn");

  if (isEditMode) {
    // État ÉDITION (Ambre)
    btn.innerHTML = `<span class="flex items-center gap-1">${SVG_ICONS.lockOpen} <span class="text-amber-500">ÉDITION</span></span>`;
    btn.className =
      "flex items-center gap-1 px-2 py-1 rounded-full bg-amber-500/10 border border-amber-500/50 transition-all scale-105 shadow-[0_0_15px_rgba(245,158,11,0.2)]";
  } else {
    // État LECTURE (Slate)
    btn.innerHTML = `<span class="flex items-center gap-1">${SVG_ICONS.lock} <span class="text-slate-400">LECTURE</span></span>`;
    btn.className =
      "flex items-center gap-1 px-2 py-1 rounded-full bg-slate-800 border border-slate-700 transition-all shadow-lg";
    saveBudget(true);
  }

  render();
};

// ─── OBJECTIFS COFFRE ──────────────────────────────────────────────────────

window.openGoalModal = function () {
  const existing = JSON.parse(
    localStorage.getItem("wari_vault_goal") || "null",
  );
  if (existing) {
    document.getElementById("goalLabel").value = existing.label;
    document.getElementById("goalAmount").value = existing.amount;
  } else {
    document.getElementById("goalLabel").value = "";
    document.getElementById("goalAmount").value = "";
  }
  document.getElementById("goalModal").classList.replace("hidden", "flex");
};

window.closeGoalModal = function () {
  document.getElementById("goalModal").classList.replace("flex", "hidden");
};

window.saveGoal = function () {
  const label = document.getElementById("goalLabel").value.trim();
  const amount = parseInt(document.getElementById("goalAmount").value);
  if (!label || !amount) return alert("Remplis les deux champs.");
  localStorage.setItem("wari_vault_goal", JSON.stringify({ label, amount }));
  updateGoalDisplay();
  closeGoalModal();
};

function updateGoalDisplay() {
  const currency = document.getElementById("currencySelector")?.value || "F";
  const goal = JSON.parse(localStorage.getItem("wari_vault_goal") || "null");

  // Correction des IDs pour correspondre à ton HTML
  const labelEl = document.getElementById("vaultGoalLabel");
  const amountEl = document.getElementById("vaultGoalAmountDisplay"); // Ajout de "Display"
  const progressBar = document.getElementById("vaultProgress");
  const deleteBtn = document.getElementById("deleteGoalBtn");

  // Si pas d'objectif, on reset l'affichage proprement
  if (!goal) {
    if (labelEl) labelEl.innerText = "Définir";
    if (amountEl) amountEl.innerText = "Objectif: --";
    if (progressBar) progressBar.style.width = "0%";
    if (deleteBtn) {
      deleteBtn.classList.add("hidden");
      deleteBtn.classList.remove("flex");
    }
    return;
  }

  // Mise à jour si l'objectif existe
  if (labelEl) labelEl.innerText = goal.label || goal.name || "Objectif";

  if (amountEl) {
    // On affiche le montant cible de l'objectif
    amountEl.innerText = `Objectif: ${goal.amount.toLocaleString()} ${currency}`;
  }

  if (progressBar) {
    const progress = Math.min((projectCapital / goal.amount) * 100, 100);
    progressBar.style.width = `${progress}%`;
  }

  if (deleteBtn) {
    deleteBtn.classList.remove("hidden");
    deleteBtn.classList.add("flex");
  }
}

window.deleteGoal = function () {
  if (!confirm("Supprimer cet objectif ?")) return;

  // 1. On supprime du stockage
  localStorage.removeItem("wari_vault_goal");

  // 2. On récupère les éléments avec les bons IDs
  const labelEl = document.getElementById("vaultGoalLabel");
  const amountEl = document.getElementById("vaultGoalAmountDisplay"); // L'ID de ton nouveau HTML
  const progressBar = document.getElementById("vaultProgress");
  const deleteBtn = document.getElementById("deleteGoalBtn");

  // 3. On reset l'affichage SANS planter (avec des vérifications "if")
  if (labelEl) labelEl.innerText = "Définir";

  if (amountEl) amountEl.innerText = "Objectif: --";

  if (progressBar) progressBar.style.width = "0%";

  if (deleteBtn) {
    deleteBtn.classList.add("hidden");
    deleteBtn.classList.remove("flex");
  }

  // 4. On rafraîchit le reste de l'interface
  if (typeof updateVaultDisplay === "function") {
    updateVaultDisplay();
  }

  console.log("Objectif supprimé avec succès.");
};

// ─── HISTORIQUE MENSUEL ────────────────────────────────────────────────────

window.openHistoryModal = function () {
  const modal = document.getElementById("historyModal");
  modal.classList.remove("hidden");
  modal.classList.add("flex");
  loadMonthlyHistory();
};

window.closeHistoryModal = function () {
  const modal = document.getElementById("historyModal");
  modal.classList.add("hidden");
  modal.classList.remove("flex");
};

// ─── GRAPHIQUE D'ÉVOLUTION ──────────────────────────────────────────────────

function loadTrendChart() {
  fetch("config/get_history.php?months=6")
    .then((res) => {
      if (!res.ok) throw new Error("Status: " + res.status);
      return res.json();
    })
    .then((data) => {
      if (data && data.success && data.history) {
        renderTrendChart(data.history);
      }
    })
    .catch((err) => {
      console.error("Erreur lors du chargement du graphique d'évolution :", err.message);
    });
}

function renderTrendChart(history) {
  const svg = document.getElementById("trendChartSvg");
  const loader = document.getElementById("chartLoader");
  if (!svg) return;

  if (!history || history.length === 0) {
    if (loader) {
      loader.innerText = "Ajoute des revenus et dépenses pour tracer ton évolution.";
      loader.className = "absolute text-slate-500 text-[10px] italic text-center px-4";
    }
    return;
  }

  // Masquer le loader et afficher le SVG
  if (loader) loader.classList.add("hidden");
  svg.classList.remove("opacity-0");
  svg.classList.add("opacity-100");

  const width = 400;
  const height = 140;
  const paddingLeft = 35;
  const paddingRight = 15;
  const paddingTop = 15;
  const paddingBottom = 20;

  const chartWidth = width - paddingLeft - paddingRight;
  const chartHeight = height - paddingTop - paddingBottom;

  // Inverser l'historique pour aller du plus ancien au plus récent
  const data = [...history].reverse();
  const n = data.length;

  const currency = document.getElementById("currencySelector")?.value || "F";

  // Calcul du max pour l'échelle Y
  let maxVal = 0;
  data.forEach((month) => {
    const rev = parseFloat(month.total_distributed) || 0;
    const exp = parseFloat(month.total_spent) || 0;
    maxVal = Math.max(maxVal, rev, exp);
  });

  // Si aucun montant, on fixe une échelle par défaut
  if (maxVal === 0) maxVal = 10000;

  // Arrondir maxVal à une valeur supérieure propre
  const exponent = Math.floor(Math.log10(maxVal));
  const magnitude = Math.pow(10, Math.max(0, exponent - 1)) || 1;
  const roundedMax = Math.ceil(maxVal / magnitude) * magnitude;
  maxVal = roundedMax;

  // Génération du contenu SVG
  let svgContent = "";

  // 1. Grille et axes Y (3 niveaux)
  const gridLines = [0, 0.5, 1];
  gridLines.forEach((ratio) => {
    const y = paddingTop + chartHeight * (1 - ratio);
    const val = Math.round(maxVal * ratio);
    
    // Ligne horizontale pointillée
    svgContent += `<line x1="${paddingLeft}" y1="${y}" x2="${width - paddingRight}" y2="${y}" stroke="rgba(255,255,255,0.06)" stroke-dasharray="2,2" stroke-width="1" />`;
    
    // Libellé de l'axe Y
    let formattedVal = val >= 1000000 ? (val / 1000000).toFixed(1) + "M" : val >= 1000 ? (val / 1000).toFixed(0) + "k" : val;
    svgContent += `<text x="${paddingLeft - 6}" y="${y + 3}" fill="#64748b" font-size="8" font-weight="800" text-anchor="end">${formattedVal}</text>`;
  });

  // 2. Définitions des dégradés (Bar Gradients)
  svgContent += `
    <defs>
      <linearGradient id="revGrad" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0%" stop-color="#10B981" />
        <stop offset="100%" stop-color="#059669" />
      </linearGradient>
      <linearGradient id="expGrad" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0%" stop-color="#EF4444" />
        <stop offset="100%" stop-color="#DC2626" />
      </linearGradient>
    </defs>
  `;

  // 3. Dessiner les barres et libellés X
  const groupWidth = chartWidth / n;
  // Largeur de chaque barre (limité pour garder un bon aspect si n est petit)
  const barWidth = Math.min(14, (groupWidth * 0.7 - 2) / 2);

  for (let i = 0; i < n; i++) {
    const groupCenter = paddingLeft + (i * groupWidth) + (groupWidth / 2);
    
    const rev = parseFloat(data[i].total_distributed) || 0;
    const exp = parseFloat(data[i].total_spent) || 0;

    // Hauteurs proportionnelles (minimum 2px pour la visibilité des petits montants non nuls)
    const hRev = rev > 0 ? Math.max(2, (rev / maxVal) * chartHeight) : 0;
    const hExp = exp > 0 ? Math.max(2, (exp / maxVal) * chartHeight) : 0;

    // Coordonnées Y
    const yRev = paddingTop + chartHeight - hRev;
    const yExp = paddingTop + chartHeight - hExp;

    // Coordonnées X (côte à côte avec 2px d'espace de séparation)
    const xRev = groupCenter - barWidth - 1;
    const xExp = groupCenter + 1;

    // Dessiner la barre Revenus (Vert)
    svgContent += `
      <g class="group">
        <title>Revenus : ${rev.toLocaleString()} ${currency}</title>
        <rect x="${xRev}" y="${yRev}" width="${barWidth}" height="${hRev}" rx="2" fill="url(#revGrad)" class="cursor-pointer transition-opacity duration-200 hover:opacity-85" />
      </g>
    `;

    // Dessiner la barre Dépenses (Rouge)
    svgContent += `
      <g class="group">
        <title>Dépenses : ${exp.toLocaleString()} ${currency}</title>
        <rect x="${xExp}" y="${yExp}" width="${barWidth}" height="${hExp}" rx="2" fill="url(#expGrad)" class="cursor-pointer transition-opacity duration-200 hover:opacity-85" />
      </g>
    `;

    // Libellé de l'axe X (Mois) - centré sous le groupe
    let shortLabel = "";
    if (data[i].label) {
      const m = data[i].label.split(" ")[0];
      shortLabel = m.length > 4 ? m.substring(0, 3) + "." : m;
    }
    svgContent += `<text x="${groupCenter}" y="${height - 4}" fill="#64748b" font-size="8" font-weight="800" text-anchor="middle">${shortLabel}</text>`;
  }

  svg.innerHTML = svgContent;
}

function loadMonthlyHistory(months = 6) {
  fetch(`config/get_history.php?months=${months}`)
    .then((res) => res.json())
    .then((data) => {
      const container = document.getElementById("historyContent");
      const currency =
        document.getElementById("currencySelector")?.value || "F";

      if (!data.success || data.history.length === 0) {
        container.innerHTML = `<p class="text-slate-500 text-[11px] italic text-center py-4">Aucun historique disponible.</p>`;
        return;
      }

      container.innerHTML = data.history
        .map(
          (month) => {
            // --- ANALYSE DES MICRO-DÉPENSES (< 1000 F) ---
            const microExpenses = month.expenses ? month.expenses.filter(e => e.amount < 1000) : [];
            const microCount = microExpenses.length;
            const microSum = microExpenses.reduce((acc, curr) => acc + curr.amount, 0);

            return `
            <div class="bg-slate-800/40 p-3.5 rounded-2xl border border-slate-700/40 mb-4 shadow-md">

              <!-- En-tête du mois -->
              <div class="flex items-center justify-between mb-3 border-b border-white/5 pb-2">
                <p class="text-amber-400 font-black text-[12px] uppercase tracking-widest">
                  ${month.label}
                </p>
                <span class="text-[8.5px] font-bold uppercase tracking-wider bg-slate-800/80 text-slate-400 px-2.5 py-1 rounded-full border border-white/5">
                  ${month.nb_repartitions} répartition${month.nb_repartitions > 1 ? "s" : ""}
                </span>
              </div>

              <!-- Totaux du mois -->
              <div class="grid grid-cols-3 gap-2 bg-slate-900/50 p-2 rounded-xl border border-white/5 mb-3 text-center">
                <div>
                  <p class="text-[8px] text-slate-500 font-bold uppercase tracking-wider">Total réparti</p>
                  <p class="text-[10px] font-black text-white mt-0.5">
                    ${parseInt(month.total_distributed).toLocaleString()} ${currency}
                  </p>
                </div>
                <div>
                  <p class="text-[8px] text-slate-500 font-bold uppercase tracking-wider">Total dépensé</p>
                  <p class="text-[10px] font-black text-red-400 mt-0.5">
                    −${parseInt(month.total_spent).toLocaleString()} ${currency}
                  </p>
                </div>
                <div>
                  <p class="text-[8px] text-slate-500 font-bold uppercase tracking-wider">Préservé</p>
                  <p class="text-[10px] font-black text-emerald-400 mt-0.5">
                    ${parseInt(month.total_saved).toLocaleString()} ${currency}
                  </p>
                </div>
              </div>

              <!-- Nudge Micro-dépenses -->
              ${microCount > 0 ? `
              <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-2.5 mb-3 text-[10px] text-amber-300 leading-normal flex items-start gap-2 select-none">
                <span class="text-xs">⚠️</span>
                <span>
                  <b>Micro-dépenses :</b> Tu as fait <b>${microCount}</b> petites dépenses (&lt; 1&nbsp;000&nbsp;${currency}) ce mois-ci, totalisant <b>${microSum.toLocaleString()} ${currency}</b>. C'est là que ton argent s'échappe en silence !
                </span>
              </div>
              ` : ''}

              <!-- Liste des Dépenses Détaillées par Jour -->
              ${month.expenses && month.expenses.length > 0 ? (() => {
                // Grouper les dépenses par jour
                const expensesByDay = {};
                month.expenses.forEach(e => {
                  const day = e.date_day_label;
                  if (!expensesByDay[day]) {
                    expensesByDay[day] = {
                      total: 0,
                      items: []
                    };
                  }
                  expensesByDay[day].total += e.amount;
                  expensesByDay[day].items.push(e);
                });

                return `
                <div class="mb-3">
                  <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-2">💸 Dépenses Détaillées Par Jour</p>
                  <div class="space-y-3 max-h-[220px] overflow-y-auto custom-scrollbar pr-1">
                    ${Object.keys(expensesByDay).map(day => {
                  const dayData = expensesByDay[day];
                  return `
                      <div class="bg-slate-900/60 border border-white/5 rounded-xl p-2">
                        <!-- Entête du Jour avec son total journalier -->
                        <div class="flex justify-between items-center mb-1.5 border-b border-white/5 pb-1 select-none">
                          <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">📅 Le ${day}</span>
                          <span class="text-[9.5px] font-black text-red-400">-${dayData.total.toLocaleString()} ${currency}</span>
                        </div>
                        
                        <!-- Liste des dépenses du jour -->
                        <div class="space-y-1">
                          ${dayData.items.map(e => {
                    const cat = categories.find(c => c.id == e.category_id);
                    const catName = cat ? cat.name : 'Dépense';

                    // Couleur visuelle selon la catégorie pour une trace visuelle instantanée
                    let catColor = 'bg-amber-500/10 text-amber-400 border-amber-500/20';
                    if (catName.toLowerCase().includes('projet')) {
                      catColor = 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20';
                    } else if (catName.toLowerCase().includes('imprévu') || catName.toLowerCase().includes('urgenc')) {
                      catColor = 'bg-red-500/10 text-red-400 border-red-500/20';
                    } else if (catName.toLowerCase().includes('dette') || catName.toLowerCase().includes('crédit')) {
                      catColor = 'bg-orange-500/10 text-orange-400 border-orange-500/20';
                    } else if (catName.toLowerCase().includes('épargne')) {
                      catColor = 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
                    }

                    return `
                            <div class="flex justify-between items-center py-1.5 px-2 bg-slate-950/40 rounded-lg border border-white/5 hover:border-white/10 transition-colors gap-2">
                              <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5">
                                  <span class="w-1.5 h-1.5 rounded-full bg-red-400 shrink-0"></span>
                                  <span class="text-[9.5px] font-bold text-slate-200 truncate block max-w-[130px]" title="${e.description}">
                                    ${e.description}
                                  </span>
                                </div>
                                <div class="flex items-center gap-2 mt-1 select-none">
                                  <!-- Badge Catégorie visuel et coloré -->
                                  <span class="text-[7.5px] font-black uppercase px-1.5 py-0.5 rounded border ${catColor} tracking-wider font-semibold">
                                    ${catName}
                                  </span>
                                  <span class="text-[7px] text-slate-500 font-medium">À ${e.time_label}</span>
                                </div>
                              </div>
                              <span class="text-[10px] font-black text-red-400 shrink-0">
                                -${e.amount.toLocaleString()} ${currency}
                              </span>
                            </div>
                            `;
                  }).join('')}
                        </div>
                      </div>
                      `;
                }).join('')}
                  </div>
                </div>
                `;
              })() : `
              <p class="text-[8.5px] text-slate-500 italic text-center py-2">Aucune dépense enregistrée ce mois.</p>
              `}

              <!-- Liste des Répartitions (Revenus) -->
              ${month.details && month.details.length > 0 ? `
              <div>
                <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1.5">💰 Revenus Répartis</p>
                <div class="space-y-1 max-h-[100px] overflow-y-auto custom-scrollbar pr-1">
                  ${month.details.map(d => `
                    <div class="flex justify-between items-center px-2 py-1 bg-slate-900/30 rounded-lg border border-white/5 text-[9px]">
                      <div class="flex items-center gap-1.5">
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                        <span class="text-slate-400">Répartition du ${d.datetime}</span>
                      </div>
                      <span class="font-black text-emerald-400">
                        +${d.amount.toLocaleString()} ${currency}
                      </span>
                    </div>
                  `).join('')}
                </div>
              </div>
              ` : ''}

            </div>
            `;
          }
        )
        .join("");
    })
    .catch((err) => {
      console.error(err);
      document.getElementById("historyContent").innerHTML =
        `<p class="text-red-400 text-[11px] italic text-center py-4">Erreur de chargement.</p>`;
    });
}

// ─── NOTIFICATIONS ─────────────────────────────────────────────────────────

function requestNotificationPermission() {
  if ("Notification" in window) Notification.requestPermission();
}
requestNotificationPermission();

function showWariNotification(title, message, score) {
  if (Notification.permission !== "granted") {
    console.warn("Les notifications ne sont pas autorisées.");
    return;
  }

  let statusIcon = "https://i.postimg.cc/x80KpBqW/warifinance3d.png";
  if (score >= 8)
    statusIcon = "https://i.postimg.cc/x80KpBqW/warifinance3d.png";
  if (score < 5) statusIcon = "https://i.postimg.cc/x80KpBqW/warifinance3d.png";

  const options = {
    body: message,
    icon: statusIcon,
    badge: "https://i.postimg.cc/x80KpBqW/warifinance3d.png",
    vibrate: score < 5 ? [500, 110, 500, 110, 450] : [200, 100, 200],
    tag: "wari-alert",
    renotify: true,
    data: { url: window.location.origin + "/dashboard", date: Date.now() },
  };

  if ("serviceWorker" in navigator) {
    navigator.serviceWorker.ready
      .then((reg) => reg.showNotification(`Coach Wari : ${title}`, options))
      .catch(() => new Notification(`Coach Wari : ${title}`, options));
  } else {
    new Notification(`Coach Wari : ${title}`, options);
  }
}

async function initialiserNotificationsWari() {
  try {
    const permission = await Notification.requestPermission();
    if (permission === "granted") {
      console.log("Notifications activées !");
      const now = new Date();
      const today = now.toDateString();
      const lastNotify = localStorage.getItem("wari_last_notification");

      if (typeof subscribeUserToPush === "function") subscribeUserToPush(false); // Silencieux au chargement

      setTimeout(() => {
        if (lastNotify !== today && now.getHours() >= 18) {
          navigator.serviceWorker.ready.then((reg) => {
            const messages = [
              "La fréquence des petites dépenses incontrôlées finit toujours par te diriger vers la ruine. Notes-les!",
              "Quand les revenus augmentent, les dépenses augmentent aussi. Sois vigilant!. Reste discipliné!",
              "Champion.ne, l'argent que tu ne contrôles pas, finit toujours par te diriger!",
            ];
            reg.showNotification("Wari - Coach", {
              body: messages[Math.floor(Math.random() * messages.length)],
              icon: "https://i.postimg.cc/x80KpBqW/warifinance3d.png",
              vibrate: [200, 100, 200],
              badge: "https://i.postimg.cc/x80KpBqW/warifinance3d.png",
            });
            localStorage.setItem("wari_last_notification", today);
          });
        }
      }, 10000);

      checkDebtReminders();
    }
  } catch (error) {
    console.error("Erreur notifications :", error);
  }
}
initialiserNotificationsWari();


// ─── RAPPELS DETTES ────────────────────────────────────────────────────────

function checkDebtReminders() {
  if (!window.dbDebts || dbDebts.length === 0) return;

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  dbDebts.forEach((debt) => {
    if (!debt.due_date) return;
    const due = new Date(debt.due_date);
    due.setHours(0, 0, 0, 0);
    const daysLeft = Math.round((due - today) / (1000 * 60 * 60 * 24));

    if (daysLeft === 3 || daysLeft === 1 || daysLeft === 0) {
      const action = debt.type === "loan" ? "récupérer" : "rembourser";
      const urgence =
        daysLeft === 0 ? "AUJOURD'HUI !" : `dans ${daysLeft} jour(s)`;
      showWariNotification(
        "Rappel de dette",
        `Tu dois ${action} ${parseInt(debt.amount).toLocaleString()} à ${debt.person_name} — ${urgence}`,
        daysLeft === 0 ? 2 : 6,
      );
    }
  });
}


// ─── INFO CATEGORIE (TOAST MOBILE) ────────────────────────────────────────

let activeToast = null;

window.showCatInfo = function (catName) {
  const descriptions = {
    "Projet": "Futur : voyage, maison, formation. Ce que tu veux construire.",
    "Épargne": "Sécurité : argent que tu ne touches pas. Pour les vrais coups durs.",
    "Imprévu": "Bouclier : réparations, urgences, dépenses soudaines. Ça arrive à tous.",
    "Train de vie": "Quotidien : nourriture, loyer, transports, loisirs. Ce qui reste après épargne."
  };

  const text = descriptions[catName] || "Ajuste le pourcentage en mode édition.";

  if (activeToast) activeToast.remove();

  const toast = document.createElement("div");
  toast.innerText = text;
  toast.className = "fixed bottom-24 left-4 right-4 bg-slate-900/95 border border-amber-500/30 text-white text-xs p-3 rounded-2xl z-50 backdrop-blur-md shadow-2xl";
  toast.style.animation = "fadeInUp 0.2s ease";
  document.body.appendChild(toast);
  activeToast = toast;

  setTimeout(() => {
    if (toast && toast.remove) toast.remove();
    if (activeToast === toast) activeToast = null;
  }, 3500);
};

// Ajout de l'animation CSS si pas déjà présente
if (!document.querySelector('#wari-toast-style')) {
  const style = document.createElement('style');
  style.id = 'wari-toast-style';
  style.textContent = `
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
  `;
  document.head.appendChild(style);
}


// ─── TOAST NOTIFICATION ────────────────────────────────────────────────

let activeInfoToast = null;

function showToastMessage(message, type = "info") {
  if (activeInfoToast) activeInfoToast.remove();

  const toast = document.createElement("div");
  toast.innerText = message;

  const bgColor = type === "info" ? "bg-blue-500/90" : "bg-emerald-500/90";
  toast.className = `fixed bottom-24 left-4 right-4 ${bgColor} text-white text-xs p-3 rounded-2xl z-50 text-center font-bold shadow-2xl`;
  toast.style.animation = "fadeInUp 0.2s ease";

  document.body.appendChild(toast);
  activeInfoToast = toast;

  setTimeout(() => {
    if (toast && toast.remove) toast.remove();
    if (activeInfoToast === toast) activeInfoToast = null;
  }, 4000);
}


// ─── MODE FOCUS ───────────────────────────────────────────────────────────

let isFocusMode = false;

window.toggleFocusMode = function () {
  const icon = document.getElementById("focusIcon");
  const text = document.getElementById("focusText");
  const btn = document.getElementById("focusModeBtn");

  isFocusMode = !isFocusMode;

  if (isFocusMode) {
    document.body.classList.add("focus-mode");
    if (btn) {
      btn.classList.remove("bg-slate-800", "border-slate-700");
      btn.classList.add("bg-blue-500/20", "border-blue-500/50");
    }
    if (text) text.innerHTML = "FOCUS ON";
    if (icon) icon.innerHTML = SVG_ICONS.lock;

    // Sauvegarder l'état
    localStorage.setItem("wari_focus_mode", "true");
  } else {
    document.body.classList.remove("focus-mode");
    if (btn) {
      btn.classList.add("bg-slate-800", "border-slate-700");
      btn.classList.remove("bg-blue-500/20", "border-blue-500/50");
    }
    if (text) text.innerHTML = "FOCUS";
    if (icon) icon.innerHTML = SVG_ICONS.target;

    // Sauvegarder l'état
    localStorage.setItem("wari_focus_mode", "false");
  }
};

// Restaurer l'état du mode focus au chargement
function restoreFocusMode() {
  const saved = localStorage.getItem("wari_focus_mode");
  if (saved === "true") {
    isFocusMode = false; // pour inverser
    toggleFocusMode();
  } else {
    const icon = document.getElementById("focusIcon");
    if (icon) icon.innerHTML = SVG_ICONS.target;
  }
}

// Appeler cette fonction au chargement de la page
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", restoreFocusMode);
} else {
  restoreFocusMode();
}


// ========================================================================================================================================================================================================================================================================================================


// --- GESTION DES NOTES DE MISE À JOUR (RELEASE NOTES) ---

const WARI_VERSION = 56; // Ta version actuelle

function checkReleaseNotes() {
  const lastSeenVersion = localStorage.getItem('wari_last_seen_version');

  // Si l'utilisateur n'a jamais vu la v55, on affiche le modal
  if (!lastSeenVersion || parseInt(lastSeenVersion) < WARI_VERSION) {
    setTimeout(showReleaseNotesModal, 2000); // Apparaît 2 secondes après le chargement
  }
}

function showReleaseNotesModal() {
  const modalHtml = `
        <div id="release-modal" style="position:fixed; inset:0; background:rgba(8,11,16,0.98); z-index:10001; display:flex; align-items:center; justify-content:center; padding:20px; backdrop-filter: blur(15px);">
            <div style="background:#0d1117; border:1px solid rgba(245,166,35,0.3); border-radius:35px; padding:20px; max-width:450px; width:100%; box-shadow: 0 25px 60px rgba(0,0,0,0.6); position:relative; overflow:hidden;">
                
                <!-- Badge Version -->
                <div style="position:absolute; top:20px; right:20px; background:#f5a623; color:#000; padding:5px 12px; border-radius:11px; font-size:11px; font-weight:900;">V1.6.0</div>

                <div style="text-align:center; margin-bottom:25px;">
                    <h2 style="color:#fff; font-weight:900; letter-spacing:-1px; text-transform:uppercase; margin:0;">Quoi de neuf ?</h2>
                    <p style="color:#f5a623; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:2px; margin-top:5px;">Wari Finance évolue pour toi</p>
                </div>

                <div style="max-height:300px; overflow-y:auto; padding-right:11px; margin-bottom:30px; text-align:justify;" class="custom-scrollbar">
                    <p style="color:#94a3b8; font-size:13px; line-height:1.7; margin:0;">
                      <strong class="text-amber-400">✨ NOUVEAUTÉS VERSION 1.6.0</strong><br/><br/>
                      
                      <strong>Mode Hors Ligne ⚡</strong><br/>
                      L'application fonctionne maintenant même sans internet ! Vos dépenses et modifications sont sauvegardées localement et synchronisées dès que vous retrouvez du réseau.<br/><br/>
                      
                      <strong>Rapidité Instantanée</strong><br/>
                      L'ajout de vos dépenses s'affiche immédiatement sur votre solde. Fini les temps d'attente frustrants !<br/><br/>
                      
                      <strong>PWA Optimisée</strong><br/>
                      Installez Wari sur votre écran d'accueil pour profiter d'une expérience ultra-fluide, comme une vraie application mobile.<br/><br/>
                      
                      <span class="text-slate-500 text-[11px]">Ces améliorations majeures sont le fruit de vos retours. Merci de faire grandir Wari avec nous.</span>
                    </p>
                </div>

                <button onclick="closeReleaseNotes()" style="background:#f5a623; color:#000; border:none; padding:11px; border-radius:18px; font-weight:900; cursor:pointer; width:100%; font-size:14px; text-transform:uppercase; transition: transform 0.2s; box-shadow: 0 11px 20px rgba(245,166,35,0.2);">
                    C'est génial, merci !
                </button>
            </div>
        </div>`;

  document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function closeReleaseNotes() {
  // On enregistre que l'utilisateur a vu la v55
  localStorage.setItem('wari_last_seen_version', WARI_VERSION);
  const modal = document.getElementById('release-modal');
  if (modal) modal.remove();
}

// ─── OFFLINE BACKGROUND SYNC ───────────────────────────────────────────────

const OFFLINE_QUEUE_KEY = 'wari_offline_queue';

function getOfflineQueue() {
  try {
    return JSON.parse(localStorage.getItem(OFFLINE_QUEUE_KEY) || '[]');
  } catch {
    return [];
  }
}

function setOfflineQueue(queue) {
  localStorage.setItem(OFFLINE_QUEUE_KEY, JSON.stringify(queue));
}

window.queueOfflineAction = function (url, body) {
  const queue = getOfflineQueue();
  queue.push({ url, body, timestamp: Date.now() });
  setOfflineQueue(queue);
  console.log("Action mise en attente hors ligne :", url);
};

window.processOfflineQueue = async function () {
  if (!navigator.onLine) return;
  const queue = getOfflineQueue();
  if (queue.length === 0) return;

  console.log(`Synchronisation de ${queue.length} actions en attente...`);
  let remainingQueue = [];

  for (let item of queue) {
    try {
      const res = await fetch(item.url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(item.body)
      });
      if (!res.ok) throw new Error("HTTP error " + res.status);
      console.log(`✅ Synchro réussie pour ${item.url}`);
    } catch (e) {
      console.warn(`❌ Echec synchro ${item.url}, sera retenté plus tard.`, e);
      remainingQueue.push(item);
    }
  }

  setOfflineQueue(remainingQueue);
  if (remainingQueue.length === 0 && queue.length > 0) {
    console.log("🎉 Synchronisation totale terminée !");
    loadTrendChart();
  }
};

window.addEventListener('online', () => {
  const badge = document.getElementById('offlineBadge');
  if (badge) badge.classList.add('hidden');
  processOfflineQueue();
});

window.addEventListener('offline', () => {
  const badge = document.getElementById('offlineBadge');
  if (badge) badge.classList.remove('hidden');
});

window.addEventListener('DOMContentLoaded', () => {
  if (!navigator.onLine) {
    const badge = document.getElementById('offlineBadge');
    if (badge) badge.classList.remove('hidden');
  }
  processOfflineQueue();
});