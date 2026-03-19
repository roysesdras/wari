<?php
// ── Charger les avis depuis la DB ──────────────────────────────────────────
// (supposé inclus après require 'connexion.php' qui définit $pdo)

$avis_list = [];
$avis_stats = ['total' => 0, 'moyenne' => 0, 'repartition' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]];

try {
    // Statistiques globales
    $stmt = $pdo->query("
        SELECT COUNT(*) as total, AVG(note) as moyenne,
               SUM(note=5) as n5, SUM(note=4) as n4, SUM(note=3) as n3,
               SUM(note=2) as n2, SUM(note=1) as n1
        FROM avis WHERE visible = 1
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($stats['total'] > 0) {
        $avis_stats = [
            'total'    => (int)$stats['total'],
            'moyenne'  => round((float)$stats['moyenne'], 1),
            'repartition' => [
                5 => (int)$stats['n5'],
                4 => (int)$stats['n4'],
                3 => (int)$stats['n3'],
                2 => (int)$stats['n2'],
                1 => (int)$stats['n1'],
            ]
        ];
    }

    // Liste des avis publiés
    $stmt = $pdo->query("
        SELECT nom, ville, note, texte, created_at
        FROM avis
        WHERE visible = 1
        ORDER BY created_at DESC
    ");
    $avis_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fail silently côté visiteur
}

// Initiales pour l'avatar
function initiales(string $nom): string
{
    $parts = explode(' ', trim($nom));
    $i = strtoupper(mb_substr($parts[0], 0, 1));
    if (count($parts) > 1) $i .= strtoupper(mb_substr(end($parts), 0, 1));
    return htmlspecialchars($i);
}

// Couleurs d'avatar cycliques
$avatar_colors = [
    ['bg' => 'rgba(45,212,191,.12)',  'color' => '#2dd4bf'],
    ['bg' => 'rgba(56,189,248,.12)',  'color' => '#38bdf8'],
    ['bg' => 'rgba(251,191,36,.12)',  'color' => '#fbbf24'],
    ['bg' => 'rgba(248,113,113,.12)', 'color' => '#f87171'],
];
?>

<!-- ══════════════════════════════════════════════════════════════════════
     SECTION AVIS — carousel dynamique
══════════════════════════════════════════════════════════════════════ -->
<section class="avis-section" id="avis" aria-label="Avis utilisateurs">

    <div class="avis-header">
        <span class="badge-avis">⭐ Ce qu'ils en disent</span>
        <h2>Avis de nos utilisateurs</h2>
        <p>Des personnes comme vous qui ont repris le contrôle de leurs finances.</p>
    </div>

    <?php if ($avis_stats['total'] > 0): ?>

        <!-- Score global ──────────────────────────────────────────────────── -->
        <div class="avis-score">
            <div class="score-main">
                <div class="score-number">
                    <?= $avis_stats['moyenne'] ?><span>/5</span>
                </div>
                <div class="score-stars">
                    <?php
                    $m = $avis_stats['moyenne'];
                    for ($s = 1; $s <= 5; $s++) {
                        if ($s <= $m) echo '<span class="star">★</span>';
                        elseif ($s - $m < 1) echo '<span class="star-half">★</span>';
                        else echo '<span class="star-empty">★</span>';
                    }
                    ?>
                </div>
                <div class="score-label">
                    Basé sur <?= $avis_stats['total'] ?> avis
                </div>
            </div>
            <div class="score-bars">
                <?php foreach ([5, 4, 3, 2, 1] as $nb): ?>
                    <?php $pct = $avis_stats['total'] > 0 ? round($avis_stats['repartition'][$nb] / $avis_stats['total'] * 100) : 0; ?>
                    <div class="score-bar-row">
                        <span><?= $nb ?>★</span>
                        <div class="score-mini-bg">
                            <div class="score-mini-fill" style="width:<?= $pct ?>%"></div>
                        </div>
                        <span class="score-count"><?= $avis_stats['repartition'][$nb] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php endif; ?>

    <!-- Carousel ──────────────────────────────────────────────────────── -->
    <?php if (!empty($avis_list)): ?>

        <div class="carousel-outer" id="carousel-outer">
            <div class="carousel-viewport" id="carousel-viewport">
                <div class="carousel-track" id="carousel-track">
                    <?php foreach ($avis_list as $i => $a):
                        $col = $avatar_colors[$i % count($avatar_colors)];
                        $loc = $a['ville'] ? htmlspecialchars($a['ville']) . ' · ' : '';
                        $date = date('M Y', strtotime($a['created_at']));
                    ?>
                        <div class="avis-card" role="listitem">
                            <div class="avis-stars">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <span class="<?= $s <= $a['note'] ? 'star' : 'star-empty' ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <p class="avis-text">"<?= htmlspecialchars($a['texte']) ?>"</p>
                            <div class="avis-author">
                                <div class="avis-avatar" style="background:<?= $col['bg'] ?>;color:<?= $col['color'] ?>;">
                                    <?= initiales($a['nom']) ?>
                                </div>
                                <div>
                                    <div class="avis-name"><?= htmlspecialchars($a['nom']) ?></div>
                                    <div class="avis-meta"><?= $loc ?><?= $date ?></div>
                                    <span class="avis-verified">✓ Utilisateur vérifié</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Contrôles carousel -->
            <div class="carousel-controls">
                <button class="carousel-btn" id="carousel-prev" aria-label="Précédent" disabled>‹</button>
                <div class="carousel-dots" id="carousel-dots" aria-label="Navigation avis"></div>
                <button class="carousel-btn" id="carousel-next" aria-label="Suivant">›</button>
            </div>
        </div>

    <?php else: ?>
        <p style="text-align:center;color:var(--muted);padding:2rem 0;">
            Soyez le premier à laisser un avis !
        </p>
    <?php endif; ?>

    <!-- CTA laisser un avis ───────────────────────────────────────────── -->
    <div class="avis-cta">
        <p>Vous utilisez Wari ? Partagez votre expérience.</p>
        <a href="laisser-avis.php" class="btn-avis">✍️ Laisser un avis</a>
    </div>

</section>

<!-- ══════════════════════════════════════════════
     CSS À AJOUTER dans le <style> de accueil.php
══════════════════════════════════════════════ -->
<style>
    /* ── SECTION AVIS ─────────────────────────────── */
    .avis-section {
        margin: 3rem 0;
    }

    .avis-header {
        text-align: center;
        margin-bottom: 2.5rem;
    }

    .badge-avis {
        background: rgba(45, 212, 191, 0.1);
        color: var(--teal);
        border: 1px solid rgba(45, 212, 191, 0.25);
        padding: 0.3rem 1rem;
        border-radius: 30px;
        font-size: 0.8rem;
        display: inline-block;
        margin-bottom: 1rem;
    }

    .avis-header h2 {
        margin-bottom: 0.5rem;
    }

    .avis-header p {
        margin-bottom: 0;
    }

    /* Score global */
    .avis-score {
        background: var(--surface2);
        border: 1px solid var(--border2);
        border-radius: 20px;
        padding: 1.5rem 1.8rem;
        display: flex;
        align-items: center;
        gap: 2rem;
        flex-wrap: wrap;
        justify-content: center;
        margin-bottom: 2rem;
    }

    .score-main {
        text-align: center;
    }

    .score-number {
        font-size: 3.5rem;
        font-weight: 700;
        color: #f1f5f9;
        line-height: 1;
    }

    .score-number span {
        font-size: 1.2rem;
        color: var(--muted);
        font-weight: 400;
    }

    .score-stars {
        display: flex;
        gap: 4px;
        margin: .3rem 0;
    }

    .score-stars .star {
        color: var(--amber);
        font-size: 1.2rem;
    }

    .score-stars .star-half {
        color: var(--amber);
        font-size: 1.2rem;
        opacity: .6;
    }

    .score-stars .star-empty {
        color: var(--border2);
        font-size: 1.2rem;
    }

    .score-label {
        font-size: 0.85rem;
        color: var(--muted);
    }

    .score-bars {
        flex: 1;
        min-width: 180px;
    }

    .score-bar-row {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        margin-bottom: 0.4rem;
        font-size: 0.8rem;
        color: var(--muted);
    }

    .score-bar-row span:first-child {
        min-width: 24px;
    }

    .score-count {
        min-width: 20px;
        text-align: right;
    }

    .score-mini-bg {
        flex: 1;
        background: var(--bg2);
        height: 5px;
        border-radius: 5px;
        overflow: hidden;
    }

    .score-mini-fill {
        background: var(--amber);
        height: 5px;
        border-radius: 5px;
    }

    /* Carousel */
    .carousel-outer {
        position: relative;
    }

    .carousel-viewport {
        overflow: hidden;
        border-radius: 16px;
    }

    .carousel-track {
        display: flex;
        gap: 1rem;
        transition: transform .4s cubic-bezier(.4, 0, .2, 1);
    }

    /* Cartes */
    .avis-card {
        flex: 0 0 calc(33.333% - .68rem);
        /* 3 par page desktop */
        min-width: 0;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 1.5rem;
        position: relative;
        transition: border-color .2s;
    }

    .avis-card::before {
        content: '\201C';
        position: absolute;
        top: 1rem;
        right: 1.2rem;
        font-size: 3.5rem;
        font-weight: 700;
        color: var(--teal);
        opacity: .1;
        line-height: 1;
        pointer-events: none;
    }

    .avis-stars {
        display: flex;
        gap: 3px;
        margin-bottom: .8rem;
    }

    .star {
        color: var(--amber);
        font-size: .9rem;
    }

    .star-empty {
        color: var(--border2);
        font-size: .9rem;
    }

    .avis-text {
        font-size: .93rem;
        color: #c8d8e8;
        line-height: 1.65;
        margin-bottom: 1.2rem;
        font-style: italic;
    }

    .avis-author {
        display: flex;
        align-items: center;
        gap: .75rem;
    }

    .avis-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: .85rem;
        border: 1.5px solid var(--border2);
        flex-shrink: 0;
    }

    .avis-name {
        font-size: .9rem;
        font-weight: 600;
        color: #f1f5f9;
    }

    .avis-meta {
        font-size: .78rem;
        color: var(--muted);
        margin-top: 1px;
    }

    .avis-verified {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .72rem;
        color: var(--teal);
        background: rgba(45, 212, 191, .08);
        border: 1px solid rgba(45, 212, 191, .2);
        border-radius: 20px;
        padding: .15rem .5rem;
        margin-top: .3rem;
    }

    /* Contrôles */
    .carousel-controls {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .8rem;
        margin-top: 1.2rem;
    }

    .carousel-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--surface2);
        border: 1px solid var(--border2);
        color: var(--text);
        font-size: 1.2rem;
        line-height: 1;
        cursor: pointer;
        transition: background .15s, opacity .15s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .carousel-btn:disabled {
        opacity: .3;
        cursor: default;
    }

    .carousel-btn:not(:disabled):hover {
        background: var(--surface);
        border-color: var(--teal);
        color: var(--teal);
    }

    .carousel-dots {
        display: flex;
        gap: 6px;
        align-items: center;
    }

    .carousel-dot {
        width: 7px;
        height: 7px;
        border-radius: 7px;
        background: var(--border2);
        transition: width .2s, background .2s;
        border: none;
        cursor: pointer;
        padding: 0;
    }

    .carousel-dot.active {
        width: 18px;
        background: var(--teal);
    }

    /* CTA */
    .avis-cta {
        text-align: center;
        margin-top: 2rem;
    }

    .avis-cta p {
        font-size: .9rem;
        margin-bottom: 1rem;
    }

    .btn-avis {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        background: transparent;
        color: var(--teal);
        font-weight: 600;
        padding: .75rem 1.8rem;
        border-radius: 40px;
        border: 1.5px solid rgba(45, 212, 191, .4);
        text-decoration: none;
        font-size: .95rem;
        transition: background .2s, border-color .2s, transform .15s;
    }

    .btn-avis:hover {
        background: rgba(45, 212, 191, .08);
        border-color: var(--teal);
        transform: translateY(-2px);
    }

    /* ── RESPONSIVE carousel ───────────────────────── */
    @media (max-width: 767px) {
        .avis-card {
            flex: 0 0 calc(85% - .5rem);
        }
    }

    @media (min-width: 768px) and (max-width: 1023px) {
        .avis-card {
            flex: 0 0 calc(50% - .5rem);
        }
    }

    /* desktop : 3 cartes — déjà défini ci-dessus */
