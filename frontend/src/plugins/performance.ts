import type { App } from 'vue'
import { useAccessibility } from '@/composables/useAccessibility'

/**
 * Plugin d'optimisation des performances et de l'accessibilité
 */
export default {
  install(app: App) {
    // Configuration globale pour les performances
    app.config.globalProperties.$performance = {
      // Mesurer les Core Web Vitals
      measureWebVitals() {
        if (typeof window !== 'undefined' && 'performance' in window) {
          // Largest Contentful Paint (LCP)
          new PerformanceObserver((entryList) => {
            const entries = entryList.getEntries()
            const lastEntry = entries[entries.length - 1]
            console.log('LCP:', lastEntry.startTime)
          }).observe({ entryTypes: ['largest-contentful-paint'] })

          // First Input Delay (FID)
          new PerformanceObserver((entryList) => {
            for (const entry of entryList.getEntries()) {
              console.log('FID:', entry.processingStart - entry.startTime)
            }
          }).observe({ entryTypes: ['first-input'] })

          // Cumulative Layout Shift (CLS)
          let clsValue = 0
          new PerformanceObserver((entryList) => {
            for (const entry of entryList.getEntries()) {
              if (!entry.hadRecentInput) {
                clsValue += entry.value
                console.log('CLS:', clsValue)
              }
            }
          }).observe({ entryTypes: ['layout-shift'] })
        }
      },

      // Précharger des ressources critiques
      preloadResource(href: string, as: string = 'script') {
        if (typeof document !== 'undefined') {
          const link = document.createElement('link')
          link.rel = 'preload'
          link.href = href
          link.as = as
          document.head.appendChild(link)
        }
      },

      // Optimiser les fonts
      optimizeFonts() {
        if (typeof document !== 'undefined') {
          const fontLinks = document.querySelectorAll('link[href*="fonts.googleapis.com"]')
          fontLinks.forEach((link) => {
            const linkElement = link as HTMLLinkElement
            linkElement.rel = 'preconnect'
            // Ajouter dns-prefetch pour les navigateurs plus anciens
            const dnsPrefetch = document.createElement('link')
            dnsPrefetch.rel = 'dns-prefetch'
            dnsPrefetch.href = 'https://fonts.gstatic.com'
            document.head.appendChild(dnsPrefetch)
          })
        }
      }
    }

    // Initialiser les optimisations globales
    if (typeof window !== 'undefined') {
      // Optimiser les fonts
      app.config.globalProperties.$performance.optimizeFonts()

      // Démarrer la mesure des Web Vitals en mode développement
      if (import.meta.env.DEV) {
        app.config.globalProperties.$performance.measureWebVitals()
      }

      // Service Worker pour le cache
      if ('serviceWorker' in navigator && import.meta.env.PROD) {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('/sw.js')
            .then((registration) => {
              console.log('SW registered: ', registration)
            })
            .catch((registrationError) => {
              console.log('SW registration failed: ', registrationError)
            })
        })
      }

      // Optimisations pour les connexions lentes
      if ('connection' in navigator) {
        const connection = (navigator as any).connection
        if (connection && connection.effectiveType === '2g') {
          // Désactiver les animations pour les connexions lentes
          document.documentElement.classList.add('slow-connection')
        }
      }

      // Préférences utilisateur pour l'économie de données
      if ('connection' in navigator) {
        const connection = (navigator as any).connection
        if (connection && connection.saveData) {
          document.documentElement.classList.add('save-data')
        }
      }
    }

    // Directive pour le lazy loading
    app.directive('lazy', {
      beforeMount(el, binding) {
        if ('IntersectionObserver' in window) {
          const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
              if (entry.isIntersecting) {
                const lazyElement = entry.target as HTMLElement
                if (binding.value && typeof binding.value === 'function') {
                  binding.value()
                }
                observer.unobserve(lazyElement)
              }
            })
          }, {
            rootMargin: '50px'
          })
          observer.observe(el)
          el._observer = observer
        } else {
          // Fallback pour les navigateurs qui ne supportent pas IntersectionObserver
          if (binding.value && typeof binding.value === 'function') {
            binding.value()
          }
        }
      },
      unmounted(el) {
        if (el._observer) {
          el._observer.disconnect()
        }
      }
    })

    // Directive pour les animations optimisées
    app.directive('animate', {
      beforeMount(el, binding) {
        const { value, modifiers } = binding
        const animationClass = value || 'animate-fade-in'
        
        // Respecter les préférences de mouvement réduit
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
        if (prefersReducedMotion && !modifiers.force) {
          return
        }

        if ('IntersectionObserver' in window) {
          const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
              if (entry.isIntersecting) {
                entry.target.classList.add(animationClass)
                observer.unobserve(entry.target)
              }
            })
          }, {
            threshold: 0.2
          })
          observer.observe(el)
          el._animationObserver = observer
        } else {
          el.classList.add(animationClass)
        }
      },
      unmounted(el) {
        if (el._animationObserver) {
          el._animationObserver.disconnect()
        }
      }
    })
  }
}

/**
 * Utilitaires pour l'optimisation des performances
 */
export const performanceUtils = {
  // Debounce pour optimiser les événements fréquents
  debounce<T extends (...args: any[]) => void>(
    func: T,
    delay: number
  ): (...args: Parameters<T>) => void {
    let timeoutId: ReturnType<typeof setTimeout>
    return (...args: Parameters<T>) => {
      clearTimeout(timeoutId)
      timeoutId = setTimeout(() => func(...args), delay)
    }
  },

  // Throttle pour limiter la fréquence d'exécution
  throttle<T extends (...args: any[]) => void>(
    func: T,
    delay: number
  ): (...args: Parameters<T>) => void {
    let inThrottle: boolean
    return (...args: Parameters<T>) => {
      if (!inThrottle) {
        func(...args)
        inThrottle = true
        setTimeout(() => inThrottle = false, delay)
      }
    }
  },

  // Mesurer les performances d'une fonction
  measure<T>(name: string, fn: () => T): T {
    const start = performance.now()
    const result = fn()
    const end = performance.now()
    console.log(`${name} took ${end - start} milliseconds`)
    return result
  },

  // Précharger une route
  preloadRoute(routeName: string) {
    if (typeof window !== 'undefined') {
      import(/* @vite-ignore */ `../views/${routeName}.vue`)
        .catch(err => console.warn('Failed to preload route:', routeName, err))
    }
  },

  // Optimiser les requêtes avec cache
  memoize<T extends (...args: any[]) => any>(fn: T): T {
    const cache = new Map()
    return ((...args: any[]) => {
      const key = JSON.stringify(args)
      if (cache.has(key)) {
        return cache.get(key)
      }
      const result = fn(...args)
      cache.set(key, result)
      return result
    }) as T
  }
}