// 1. Configuration initiale
//window.dernierAjoutProjet = 0;

let categories = [
  { id: 3, name: "Projet", percent: 25, icon: "🚀", balance: 0 },
  { id: 1, name: "Épargne", percent: 15, icon: "💰", balance: 0 },
  { id: 4, name: "Imprévu", percent: 10, icon: "🆘", balance: 0 },
  { id: 2, name: "Train de vie", percent: 50, icon: "🏠", balance: 0 },
];

let projectCapital = 0; // Le cumul pour tes investissements
let isEditMode = false; // Verrouillé par défaut
let isInitialLoad = true; // Empêche notifications au démarrage
let vaultTransactions = []; // À ajouter dans ton loadBudget plus tard
//let hasDepositedToday = false; // Empêche le double versement

//let currentBalances = {}; // Contiendra { 1: 5000, 2: 3000, ... }

const mainInput = document.getElementById("mainAmount");
const container = document.getElementById("categoryContainer");

// Vérifier si nous sommes sur la bonne page avant d'exécuter le code
if (!mainInput || !container) {
  console.log("Wari-Finance: Éléments principaux non trouvés - arrêt du script");
  // Arrêter l'exécution du script si nous ne sommes pas sur la page principale
  return;
}

// 3. Fonction principale (Calculs + UI)
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

  const aDuSolde = categories.some((cat) => (cat.balance || 0) > 0);

  // --- BOUTON COFFRE : remplacé par affichage informatif ---
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

  // ─── CALCULS PAR CATÉGORIE ──────────────────────────────────────
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

  // Ajustement des arrondis
  const difference = total - calculatedTotalAmount;
  if (difference !== 0 && results.length > 0) {
    const biggestCat = results.reduce((p, c) =>
      p.percent > c.percent ? p : c,
    );
    biggestCat.amount += difference;
  }

  // ─── AFFICHAGE DES CARTES ───────────────────────────────────────
  container.innerHTML = "";

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

  results.forEach((cat) => {
    const isProjet = cat.name.toLowerCase().includes("projet");
    const spent =
      typeof currentExpenses !== "undefined" && currentExpenses[cat.id]
        ? parseInt(currentExpenses[cat.id])
        : 0;

    // ── CARTE PROJET : mode journal ────────────────────────────────
    if (isProjet) {
      // partSimulation = ce qui va être versé au coffre si on clique Mettre à jour
      const partSimulation = cat.amount - (cat.balance || 0); // = cat.amount car balance=0

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
      card.className = `glass-card p-2 flex flex-col gap-2 relative mb-4 transition-all duration-300 border-l-4 border-amber-500/50 hover:border-amber-500/80`;

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
                ${spent > 0 ? `${spent.toLocaleString()} ${currency} prélevés sur le capital` : "Aucune dépense sur ce projet"}
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

      <div class="mt-3 pt-2 border-t border-slate-800/30">
        <p class="text-[9px] text-slate-600 italic tracking-wide">
          ${getCatSubtitle(cat.name)}
        </p>
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
  `;

      container.appendChild(card);
      return;
    }

    // ── TOUTES LES AUTRES CARTES : rendu normal ────────────────────
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

      <div class="mt-1 pt-3 border-t border-slate-800/20">
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

      <div class="mt-1 pt-2 border-t border-slate-800/30">
        <p class="text-[9px] text-slate-600 italic tracking-wide mt-1">
          ${getCatSubtitle(cat.name)}
        </p>
      </div>
    `;

    container.appendChild(card);
  }); // ← FIN results.forEach — tout le reste est EN DEHORS

  // ─── CALCUL BANQUE / CASH ───────────────────────────────────────
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

    // Pour le Projet : on utilise le capital coffre comme référence
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
  if (bankSpentEl && bankSpent > 0)
    bankSpentEl.innerText = `▼ ${bankSpent.toLocaleString()} dépensés`;
  if (cashSpentEl && cashSpent > 0)
    cashSpentEl.innerText = `▼ ${cashSpent.toLocaleString()} dépensés`;

  // ─── JAUGE DE SANTÉ ─────────────────────────────────────────────
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
      alertMsg = "⚠️ Budget entamé — ralentis avant la fin du mois.";
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
// Le reste de ton render() continuera de s'exécuter même s'il y a une erreur ici

window.confirmerRepartition = function () {
  const total = parseFloat(mainInput.value) || 0;
  if (total <= 0) return alert("Rien à répartir !");

  if (confirm(`Voulez-vous répartir ${total.toLocaleString()} ?`)) {
    dernierAjoutProjet = 0; // Reset

    categories.forEach((cat) => {
      const part = Math.round((total * cat.percent) / 100);
      cat.balance = (cat.balance || 0) + part;

      // --- DIAGNOSTIC ICI ---
      console.log("Analyse catégorie :", cat.name);

      if (
        cat.name.toLowerCase().includes("projet") ||
        cat.name.toLowerCase().includes("épargne")
      ) {
        dernierAjoutProjet = part;
        console.log("MATCH TROUVÉ ! Montant réservé :", dernierAjoutProjet);
      }
    });

    if (dernierAjoutProjet === 0) {
      alert(
        "ATTENTION : Aucune catégorie 'Projet' ou 'Épargne' n'a été détectée dans votre liste !",
      );
    }

    mainInput.value = "";
    saveBudget();
    render();
  }
};

// 4. Fonctions du Coffre-fort Projet
function updateVaultDisplay() {
  const projectEl = document.getElementById("totalProjectSaved");
  const progressBar = document.getElementById("vaultProgress");
  const currency = document.getElementById("currencySelector")?.value || "F";

  if (projectEl) {
    const oldAmount = parseInt(projectEl.innerText.replace(/[^0-9]/g, "")) || 0;

    // 1. Mise à jour du texte
    projectEl.innerText = `${projectCapital.toLocaleString()} ${currency}`;

    // 2. Animation de "Flash" (Vert si ça monte, Rouge si ça baisse)
    if (projectCapital > oldAmount) {
      projectEl.classList.add("text-emerald-400", "scale-110");
      setTimeout(() => projectEl.classList.remove("scale-110"), 300);
    } else if (projectCapital < oldAmount) {
      projectEl.classList.add("text-red-400", "scale-95");
      setTimeout(() => projectEl.classList.remove("scale-95"), 300);
    }

    // 3. Gestion de la barre de progression (Objectif imaginaire de 1.000.000 pour le visuel)
    if (progressBar) {
      // Remplace : const goal = 1000000;
      const savedGoal = JSON.parse(
        localStorage.getItem("wari_vault_goal") || "null",
      );
      const goal = savedGoal ? savedGoal.amount : 1000000;
      const progress = Math.min((projectCapital / goal) * 100, 100);
      progressBar.style.width = `${progress}%`;
    }

    // Reset des couleurs après l'animation
    setTimeout(() => {
      projectEl.classList.remove("text-emerald-400", "text-red-400");
    }, 2000);
  }
}

window.addToProjectVault = function (event) {
  const btn =
    event?.currentTarget ||
    document.querySelector('button[onclick*="addToProjectVault"]');
  if (!btn) return;

  // STRATÉGIE RADICALE : On extrait le nombre directement du texte du bouton
  // Si le bouton dit "+2 500 F", on récupère 2500
  const texteBouton = btn.innerText;
  const montantExtrait = parseInt(texteBouton.replace(/[^0-9]/g, "")) || 0;

  if (montantExtrait <= 0) {
    return alert(
      "Aucun nouvel ajout détecté. Répartissez d'abord de l'argent !",
    );
  }

  const currency = document.getElementById("currencySelector")?.value || "F";

  if (
    confirm(
      `Sécuriser les ${montantExtrait.toLocaleString()} ${currency} dans le coffre ?`,
    )
  ) {
    // 1. Mise à jour du capital global
    projectCapital += montantExtrait;

    // 2. On déduit le montant de la catégorie Projet pour ne pas l'avoir en double
    const projectCat = categories.find(
      (c) =>
        c.name.toLowerCase().includes("projet") ||
        c.name.toLowerCase().includes("épargne"),
    );
    if (projectCat) {
      projectCat.balance = Math.max(
        0,
        (projectCat.balance || 0) - montantExtrait,
      );
    }

    // 3. Historique et reset
    addVaultTransaction("in", montantExtrait, "Transfert vers Capital");
    window.dernierAjoutProjet = 0;

    // 4. Feedback visuel immédiat
    btn.disabled = true;
    btn.classList.add("opacity-50", "grayscale");
    btn.innerHTML = `<span>✅</span> CAPITAL SÉCURISÉ`;

    saveBudget();
    render();
  }
};

window.resetVault = function () {
  if (confirm("Voulez-vous réinitialiser votre capital accumulé à 0 ?")) {
    projectCapital = 0;
    saveBudget(true); // ✅ silent = true → pas d'alerte "montant requis"
    render();
  }
};

// 5. Utilitaires & Sauvegarde
window.updatePercent = function (id, val) {
  const cat = categories.find((c) => c.id === id);
  if (cat) {
    cat.percent = parseInt(val);
    render();
    notifyUnsavedChanges(); // On active l'alerte
  }
};

window.updateName = function (id, val) {
  const cat = categories.find((c) => c.id === id);
  if (cat) {
    cat.name = val;
    notifyUnsavedChanges(); // On active l'alerte
  }
};

function notifyUnsavedChanges() {
  const saveBtn = document.querySelector('button[onclick="saveBudget()"]');
  if (saveBtn) {
    // Style "Alerte Modification"
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

    // On change le texte et l'icône temporairement
    saveBtn.querySelector("span").innerText = "Valider les modifs ?";
  }
}

function updateStatus(total) {
  const status = document.getElementById("statusIndicator");
  const text = document.getElementById("statusText");

  // On prépare une base de classe commune pour éviter les répétitions
  const baseClass =
    "mt-8 flex items-center justify-center p-2 rounded-2xl border transition-all duration-300 ";

  if (total === 100) {
    // VICTOIRE : Le budget est parfait
    status.className =
      baseClass +
      "bg-emerald-500/10 border-emerald-500/30 text-emerald-500 shadow-[0_0_15px_rgba(16,185,129,0.2)]";
    text.innerHTML = `<span class="mr-2">✅</span> 100% - Ton argent est dompté !`;
  } else if (total === 0) {
    // ATTENTE : L'appli est prête
    status.className =
      baseClass + "bg-slate-800/50 border-slate-700 text-slate-500";
    text.innerHTML = `💰 WARI-FINANCE : Prêt pour le calcul`;
  } else {
    // ERREUR : Trop ou pas assez
    const isOver = total > 100;
    status.className =
      baseClass +
      "bg-orange-500/10 border-orange-500/30 text-orange-500 animate-pulse";

    // Message plus "coach" pour l'utilisateur
    if (isOver) {
      text.innerHTML = `<span class="mr-2">⚠️</span> Oups ! ${total}% ? Tu dépenses plus que tu n'as.`;
    } else {
      text.innerHTML = `<span class="mr-2">⏳</span> ${total}% répartis... Continue jusqu'à 100%.`;
    }
  }
}

window.saveBudget = function (silent = false) {
  console.log("Démarrage de la sauvegarde...");

  const totalAAjouter = parseFloat(mainInput.value) || 0;
  const currentCurrency =
    document.getElementById("currencySelector")?.value || "F";

  if (totalAAjouter <= 0 && !silent) {
    alert("Veuillez entrer un montant avant de valider.");
    return;
  }

  // ─── MISE À JOUR DES CATÉGORIES ──────────────────────────────────
  categories = categories.map((cat) => {
    const partNouvelle = Math.round((totalAAjouter * cat.percent) / 100);
    const isProjet = cat.name.toLowerCase().includes("projet");

    if (isProjet) {
      // ✅ Versement immédiat et automatique au coffre
      projectCapital += partNouvelle;
      addVaultTransaction(
        "in",
        partNouvelle,
        "Versement automatique — répartition",
      );
      console.log(`Coffre alimenté : +${partNouvelle} ${currentCurrency}`);

      // ✅ Carte Projet vidée — elle ne stocke plus rien
      return { ...cat, balance: 0 };
    }

    // Toutes les autres catégories : cumul normal
    console.log(
      `Nouveau solde pour ${cat.name} : ${(cat.balance || 0) + partNouvelle}`,
    );
    return { ...cat, balance: (cat.balance || 0) + partNouvelle };
  });

  // ─── SAUVEGARDE ──────────────────────────────────────────────────
  const dataToSave = {
    categories: categories,
    projectCapital: projectCapital,
    currency: currentCurrency,
    vaultTransactions: vaultTransactions,
    lastSavedMonth: new Date().toISOString().slice(0, 7), // "2026-03"
  };

  // Local
  localStorage.setItem("wari_budget_data", JSON.stringify(dataToSave));
  console.log("Données sauvegardées dans le LocalStorage");

  // Nettoyage et rendu
  mainInput.value = "";
  render();
  console.log("Rendu rafraîchi");

  // Synchro serveur en arrière-plan
  fetch("config/save_data.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(dataToSave),
  })
    .then(() => console.log("Synchro serveur réussie"))
    .catch((err) =>
      console.warn("Erreur synchro serveur (mais local OK) :", err),
    );
};