</style>


<!-- ══════════════════════════════════════════════
     JS À AJOUTER avant </body> de accueil.php
══════════════════════════════════════════════ -->
<script>
    (function() {
        var track = document.getElementById('carousel-track');
        var dotsEl = document.getElementById('carousel-dots');
        var btnPrev = document.getElementById('carousel-prev');
        var btnNext = document.getElementById('carousel-next');
        if (!track) return;

        var cards = track.querySelectorAll('.avis-card');
        var total = cards.length;
        var pos = 0; // index de la première carte visible
        var perPage = 3; // recalculé au resize
        var autoTimer;

        /* ── Nombre de cartes par page selon largeur ── */
        function calcPerPage() {
            var w = track.parentElement.offsetWidth;
            if (w < 600) return 1;
            if (w < 900) return 2;
            return 3;
        }

        /* ── Construire les dots ── */
        function buildDots() {
            dotsEl.innerHTML = '';
            var pages = Math.ceil(total / perPage);
            for (var i = 0; i < pages; i++) {
                var btn = document.createElement('button');
                btn.className = 'carousel-dot' + (i === 0 ? ' active' : '');
                btn.setAttribute('aria-label', 'Aller à la page ' + (i + 1));
                btn.dataset.page = i;
                btn.addEventListener('click', function() {
                    goTo(parseInt(this.dataset.page) * perPage);
                });
                dotsEl.appendChild(btn);
            }
        }

        /* ── Aller à la position pos ── */
        function goTo(newPos) {
            pos = Math.max(0, Math.min(newPos, total - perPage));
            /* Largeur d'une carte + gap en % */
            var pct = (100 / perPage);
            var gapPx = 16; /* = 1rem gap */
            track.style.transform =
                'translateX(calc(-' + pos + ' * (' + pct + '% + ' + gapPx / perPage + 'px)))';
            btnPrev.disabled = pos === 0;
            btnNext.disabled = pos >= total - perPage;
            /* Dots */
            var page = Math.floor(pos / perPage);
            dotsEl.querySelectorAll('.carousel-dot').forEach(function(d, i) {
                d.classList.toggle('active', i === page);
            });
        }

        /* ── Boutons ── */
        btnPrev.addEventListener('click', function() {
            resetAuto();
            goTo(pos - perPage);
        });
        btnNext.addEventListener('click', function() {
            resetAuto();
            goTo(pos + perPage);
        });

        /* ── Autoplay toutes les 5s ── */
        function autoPlay() {
            autoTimer = setInterval(function() {
                var next = pos + perPage;
                goTo(next >= total ? 0 : next);
            }, 5000);
        }

        function resetAuto() {
            clearInterval(autoTimer);
            autoPlay();
        }

        /* ── Pause au survol ── */
        var outer = document.getElementById('carousel-outer');
        if (outer) {
            outer.addEventListener('mouseenter', function() {
                clearInterval(autoTimer);
            });
            outer.addEventListener('mouseleave', autoPlay);
        }

        /* ── Touch/swipe ── */
        var touchStartX = 0;
        track.addEventListener('touchstart', function(e) {
            touchStartX = e.touches[0].clientX;
            clearInterval(autoTimer);
        }, {
            passive: true
        });
        track.addEventListener('touchend', function(e) {
            var diff = touchStartX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 40) {
                goTo(diff > 0 ? pos + 1 : pos - 1);
            }
            autoPlay();
        }, {
            passive: true
        });

        /* ── Resize ── */
        function onResize() {
            var newPer = calcPerPage();
            if (newPer !== perPage) {
                perPage = newPer;
                pos = 0;
                buildDots();
                goTo(0);
            }
        }
        window.addEventListener('resize', onResize);

        /* ── Init ── */
        perPage = calcPerPage();
        buildDots();
        goTo(0);
        autoPlay();
    })();
</script>