const cacheName = "wari-v74"; // On passe à v74 pour le support hors ligne
// Fichiers statiques et page de secours
const assets = [
  "./manifest.json",
  "./assets/styles.css",
  "./assets/main.js",
  "./offline.php",
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

  // A. Bypasser le cache pour les requêtes POST (Auth, Formulaires)
  if (req.method !== "GET") {
    return e.respondWith(fetch(req));
  }

  // B. Ne pas mettre en cache les actions de session/API sensibles
  if (url.pathname.startsWith("/config/") || url.pathname.includes('logout.php')) {
    return e.respondWith(fetch(req));
  }

  // C. NETWORK FIRST : Pour les pages PHP et la navigation HTML
  // On tente le réseau en premier, puis on tombe sur le cache hors ligne
  if (
    req.mode === "navigate" ||
    url.pathname.endsWith(".php") ||
    url.pathname === "/"
  ) {
    e.respondWith(
      fetch(req)
        .then((networkRes) => {
          // Si on est en ligne, on met en cache la page fraîche !
          return caches.open(cacheName).then((cache) => {
            if (req.url.startsWith("http")) {
              cache.put(req, networkRes.clone());
            }
            return networkRes;
          });
        })
        .catch(async () => {
          // Si on est HORS LIGNE, on cherche d'abord la page dans le cache
          const cachedRes = await caches.match(req);
          if (cachedRes) {
            return cachedRes; // Page déjà visitée, on l'affiche !
          }
          // Si la page n'est pas dans le cache, on affiche la page offline de secours
          if (req.mode === "navigate") {
            return caches.match("./offline.php");
          }
        })
    );
    return;
  }

  // D. CACHE FIRST : Pour les assets statiques (CSS, JS, Images)
  e.respondWith(
    caches.match(req).then((cacheRes) => {
      return (
        cacheRes ||
        fetch(req).then((networkRes) => {
          if (
            !networkRes ||
            networkRes.status !== 200 ||
            networkRes.type !== "basic"
          ) {
            return networkRes;
          }
          return caches.open(cacheName).then((cache) => {
            if (req.url.startsWith("http")) {
              cache.put(req, networkRes.clone());
            }
            return networkRes;
          });
        })
      );
    })
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
