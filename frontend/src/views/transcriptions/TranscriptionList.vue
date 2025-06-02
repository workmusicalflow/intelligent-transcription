<template>
  <div class="container-app section-padding">
    <!-- En-tête avec actions -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Mes transcriptions
        </h1>
        <p v-if="stats" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          {{ stats.total }} transcription{{ stats.total > 1 ? 's' : '' }} • 
          {{ stats.completed }} terminée{{ stats.completed > 1 ? 's' : '' }} • 
          {{ stats.processing }} en cours
        </p>
      </div>
      <router-link 
        to="/transcriptions/create"
        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md transition-colors flex items-center"
      >
        <span class="mr-2">➕</span>
        Nouvelle transcription
      </router-link>
    </div>

    <!-- Filtres et recherche -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Recherche -->
        <div class="md:col-span-2">
          <div class="relative">
            <input
              v-model="filters.search"
              @input="debouncedSearch"
              type="text"
              placeholder="Rechercher par nom de fichier ou contenu..."
              class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
            />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-400">🔍</span>
            </div>
          </div>
        </div>

        <!-- Filtre par langue -->
        <div>
          <select
            v-model="filters.language"
            @change="applyFilters"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="">Toutes les langues</option>
            <option v-for="lang in availableLanguages" :key="lang.language" :value="lang.language">
              {{ getLanguageName(lang.language) }} ({{ lang.count }})
            </option>
          </select>
        </div>

        <!-- Filtre par statut -->
        <div>
          <select
            v-model="filters.status"
            @change="applyFilters"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="">Tous les statuts</option>
            <option value="completed">Terminées</option>
            <option value="processing">En cours</option>
          </select>
        </div>
      </div>

      <!-- Options de tri -->
      <div class="flex flex-wrap items-center justify-between mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center space-x-4">
          <span class="text-sm text-gray-500 dark:text-gray-400">Trier par :</span>
          <select
            v-model="filters.sortBy"
            @change="applyFilters"
            class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="created_at">Date de création</option>
            <option value="file_name">Nom de fichier</option>
            <option value="language">Langue</option>
            <option value="duration">Durée</option>
            <option value="file_size">Taille</option>
          </select>
          <button
            @click="toggleSortOrder"
            class="px-2 py-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            :title="filters.sortOrder === 'desc' ? 'Décroissant' : 'Croissant'"
          >
            {{ filters.sortOrder === 'desc' ? '⬇️' : '⬆️' }}
          </button>
        </div>

        <div class="flex items-center space-x-2 mt-2 md:mt-0">
          <span class="text-sm text-gray-500 dark:text-gray-400">Par page :</span>
          <select
            v-model="pagination.limit"
            @change="applyFilters"
            class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Liste des transcriptions -->
    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
      <p class="text-gray-500 dark:text-gray-400 mt-2">Chargement des transcriptions...</p>
    </div>

    <div v-else-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
      <div class="flex items-center">
        <span class="text-red-500 mr-2">❌</span>
        <span class="text-red-700 dark:text-red-300">{{ error }}</span>
      </div>
    </div>

    <div v-else-if="transcriptions.length === 0" class="text-center py-12">
      <div class="text-6xl mb-4">📝</div>
      <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
        {{ hasFilters ? 'Aucune transcription trouvée' : 'Aucune transcription' }}
      </h3>
      <p class="text-gray-500 dark:text-gray-400 mb-6">
        {{ hasFilters 
          ? 'Essayez de modifier vos critères de recherche' 
          : 'Commencez par créer votre première transcription' 
        }}
      </p>
      <router-link 
        v-if="!hasFilters"
        to="/transcriptions/create"
        class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md transition-colors inline-flex items-center"
      >
        <span class="mr-2">➕</span>
        Créer ma première transcription
      </router-link>
      <button 
        v-else
        @click="clearFilters"
        class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-md transition-colors"
      >
        Effacer les filtres
      </button>
    </div>

    <div v-else class="space-y-4">
      <!-- Grille des transcriptions -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div
          v-for="transcription in transcriptions"
          :key="transcription.id"
          :class="[
            'bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-lg transition-shadow border border-gray-200 dark:border-gray-700',
            transcription.status === 'processing' ? 'processing-card' : '',
            transcription.status === 'completed' ? 'completed-card' : ''
          ]"
        >
          <div class="p-4">
            <!-- En-tête de la carte -->
            <div class="flex items-start justify-between mb-3">
              <div class="flex-1 min-w-0">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white truncate">
                  {{ transcription.fileName }}
                </h3>
                <div class="flex items-center space-x-2 mt-1">
                  <span 
                    :class="[
                      'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium status-transition',
                      transcription.status === 'completed'
                        ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400'
                    ]"
                  >
                    <span class="status-icon">
                      {{ transcription.status === 'completed' ? '✅' : '⏳' }}
                    </span>
                    {{ transcription.status === 'completed' ? ' Terminée' : ' En cours' }}
                  </span>
                  <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ getLanguageName(transcription.language) }}
                  </span>
                  <span 
                    v-if="transcription.sourceType === 'youtube'"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400"
                  >
                    🎥 YouTube
                  </span>
                </div>
              </div>
              
              <!-- Menu d'actions -->
              <div class="relative">
                <button
                  @click="toggleMenu(transcription.id)"
                  class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                  ⋯
                </button>
                <div
                  v-if="openMenuId === transcription.id"
                  class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-10"
                >
                  <router-link
                    :to="`/transcriptions/${transcription.id}`"
                    class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                    @click="closeMenu"
                  >
                    👁️ Voir les détails
                  </router-link>
                  <button
                    @click="downloadTranscription(transcription)"
                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                  >
                    💾 Télécharger
                  </button>
                  <button
                    @click="shareTranscription(transcription)"
                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                  >
                    🔗 Partager
                  </button>
                  <hr class="border-gray-200 dark:border-gray-700">
                  <button
                    @click="deleteTranscription(transcription)"
                    class="block w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20"
                  >
                    🗑️ Supprimer
                  </button>
                </div>
              </div>
            </div>

            <!-- Aperçu du contenu -->
            <p class="text-gray-600 dark:text-gray-300 text-sm mb-3 line-clamp-3">
              {{ transcription.previewText }}
            </p>

            <!-- Métadonnées -->
            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
              <div class="flex items-center space-x-4">
                <span>📅 {{ formatDate(transcription.createdAt) }}</span>
                <span v-if="transcription.duration">⏱️ {{ formatDuration(transcription.duration) }}</span>
                <span v-if="transcription.fileSize">💾 {{ formatFileSize(transcription.fileSize) }}</span>
              </div>
            </div>
          </div>

          <!-- Barre de progression pour les transcriptions en cours -->
          <div v-if="transcription.status === 'processing'" class="px-4 pb-4">
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
              <div class="bg-gradient-to-r from-yellow-400 to-orange-500 h-2 rounded-full relative">
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer-fast"></div>
              </div>
            </div>
            <div class="flex items-center justify-between mt-2">
              <p class="text-xs text-yellow-600 dark:text-yellow-400 flex items-center">
                <span class="animate-spin mr-1">⚡</span>
                Transcription en cours...
              </p>
              <div class="flex space-x-1">
                <div class="w-1 h-1 bg-yellow-500 rounded-full animate-bounce"></div>
                <div class="w-1 h-1 bg-yellow-500 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                <div class="w-1 h-1 bg-yellow-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.totalPages > 1" class="flex items-center justify-between mt-8">
        <div class="text-sm text-gray-500 dark:text-gray-400">
          Affichage {{ ((pagination.currentPage - 1) * pagination.limit) + 1 }} à 
          {{ Math.min(pagination.currentPage * pagination.limit, pagination.totalCount) }} 
          sur {{ pagination.totalCount }} transcriptions
        </div>
        
        <div class="flex items-center space-x-2">
          <button
            @click="goToPage(pagination.currentPage - 1)"
            :disabled="!pagination.hasPrev"
            class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300"
          >
            ← Précédent
          </button>
          
          <div class="flex space-x-1">
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="goToPage(page)"
              :class="[
                'px-3 py-2 text-sm border rounded-md',
                page === pagination.currentPage
                  ? 'bg-blue-500 text-white border-blue-500'
                  : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
              ]"
            >
              {{ page }}
            </button>
          </div>
          
          <button
            @click="goToPage(pagination.currentPage + 1)"
            :disabled="!pagination.hasNext"
            class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300"
          >
            Suivant →
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { TranscriptionAPI } from '@/api/transcriptions'
import { useUIStore } from '@/stores/ui'

