<template>
  <div class="translation-list">
    <!-- En-tête avec filtres -->
    <div class="list-header">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Mes Traductions
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Gérez vos traductions et téléchargez les résultats
          </p>
        </div>
        
        <button
          @click="$emit('create-new')"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 
                 transition-colors flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Nouvelle traduction
        </button>
      </div>

      <!-- Filtres et recherche -->
      <div class="filters-section bg-gray-50 dark:bg-gray-800 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Recherche -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Rechercher
            </label>
            <input
              v-model="filters.search"
              type="text"
              placeholder="ID transcription, langue..."
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                     focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700"
              @input="debouncedRefresh"
            />
          </div>

          <!-- Langue -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Langue
            </label>
            <select
              v-model="filters.target_language"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                     focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700"
              @change="refreshList"
            >
              <option value="">Toutes les langues</option>
              <option value="fr">Français</option>
              <option value="es">Español</option>
              <option value="de">Deutsch</option>
              <option value="it">Italiano</option>
              <option value="pt">Português</option>
              <option value="en">English</option>
            </select>
          </div>

          <!-- Provider -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Service
            </label>
            <select
              v-model="filters.provider"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                     focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700"
              @change="refreshList"
            >
              <option value="">Tous les services</option>
              <option value="gpt-4o-mini">GPT-4o Mini</option>
              <option value="hybrid">Service Hybride</option>
              <option value="whisper-1">Whisper-1</option>
            </select>
          </div>

          <!-- Statut -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Statut
            </label>
            <select
              v-model="filters.status"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                     focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700"
              @change="refreshList"
            >
              <option value="">Tous les statuts</option>
              <option value="pending">En attente</option>
              <option value="processing">En cours</option>
              <option value="completed">Terminé</option>
              <option value="failed">Échoué</option>
            </select>
          </div>
        </div>

        <!-- Tri -->
        <div class="flex items-center gap-4 mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
          <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
              Trier par:
            </label>
            <select
              v-model="filters.sort_by"
              class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded
                     bg-white dark:bg-gray-700 text-sm"
              @change="refreshList"
            >
              <option value="created_at">Date de création</option>
              <option value="target_language">Langue</option>
              <option value="quality_score">Score qualité</option>
              <option value="processing_time">Temps traitement</option>
            </select>
          </div>
          
          <button
            @click="toggleSortOrder"
            class="flex items-center gap-1 px-2 py-1 text-sm border border-gray-300 
                   dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            <svg 
              class="w-4 h-4 transform transition-transform"
              :class="{ 'rotate-180': filters.sort_order === 'asc' }"
              fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
            {{ filters.sort_order === 'desc' ? 'Desc' : 'Asc' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-8">
      <div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full mx-auto"></div>
      <p class="text-gray-600 dark:text-gray-400 mt-2">Chargement des traductions...</p>
    </div>

    <!-- Liste des traductions -->
    <div v-else-if="translations.length > 0" class="space-y-4">
      <div
        v-for="translation in translations"
        :key="translation.id"
        class="translation-card bg-white dark:bg-gray-800 rounded-lg border border-gray-200 
               dark:border-gray-600 p-6 hover:shadow-md transition-shadow"
      >
        <div class="flex items-start justify-between">
          <!-- Informations principales -->
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
              <h4 class="font-medium text-gray-900 dark:text-white">
                {{ getLanguageName(translation.target_language) }}
              </h4>
              <span :class="getStatusBadgeClass(translation.status)">
                {{ getStatusLabel(translation.status) }}
              </span>
              <span class="text-xs text-gray-500 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                {{ translation.provider_used }}
              </span>
            </div>

            <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">
              <div>Transcription: {{ translation.transcription_id }}</div>
              <div class="flex items-center gap-4 mt-1">
                <span>{{ translation.segments_count }} segments</span>
                <span>{{ formatDuration(translation.total_duration) }}</span>
                <span>Créé le {{ formatDate(translation.created_at) }}</span>
              </div>
            </div>

            <!-- Métriques -->
            <div class="flex items-center gap-6 text-sm">
              <!-- Score qualité -->
              <div v-if="translation.quality_score" class="flex items-center gap-1">
                <span class="text-gray-600 dark:text-gray-400">Qualité:</span>
                <div class="flex items-center gap-1">
                  <div class="w-12 h-2 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                    <div 
                      class="h-full bg-gradient-to-r from-red-400 via-yellow-400 to-green-400 rounded-full"
                      :style="{ width: `${translation.quality_score * 100}%` }"
                    ></div>
                  </div>
                  <span class="font-medium">{{ (translation.quality_score * 100).toFixed(1) }}%</span>
                </div>
              </div>

              <!-- Coût -->
              <div v-if="translation.actual_cost || translation.estimated_cost">
                <span class="text-gray-600 dark:text-gray-400">Coût:</span>
                <span class="font-medium">
                  ${{ (translation.actual_cost || translation.estimated_cost).toFixed(4) }}
                </span>
              </div>

              <!-- Temps de traitement -->
              <div v-if="translation.processing_time">
                <span class="text-gray-600 dark:text-gray-400">Traitement:</span>
                <span class="font-medium">{{ translation.processing_time.toFixed(1) }}s</span>
              </div>
            </div>

            <!-- Capacités spéciales -->
            <div v-if="hasSpecialCapabilities(translation)" class="flex items-center gap-2 mt-2">
              <span class="text-xs text-gray-500">Capacités:</span>
              <div class="flex gap-1">
                <span v-if="translation.has_word_timestamps" 
                      class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                  Timestamps mots
                </span>
                <span v-if="translation.has_emotional_context" 
                      class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded">
                  Émotions
                </span>
                <span v-if="translation.has_character_names" 
                      class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                  Personnages
                </span>
                <span v-if="translation.has_technical_terms" 
                      class="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded">
                  Termes tech
                </span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-2 ml-4">
            <!-- Bouton de statut en temps réel -->
            <button
              v-if="translation.status === 'processing'"
              @click="refreshTranslationStatus(translation.id)"
              class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
              title="Actualiser le statut"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
              </svg>
            </button>

            <!-- Téléchargement -->
            <div v-if="translation.status === 'completed'" class="relative">
              <button
                @click="toggleDownloadMenu(translation.id)"
                class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
                title="Télécharger"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
              </button>

              <!-- Menu de téléchargement -->
              <div
                v-if="openDownloadMenu === translation.id"
                class="absolute right-0 top-full mt-1 bg-white dark:bg-gray-700 border border-gray-200 
                       dark:border-gray-600 rounded-lg shadow-lg z-10 min-w-40"
              >
                <button
                  v-for="format in downloadFormats"
                  :key="format.value"
                  @click="downloadTranslation(translation.id, format.value)"
                  class="block w-full px-4 py-2 text-left text-sm hover:bg-gray-50 
                         dark:hover:bg-gray-600 first:rounded-t-lg last:rounded-b-lg"
                >
                  {{ format.label }}
                </button>
              </div>
            </div>

            <!-- Détails -->
            <button
              @click="$emit('view-details', translation.id)"
              class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
              title="Voir les détails"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- État vide -->
    <div v-else-if="!loading" class="text-center py-12">
      <div class="text-gray-400 mb-4">
        <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" 
                d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h4a1 1 0 110 2h-1v11a3 3 0 01-3 3H6a3 3 0 01-3-3V6H2a1 1 0 110-2h4zM9 3v1h6V3H9zm2 8a1 1 0 112 0v4a1 1 0 11-2 0v-4z"/>
        </svg>
      </div>
      <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
        Aucune traduction trouvée
      </h4>
      <p class="text-gray-600 dark:text-gray-400 mb-4">
        {{ hasFilters ? 'Aucune traduction ne correspond à vos critères.' : 'Vous n\'avez pas encore créé de traduction.' }}
      </p>
      <button
        @click="$emit('create-new')"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      >
        Créer ma première traduction
      </button>
    </div>

    <!-- Pagination -->
    <div v-if="pagination && pagination.total_pages > 1" class="pagination mt-8">
      <div class="flex items-center justify-between">
        <div class="text-sm text-gray-600 dark:text-gray-400">
          {{ pagination.total_items }} résultat(s) - Page {{ pagination.current_page }} sur {{ pagination.total_pages }}
        </div>
        
        <div class="flex items-center gap-2">
          <button
            @click="goToPage(pagination.current_page - 1)"
            :disabled="!pagination.has_previous_page"
            class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded
                   disabled:opacity-50 disabled:cursor-not-allowed
                   hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            Précédent
          </button>
          
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="goToPage(page)"
            :class="[
              'px-3 py-1 border rounded',
              page === pagination.current_page
                ? 'border-blue-500 bg-blue-50 text-blue-700'
                : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
            ]"
          >
            {{ page }}
          </button>
          
          <button
            @click="goToPage(pagination.current_page + 1)"
            :disabled="!pagination.has_next_page"
            class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded
                   disabled:opacity-50 disabled:cursor-not-allowed
                   hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            Suivant
          </button>
        </div>
      </div>
    </div>

    <!-- Statistiques utilisateur -->
    <div v-if="statistics" class="statistics mt-8 bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
      <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
        Statistiques de traduction
      </h4>
      
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div class="text-center">
          <div class="text-2xl font-bold text-blue-600">{{ statistics.total_translations }}</div>
          <div class="text-gray-600 dark:text-gray-400">Total</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-green-600">{{ statistics.success_rate }}%</div>
          <div class="text-gray-600 dark:text-gray-400">Taux succès</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-purple-600">${{ statistics.total_cost_usd.toFixed(3) }}</div>
          <div class="text-gray-600 dark:text-gray-400">Coût total</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-orange-600">
            {{ statistics.average_quality_score ? (statistics.average_quality_score * 100).toFixed(1) + '%' : 'N/A' }}
          </div>
          <div class="text-gray-600 dark:text-gray-400">Qualité moy.</div>
        </div>
      </div>
      
      <div class="mt-4 text-xs text-gray-500 text-center">
        Langue préférée: {{ getLanguageName(statistics.favorite_target_language) }} • 
        Service favori: {{ statistics.most_used_provider }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import { TranslationAPI, type GetTranslationsParams, type TranslationsListResponse } from '@/api/translations'

const emit = defineEmits<{
  'create-new': []
  'view-details': [translationId: string]
}>()

// État local
const loading = ref(false)
const translations = ref<any[]>([])
const pagination = ref<any>(null)
const statistics = ref<any>(null)
const openDownloadMenu = ref<string | null>(null)

// Filtres
const filters = reactive<GetTranslationsParams>({
  page: 1,
  limit: 10,
  target_language: '',
  provider: '',
  status: '',
  search: '',
  sort_by: 'created_at',
  sort_order: 'desc'
})

// Formats de téléchargement
const downloadFormats = [
  { value: 'json', label: 'JSON (natif)' },
  { value: 'srt', label: 'SRT (sous-titres)' },
  { value: 'vtt', label: 'WebVTT' },
  { value: 'txt', label: 'Texte simple' },
  { value: 'dubbing_json', label: 'JSON doublage' }
]

// Computed
const hasFilters = computed(() => {
  return filters.target_language || filters.provider || filters.status || filters.search
})

const visiblePages = computed(() => {
  if (!pagination.value) return []
  
  const current = pagination.value.current_page
  const total = pagination.value.total_pages
  const pages = []
  
  // Afficher 5 pages max autour de la page courante
  const start = Math.max(1, current - 2)
  const end = Math.min(total, current + 2)
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
})

// Méthodes utilitaires
const getLanguageName = (code: string | undefined) => {
  const languages: Record<string, string> = {
    fr: 'Français',
    es: 'Español', 
    de: 'Deutsch',
    it: 'Italiano',
    pt: 'Português',
    en: 'English',
    nl: 'Nederlands'
  }
  return languages[code || ''] || code || 'Inconnu'
}

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    pending: 'En attente',
    processing: 'En cours',
    completed: 'Terminé',
    failed: 'Échoué'
  }
  return labels[status] || status
}

