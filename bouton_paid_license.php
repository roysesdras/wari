<!-- Bouton de Licence Wari Finance -->
<div class="fixed bottom-20 right-6 z-50 flex flex-col items-end font-sans">
    <!-- Bulle de message -->
    <div id="license-message" class="mb-3 translate-y-2 opacity-0 transition-all duration-500 ease-out bg-slate-950 border border-amber-500/40 shadow-2xl rounded-2xl p-4 pr-8 max-w-[200px] relative">
        <button onclick="closeLicenseMsg()" class="absolute top-1 right-1 text-slate-500 hover:text-amber-400 p-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <p class="text-[12px] font-bold text-slate-200 leading-tight">
            Champion•ne, <br>
            active ta <span class="text-amber-500 uppercase text-[10px] tracking-widest">LICENCE Wari Finance</span> ici
        </p>
        <div class="absolute -bottom-1.5 right-6 w-3 h-3 bg-slate-950 border-r border-b border-amber-500/40 rotate-45"></div>
    </div>

    <!-- Bouton Principal (Lien vers Paiement) -->
    <a href="https://wari.digiroys.com/paid/landing-vente.php"
        target="_blank"
        onclick="trackLicenseBuy()"
        class="flex items-center justify-center w-14 h-14 bg-slate-950 border-2 border-amber-500 rounded-full shadow-lg hover:bg-slate-800 transition-all duration-300 active:scale-95 group">
        
        <!-- Icône Carte Bancaire (Lucide CreditCard) -->
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-500">
            <rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/>
        </svg>
    </a>
</div>

<script>
    window.addEventListener('load', () => {
        const msgBulle = document.getElementById('license-message');
        setTimeout(() => {
            if (msgBulle) {
                msgBulle.classList.remove('translate-y-2', 'opacity-0');
                msgBulle.classList.add('translate-y-0', 'opacity-100');
            }
        }, 2000);
    });

    function closeLicenseMsg() {
        const msgBulle = document.getElementById('license-message');
        if (window.DigiStats) {
            window.DigiStats.track('close_license_bubble');
        }
        if (msgBulle) {
            msgBulle.classList.add('opacity-0', 'translate-y-2');
            setTimeout(() => { msgBulle.style.display = 'none'; }, 500);
        }
    }

    function trackLicenseBuy() {
        if (window.DigiStats && typeof window.DigiStats.track === 'function') {
            window.DigiStats.track('click_license_buy', {
                platform: 'web',
                label: 'Licence Wari Finance'
            });
        }
    }
</script>