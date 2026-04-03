// ─── CONFIGURATION INITIALE ────────────────────────────────────────────────

let categories = [
  { id: 3, name: "Projet", percent: 25, icon: "🚀", balance: 0 },
  { id: 1, name: "Épargne", percent: 15, icon: "💰", balance: 0 },
  { id: 4, name: "Imprévu", percent: 10, icon: "🆘", balance: 0 },
  { id: 2, name: "Train de vie", percent: 50, icon: "🏠", balance: 0 },
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

function render() {
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
      btnVault.innerHTML = `<span>💎</span> Capital sécurisé à chaque répartition`;
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

  // ── Sous-titres des cartes ─────────────────────────────────────────────
  const catSubtitles = {
    projet: "Le moteur de ton avenir — versé au coffre en priorité.",
    épargne: "La sécurité de demain — intouchable.",
    imprévu: "Ton bouclier contre les chocs de la vie.",
    "train de vie": "Ce qui reste pour vivre — après avoir protégé l'avenir.",
  };

  function getCatSubtitle(name) {
    const key = Object.keys(catSubtitles).find((k) =>
      name.toLowerCase().includes(k),
    );
    return key ? catSubtitles[key] : "";
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
    const totalPrevisionnel = isProjet
      ? parseFloat(projectCapital) || 0
      : currentBalance + montantAjoute;

    const remaining = Math.max(0, totalPrevisionnel - spent);
    const progress =
      totalPrevisionnel > 0
        ? Math.min(100, (spent / totalPrevisionnel) * 100)
        : 0;

    const card = document.createElement("div");
    card.className = `glass-card p-3 flex flex-col transition-all duration-300`;

    card.innerHTML = `
        <div class="flex items-center justify-between mb-4">
            <span class="text-xl">${cat.icon}</span>
            <div class="flex items-center justify-center bg-slate-900/80 py-1 rounded-lg border border-white/5 gap-[2px]">
              <input type="number" value="${currentPercent}" 
                  ${isEditMode ? "" : "disabled"}
                  oninput="updatePercent(${cat.id}, this.value)"
                  /* w-auto et text-right supprimés pour favoriser le centrage global */
                  class="w-[22px] bg-transparent text-[11px] font-black outline-none text-right p-0 m-0 ${isEditMode ? "text-amber-400" : "text-slate-400"} [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
              
              <span class="text-[11px] font-bold text-slate-600 p-0 m-0">%</span>
          </div>
        </div>

        <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">
            ${cat.name}
        </h4>

        <div class="mb-4">
            <div class="flex items-end justify-between">
                <div class="text-xl font-black text-white leading-none">
                    ${totalPrevisionnel.toLocaleString()}
                    <span class="text-[10px] text-slate-600 font-normal uppercase">${currency}</span>
                </div>
                
                <div class="text-[11px] font-bold text-emerald-500 bg-emerald-500/10 px-1.5 py-0.5 rounded">
                    +${montantAjoute.toLocaleString()}
                </div>
            </div>
        </div>

        <div class="mt-auto pt-2">
            <div class="flex justify-between items-center mb-1.5">
                <span class="text-[9px] text-slate-500 uppercase font-bold">Reste: ${remaining.toLocaleString()}</span>
                <span class="text-[9px] font-black ${progress > 90 ? "text-red-500" : "text-slate-400"}">${Math.round(progress)}%</span>
            </div>
            <div class="w-full h-1 bg-slate-950/60 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 transition-all duration-700" 
                     style="width: ${progress}%">
                </div>
            </div>
        </div>

        ${
          isEditMode
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
    const soldeReel = isProjet
      ? Math.max(0, projectCapital)
      : Math.max(0, c.amount - spent);
    const n = c.name.toLowerCase();

    totalAlloue += isProjet ? projectCapital : c.amount;
    totalDepense += spent;

    if (
      c.id === 1 ||
      c.id === 3 ||
      n.includes("épargne") ||
      n.includes("projet")
    ) {
      bank += soldeReel;
      bankSpent += spent;
    } else {
      cash += soldeReel;
      cashSpent += spent;
    }
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

  // ── Jauge de santé ────────────────────────────────────────────────────
  const gaugeBar = document.getElementById("gaugeBar");
  const gaugePercent = document.getElementById("gaugePercent");
  const gaugeAlert = document.getElementById("gaugeAlert");

  if (gaugeBar && totalAlloue > 0) {
    const pctIntact = Math.round(
      ((totalAlloue - totalDepense) / totalAlloue) * 100,
    );
    const pctDepense = 100 - pctIntact;
    gaugePercent.innerText = pctIntact + "% intact";

    let barColor, alertStyle, alertMsg;

    if (pctDepense <= 30) {
      barColor = "bg-emerald-500";
      alertStyle =
        "bg-emerald-500/10 text-emerald-400 border border-emerald-500/20";
      alertMsg = "✅ Excellente discipline — ton budget est bien préservé.";
    } else if (pctDepense <= 60) {
      barColor = "bg-amber-500";
      alertStyle = "bg-amber-500/10 text-amber-400 border border-amber-500/20";
      alertMsg = "⚡ Mi-parcours — surveille tes sorties cash.";
    } else if (pctDepense <= 85) {
      barColor = "bg-orange-500";
      alertStyle =
        "bg-orange-500/10 text-orange-400 border border-orange-500/20";
      alertMsg = "⚠️ Budget entamé — ralentis tes dépenses.";
    } else {
      barColor = "bg-red-500 animate-pulse";
      alertStyle = "bg-red-500/10 text-red-400 border border-red-500/20";
      alertMsg = "🚨 ALERTE — Il ne reste presque rien. Stop les dépenses.";
    }

    gaugeBar.className = `h-full rounded-full transition-all duration-700 ease-out ${barColor}`;
    gaugeBar.style.width = `${pctIntact}%`;
    gaugePercent.className = `font-black text-sm ${barColor.replace("bg-", "text-").replace(" animate-pulse", "")}`;

    if (gaugeAlert) {
      gaugeAlert.className = `text-[10px] text-center py-2 px-3 rounded-lg font-bold ${alertStyle}`;
      gaugeAlert.innerText = alertMsg;
    }
  }

  updateVaultDisplay();
  updateStatus(currentTotalPercent);
  if (typeof generateFinancialReport === "function") generateFinancialReport();
}

// ─── COFFRE ────────────────────────────────────────────────────────────────

function updateVaultDisplay() {
  const projectEl = document.getElementById("totalProjectSaved");
  const progressBar = document.getElementById("vaultProgress");
  const deleteBtn = document.getElementById("deleteGoalBtn"); // On récupère le bouton X
  const currency = document.getElementById("currencySelector")?.value || "F";

  if (projectEl) {
    // Garder ton animation de couleur
    const oldAmount = parseInt(projectEl.innerText.replace(/[^0-9]/g, "")) || 0;
    projectEl.innerText = `${projectCapital.toLocaleString()} ${currency}`;

    if (projectCapital > oldAmount) {
      projectEl.classList.add("text-emerald-400", "scale-110");
      setTimeout(() => projectEl.classList.remove("scale-110"), 300);
    } else if (projectCapital < oldAmount) {
      projectEl.classList.add("text-red-400", "scale-95");
      setTimeout(() => projectEl.classList.remove("scale-95"), 300);
    }

    // Gestion de la Jauge et du Bouton Supprimer
    const savedGoal = JSON.parse(
      localStorage.getItem("wari_vault_goal") || "null",
    );

    // 1. La Jauge
    if (progressBar) {
      const goal = savedGoal ? savedGoal.amount : 1000000;
      const progress = Math.min((projectCapital / goal) * 100, 100);
      progressBar.style.width = `${progress}%`;
    }

    // 2. Le bouton de suppression (Affiché seulement si un objectif existe)
    if (deleteBtn) {
      if (savedGoal) {
        deleteBtn.classList.remove("hidden");
      } else {
        deleteBtn.classList.add("hidden");
      }
    }

    setTimeout(() => {
      projectEl.classList.remove("text-emerald-400", "text-red-400");
    }, 2000);
  }
}
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
    render();
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
      ? `<span class="mr-2">⚠️</span> Oups ! ${total}% ? Tu dépenses plus que tu n'as.`
      : `<span class="mr-2">⏳</span> ${total}% répartis... Continue jusqu'à 100%.`;
  }
}

// ─── SAUVEGARDE ────────────────────────────────────────────────────────────

window.saveBudget = function (silent = false) {
  console.log("Démarrage de la sauvegarde...");

  const totalAAjouter = parseFloat(mainInput.value) || 0;
  const currentCurrency =
    document.getElementById("currencySelector")?.value || "F";

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
          "Versement automatique — répartition",
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
.then(() => console.log("✅ Synchro serveur réussie"))
.catch((err) => {
    console.error("❌ Erreur critique save_data :", err.message);
    // Optionnel : Alerter l'utilisateur que ses données ne sont que locales
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
    .then(() => console.log("✅ Distribution synchronisée"))
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
    if (data.categories) categories = data.categories;
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
          "🌟 NOUVEAU MOIS, NOUVEL OBJECTIF !\n\nFélicitations Champion·ne, tes compteurs sont remis à zéro. Le coffre, lui, continue de grandir !",
        );
      }, 1000);

      categories = categories.map((cat) => ({ ...cat, balance: 0 }));
      data.lastSavedMonth = currentMonth;
      data.vaultTransactions = vaultTransactions;
      data.categories = categories;
      localStorage.setItem("wari_budget_data", JSON.stringify(data));
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
}

