<template>
  <div class="translation-processing-indicator">
    <!-- Version inline pour liste -->
    <div v-if="variant === 'inline'" class="flex items-center gap-2 p-3 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
      <!-- Spinner -->
      <div class="relative">
        <div class="animate-spin w-5 h-5 border-2 border-blue-200 border-t-blue-500 rounded-full"></div>
        <div class="absolute inset-0 flex items-center justify-center text-xs">
          🌐
        </div>
      </div>
      
      <!-- Contenu -->
      <div class="flex-1">
        <div class="flex items-center gap-2">
          <span class="font-medium text-blue-700 dark:text-blue-300">⚡ Traitement immédiat</span>
          <span v-if="showProgress" class="text-xs px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full">
            {{ estimatedProgress }}%
          </span>
        </div>
        <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">
          {{ getProcessingMessage() }}
        </div>
      </div>
      
      <!-- Barre de progression -->
      <div v-if="showProgress" class="w-20 h-1 bg-blue-100 dark:bg-blue-900/30 rounded-full overflow-hidden">
        <div 
          class="h-full bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full transition-all duration-500 ease-out relative"
          :style="{ width: `${estimatedProgress}%` }"
        >
          <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer"></div>
        </div>
      </div>
    </div>
    
    <!-- Version détaillée pour page de détail -->
    <div v-else-if="variant === 'detailed'" class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-700 rounded-xl p-6">
      <!-- Header avec titre et temps -->
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="relative">
            <div class="animate-spin w-8 h-8 border-3 border-blue-200 border-t-blue-500 rounded-full"></div>
            <div class="absolute inset-0 flex items-center justify-center text-lg">
              🌐
            </div>
          </div>
          <div>
            <h3 class="font-semibold text-blue-800 dark:text-blue-200">Traitement immédiat en cours</h3>
            <p class="text-sm text-blue-600 dark:text-blue-400">Notre IA traduit avec précision émotionnelle</p>
          </div>
        </div>
        <div class="text-right">
          <div class="text-sm font-medium text-blue-700 dark:text-blue-300">{{ formatElapsedTime() }}</div>
          <div class="text-xs text-blue-600 dark:text-blue-400">temps écoulé</div>
        </div>
      </div>
      
      <!-- Barre de progression principale -->
      <div class="mb-4">
        <div class="flex justify-between text-sm mb-2">
          <span class="text-blue-700 dark:text-blue-300">Progression</span>
          <span class="font-medium text-blue-800 dark:text-blue-200">{{ estimatedProgress }}%</span>
        </div>
        <div class="w-full h-3 bg-blue-100 dark:bg-blue-900/30 rounded-full overflow-hidden">
          <div 
            class="h-full bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full transition-all duration-500 ease-out relative"
            :style="{ width: `${estimatedProgress}%` }"
          >
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer"></div>
          </div>
        </div>
      </div>
      
      <!-- Messages rotatifs -->
      <div class="text-center">
        <div class="text-sm text-blue-700 dark:text-blue-300 min-h-[1.25rem]">
          <span class="typing-cursor">{{ getProcessingMessage() }}</span>
        </div>
      </div>
      
      <!-- Estimation temps restant -->
      <div v-if="estimatedTimeLeft > 0" class="mt-3 text-center">
        <div class="text-xs text-blue-600 dark:text-blue-400">
          Temps restant estimé: ~{{ estimatedTimeLeft }}s
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

interface Props {
  variant?: 'inline' | 'detailed'
  segmentsCount?: number
  showProgress?: boolean
  startTime?: Date
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'inline',
  segmentsCount: 10,
  showProgress: true,
  startTime: () => new Date()
})

// État local
const elapsedTime = ref(0)
const messageIndex = ref(0)
let interval: NodeJS.Timeout | null = null

// Messages rotatifs inspirés de la transcription
const processingMessages = [
  "Analyse du contexte émotionnel...",
  "Préservation des nuances linguistiques...",
  "Adaptation du timing pour le doublage...",
  "Optimisation de la qualité de traduction...",
  "Génération des timestamps précis...",
  "Finalisation de la traduction..."
]

// Progression estimée basée sur le temps écoulé
const estimatedProgress = computed(() => {
  // Base: 2 secondes par segment pour traitement immédiat
  const estimatedTotalTime = props.segmentsCount * 2
  const progressPercent = Math.min(95, (elapsedTime.value / estimatedTotalTime) * 100)
  return Math.round(progressPercent)
})

// Temps restant estimé
const estimatedTimeLeft = computed(() => {
  const estimatedTotalTime = props.segmentsCount * 2
  const remaining = Math.max(0, estimatedTotalTime - elapsedTime.value)
  return Math.round(remaining)
})

// Formatage du temps écoulé
const formatElapsedTime = () => {
  const minutes = Math.floor(elapsedTime.value / 60)
  const seconds = Math.floor(elapsedTime.value % 60)
  if (minutes > 0) {
    return `${minutes}m ${seconds}s`
  }
  return `${seconds}s`
}

// Message de traitement actuel
const getProcessingMessage = () => {
  return processingMessages[messageIndex.value]
}

// Démarrer le timer et rotation des messages
onMounted(() => {
  interval = setInterval(() => {
    elapsedTime.value++
    
    // Changer de message toutes les 3 secondes
    if (elapsedTime.value % 3 === 0) {
      messageIndex.value = (messageIndex.value + 1) % processingMessages.length
    }
  }, 1000)
})

// Nettoyer le timer
onUnmounted(() => {
  if (interval) {
    clearInterval(interval)
  }
})
</script>

<style scoped>
@keyframes typing-cursor {
  0%, 50% { 
    opacity: 1; 
  }
  51%, 100% { 
    opacity: 0; 
  }
}

.typing-cursor {
  animation: typing-cursor 1s infinite;
  color: #3b82f6;
  font-weight: bold;
}

.translation-processing-indicator {
  @apply font-sans;
}
</style>