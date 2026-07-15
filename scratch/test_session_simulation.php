<?php
// Simulation of session_check.php logic (Strict FedaPay check post-2027)

function simulate($userEmail, $currentTimeStr, $date_expiration, $has_paid, $licence_statut = 'utilise') {
    $currentTime = strtotime($currentTimeStr);
    $transitionDate = strtotime('2027-01-01 00:00:00');
    $isTransitionActive = ($currentTime >= $transitionDate);

    // 1. Est premium si la licence est valide ET qu'il a payé via FedaPay
    $is_premium = ($date_expiration !== null && strtotime($date_expiration) >= $currentTime && $has_paid);

    // 2. Redirection / Blocage
    $is_redirected = false;
    if ($licence_statut === 'suspendu') {
        $is_redirected = true; // Redirigé pour suspension
    } else {
        if ($userEmail === 'info@rebonly.com' || $isTransitionActive) {
            // L'accès est bloqué si la licence est absente, expirée OU non payée via FedaPay
            $isExpired = (
                $date_expiration === null || 
                strtotime($date_expiration) < $currentTime ||
                !$has_paid
            );
            if ($isExpired) {
                $is_redirected = true;
            }
        }
    }

    return [
        'is_premium' => $is_premium ? '💎 PREMIUM' : '🎟️ GRATUIT',
        'access' => $is_redirected ? '❌ BLOQUÉ (Redirigé vers paiement)' : '✅ AUTORISÉ (Accès à l\'app)'
    ];
}

$scenarios = [
    // --- AVANT 2027 (Période d'essai libre) ---
    [
        'title' => "1. Utilisateur sans licence avant 2027 (ex: un visiteur gratuit)",
        'email' => "user@gmail.com", 'time' => "2026-07-05 12:00:00", 'exp' => null, 'paid' => false
    ],
    [
        'title' => "2. Utilisateur avec licence manuelle active avant 2027 (offerte par l'admin)",
        'email' => "friend@gmail.com", 'time' => "2026-07-05 12:00:00", 'exp' => "2026-08-05 12:00:00", 'paid' => false
    ],
    [
        'title' => "3. Utilisateur Premium actif avant 2027 (payé FedaPay)",
        'email' => "buyer@gmail.com", 'time' => "2026-07-05 12:00:00", 'exp' => "2026-08-05 12:00:00", 'paid' => true
    ],
    [
        'title' => "4. Utilisateur Premium expiré avant 2027",
        'email' => "buyer_old@gmail.com", 'time' => "2026-07-05 12:00:00", 'exp' => "2026-06-05 12:00:00", 'paid' => true
    ],

    // --- CAS AGILE (info@rebonly.com) ---
    [
        'title' => "5. Compte test agile info@rebonly.com avec licence Premium active",
        'email' => "info@rebonly.com", 'time' => "2026-07-05 12:00:00", 'exp' => "2026-08-05 12:00:00", 'paid' => true
    ],
    [
        'title' => "6. Compte test agile info@rebonly.com avec licence Premium expirée",
        'email' => "info@rebonly.com", 'time' => "2026-07-05 12:00:00", 'exp' => "2026-06-05 12:00:00", 'paid' => true
    ],
    [
        'title' => "7. Compte test agile info@rebonly.com sans licence",
        'email' => "info@rebonly.com", 'time' => "2026-07-05 12:00:00", 'exp' => null, 'paid' => false
    ],
    [
        'title' => "8. Compte test agile info@rebonly.com avec licence manuelle active (sans FedaPay)",
        'email' => "info@rebonly.com", 'time' => "2026-07-05 12:00:00", 'exp' => "2026-08-05 12:00:00", 'paid' => false
    ],

    // --- APRÈS LE 1er JANVIER 2027 (Lancement officiel 100% payant) ---
    [
        'title' => "9. Utilisateur sans licence après le 1er Janvier 2027",
        'email' => "user2027@gmail.com", 'time' => "2027-01-15 12:00:00", 'exp' => null, 'paid' => false
    ],
    [
        'title' => "10. Utilisateur avec licence manuelle active après le 1er Janvier 2027 (offerte mais non payée)",
        'email' => "friend2027@gmail.com", 'time' => "2027-01-15 12:00:00", 'exp' => "2027-02-15 12:00:00", 'paid' => false
    ],
    [
        'title' => "11. Utilisateur Premium actif après le 1er Janvier 2027 (payé FedaPay)",
        'email' => "buyer2027@gmail.com", 'time' => "2027-01-15 12:00:00", 'exp' => "2027-02-15 12:00:00", 'paid' => true
    ],
    [
        'title' => "12. Utilisateur avec licence Premium expirée après le 1er Janvier 2027",
        'email' => "buyer2027@gmail.com", 'time' => "2027-01-15 12:00:00", 'exp' => "2026-12-15 12:00:00", 'paid' => true
    ],
];

echo "============================================================\n";
echo "    SIMULATION DES ACCÈS ET DU PREMIUM (WARI FINANCE)\n";
echo "============================================================\n\n";

foreach ($scenarios as $s) {
    echo "📌 " . $s['title'] . "\n";
    echo "   📅 Date simulée : " . $s['time'] . "\n";
    echo "   📧 Email        : " . $s['email'] . "\n";
    $res = simulate($s['email'], $s['time'], $s['exp'], $s['paid']);
    echo "   🔑 Offre        : " . $res['is_premium'] . "\n";
    echo "   🛡️ Statut       : " . $res['access'] . "\n";
    echo "------------------------------------------------------------\n";
}
