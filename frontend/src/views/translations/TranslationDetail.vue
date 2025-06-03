<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
      <div class="container-app section-padding">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
              Détails de la traduction
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
              ID: {{ translationId }}
            </p>
          </div>
          <button
            @click="$router.push('/translations')"
            class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 
                   text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg transition-colors"
          >
            Retour à la liste
          </button>
        </div>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="flex items-center justify-center min-h-96">
      <div class="text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        <p class="text-gray-500 dark:text-gray-400 mt-4">Chargement des détails...</p>
      </div>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="container-app section-padding">
      <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6 text-center">
        <div class="text-red-500 text-4xl mb-4">❌</div>
        <h2 class="text-xl font-semibold text-red-800 dark:text-red-300 mb-2">Erreur</h2>
        <p class="text-red-600 dark:text-red-400 mb-4">{{ error }}</p>
        <button
          @click="$router.push('/translations')"
          class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md transition-colors"
        >
          Retour à la liste
        </button>
      </div>
    </div>

    <!-- Content -->
    <div v-else-if="translation" class="container-app section-padding">
      <!-- Status and Actions -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
              <div
                :class="[
                  'w-3 h-3 rounded-full',
                  getStatusColor(translation.status)
                ]"
              ></div>
              <span class="font-medium text-gray-900 dark:text-white">
                {{ getStatusLabel(translation.status) }}
              </span>
            </div>
            
            <div v-if="translation.status === 'processing'" class="flex items-center gap-2">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-500"></div>
              <span class="text-sm text-gray-600 dark:text-gray-400">
                Traitement en cours...
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-2">
            <!-- Process Immediately -->
            <button
              v-if="translation.status === 'pending' && canProcessImmediately()"
              @click="processImmediately"
              class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg 
                     flex items-center gap-2 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M13 10V3L4 14h7v7l9-11h-7z"/>
              </svg>
              Traitement immédiat
            </button>

            <!-- Stop Translation -->
            <button
              v-if="['pending', 'processing'].includes(translation.status)"
              @click="stopTranslation"
              class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg 
                     flex items-center gap-2 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
              </svg>
              Arrêter
            </button>

            <!-- Download Actions -->
            <div v-if="translation.status === 'completed'" class="relative">
              <button
                @click="toggleDownloadMenu"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg 
                       flex items-center gap-2 transition-colors"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Télécharger
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <!-- Download Menu -->
              <div
                v-if="showDownloadMenu"
                class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-10"
              >
                <div class="py-1">
                  <button
                    v-for="format in downloadFormats"
                    :key="format.value"
                    @click="downloadTranslation(format.value)"
                    class="flex items-center gap-2 w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 
                           hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                  >
                    <span>{{ format.label }}</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Delete Translation -->
            <button
              @click="deleteTranslation"
              class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg 
                     flex items-center gap-2 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
              Supprimer
            </button>
          </div>
        </div>

        <!-- Translation Info Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Basic Info -->
          <div class="space-y-4">
            <h3 class="font-medium text-gray-900 dark:text-white">Informations générales</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Langue cible:</span>
                <span class="font-medium">{{ getLanguageName(translation.target_language) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Service:</span>
                <span class="font-medium">{{ getProviderName(translation.provider_used) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Créé le:</span>
                <span class="font-medium">{{ formatDate(translation.created_at) }}</span>
              </div>
              <div v-if="translation.completed_at" class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Terminé le:</span>
                <span class="font-medium">{{ formatDate(translation.completed_at) }}</span>
              </div>
            </div>
          </div>

          <!-- Quality Metrics -->
          <div class="space-y-4">
            <h3 class="font-medium text-gray-900 dark:text-white">Métriques de qualité</h3>
            <div class="space-y-2 text-sm">
              <div v-if="translation.quality_score" class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Score qualité:</span>
                <span class="font-medium">{{ (translation.quality_score * 100).toFixed(1) }}%</span>
              </div>
              <div v-if="translation.processing_time_seconds" class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Temps traitement:</span>
                <span class="font-medium">{{ translation.processing_time_seconds.toFixed(1) }}s</span>
              </div>
              <div v-if="translation.segments_count" class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Segments:</span>
                <span class="font-medium">{{ translation.segments_count }}</span>
              </div>
            </div>
          </div>

          <!-- Cost Info -->
          <div class="space-y-4">
            <h3 class="font-medium text-gray-900 dark:text-white">Informations de coût</h3>
            <div class="space-y-2 text-sm">
              <div v-if="translation.estimated_cost" class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Coût estimé:</span>
                <span class="font-medium">${{ translation.estimated_cost.toFixed(4) }}</span>
              </div>
              <div v-if="translation.actual_cost" class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Coût réel:</span>
                <span class="font-medium">${{ translation.actual_cost.toFixed(4) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Configuration Used -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h3 class="font-medium text-gray-900 dark:text-white mb-4">Configuration utilisée</h3>
        <div v-if="translationConfig" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div class="flex items-center gap-2">
            <div :class="translationConfig.optimize_for_dubbing ? 'text-green-500' : 'text-gray-400'">
              {{ translationConfig.optimize_for_dubbing ? '✓' : '✗' }}
            </div>
            <span class="text-gray-600 dark:text-gray-400">Optimisation doublage</span>
          </div>
          <div class="flex items-center gap-2">
            <div :class="translationConfig.preserve_emotions ? 'text-green-500' : 'text-gray-400'">
              {{ translationConfig.preserve_emotions ? '✓' : '✗' }}
            </div>
            <span class="text-gray-600 dark:text-gray-400">Préservation émotions</span>
          </div>
          <div class="flex items-center gap-2">
            <div :class="translationConfig.use_character_names ? 'text-green-500' : 'text-gray-400'">
              {{ translationConfig.use_character_names ? '✓' : '✗' }}
            </div>
            <span class="text-gray-600 dark:text-gray-400">Noms de personnages</span>
          </div>
          <div class="flex items-center gap-2">
            <div :class="translationConfig.length_optimization ? 'text-green-500' : 'text-gray-400'">
              {{ translationConfig.length_optimization ? '✓' : '✗' }}
            </div>
            <span class="text-gray-600 dark:text-gray-400">Optimisation longueur</span>
          </div>
        </div>
      </div>

      <!-- Translation Preview/Results -->
      <div v-if="translation.status === 'completed'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="font-medium text-gray-900 dark:text-white mb-4">Aperçu de la traduction</h3>
        
        <!-- Loading segments -->
        <div v-if="loadingSegments" class="text-center py-8">
          <LoadingSpinner size="md" />
          <p class="text-gray-500 dark:text-gray-400 mt-2">Chargement des segments...</p>
        </div>
        
        <!-- Display segments -->
        <div v-else-if="translationSegments.length > 0" class="space-y-3 max-h-96 overflow-y-auto">
          <div 
            v-for="(segment, index) in translationSegments.slice(0, 5)" 
            :key="index"
            class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg"
          >
            <div class="flex items-start gap-3">
              <span class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-1">
                {{ formatSegmentTime(segment.start) }}
              </span>
              <p class="flex-1 text-gray-700 dark:text-gray-300">
                {{ segment.text }}
              </p>
            </div>
          </div>
          
          <!-- Message si plus de segments -->
          <div v-if="translationSegments.length > 5" 
               class="text-center py-4 text-gray-500 dark:text-gray-400">
            <p class="text-sm">
              ... et {{ translationSegments.length - 5 }} segments supplémentaires
            </p>
            <p class="text-xs mt-1">
              Téléchargez le fichier complet pour voir tous les segments
            </p>
          </div>
        </div>
        
        <!-- Fallback message -->
        <div v-else class="text-center text-gray-500 dark:text-gray-400 py-8">
          <p>Aucun segment disponible pour l'aperçu</p>
          <p class="text-sm mt-2">Utilisez le bouton de téléchargement pour accéder au contenu</p>
        </div>
      </div>

      <!-- Processing Status -->
      <div v-else-if="translation.status === 'processing'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <!-- Traitement immédiat avec animation détaillée -->
        <div v-if="translation.immediate_processing">
          <h3 class="font-medium text-gray-900 dark:text-white mb-4">Traitement immédiat</h3>
          <TranslationProcessingIndicator 
            variant="detailed" 
            :segments-count="translation.segments_count || 10"
            :start-time="new Date(translation.started_at || Date.now())"
            :show-progress="true"
          />
          <div class="flex justify-center mt-4">
            <button
              @click="refreshStatus"
              class="bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:hover:bg-blue-800 
                     text-blue-700 dark:text-blue-300 px-4 py-2 rounded-lg transition-colors"
            >
              Actualiser le statut
            </button>
          </div>
        </div>
        
        <!-- Traitement standard en arrière-plan -->
        <div v-else>
          <h3 class="font-medium text-gray-900 dark:text-white mb-4">État du traitement</h3>
          <div class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mb-4"></div>
            <p class="text-gray-600 dark:text-gray-400">
              Votre traduction est en cours de traitement par {{ getProviderName(translation.provider_used) }}
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">
              Cela peut prendre quelques minutes selon la longueur du contenu
            </p>
            <button
              @click="refreshStatus"
              class="mt-4 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:hover:bg-blue-800 
                     text-blue-700 dark:text-blue-300 px-4 py-2 rounded-lg transition-colors"
            >
              Actualiser le statut
            </button>
          </div>
        </div>
      </div>

      <!-- Pending Status -->
      <div v-else-if="translation.status === 'pending'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="font-medium text-gray-900 dark:text-white mb-4">En attente</h3>
        <div class="text-center py-8">
          <div class="text-4xl mb-4">⏳</div>
          <p class="text-gray-600 dark:text-gray-400">
            Votre traduction est en file d'attente et sera bientôt traitée
          </p>
          <button
            @click="refreshStatus"
            class="mt-4 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:hover:bg-blue-800 
                   text-blue-700 dark:text-blue-300 px-4 py-2 rounded-lg transition-colors"
          >
            Actualiser le statut
          </button>
        </div>
      </div>

      <!-- Failed Status -->
      <div v-else-if="translation.status === 'failed'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="font-medium text-gray-900 dark:text-white mb-4">Échec du traitement</h3>
        <div class="text-center py-8">
          <div class="text-4xl mb-4 text-red-500">❌</div>
          <p class="text-red-600 dark:text-red-400 mb-4">
            La traduction a échoué lors du traitement
          </p>
          <button
            @click="retryTranslation"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
          >
            Réessayer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { TranslationAPI } from '@/api/translations'
import TranslationProcessingIndicator from '@/components/translation/TranslationProcessingIndicator.vue'

// Props
const route = useRoute()
const router = useRouter()
const translationId = computed(() => route.params.id as string)

// État local
const loading = ref(true)
const error = ref<string | null>(null)
const translation = ref<any>(null)
const showDownloadMenu = ref(false)
const loadingSegments = ref(false)
const translationSegments = ref<any[]>([])

// Polling pour les mises à jour de statut
let statusInterval: NodeJS.Timeout | null = null

// Configuration de traduction parsée
const translationConfig = computed(() => {
  if (!translation.value?.config_json) return null
  try {
    return JSON.parse(translation.value.config_json)
  } catch {
    return null
  }
})

// Formats de téléchargement
const downloadFormats = [
  { value: 'json', label: 'JSON (natif)' },
  { value: 'srt', label: 'SRT (sous-titres)' },
  { value: 'vtt', label: 'WebVTT' },
  { value: 'txt', label: 'Texte simple' },
  { value: 'dubbing_json', label: 'JSON doublage' }
]

// Méthodes utilitaires
const getStatusColor = (status: string) => {
  switch (status) {
    case 'completed': return 'bg-green-500'
    case 'processing': return 'bg-blue-500'
    case 'pending': return 'bg-yellow-500'
    case 'failed': return 'bg-red-500'
    case 'cancelled': return 'bg-orange-500'
    default: return 'bg-gray-500'
  }
}

const getStatusLabel = (status: string) => {
  switch (status) {
    case 'completed': return 'Terminé'
    case 'processing': return 'En cours'
    case 'pending': return 'En attente'
    case 'failed': return 'Échoué'
    case 'cancelled': return 'Annulé'
    default: return 'Inconnu'
  }
}

const getLanguageName = (code: string) => {
  const languages: Record<string, string> = {
    'fr': 'Français',
    'es': 'Español',
    'de': 'Deutsch',
    'it': 'Italiano',
    'pt': 'Português',
    'en': 'English'
  }
  return languages[code] || code.toUpperCase()
}

const getProviderName = (provider: string) => {
  const providers: Record<string, string> = {
    'gpt-4o-mini': 'GPT-4o Mini',
    'hybrid': 'Service Hybride',
    'whisper-1': 'Whisper-1'
  }
  return providers[provider] || provider
}

const formatDate = (dateString: string) => {
  try {
    return new Date(dateString).toLocaleString('fr-FR')
  } catch {
    return dateString
  }
}

// Actions
const loadTranslation = async (silent = false) => {
  try {
    if (!silent) loading.value = true
    error.value = null
    
    const response = await TranslationAPI.getTranslationStatus(translationId.value)
    
    if (response.success) {
      translation.value = response.data
      
      // Charger les segments si la traduction est complète
      if (response.data?.status === 'completed' && !silent) {
        await loadTranslationSegments()
      }
    } else {
      error.value = 'Traduction non trouvée'
    }
  } catch (err: any) {
    // Ne pas spammer la console pour les erreurs de polling
    if (!silent) {
      console.error('Erreur chargement:', err)
    }
    error.value = err.message || 'Erreur lors du chargement'
  } finally {
    if (!silent) loading.value = false
  }
}

const refreshStatus = async () => {
  await loadTranslation()
}

const toggleDownloadMenu = () => {
  showDownloadMenu.value = !showDownloadMenu.value
}

const downloadTranslation = async (format: string) => {
  try {
    showDownloadMenu.value = false
    const blob = await TranslationAPI.downloadTranslation(translationId.value, format as any)
    
    // Créer et déclencher le téléchargement
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.style.display = 'none'
    a.href = url
    a.download = `translation_${translationId.value}.${format === 'dubbing_json' ? 'json' : format}`
    
    document.body.appendChild(a)
    a.click()
    window.URL.revokeObjectURL(url)
    document.body.removeChild(a)
  } catch (error: any) {
    console.error('Erreur téléchargement:', error)
    // TODO: Afficher notification d'erreur
  }
}

const retryTranslation = async () => {
  // TODO: Implémenter la logique de retry
  console.log('Retry translation:', translationId.value)
}

const stopTranslation = async () => {
  if (!confirm('Êtes-vous sûr de vouloir arrêter cette traduction ?')) {
    return
  }
  
  try {
    const response = await TranslationAPI.stopTranslation(translationId.value)
    
    if (response.success) {
      // Mettre à jour le statut localement et arrêter le polling
      if (translation.value) {
        translation.value.status = 'cancelled'
        translation.value.completed_at = new Date().toISOString()
      }
      stopStatusPolling()
      
      // TODO: Afficher notification de succès
      console.log('Traduction arrêtée avec succès')
    }
  } catch (error: any) {
    console.error('Erreur lors de l\'arrêt:', error)
    // TODO: Afficher notification d'erreur
  }
}

const deleteTranslation = async () => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer définitivement cette traduction ? Cette action est irréversible.')) {
    return
  }
  
  try {
    const response = await TranslationAPI.deleteTranslation(translationId.value)
    
    if (response.success) {
      stopStatusPolling()
      
      // Rediriger vers la liste des traductions
      await router.push('/translations')
      
      // TODO: Afficher notification de succès
      console.log('Traduction supprimée avec succès')
    }
  } catch (error: any) {
    console.error('Erreur lors de la suppression:', error)
    // TODO: Afficher notification d'erreur
  }
}

const canProcessImmediately = () => {
  return translation.value?.status === 'pending' && 
         (translation.value.segments_count || 0) <= 20 // Limite pour traitement immédiat
}

const processImmediately = async () => {
  if (!confirm('Lancer le traitement immédiat ? Cela peut prendre quelques minutes selon la taille de la traduction.')) {
    return
  }
  
  try {
    // Marquer localement comme processing
    if (translation.value) {
      translation.value.status = 'processing'
      translation.value.started_at = new Date().toISOString()
    }
    
    // Lancer le traitement
    const response = await TranslationAPI.processImmediately(translationId.value)
    
    if (response.success) {
      // Démarrer le polling pour le feedback temps réel
      startStatusPolling()
      
      // TODO: Afficher notification de succès
      console.log('Traitement immédiat démarré')
    }
  } catch (error: any) {
    // Restaurer le statut en cas d'erreur
    if (translation.value) {
      translation.value.status = 'pending'
      translation.value.started_at = null
    }
    
    console.error('Erreur lors du lancement:', error)
    // TODO: Afficher notification d'erreur
  }
}

// Charger les segments traduits
const loadTranslationSegments = async () => {
  if (translation.value?.status !== 'completed') return
  
  try {
    loadingSegments.value = true
    
    // Télécharger le JSON pour obtenir les segments
    const response = await fetch(`/api/v2/translations/download/${translationId.value}?format=json`)
    if (response.ok) {
      const data = await response.json()
      translationSegments.value = data.segments || []
    }
  } catch (err) {
    console.error('Erreur chargement segments:', err)
    translationSegments.value = []
  } finally {
    loadingSegments.value = false
  }
}

// Formater le temps d'un segment
const formatSegmentTime = (seconds: number) => {
  if (!seconds && seconds !== 0) return '0:00'
  const mins = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

// Polling automatique pour les traductions en cours
const startStatusPolling = () => {
  if (translation.value?.status === 'processing' || translation.value?.status === 'pending') {
    statusInterval = setInterval(async () => {
      // Utiliser le mode silencieux pour ne pas spammer la console
      await loadTranslation(true)
      
      // Arrêter le polling si terminé
      if (['completed', 'failed', 'cancelled'].includes(translation.value?.status || '')) {
        stopStatusPolling()
      }
    }, 5000) // Polling toutes les 5 secondes
  }
}

const stopStatusPolling = () => {
  if (statusInterval) {
    clearInterval(statusInterval)
    statusInterval = null
  }
}

// Lifecycle
onMounted(async () => {
  await loadTranslation()
  startStatusPolling()
})

onBeforeUnmount(() => {
  stopStatusPolling()
})

// Fermer le menu de téléchargement si on clique ailleurs
const handleClickOutside = (event: MouseEvent) => {
  if (showDownloadMenu.value) {
    const target = event.target as HTMLElement
    if (!target.closest('.relative')) {
      showDownloadMenu.value = false
    }
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.container-app {
  @apply max-w-7xl mx-auto px-4 sm:px-6 lg:px-8;
}

.section-padding {
  @apply py-6;
}
</style>