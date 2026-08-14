<?php
/**
 * WARI SECURITY HEADERS
 * Centralise et applique les 9 en-têtes de sécurité HTTP de l'application.
 */
if (!headers_sent()) {
    // 1. Content-Security-Policy (CSP) - Whitelist sécurisée de tous les CDN et services de l'application
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://stats.digiroys.com https://cdn.jsdelivr.net https://cdn.quilljs.com https://cdn.fedapay.com https://cdn.cinetpay.com https://api.fedapay.com https://api.cinetpay.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdn.quilljs.com https://cdn.tailwindcss.com; font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data: blob: https: http:; connect-src 'self' https://stats.digiroys.com https://api.fedapay.com https://checkout.fedapay.com https://api-checkout.cinetpay.com https://cdn.tailwindcss.com https:; frame-src 'self' https://www.youtube.com https://youtube.com https://www.youtube-nocookie.com https://player.vimeo.com https://checkout.fedapay.com https://api-checkout.cinetpay.com https://*.fedapay.com https://*.cinetpay.com; media-src 'self' blob: https:; object-src 'none'; base-uri 'self'; form-action 'self' https:;");

    // 2. Strict-Transport-Security (HSTS)
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");

    // 3. X-Content-Type-Options
    header("X-Content-Type-Options: nosniff");

    // 4. X-Frame-Options
    header("X-Frame-Options: SAMEORIGIN");

    // 5. Referrer-Policy
    header("Referrer-Policy: strict-origin-when-cross-origin");

    // 6. Permissions-Policy
    header("Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(self)");

    // 7. Cross-Origin-Opener-Policy (COOP)
    header("Cross-Origin-Opener-Policy: same-origin-allow-popups");

    // 8. Cross-Origin-Resource-Policy (CORP)
    header("Cross-Origin-Resource-Policy: same-origin");

    // 9. Cross-Origin-Embedder-Policy (COEP)
    header("Cross-Origin-Embedder-Policy: unsafe-none");

    // 10. Web Application Firewall Identification
    header("X-Protected-By: ModSecurity-WAF");
}