function loadBudget() {
  let data = null;

  // ─── RÉCUPÉRATION DES DONNÉES ────────────────────────────────────
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

  // ─── TRAITEMENT DES DONNÉES ──────────────────────────────────────
  if (data) {
    // ── RESET AU NOUVEAU MOIS ──────────────────────────────────────
    // Uniquement les balances des catégories NON-Projet
    // Le coffre (projectCapital) lui ne se remet jamais à zéro — il est cumulatif
    const currentMonth = new Date().toISOString().slice(0, 7); // "2026-03"
    const lastSavedMonth = data.lastSavedMonth || null;

    if (lastSavedMonth && lastSavedMonth !== currentMonth) {
      console.log("Nouveau mois détecté — reset des balances");

      // ✅ Séparateur de clôture AVANT le reset
      vaultTransactions.unshift({
        date: lastSavedMonth,
        type: "separator",
        label: `── Clôture ${lastSavedMonth} ──`,
        amount: 0,
      });

      setTimeout(() => {
        alert(
          "🌟 NOUVEAU MOIS, NOUVEL OBJECTIF !\n\nFélicitations Champion·ne, tes compteurs sont remis à zéro. Le coffre, lui, continue de grandir !",
        );
      }, 1000);

      // Reset uniquement les catégories hors Projet
      if (data.categories) {
        data.categories = data.categories.map((cat) => {
          const isProjet = cat.name.toLowerCase().includes("projet");
          // Projet reste à 0 de toute façon — les autres se remettent à zéro
          return { ...cat, balance: 0 };
        });
      }

      // On met à jour le mois sauvegardé
      data.lastSavedMonth = currentMonth;

      // On sauvegarde immédiatement le reset
      localStorage.setItem("wari_budget_data", JSON.stringify(data));
    }

    // ── REMPLISSAGE DES VARIABLES GLOBALES ────────────────────────
    if (data.categories) categories = data.categories;
    projectCapital = data.projectCapital || 0;
    vaultTransactions = data.vaultTransactions || [];

    // ── DEVISE ────────────────────────────────────────────────────
    if (data.currency) {
      const selector = document.getElementById("currencySelector");
      if (selector) selector.value = data.currency;
    }

    // ── PLUS DE hasDepositedToday ─────────────────────────────────
    // Supprimé — le versement est automatique à chaque saveBudget()
    // Le bouton coffre est désormais un simple affichage informatif

    // ── RENDU FINAL ───────────────────────────────────────────────
    render();
    console.log("Interface Wari mise à jour.");
  } else {
    // Aucune donnée — rendu vide par défaut
    render();
  }
}