const getStatusBadgeClass = (status: string) => {
  const classes: Record<string, string> = {
    pending: 'px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full',
    processing: 'px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full animate-pulse',
    completed: 'px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full',
    failed: 'px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full'
  }
  return classes[status] || 'px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full'
}

const formatDuration = (seconds: number) => {
  const minutes = Math.floor(seconds / 60)
  const remainingSeconds = Math.floor(seconds % 60)
  return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const hasSpecialCapabilities = (translation: any) => {
  return translation.has_word_timestamps || 
         translation.has_emotional_context || 
         translation.has_character_names || 
         translation.has_technical_terms
}

// Actions
const refreshList = async () => {
  loading.value = true
  try {
    const response = await TranslationAPI.getTranslations(filters)
    
    if (response.success) {
      const data = response.data as TranslationsListResponse
      translations.value = data.translations
      pagination.value = data.pagination
      statistics.value = data.statistics
    } else {
      console.error('Erreur chargement traductions:', response.error)
    }
  } catch (error: any) {
    console.error('Erreur chargement traductions:', error)
  } finally {
    loading.value = false
  }
}

const debouncedRefresh = (() => {
  let timeout: any
  return () => {
    clearTimeout(timeout)
    timeout = setTimeout(refreshList, 500)
  }
})()

const toggleSortOrder = () => {
  filters.sort_order = filters.sort_order === 'desc' ? 'asc' : 'desc'
  refreshList()
}

const goToPage = (page: number) => {
  if (page >= 1 && pagination.value && page <= pagination.value.total_pages) {
    filters.page = page
    refreshList()
  }
}

const toggleDownloadMenu = (translationId: string) => {
  openDownloadMenu.value = openDownloadMenu.value === translationId ? null : translationId
}

const downloadTranslation = async (translationId: string, format: string) => {
  try {
    const blob = await TranslationAPI.downloadTranslation(translationId, format as any)
    
    // Créer et déclencher le téléchargement
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.style.display = 'none'
    a.href = url
    a.download = `translation_${translationId}.${format === 'dubbing_json' ? 'json' : format}`
    
    document.body.appendChild(a)
    a.click()
    window.URL.revokeObjectURL(url)
    document.body.removeChild(a)
    
    openDownloadMenu.value = null
  } catch (error: any) {
    console.error('Erreur téléchargement:', error)
    // TODO: Afficher notification d'erreur
  }
}

const refreshTranslationStatus = async (translationId: string) => {
  try {
    const response = await TranslationAPI.getTranslationStatus(translationId)
    
    if (response.success) {
      // Mettre à jour la traduction dans la liste
      const index = translations.value.findIndex(t => t.id === translationId)
      if (index !== -1) {
        translations.value[index] = response.data
      }
    }
  } catch (error: any) {
    console.error('Erreur actualisation statut:', error)
  }
}

// Fermer le menu de téléchargement quand on clique ailleurs
const handleClickOutside = (event: Event) => {
  if (openDownloadMenu.value) {
    const target = event.target as Element
    if (!target.closest('.relative')) {
      openDownloadMenu.value = null
    }
  }
}

// Watchers
watch(() => filters.page, refreshList)

// Lifecycle
onMounted(() => {
  refreshList()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.translation-list {
  @apply max-w-6xl mx-auto;
}

.translation-card {
  @apply transition-all duration-200;
}

.translation-card:hover {
  @apply shadow-md scale-[1.02];
}

.filters-section {
  @apply backdrop-blur-sm;
}
</style>