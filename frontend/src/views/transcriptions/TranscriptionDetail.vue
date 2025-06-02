<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Loading state -->
    <div v-if="loading" class="flex items-center justify-center min-h-screen" data-testid="loading">
      <div class="text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        <p class="text-gray-500 dark:text-gray-400 mt-4">Chargement de la transcription...</p>
      </div>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="container-app section-padding" data-testid="error">
      <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6 text-center">
        <div class="text-red-500 text-4xl mb-4">❌</div>
        <h2 class="text-xl font-semibold text-red-800 dark:text-red-300 mb-2">Erreur</h2>
        <p class="text-red-600 dark:text-red-400 mb-4">{{ error }}</p>
        <button
          @click="$router.push('/transcriptions')"
          class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md transition-colors"
        >
          Retour à la liste
        </button>
      </div>
    </div>

    <!-- Main content -->
    <div v-else-if="transcription" class="flex flex-col lg:flex-row min-h-screen">
      <!-- Sidebar with controls and info -->
      <div class="w-full lg:w-80 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
        <div class="p-6 space-y-6">
          <!-- Header with back button -->
          <div class="flex items-center justify-between">
            <button
              @click="$router.push('/transcriptions')"
              class="flex items-center text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
              <span class="mr-2">←</span>
              Retour
            </button>
            <div class="relative">
              <button
                @click="showActionsMenu = !showActionsMenu"
                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700"
              >
                ⋯
              </button>
              <div
                v-if="showActionsMenu"
                class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-10"
              >
                <button
                  v-for="action in actionsMenu"
                  :key="action.key"
                  @click="executeAction(action.key)"
                  :class="[
                    'block w-full text-left px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700',
                    action.key === 'delete' ? 'text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300'
                  ]"
                >
                  {{ action.icon }} {{ action.label }}
                </button>
              </div>
            </div>
          </div>

          <!-- Transcription info -->
          <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-2 break-words">
              {{ transcription.fileName }}
            </h1>
            <div class="flex items-center space-x-2 mb-4">
              <span 
                :class="[
                  'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                  transcription.status === 'completed'
                    ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                    : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400'
                ]"
              >
                {{ transcription.status === 'completed' ? '✅ Terminée' : '⏳ En cours' }}
              </span>
              <span 
                v-if="transcription.sourceType === 'youtube'"
                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400"
              >
                🎥 YouTube
              </span>
            </div>
          </div>

          <!-- Media player (if available) -->
          <div v-if="audioUrl && transcription.sourceType === 'file'" class="space-y-4">
            <h3 class="font-medium text-gray-900 dark:text-white">Lecture audio</h3>
            <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4">
              <audio
                ref="audioPlayer"
                :src="audioUrl"
                controls
                class="w-full mb-3"
                @timeupdate="onTimeUpdate"
                @loadedmetadata="onMediaLoaded"
                @play="isPlaying = true"
                @pause="isPlaying = false"
              ></audio>
              
              <!-- Playback controls -->
              <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                <span>{{ formatTime(currentTime) }} / {{ formatTime(totalDuration) }}</span>
                <div class="flex items-center space-x-2">
                  <label class="text-xs">Vitesse:</label>
                  <select
                    v-model="playbackRate"
                    @change="updatePlaybackRate"
                    class="text-xs border border-gray-300 dark:border-gray-600 rounded px-1 py-0.5 bg-white dark:bg-gray-800"
                  >
                    <option value="0.5">0.5x</option>
                    <option value="0.75">0.75x</option>
                    <option value="1">1x</option>
                    <option value="1.25">1.25x</option>
                    <option value="1.5">1.5x</option>
                    <option value="2">2x</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- YouTube link (if YouTube source) -->
          <div v-else-if="transcription.youtubeUrl" class="space-y-4">
            <h3 class="font-medium text-gray-900 dark:text-white">Source YouTube</h3>
            <a
              :href="transcription.youtubeUrl"
              target="_blank"
              class="block bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors"
            >
              <div class="flex items-center">
                <div class="text-red-500 text-2xl mr-3">🎥</div>
                <div>
                  <div class="font-medium text-red-800 dark:text-red-300">Voir sur YouTube</div>
                  <div class="text-sm text-red-600 dark:text-red-400">Ouvrir dans un nouvel onglet</div>
                </div>
              </div>
            </a>
          </div>

          <!-- Statistics -->
          <div v-if="textStats" class="space-y-4">
            <h3 class="font-medium text-gray-900 dark:text-white">Statistiques</h3>
            <div class="grid grid-cols-2 gap-3">
              <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 text-center">
                <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ textStats.wordCount }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Mots</div>
              </div>
              <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 text-center">
                <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ formatDuration(transcription.duration) }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Durée</div>
              </div>
              <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 text-center">
                <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ textStats.estimatedReadingTime }}min</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Lecture</div>
              </div>
              <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 text-center">
                <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ getLanguageName(transcription.language) }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Langue</div>
              </div>
            </div>
          </div>

          <!-- Export options -->
          <div class="space-y-4">
            <h3 class="font-medium text-gray-900 dark:text-white">Export</h3>
            <div class="grid grid-cols-2 gap-2">
              <button
                v-for="format in exportFormats"
                :key="format.key"
                @click="exportTranscription(format.key)"
                :disabled="exportLoading === format.key"
                class="flex items-center justify-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
                :data-testid="`export-${format.key}`"
              >
                <span v-if="exportLoading === format.key" class="animate-spin mr-2">⏳</span>
                {{ format.icon }} {{ format.label }}
              </button>
            </div>
          </div>

          <!-- Metadata -->
          <div class="space-y-4">
            <h3 class="font-medium text-gray-900 dark:text-white">Informations</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">Créé le:</span>
                <span class="text-gray-900 dark:text-white">{{ formatDate(transcription.createdAt) }}</span>
              </div>
              <div v-if="transcription.fileSize" class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">Taille:</span>
                <span class="text-gray-900 dark:text-white">{{ formatFileSize(transcription.fileSize) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">Format:</span>
                <span class="text-gray-900 dark:text-white">{{ transcription.sourceType === 'youtube' ? 'YouTube' : 'Fichier' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main content area -->
      <div class="flex-1 flex flex-col">
        <!-- Toolbar -->
        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4">
          <div class="flex items-center justify-between">
            <!-- View mode toggle -->
            <div class="flex items-center space-x-4">
              <div class="flex items-center space-x-2">
                <button
                  @click="viewMode = 'read'"
                  :class="[
                    'px-3 py-1 rounded-md text-sm transition-colors',
                    viewMode === 'read'
                      ? 'bg-blue-500 text-white'
                      : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                  ]"
                >
                  👁️ Lecture
                </button>
                <button
                  @click="viewMode = 'edit'"
                  :class="[
                    'px-3 py-1 rounded-md text-sm transition-colors',
                    viewMode === 'edit'
                      ? 'bg-blue-500 text-white'
                      : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                  ]"
                  data-testid="edit-mode-btn"
                >
                  ✏️ Édition
                </button>
                <button
                  v-if="segments && segments.length > 0"
                  @click="viewMode = 'segments'"
                  :class="[
                    'px-3 py-1 rounded-md text-sm transition-colors',
                    viewMode === 'segments'
                      ? 'bg-blue-500 text-white'
                      : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                  ]"
                  data-testid="segments-mode-btn"
                >
                  🎯 Segments
                </button>
              </div>
            </div>

            <!-- Text controls -->
            <div class="flex items-center space-x-4">
              <!-- Font size -->
              <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Taille:</span>
                <button
                  @click="adjustFontSize(-1)"
                  class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                  data-testid="decrease-font"
                >
                  A-
                </button>
                <button
                  @click="adjustFontSize(1)"
                  class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                  data-testid="increase-font"
                >
                  A+
                </button>
              </div>

              <!-- Search -->
              <div class="relative">
                <input
                  v-model="searchTerm"
                  @keyup.enter="searchNext"
                  type="text"
                  placeholder="Rechercher..."
                  class="w-64 pl-8 pr-4 py-1 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                />
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-400 text-sm">🔍</span>
                </div>
                <div v-if="searchTerm" class="absolute inset-y-0 right-0 pr-2 flex items-center">
                  <span class="text-xs text-gray-500">{{ searchResults.current }}/{{ searchResults.total }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Content area -->
        <div class="flex-1 overflow-auto">
          <!-- Reading mode -->
          <div v-if="viewMode === 'read'" class="p-8" data-testid="read-mode">
            <!-- Loading state for text content -->
            <TranscriptionLoader 
              v-if="transcription?.status === 'processing'" 
              :show-steps="true"
              :steps="transcriptionSteps"
            />
            
            <!-- Text content with fade-in animation -->
            <transition
              name="text-reveal"
              enter-active-class="transition-all duration-1000 ease-out"
              enter-from-class="opacity-0 transform translate-y-8 scale-95"
              enter-to-class="opacity-100 transform translate-y-0 scale-100"
            >
              <div
                v-if="transcription?.status === 'completed' && transcription?.text"
                ref="textContainer"
                class="prose prose-lg max-w-none dark:prose-invert"
                :style="{ fontSize: fontSize + 'px', lineHeight: '1.8' }"
                v-html="highlightedText"
              ></div>
            </transition>
            
            <!-- Empty state for no text yet -->
            <div v-if="!transcription?.text && transcription?.status !== 'processing'" class="text-center py-12">
              <div class="text-6xl mb-4 opacity-50">📝</div>
              <h3 class="text-lg font-medium text-gray-500 dark:text-gray-400 mb-2">
                Aucun texte disponible
              </h3>
              <p class="text-gray-400 dark:text-gray-500">
                La transcription n'a pas encore été générée
              </p>
            </div>
          </div>

          <!-- Edit mode -->
          <div v-else-if="viewMode === 'edit'" class="p-8" data-testid="edit-mode">
            <div class="mb-4 flex items-center justify-between">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">Édition du texte</h3>
              <div class="space-x-2">
                <button
                  @click="saveChanges"
                  :disabled="!hasChanges || saving"
                  class="bg-green-500 hover:bg-green-600 disabled:bg-gray-300 text-white px-4 py-2 rounded-md text-sm transition-colors"
                  data-testid="save-changes"
                >
                  {{ saving ? '⏳ Enregistrement...' : '💾 Enregistrer' }}
                </button>
                <button
                  @click="cancelEdit"
                  class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm transition-colors"
                >
                  Annuler
                </button>
              </div>
            </div>
            <textarea
              v-model="editText"
              class="w-full h-96 p-4 border border-gray-300 dark:border-gray-600 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
              :style="{ fontSize: fontSize + 'px', lineHeight: '1.8' }"
            ></textarea>
          </div>

          <!-- Segments mode -->
          <div v-else-if="viewMode === 'segments' && segments" class="p-8" data-testid="segments-mode">
            <div class="mb-6">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Navigation par segments</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Cliquez sur un segment pour naviguer dans l'audio (timestamps estimés)
              </p>
            </div>
            
            <div class="space-y-3">
              <div
                v-for="(segment, index) in segments"
                :key="segment.id"
                @click="seekToSegment(segment)"
                :class="[
                  'p-4 border rounded-lg cursor-pointer transition-all',
                  currentSegment === segment.id
                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                ]"
              >
                <div class="flex items-start justify-between mb-2">
                  <span class="text-sm font-medium text-blue-600 dark:text-blue-400">
                    Segment {{ index + 1 }}
                  </span>
                  <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ formatTime(segment.startTime) }} - {{ formatTime(segment.endTime) }}
                    {{ segment.isEstimated ? '(estimé)' : '' }}
                  </span>
                </div>
                <p class="text-gray-900 dark:text-white leading-relaxed">{{ segment.text }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Actions menu overlay -->
  <div
    v-if="showActionsMenu"
    @click="showActionsMenu = false"
    class="fixed inset-0 z-0"
  ></div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { TranscriptionAPI } from '@/api/transcriptions'
import { useUIStore } from '@/stores/ui'
import TranscriptionLoader from '@/components/ui/TranscriptionLoader.vue'

const route = useRoute()
const router = useRouter()
const uiStore = useUIStore()

// Types
interface TranscriptionSegment {
  id: number
  text: string
  startTime: number
  endTime: number
  wordCount: number
  isEstimated: boolean
}

interface Transcription {
  id: string
  fileName: string
  filePath: string | null
  text: string
  language: string
  originalText: string | null
  sourceType: 'file' | 'youtube'
  youtubeUrl: string | null
  youtubeId: string | null
  createdAt: string
  fileSize: number | null
  duration: number | null
  isProcessed: boolean
  status: string
  preprocessedPath: string | null
}

interface TextStats {
  characterCount: number
  wordCount: number
  paragraphCount: number
  estimatedReadingTime: number
}

// État principal
const loading = ref(true)
const error = ref('')
const transcription = ref<Transcription | null>(null)
const textStats = ref<TextStats | null>(null)
const segments = ref<TranscriptionSegment[]>([])
const audioUrl = ref<string | null>(null)

// Interface
const viewMode = ref<'read' | 'edit' | 'segments'>('read')
const fontSize = ref(16)
const showActionsMenu = ref(false)

// Lecture audio
const audioPlayer = ref<HTMLAudioElement>()
const isPlaying = ref(false)
const currentTime = ref(0)
const totalDuration = ref(0)
const playbackRate = ref(1)
const currentSegment = ref<number | null>(null)

// Édition
const editText = ref('')
const originalText = ref('')
const hasChanges = ref(false)
const saving = ref(false)

// Recherche
const searchTerm = ref('')
const searchResults = ref({ current: 0, total: 0 })
const textContainer = ref<HTMLElement>()

// Export
const exportLoading = ref<string | null>(null)

// Configuration
const actionsMenu = [
  { key: 'share', icon: '🔗', label: 'Partager' },
  { key: 'duplicate', icon: '📋', label: 'Dupliquer' },
  { key: 'download', icon: '💾', label: 'Télécharger' },
  { key: 'delete', icon: '🗑️', label: 'Supprimer' }
]

const exportFormats = [
  { key: 'txt', icon: '📄', label: 'TXT' },
  { key: 'pdf', icon: '📑', label: 'PDF' },
  { key: 'docx', icon: '📘', label: 'DOCX' },
  { key: 'json', icon: '⚙️', label: 'JSON' }
]

// Étapes de transcription pour l'animation
const transcriptionSteps = ref([
  { label: 'Préparation', completed: true, active: false },
  { label: 'Analyse IA', completed: false, active: true },
  { label: 'Finalisation', completed: false, active: false }
])

// Computed
const highlightedText = computed(() => {
  if (!transcription.value) return ''
  
  let text = transcription.value.text
  
  // Ajouter la recherche
  if (searchTerm.value) {
    const regex = new RegExp(`(${searchTerm.value})`, 'gi')
    text = text.replace(regex, '<mark class="bg-yellow-200 dark:bg-yellow-600">$1</mark>')
  }
  
  // Formatage de base
  text = text.replace(/\n\n/g, '</p><p class="mb-4">')
  text = text.replace(/\n/g, '<br>')
  
  return `<p class="mb-4">${text}</p>`
})

// Fonctions utilitaires
const getLanguageName = (code: string): string => {
  if (!code || typeof code !== 'string') {
    return 'N/A'
  }
  
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
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatDuration = (seconds: number | null): string => {
  if (!seconds) return 'N/A'
  
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

const formatTime = (seconds: number): string => {
  const minutes = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)
  return `${minutes}:${secs.toString().padStart(2, '0')}`
}

// Fonctions de l'interface
const adjustFontSize = (delta: number) => {
  fontSize.value = Math.max(12, Math.min(24, fontSize.value + delta))
}

const searchNext = () => {
  // TODO: Implémenter la recherche dans le texte
  console.log('Recherche:', searchTerm.value)
}

// Fonctions audio
const onTimeUpdate = () => {
  if (audioPlayer.value) {
    currentTime.value = audioPlayer.value.currentTime
    
    // Trouver le segment actuel
    if (segments.value.length > 0) {
      const current = segments.value.find(s => 
        currentTime.value >= s.startTime && currentTime.value <= s.endTime
      )
      if (current) {
        currentSegment.value = current.id
      }
    }
  }
}

const onMediaLoaded = () => {
  if (audioPlayer.value) {
    totalDuration.value = audioPlayer.value.duration
  }
}

const updatePlaybackRate = () => {
  if (audioPlayer.value) {
    audioPlayer.value.playbackRate = playbackRate.value
  }
}

const seekToSegment = (segment: TranscriptionSegment) => {
  if (audioPlayer.value) {
    audioPlayer.value.currentTime = segment.startTime
    currentSegment.value = segment.id
    if (!isPlaying.value) {
      audioPlayer.value.play()
    }
  }
}

// Fonctions d'édition
const saveChanges = async () => {
  saving.value = true
  try {
    // TODO: Implémenter la sauvegarde
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    if (transcription.value) {
      transcription.value.text = editText.value
      originalText.value = editText.value
      hasChanges.value = false
      viewMode.value = 'read'
    }
  } catch (err: any) {
    error.value = 'Erreur lors de la sauvegarde'
  } finally {
    saving.value = false
  }
}

const cancelEdit = () => {
  editText.value = originalText.value
  hasChanges.value = false
  viewMode.value = 'read'
}

// Fonctions d'export
const exportTranscription = async (format: string) => {
  if (!transcription.value) return
  
  exportLoading.value = format
  try {
    // TODO: Implémenter l'export réel
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Simulation de téléchargement
    const content = transcription.value.text
    const blob = new Blob([content], { type: 'text/plain' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `${transcription.value.fileName}.${format}`
    a.click()
    URL.revokeObjectURL(url)
    
  } catch (err: any) {
    error.value = `Erreur lors de l'export ${format}`
  } finally {
    exportLoading.value = null
  }
}

// Actions du menu
const executeAction = (action: string) => {
  showActionsMenu.value = false
  
  switch (action) {
    case 'share':
      // TODO: Implémenter le partage
      console.log('Partage de la transcription')
      break
    case 'duplicate':
      // TODO: Implémenter la duplication
      console.log('Duplication de la transcription')
      break
    case 'download':
      exportTranscription('txt')
      break
    case 'delete':
      if (confirm('Êtes-vous sûr de vouloir supprimer cette transcription ?')) {
        // TODO: Implémenter la suppression
        console.log('Suppression de la transcription')
        router.push('/transcriptions')
      }
      break
  }
}

// Chargement des données
const loadTranscription = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const transcriptionId = route.params.id as string
    
    const response = await TranscriptionAPI.getTranscriptionDetails(transcriptionId)
    
    if (response.success && response.data) {
      transcription.value = response.data.transcription
      textStats.value = response.data.textStats
      segments.value = response.data.segments
      audioUrl.value = response.data.audioUrl
      
      // Initialiser l'édition
      if (transcription.value) {
        originalText.value = transcription.value.text
        editText.value = transcription.value.text
      }
      
    } else {
      throw new Error(response.message || 'Erreur lors du chargement de la transcription')
    }
    
  } catch (err: any) {
    error.value = err.message
    console.error('Erreur lors du chargement de la transcription:', err)
    
    // Afficher notification d'erreur
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur de chargement',
      message: err.message || 'Impossible de charger la transcription'
    })
  } finally {
    loading.value = false
  }
}