// Appeler loadBudget au démarrage
loadBudget();
// ON LANCE LA SYNCHRO DB ICI
loadVaultHistory();
updateGoalDisplay();

setTimeout(() => {
  isInitialLoad = false;
  console.log("Wari-Finance est prêt.");
}, 500);

function loadVaultHistory() {
  fetch("config/get_vault_history.php")
    .then((res) => res.json())
    .then((data) => {
      if (data.success && data.history) {
        // On remplit notre tableau global avec les données de la DB
        vaultTransactions = data.history;
        // On affiche l'historique dans le dashboard
        renderVaultHistory();
      }
    })
    .catch((err) => console.error("Erreur lors du chargement du coffre:", err));
}

// founction pour dettes
function renderDebts() {
  const debtList = document.getElementById("debtList");

  // 1. Récupérer la devise globale choisie par l'utilisateur
  const currencyElement = document.getElementById("currencySelector");
  const currency = currencyElement ? currencyElement.value : "F";

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

// Appelle la fonction au démarrage
document.addEventListener("DOMContentLoaded", renderDebts);

function applyModel(modelKey) {
  const models = {
    wari: [
      { id: 1, name: "Épargne", percent: 15, icon: "💰" },
      { id: 2, name: "Train de vie", percent: 55, icon: "🏠" },
      { id: 3, name: "Projet", percent: 20, icon: "🚀" },
      { id: 4, name: "Imprévu", percent: 10, icon: "🆘" },
    ],
  };
  if (confirm("Appliquer ce modèle ?")) {
    categories = JSON.parse(JSON.stringify(models[modelKey]));
    render();
    saveBudget(true); // Silencieux, on sauvegarde juste les nouveaux %
  }
}

mainInput.addEventListener("input", () => {
  // On ne réactive plus le bouton ici car il dépend désormais
  // du solde de la catégorie et non de la saisie en cours.

  render(); // On garde uniquement le rafraîchissement visuel
});

window.onload = function () {
  isInitialLoad = false;
};

// Function pour ouvrire le modal depenses
window.openExpenseModal = function () {
  const modal = document.getElementById("expenseModal");
  const select = document.getElementById("expCategory");

  // 1. Récupérer et appliquer la devise actuelle
  const currency = document.getElementById("currencySelector")?.value || "F";
  document
    .querySelectorAll(".currencyLabel")
    .forEach((el) => (el.innerText = currency));

  // 2. Remplir le sélecteur de catégories
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
    body: JSON.stringify({
      amount: amount,
      category_id: catId,
      description: note,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        // Mise à jour locale pour le graphique
        if (!currentExpenses[catId]) currentExpenses[catId] = 0;
        currentExpenses[catId] = parseInt(currentExpenses[catId]) + amount;

        // Logique Capital Projet
        const cat = categories.find((c) => c.id == catId);
        if (cat && cat.name.toLowerCase().includes("projet")) {
          projectCapital = Math.max(0, projectCapital - amount);
          addVaultTransaction("out", amount, note);
          // saveBudget();
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

// pour les dettes.
window.openDebtModal = () => {
  // Récupérer la devise actuelle
  const currency = document.getElementById("currencySelector")?.value || "F";

  // Mettre à jour tous les petits indicateurs de devise dans les modals
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

  // Petite touche émotionnelle
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

window.markAsPaid = async (id) => {
  if (!confirm("Cette dette est-elle vraiment réglée ?")) return;

  const res = await fetch("config/pay_debt.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      person,
      amount,
      type,
      due_date: document.getElementById("debtDueDate").value || null,
    }),
  });

  const data = await res.json();
  if (data.success) {
    location.reload(); // On rafraîchit pour mettre à jour la liste
  }
};

// Ouvrire modal de payDebt
window.openPayModal = (id, name, currentAmount, type) => {
  const currency = document.getElementById("currencySelector")?.value || "F";

  // Mettre à jour les labels de devise (F, $, €)
  document.querySelectorAll(".currencyLabel").forEach((el) => {
    el.innerText = currency;
  });

  document.getElementById("payDebtId").value = id;
  document.getElementById("payDebtType").value = type;

  // On formate le message avec la devise dynamique
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

  // Récupérer la devise actuelle pour le message de confirmation
  const currency = document.getElementById("currencySelector")?.value || "F";

  if (!amount || amount <= 0) {
    alert("Veuillez saisir un montant valide.");
    return;
  }

  // --- TOUCHE ÉMOTIONNELLE & SÉCURITÉ ---
  const actionLabel = type === "loan" ? "reçu" : "remboursé";
  const confirmMsg = `Tu confirmes avoir ${actionLabel} ${amount} ${currency} ? \nChaque petit pas compte pour ta liberté financière.`;

  if (!confirm(confirmMsg)) return;

  const res = await fetch("config/partial_pay.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id, amount, type }),
  });

  const data = await res.json();
  if (data.success) {
    // Petit trick : on ajoute un paramètre de temps pour éviter le cache navigateur
    window.location.href = window.location.pathname + "?updated=" + Date.now();
  } else {
    alert("Erreur lors de l'enregistrement. Réessaie.");
  }
};

window.addVaultTransaction = function (type, amount, label) {
  // 1. Enregistrement local pour l'affichage immédiat
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

  // 2. Envoi au serveur pour sauvegarde permanente
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

// Exemple d'utilisation :
// addVaultTransaction('in', 50000, 'Investissement mensuel');
// addVaultTransaction('out', 15000, 'Achat matériel pour projet');
// addVaultTransaction('in', 20000, 'Revenu passif généré');
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
      // Rendu spécial pour le séparateur de mois
      if (tx.type === "separator") {
        return `
        <div class="flex items-center gap-2 py-1">
          <div class="flex-1 h-[1px] bg-slate-700/50"></div>
          <span class="text-[9px] text-slate-600 font-bold uppercase tracking-widest">
            ${tx.label}
          </span>
          <div class="flex-1 h-[1px] bg-slate-700/50"></div>
        </div>
      `;
      }

      // Rendu normal
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

// Function pour Coach
function generateFinancialReport() {
  if (!categories || categories.length === 0) return;

  const scoreElement = document.getElementById("disciplineScore");
  const progressBar = document.getElementById("budgetSuccessBar");
  const progressText = document.getElementById("budgetSuccessText");
  const coachMessageElement = document.getElementById("aiCoachMessage");
  const currency = document.getElementById("currencySelector")?.value || "F";

  if (!scoreElement || !progressBar || !coachMessageElement) return;

  const totalCats = categories.length;
  let respectedCats = 0;
  let totalOverspent = 0;
  let savingSacrificed = false;

  categories.forEach((cat) => {
    const spent = currentExpenses[cat.id] || 0;
    const name = cat.name.toLowerCase();
    const isProjet = name.includes("projet");

    // ✅ Pour Projet : on compare les dépenses au capital coffre
    // Pour les autres : on compare au solde de la catégorie
    const planned = isProjet ? projectCapital : cat.balance || 0;

    if (planned === 0 && !isProjet) return; // Catégorie vide non-Projet → on ignore

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
    "text-3xl font-black transition-all duration-500 " +
    (finalScore >= 8
      ? "text-emerald-400 animate-bounce"
      : finalScore >= 5
        ? "text-yellow-500"
        : "text-red-500");

  const successPercent = Math.round((respectedCats / totalCats) * 100);
  progressBar.style.width = `${successPercent}%`;
  if (progressText)
    progressText.innerText = `${successPercent}% des objectifs tenus`;

  let message = "";
  const aSolde =
    categories.some((c) => (c.balance || 0) > 0) || projectCapital > 0;

  if (!aSolde) {
    message = "Enregistre tes revenus pour activer le coaching Wari. 🚀";
  } else if (savingSacrificed) {
    message = `🚨 ALERTE : Tu as pioché dans tes réserves ! Tu as pris ${totalOverspent.toLocaleString()} ${currency} à ton futur.`;
  } else if (finalScore >= 9) {
    message =
      "💎 EXPERT : Ton épargne et tes projets sont en sécurité. Ta souveraineté financière avance !";
  } else if (finalScore >= 7) {
    message =
      "✅ BIEN : L'essentiel est sauf. Surveille juste tes petits écarts de train de vie.";
  } else if (finalScore >= 5) {
    message = `⚖️ FRAGILE : Tu as dépassé de ${totalOverspent.toLocaleString()} ${currency}. Tes envies d'aujourd'hui menacent tes projets de demain.`;
  } else {
    message =
      "⚠️ NAUFRAGE : Tu dépenses sans regarder. Ferme les vannes avant de couler !";
  }

  coachMessageElement.innerHTML = `<span class="italic">"${message}"</span>`;
}

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
    saveBudget(true); // Sauvegarde auto quand on verrouille
  }
  render(); // Indispensable pour rafraîchir l'état "disabled"
};

// Demande de permission pour les notifications (pour les alertes de dépenses ou rappels)
function requestNotificationPermission() {
  if ("Notification" in window) {
    Notification.requestPermission();
  }
}

// Appelle-la au démarrage
requestNotificationPermission();

// fonction pour afficher une notification du Coach Wari (à appeler dans les moments clés, ex: après l'ajout d'une dépense)
/**
 * Affiche une notification stylée du Coach Wari
 * @param {string} title - Le titre de l'alerte
 * @param {string} message - Le conseil du coach
 * @param {number} score - Le score de discipline (0 à 10)
 */
function showWariNotification(title, message, score) {
  // 1. Vérification des permissions
  if (Notification.permission !== "granted") {
    console.warn("Les notifications ne sont pas autorisées.");
    return;
  }

  // 2. Personnalisation de l'icône selon le score (Optionnel mais pro)
  // Vert pour le succès (>7), Orange pour la vigilance, Rouge pour l'alerte (<4)
  let statusIcon = "https://cdn-icons-png.flaticon.com/512/1998/1998592.png"; // Icône par défaut
  if (score >= 8)
    statusIcon = "https://cdn-icons-png.flaticon.com/512/190/190411.png"; // Check vert
  if (score < 5)
    statusIcon = "https://cdn-icons-png.flaticon.com/512/564/564619.png"; // Alerte rouge

  const options = {
    body: message,
    icon: statusIcon,
    badge: "https://cdn-icons-png.flaticon.com/512/1998/1998592.png", // Petite icône dans la barre d'état
    vibrate: score < 5 ? [500, 110, 500, 110, 450] : [200, 100, 200], // Vibration plus forte si le score est mauvais
    tag: "wari-alert", // Remplace la précédente pour ne pas polluer
    renotify: true, // Fait vibrer même si le tag est le même
    data: {
      url: window.location.origin + "/dashboard", // Pour rediriger au clic
      date: Date.now(),
    },
  };

  // 3. Exécution via Service Worker (Recommandé pour PWA)
  if ("serviceWorker" in navigator) {
    navigator.serviceWorker.ready
      .then((registration) => {
        registration.showNotification(`Coach Wari : ${title}`, options);
      })
      .catch((err) => {
        console.error("Erreur Service Worker:", err);
        new Notification(`Coach Wari : ${title}`, options); // Fallback
      });
  } else {
    new Notification(`Coach Wari : ${title}`, options); // Fallback si pas de SW
  }
}

// On crée une fonction asynchrone pour pouvoir utiliser "await"
async function initialiserNotificationsWari() {
  try {
    // 1. On demande la permission (L'erreur venait d'ici)
    const permission = await Notification.requestPermission();

    if (permission === "granted") {
      console.log("Notifications activées pour le Futur Riche !");

      const now = new Date();
      const today = now.toDateString();
      const lastNotify = localStorage.getItem("wari_last_notification");

      // 2. On lance l'abonnement Web Push pour ta base SQL
      // Assure-toi que cette fonction est bien définie quelque part avant
      if (typeof subscribeUserToPush === "function") {
        subscribeUserToPush();
      }

      // 3. Le rappel de discipline après 10 secondes
      setTimeout(() => {
        if (lastNotify !== today && now.getHours() >= 18) {
          navigator.serviceWorker.ready.then((registration) => {
            const messages = [
              "Cher.e ami.e, fais un petit point sur tes entrées-sorties 💰",
              "Salut, n'oublie pas de noter tes dépenses pour rester discipliné. 🚀",
              "Champion.ne, as-tu enregistré tes flux pour aujourd'hui ? 🌟",
            ];
            const randomMsg =
              messages[Math.floor(Math.random() * messages.length)];

            registration.showNotification("Wari - Coach", {
              body: randomMsg,
              icon: "https://i.postimg.cc/NFhtHvBK/wari-logos-sfnd.png",
              vibrate: [200, 100, 200],
              badge: "https://i.postimg.cc/NFhtHvBK/wari-logos-sfnd.png",
            });

            localStorage.setItem("wari_last_notification", today);
          });
        }
      }, 10000);
      checkDebtReminders();
    }
  } catch (error) {
    console.error("Erreur lors de l'initialisation des notifications :", error);
  }
}

// ON APPELLE LA FONCTION
initialiserNotificationsWari();
// ICI : On profite que la permission est accordée pour enregistrer le Web Push
// C'est cette fonction qui va remplir ta base de données !

// --- FONCTION INDISPENSABLE POUR LE WEB PUSH ---
function urlBase64ToUint8Array(base64String) {
  const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, "+").replace(/_/g, "/");
  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  return outputArray;
}

async function forceNewSubscription() {
  // 1. On déclare et on demande la permission (Indispensable !)
  const permission = await Notification.requestPermission();

  if (permission !== "granted") {
    console.error("Le Leader a refusé ou bloqué les notifications.");
    return;
  }

  // 2. On attend que le Service Worker soit prêt
  const registration = await navigator.serviceWorker.ready;

  try {
    // 3. On nettoie l'ancien abonnement pour éviter les conflits
    const oldSub = await registration.pushManager.getSubscription();
    if (oldSub) {
      await oldSub.unsubscribe();
      console.log("Ancien abonnement nettoyé.");
    }

    // 4. On crée le nouvel abonnement avec TA CLÉ PUBLIQUE
    // REMPLACE BIEN "TA_CLE_PUBLIQUE_ICI" PAR TA VRAIE CLÉ
    const newSub = await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(
        "BH9WpcuMhkSEOjnwf8KVZfDTv9Ps6nGaQ9RQ77e4D15ywgPmO7wNgTlldejjFjyWCp3PoBYareDXjlFBTdpzm40",
      ),
    });

    console.log("Nouvel abonnement créé :", newSub);

    // 5. Envoi au serveur PHP
    const response = await fetch("config/save_subscription.php", {
      method: "POST",
      body: JSON.stringify(newSub),
      headers: { "Content-Type": "application/json" },
    });

    const text = await response.text();
    console.log("Réponse du serveur PHP :", text);
  } catch (err) {
    console.error("Échec critique de l'abonnement :", err);
  }
}