const router = useRouter()
const uiStore = useUIStore()

// État des données
const transcriptions = ref<any[]>([])
const loading = ref(false)
const error = ref('')
const stats = ref<any>(null)
const availableLanguages = ref<any[]>([])
const openMenuId = ref<string | null>(null)

// Filtres et pagination
const filters = ref({
  search: '',
  language: '',
  status: '',
  sortBy: 'created_at',
  sortOrder: 'desc' as 'asc' | 'desc'
})

const pagination = ref({
  currentPage: 1,
  totalPages: 1,
  totalCount: 0,
  limit: 10,
  hasNext: false,
  hasPrev: false
})

// Fonctions utilitaires
const getLanguageName = (code: string): string => {
  const languages: Record<string, string> = {
    'fr': 'Français',
    'en': 'Anglais',
    'es': 'Espagnol',
    'de': 'Allemand',
    'it': 'Italien',
    'auto': 'Auto-détecté'
  }
  return languages[code] || code.toUpperCase()
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const formatDuration = (seconds: number): string => {
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = seconds % 60
  
  if (hours > 0) {
    return `${hours}h ${minutes}m`
  } else if (minutes > 0) {
    return `${minutes}m ${secs}s`
  } else {
    return `${secs}s`
  }
}

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// Pagination visible
const visiblePages = computed(() => {
  const total = pagination.value.totalPages
  const current = pagination.value.currentPage
  const pages: number[] = []
  
  let start = Math.max(1, current - 2)
  let end = Math.min(total, current + 2)
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
})

// Vérifier si des filtres sont appliqués
const hasFilters = computed(() => {
  return filters.value.search !== '' || 
         filters.value.language !== '' || 
         filters.value.status !== ''
})

// Chargement des transcriptions
const loadTranscriptions = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const params = {
      page: pagination.value.currentPage,
      limit: pagination.value.limit,
      sort: filters.value.sortBy,
      order: filters.value.sortOrder,
      ...(filters.value.search && { search: filters.value.search }),
      ...(filters.value.language && { language: filters.value.language }),
      ...(filters.value.status && { status: filters.value.status })
    }
    
    const response = await TranscriptionAPI.listTranscriptions(params)
    
    if (response.success && response.data) {
      transcriptions.value = response.data.transcriptions
      pagination.value = response.data.pagination
      stats.value = response.data.stats
      availableLanguages.value = response.data.availableLanguages
    } else {
      throw new Error(response.message || 'Erreur lors du chargement des transcriptions')
    }
    
  } catch (err: any) {
    error.value = err.message
    console.error('Erreur lors du chargement des transcriptions:', err)
    
    // Afficher notification d'erreur
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur de chargement',
      message: err.message || 'Impossible de charger les transcriptions'
    })
  } finally {
    loading.value = false
  }
}