// Variables pour le polling
const pollingInterval = ref<NodeJS.Timeout | null>(null)
const isPolling = ref(false)

// Fonction de polling pour les transcriptions en cours
const startPolling = () => {
  if (isPolling.value) return
  
  isPolling.value = true
  pollingInterval.value = setInterval(async () => {
    try {
      const transcriptionId = route.params.id as string
      const response = await TranscriptionAPI.getTranscriptionDetails(transcriptionId)
      
      if (response.success && response.data) {
        const newTranscription = response.data.transcription
        
        // Vérifier si le statut a changé
        if (transcription.value && newTranscription.status !== transcription.value.status) {
          console.log('🔄 Mise à jour du statut:', transcription.value.status, '→', newTranscription.status)
          
          // Afficher une notification
          if (newTranscription.status === 'completed') {
            uiStore.showNotification({
              type: 'success',
              title: 'Transcription terminée !',
              message: 'Votre transcription a été traitée avec succès.',
              duration: 5000
            })
          } else if (newTranscription.status === 'failed') {
            uiStore.showNotification({
              type: 'error',
              title: 'Erreur de transcription',
              message: 'Une erreur est survenue lors du traitement.',
              duration: 5000
            })
          }
          
          // Mettre à jour toutes les données
          transcription.value = newTranscription
          textStats.value = response.data.textStats
          segments.value = response.data.segments
          
          // Mettre à jour le texte d'édition si la transcription est terminée
          if (newTranscription.status === 'completed' && newTranscription.text) {
            originalText.value = newTranscription.text
            if (!hasChanges.value) {
              editText.value = newTranscription.text
            }
          }
          
          // Arrêter le polling si terminé ou échoué
          if (newTranscription.status === 'completed' || newTranscription.status === 'failed') {
            stopPolling()
          }
        }
      }
    } catch (err) {
      console.error('Erreur lors du polling:', err)
      // Continuer le polling même en cas d'erreur
    }
  }, 3000) // Vérifier toutes les 3 secondes
}

