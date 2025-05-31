<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
      Paramètres d'interface
    </h3>
    
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Thème -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
          Thème d'affichage
        </label>
        <div class="grid grid-cols-3 gap-3">
          <div
            v-for="theme in themeOptions"
            :key="theme.value"
            @click="form.theme = theme.value"
            :class="[
              'cursor-pointer rounded-lg border-2 p-3 transition-colors',
              form.theme === theme.value
                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
            ]"
          >
            <div class="text-center">
              <div class="flex justify-center mb-2">
                <component :is="theme.icon" class="h-6 w-6 text-gray-600 dark:text-gray-400" />
              </div>
              <p class="text-sm font-medium text-gray-900 dark:text-white">
                {{ theme.label }}
              </p>
              <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                {{ theme.description }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Langue -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Langue de l'interface
        </label>
        <select
          v-model="form.language"
          :disabled="loading"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
        >
          <option value="fr">Français</option>
          <option value="en">English</option>
          <option value="es">Español</option>
          <option value="de">Deutsch</option>
        </select>
      </div>

      <!-- Options d'affichage -->
      <div class="space-y-4">
        <h4 class="text-md font-medium text-gray-900 dark:text-white">
          Options d'affichage
        </h4>
        
        <div class="space-y-3">
          <label class="flex items-center justify-between">
            <div>
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Mode compact
              </span>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Interface plus dense avec moins d'espacement
              </p>
            </div>
            <input
              v-model="form.compactMode"
              type="checkbox"
              :disabled="loading"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
            >
          </label>
          
          <label class="flex items-center justify-between">
            <div>
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Afficher les tutoriels
              </span>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Bulles d'aide et guides pour les nouvelles fonctionnalités
              </p>
            </div>
            <input
              v-model="form.showTutorials"
              type="checkbox"
              :disabled="loading"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
            >
          </label>
          
          <label class="flex items-center justify-between">
            <div>
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Sauvegarde automatique
              </span>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Sauvegarder automatiquement les brouillons et modifications
              </p>
            </div>
            <input
              v-model="form.autoSave"
              type="checkbox"
              :disabled="loading"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
            >
          </label>
          
          <label class="flex items-center justify-between">
            <div>
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Confirmer les suppressions
              </span>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Demander confirmation avant de supprimer des éléments
              </p>
            </div>
            <input
              v-model="form.confirmDeletions"
              type="checkbox"
              :disabled="loading"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
            >
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

interface InterfaceSettings {
  theme: 'light' | 'dark' | 'system'
  language: string
  compactMode: boolean
  showTutorials: boolean
  autoSave: boolean
  confirmDeletions: boolean
}

interface Props {
  settings: InterfaceSettings
  loading: boolean
}

interface Emits {
  (e: 'update', settings: Partial<InterfaceSettings>): void
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
const form = reactive<InterfaceSettings>({
  theme: 'system',
  language: 'fr',
  compactMode: false,
  showTutorials: true,
  autoSave: true,
  confirmDeletions: true
})

// État initial pour détecter les changements
let initialForm: InterfaceSettings | null = null

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

<script lang="ts">
// Icônes pour les thèmes
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
  components: {
    SunIcon,
    MoonIcon,
    ComputerIcon
  }
}
</script>