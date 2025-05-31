<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
      Paramètres de transcription
    </h3>
    
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Langue par défaut -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Langue de transcription par défaut
        </label>
        <select
          v-model="form.defaultLanguage"
          :disabled="loading"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
        >
          <option value="auto">Détection automatique</option>
          <option value="fr">Français</option>
          <option value="en">Anglais</option>
          <option value="es">Espagnol</option>
          <option value="de">Allemand</option>
          <option value="it">Italien</option>
        </select>
      </div>

      <!-- Format de sortie -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Format de sortie par défaut
        </label>
        <select
          v-model="form.defaultOutputFormat"
          :disabled="loading"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
        >
          <option value="txt">Texte brut (.txt)</option>
          <option value="srt">Sous-titres (.srt)</option>
          <option value="vtt">WebVTT (.vtt)</option>
          <option value="json">JSON (.json)</option>
          <option value="docx">Word (.docx)</option>
        </select>
      </div>

      <!-- Préréglage de qualité -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Préréglage de qualité
        </label>
        <div class="grid grid-cols-3 gap-3">
          <div
            v-for="preset in qualityPresets"
            :key="preset.value"
            @click="form.qualityPreset = preset.value"
            :class="[
              'cursor-pointer rounded-lg border-2 p-3 transition-colors',
              form.qualityPreset === preset.value
                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
            ]"
          >
            <div class="text-center">
              <p class="text-sm font-medium text-gray-900 dark:text-white">
                {{ preset.label }}
              </p>
              <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                {{ preset.description }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Taille des segments -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Taille des segments (en MB)
        </label>
        <div class="flex items-center space-x-4">
          <input
            v-model.number="form.chunkSize"
            type="range"
            min="10"
            max="100"
            step="5"
            :disabled="loading"
            class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
          >
          <span class="text-sm font-medium text-gray-900 dark:text-white min-w-0">
            {{ form.chunkSize }} MB
          </span>
        </div>
        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
          Les fichiers volumineux seront découpés en segments de cette taille
        </p>
      </div>

      <!-- Options avancées -->
      <div class="space-y-4">
        <h4 class="text-md font-medium text-gray-900 dark:text-white">
          Options avancées
        </h4>
        
        <div class="space-y-3">
          <label class="flex items-center">
            <input
              v-model="form.enableTimestamps"
              type="checkbox"
              :disabled="loading"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
            >
            <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">
              Activer les timestamps par défaut
            </span>
          </label>
          
          <label class="flex items-center">
            <input
              v-model="form.enableSpeakerDetection"
              type="checkbox"
              :disabled="loading"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
            >
            <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">
              Détection des interlocuteurs (bientôt disponible)
            </span>
          </label>
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
import { reactive, watch, onMounted } from 'vue'
import Button from '@/components/ui/Button.vue'

interface TranscriptionSettings {
  defaultLanguage: string
  enableTimestamps: boolean
  enableSpeakerDetection: boolean
  defaultOutputFormat: string
  qualityPreset: 'fast' | 'balanced' | 'high'
  chunkSize: number
}

interface Props {
  settings: TranscriptionSettings
  loading: boolean
}

interface Emits {
  (e: 'update', settings: Partial<TranscriptionSettings>): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Préréglages de qualité
const qualityPresets = [
  {
    value: 'fast',
    label: 'Rapide',
    description: 'Traitement rapide'
  },
  {
    value: 'balanced',
    label: 'Équilibré',
    description: 'Bon compromis'
  },
  {
    value: 'high',
    label: 'Haute qualité',
    description: 'Meilleure précision'
  }
]

// Formulaire réactif
const form = reactive<TranscriptionSettings>({
  defaultLanguage: 'auto',
  enableTimestamps: true,
  enableSpeakerDetection: false,
  defaultOutputFormat: 'txt',
  qualityPreset: 'balanced',
  chunkSize: 25
})

// État initial pour détecter les changements
let initialForm: TranscriptionSettings | null = null

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