const stopPolling = () => {
  if (pollingInterval.value) {
    clearInterval(pollingInterval.value)
    pollingInterval.value = null
  }
  isPolling.value = false
}

// Watchers
watch(() => editText.value, (newValue) => {
  hasChanges.value = newValue !== originalText.value
})

// Fonction pour mettre à jour les étapes de progression
const updateTranscriptionSteps = (status: string) => {
  if (status === 'processing') {
    // Simulation de progression des étapes pendant le traitement
    transcriptionSteps.value = [
      { label: 'Préparation', completed: true, active: false },
      { label: 'Analyse IA', completed: false, active: true },
      { label: 'Finalisation', completed: false, active: false }
    ]
    
    // Simuler la progression toutes les 10 secondes
    setTimeout(() => {
      if (transcription.value?.status === 'processing') {
        transcriptionSteps.value = [
          { label: 'Préparation', completed: true, active: false },
          { label: 'Analyse IA', completed: true, active: false },
          { label: 'Finalisation', completed: false, active: true }
        ]
      }
    }, 10000)
  } else if (status === 'completed') {
    transcriptionSteps.value = [
      { label: 'Préparation', completed: true, active: false },
      { label: 'Analyse IA', completed: true, active: false },
      { label: 'Finalisation', completed: true, active: false }
    ]
  }
}

