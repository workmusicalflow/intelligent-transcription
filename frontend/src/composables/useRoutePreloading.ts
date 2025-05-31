import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

interface PreloadConfig {
  routes: string[]
  delay?: number
  priority?: 'high' | 'low'
  condition?: () => boolean
}

/**
 * Composable pour le préchargement des routes critiques
 */
export function useRoutePreloading(config: PreloadConfig) {
  const router = useRouter()
  const route = useRoute()
  const isPreloading = ref(false)
  const preloadedRoutes = ref<Set<string>>(new Set())

  const {
    routes,
    delay = 2000,
    priority = 'low',
    condition = () => true
  } = config

  // Précharger une route spécifique
  const preloadRoute = async (routeName: string) => {
    if (preloadedRoutes.value.has(routeName)) {
      return
    }

    try {
      // Marquer comme en cours de préchargement
      preloadedRoutes.value.add(routeName)

      // Trouver la route dans la configuration du router
      const routeRecord = router.getRoutes().find(r => r.name === routeName)
      if (!routeRecord || !routeRecord.components) {
        return
      }

      // Si c'est une fonction (lazy loading), l'exécuter
      const component = routeRecord.components?.default
      if (typeof component === 'function') {
        await component()
        console.log(`Route preloaded: ${routeName}`)
      }
    } catch (error) {
      console.warn(`Failed to preload route ${routeName}:`, error)
      preloadedRoutes.value.delete(routeName)
    }
  }

  // Précharger plusieurs routes
  const preloadRoutes = async (routeNames: string[]) => {
    isPreloading.value = true

    try {
      // Précharger les routes critiques en parallèle
      const criticalRoutes = routeNames.slice(0, 2)
      await Promise.allSettled(
        criticalRoutes.map(routeName => preloadRoute(routeName))
      )

      // Précharger les autres routes avec un délai
      const otherRoutes = routeNames.slice(2)
      for (const routeName of otherRoutes) {
        await new Promise(resolve => setTimeout(resolve, 100))
        await preloadRoute(routeName)
      }
    } finally {
      isPreloading.value = false
    }
  }

  // Précharger au survol d'un lien
  const preloadOnHover = (routeName: string) => {
    return {
      onMouseenter: () => {
        if (!preloadedRoutes.value.has(routeName)) {
          preloadRoute(routeName)
        }
      }
    }
  }

  // Précharger selon la connexion réseau
  const preloadBasedOnConnection = () => {
    if ('connection' in navigator) {
      const connection = (navigator as any).connection
      
      // Ne précharger que sur une bonne connexion
      if (connection && (connection.effectiveType === '4g' || connection.effectiveType === '3g')) {
        return true
      }
      
      // Éviter le préchargement sur une connexion lente ou avec économie de données
      if (connection && (connection.saveData || connection.effectiveType === '2g')) {
        return false
      }
    }
    
    return true // Par défaut, autoriser le préchargement
  }

  // Démarrer le préchargement automatique
  const startPreloading = () => {
    if (!condition() || !preloadBasedOnConnection()) {
      return
    }

    // Précharger après un délai pour éviter d'impacter le chargement initial
    setTimeout(() => {
      preloadRoutes(routes)
    }, delay)
  }

  onMounted(() => {
    startPreloading()
  })

  return {
    isPreloading,
    preloadedRoutes,
    preloadRoute,
    preloadRoutes,
    preloadOnHover,
    startPreloading
  }
}

/**
 * Configuration de préchargement par défaut selon la page courante
 */
export const getDefaultPreloadConfig = (currentRoute: string): PreloadConfig => {
  const configs: Record<string, PreloadConfig> = {
    'Dashboard': {
      routes: ['TranscriptionList', 'CreateTranscription', 'Chat', 'Analytics'],
      delay: 1500,
      priority: 'high'
    },
    'Login': {
      routes: ['Dashboard', 'Register'],
      delay: 2000,
      priority: 'high'
    },
    'TranscriptionList': {
      routes: ['CreateTranscription', 'TranscriptionDetail', 'Chat'],
      delay: 1000,
      priority: 'high'
    },
    'CreateTranscription': {
      routes: ['TranscriptionDetail', 'TranscriptionList'],
      delay: 2000,
      priority: 'low'
    },
    'Chat': {
      routes: ['ChatDetail', 'TranscriptionList'],
      delay: 1500,
      priority: 'low'
    }
  }

  return configs[currentRoute] || {
    routes: ['Dashboard'],
    delay: 3000,
    priority: 'low'
  }
}

/**
 * Hook pour le préchargement automatique basé sur la route courante
 */
export function useSmartPreloading() {
  const route = useRoute()
  const config = getDefaultPreloadConfig(route.name as string)
  
  return useRoutePreloading({
    ...config,
    condition: () => {
      // Ne précharger que si l'utilisateur semble engagé
      return document.hasFocus() && !document.hidden
    }
  })
}

/**
 * Préchargement conditionnel pour les composants critiques
 */
export function useCriticalComponentPreloading() {
  const preloadedComponents = ref<Set<string>>(new Set())

  const preloadComponent = async (importFn: () => Promise<any>, componentName: string) => {
    if (preloadedComponents.value.has(componentName)) {
      return
    }

    try {
      preloadedComponents.value.add(componentName)
      await importFn()
      console.log(`Component preloaded: ${componentName}`)
    } catch (error) {
      console.warn(`Failed to preload component ${componentName}:`, error)
      preloadedComponents.value.delete(componentName)
    }
  }

  return {
    preloadedComponents,
    preloadComponent
  }
}