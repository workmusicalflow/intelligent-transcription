<template>
  <div :class="containerClasses">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center space-x-3">
        <div :class="statusIconClasses">
          <component :is="statusIcon" class="h-5 w-5" />
        </div>
        <div>
          <h3 class="text-sm font-medium text-gray-900 dark:text-white">
            {{ transcription?.audioFile.originalName }}
          </h3>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ statusText }}
          </p>
        </div>
      </div>
      
      <!-- Progress percentage -->
      <div v-if="isProcessing" class="text-right">
        <div class="text-lg font-semibold text-gray-900 dark:text-white">
          {{ Math.round(progressPercentage) }}%
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400">
          {{ currentStage }}
        </div>
      </div>
    </div>

    <!-- Progress bar -->
    <div v-if="isProcessing" class="space-y-2">
      <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
        <div
          :class="progressBarClasses"
          :style="{ width: `${progressPercentage}%` }"
        >
          <div class="h-full w-full bg-gradient-to-r from-transparent to-white opacity-25 animate-pulse"></div>
        </div>
      </div>
      
      <!-- Stage indicators -->
      <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
        <span
          v-for="stage in stages"
          :key="stage.name"
          :class="[
            'transition-colors duration-200',
            isStageActive(stage.name) ? 'text-primary-600 dark:text-primary-400 font-medium' : '',
            isStageCompleted(stage.name) ? 'text-green-600 dark:text-green-400' : ''
          ]"
        >
          {{ stage.label }}
        </span>
      </div>
    </div>

    <!-- Real-time updates -->
    <div v-if="lastUpdate && showUpdates" class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
      <div class="flex items-start space-x-2">
        <div class="flex-shrink-0 mt-0.5">
          <div class="h-2 w-2 bg-green-400 rounded-full animate-pulse"></div>
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-xs font-medium text-gray-900 dark:text-white">
            {{ formatUpdateTime(lastUpdate.timestamp) }}
          </p>
          <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
            {{ lastUpdate.message }}
          </p>
        </div>
      </div>
    </div>

    <!-- Error state -->
    <div v-if="transcription?.status === 'failed'" class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md">
      <div class="flex items-center space-x-2">
        <ExclamationTriangleIcon class="h-4 w-4 text-red-500" />
        <p class="text-sm text-red-700 dark:text-red-300">
          La transcription a échoué. Vous pouvez réessayer ou contacter le support.
        </p>
      </div>
      <div class="mt-2 flex space-x-2">
        <Button size="sm" variant="secondary" @click="retryTranscription">
          Réessayer
        </Button>
        <Button size="sm" variant="ghost" @click="contactSupport">
          Contacter le support
        </Button>
      </div>
    </div>

    <!-- Completed state -->
    <div v-if="transcription?.status === 'completed'" class="mt-4">
      <div class="flex items-center space-x-2 mb-3">
        <CheckCircleIcon class="h-5 w-5 text-green-500" />
        <span class="text-sm font-medium text-green-700 dark:text-green-300">
          Transcription terminée avec succès
        </span>
      </div>
      
      <!-- Quick stats -->
      <div class="grid grid-cols-2 gap-4 text-xs text-gray-600 dark:text-gray-400">
        <div>
          <span class="font-medium">Temps de traitement:</span>
          {{ formatDuration(processingDuration) }}
        </div>
        <div>
          <span class="font-medium">Mots transcrits:</span>
          {{ wordCount }}
        </div>
        <div v-if="transcription.cost">
          <span class="font-medium">Coût:</span>
          {{ transcription.cost.formatted }}
        </div>
        <div>
          <span class="font-medium">Langue:</span>
          {{ transcription.language.name }}
        </div>
      </div>
    </div>

    <!-- Connection status -->
    <div v-if="!connectionStatus.connected && isProcessing" class="mt-4 p-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md">
      <div class="flex items-center space-x-2">
        <div class="h-2 w-2 bg-yellow-500 rounded-full"></div>
        <p class="text-xs text-yellow-700 dark:text-yellow-300">
          {{ connectionStatus.reconnecting ? 'Reconnexion en cours...' : 'Connexion interrompue' }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  ClockIcon,
  Cog6ToothIcon,
  CheckCircleIcon,
  XCircleIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'
import { formatDistanceToNow } from 'date-fns'
import { fr } from 'date-fns/locale'

import Button from '@components/ui/Button.vue'
import { useTranscriptionSubscriptions } from '@composables/useTranscriptionSubscriptions'
import { useGlobalWebSocket } from '@composables/useWebSocket'
import type { Transcription } from '@/types'

interface Props {
  transcriptionId: string
  showUpdates?: boolean
  compact?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showUpdates: true,
  compact: false
})

