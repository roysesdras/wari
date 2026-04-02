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

    // ── CARTE PROJET : mode journal ───────────────────────────────────────
    if (isProjet) {
      const partSimulation = cat.amount - (cat.balance || 0);

      const percentConsumed =
        projectCapital > 0
          ? Math.min((spent / (projectCapital + spent)) * 100, 100)
          : 0;

      let barColor = "bg-emerald-500";
      let alertColor = "text-emerald-400";
      let alertMsg = "Aucune dépense sur ce projet.";

      if (percentConsumed > 80) {
        barColor = "bg-red-500 animate-pulse";
        alertColor = "text-red-400";
        alertMsg = "🚨 Capital très entamé — ralentis les dépenses projet.";
      } else if (percentConsumed > 50) {
        barColor = "bg-orange-500";
        alertColor = "text-orange-400";
        alertMsg = "⚠️ Plus de la moitié du capital consommé.";
      } else if (spent > 0) {
        alertMsg = "Capital sous contrôle.";
      }

      const depensesProjet = vaultTransactions
        .filter((tx) => tx.type === "out")
        .slice(0, 5);

      const card = document.createElement("div");
      card.className = `glass-card p-3 flex flex-col gap-2 relative mb-4 transition-all duration-300 border-l-4 border-amber-500/50 hover:border-amber-500/80`;

      card.innerHTML = `
        <div class="flex justify-between items-start">
          <div class="flex items-center gap-3">
            <span class="text-2xl drop-shadow-md">${cat.icon}</span>
            <div>
              <div class="font-black text-slate-100 text-sm uppercase tracking-tight flex items-center gap-2">
                ${cat.name}
                <span class="text-[9px] bg-blue-500/20 text-blue-400 border border-blue-500/30 px-2 py-0.5 rounded-full font-bold">journal</span>
              </div>
              <div class="text-[10px] text-slate-500 mt-0.5">
                ${
                  spent > 0
                    ? `${spent.toLocaleString()} ${currency} prélevés sur le capital`
                    : "Aucune dépense sur ce projet"
                }
              </div>
            </div>
          </div>
          <div class="text-right">
            <div class="font-black text-lg leading-tight ${
              spent > 0
                ? "text-red-400"
                : partSimulation > 0
                  ? "text-amber-400"
                  : "text-emerald-400"
            }">
              ${
                spent > 0
                  ? `−${spent.toLocaleString()}`
                  : partSimulation > 0
                    ? `+${partSimulation.toLocaleString()}`
                    : "0"
              }
              <span class="ml-1 text-sm">${currency}</span>
            </div>
            <div class="inline-flex items-center bg-slate-900/60 px-2 py-1 rounded-lg border border-white/5 mt-1">
              <span class="text-[11px] font-black text-slate-300">${cat.percent}</span>
              <span class="text-[10px] text-slate-600 font-bold ml-0.5">%</span>
            </div>
          </div>
        </div>

        ${
          partSimulation > 0
            ? `
          <div class="flex items-center justify-between bg-amber-500/10 border border-amber-500/30 px-3 py-2 rounded-xl">
            <div class="flex items-center gap-2">
              <span class="text-sm">⏳</span>
              <span class="text-[10px] text-amber-400 font-bold uppercase tracking-wider">À verser au coffre</span>
            </div>
            <span class="text-amber-400 font-black text-sm">+${partSimulation.toLocaleString()} ${currency}</span>
          </div>
        `
            : ""
        }

        <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden border border-white/5">
          <div class="h-full ${barColor} transition-all duration-700 ease-out" style="width: ${percentConsumed}%"></div>
        </div>

        <div class="mt-1 pt-2 border-t border-slate-800/20">
          ${
            depensesProjet.length > 0
              ? `
            <div class="text-[9px] uppercase tracking-widest text-slate-500 font-bold mb-2">
              Pourquoi le capital a diminué
            </div>
            <div class="space-y-1.5">
              ${depensesProjet
                .map(
                  (tx) => `
                <div class="flex justify-between items-center bg-slate-900/40 px-3 py-2 rounded-lg border border-white/5">
                  <div>
                    <p class="text-[11px] text-slate-300 font-medium">${tx.label}</p>
                    <p class="text-[9px] text-slate-600">${tx.date}</p>
                  </div>
                  <span class="text-[11px] font-black text-red-400">−${tx.amount.toLocaleString()} ${currency}</span>
                </div>
              `,
                )
                .join("")}
            </div>
          `
              : `
            <div class="text-[10px] ${alertColor} font-bold text-center py-1">
              ${alertMsg}
            </div>
          `
          }
        </div>

        <div class="pt-1">
          <p class="text-[9px] text-slate-600 italic tracking-wide">
            ${getCatSubtitle(cat.name)}
          </p>
        </div>
      `;

      container.appendChild(card);
      return;
    }

    // ── CARTES NORMALES ───────────────────────────────────────────────────
    const remaining = cat.amount - spent;
    const percentSpent =
      cat.amount > 0 ? Math.min((spent / cat.amount) * 100, 100) : 0;

    let barColor = "bg-emerald-500";
    let remainingTextColor = "text-slate-400";

    if (percentSpent > 90) {
      barColor = "bg-red-500 animate-pulse";
      remainingTextColor = "text-red-500 font-black";
    } else if (percentSpent > 70) {
      barColor = "bg-orange-500";
      remainingTextColor = "text-orange-400";
    }

    const card = document.createElement("div");
    card.className = `glass-card p-3 flex flex-col gap-2 relative mb-3 transition-all duration-300 border-l-4 border-amber-500/50 hover:border-amber-500/80`;

    card.innerHTML = `
      <div class="flex justify-between items-start">
        <div class="flex items-center gap-3">
          <span class="text-2xl drop-shadow-md">${cat.icon}</span>
          <input type="text" value="${cat.name}"
            ${isEditMode ? "" : "disabled"}
            onchange="updateName(${cat.id}, this.value)"
            class="bg-transparent font-black text-slate-100 text-sm outline-none w-32 focus:text-amber-400 transition-colors uppercase tracking-tight">
        </div>
        <div class="text-right">
          <div class="text-[10px] text-slate-500 font-medium uppercase tracking-tighter">
            Solde : ${(cat.balance || 0).toLocaleString()} ${currency}
          </div>
          <div class="text-amber-400 font-black text-lg leading-tight">
            ${(cat.balance || 0).toLocaleString()}
            <span class="text-white/40 text-sm font-light">+</span>
            <span class="text-emerald-400">${(cat.amount - (cat.balance || 0)).toLocaleString()}</span>
            <span class="ml-1">${currency}</span>
          </div>
          <div class="inline-flex items-center bg-slate-900/60 px-2 py-1 rounded-lg border border-white/5 mt-1">
            <input type="number" value="${cat.percent}" min="0" max="100"
              ${isEditMode ? "" : "disabled"}
              onchange="updatePercent(${cat.id}, this.value)"
              class="bg-transparent text-[11px] font-black text-slate-300 w-7 outline-none text-right [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
            <span class="text-[10px] text-slate-600 font-bold ml-0.5">%</span>
          </div>
        </div>
      </div>

      <div class="relative flex items-center h-2">
        <input type="range" value="${cat.percent}" min="0" max="100" step="1"
          ${isEditMode ? "" : "disabled"}
          oninput="updatePercent(${cat.id}, this.value)"
          class="w-full h-1.5 bg-slate-800 rounded-full appearance-none accent-amber-500 cursor-pointer transition-all ${isEditMode ? "opacity-100" : "opacity-30 cursor-not-allowed"}">
      </div>

      <div class="mt-1 pt-2 border-t border-slate-800/20">
        <div class="flex justify-between text-[10px] font-bold mb-1.5 uppercase tracking-tighter">
          <span class="text-slate-400 font-medium">Reste : <span class="${remainingTextColor}">${remaining.toLocaleString()} ${currency}</span></span>
          <span class="${percentSpent > 90 ? "text-red-500 animate-pulse" : "text-slate-400"}">${Math.round(percentSpent)}%</span>
        </div>
        <div class="w-full h-1.5 bg-slate-950/50 rounded-full overflow-hidden border border-white/5">
          <div class="h-full ${barColor} transition-all duration-700 ease-out" style="width: ${percentSpent}%"></div>
        </div>
        <div class="flex justify-between mt-1">
          <p class="text-[8px] text-slate-700 uppercase font-medium">Utilisé : ${spent.toLocaleString()} ${currency}</p>
          <p class="text-[8px] text-slate-800 font-black">ID: #${cat.id}</p>
        </div>
      </div>

      <div class="pt-1">
        <p class="text-[9px] text-slate-600 italic tracking-wide">
          ${getCatSubtitle(cat.name)}
        </p>
      </div>
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
  const currency = document.getElementById("currencySelector")?.value || "F";

  if (projectEl) {
    const oldAmount = parseInt(projectEl.innerText.replace(/[^0-9]/g, "")) || 0;
    projectEl.innerText = `${projectCapital.toLocaleString()} ${currency}`;

    if (projectCapital > oldAmount) {
      projectEl.classList.add("text-emerald-400", "scale-110");
      setTimeout(() => projectEl.classList.remove("scale-110"), 300);
    } else if (projectCapital < oldAmount) {
      projectEl.classList.add("text-red-400", "scale-95");
      setTimeout(() => projectEl.classList.remove("scale-95"), 300);
    }

    if (progressBar) {
      const savedGoal = JSON.parse(
        localStorage.getItem("wari_vault_goal") || "null",
      );
      const goal = savedGoal ? savedGoal.amount : 1000000;
      const progress = Math.min((projectCapital / goal) * 100, 100);
      progressBar.style.width = `${progress}%`;
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
    cat.percent = parseInt(val);
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
    "mt-4 flex items-center justify-center p-3 rounded-2xl border transition-all duration-300 ";

  if (total === 100) {
    status.className =
      baseClass +
      "bg-emerald-500/10 border-emerald-500/30 text-emerald-500 shadow-[0_0_15px_rgba(16,185,129,0.2)]";
    text.innerHTML = `<span class="mr-2">✅</span> 100% - Ton argent est dompté !`;
  } else if (total === 0) {
    status.className =
      baseClass + "bg-slate-800/50 border-slate-700 text-slate-500";
    text.innerHTML = `💰 WARI-FINANCE : Prêt pour le calcul`;
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

  fetch("config/save_data.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(dataToSave),
  })
    .then(() => console.log("Synchro serveur réussie"))
    .catch((err) =>
      console.warn("Erreur synchro serveur (mais local OK) :", err),
    );

  if (totalAAjouter > 0) {
    fetch("config/add_distribution.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ amount: totalAAjouter }),
    }).catch((err) => console.warn("Erreur synchro distribution :", err));
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
    <div class="flex items-center justify-between bg-slate-800/50 p-3 rounded-xl border border-slate-700/50 hover:border-slate-500 transition-all">
      <div>
        <p class="text-[9px] font-black ${debt.type === "loan" ? "text-emerald-400" : "text-red-400"} uppercase tracking-wider">
          ${debt.type === "loan" ? "💰 On me doit" : "💸 Je dois"}
        </p>
        <p class="text-white font-bold text-sm">${debt.person_name}</p>
      </div>
      <div class="flex items-center gap-3">
        <span class="text-white font-black text-sm">${parseInt(debt.amount).toLocaleString()} ${currency}</span>
        <button onclick="openPayModal(${debt.id}, '${debt.person_name}', ${debt.amount}, '${debt.type}')"
                class="w-10 h-10 rounded-full bg-slate-700/50 flex items-center justify-center hover:bg-emerald-600 hover:scale-110 active:scale-95 transition-all shadow-lg">
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

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialisation de ton état de chargement
    isInitialLoad = false;

    // 2. Lancement des notes de mise à jour (V55)
    // On met un petit délai pour ne pas agresser l'utilisateur dès la première seconde
    checkReleaseNotes(); 

    // 3. Tes autres vérifications (Radar, etc.)
    const lastClosed = localStorage.getItem('wari_push_modal_closed');
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

  let totalAlloue = 0;
  let totalDepense = 0;
  let respectedCats = 0;
  let totalOverspent = 0;
  let savingSacrificed = false;

  categories.forEach((cat) => {
    const spent = currentExpenses[cat.id] || 0;
    const name = cat.name.toLowerCase();
    const isProjet = name.includes("projet");
    const planned = isProjet ? projectCapital : cat.balance || 0;

    // Recalcul des totaux pour l'IA
    totalAlloue += isProjet ? projectCapital : (cat.balance || 0);
    totalDepense += spent;

    if (planned === 0 && !isProjet) return;

    if (spent <= planned) {
      respectedCats++;
    } else {
      totalOverspent += spent - planned;
      if (name.includes("épargne") || name.includes("projet") || name.includes("investissement")) {
        savingSacrificed = true;
      }
    }
  });

  let finalScore = Math.round((respectedCats / categories.length) * 10);
  if (savingSacrificed) finalScore = Math.max(0, finalScore - 4);

  scoreElement.innerText = `${finalScore}/10`;
  scoreElement.className = "text-xl font-black transition-all duration-500 " +
    (finalScore >= 8 ? "text-emerald-400" : finalScore >= 5 ? "text-yellow-500" : "text-red-500");

  const aSolde = categories.some((c) => (c.balance || 0) > 0) || projectCapital > 0;

  if (!aSolde) {
    coachMessageElement.innerHTML = `<span class="italic text-slate-500">Enregistre tes revenus pour activer le coaching Wari personnalisé. 🚀</span>`;
    return;
  }

  // Préparation du contexte complet pour l'IA
  const fullContext = {
    score: finalScore,
    total_depense: totalDepense,
    total_alloue: totalAlloue,
    reste_a_vivre: totalAlloue - totalDepense,
    categories: categories.map(c => ({
      nom: c.name,
      solde: c.balance,
      depense: currentExpenses[c.id] || 0
    })),
    dettes: (window.dbDebts || []).map(d => ({
      nom: d.person_name,
      montant: d.amount,
      type: d.type,
      echeance: d.due_date
    })),
    capital_coffre: projectCapital,
    devise: currency
  };

  fetchAiCoachAdvice(fullContext);
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
          ${result.prediction ? `<p class="text-[10px] text-amber-400/80 font-bold">PRÉDICTION : ${result.prediction}</p>` : ''}
          ${result.dette_conseil ? `<p class="text-[10px] text-blue-400/80 font-bold">DETTES : ${result.dette_conseil}</p>` : ''}
          ${result.academy_reco ? `<p class="text-[10px] text-emerald-400/80 font-bold">ACADEMY : "Apprends à ${result.academy_reco}"</p>` : ''}
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
    btn.innerHTML = `<span>🔓</span> <span class="text-amber-500">MODIFIER</span>`;
    btn.className =
      "flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-500/10 border border-amber-500/50 transition-all scale-105";
  } else {
    btn.innerHTML = `<span>🔒</span> <span class="text-slate-400">LECTURE</span>`;
    btn.className =
      "flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-800 border border-slate-700 transition-all";
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
  const labelEl = document.getElementById("vaultGoalLabel");
  const amountEl = document.getElementById("vaultGoalAmount");
  const progressBar = document.getElementById("vaultProgress");
  const deleteBtn = document.getElementById("deleteGoalBtn");

  if (!goal || !labelEl) {
    if (deleteBtn) {
      deleteBtn.classList.add("hidden");
      deleteBtn.classList.remove("flex");
    }
    return;
  }

  labelEl.innerText = goal.label;
  amountEl.innerText = `${projectCapital.toLocaleString()} / ${goal.amount.toLocaleString()} ${currency}`;

  if (progressBar) {
    progressBar.style.width = `${Math.min((projectCapital / goal.amount) * 100, 100)}%`;
  }
  if (deleteBtn) {
    deleteBtn.classList.remove("hidden");
    deleteBtn.classList.add("flex");
  }
}

window.deleteGoal = function () {
  if (!confirm("Supprimer cet objectif ?")) return;
  localStorage.removeItem("wari_vault_goal");
  document.getElementById("vaultGoalLabel").innerText = "—";
  document.getElementById("vaultGoalAmount").innerText = "";
  const deleteBtn = document.getElementById("deleteGoalBtn");
  if (deleteBtn) {
    deleteBtn.classList.add("hidden");
    deleteBtn.classList.remove("flex");
  }
  const progressBar = document.getElementById("vaultProgress");
  if (progressBar) {
    progressBar.style.width = `${Math.min((projectCapital / 1000000) * 100, 100)}%`;
  }
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

const WARI_VERSION = 55; // Ta version actuelle

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
            <div style="background:#0d1117; border:1px solid rgba(245,166,35,0.3); border-radius:35px; padding:35px; max-width:450px; width:100%; box-shadow: 0 25px 60px rgba(0,0,0,0.6); position:relative; overflow:hidden;">
                
                <!-- Badge Version -->
                <div style="position:absolute; top:20px; right:20px; background:#f5a623; color:#000; padding:5px 12px; border-radius:10px; font-size:10px; font-weight:900;">V1.5.5</div>

                <div style="text-align:center; margin-bottom:25px;">
                    <h2 style="color:#fff; font-weight:900; letter-spacing:-1px; text-transform:uppercase; margin:0;">Quoi de neuf ?</h2>
                    <p style="color:#f5a623; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:2px; margin-top:5px;">Wari Finance évolue pour toi</p>
                </div>

                <div style="max-height:300px; overflow-y:auto; padding-right:10px; margin-bottom:30px;" class="custom-scrollbar">
                    
                    <div style="margin-bottom:20px;">
                        <h4 style="color:#fff; font-size:14px; margin-bottom:5px;">Coach Wari Plus intelligent</h4>
                        <p style="color:#64748b; font-size:12px; line-height:1.5; margin:0;">Ton coach prédit désormais ta fin de mois et te conseille sur tes dettes prioritaires.</p>
                    </div>

                    <div style="margin-bottom:20px;">
                        <h4 style="color:#fff; font-size:14px; margin-bottom:5px;">Radar & Notifications</h4>
                        <p style="color:#64748b; font-size:12px; line-height:1.5; margin:0;">Nouveau guide visuel pour activer ton radar si ton téléphone bloque les alertes enfin de toujours rester informé</p>
                    </div>

                    <div style="margin-bottom:20px;">
                        <h4 style="color:#fff; font-size:14px; margin-bottom:5px;">Wari Academy</h4>
                        <p style="color:#64748b; font-size:12px; line-height:1.5; margin:0;">Wari te suggère maintenant les cours exacts dont tu as besoin selon tes dépenses.</p>
                    </div>

                    <div style="margin-bottom:10px;">
                        <h4 style="color:#fff; font-size:14px; margin-bottom:5px;">Corrections</h4>
                        <p style="color:#64748b; font-size:12px; line-height:1.5; margin:0;">Amélioration de la jauge de santé et synchronisation plus rapide des données.</p>
                    </div>

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