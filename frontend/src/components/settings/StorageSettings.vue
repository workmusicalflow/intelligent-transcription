<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
      Paramètres de stockage
    </h3>
    
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Utilisation du stockage -->
      <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">
          Utilisation actuelle du stockage
        </h4>
        
        <div class="space-y-3">
          <!-- Barre de progression globale -->
          <div>
            <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
              <span>Utilisé: {{ formatSize(usage.totalSize - usage.available) }}</span>
              <span>Disponible: {{ formatSize(usage.available) }}</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
              <div 
                class="bg-blue-500 h-2 rounded-full transition-all"
                :style="{ width: `${getUsagePercentage()}%` }"
              ></div>
            </div>
          </div>
          
          <!-- Détails par type -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
            <div class="text-center">
              <div class="flex items-center justify-center mb-1">
                <MicrophoneIcon class="h-4 w-4 text-blue-500 mr-1" />
                <span class="text-gray-600 dark:text-gray-400">Audio</span>
              </div>
              <span class="font-medium text-gray-900 dark:text-white">
                {{ formatSize(usage.audioFiles) }}
              </span>
            </div>
            <div class="text-center">
              <div class="flex items-center justify-center mb-1">
                <DocumentTextIcon class="h-4 w-4 text-green-500 mr-1" />
                <span class="text-gray-600 dark:text-gray-400">Textes</span>
              </div>
              <span class="font-medium text-gray-900 dark:text-white">
                {{ formatSize(usage.transcriptions) }}
              </span>
            </div>
            <div class="text-center">
              <div class="flex items-center justify-center mb-1">
                <DatabaseIcon class="h-4 w-4 text-yellow-500 mr-1" />
                <span class="text-gray-600 dark:text-gray-400">Cache</span>
              </div>
              <span class="font-medium text-gray-900 dark:text-white">
                {{ formatSize(usage.cache) }}
              </span>
            </div>
            <div class="text-center">
              <div class="flex items-center justify-center mb-1">
                <ChartBarIcon class="h-4 w-4 text-purple-500 mr-1" />
                <span class="text-gray-600 dark:text-gray-400">Total</span>
              </div>
              <span class="font-medium text-gray-900 dark:text-white">
                {{ formatSize(usage.totalSize) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Gestion automatique -->
      <div class="space-y-4">
        <h4 class="text-md font-medium text-gray-900 dark:text-white">
          Gestion automatique
        </h4>
        
        <label class="flex items-center justify-between">
          <div>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
              Suppression automatique des fichiers audio
            </span>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Supprimer les fichiers audio après transcription réussie
            </p>
          </div>
          <input
            v-model="form.autoDeleteAudio"
            type="checkbox"
            :disabled="loading"
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
          >
        </label>
      </div>

      <!-- Rétention des fichiers -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Durée de rétention des fichiers audio (en jours)
        </label>
        <div class="flex items-center space-x-4">
          <input
            v-model.number="form.audioRetentionDays"
            type="range"
            min="1"
            max="30"
            step="1"
            :disabled="loading || form.autoDeleteAudio"
            class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
          >
          <span class="text-sm font-medium text-gray-900 dark:text-white min-w-0">
            {{ form.audioRetentionDays }} jour{{ form.audioRetentionDays > 1 ? 's' : '' }}
          </span>
        </div>
        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
          Les fichiers audio seront automatiquement supprimés après cette période
        </p>
      </div>

      <!-- Taille max du cache -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Taille maximale du cache (en MB)
        </label>
        <div class="flex items-center space-x-4">
          <input
            v-model.number="form.maxCacheSize"
            type="range"
            min="100"
            max="5000"
            step="100"
            :disabled="loading"
            class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
          >
          <span class="text-sm font-medium text-gray-900 dark:text-white min-w-0">
            {{ form.maxCacheSize }} MB
          </span>
        </div>
        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
          Le cache sera automatiquement vidé si cette limite est dépassée
        </p>
      </div>

      <!-- Compression -->
      <div>
        <label class="flex items-center justify-between">
          <div>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
              Compression des fichiers
            </span>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Compresser les fichiers pour économiser l'espace de stockage
            </p>
          </div>
          <input
            v-model="form.enableCompression"
            type="checkbox"
            :disabled="loading"
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
          >
        </label>
      </div>

      <!-- Actions de nettoyage -->
      <div class="space-y-4">
        <h4 class="text-md font-medium text-gray-900 dark:text-white">
          Actions de nettoyage
        </h4>
        
        <div class="space-y-3">
          <div class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-600 rounded-lg">
            <div>
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Vider le cache
              </span>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Supprimer tous les fichiers temporaires et le cache
              </p>
            </div>
            <Button
              type="button"
              variant="secondary"
              @click="clearCache"
              :loading="clearingCache"
            >
              <TrashIcon class="h-4 w-4 mr-2" />
              Vider
            </Button>
          </div>
          
          <div class="flex items-center justify-between p-3 border border-red-200 dark:border-red-800 rounded-lg bg-red-50 dark:bg-red-900/20">
            <div>
              <span class="text-sm font-medium text-red-700 dark:text-red-300">
                Supprimer toutes les transcriptions
              </span>
              <p class="text-sm text-red-600 dark:text-red-400">
                Action irréversible - toutes vos transcriptions seront perdues
              </p>
            </div>
            <Button
              type="button"
              variant="danger"
              @click="clearTranscriptions"
              :loading="clearingTranscriptions"
            >
              <ExclamationIcon class="h-4 w-4 mr-2" />
              Supprimer
            </Button>
          </div>
        </div>
      </div>

      <!-- Optimisations -->
      <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-start">
          <LightBulbIcon class="h-5 w-5 text-blue-500 mt-0.5 mr-3" />
          <div>
            <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">
              Optimisations suggérées
            </h4>
            <ul class="text-xs text-blue-700 dark:text-blue-300 mt-1 space-y-1">
              <li v-if="usage.cache > 500">• Votre cache est volumineux, pensez à le vider</li>
              <li v-if="!form.enableCompression">• Activez la compression pour économiser l'espace</li>
              <li v-if="form.audioRetentionDays > 7">• Réduisez la rétention audio pour libérer de l'espace</li>
              <li v-if="getUsagePercentage() > 80">• Stockage presque plein, libérez de l'espace</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-3 pt-4">
        <Button
          type="button"
          variant="secondary"
          @click="resetForm"
          :disabled="loading"
        >
          Réinitialiser
        </Button>
        <Button
          type="submit"
          variant="primary"
          :loading="loading"
        >
          Sauvegarder
        </Button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { reactive, watch, onMounted, ref } from 'vue'
import Button from '@/components/ui/Button.vue'

interface StorageSettings {
  autoDeleteAudio: boolean
  audioRetentionDays: number
  maxCacheSize: number
  enableCompression: boolean
}

interface StorageUsage {
  totalSize: number
  audioFiles: number
  transcriptions: number
  cache: number
  available: number
}

interface Props {
  settings: StorageSettings
  usage: StorageUsage
  loading: boolean
}

interface Emits {
  (e: 'update', settings: Partial<StorageSettings>): void
  (e: 'clear-cache'): void
  (e: 'clear-transcriptions'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const clearingCache = ref(false)
const clearingTranscriptions = ref(false)

// Formulaire réactif
const form = reactive<StorageSettings>({
  autoDeleteAudio: true,
  audioRetentionDays: 1,
  maxCacheSize: 1024,
  enableCompression: true
})

// État initial pour détecter les changements
let initialForm: StorageSettings | null = null

/**
 * Initialiser le formulaire
 */
function initializeForm() {
  Object.assign(form, props.settings)
  initialForm = { ...form }
}

/**
 * Réinitialiser le formulaire
 */
function resetForm() {
  if (initialForm) {
    Object.assign(form, initialForm)
  }
}

/**
 * Soumettre le formulaire
 */
function handleSubmit() {
  emit('update', { ...form })
  initialForm = { ...form }
}

/**
 * Vider le cache
 */
async function clearCache() {
  clearingCache.value = true
  try {
    emit('clear-cache')
    await new Promise(resolve => setTimeout(resolve, 1000))
  } finally {
    clearingCache.value = false
  }
}

/**
 * Supprimer toutes les transcriptions
 */
async function clearTranscriptions() {
  if (!confirm('Êtes-vous sûr de vouloir supprimer toutes les transcriptions ? Cette action est irréversible.')) {
    return
  }
  
  clearingTranscriptions.value = true
  try {
    emit('clear-transcriptions')
    await new Promise(resolve => setTimeout(resolve, 2000))
  } finally {
    clearingTranscriptions.value = false
  }
}

/**
 * Formater la taille en unités lisibles
 */
function formatSize(sizeInMB: number): string {
  if (sizeInMB >= 1024) {
    return `${(sizeInMB / 1024).toFixed(1)} GB`
  }
  return `${sizeInMB} MB`
}

/**
 * Calculer le pourcentage d'utilisation
 */
function getUsagePercentage(): number {
  const used = props.usage.totalSize - props.usage.available
  return Math.round((used / props.usage.totalSize) * 100)
}

// Initialiser au montage
onMounted(() => {
  initializeForm()
})

// Réagir aux changements des props
watch(
  () => props.settings,
  () => {
    initializeForm()
  },
  { deep: true }
)
</script>

<script lang="ts">
// Icônes pour le stockage
const MicrophoneIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
    </svg>
  `
}

const DocumentTextIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
    </svg>
  `
}

const DatabaseIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
    </svg>
  `
}

const ChartBarIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
    </svg>
  `
}

const TrashIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
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

const LightBulbIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
    </svg>
  `
}

export default {
  components: {
    MicrophoneIcon,
    DocumentTextIcon,
    DatabaseIcon,
    ChartBarIcon,
    TrashIcon,
    ExclamationIcon,
    LightBulbIcon
  }
}
</script>