setTimeout(() => {
  isInitialLoad = false;
  console.log("Wari-Finance est prêt.");
}, 500);

// ─── HISTORIQUE COFFRE ─────────────────────────────────────────────────────

function loadVaultHistory() {
  fetch("config/get_vault_history.php")
    .then((res) => res.json())
    .then((data) => {
      if (data.success && data.history) {
        // ✅ On reconstruit les séparateurs depuis les données serveur
        // en détectant les changements de mois entre transactions
        const withSeparators = [];
        let lastMonth = null;

        data.history.forEach((tx) => {
          // Le serveur renvoie la date au format "18 mars" — on extrait le mois
          const moisTx = tx.date.split(" ")[1]; // "mars", "avr.", etc.

          if (lastMonth && moisTx !== lastMonth) {
            // Changement de mois détecté → on insère un séparateur
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
    .catch((err) => console.error("Erreur lors du chargement du coffre:", err));
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
    container.innerHTML = `<p class="text-[10px] text-slate-600 italic text-center">En attente de ton premier investissement...</p>`;
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
            <span class="text-[10px] text-slate-200 font-semibold">${tx.label}</span>
            <span class="text-[8px] text-slate-500 font-medium">${tx.date}</span>
          </div>
          <span class="text-[10px] font-black ${tx.type === "in" ? "text-emerald-400" : "text-red-400"}">
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
    debtList.innerHTML = `<p class="text-slate-500 text-[10px] italic text-center uppercase tracking-widest">Paix totale : Aucune dette en cours.</p>`;
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
          💰
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
      { id: 3, name: "Projet", percent: 25, icon: "🚀" },
      { id: 1, name: "Épargne", percent: 15, icon: "💰" },
      { id: 4, name: "Imprévu", percent: 10, icon: "🆘" },
      { id: 2, name: "Train de vie", percent: 50, icon: "🏠" },
    ],
  };
  if (confirm("Appliquer ce modèle ?")) {
    categories = JSON.parse(JSON.stringify(models[modelKey]));
    render();
    saveBudget(true);
  }
}

mainInput.addEventListener("input", () => render());

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
    .map((cat) => `<option value="${cat.id}">${cat.icon} ${cat.name}</option>`)
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

  fetch("config/add_expense.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ amount, category_id: catId, description: note }),
  })
    .then((res) => res.text()) // ← text() d'abord pour voir ce qui arrive
    .then((raw) => {
      console.log("Réponse brute :", raw); // ← diagnostic
      const data = JSON.parse(raw);
      if (data.success) {
        if (!currentExpenses[catId]) currentExpenses[catId] = 0;
        currentExpenses[catId] = parseInt(currentExpenses[catId]) + amount;

        const cat = categories.find((c) => c.id == catId);
        if (cat && cat.name.toLowerCase().includes("projet")) {
          projectCapital = Math.max(0, projectCapital - amount);
          addVaultTransaction("out", amount, note);
          saveBudget(true);
        }

        closeExpenseModal();
        amountInput.value = "";
        if (noteInput) noteInput.value = "";
        render();
      } else {
        alert("Erreur : " + (data.error || "Échec"));
      }
    })
    .catch((error) => alert("Erreur réseau : " + error.message));
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

  const res = await fetch("config/add_debt.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ person, amount, type }),
  });
  const data = await res.json();
  if (data.success) {
    window.location.href =
      window.location.href.split("?")[0] + "?t=" + Date.now();
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

  const res = await fetch("config/partial_pay.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id, amount, type }),
  });
  const data = await res.json();
  if (data.success) {
    window.location.href = window.location.pathname + "?updated=" + Date.now();
  } else {
    alert("Erreur lors de l'enregistrement. Réessaie.");
  }
};

// ─── COACH ─────────────────────────────────────────────────────────────────

function generateFinancialReport() {
  if (!categories || categories.length === 0) return;

  const scoreElement = document.getElementById("disciplineScore");
  const coachMessageElement = document.getElementById("aiCoachMessage");
  const currency = document.getElementById("currencySelector")?.value || "F";

  if (!scoreElement || !coachMessageElement) return;

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

  const aSolde =
    categories.some((c) => (c.balance || 0) > 0) || projectCapital > 0;

  if (!aSolde) {
    coachMessageElement.innerHTML = `<span class="italic text-slate-500">Enregistre tes revenus pour activer le coaching Wari personnalisé. 🚀</span>`;
    return;
  }

  // On prépare les données pour l'IA
  const summary = categories
    .map(
      (c) =>
        `${c.name}: ${currentExpenses[c.id] || 0} / ${c.name.toLowerCase().includes("projet") ? projectCapital : c.balance || 0} ${currency}`,
    )
    .join(", ");
  const statusData = {
    score: finalScore,
    overspent: totalOverspent,
    summary: summary,
    currency: currency,
    has_sacrificed_saving: savingSacrificed,
  };

  fetchAiCoachAdvice(statusData);
}

// Nouvelle fonction pour appeler Gemini
async function fetchAiCoachAdvice(data) {
  const coachMessageElement = document.getElementById("aiCoachMessage");
  const gaugeAlert = document.getElementById("gaugeAlert");
  if (!coachMessageElement) return;

  try {
    const formData = new FormData();
    formData.append('action', 'get_coach_advice');
    formData.append('data', JSON.stringify(data));

    const res = await fetch('academy-admin/ai_gateway.php', {
      method: 'POST',
      body: formData
    });
    const result = await res.json();

    if (result.message) {
      // Affichage du message principal (Ton Wari)
      coachMessageElement.innerHTML = `
        <div class="space-y-1">
          <p class="text-slate-200">"${result.message}"</p>
          ${result.prediction ? `<p class="text-[10px] text-amber-400/80 font-bold">${result.prediction}</p>` : ''}
          ${result.dette_conseil ? `<p class="text-[10px] text-blue-400/80 font-bold">${result.dette_conseil}</p>` : ''}
          ${result.academy_reco ? `<p class="text-[10px] text-emerald-400/80 font-bold">Je te recommande ce cours : "${result.academy_reco}"</p>` : ''}
        </div>
      `;

      // Mise à jour de l'alerte de la jauge si l'IA détecte un danger
      if (result.alerte_rouge && gaugeAlert) {
        gaugeAlert.innerHTML = `🚨 ${result.alerte_rouge}`;
        gaugeAlert.classList.add('animate-bounce');
      }
    }
  } catch (e) {
    console.error("Erreur Coach Wari:", e);
  }
}

// ─── MODE ÉDITION ──────────────────────────────────────────────────────────

window.toggleEditMode = function () {
  isEditMode = !isEditMode;
  const btn = document.getElementById("lockBtn");

  if (isEditMode) {
    // État ÉDITION (Ambre)
    btn.innerHTML = `<span>🔓</span> <span class="text-amber-500">ÉDITION</span>`;
    btn.className =
      "flex items-center gap-1 px-2 py-1 rounded-full bg-amber-500/10 border border-amber-500/50 transition-all scale-105 shadow-[0_0_15px_rgba(245,158,11,0.2)]";
  } else {
    // État LECTURE (Slate)
    btn.innerHTML = `<span>🔒</span> <span class="text-slate-400">LECTURE</span>`;
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

function loadMonthlyHistory(months = 6) {
  fetch(`config/get_history.php?months=${months}`)
    .then((res) => res.json())
    .then((data) => {
      const container = document.getElementById("historyContent");
      const currency =
        document.getElementById("currencySelector")?.value || "F";

      if (!data.success || data.history.length === 0) {
        container.innerHTML = `<p class="text-slate-500 text-[10px] italic text-center py-4">Aucun historique disponible.</p>`;
        return;
      }

      container.innerHTML = data.history
        .map(
          (month) => `
        <div class="bg-slate-800/50 p-3 rounded-xl border border-slate-700/50 mb-3">

          <!-- En-tête du mois -->
          <div class="flex items-center justify-between mb-3">
            <p class="text-amber-400 font-black text-[10px] uppercase tracking-widest">
              ${month.label}
            </p>
            <span class="text-[9px] bg-slate-700/50 text-slate-400 px-2 py-0.5 rounded-full">
              ${month.nb_repartitions} répartition${month.nb_repartitions > 1 ? "s" : ""}
            </span>
          </div>

          <!-- Détail des répartitions individuelles -->
          ${
            month.details.length > 0
              ? `
            <div class="space-y-1 mb-3">
              ${month.details
                .map(
                  (d) => `
                <div class="flex justify-between items-center px-2 py-1.5 bg-slate-900/40 rounded-lg border border-white/5">
                  <div class="flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                    <span class="text-[9px] text-slate-400">Répartition du ${d.datetime}</span>
                  </div>
                  <span class="text-[10px] font-black text-emerald-400">
                    +${d.amount.toLocaleString()} ${currency}
                  </span>
                </div>
              `,
                )
                .join("")}
            </div>
          `
              : ""
          }
 
          <!-- Totaux du mois -->
          <div class="border-t border-slate-700/50 pt-2 space-y-1">
            <div class="flex justify-between text-[10px]">
              <span class="text-slate-400">Total réparti</span>
              <span class="text-white font-bold">
                ${parseInt(month.total_distributed).toLocaleString()} ${currency}
              </span>
            </div>
            <div class="flex justify-between text-[10px]">
              <span class="text-slate-400">Dépensé</span>
              <span class="text-red-400 font-bold">
                −${parseInt(month.total_spent).toLocaleString()} ${currency}
              </span>
            </div>
            <div class="flex justify-between text-[10px]">
              <span class="text-slate-400">Préservé</span>
              <span class="text-emerald-400 font-bold">
                ${parseInt(month.total_saved).toLocaleString()} ${currency}
              </span>
            </div>
          </div>

        </div>
      `,
        )
        .join("");
    })
    .catch(() => {
      document.getElementById("historyContent").innerHTML =
        `<p class="text-red-400 text-[10px] italic text-center py-4">Erreur de chargement.</p>`;
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

      if (typeof subscribeUserToPush === "function") subscribeUserToPush();

      setTimeout(() => {
        if (lastNotify !== today && now.getHours() >= 18) {
          navigator.serviceWorker.ready.then((reg) => {
            const messages = [
              "Champion·ne, fais un petit point sur tes entrées-sorties 💰",
              "Salut, n'oublie pas de noter tes dépenses pour rester discipliné. 🚀",
              "Champion.ne, as-tu enregistré tes flux pour aujourd'hui ? 🌟",
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

// ─── WEB PUSH ──────────────────────────────────────────────────────────────

function urlBase64ToUint8Array(base64String) {
  const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, "+").replace(/_/g, "/");
  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);
  for (let i = 0; i < rawData.length; ++i)
    outputArray[i] = rawData.charCodeAt(i);
  return outputArray;
}

async function forceNewSubscription() {
  const permission = await Notification.requestPermission();
  if (permission !== "granted") {
    console.error("Notifications refusées.");
    return;
  }

  const registration = await navigator.serviceWorker.ready;

  try {
    const oldSub = await registration.pushManager.getSubscription();
    if (oldSub) {
      await oldSub.unsubscribe();
      console.log("Ancien abonnement nettoyé.");
    }

    const newSub = await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(
        "BH9WpcuMhkSEOjnwf8KVZfDTv9Ps6nGaQ9RQ77e4D15ywgPmO7wNgTlldejjFjyWCp3PoBYareDXjlFBTdpzm40",
      ),
    });

    console.log("Nouvel abonnement créé :", newSub);

    const response = await fetch("config/save_subscription.php", {
      method: "POST",
      body: JSON.stringify(newSub),
      headers: { "Content-Type": "application/json" },
    });
    console.log("Réponse serveur PHP :", await response.text());
  } catch (err) {
    console.error("Échec abonnement :", err);
  }
}
forceNewSubscription();

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
                <div style="position:absolute; top:20px; right:20px; background:#f5a623; color:#000; padding:5px 12px; border-radius:10px; font-size:10px; font-weight:900;">V1.5.6</div>

                <div style="text-align:center; margin-bottom:25px;">
                    <h2 style="color:#fff; font-weight:900; letter-spacing:-1px; text-transform:uppercase; margin:0;">Quoi de neuf ?</h2>
                    <p style="color:#f5a623; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:2px; margin-top:5px;">Wari Finance évolue pour toi</p>
                </div>

                <div style="max-height:300px; overflow-y:auto; padding-right:10px; margin-bottom:30px; text-align:justify;" class="custom-scrollbar">
                    <p style="color:#94a3b8; font-size:13px; line-height:1.7; margin:0;">
                        À l'occasion des fêtes de Pâques, période de renouveau et de partage, Wari Finance franchit une nouvelle étape pour transformer la gestion de votre patrimoine en une expérience de sérénité absolue. Cette version 1.5.6 a été conçue pour refléter cette clarté : l'interface évolue vers plus de précision, vous offrant un contrôle total sur chaque flux financier. Le Coach Wari monte en puissance avec une intelligence prédictive capable d'analyser vos habitudes de consommation en temps réel pour anticiper votre solde de fin de mois et vous orienter vers les dettes à solder prioritairement. 
                    </p>
                </div>

                <button onclick="closeReleaseNotes()" style="background:#f5a623; color:#000; border:none; padding:10px; border-radius:18px; font-weight:900; cursor:pointer; width:100%; font-size:14px; text-transform:uppercase; transition: transform 0.2s; box-shadow: 0 10px 20px rgba(245,166,35,0.2);">
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