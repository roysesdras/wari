<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.10/build/css/intlTelInput.css">

<form action="./assets/subscribe_whatsapp.php" method="POST" id="wa-form" class="max-w-md mx-auto">
    <div class="mb-4">
        <input type="tel" id="phone" name="full_number" required 
            class="w-full bg-slate-950 border border-white/10 rounded-2xl py-4 px-4 text-white outline-none focus:border-green-500/50 transition-all">
    </div>
    
    <input type="hidden" name="whatsapp" id="whatsapp_hidden">

    <button type="submit" class="w-full bg-green-600 text-white font-black py-3 rounded-2xl hover:bg-green-500 transition-all uppercase text-xs tracking-widest active:scale-95 shadow-lg shadow-green-900/20">
        S'abonner via WhatsApp
    </button>
</form>

<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.10/build/js/intlTelInput.min.js"></script>
<script>
    const input = document.querySelector("#phone");
    const hiddenInput = document.querySelector("#whatsapp_hidden");
    
    const iti = window.intlTelInput(input, {
        initialCountry: "bj", // Bénin par défaut
        preferredCountries: ["bj", "tg", "ci", "sn", "bf"], // Pays de l'UEMOA en premier
        utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.10/build/js/utils.js",
    });

    // Avant l'envoi, on récupère le numéro complet (ex: +229XXXXXXXX)
    document.querySelector("#wa-form").onsubmit = function() {
        hiddenInput.value = iti.getNumber();
    };
</script>

<style>
    /* Correction pour que la liste des pays s'adapte à ton thème sombre */
    .iti { width: 100%; }
    .iti__country-list { background-color: #020617; border: 1px solid #1e293b; color: white; }
    .iti__country:hover { background-color: #1e293b; }
    .iti__selected-country { background: transparent !important; }
</style>