// Recherche avec délai
let searchTimeout: NodeJS.Timeout
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    pagination.value.currentPage = 1
    loadTranscriptions()
  }, 500)
}

// Application des filtres
const applyFilters = () => {
  pagination.value.currentPage = 1
  loadTranscriptions()
}

// Toggle du tri
const toggleSortOrder = () => {
  filters.value.sortOrder = filters.value.sortOrder === 'desc' ? 'asc' : 'desc'
  applyFilters()
}

// Effacer les filtres
const clearFilters = () => {
  filters.value.search = ''
  filters.value.language = ''
  filters.value.status = ''
  filters.value.sortBy = 'created_at'
  filters.value.sortOrder = 'desc'
  pagination.value.currentPage = 1
  loadTranscriptions()
}

// Navigation pagination
const goToPage = (page: number) => {
  if (page >= 1 && page <= pagination.value.totalPages) {
    pagination.value.currentPage = page
    loadTranscriptions()
  }
}

// Gestion du menu
const toggleMenu = (id: string) => {
  openMenuId.value = openMenuId.value === id ? null : id
}

const closeMenu = () => {
  openMenuId.value = null
}

// Actions sur les transcriptions
const downloadTranscription = (transcription: any) => {
  console.log('Téléchargement:', transcription.fileName)
  // TODO: Implémenter le téléchargement
  closeMenu()
}

const shareTranscription = (transcription: any) => {
  console.log('Partage:', transcription.fileName)
  // TODO: Implémenter le partage
  closeMenu()
}

const deleteTranscription = (transcription: any) => {
  if (confirm(`Êtes-vous sûr de vouloir supprimer "${transcription.fileName}" ?`)) {
    console.log('Suppression:', transcription.fileName)
    // TODO: Implémenter la suppression
  }
  closeMenu()
}

// Fermer le menu quand on clique ailleurs
const handleClickOutside = (event: Event) => {
  const target = event.target as HTMLElement
  if (!target.closest('.relative')) {
    closeMenu()
  }
}

// Variables pour le polling
const pollingInterval = ref<NodeJS.Timeout | null>(null)
const isPolling = ref(false)

