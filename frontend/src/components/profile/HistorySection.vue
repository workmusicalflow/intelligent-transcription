<template>
  <div class="space-y-6">
    <!-- Statistiques globales -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
        Statistiques d'utilisation
      </h3>
      
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="text-center">
          <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
            {{ stats?.transcriptions.total || 0 }}
          </div>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Transcriptions totales
          </p>
        </div>
        
        <div class="text-center">
          <div class="text-3xl font-bold text-green-600 dark:text-green-400">
            {{ formatDuration(stats?.usage.audioHours || 0) }}
          </div>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Heures d'audio
          </p>
        </div>
        
        <div class="text-center">
          <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
            {{ formatCost(stats?.usage.totalCost || 0) }}
          </div>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Coût total
          </p>
        </div>
        
        <div class="text-center">
          <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">
            {{ stats?.activity.activeDays || 0 }}
          </div>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Jours actifs
          </p>
        </div>
      </div>
    </div>

    <!-- Activité récente -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Activité récente
        </h3>
        <Button
          variant="secondary"
          size="sm"
          @click="$emit('load-more')"
          :loading="loading"
        >
          Charger plus
        </Button>
      </div>
      
      <div v-if="loading && !recentActivity?.length" class="flex justify-center py-8">
        <LoadingSpinner size="md" />
      </div>
      
      <div v-else-if="recentActivity?.length" class="space-y-4">
        <div
          v-for="activity in recentActivity"
          :key="activity.id"
          class="flex items-start space-x-3 p-4 rounded-lg border border-gray-200 dark:border-gray-600"
        >
          <div class="flex-shrink-0">
            <component :is="getActivityIcon(activity.type)" :class="getActivityIconColor(activity.type)" class="h-5 w-5" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
              {{ activity.title }}
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
              {{ activity.description }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
              {{ formatDate(activity.timestamp) }}
            </p>
          </div>
          <div v-if="activity.metadata" class="flex-shrink-0 text-xs text-gray-500 dark:text-gray-500">
            <span v-if="activity.metadata.duration">
              {{ formatDuration(activity.metadata.duration) }}
            </span>
            <span v-if="activity.metadata.cost" class="ml-2">
              {{ formatCost(activity.metadata.cost) }}
            </span>
          </div>
        </div>
      </div>
      
      <div v-else class="text-center py-8">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
        </svg>
        <p class="text-gray-600 dark:text-gray-400 mt-2">
          Aucune activité récente
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { UserStats } from '@/types'
import Button from '@/components/ui/Button.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'

interface Props {
  stats: UserStats | null
  recentActivity: any[]
  loading: boolean
}

interface Emits {
  (e: 'load-more'): void
}

defineProps<Props>()
defineEmits<Emits>()

/**
 * Formater la durée en heures et minutes
 */
function formatDuration(hours: number): string {
  const h = Math.floor(hours)
  const m = Math.round((hours - h) * 60)
  
  if (h === 0) {
    return `${m}min`
  } else if (m === 0) {
    return `${h}h`
  } else {
    return `${h}h ${m}min`
  }
}

/**
 * Formater le coût
 */
function formatCost(cost: number): string {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 2
  }).format(cost)
}

/**
 * Formater une date
 */
function formatDate(dateString: string): string {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
  
  if (diffHours < 1) {
    return 'Il y a moins d\'une heure'
  } else if (diffHours < 24) {
    return `Il y a ${diffHours} heure${diffHours > 1 ? 's' : ''}`
  } else {
    return date.toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit'
    })
  }
}

/**
 * Obtenir l'icône d'activité
 */
function getActivityIcon(type: string) {
  switch (type) {
    case 'transcription_completed':
      return 'CheckIcon'
    case 'transcription_failed':
      return 'XIcon'
    case 'transcription_started':
      return 'PlayIcon'
    case 'chat_created':
      return 'ChatIcon'
    case 'export':
      return 'DownloadIcon'
    default:
      return 'InfoIcon'
  }
}

/**
 * Obtenir la couleur de l'icône d'activité
 */
function getActivityIconColor(type: string): string {
  switch (type) {
    case 'transcription_completed':
      return 'text-green-500'
    case 'transcription_failed':
      return 'text-red-500'
    case 'transcription_started':
      return 'text-blue-500'
    case 'chat_created':
      return 'text-purple-500'
    case 'export':
      return 'text-orange-500'
    default:
      return 'text-gray-500'
  }
}
</script>

<script lang="ts">
// Composants d'icônes
const CheckIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
    </svg>
  `
}

const XIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
    </svg>
  `
}

const PlayIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.586a1 1 0 01.707.293l4.828 4.828a1 1 0 01.707.293H15M9 10v4a3 3 0 11-6 0v-4"></path>
    </svg>
  `
}

const ChatIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
    </svg>
  `
}

const DownloadIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
    </svg>
  `
}

const InfoIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
  `
}

export default {
  name: 'HistorySection',
  components: {
    CheckIcon,
    XIcon,
    PlayIcon,
    ChatIcon,
    DownloadIcon,
    InfoIcon
  }
}
</script>