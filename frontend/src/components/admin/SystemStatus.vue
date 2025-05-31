<template>
  <div class="space-y-4">
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 4" :key="i" class="animate-pulse">
        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
          <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-gray-200 dark:bg-gray-600 rounded"></div>
            <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded w-20"></div>
          </div>
          <div class="w-16 h-5 bg-gray-200 dark:bg-gray-600 rounded"></div>
        </div>
      </div>
    </div>
    
    <div v-else class="space-y-3">
      <!-- État global -->
      <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div 
              :class="[
                'w-3 h-3 rounded-full',
                overallStatus === 'operational' ? 'bg-green-500' :
                overallStatus === 'degraded' ? 'bg-yellow-500' : 'bg-red-500'
              ]"
            ></div>
            <span class="font-medium text-gray-900 dark:text-white">
              État général du système
            </span>
          </div>
          <span 
            :class="[
              'text-sm font-medium',
              overallStatus === 'operational' ? 'text-green-600 dark:text-green-400' :
              overallStatus === 'degraded' ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400'
            ]"
          >
            {{ statusLabels[overallStatus] }}
          </span>
        </div>
        
        <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
          <div class="flex items-center justify-between">
            <span>Disponibilité: {{ status.uptime }}%</span>
            <span>Dernière vérification: {{ formatLastCheck() }}</span>
          </div>
        </div>
      </div>
      
      <!-- Services individuels -->
      <div class="space-y-2">
        <div
          v-for="service in services"
          :key="service.key"
          class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        >
          <div class="flex items-center space-x-3">
            <div class="flex items-center justify-center w-8 h-8 rounded bg-white dark:bg-gray-800 shadow-sm">
              <component :is="service.icon" class="w-4 h-4 text-gray-600 dark:text-gray-400" />
            </div>
            <div>
              <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                {{ service.name }}
              </h4>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ service.description }}
              </p>
            </div>
          </div>
          
          <div class="flex items-center space-x-2">
            <div 
              :class="[
                'w-2 h-2 rounded-full',
                status[service.key] === 'operational' ? 'bg-green-500' :
                status[service.key] === 'degraded' ? 'bg-yellow-500' : 'bg-red-500'
              ]"
            ></div>
            <span 
              :class="[
                'text-xs font-medium',
                status[service.key] === 'operational' ? 'text-green-600 dark:text-green-400' :
                status[service.key] === 'degraded' ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400'
              ]"
            >
              {{ statusLabels[status[service.key] as keyof typeof statusLabels] }}
            </span>
          </div>
        </div>
      </div>
      
      <!-- Actions -->
      <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <button
            @click="$emit('refresh')"
            class="flex items-center space-x-2 text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
            :disabled="refreshing"
          >
            <svg
              :class="[
                'w-4 h-4',
                refreshing ? 'animate-spin' : ''
              ]"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <span>{{ refreshing ? 'Actualisation...' : 'Actualiser' }}</span>
          </button>
          
          <router-link
            to="/admin/system"
            class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-colors"
          >
            Voir détails →
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

interface SystemStatusData {
  api: 'operational' | 'degraded' | 'outage'
  database: 'operational' | 'degraded' | 'outage'
  storage: 'operational' | 'degraded' | 'outage'
  transcription: 'operational' | 'degraded' | 'outage'
  uptime: number
  lastCheck: string
}

interface Props {
  status: SystemStatusData
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const emit = defineEmits<{
  refresh: []
}>()

const refreshing = ref(false)

const statusLabels = {
  operational: 'Opérationnel',
  degraded: 'Dégradé',
  outage: 'Panne'
}

const services = [
  {
    key: 'api' as keyof SystemStatusData,
    name: 'API',
    description: 'Interface de programmation',
    icon: 'CloudIcon'
  },
  {
    key: 'database' as keyof SystemStatusData,
    name: 'Base de données',
    description: 'Stockage des données',
    icon: 'DatabaseIcon'
  },
  {
    key: 'storage' as keyof SystemStatusData,
    name: 'Stockage',
    description: 'Fichiers et médias',
    icon: 'FolderIcon'
  },
  {
    key: 'transcription' as keyof SystemStatusData,
    name: 'Transcription',
    description: 'Service de transcription',
    icon: 'SpeakerWaveIcon'
  }
]

// Icônes factices (à remplacer par de vraies icônes)
const CloudIcon = () => ({
  template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>'
})

const DatabaseIcon = () => ({
  template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>'
})

const FolderIcon = () => ({
  template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>'
})

const SpeakerWaveIcon = () => ({
  template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M6 10H4a1 1 0 00-1 1v2a1 1 0 001 1h2l3.464 2.464A1 1 0 0010 16V8a1 1 0 00-1.536-.864L6 10z"></path></svg>'
})

const overallStatus = computed(() => {
  const statuses = [props.status.api, props.status.database, props.status.storage, props.status.transcription]
  
  if (statuses.includes('outage')) {
    return 'outage'
  }
  if (statuses.includes('degraded')) {
    return 'degraded'
  }
  return 'operational'
})

function formatLastCheck(): string {
  const date = new Date(props.status.lastCheck)
  const now = new Date()
  const diffInMs = now.getTime() - date.getTime()
  const diffInMinutes = Math.floor(diffInMs / (1000 * 60))
  
  if (diffInMinutes < 1) {
    return 'À l\'instant'
  } else if (diffInMinutes < 60) {
    return `il y a ${diffInMinutes}min`
  } else {
    return date.toLocaleTimeString('fr-FR', {
      hour: '2-digit',
      minute: '2-digit'
    })
  }
}

// Simuler un rafraîchissement
function refresh() {
  refreshing.value = true
  setTimeout(() => {
    refreshing.value = false
    emit('refresh')
  }, 1000)
}
</script>

<script lang="ts">
export default {
  name: 'SystemStatus'
}
</script>