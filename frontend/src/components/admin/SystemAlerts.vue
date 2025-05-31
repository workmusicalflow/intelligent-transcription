<template>
  <div class="space-y-3">
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 3" :key="i" class="animate-pulse">
        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
          <div class="flex items-start space-x-3">
            <div class="w-5 h-5 bg-gray-200 dark:bg-gray-600 rounded"></div>
            <div class="flex-1 space-y-2">
              <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded w-3/4"></div>
              <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-full"></div>
              <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/2"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div v-else-if="!alerts?.length" class="text-center py-8">
      <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      <p class="text-gray-500 dark:text-gray-400">Aucune alerte système</p>
      <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Tout fonctionne normalement</p>
    </div>
    
    <div v-else class="space-y-3 max-h-96 overflow-y-auto">
      <div
        v-for="alert in sortedAlerts"
        :key="alert.id"
        :class="[
          'p-4 rounded-lg border-l-4 transition-all duration-200',
          alert.resolved 
            ? 'bg-gray-50 dark:bg-gray-700/30 border-gray-400 opacity-75'
            : alertClasses[alert.type]?.bg || 'bg-gray-50 dark:bg-gray-700/50',
          alert.resolved
            ? 'border-gray-400'
            : alertClasses[alert.type]?.border || 'border-gray-400'
        ]"
      >
        <div class="flex items-start justify-between">
          <div class="flex items-start space-x-3 flex-1">
            <!-- Icône d'alerte -->
            <div class="flex-shrink-0 mt-0.5">
              <component 
                :is="getAlertIcon(alert.type)" 
                :class="[
                  'w-5 h-5',
                  alert.resolved 
                    ? 'text-gray-400'
                    : alertClasses[alert.type]?.icon || 'text-gray-600'
                ]"
              />
            </div>
            
            <!-- Contenu de l'alerte -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center space-x-2 mb-1">
                <h4 
                  :class="[
                    'text-sm font-medium',
                    alert.resolved 
                      ? 'text-gray-500 dark:text-gray-400 line-through'
                      : 'text-gray-900 dark:text-white'
                  ]"
                >
                  {{ alert.title }}
                </h4>
                <span
                  v-if="alert.resolved"
                  class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300"
                >
                  Résolu
                </span>
                <span
                  v-else
                  :class="[
                    'inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium',
                    alertClasses[alert.type]?.badge || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
                  ]"
                >
                  {{ typeLabels[alert.type] }}
                </span>
              </div>
              
              <p 
                :class="[
                  'text-sm mb-2',
                  alert.resolved 
                    ? 'text-gray-400 dark:text-gray-500'
                    : 'text-gray-700 dark:text-gray-300'
                ]"
              >
                {{ alert.message }}
              </p>
              
              <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  {{ formatRelativeTime(alert.timestamp) }}
                </span>
                
                <!-- Actions -->
                <div v-if="!alert.resolved" class="flex items-center space-x-2">
                  <button
                    @click="$emit('resolve', alert.id)"
                    class="text-xs text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300 font-medium transition-colors"
                    title="Marquer comme résolu"
                  >
                    Résoudre
                  </button>
                  <span class="text-gray-300 dark:text-gray-600">•</span>
                  <button
                    @click="$emit('dismiss', alert.id)"
                    class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 font-medium transition-colors"
                    title="Ignorer cette alerte"
                  >
                    Ignorer
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Menu d'actions -->
          <div class="relative ml-2">
            <button
              @click="toggleAlertMenu(alert.id)"
              class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
              title="Plus d'actions"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
              </svg>
            </button>
            
            <!-- Menu déroulant -->
            <div
              v-if="openMenuId === alert.id"
              class="absolute right-0 top-full mt-1 w-40 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-10"
              @click.stop
            >
              <button
                @click="$emit('view-details', alert)"
                class="block w-full text-left px-3 py-2 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
              >
                Voir détails
              </button>
              <button
                v-if="!alert.resolved"
                @click="$emit('resolve', alert.id)"
                class="block w-full text-left px-3 py-2 text-xs text-green-700 dark:text-green-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
              >
                Marquer résolu
              </button>
              <button
                @click="$emit('copy-details', alert)"
                class="block w-full text-left px-3 py-2 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
              >
                Copier détails
              </button>
              <button
                @click="$emit('dismiss', alert.id)"
                class="block w-full text-left px-3 py-2 text-xs text-red-700 dark:text-red-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
              >
                Supprimer
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue'

