<template>
  <div class="transcription-loader">
    <!-- Container principal -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-8 border border-blue-200 dark:border-blue-800">
      <!-- Spinner central avec icône -->
      <div class="flex items-center justify-center mb-6">
        <div class="relative">
          <div class="w-16 h-16 border-4 border-blue-200 dark:border-blue-700 rounded-full animate-spin border-t-blue-500 dark:border-t-blue-400"></div>
          <div class="absolute inset-0 flex items-center justify-center">
            <span class="text-2xl animate-pulse">{{ icon }}</span>
          </div>
        </div>
      </div>
      
      <!-- Texte principal -->
      <div class="text-center space-y-4">
        <h3 class="text-xl font-semibold text-blue-800 dark:text-blue-200">
          {{ title }}
        </h3>
        <p class="text-blue-600 dark:text-blue-300">
          {{ subtitle }}
        </p>
        
        <!-- Barre de progression animée -->
        <div class="w-full bg-blue-200 dark:bg-blue-800 rounded-full h-3 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-500 to-indigo-500 h-full rounded-full animate-pulse relative">
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer"></div>
          </div>
        </div>
        
        <!-- Animation de frappe avec chatbot -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-blue-200 dark:border-blue-700">
          <div class="flex items-start space-x-3">
            <div class="flex-shrink-0 w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
              <span class="text-white text-sm">🤖</span>
            </div>
            <div class="flex-1">
              <div class="typing-animation text-gray-700 dark:text-gray-300">
                <span class="typing-text">{{ typingText }}</span>
                <span class="typing-cursor">|</span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Étapes de progression (optionnel) -->
        <div v-if="showSteps" class="grid grid-cols-3 gap-4 mt-6">
          <div v-for="(step, index) in steps" :key="index" class="text-center">
            <div :class="[
              'w-8 h-8 rounded-full mx-auto mb-2 flex items-center justify-center text-sm',
              step.completed 
                ? 'bg-green-500 text-white' 
                : step.active
                  ? 'bg-blue-500 text-white animate-pulse'
                  : 'bg-gray-200 dark:bg-gray-700 text-gray-500'
            ]">
              {{ step.completed ? '✓' : index + 1 }}
            </div>
            <p :class="[
              'text-xs',
              step.completed 
                ? 'text-green-600 dark:text-green-400 font-medium' 
                : step.active
                  ? 'text-blue-600 dark:text-blue-400 font-medium'
                  : 'text-gray-500'
            ]">
              {{ step.label }}
            </p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Skeleton préview du contenu à venir -->
    <div v-if="showPreview" class="space-y-4 mt-6 opacity-50">
      <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
      <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-4/5"></div>
      <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-3/4"></div>
      <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-5/6"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

interface Step {
  label: string
  completed: boolean
  active: boolean
}

interface Props {
  title?: string
  subtitle?: string
  icon?: string
  showSteps?: boolean
  showPreview?: boolean
  steps?: Step[]
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Transcription en cours...',
  subtitle: 'Notre IA analyse votre audio et génère la transcription',
  icon: '🎧',
  showSteps: false,
  showPreview: true,
  steps: () => [
    { label: 'Analyse', completed: true, active: false },
    { label: 'Traitement', completed: false, active: true },
    { label: 'Finalisation', completed: false, active: false }
  ]
})

// Animation du texte de frappe
const typingTexts = [
  'Analyse de l\'audio en cours',
  'Détection de la langue',
  'Conversion de la parole en texte',
  'Optimisation de la transcription',
  'Finalisation du document'
]

const typingText = ref(typingTexts[0])
let typingInterval: NodeJS.Timeout

onMounted(() => {
  let currentIndex = 0
  typingInterval = setInterval(() => {
    currentIndex = (currentIndex + 1) % typingTexts.length
    typingText.value = typingTexts[currentIndex]
  }, 3000)
})

onUnmounted(() => {
  if (typingInterval) {
    clearInterval(typingInterval)
  }
})
</script>

<style scoped>
/* Animation de l'effet shimmer */
@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.animate-shimmer {
  animation: shimmer 2s infinite;
}

/* Animation de typing */
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

.typing-animation {
  font-family: 'Courier New', monospace;
}

.typing-text {
  position: relative;
}

/* Animation pour les skeleton loaders */
@keyframes skeleton-loading {
  0% {
    background-position: -200px 0;
  }
  100% {
    background-position: calc(200px + 100%) 0;
  }
}

.animate-pulse {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200px 100%;
  animation: skeleton-loading 1.5s infinite;
}

.dark .animate-pulse {
  background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
  background-size: 200px 100%;
  animation: skeleton-loading 1.5s infinite;
}

/* Animation d'entrée pour le composant */
.transcription-loader {
  animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(2rem);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>