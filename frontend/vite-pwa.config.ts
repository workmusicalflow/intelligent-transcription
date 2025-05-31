import { VitePWA } from 'vite-plugin-pwa'

export const pwaConfig = VitePWA({
  registerType: 'autoUpdate',
  workbox: {
    globPatterns: ['**/*.{js,css,html,ico,png,svg,json,vue,txt,woff2}'],
    cleanupOutdatedCaches: true,
    skipWaiting: true,
    runtimeCaching: [
      {
        urlPattern: /^https:\/\/fonts\.googleapis\.com\/.*/i,
        handler: 'CacheFirst',
        options: {
          cacheName: 'google-fonts-cache',
          expiration: {
            maxEntries: 10,
            maxAgeSeconds: 60 * 60 * 24 * 365 // 1 year
          },
          cacheKeyWillBeUsed: async ({ request }) => {
            return `${request.url}?v=1`
          }
        }
      },
      {
        urlPattern: /^https:\/\/fonts\.gstatic\.com\/.*/i,
        handler: 'CacheFirst',
        options: {
          cacheName: 'gstatic-fonts-cache',
          expiration: {
            maxEntries: 10,
            maxAgeSeconds: 60 * 60 * 24 * 365 // 1 year
          }
        }
      },
      {
        urlPattern: /\/api\/v2\/.*/i,
        handler: 'NetworkFirst',
        options: {
          cacheName: 'api-cache',
          expiration: {
            maxEntries: 100,
            maxAgeSeconds: 60 * 60 * 24 // 1 day
          },
          networkTimeoutSeconds: 10,
          cacheKeyWillBeUsed: async ({ request }) => {
            // Remove auth headers from cache key for privacy
            const url = new URL(request.url)
            return url.href
          }
        }
      },
      {
        urlPattern: /\/graphql/i,
        handler: 'NetworkFirst',
        options: {
          cacheName: 'graphql-cache',
          expiration: {
            maxEntries: 50,
            maxAgeSeconds: 60 * 30 // 30 minutes
          },
          networkTimeoutSeconds: 10
        }
      },
      {
        urlPattern: /\.(?:png|jpg|jpeg|svg|gif|webp)$/i,
        handler: 'CacheFirst',
        options: {
          cacheName: 'images-cache',
          expiration: {
            maxEntries: 100,
            maxAgeSeconds: 60 * 60 * 24 * 30 // 30 days
          }
        }
      }
    ]
  },
  includeAssets: ['favicon.ico', 'apple-touch-icon.png', 'masked-icon.svg'],
  manifest: {
    name: 'Intelligent Transcription',
    short_name: 'IT',
    description: 'Application de transcription intelligente avec IA',
    theme_color: '#3b82f6',
    background_color: '#ffffff',
    display: 'standalone',
    orientation: 'portrait',
    scope: '/',
    start_url: '/',
    categories: ['productivity', 'business', 'utilities'],
    lang: 'fr',
    icons: [
      {
        src: '/pwa-192x192.png',
        sizes: '192x192',
        type: 'image/png'
      },
      {
        src: '/pwa-512x512.png',
        sizes: '512x512',
        type: 'image/png'
      },
      {
        src: '/pwa-512x512.png',
        sizes: '512x512',
        type: 'image/png',
        purpose: 'any maskable'
      }
    ],
    screenshots: [
      {
        src: '/screenshots/desktop-1.png',
        sizes: '1280x720',
        type: 'image/png',
        form_factor: 'wide'
      },
      {
        src: '/screenshots/mobile-1.png',
        sizes: '360x640',
        type: 'image/png',
        form_factor: 'narrow'
      }
    ],
    shortcuts: [
      {
        name: 'Nouvelle transcription',
        short_name: 'Nouvelle',
        description: 'Créer une nouvelle transcription',
        url: '/transcriptions/create',
        icons: [
          {
            src: '/icons/shortcut-transcription.png',
            sizes: '192x192'
          }
        ]
      },
      {
        name: 'Chat',
        short_name: 'Chat',
        description: 'Ouvrir le chat contextuel',
        url: '/chat',
        icons: [
          {
            src: '/icons/shortcut-chat.png',
            sizes: '192x192'
          }
        ]
      },
      {
        name: 'Mes transcriptions',
        short_name: 'Mes fichiers',
        description: 'Voir mes transcriptions',
        url: '/transcriptions',
        icons: [
          {
            src: '/icons/shortcut-files.png',
            sizes: '192x192'
          }
        ]
      }
    ],
    prefer_related_applications: false,
    edge_side_panel: {
      preferred_width: 400
    }
  },
  devOptions: {
    enabled: process.env.NODE_ENV === 'development',
    type: 'module',
    navigateFallback: 'index.html'
  }
})