// Fonction de polling pour les transcriptions en cours
const startPolling = () => {
  if (isPolling.value) return
  
  // Vérifier s'il y a des transcriptions en cours
  const hasProcessing = transcriptions.value.some(t => t.status === 'processing')
  if (!hasProcessing) return
  
  isPolling.value = true
  pollingInterval.value = setInterval(async () => {
    try {
      // Recharger la liste silencieusement (sans loader)
      const response = await TranscriptionAPI.listTranscriptions({
        page: pagination.value.currentPage,
        limit: pagination.value.limit,
        search: filters.value.search,
        language: filters.value.language,
        status: filters.value.status,
        sort: filters.value.sortBy,
        order: filters.value.sortOrder
      })
      
      if (response.success && response.data) {
        const oldTranscriptions = [...transcriptions.value]
        transcriptions.value = response.data.transcriptions
        
        // Vérifier s'il y a des changements de statut
        oldTranscriptions.forEach(oldT => {
          const newT = transcriptions.value.find(t => t.id === oldT.id)
          if (newT && oldT.status !== newT.status) {
            console.log('🔄 Transcription mise à jour:', oldT.id, oldT.status, '→', newT.status)
            
            if (newT.status === 'completed') {
              uiStore.showNotification({
                type: 'success',
                title: 'Transcription terminée',
                message: `"${newT.fileName}" a été traitée avec succès.`,
                duration: 4000
              })
            }
          }
        })
        
        // Arrêter le polling s'il n'y a plus de transcriptions en cours
        const stillProcessing = transcriptions.value.some(t => t.status === 'processing')
        if (!stillProcessing) {
          stopPolling()
        }
      }
    } catch (err) {
      console.error('Erreur lors du polling de la liste:', err)
    }
  }, 5000) // Vérifier toutes les 5 secondes
}

const stopPolling = () => {
  if (pollingInterval.value) {
    clearInterval(pollingInterval.value)
    pollingInterval.value = null
  }
  isPolling.value = false
}

// Watcher pour démarrer le polling quand il y a des transcriptions en cours
watch(() => transcriptions.value, (newTranscriptions) => {
  const hasProcessing = newTranscriptions.some(t => t.status === 'processing')
  if (hasProcessing && !isPolling.value) {
    console.log('🚀 Démarrage du polling - transcriptions en cours détectées')
    startPolling()
  } else if (!hasProcessing && isPolling.value) {
    console.log('✅ Arrêt du polling - plus de transcriptions en cours')
    stopPolling()
  }
}, { deep: true })

// Lifecycle
onMounted(() => {
  loadTranscriptions()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  stopPolling()
  document.removeEventListener('click', handleClickOutside)
})

// Nettoyage
const cleanup = () => {
  document.removeEventListener('click', handleClickOutside)
}

// Nettoyage à la destruction du composant
watch(() => router.currentRoute.value, () => {
  cleanup()
})
</script>

<script lang="ts">
export default {
  name: 'TranscriptionList'
}
</script>

<style scoped>
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Animation shimmer rapide pour les barres de progression */
@keyframes shimmer-fast {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.animate-shimmer-fast {
  animation: shimmer-fast 1.5s infinite;
}

/* Animation d'apparition pour les cartes de transcription */
.grid > div {
  animation: fadeInScale 0.5s ease-out;
  animation-fill-mode: both;
}

.grid > div:nth-child(1) { animation-delay: 0.1s; }
.grid > div:nth-child(2) { animation-delay: 0.2s; }
.grid > div:nth-child(3) { animation-delay: 0.3s; }
.grid > div:nth-child(4) { animation-delay: 0.4s; }
.grid > div:nth-child(5) { animation-delay: 0.5s; }
.grid > div:nth-child(6) { animation-delay: 0.6s; }

@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: translateY(1rem) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Animation pour les changements de statut */
.status-transition {
  transition: all 0.3s ease-in-out;
}

/* Animation de pulsation pour les transcriptions en cours */
.processing-card {
  position: relative;
  overflow: hidden;
}

.processing-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
  animation: processingGlow 2s infinite;
}

@keyframes processingGlow {
  0% {
    left: -100%;
  }
  100% {
    left: 100%;
  }
}

/* Animation de completion */
@keyframes completionPulse {
  0%, 100% {
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4);
  }
  50% {
    box-shadow: 0 0 0 8px rgba(34, 197, 94, 0);
  }
}

.completed-card {
  animation: completionPulse 1s ease-out;
}

/* Amélioration des transitions de survol */
.grid > div:hover {
  transform: translateY(-2px);
  transition: transform 0.2s ease-out;
}

/* Animation des icônes de statut */
.status-icon {
  transition: transform 0.2s ease-in-out;
}

.status-icon:hover {
  transform: scale(1.1);
}
</style>
