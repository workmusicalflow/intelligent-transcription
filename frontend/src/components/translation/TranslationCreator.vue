<template>
  <div class="translation-creator">
    <!-- En-tête -->
    <div class="creator-header">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        Créer une Traduction
      </h3>
      <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
        Traduisez votre transcription vers une autre langue avec notre système révolutionnaire
      </p>
    </div>

    <!-- Formulaire de création -->
    <form @submit.prevent="createTranslation" class="space-y-6">
      <!-- Sélection de la transcription -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Transcription source
        </label>
        <select 
          v-model="formData.transcription_id"
          :disabled="!!transcriptionId"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                 focus:ring-2 focus:ring-blue-500 focus:border-transparent
                 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          required
        >
          <option value="">Sélectionner une transcription...</option>
          <option 
            v-for="transcription in availableTranscriptions" 
            :key="transcription.id"
            :value="transcription.id"
          >
            {{ transcription.title || transcription.file_name || transcription.id }}
            ({{ transcription.language }})
          </option>
        </select>
      </div>

      <!-- Sélection de la langue cible -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Langue de destination *
        </label>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
          <button
            v-for="(language, code) in supportedLanguages"
            :key="code"
            type="button"
            @click="selectTargetLanguage(code)"
            :class="[
              'p-3 border rounded-lg text-left transition-all duration-200',
              formData.target_language === code
                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300'
                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
            ]"
          >
            <div class="font-medium text-sm">{{ language.name }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
              Qualité: {{ language.quality }}
            </div>
          </button>
        </div>
      </div>

      <!-- Sélection du provider -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Service de traduction
        </label>
        <div class="space-y-3">
          <div
            v-for="provider in availableProviders"
            :key="provider.id"
            @click="selectProvider(provider.id)"
            :class="[
              'p-4 border rounded-lg cursor-pointer transition-all duration-200',
              formData.provider === provider.id
                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'
            ]"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <div class="font-medium text-sm">{{ provider.name }}</div>
                  <span
                    v-if="provider.recommended"
                    class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full"
                  >
                    Recommandé
                  </span>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  {{ provider.description }}
                </div>
                <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                  <span>Coût: ${{ provider.cost_per_minute }}/min</span>
                  <span>Qualité: {{ provider.quality_level }}</span>
                  <span v-if="provider.dubbing_optimized" class="text-green-600">
                    ✓ Doublage optimisé
                  </span>
                </div>
              </div>
              <input
                type="radio"
                :value="provider.id"
                v-model="formData.provider"
                class="mt-1"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Configuration avancée -->
      <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
          Configuration avancée
        </h4>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Style d'adaptation -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Style d'adaptation
            </label>
            <select 
              v-model="formData.config.style_adaptation"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                     focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700"
            >
              <option value="cinematic">Cinématique (films/séries)</option>
              <option value="educational">Éducatif (cours/tutoriels)</option>
              <option value="formal">Formel (business)</option>
              <option value="casual">Décontracté (réseaux sociaux)</option>
            </select>
          </div>

          <!-- Seuil de qualité -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Seuil de qualité minimum
            </label>
            <select 
              v-model="formData.config.quality_threshold"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                     focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700"
            >
              <option :value="0.8">Standard (80%)</option>
              <option :value="0.85">Élevé (85%)</option>
              <option :value="0.9">Très élevé (90%)</option>
              <option :value="0.95">Premium (95%)</option>
            </select>
          </div>
        </div>

        <!-- Options spécialisées -->
        <div class="mt-4 space-y-3">
          <label class="flex items-center gap-2">
            <input
              type="checkbox"
              v-model="formData.config.optimize_for_dubbing"
              class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
            <span class="text-sm text-gray-700 dark:text-gray-300">
              Optimiser pour le doublage (préservation émotions + timing strict)
            </span>
          </label>

          <label class="flex items-center gap-2">
            <input
              type="checkbox"
              v-model="formData.config.preserve_emotions"
              class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
            <span class="text-sm text-gray-700 dark:text-gray-300">
              Préserver le contexte émotionnel
            </span>
          </label>

          <label class="flex items-center gap-2">
            <input
              type="checkbox"
              v-model="formData.config.use_character_names"
              class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
            <span class="text-sm text-gray-700 dark:text-gray-300">
              Préserver les noms de personnages
            </span>
          </label>

          <label class="flex items-center gap-2">
            <input
              type="checkbox"
              v-model="formData.config.technical_terms_handling"
              class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
            <span class="text-sm text-gray-700 dark:text-gray-300">
              Gestion intelligente des termes techniques
            </span>
          </label>

          <label class="flex items-center gap-2">
            <input
              type="checkbox"
              v-model="formData.config.length_optimization"
              class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
            <span class="text-sm text-gray-700 dark:text-gray-300">
              Optimisation de longueur pour synchronisation
            </span>
          </label>
        </div>
      </div>

      <!-- Estimation des coûts -->
      <div 
        v-if="costEstimate"
        class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4"
      >
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">
          Estimation
        </h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
          <div>
            <div class="text-gray-600 dark:text-gray-400">Coût estimé</div>
            <div class="font-semibold">${{ costEstimate.estimated_cost.toFixed(4) }}</div>
          </div>
          <div>
            <div class="text-gray-600 dark:text-gray-400">Temps traitement</div>
            <div class="font-semibold">{{ costEstimate.estimated_processing_time }}s</div>
          </div>
          <div>
            <div class="text-gray-600 dark:text-gray-400">Qualité estimée</div>
            <div class="font-semibold">{{ (costEstimate.quality_estimate * 100).toFixed(1) }}%</div>
          </div>
          <div>
            <div class="text-gray-600 dark:text-gray-400">Provider optimal</div>
            <div class="font-semibold">{{ costEstimate.recommended_provider }}</div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-600">
        <button
          type="button"
          @click="estimateCost"
          :disabled="!canEstimate || estimating"
          class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg
                 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors
                 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ estimating ? 'Estimation...' : 'Estimer le coût' }}
        </button>

        <button
          type="submit"
          :disabled="!canCreate || creating"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 
                 transition-colors disabled:opacity-50 disabled:cursor-not-allowed
                 flex items-center gap-2"
        >
          <svg v-if="creating" class="animate-spin h-4 w-4" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
          </svg>
          {{ creating ? 'Création...' : 'Créer la traduction' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { TranslationAPI, type CreateTranslationRequest, type TranslationConfig } from '@/api/translations'
import { TranscriptionAPI } from '@/api/transcriptions'

interface Props {
  transcriptionId?: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  translationCreated: [translationId: string]
  error: [message: string]
}>()

// État local
const creating = ref(false)
const estimating = ref(false)
const capabilities = ref<any>(null)
const costEstimate = ref<any>(null)
const availableTranscriptions = ref<any[]>([])

// Données du formulaire
const formData = reactive<CreateTranslationRequest & { config: TranslationConfig }>({
  transcription_id: props.transcriptionId || '',
  target_language: '',
  provider: 'gpt-4o-mini',
  config: {
    optimize_for_dubbing: true,
    preserve_emotions: true,
    use_character_names: false,
    technical_terms_handling: true,
    style_adaptation: 'cinematic',
    length_optimization: true,
    quality_threshold: 0.85
  }
})

// Langues supportées (calculé depuis les capacités)
const supportedLanguages = computed(() => {
  return capabilities.value?.data?.supported_languages || {}
})

// Providers disponibles avec recommandations
const availableProviders = computed(() => {
  if (!capabilities.value?.data) return []
  
  const targetLang = formData.target_language
  const targetLanguageInfo = supportedLanguages.value[targetLang]
  
  return [
    {
      id: 'gpt-4o-mini',
      name: 'GPT-4o Mini',
      description: 'IA avancée avec préservation émotions et timing précis',
      cost_per_minute: 0.008,
      quality_level: 'Excellent',
      dubbing_optimized: true,
      recommended: targetLanguageInfo?.optimal_providers?.includes('gpt-4o-mini') || false
    },
    {
      id: 'hybrid',
      name: 'Service Hybride',
      description: 'Fiabilité maximale avec fallbacks automatiques',
      cost_per_minute: 0.009,
      quality_level: 'Premium',
      dubbing_optimized: true,
      recommended: targetLanguageInfo?.optimal_providers?.includes('hybrid') || false
    },
    {
      id: 'whisper-1',
      name: 'Whisper-1',
      description: 'OpenAI natif, optimisé pour l\'anglais',
      cost_per_minute: 0.006,
      quality_level: 'Bon',
      dubbing_optimized: false,
      recommended: targetLanguageInfo?.optimal_providers?.includes('whisper-1') || false
    }
  ]
})

// Validation
const canEstimate = computed(() => {
  return formData.transcription_id && formData.target_language
})

const canCreate = computed(() => {
  return canEstimate.value && formData.provider
})

// Méthodes
const selectTargetLanguage = (languageCode: string) => {
  formData.target_language = languageCode
  
  // Auto-sélectionner le provider recommandé
  const languageInfo = supportedLanguages.value[languageCode]
  if (languageInfo?.optimal_providers?.length > 0) {
    const recommendedProvider = languageInfo.optimal_providers[0]
    if (availableProviders.value.find(p => p.id === recommendedProvider)) {
      formData.provider = recommendedProvider
    }
  }
  
  // Reset l'estimation
  costEstimate.value = null
}

const selectProvider = (providerId: string) => {
  formData.provider = providerId
  costEstimate.value = null // Reset l'estimation
}

const estimateCost = async () => {
  if (!canEstimate.value) return
  
  estimating.value = true
  try {
    const response = await TranslationAPI.estimateTranslationCost(
      formData.transcription_id,
      formData.target_language,
      formData.provider,
      formData.config
    )
    
    if (response.success) {
      costEstimate.value = response.data
    } else {
      emit('error', response.error || 'Erreur lors de l\'estimation')
    }
  } catch (error: any) {
    emit('error', error.message || 'Erreur lors de l\'estimation')
  } finally {
    estimating.value = false
  }
}

const createTranslation = async () => {
  if (!canCreate.value) return
  
  creating.value = true
  try {
    const response = await TranslationAPI.createTranslation({
      transcription_id: formData.transcription_id,
      target_language: formData.target_language,
      provider: formData.provider,
      config: formData.config
    })
    
    if (response.success) {
      emit('translationCreated', response.data.translation_id)
    } else {
      emit('error', response.error || 'Erreur lors de la création')
    }
  } catch (error: any) {
    emit('error', error.message || 'Erreur lors de la création')
  } finally {
    creating.value = false
  }
}

const loadCapabilities = async () => {
  try {
    capabilities.value = await TranslationAPI.getCapabilities()
  } catch (error: any) {
    console.error('Erreur chargement capacités:', error)
  }
}

const loadTranscriptions = async () => {
  if (props.transcriptionId) return // Transcription fixée
  
  try {
    const response = await TranscriptionAPI.listTranscriptions({ 
      limit: 50, 
      status: 'completed' 
    })
    
    if (response.success) {
      availableTranscriptions.value = response.data?.transcriptions || []
    }
  } catch (error: any) {
    console.error('Erreur chargement transcriptions:', error)
  }
}

// Auto-estimer quand les paramètres changent
watch([() => formData.transcription_id, () => formData.target_language, () => formData.provider], () => {
  costEstimate.value = null
}, { deep: true })

// Initialisation
onMounted(async () => {
  await Promise.all([
    loadCapabilities(),
    loadTranscriptions()
  ])
})
</script>

<style scoped>
.translation-creator {
  @apply max-w-4xl mx-auto p-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg;
}

.creator-header {
  @apply mb-6 pb-4 border-b border-gray-200 dark:border-gray-600;
}
</style>