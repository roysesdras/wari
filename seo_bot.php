<?php

/**
 * Wari-Finance SEO Bot - Sitemap propre (marketing uniquement)
 */

$base_url = "https://wari.digiroys.com";

// UNIQUEMENT les pages publiques SEO
$pages = [
    ["/accueil/", "1.0"]
];

$date = date('c'); // format ISO propre

$sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n\n";

foreach ($pages as $page) {
    $path = $page[0];
    $priority = $page[1];

    $sitemap .= "  <url>\n";
    $sitemap .= "    <loc>{$base_url}{$path}</loc>\n";
    $sitemap .= "    <lastmod>{$date}</lastmod>\n";
    $sitemap .= "    <changefreq>weekly</changefreq>\n";
    $sitemap .= "    <priority>{$priority}</priority>\n";
    $sitemap .= "  </url>\n\n";
}

$sitemap .= "</urlset>";

// 🔥 IMPORTANT : on met le sitemap dans /accueil/
file_put_contents('/var/www/wari.digiroys.com/accueil/sitemap.xml', $sitemap);

echo "[" . date('Y-m-d H:i:s') . "] ✅ Sitemap généré dans /accueil/\n";
