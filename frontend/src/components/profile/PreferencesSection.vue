<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <div class="flex justify-between items-center mb-6">
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Préférences
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
          Personnalisez votre expérience d'utilisation
        </p>
      </div>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-8">
      <!-- Préférences d'interface -->
      <div>
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
          Interface
        </h4>
        
        <div class="space-y-4">
          <!-- Thème -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
              Thème d'affichage
            </label>
            <div class="grid grid-cols-3 gap-3">
              <div
                v-for="theme in themeOptions"
                :key="theme.value"
                @click="form.theme = theme.value as 'light' | 'dark' | 'system'"
                :class="[
                  'relative cursor-pointer rounded-lg border-2 p-3 transition-colors',
                  form.theme === theme.value
                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                ]"
              >
                <div class="flex items-center justify-center mb-2">
                  <component :is="theme.icon" class="h-6 w-6 text-gray-600 dark:text-gray-400" />
                </div>
                <div class="text-center">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ theme.label }}
                  </p>
                  <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                    {{ theme.description }}
                  </p>
                </div>
                <div
                  v-if="form.theme === theme.value"
                  class="absolute top-2 right-2"
                >
                  <svg class="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                  </svg>
                </div>
              </div>
            </div>
          </div>

          <!-- Langue -->
          <div>
            <label for="language" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Langue de l'interface
            </label>
            <select
              id="language"
              v-model="form.language"
              :disabled="loading"
              class="input-base w-full md:w-64"
            >
              <option value="fr">Français</option>
              <option value="en">English</option>
              <option value="es">Español</option>
              <option value="de">Deutsch</option>
              <option value="it">Italiano</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Préférences de transcription -->
      <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
          Transcription
        </h4>
        
        <div class="space-y-4">
          <!-- Langue par défaut -->
          <div>
            <label for="defaultLanguage" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Langue de transcription par défaut
            </label>
            <select
              id="defaultLanguage"
              v-model="form.defaultTranscriptionLanguage"
              :disabled="loading"
              class="input-base w-full md:w-64"
            >
              <option value="auto">Détection automatique</option>
              <option value="fr">Français</option>
              <option value="en">Anglais</option>
              <option value="es">Espagnol</option>
              <option value="de">Allemand</option>
              <option value="it">Italien</option>
              <option value="pt">Portugais</option>
              <option value="ru">Russe</option>
              <option value="zh">Chinois</option>
              <option value="ja">Japonais</option>
            </select>
          </div>

          <!-- Format d'export par défaut -->
          <div>
            <label for="exportFormat" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Format d'export par défaut
            </label>
            <select
              id="exportFormat"
              v-model="form.defaultExportFormat"
              :disabled="loading"
              class="input-base w-full md:w-64"
            >
              <option value="txt">Texte (.txt)</option>
              <option value="docx">Word (.docx)</option>
              <option value="pdf">PDF (.pdf)</option>
              <option value="srt">Sous-titres (.srt)</option>
              <option value="vtt">WebVTT (.vtt)</option>
            </select>
          </div>

          <!-- Options de traitement -->
          <div class="space-y-3">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Options de traitement
            </label>
            
            <div class="space-y-2">
              <label class="flex items-center">
                <input
                  v-model="form.autoDeleteAudio"
                  type="checkbox"
                  :disabled="loading"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
                >
                <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                  Supprimer automatiquement les fichiers audio après transcription
                </span>
              </label>
              
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
        </div>
      </div>

      <!-- Notifications -->
      <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
          Notifications
        </h4>
        
        <div class="space-y-4">
          <div class="space-y-3">
            <label class="flex items-center justify-between">
              <div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Notifications par email
                </span>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Recevoir les notifications importantes par email
                </p>
              </div>
              <input
                v-model="form.emailNotifications"
                type="checkbox"
                :disabled="loading"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
              >
            </label>
            
            <!-- Sous-options email -->
            <div v-if="form.emailNotifications" class="ml-6 space-y-2">
              <label class="flex items-center">
                <input
                  v-model="form.transcriptionCompleteEmail"
                  type="checkbox"
                  :disabled="loading"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
                >
                <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">
                  Transcription terminée
                </span>
              </label>
              
              <label class="flex items-center">
                <input
                  v-model="form.transcriptionFailedEmail"
                  type="checkbox"
                  :disabled="loading"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
                >
                <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">
                  Échec de transcription
                </span>
              </label>
              
              <label class="flex items-center">
                <input
                  v-model="form.weeklyReportEmail"
                  type="checkbox"
                  :disabled="loading"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
                >
                <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">
                  Rapport hebdomadaire d'utilisation
                </span>
              </label>
            </div>
            
            <label class="flex items-center justify-between">
              <div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Notifications push (navigateur)
                </span>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Recevoir des notifications dans le navigateur
                </p>
              </div>
              <input
                v-model="form.pushNotifications"
                type="checkbox"
                :disabled="loading"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
              >
            </label>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
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
          :disabled="!hasChanges"
        >
          Sauvegarder les préférences
        </Button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import type { User } from '@/types'
