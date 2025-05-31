<template>
  <div class="space-y-3">
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 5" :key="i" class="animate-pulse">
        <div class="flex items-start space-x-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
          <div class="w-8 h-8 bg-gray-200 dark:bg-gray-600 rounded-full"></div>
          <div class="flex-1 space-y-1">
            <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded w-3/4"></div>
            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/2"></div>
          </div>
        </div>
      </div>
    </div>
    
    <div v-else-if="!activities?.length" class="text-center py-8">
      <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
      </svg>
      <p class="text-gray-500 dark:text-gray-400">Aucune activité récente</p>
    </div>
    
    <div v-else class="space-y-3 max-h-96 overflow-y-auto">
      <div
        v-for="activity in activities"
        :key="activity.id"
        class="flex items-start space-x-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
      >
        <!-- Icône d'activité -->
        <div class="flex-shrink-0">
          <div 
            :class="[
              'w-8 h-8 rounded-full flex items-center justify-center',
              severityClasses[activity.severity]?.bg || 'bg-gray-100 dark:bg-gray-600'
            ]"
          >
            <component 
              :is="getActivityIcon(activity.type)" 
              :class="[
                'w-4 h-4',
                severityClasses[activity.severity]?.text || 'text-gray-600 dark:text-gray-400'
              ]"
            />
          </div>
        </div>
        
        <!-- Contenu de l'activité -->
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <p class="text-sm text-gray-900 dark:text-white">
                {{ activity.message }}
              </p>
              <div class="flex items-center space-x-2 mt-1">
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  {{ formatRelativeTime(activity.timestamp) }}
                </span>
                <span
                  :class="[
                    'inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium',
                    typeClasses[activity.type as keyof typeof typeClasses] || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
                  ]"
                >
                  {{ typeLabels[activity.type as keyof typeof typeLabels] || activity.type }}
                </span>
              </div>
            </div>
            
            <!-- Menu d'actions -->
            <div class="relative ml-2">
              <button
                @click="toggleActivityMenu(activity.id)"
                class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                title="Actions"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                </svg>
              </button>
              
              <!-- Menu déroulant -->
              <div
                v-if="openMenuId === activity.id"
                class="absolute right-0 top-full mt-1 w-32 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-10"
                @click.stop
              >
                <button
                  @click="$emit('view-details', activity)"
                  class="block w-full text-left px-3 py-2 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                  Voir détails
                </button>
                <button
                  @click="$emit('mark-read', activity)"
                  class="block w-full text-left px-3 py-2 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                  Marquer lu
                </button>
                <button
                  @click="$emit('archive', activity)"
                  class="block w-full text-left px-3 py-2 text-xs text-red-700 dark:text-red-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                  Archiver
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Bouton "Voir plus" -->
    <div v-if="activities?.length > 0" class="pt-3 border-t border-gray-200 dark:border-gray-700">
      <button
        @click="$emit('load-more')"
        class="w-full text-center text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors py-2"
      >
        Charger plus d'activités
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

interface Activity {
  id: string
  type: string
  message: string
  timestamp: string
  severity: 'info' | 'success' | 'warning' | 'error'
  [key: string]: any
}

interface Props {
  activities: Activity[]
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const emit = defineEmits<{
  'view-details': [activity: Activity]
  'mark-read': [activity: Activity]
  'archive': [activity: Activity]
  'load-more': []
}>()

const openMenuId = ref<string | null>(null)

const severityClasses = {
  info: {
    bg: 'bg-blue-100 dark:bg-blue-900/30',
    text: 'text-blue-600 dark:text-blue-400'
  },
  success: {
    bg: 'bg-green-100 dark:bg-green-900/30',
    text: 'text-green-600 dark:text-green-400'
  },
  warning: {
    bg: 'bg-yellow-100 dark:bg-yellow-900/30',
    text: 'text-yellow-600 dark:text-yellow-400'
  },
  error: {
    bg: 'bg-red-100 dark:bg-red-900/30',
    text: 'text-red-600 dark:text-red-400'
  }
}

const typeClasses = {
  user_registration: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
  system_alert: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
  transcription_completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
  transcription_failed: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
  backup_completed: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
  maintenance: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300'
}

const typeLabels = {
  user_registration: 'Utilisateur',
  system_alert: 'Système',
  transcription_completed: 'Transcription',
  transcription_failed: 'Erreur',
  backup_completed: 'Sauvegarde',
  maintenance: 'Maintenance'
}

// Icônes factices pour différents types d'activité
const UserIcon = () => ({
  template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>'
})

const ExclamationTriangleIcon = () => ({
  template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>'
})

const CheckCircleIcon = () => ({
  template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
})

const XCircleIcon = () => ({
  template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
})

const CogIcon = () => ({
  template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>'
})

const ActivityIcon = () => ({
  template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>'
})

function getActivityIcon(type: string) {
  const iconMap = {
    user_registration: UserIcon,
    system_alert: ExclamationTriangleIcon,
    transcription_completed: CheckCircleIcon,
    transcription_failed: XCircleIcon,
    backup_completed: CheckCircleIcon,
    maintenance: CogIcon
  }
  
  return iconMap[type as keyof typeof iconMap] || ActivityIcon
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

function toggleActivityMenu(activityId: string) {
  openMenuId.value = openMenuId.value === activityId ? null : activityId
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
  name: 'RecentActivity'
}
</script>