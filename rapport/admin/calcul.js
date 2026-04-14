
    const depenseInput = document.querySelector('input[name="budget_depense"]');
    const recetteInput = document.querySelector('input[name="recettes_generees"]');
    const balanceDisplay = document.getElementById('balance_display');

    function calculerBalance() {
        const dep = parseFloat(depenseInput.value) || 0;
        const rec = parseFloat(recetteInput.value) || 0;
        const net = rec - dep;
        
        balanceDisplay.innerText = net.toLocaleString() + " XOF";
        
        // Couleur dynamique
        if(net > 0) balanceDisplay.style.color = "#10b981"; // Vert
        else if(net < 0) balanceDisplay.style.color = "#ef4444"; // Rouge
        else balanceDisplay.style.color = "#94a3b8"; // Gris
    }

    depenseInput.addEventListener('input', calculerBalance);
    recetteInput.addEventListener('input', calculerBalance);
    window.onload = calculerBalance; // Calcul au chargement pour l'édition
