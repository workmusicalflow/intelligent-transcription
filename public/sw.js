// Service Worker pour la mise en cache et les performances
const CACHE_NAME = 'transcription-intelligente-v1'
const STATIC_CACHE = 'static-v1'
const DYNAMIC_CACHE = 'dynamic-v1'

// Ressources à mettre en cache immédiatement
const STATIC_ASSETS = [
  '/',
  '/index.html',
  '/manifest.json',
  '/icon-192x192.png',
  '/icon-512x512.png'
]

// Stratégies de cache
const CACHE_STRATEGIES = {
  // Cache d'abord pour les ressources statiques
  CACHE_FIRST: 'cache-first',
  // Réseau d'abord pour les API
  NETWORK_FIRST: 'network-first',
  // Stale while revalidate pour les ressources souvent mises à jour
  STALE_WHILE_REVALIDATE: 'stale-while-revalidate'
}

// Installation du service worker
self.addEventListener('install', event => {
  console.log('Service Worker installing...')
  
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => {
        console.log('Caching static assets')
        return cache.addAll(STATIC_ASSETS)
      })
      .then(() => {
        return self.skipWaiting()
      })
  )
})

// Activation du service worker
self.addEventListener('activate', event => {
  console.log('Service Worker activating...')
  
  event.waitUntil(
    caches.keys()
      .then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            // Supprimer les anciens caches
            if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE) {
              console.log('Deleting old cache:', cacheName)
              return caches.delete(cacheName)
            }
          })
        )
      })
      .then(() => {
        return self.clients.claim()
      })
  )
})

// Interception des requêtes
self.addEventListener('fetch', event => {
  const { request } = event
  const url = new URL(request.url)

  // Ignorer les requêtes non-HTTP
  if (!request.url.startsWith('http')) {
    return
  }

  // Stratégie selon le type de ressource
  if (request.destination === 'document') {
    // HTML: Network first avec fallback
    event.respondWith(networkFirstStrategy(request))
  } else if (request.url.includes('/api/')) {
    // API: Network first
    event.respondWith(networkFirstStrategy(request))
  } else if (request.destination === 'image') {
    // Images: Cache first
    event.respondWith(cacheFirstStrategy(request))
  } else if (request.destination === 'script' || request.destination === 'style') {
    // JS/CSS: Stale while revalidate
    event.respondWith(staleWhileRevalidateStrategy(request))
  } else {
    // Autres: Cache first
    event.respondWith(cacheFirstStrategy(request))
  }
})

// Stratégie Cache First
async function cacheFirstStrategy(request) {
  try {
    const cachedResponse = await caches.match(request)
    if (cachedResponse) {
      return cachedResponse
    }

    const response = await fetch(request)
    if (response.ok) {
      const cache = await caches.open(DYNAMIC_CACHE)
      cache.put(request, response.clone())
    }
    return response
  } catch (error) {
    console.error('Cache first strategy failed:', error)
    return new Response('Offline', { status: 503 })
  }
}

// Stratégie Network First
async function networkFirstStrategy(request) {
  try {
    const response = await fetch(request)
    if (response.ok) {
      const cache = await caches.open(DYNAMIC_CACHE)
      cache.put(request, response.clone())
    }
    return response
  } catch (error) {
    console.log('Network failed, trying cache:', error)
    const cachedResponse = await caches.match(request)
    if (cachedResponse) {
      return cachedResponse
    }
    
    // Fallback pour les pages HTML
    if (request.destination === 'document') {
      return caches.match('/offline.html') || 
             new Response('Offline', { 
               status: 503,
               headers: { 'Content-Type': 'text/html' }
             })
    }
    
    throw error
  }
}

// Stratégie Stale While Revalidate
async function staleWhileRevalidateStrategy(request) {
  const cache = await caches.open(DYNAMIC_CACHE)
  const cachedResponse = await cache.match(request)
  
  const fetchPromise = fetch(request).then(response => {
    if (response.ok) {
      cache.put(request, response.clone())
    }
    return response
  }).catch(error => {
    console.error('Fetch failed for stale-while-revalidate:', error)
    return cachedResponse
  })

  return cachedResponse || fetchPromise
}

// Nettoyage périodique du cache
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'CLEANUP_CACHE') {
    cleanupCache()
  }
})

async function cleanupCache() {
  const cache = await caches.open(DYNAMIC_CACHE)
  const requests = await cache.keys()
  
  // Garder seulement les 100 entrées les plus récentes
  if (requests.length > 100) {
    const oldRequests = requests.slice(0, requests.length - 100)
    await Promise.all(oldRequests.map(request => cache.delete(request)))
  }
}

// Synchronisation en arrière-plan pour les données critiques
self.addEventListener('sync', event => {
  if (event.tag === 'background-sync') {
    event.waitUntil(doBackgroundSync())
  }
})

async function doBackgroundSync() {
  try {
    // Synchroniser les données critiques quand la connexion revient
    console.log('Background sync triggered')
    // Implémenter la logique de synchronisation ici
  } catch (error) {
    console.error('Background sync failed:', error)
  }
}

// Push notifications (si nécessaire)
self.addEventListener('push', event => {
  if (event.data) {
    const data = event.data.json()
    const options = {
      body: data.body,
      icon: '/icon-192x192.png',
      badge: '/icon-192x192.png',
      data: data.url,
      actions: [
        {
          action: 'open',
          title: 'Ouvrir'
        },
        {
          action: 'close',
          title: 'Fermer'
        }
      ]
    }
    
    event.waitUntil(
      self.registration.showNotification(data.title, options)
    )
  }
})

// Gestion des clics sur les notifications
self.addEventListener('notificationclick', event => {
  event.notification.close()
  
  if (event.action === 'open') {
    event.waitUntil(
      clients.openWindow(event.notification.data || '/')
    )
  }
})