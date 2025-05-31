<template>
  <div class="space-y-4">
    <!-- Loading state -->
    <div v-if="loading" class="flex items-center justify-center py-8">
      <LoadingSpinner size="lg" />
    </div>
    
    <!-- Error state -->
    <div v-else-if="error" class="flex items-center justify-center py-8 text-red-500 dark:text-red-400">
      <div class="text-center">
        <ExclamationIcon class="h-12 w-12 mx-auto mb-2" />
        <p>Erreur lors du chargement des activités</p>
      </div>
    </div>
    
    <!-- Activities list -->
    <div v-else-if="activities.length > 0" class="space-y-3">
      <div
        v-for="activity in activities"
        :key="activity.id"
        class="flex items-start space-x-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
      >
        <!-- Activity icon -->
        <div :class="[
          'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center',
          getActivityColor(activity.type)
        ]">
          <component :is="getActivityIcon(activity.type)" class="h-4 w-4 text-white" />
        </div>
        
        <!-- Activity content -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-900 dark:text-white">
            {{ activity.message }}
          </p>
          <div class="flex items-center space-x-2 mt-1">
            <span class="text-xs text-gray-500 dark:text-gray-400">
              {{ formatTimestamp(activity.timestamp) }}
            </span>
            <span :class="[
              'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
              getActivityBadgeColor(activity.type)
            ]">
              {{ getActivityTypeLabel(activity.type) }}
            </span>
          </div>
          
          <!-- Metadata if available -->
          <div v-if="activity.metadata" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            <div v-if="activity.metadata.filename" class="truncate">
              Fichier: {{ activity.metadata.filename }}
            </div>
            <div v-if="activity.metadata.duration" class="truncate">
              Durée: {{ formatDuration(activity.metadata.duration) }}
            </div>
            <div v-if="activity.metadata.language" class="truncate">
              Langue: {{ activity.metadata.language }}
            </div>
            <div v-if="activity.metadata.cost" class="truncate">
              Coût: {{ formatCurrency(activity.metadata.cost) }}
            </div>
          </div>
        </div>
        
        <!-- Activity status/actions -->
        <div class="flex-shrink-0">
          <button
            v-if="activity.type === 'transcription' && activity.metadata?.id"
            @click="viewTranscription(activity.metadata.id)"
            class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-xs font-medium"
          >
            Voir
          </button>
          <button
            v-else-if="activity.type === 'error'"
            @click="viewDetails(activity)"
            class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-xs font-medium"
          >
            Détails
          </button>
        </div>
      </div>
    </div>
    
    <!-- Empty state -->
    <div v-else class="flex items-center justify-center py-8 text-gray-500 dark:text-gray-400">
      <div class="text-center">
        <ClockIcon class="h-12 w-12 mx-auto mb-2" />
        <p>Aucune activité récente</p>
      </div>
    </div>
    
    <!-- Load more button -->
    <div v-if="!loading && activities.length > 0 && hasMore" class="flex justify-center pt-4">
      <button
        @click="loadMore"
        :disabled="loadingMore"
        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <LoadingSpinner v-if="loadingMore" size="sm" class="mr-2" />
        Voir plus
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import { useUIStore } from '@/stores/ui'

interface Activity {
  id: string
  type: 'transcription' | 'error' | 'optimization' | 'report'
  message: string
  timestamp: string
  metadata?: {
    id?: string
    filename?: string
    duration?: number
    language?: string
    cost?: number
    errorCode?: string
    details?: string
  }
}

interface Props {
  activities: Activity[]
  loading?: boolean
  error?: boolean
  hasMore?: boolean
}

interface Emits {
  (e: 'load-more'): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  error: false,
  hasMore: false
})

const emit = defineEmits<Emits>()

const router = useRouter()
const uiStore = useUIStore()
const loadingMore = ref(false)

// Methods
function getActivityIcon(type: Activity['type']) {
  const iconMap = {
    transcription: DocumentTextIcon,
    error: ExclamationIcon,
    optimization: CogIcon,
    report: DocumentReportIcon
  }
  return iconMap[type] || DocumentTextIcon
}

function getActivityColor(type: Activity['type']): string {
  const colorMap = {
    transcription: 'bg-blue-500',
    error: 'bg-red-500',
    optimization: 'bg-green-500',
    report: 'bg-purple-500'
  }
  return colorMap[type] || 'bg-gray-500'
}

function getActivityBadgeColor(type: Activity['type']): string {
  const colorMap = {
    transcription: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
    error: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
    optimization: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
    report: 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400'
  }
  return colorMap[type] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
}

function getActivityTypeLabel(type: Activity['type']): string {
  const labelMap = {
    transcription: 'Transcription',
    error: 'Erreur',
    optimization: 'Optimisation',
    report: 'Rapport'
  }
  return labelMap[type] || 'Activité'
}

function formatTimestamp(timestamp: string): string {
  const date = new Date(timestamp)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / (1000 * 60))
  const diffHours = Math.floor(diffMins / 60)
  const diffDays = Math.floor(diffHours / 24)
  
  if (diffMins < 1) {
    return 'À l\'instant'
  } else if (diffMins < 60) {
    return `Il y a ${diffMins} min`
  } else if (diffHours < 24) {
    return `Il y a ${diffHours}h`
  } else if (diffDays < 7) {
    return `Il y a ${diffDays}j`
  } else {
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short'
    })
  }
}

function formatDuration(seconds: number): string {
  if (seconds < 60) {
    return `${seconds}s`
  } else if (seconds < 3600) {
    return `${Math.floor(seconds / 60)}min`
  } else {
    return `${Math.floor(seconds / 3600)}h ${Math.floor((seconds % 3600) / 60)}min`
  }
}

function formatCurrency(amount: number): string {
  return `${amount.toFixed(2)}€`
}

function viewTranscription(id: string) {
  router.push(`/transcriptions/${id}`)
}

function viewDetails(activity: Activity) {
  uiStore.showNotification({
    type: 'info',
    title: 'Détails de l\'erreur',
    message: activity.metadata?.details || 'Aucun détail disponible'
  })
}

async function loadMore() {
  loadingMore.value = true
  try {
    emit('load-more')
  } finally {
    loadingMore.value = false
  }
}

// Icons
const DocumentTextIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
    </svg>
  `
}

const ExclamationIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
    </svg>
  `
}

const CogIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
    </svg>
  `
}

const DocumentReportIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
    </svg>
  `
}

const ClockIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
  `
}
</script>