// On lance la fonction
forceNewSubscription();

// Historique mensuel
window.openHistoryModal = function () {
  const modal = document.getElementById("historyModal");
  modal.classList.remove("hidden");
  modal.classList.add("flex");
  loadMonthlyHistory(); // On charge les données
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
                <div class="bg-slate-800/50 p-3 rounded-xl border border-slate-700/50">
                    <p class="text-amber-400 font-black text-[10px] uppercase tracking-widest mb-2">${month.label}</p>
                    <div class="space-y-1">
                        <div class="flex justify-between text-[10px]">
                            <span class="text-slate-400">Réparti</span>
                            <span class="text-white font-bold">${parseInt(month.total_distributed).toLocaleString()} ${currency}</span>
                        </div>
                        <div class="flex justify-between text-[10px]">
                            <span class="text-slate-400">Dépensé</span>
                            <span class="text-red-400 font-bold">${parseInt(month.total_spent).toLocaleString()} ${currency}</span>
                        </div>
                        <div class="flex justify-between text-[10px]">
                            <span class="text-slate-400">Épargné</span>
                            <span class="text-emerald-400 font-bold">${parseInt(month.total_saved).toLocaleString()} ${currency}</span>
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

// --- OBJECTIFS COFFRE ---
window.openGoalModal = function () {
  // Pré-remplir si un objectif existe déjà
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
  const currency = document.getElementById("currencySelector")?.value || "F";
  if (!label || !amount) return alert("Remplis les deux champs.");

  // Sauvegarde locale
  localStorage.setItem("wari_vault_goal", JSON.stringify({ label, amount }));

  // Mise à jour visuelle immédiate
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
    // Pas d'objectif : cacher le bouton supprimer
    if (deleteBtn) {
      deleteBtn.classList.add("hidden");
      deleteBtn.classList.remove("flex");
    }
    return;
  }

  labelEl.innerText = goal.label;
  amountEl.innerText = `${projectCapital.toLocaleString()} / ${goal.amount.toLocaleString()} ${currency}`;

  if (progressBar) {
    const progress = Math.min((projectCapital / goal.amount) * 100, 100);
    progressBar.style.width = `${progress}%`;
  }

  if (deleteBtn) {
    deleteBtn.classList.remove("hidden");
    deleteBtn.classList.add("flex");
  }
}

// ← EN DEHORS de updateGoalDisplay
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
    const progress = Math.min((projectCapital / 1000000) * 100, 100);
    progressBar.style.width = `${progress}%`;
  }
};

// Vérifie les rappels de dettes  (à appeler au démarrage et après chaque ajout de dette)
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
