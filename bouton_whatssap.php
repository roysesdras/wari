<div class="fixed bottom-20 right-6 z-50 flex flex-col items-end font-sans">

    <div id="whatsapp-message" class="mb-3 translate-y-2 opacity-0 transition-all duration-500 ease-out bg-slate-950 border border-amber-500/40 shadow-2xl rounded-2xl p-4 pr-8 max-w-[200px] relative">
        <button onclick="closeWhatsappMsg()" class="absolute top-1 right-1 text-slate-500 hover:text-amber-400 p-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <p class="text-[12px] font-bold text-slate-200 leading-tight">
            Champion•ne, <br>
            demande ton<span class="text-amber-500 uppercase text-[10px] tracking-widest"> PASS d'activation</span> gratuit ici
        </p>
        <div class="absolute -bottom-1.5 right-6 w-3 h-3 bg-slate-950 border-r border-b border-amber-500/40 rotate-45"></div>
    </div>

    <a href="https://wa.me/22961418976?text=Salut%20je%20souhaite%20recevoir%20mon%20PASS%20d%27activation%20gratuit%20pour%20Wari."
        target="_blank"
        onclick="trackWariCommunity()"
        class="flex items-center justify-center w-14 h-14 bg-slate-950 border-2 border-amber-500 rounded-full shadow-lg hover:bg-slate-700 transition-all duration-300 active:scale-95 group">

        <svg width="40" height="40" class="text-amber-500" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M21 14.645c.036-.052.07-.106.107-.156A8.182 8.182 0 0 0 22.5 9.93c.015-4.654-3.912-8.43-8.768-8.43-4.236 0-7.77 2.882-8.597 6.709a8.1 8.1 0 0 0-.188 1.726c0 4.66 3.777 8.537 8.633 8.537.772 0 1.812-.233 2.382-.389a15.46 15.46 0 0 0 1.282-.42c.147-.055.375-.112.558-.06l3.629 1.05a.187.187 0 0 0 .234-.228l-.83-3.164c-.058-.234-.066-.278.165-.616Z"></path>
            <path d="M14.65 19.468a7.75 7.75 0 0 1-1.09.096c-1.989 0-3.867-.525-5.39-1.51a8.629 8.629 0 0 1-2.49-2.311c-1.222-1.62-1.888-3.68-1.888-5.836 0-.146.005-.287.01-.429a.203.203 0 0 0-.353-.146 7.442 7.442 0 0 0-.697 9.152c.116.176.181.313.161.404l-.66 3.387a.187.187 0 0 0 .245.212l3.187-1.136a.788.788 0 0 1 .606.01c.954.375 2.009.606 3.064.606a7.943 7.943 0 0 0 5.467-2.156.2.2 0 0 0-.172-.343Z"></path>
        </svg>
    </a>
</div>

<script>
    // Pour tes tests, on enlève la sécurité de session
    window.addEventListener('load', () => {
        const msgBulle = document.getElementById('whatsapp-message');

        setTimeout(() => {
            if (msgBulle) {
                msgBulle.classList.remove('translate-y-2', 'opacity-0');
                msgBulle.classList.add('translate-y-0', 'opacity-100');
            }
        }, 2000); // 2 secondes pour tester vite
    });

    function closeWhatsappMsg() {
        const msgBulle = document.getElementById('whatsapp-message');
        if (msgBulle) {
            msgBulle.classList.remove('opacity-100', 'translate-y-0');
            msgBulle.classList.add('opacity-0', 'translate-y-2');
            // On attend la fin de l'animation avant de masquer
            setTimeout(() => {
                msgBulle.style.display = 'none';
            }, 500);
        }
    }

    function trackWariCommunity() {
        // On vérifie si ton tracker est bien chargé
        if (window.WariTracker || window.tracker) {
            // Adapte le nom de la fonction selon la doc de ton tracker Digiroys
            // Généralement c'est .track() ou .send()
            try {
                tracker.send('click_community_join', {
                    page: 'dashboard',
                    label: 'Cercle Wari',
                    timestamp: new Date().toISOString()
                });
                console.log("📊 Analytics : Clic Communauté enregistré sur Digiroys.");
            } catch (e) {
                console.error("Erreur Analytics :", e);
            }
        }
    }



    /**
     * Clic sur le bouton et envoi l'info à Digiroys via DigiStats
     */
    function trackWariCommunity() {
        console.log("🚀 Tentative d'envoi à Digiroys...");

        // On utilise le nom exact exposé par ton tracker.js
        if (window.DigiStats && typeof window.DigiStats.track === 'function') {
            window.DigiStats.track('click_community_join', {
                platform: 'whatsapp',
                label: 'Le Cercle Wari'
            });
            console.log("✅ Envoyé avec succès via DigiStats.track !");
        } else {
            console.warn("❌ DigiStats est introuvable. Vérifie le chargement de tracker.js.");
        }
    }

    /**
     * Fermeture de la bulle
     */
    function closeWhatsappMsg() {
        const msgBulle = document.getElementById('whatsapp-message');

        // Envoi de l'info de fermeture
        if (window.DigiStats) {
            window.DigiStats.track('close_community_bubble');
        }

        if (msgBulle) {
            msgBulle.classList.add('opacity-0', 'translate-y-2');
            setTimeout(() => {
                msgBulle.style.display = 'none';
            }, 500);
        }
    }
</script>