interface Alert {
  id: string
  type: 'error' | 'warning' | 'info'
  title: string
  message: string
  timestamp: string
  resolved?: boolean
}

interface Props {
  alerts: Alert[]
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const emit = defineEmits<{
  dismiss: [alertId: string]
  resolve: [alertId: string]
  'view-details': [alert: Alert]
  'copy-details': [alert: Alert]
}>()

const openMenuId = ref<string | null>(null)

const alertClasses = {
  error: {
    bg: 'bg-red-50 dark:bg-red-900/20',
    border: 'border-red-400',
    icon: 'text-red-500 dark:text-red-400',
    badge: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
  },
  warning: {
    bg: 'bg-yellow-50 dark:bg-yellow-900/20',
    border: 'border-yellow-400',
    icon: 'text-yellow-500 dark:text-yellow-400',
    badge: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'
  },
  info: {
    bg: 'bg-blue-50 dark:bg-blue-900/20',
    border: 'border-blue-400',
    icon: 'text-blue-500 dark:text-blue-400',
    badge: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
  }
}

const typeLabels = {
  error: 'Erreur',
  warning: 'Avertissement',
  info: 'Information'
}

// Icônes pour différents types d'alertes
const ExclamationTriangleIcon = () => ({
  template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>'
})

const XCircleIcon = () => ({
  template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
})

const InformationCircleIcon = () => ({
  template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
})

const sortedAlerts = computed(() => {
  return [...props.alerts].sort((a, b) => {
    // Les alertes non résolues en premier
    if (a.resolved !== b.resolved) {
      return a.resolved ? 1 : -1
    }
    
    // Puis par ordre de priorité: error > warning > info
    const priority = { error: 3, warning: 2, info: 1 }
    const aPriority = priority[a.type] || 0
    const bPriority = priority[b.type] || 0
    
    if (aPriority !== bPriority) {
      return bPriority - aPriority
    }
    
    // Enfin par date (plus récent en premier)
    return new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime()
  })
})

function getAlertIcon(type: string) {
  const iconMap = {
    error: XCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon
  }
  
  return iconMap[type as keyof typeof iconMap] || InformationCircleIcon
}

function formatRelativeTime(dateStr: string): string {
  const date = new Date(dateStr)
  const now = new Date()
  const diffInMs = now.getTime() - date.getTime()
  const diffInMinutes = Math.floor(diffInMs / (1000 * 60))
  const diffInHours = Math.floor(diffInMinutes / 60)
  const diffInDays = Math.floor(diffInHours / 24)
  
  if (diffInMinutes < 1) {
    return 'À l\'instant'
  } else if (diffInMinutes < 60) {
    return `il y a ${diffInMinutes}min`
  } else if (diffInHours < 24) {
    return `il y a ${diffInHours}h`
  } else if (diffInDays === 1) {
    return 'Hier'
  } else if (diffInDays < 7) {
    return `il y a ${diffInDays} jours`
  } else {
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: '2-digit'
    })
  }
}

function toggleAlertMenu(alertId: string) {
  openMenuId.value = openMenuId.value === alertId ? null : alertId
}

function closeMenus() {
  openMenuId.value = null
}

// Fermer les menus quand on clique ailleurs
onMounted(() => {
  document.addEventListener('click', closeMenus)
})

onUnmounted(() => {
  document.removeEventListener('click', closeMenus)
})
</script>

<script lang="ts">
export default {
  name: 'SystemAlerts'
}
</script>