const cacheName = "wari-v47"; // On passe de v16 à v17
// Fichiers statiques uniquement — NE PAS mettre en cache les pages d'auth/API
const assets = [
  "./manifest.json",
  "./assets/styles.css",
  "./assets/main.js",
  // Icônes ou autres ressources statiques si nécessaire
];

// 1. Installation : On enregistre les fichiers dans le cache du téléphone
self.addEventListener("install", (e) => {
  self.skipWaiting(); // Force la mise à jour immédiate
  e.waitUntil(
    caches.open(cacheName).then((cache) => {
      console.log("Wari : Mise en cache des fichiers statiques...");
      return cache.addAll(assets);
    }),
  );
});

// 2. Activation : On nettoie les vieux caches si nécessaire
self.addEventListener("activate", (e) => {
  e.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys
          .filter((key) => key !== cacheName)
          .map((key) => caches.delete(key)),
      );
    }),
  );
});

// 3. Stratégie réseau : Gestion intelligente du cache
self.addEventListener("fetch", (e) => {
  const req = e.request;
  const url = new URL(req.url);

  // A. Toujours bypasser le cache pour les requêtes POST (Auth, Formulaires)
  if (req.method !== "GET") {
    return e.respondWith(fetch(req));
  }

  // B. NE JAMAIS mettre en cache les pages PHP (Index, Auth, API)
  // On veut que le serveur PHP décide toujours quelle donnée afficher
  if (
    url.pathname.endsWith(".php") ||
    url.pathname.startsWith("/config/") ||
    url.pathname === "/"
  ) {
    return e.respondWith(
      fetch(req, { cache: "no-store" }).catch(() => {
        // En cas de panne totale de réseau (offline), on tente de montrer le manifest ou une page d'erreur
        return caches.match("./manifest.json");
      }),
    );
  }

  // C. Pour les assets statiques (CSS, JS, Images) : Cache d'abord
  // C. Pour les assets statiques (CSS, JS, Images) : Cache d'abord
  e.respondWith(
    caches.match(req).then((cacheRes) => {
      return (
        cacheRes ||
        fetch(req)
          .then((networkRes) => {
            // Optionnel : ne pas mettre en cache si ce n'est pas un succès
            if (
              !networkRes ||
              networkRes.status !== 200 ||
              networkRes.type !== "basic"
            ) {
              return networkRes;
            }
            return caches.open(cacheName).then((cache) => {
              // --- SÉCURITÉ : On ne met en cache que les requêtes HTTP/HTTPS ---
              // Cela évite les erreurs "chrome-extension" ou "data:"
              if (req.url.startsWith("http")) {
                cache.put(req, networkRes.clone());
              }

              return networkRes;
            });
          })
          .catch(() => {
            // ICI : Si c'est une navigation (page), on essaie de renvoyer le cache
            if (req.mode === "navigate") {
              return caches.match("./index.php"); // Ou une page offline.html
            }
          })
      );
    }),
  );
});

// 4. Gestion des notifications
self.addEventListener("push", function (event) {
  let data = {
    title: "Wari Finance",
    body: "💰",
    url: "https://wari.digiroys.com",
  };
  if (event.data) data = event.data.json();

  const options = {
    body: data.body,
    icon: "./assets/warifinance3d.png",
    badge: "./assets/warifinance3d.png",
    vibrate: [100, 50, 100],
    data: { url: data.url }, // ← URL transmise au clic
    actions: [
      { action: "explore", title: "Ouvrir Wari", icon: "check.png" },
      { action: "close", title: "Plus tard", icon: "xmark.png" },
    ],
  };

  event.waitUntil(self.registration.showNotification(data.title, options));
});

// Quand l'utilisateur clique sur la notification
self.addEventListener("notificationclick", function (event) {
  event.notification.close();
  const url = event.notification.data?.url || "https://wari.digiroys.com";
  event.waitUntil(clients.openWindow(url));
});