const emit = defineEmits<{
  retry: [transcriptionId: string]
  support: [transcriptionId: string]
}>()

// Composables
const {
  transcription,
  currentProgress,
  lastUpdate,
  isProcessing,
  progressPercentage,
  currentStage
} = useTranscriptionSubscriptions(props.transcriptionId)

const webSocket = useGlobalWebSocket()

// Processing stages
const stages = [
  { name: 'upload', label: 'Upload' },
  { name: 'preprocessing', label: 'Préparation' },
  { name: 'transcription', label: 'Transcription' },
  { name: 'postprocessing', label: 'Finalisation' }
]

// Computed
const containerClasses = computed(() => [
  'bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700',
  props.compact ? 'p-4' : 'p-6'
])

const statusIcon = computed(() => {
  switch (transcription.value?.status) {
    case 'pending': return ClockIcon
    case 'processing': return Cog6ToothIcon
    case 'completed': return CheckCircleIcon
    case 'failed': return XCircleIcon
    default: return ClockIcon
  }
})

const statusIconClasses = computed(() => [
  'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center',
  {
    'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/20 dark:text-yellow-400': transcription.value?.status === 'pending',
    'bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400': transcription.value?.status === 'processing',
    'bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400': transcription.value?.status === 'completed',
    'bg-red-100 text-red-600 dark:bg-red-900/20 dark:text-red-400': transcription.value?.status === 'failed'
  }
])

const statusText = computed(() => {
  switch (transcription.value?.status) {
    case 'pending': return 'En attente de traitement'
    case 'processing': return `En cours - ${currentStage.value}`
    case 'completed': return 'Transcription terminée'
    case 'failed': return 'Échec de la transcription'
    default: return 'Statut inconnu'
  }
})

const progressBarClasses = computed(() => [
  'h-full transition-all duration-500 ease-out relative overflow-hidden',
  {
    'bg-blue-500': transcription.value?.status === 'processing',
    'bg-green-500': transcription.value?.status === 'completed'
  }
])

const connectionStatus = computed(() => webSocket.state)

const processingDuration = computed(() => {
  if (!transcription.value?.createdAt || !transcription.value?.updatedAt) return 0
  const start = new Date(transcription.value.createdAt).getTime()
  const end = new Date(transcription.value.updatedAt).getTime()
  return Math.round((end - start) / 1000)
})

const wordCount = computed(() => {
  if (!transcription.value?.text) return 0
  return transcription.value.text.split(/\s+/).filter((word: string) => word.length > 0).length
})

// Methods
const isStageActive = (stageName: string) => {
  return currentStage.value.toLowerCase().includes(stageName)
}

const isStageCompleted = (stageName: string) => {
  const stageIndex = stages.findIndex(s => s.name === stageName)
  const currentIndex = stages.findIndex(s => isStageActive(s.name))
  return stageIndex < currentIndex
}

const formatUpdateTime = (timestamp: string) => {
  return formatDistanceToNow(new Date(timestamp), { 
    addSuffix: true, 
    locale: fr 
  })
}

const formatDuration = (seconds: number) => {
  if (seconds < 60) return `${seconds}s`
  if (seconds < 3600) return `${Math.round(seconds / 60)}min`
  return `${Math.round(seconds / 3600)}h`
}

const retryTranscription = () => {
  emit('retry', props.transcriptionId)
}

const contactSupport = () => {
  emit('support', props.transcriptionId)
}
</script>

<script lang="ts">
export default {
  name: 'TranscriptionProgress'
}
</script>