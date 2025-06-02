<template>
  <div class="translations-page">
    <div class="container mx-auto px-4 py-8">
      <!-- En-tête de la page -->
      <div class="page-header mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
              Traductions
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
              Système révolutionnaire de traduction avec préservation des émotions et timing précis
            </p>
          </div>
          
          <!-- Boutons d'action principaux -->
          <div class="flex items-center gap-3">
            <button
              @click="showCapabilities = true"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                     hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors
                     flex items-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Capacités
            </button>
            
            <button
              @click="currentView = 'create'"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 
                     transition-colors flex items-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              Nouvelle traduction
            </button>
          </div>
        </div>

        <!-- Statistiques rapides -->
        <div v-if="quickStats" class="quick-stats mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="stat-card bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
              </div>
              <div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                  {{ quickStats.total_translations }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Traductions</div>
              </div>
            </div>
          </div>

          <div class="stat-card bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
              </div>
              <div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                  {{ quickStats.success_rate }}%
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Succès</div>
              </div>
            </div>
          </div>

          <div class="stat-card bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                </svg>
              </div>
              <div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                  ${{ quickStats.total_cost_usd?.toFixed(3) || '0.000' }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Coût total</div>
              </div>
            </div>
          </div>

          <div class="stat-card bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-orange-100 dark:bg-orange-900 rounded-lg">
                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
              </div>
              <div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                  {{ quickStats.average_quality_score ? (quickStats.average_quality_score * 100).toFixed(1) + '%' : 'N/A' }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Qualité moy.</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation des vues -->
      <div class="view-navigation mb-6">
        <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
          <button
            @click="currentView = 'list'"
            :class="[
              'px-4 py-2 rounded-md transition-all duration-200 text-sm font-medium',
              currentView === 'list'
                ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
            ]"
          >
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
              </svg>
              Mes traductions
            </div>
          </button>
          
          <button
            @click="currentView = 'create'"
            :class="[
              'px-4 py-2 rounded-md transition-all duration-200 text-sm font-medium',
              currentView === 'create'
                ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
            ]"
          >
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              Nouvelle traduction
            </div>
          </button>
        </div>
      </div>

      <!-- Contenu principal -->
      <div class="main-content">
        <!-- Vue Liste -->
        <div v-if="currentView === 'list'" class="list-view">
          <TranslationList
            @create-new="currentView = 'create'"
            @view-details="viewTranslationDetails"
            @update-stats="loadQuickStats"
          />
        </div>

        <!-- Vue Création -->
        <div v-else-if="currentView === 'create'" class="create-view">
          <TranslationCreator
            :transcription-id="selectedTranscriptionId"
            @translation-created="onTranslationCreated"
            @error="onTranslationError"
          />
        </div>

        <!-- Vue Détails -->
        <div v-else-if="currentView === 'details'" class="details-view">
          <!-- TODO: Composant TranslationDetails -->
          <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold">Détails de la traduction</h3>
              <button
                @click="currentView = 'list'"
                class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                Retour à la liste
              </button>
            </div>
            <p class="text-gray-600">ID: {{ selectedTranslationId }}</p>
            <p class="text-sm text-gray-500 mt-2">Composant en cours de développement...</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal des capacités -->
    <div
      v-if="showCapabilities"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click="showCapabilities = false"
    >
      <div
        class="bg-white dark:bg-gray-800 rounded-lg max-w-4xl w-full max-h-[90vh] overflow-auto"
        @click.stop
      >
        <div class="p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
              Capacités de traduction
            </h3>
            <button
              @click="showCapabilities = false"
              class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <div v-if="capabilities" class="space-y-6">
            <!-- Langues supportées -->
            <div>
              <h4 class="text-lg font-medium mb-3">Langues supportées</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                <div
                  v-for="(language, code) in capabilities.data.supported_languages"
                  :key="code"
                  class="p-3 border border-gray-200 dark:border-gray-600 rounded-lg"
                >
                  <div class="font-medium">{{ language.name }}</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">
                    Qualité: {{ language.quality }}
                  </div>
                  <div class="text-xs text-gray-500 mt-1">
                    {{ language.specialties.join(', ') }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Services disponibles -->
            <div>
              <h4 class="text-lg font-medium mb-3">Services de traduction</h4>
              <div class="space-y-3">
                <div
                  v-for="(provider, name) in capabilities.data.pricing"
                  :key="name"
                  class="p-4 border border-gray-200 dark:border-gray-600 rounded-lg"
                >
                  <div class="flex items-center justify-between">
                    <div>
                      <div class="font-medium capitalize">{{ name }}</div>
                      <div class="text-sm text-gray-600 dark:text-gray-400">
                        {{ provider.includes.join(', ') }}
                      </div>
                    </div>
                    <div class="text-right">
                      <div class="font-medium">${{ provider.base_cost_per_minute }}/min</div>
                      <div class="text-sm text-gray-500">{{ provider.currency }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Capacités techniques -->
            <div>
              <h4 class="text-lg font-medium mb-3">Capacités techniques</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <h5 class="font-medium">Préservation timing</h5>
                  <div class="text-sm space-y-1">
                    <div>✓ Niveau mot: {{ capabilities.data.features.timestamp_preservation.word_level ? 'Oui' : 'Non' }}</div>
                    <div>✓ Niveau segment: {{ capabilities.data.features.timestamp_preservation.segment_level ? 'Oui' : 'Non' }}</div>
                    <div>✓ Précision: {{ capabilities.data.features.timestamp_preservation.precision }}</div>
                    <div>✓ Prêt doublage: {{ capabilities.data.features.timestamp_preservation.dubbing_ready ? 'Oui' : 'Non' }}</div>
                  </div>
                </div>
                
                <div class="space-y-2">
                  <h5 class="font-medium">Adaptation intelligente</h5>
                  <div class="text-sm space-y-1">
                    <div>✓ Optimisation longueur: {{ capabilities.data.features.intelligent_adaptation.length_optimization ? 'Oui' : 'Non' }}</div>
                    <div>✓ Contexte émotionnel: {{ capabilities.data.features.intelligent_adaptation.emotional_context ? 'Oui' : 'Non' }}</div>
                    <div>✓ Préservation personnages: {{ capabilities.data.features.intelligent_adaptation.character_preservation ? 'Oui' : 'Non' }}</div>
                    <div>✓ Termes techniques: {{ capabilities.data.features.intelligent_adaptation.technical_terms ? 'Oui' : 'Non' }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Limites -->
            <div>
              <h4 class="text-lg font-medium mb-3">Limites et garanties</h4>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                  <div class="font-medium">Durée max</div>
                  <div>{{ capabilities.data.limits.max_audio_duration_minutes }} min</div>
                </div>
                <div>
                  <div class="font-medium">Segments max</div>
                  <div>{{ capabilities.data.limits.max_segments_per_request }}</div>
                </div>
                <div>
                  <div class="font-medium">Req/min</div>
                  <div>{{ capabilities.data.limits.rate_limits.requests_per_minute }}</div>
                </div>
                <div>
                  <div class="font-medium">Cache</div>
                  <div>{{ capabilities.data.limits.cache_retention_hours }}h</div>
                </div>
              </div>
            </div>
          </div>
          
          <div v-else class="text-center py-8">
            <div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full mx-auto"></div>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Chargement des capacités...</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Notifications -->
    <div
      v-if="notification"
      :class="[
        'fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 transform transition-all duration-300',
        notification.type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white',
        showNotification ? 'translate-x-0 opacity-100' : 'translate-x-full opacity-0'
      ]"
    >
      <div class="flex items-center gap-3">
        <svg 
          v-if="notification.type === 'success'"
          class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <svg 
          v-else
          class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        <span>{{ notification.message }}</span>
        <button
          @click="hideNotification"
          class="ml-2 text-white hover:text-gray-200"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { TranslationAPI } from '@/api/translations'
import TranslationCreator from '@/components/translation/TranslationCreator.vue'
import TranslationList from '@/components/translation/TranslationList.vue'

// État local
const currentView = ref<'list' | 'create' | 'details'>('list')
const selectedTranscriptionId = ref<string>('')
const selectedTranslationId = ref<string>('')
const showCapabilities = ref(false)
const capabilities = ref<any>(null)
const quickStats = ref<any>(null)

// Notifications
const notification = ref<{ type: 'success' | 'error', message: string } | null>(null)
const showNotification = ref(false)

// Méthodes
const viewTranslationDetails = (translationId: string) => {
  selectedTranslationId.value = translationId
  currentView.value = 'details'
}

const onTranslationCreated = (translationId: string) => {
  showSuccessNotification(`Traduction ${translationId} créée avec succès`)
  currentView.value = 'list'
  loadQuickStats() // Recharger les stats
}

const onTranslationError = (message: string) => {
  showErrorNotification(message)
}

const showSuccessNotification = (message: string) => {
  notification.value = { type: 'success', message }
  showNotification.value = true
  setTimeout(hideNotification, 5000)
}

const showErrorNotification = (message: string) => {
  notification.value = { type: 'error', message }
  showNotification.value = true
  setTimeout(hideNotification, 5000)
}

const hideNotification = () => {
  showNotification.value = false
  setTimeout(() => {
    notification.value = null
  }, 300)
}

const loadCapabilities = async () => {
  try {
    capabilities.value = await TranslationAPI.getCapabilities()
  } catch (error: any) {
    console.error('Erreur chargement capacités:', error)
  }
}

const loadQuickStats = async () => {
  try {
    const response = await TranslationAPI.getTranslations({ limit: 1 })
    if (response.success && response.data) {
      quickStats.value = response.data.statistics
    }
  } catch (error: any) {
    console.error('Erreur chargement stats:', error)
  }
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    loadCapabilities(),
    loadQuickStats()
  ])
})
</script>

<style scoped>
.translations-page {
  @apply min-h-screen bg-gray-50 dark:bg-gray-900;
}

.stat-card {
  @apply transition-all duration-200 hover:shadow-md hover:scale-105;
}

.view-navigation {
  @apply sticky top-0 z-10 bg-gray-50 dark:bg-gray-900 pb-2;
}

@media (max-width: 768px) {
  .quick-stats {
    @apply grid-cols-2;
  }
  
  .view-navigation .flex {
    @apply flex-col gap-2;
  }
  
  .view-navigation button {
    @apply w-full justify-center;
  }
}
</style>