// Watcher pour démarrer/arrêter le polling selon le statut
watch(() => transcription.value?.status, (newStatus) => {
  if (newStatus === 'processing') {
    console.log('🚀 Démarrage du polling pour transcription en cours')
    updateTranscriptionSteps(newStatus)
    startPolling()
  } else if (newStatus === 'completed' || newStatus === 'failed') {
    console.log('✅ Arrêt du polling - transcription terminée')
    updateTranscriptionSteps(newStatus)
    stopPolling()
  }
}, { immediate: true })

// Lifecycle
onMounted(() => {
  loadTranscription()
})

onUnmounted(() => {
  stopPolling()
})
</script>

<script lang="ts">
export default {
  name: 'TranscriptionDetail'
}
</script>

<style scoped>
.prose {
  max-width: none;
}

.prose p {
  margin-bottom: 1rem;
}

.prose mark {
  padding: 0.1em 0.2em;
  border-radius: 0.2em;
}

/* Smooth scrolling for segment navigation */
html {
  scroll-behavior: smooth;
}

/* Custom scrollbar */
.overflow-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-auto::-webkit-scrollbar-track {
  background: transparent;
}

.overflow-auto::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 3px;
}

.overflow-auto::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

.dark .overflow-auto::-webkit-scrollbar-thumb {
  background: #4b5563;
}