import Button from '@/components/ui/Button.vue'

interface Props {
  preferences: User['preferences'] | undefined
  loading: boolean
}

interface Emits {
  (e: 'update', preferences: Partial<User['preferences']>): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Options de thème
const themeOptions = [
  {
    value: 'light',
    label: 'Clair',
    description: 'Thème lumineux',
    icon: 'SunIcon'
  },
  {
    value: 'dark',
    label: 'Sombre',
    description: 'Thème sombre',
    icon: 'MoonIcon'
  },
  {
    value: 'system',
    label: 'Système',
    description: 'Suit le système',
    icon: 'ComputerIcon'
  }
]

// Formulaire réactif
const form = reactive({
  theme: 'system' as 'light' | 'dark' | 'system',
  language: 'fr',
  defaultTranscriptionLanguage: 'auto',
  defaultExportFormat: 'txt',
  autoDeleteAudio: true,
  enableTimestamps: true,
  enableSpeakerDetection: false,
  emailNotifications: true,
  transcriptionCompleteEmail: true,
  transcriptionFailedEmail: true,
  weeklyReportEmail: false,
  pushNotifications: false
})

// État initial pour détecter les changements
const initialForm = ref<typeof form | null>(null)

// Détecter les changements
const hasChanges = computed(() => {
  if (!initialForm.value) return false
  
  return Object.keys(form).some(key => {
    return form[key as keyof typeof form] !== initialForm.value![key as keyof typeof form]
  })
})

/**
 * Initialiser le formulaire
 */
function initializeForm() {
  if (props.preferences) {
    Object.assign(form, {
      theme: props.preferences.theme || 'system',
      language: props.preferences.language || 'fr',
      defaultTranscriptionLanguage: props.preferences.defaultTranscriptionLanguage || 'auto',
      defaultExportFormat: props.preferences.defaultExportFormat || 'txt',
      autoDeleteAudio: props.preferences.autoDeleteAudio ?? true,
      enableTimestamps: props.preferences.enableTimestamps ?? true,
      enableSpeakerDetection: props.preferences.enableSpeakerDetection ?? false,
      emailNotifications: props.preferences.notifications?.email ?? true,
      transcriptionCompleteEmail: props.preferences.notifications?.transcriptionComplete ?? true,
      transcriptionFailedEmail: props.preferences.notifications?.transcriptionFailed ?? true,
      weeklyReportEmail: props.preferences.notifications?.weeklyReport ?? false,
      pushNotifications: props.preferences.notifications?.push ?? false
    })
  }
  
  // Sauvegarder l'état initial
  initialForm.value = { ...form }
}

/**
 * Réinitialiser le formulaire
 */
function resetForm() {
  if (initialForm.value) {
    Object.assign(form, initialForm.value)
  }
}

/**
 * Soumettre le formulaire
 */
function handleSubmit() {
  const preferences = {
    theme: form.theme,
    language: form.language,
    defaultTranscriptionLanguage: form.defaultTranscriptionLanguage,
    defaultExportFormat: form.defaultExportFormat,
    autoDeleteAudio: form.autoDeleteAudio,
    enableTimestamps: form.enableTimestamps,
    enableSpeakerDetection: form.enableSpeakerDetection,
    notifications: {
      email: form.emailNotifications,
      transcriptionComplete: form.transcriptionCompleteEmail,
      transcriptionFailed: form.transcriptionFailedEmail,
      weeklyReport: form.weeklyReportEmail,
      push: form.pushNotifications
    }
  }
  
  emit('update', preferences)
  
  // Mettre à jour l'état initial
  initialForm.value = { ...form }
}

// Watchers
watch(() => props.preferences, initializeForm, { immediate: true, deep: true })
</script>

<script lang="ts">
// Composants d'icônes
const SunIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
    </svg>
  `
}

const MoonIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
    </svg>
  `
}

const ComputerIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
    </svg>
  `
}

export default {
  name: 'PreferencesSection',
  components: {
    SunIcon,
    MoonIcon,
    ComputerIcon
  }
}
</script>