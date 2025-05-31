<template>
  <div :class="cardClasses" @click="handleClick">
    <!-- Header -->
    <div class="flex items-start justify-between p-4 border-b border-gray-200 dark:border-gray-700">
      <div class="flex items-center space-x-3 min-w-0 flex-1">
        <!-- File icon -->
        <div :class="iconWrapperClasses">
          <component
            :is="fileIcon"
            class="h-5 w-5"
          />
        </div>
        
        <!-- Title and metadata -->
        <div class="min-w-0 flex-1">
          <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">
            {{ transcription.audioFile.originalName }}
          </h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            {{ formatDate(transcription.createdAt) }} • {{ transcription.language.name }}
          </p>
        </div>
      </div>
      
      <!-- Actions menu -->
      <TranscriptionActions
        :transcription="transcription"
        @action="handleAction"
      />
    </div>

    <!-- Content -->
    <div class="p-4 space-y-3">
      <!-- Status -->
      <div class="flex items-center justify-between">
        <TranscriptionStatus :status="transcription.status" />
        
        <!-- Progress for processing transcriptions -->
        <div v-if="transcription.status === 'processing' && transcription.processingProgress" class="flex items-center space-x-2">
          <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
            <div
              class="bg-primary-600 h-1.5 rounded-full transition-all duration-300"
              :style="{ width: `${transcription.processingProgress}%` }"
            />
          </div>
          <span class="text-xs text-gray-500">{{ transcription.processingProgress }}%</span>
        </div>
      </div>

      <!-- Preview text -->
      <div v-if="transcription.text && transcription.status === 'completed'" class="space-y-2">
        <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-3">
          {{ transcription.text }}
        </p>
        <button
          v-if="transcription.text.length > 150"
          @click.stop="toggleExpanded"
          class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300"
        >
          {{ expanded ? 'Voir moins' : 'Voir plus' }}
        </button>
      </div>

      <!-- Processing info -->
      <div v-else-if="transcription.status === 'processing'" class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
        <LoadingSpinner size="sm" />
        <span>Transcription en cours...</span>
      </div>

      <!-- Error info -->
      <div v-else-if="transcription.status === 'failed'" class="text-sm text-red-600 dark:text-red-400">
        <ExclamationTriangleIcon class="h-4 w-4 inline mr-1" />
        Échec de la transcription
      </div>

      <!-- YouTube metadata -->
      <div v-if="transcription.youtube" class="flex items-center space-x-2 p-2 bg-gray-50 dark:bg-gray-700 rounded-md">
        <img
          :src="transcription.youtube.thumbnail"
          :alt="transcription.youtube.title"
          class="h-8 w-8 rounded object-cover"
        />
        <div class="min-w-0 flex-1">
          <p class="text-xs font-medium text-gray-900 dark:text-white truncate">
            {{ transcription.youtube.title }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ formatDuration(transcription.youtube.duration) }}
          </p>
        </div>
      </div>

      <!-- File info -->
      <div class="grid grid-cols-2 gap-4 text-xs text-gray-600 dark:text-gray-400">
        <div>
          <span class="font-medium">Taille:</span>
          {{ formatFileSize(transcription.audioFile.size) }}
        </div>
        <div v-if="transcription.audioFile.duration">
          <span class="font-medium">Durée:</span>
          {{ formatDuration(transcription.audioFile.duration) }}
        </div>
        <div v-if="transcription.cost">
          <span class="font-medium">Coût:</span>
          {{ transcription.cost.formatted }}
        </div>
        <div v-if="transcription.status === 'completed'">
          <span class="font-medium">Mots:</span>
          {{ wordCount }}
        </div>
      </div>
    </div>

    <!-- Footer actions -->
    <div v-if="transcription.status === 'completed'" class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
      <div class="flex items-center justify-between">
        <div class="flex space-x-2">
          <Button
            size="sm"
            variant="ghost"
            @click.stop="startChat"
          >
            <ChatBubbleLeftRightIcon class="h-4 w-4 mr-1" />
            Chat
          </Button>
          <Button
            size="sm"
            variant="ghost"
            @click.stop="downloadTranscription"
          >
            <ArrowDownTrayIcon class="h-4 w-4 mr-1" />
            Télécharger
          </Button>
        </div>
        
        <Button
          size="sm"
          @click.stop="viewDetails"
        >
          Voir détails
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  DocumentTextIcon,
  VideoCameraIcon,
  ExclamationTriangleIcon,
  ChatBubbleLeftRightIcon,
  ArrowDownTrayIcon
} from '@heroicons/vue/24/outline'
import { format } from 'date-fns'
import { fr } from 'date-fns/locale'

import Button from '@components/ui/Button.vue'
import LoadingSpinner from '@components/ui/LoadingSpinner.vue'
import TranscriptionStatus from './TranscriptionStatus.vue'
import TranscriptionActions from './TranscriptionActions.vue'
import type { Transcription } from '@/types'

interface Props {
  transcription: Transcription
  clickable?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  clickable: true
})

const emit = defineEmits<{
  click: [transcription: Transcription]
  action: [action: string, transcription: Transcription]
}>()

// Composables
const router = useRouter()

// State
const expanded = ref(false)

// Computed
const cardClasses = computed(() => [
  'bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm transition-all duration-200',
  {
    'hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 cursor-pointer': props.clickable,
    'ring-2 ring-primary-500 ring-opacity-50': props.transcription.status === 'processing'
  }
])

const iconWrapperClasses = computed(() => [
  'flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center',
  {
    'bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400': props.transcription.youtube,
    'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400': !props.transcription.youtube
  }
])

const fileIcon = computed(() => {
  return props.transcription.youtube ? VideoCameraIcon : DocumentTextIcon
})

const wordCount = computed(() => {
  if (!props.transcription.text) return 0
  return props.transcription.text.split(/\s+/).filter((word: string) => word.length > 0).length
})

// Methods
const formatDate = (dateString: string) => {
  return format(new Date(dateString), 'dd MMM yyyy à HH:mm', { locale: fr })
}

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(1))} ${sizes[i]}`
}

const formatDuration = (seconds: number) => {
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = Math.floor(seconds % 60)
  
  if (hours > 0) {
    return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
  }
  return `${minutes}:${secs.toString().padStart(2, '0')}`
}

const handleClick = () => {
  if (props.clickable) {
    emit('click', props.transcription)
  }
}

const handleAction = (action: string) => {
  emit('action', action, props.transcription)
}

const toggleExpanded = () => {
  expanded.value = !expanded.value
}

const viewDetails = () => {
  router.push(`/transcriptions/${props.transcription.id}`)
}

const startChat = () => {
  router.push(`/chat?transcription=${props.transcription.id}`)
}

const downloadTranscription = () => {
  // Implementation for downloading transcription
  handleAction('download')
}
</script>

<script lang="ts">
export default {
  name: 'TranscriptionCard'
}
</script>

<style scoped>
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>