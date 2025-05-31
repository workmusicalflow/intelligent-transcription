import { defineAsyncComponent, type AsyncComponentLoader, type Component } from 'vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'

interface LazyComponentOptions {
  loadingComponent?: Component
  errorComponent?: Component
  delay?: number
  timeout?: number
  suspensible?: boolean
}

/**
 * Composable pour le lazy loading de composants avec gestion d'erreur
 */
export function useLazyComponent<T extends Component>(
  loader: AsyncComponentLoader<T>,
  options: LazyComponentOptions = {}
) {
  const {
    loadingComponent = LoadingSpinner,
    errorComponent,
    delay = 200,
    timeout = 10000,
    suspensible = false
  } = options

  return defineAsyncComponent({
    loader,
    loadingComponent,
    errorComponent: errorComponent || {
      template: `
        <div class="flex items-center justify-center p-8">
          <div class="text-center">
            <div class="text-red-500 mb-2">
              <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <p class="text-gray-600 dark:text-gray-400">Erreur de chargement du composant</p>
            <button 
              @click="$emit('retry')"
              class="mt-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              Réessayer
            </button>
          </div>
        </div>
      `
    },
    delay,
    timeout,
    suspensible
  })
}

/**
 * Lazy loading pour les composants de graphiques lourds
 */
export const useLazyChart = (chartLoader: AsyncComponentLoader<any>) => {
  return useLazyComponent(chartLoader, {
    delay: 300,
    timeout: 15000
  })
}

/**
 * Lazy loading pour les composants d'administration
 */
export const useLazyAdmin = (componentLoader: AsyncComponentLoader<any>) => {
  return useLazyComponent(componentLoader, {
    delay: 100,
    timeout: 8000
  })
}

/**
 * Lazy loading pour les modales complexes
 */
export const useLazyModal = (modalLoader: AsyncComponentLoader<any>) => {
  return useLazyComponent(modalLoader, {
    delay: 0,
    timeout: 5000,
    suspensible: true
  })
}