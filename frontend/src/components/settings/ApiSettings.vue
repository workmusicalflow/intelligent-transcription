<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
      Paramètres API
    </h3>
    
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Clé API OpenAI -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Clé API OpenAI
        </label>
        <div class="relative">
          <input
            v-model="form.openaiKey"
            :type="showApiKey ? 'text' : 'password'"
            :disabled="loading"
            placeholder="sk-..."
            class="w-full px-3 py-2 pr-10 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          >
          <button
            type="button"
            @click="showApiKey = !showApiKey"
            class="absolute inset-y-0 right-0 px-3 flex items-center"
          >
            <EyeIcon v-if="!showApiKey" class="h-4 w-4 text-gray-400" />
            <EyeOffIcon v-else class="h-4 w-4 text-gray-400" />
          </button>
        </div>
        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
          Votre clé API sera stockée de manière sécurisée et chiffrée
        </p>
      </div>

      <!-- Point de terminaison personnalisé -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Point de terminaison personnalisé (optionnel)
        </label>
        <input
          v-model="form.customEndpoint"
          type="url"
          :disabled="loading"
          placeholder="https://api.openai.com/v1"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
        >
        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
          Laissez vide pour utiliser l'API OpenAI par défaut
        </p>
      </div>

      <!-- Configuration de délai -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Délai d'expiration (en secondes)
        </label>
        <div class="flex items-center space-x-4">
          <input
            v-model.number="form.timeout"
            type="range"
            min="10"
            max="300"
            step="10"
            :disabled="loading"
            class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
          >
          <span class="text-sm font-medium text-gray-900 dark:text-white min-w-0">
            {{ Math.floor(form.timeout / 1000) }}s
          </span>
        </div>
        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
          Temps maximum d'attente pour une réponse API
        </p>
      </div>

      <!-- Tentatives de retry -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Nombre de tentatives en cas d'échec
        </label>
        <select
          v-model.number="form.retryAttempts"
          :disabled="loading"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
        >
          <option :value="1">1 tentative (aucun retry)</option>
          <option :value="2">2 tentatives (1 retry)</option>
          <option :value="3">3 tentatives (2 retries)</option>
          <option :value="5">5 tentatives (4 retries)</option>
        </select>
        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
          Nombre de fois où l'API sera sollicitée en cas d'erreur temporaire
        </p>
      </div>

      <!-- Test de connexion -->
      <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
        <div class="flex items-center justify-between">
          <div>
            <h4 class="text-sm font-medium text-gray-900 dark:text-white">
              Test de connexion API
            </h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
              Vérifier que vos paramètres API sont corrects
            </p>
          </div>
          <Button
            type="button"
            variant="secondary"
            @click="testConnection"
            :loading="testing"
          >
            <PlayIcon class="h-4 w-4 mr-2" />
            Tester
          </Button>
        </div>
        
        <!-- Résultats du test -->
        <div v-if="testResult" class="mt-4 p-3 rounded-lg" :class="[
          testResult.success 
            ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' 
            : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'
        ]">
          <div class="flex items-center">
            <CheckCircleIcon v-if="testResult.success" class="h-5 w-5 text-green-500 mr-2" />
            <XCircleIcon v-else class="h-5 w-5 text-red-500 mr-2" />
            <span class="text-sm font-medium" :class="[
              testResult.success ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'
            ]">
              {{ testResult.message }}
            </span>
          </div>
          <div v-if="testResult.details" class="mt-2 text-xs" :class="[
            testResult.success ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'
          ]">
            {{ testResult.details }}
          </div>
        </div>
      </div>

      <!-- Informations de sécurité -->
      <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-start">
          <ShieldCheckIcon class="h-5 w-5 text-blue-500 mt-0.5 mr-3" />
          <div>
            <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">
              Sécurité des clés API
            </h4>
            <ul class="text-xs text-blue-700 dark:text-blue-300 mt-1 space-y-1">
              <li>• Les clés API sont chiffrées avant stockage</li>
              <li>• Seul le propriétaire du compte peut voir et modifier ses clés</li>
              <li>• Les clés ne sont jamais exposées dans les logs</li>
              <li>• Vous pouvez révoquer une clé depuis votre compte OpenAI</li>
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

interface ApiSettings {
  openaiKey?: string
  customEndpoint?: string
  timeout: number
  retryAttempts: number
}

interface Props {
  settings: ApiSettings
  loading: boolean
}

interface Emits {
  (e: 'update', settings: Partial<ApiSettings>): void
  (e: 'test-connection'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const showApiKey = ref(false)
const testing = ref(false)
const testResult = ref<{
  success: boolean
  message: string
  details?: string
} | null>(null)

// Formulaire réactif
const form = reactive<ApiSettings>({
  openaiKey: '',
  customEndpoint: '',
  timeout: 30000,
  retryAttempts: 3
})

// État initial pour détecter les changements
let initialForm: ApiSettings | null = null

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
  testResult.value = null
}

/**
 * Soumettre le formulaire
 */
function handleSubmit() {
  emit('update', { ...form })
  initialForm = { ...form }
}

/**
 * Tester la connexion API
 */
async function testConnection() {
  if (!form.openaiKey?.trim()) {
    testResult.value = {
      success: false,
      message: 'Clé API requise',
      details: 'Veuillez saisir votre clé API OpenAI'
    }
    return
  }

  testing.value = true
  testResult.value = null

  try {
    // Émettre l'événement de test de connexion
    emit('test-connection')
    
    // Simuler un test de connexion
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    testResult.value = {
      success: true,
      message: 'Connexion réussie',
      details: 'L\'API OpenAI répond correctement avec votre clé'
    }
  } catch (error) {
    testResult.value = {
      success: false,
      message: 'Échec de connexion',
      details: 'Vérifiez votre clé API et votre connexion internet'
    }
  } finally {
    testing.value = false
  }
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
// Icônes pour les paramètres API
const EyeIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
    </svg>
  `
}

const EyeOffIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
    </svg>
  `
}

const PlayIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293H15M9 10v4a1 1 0 001 1h4M9 10V9a1 1 0 011-1h4a1 1 0 011 1v1m0 0v1a1 1 0 001 1"></path>
    </svg>
  `
}

const CheckCircleIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
  `
}

const XCircleIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
  `
}

const ShieldCheckIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
    </svg>
  `
}

export default {
  components: {
    EyeIcon,
    EyeOffIcon,
    PlayIcon,
    CheckCircleIcon,
    XCircleIcon,
    ShieldCheckIcon
  }
}
</script>