.dark .overflow-auto::-webkit-scrollbar-thumb:hover {
  background: #6b7280;
}

/* Animation pour la révélation du texte */
.text-reveal-enter-active {
  transition: all 1s ease-out;
}

.text-reveal-enter-from {
  opacity: 0;
  transform: translateY(2rem) scale(0.95);
}

.text-reveal-enter-to {
  opacity: 1;
  transform: translateY(0) scale(1);
}

/* Animation de l'effet shimmer */
@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.animate-shimmer {
  animation: shimmer 2s infinite;
}

/* Animation de typing */
@keyframes typing-cursor {
  0%, 50% {
    opacity: 1;
  }
  51%, 100% {
    opacity: 0;
  }
}

.typing-cursor {
  animation: typing-cursor 1s infinite;
  color: #3b82f6;
  font-weight: bold;
}

.typing-animation {
  font-family: 'Courier New', monospace;
}

.typing-text {
  position: relative;
}

/* Animation de pulsation douce pour les éléments de chargement */
@keyframes soft-pulse {
  0%, 100% {
    opacity: 0.4;
  }
  50% {
    opacity: 0.8;
  }
}

.animate-soft-pulse {
  animation: soft-pulse 2s ease-in-out infinite;
}

/* Transition pour l'apparition progressive des paragraphes */
.prose p {
  animation: fadeInUp 0.6s ease-out;
  animation-fill-mode: both;
}

.prose p:nth-child(1) { animation-delay: 0.1s; }
.prose p:nth-child(2) { animation-delay: 0.2s; }
.prose p:nth-child(3) { animation-delay: 0.3s; }
.prose p:nth-child(4) { animation-delay: 0.4s; }
.prose p:nth-child(5) { animation-delay: 0.5s; }

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(1rem);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Animation pour les skeleton loaders */
@keyframes skeleton-loading {
  0% {
    background-position: -200px 0;
  }
  100% {
    background-position: calc(200px + 100%) 0;
  }
}

.animate-pulse {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200px 100%;
  animation: skeleton-loading 1.5s infinite;
}

.dark .animate-pulse {
  background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
  background-size: 200px 100%;
  animation: skeleton-loading 1.5s infinite;
}
</style>
