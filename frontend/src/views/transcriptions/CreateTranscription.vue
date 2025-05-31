<template>
  <div class="container-app section-padding">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
      Nouvelle transcription
    </h1>

    <!-- Options de transcription -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
      <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
        <nav class="flex space-x-8">
          <button
            @click="activeTab = 'upload'"
            :class="[
              'py-2 px-1 border-b-2 font-medium text-sm transition-colors',
              activeTab === 'upload'
                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
            ]"
          >
            📁 Upload de fichier
          </button>
          <button
            @click="activeTab = 'youtube'"
            :class="[
              'py-2 px-1 border-b-2 font-medium text-sm transition-colors',
              activeTab === 'youtube'
                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
            ]"
          >
            🎥 Lien YouTube
          </button>
        </nav>
      </div>

      <!-- Upload de fichier -->
      <div v-if="activeTab === 'upload'" class="space-y-6">
        <div class="text-center">
          <div
            @drop="handleDrop"
            @dragover.prevent
            @dragenter.prevent
            :class="[
              'border-2 border-dashed rounded-lg p-8 transition-colors',
              isDragging
                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                : 'border-gray-300 dark:border-gray-600 hover:border-gray-400'
            ]"
          >
            <div class="flex flex-col items-center">
              <div class="text-4xl mb-4">📄</div>
              <div class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                Glissez votre fichier audio ici
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                ou cliquez pour sélectionner
              </div>
              <input
                ref="fileInput"
                type="file"
                accept="audio/*,video/*"
                @change="handleFileSelect"
                class="hidden"
              />
              <button
                @click="fileInput?.click()"
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md transition-colors"
              >
                Choisir un fichier
              </button>
            </div>
          </div>

          <!-- Fichier sélectionné -->
          <div v-if="selectedFile" class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="text-2xl mr-3">🎵</div>
                <div class="text-left">
                  <div class="font-medium text-gray-900 dark:text-white">
                    {{ selectedFile.name }}
                  </div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ formatFileSize(selectedFile.size) }}
                  </div>
                </div>
              </div>
              <button
                @click="removeFile"
                class="text-red-500 hover:text-red-700 transition-colors"
              >
                ✕
              </button>
            </div>
          </div>
        </div>

        <!-- Options de transcription pour fichier -->
        <div v-if="selectedFile" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Langue du fichier
            </label>
            <select
              v-model="fileLanguage"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
            >
              <option value="auto">Détection automatique</option>
              <option value="fr">Français</option>
              <option value="en">Anglais</option>
              <option value="es">Espagnol</option>
              <option value="de">Allemand</option>
              <option value="it">Italien</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Titre de la transcription (optionnel)
            </label>
            <input
              v-model="fileTitle"
              type="text"
              :placeholder="selectedFile.name"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
            />
          </div>
        </div>
      </div>

      <!-- URL YouTube -->
      <div v-if="activeTab === 'youtube'" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            URL de la vidéo YouTube
          </label>
          <div class="flex">
            <input
              v-model="youtubeUrl"
              type="url"
              placeholder="https://www.youtube.com/watch?v=..."
              class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-l-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
            />
            <button
              @click="validateYouTubeUrl"
              :disabled="!youtubeUrl || isValidatingUrl"
              class="px-4 py-2 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-300 text-white rounded-r-md transition-colors"
            >
              {{ isValidatingUrl ? '⏳' : '✓' }}
            </button>
          </div>
          <div v-if="urlError" class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ urlError }}
          </div>
        </div>

        <!-- Aperçu YouTube -->
        <div v-if="youtubePreview" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
          <div class="flex items-start space-x-4">
            <img
              :src="youtubePreview.thumbnail"
              :alt="youtubePreview.title"
              class="w-32 h-24 object-cover rounded"
            />
            <div class="flex-1">
              <h3 class="font-medium text-gray-900 dark:text-white">
                {{ youtubePreview.title }}
              </h3>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ youtubePreview.channel }}
              </p>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Durée: {{ youtubePreview.duration }}
              </p>
            </div>
          </div>
        </div>

        <!-- Options de transcription pour YouTube -->
        <div v-if="youtubePreview" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Langue de la vidéo
            </label>
            <select
              v-model="youtubeLanguage"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
            >
              <option value="auto">Détection automatique</option>
              <option value="fr">Français</option>
              <option value="en">Anglais</option>
              <option value="es">Espagnol</option>
              <option value="de">Allemand</option>
              <option value="it">Italien</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Boutons d'action -->
      <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
        <button
          @click="$router.push('/transcriptions')"
          class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 rounded-md transition-colors"
        >
          Annuler
        </button>
        <button
          @click="startTranscription"
          :disabled="!canStartTranscription || isProcessing"
          class="px-6 py-2 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-300 text-white rounded-md transition-colors flex items-center"
        >
          <span v-if="isProcessing" class="animate-spin mr-2">⏳</span>
          {{ isProcessing ? 'Traitement...' : 'Démarrer la transcription' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { TranscriptionAPI } from '@/api/transcriptions'
import type { YouTubeValidationResponse } from '@/api/transcriptions'
import { useUIStore } from '@/stores/ui'

const router = useRouter()
const uiStore = useUIStore()

// Références de template
const fileInput = ref<HTMLInputElement>()

// État des onglets
const activeTab = ref<'upload' | 'youtube'>('upload')

// Upload de fichier
const selectedFile = ref<File | null>(null)
const fileTitle = ref('')
const fileLanguage = ref('auto')
const isDragging = ref(false)

// YouTube
const youtubeUrl = ref('')
const youtubeLanguage = ref('auto')
const youtubePreview = ref<YouTubeValidationResponse | null>(null)
const urlError = ref('')
const isValidatingUrl = ref(false)

// État général
const isProcessing = ref(false)

// Gestion du glisser-déposer
const handleDrop = (event: DragEvent) => {
  event.preventDefault()
  isDragging.value = false
  
  const files = event.dataTransfer?.files
  if (files && files.length > 0) {
    const file = files[0]
    if (file.type.startsWith('audio/') || file.type.startsWith('video/')) {
      selectedFile.value = file
      fileTitle.value = file.name.replace(/\.[^/.]+$/, '')
    }
  }
}

// Sélection de fichier
const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) {
    selectedFile.value = file
    fileTitle.value = file.name.replace(/\.[^/.]+$/, '')
  }
}

// Supprimer le fichier
const removeFile = () => {
  selectedFile.value = null
  fileTitle.value = ''
}

// Formatage de la taille de fichier
const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// Validation URL YouTube
const validateYouTubeUrl = async () => {
  if (!youtubeUrl.value) return
  
  isValidatingUrl.value = true
  urlError.value = ''
  youtubePreview.value = null
  
  try {
    const response = await TranscriptionAPI.validateYouTubeUrl(youtubeUrl.value)
    
    if (response.success && response.data) {
      youtubePreview.value = response.data
      urlError.value = ''
    } else {
      throw new Error('Réponse invalide du serveur')
    }
  } catch (error: any) {
    console.error('Erreur lors de la validation YouTube:', error)
    urlError.value = error.message || 'Erreur lors de la validation de l\'URL'
    youtubePreview.value = null
  } finally {
    isValidatingUrl.value = false
  }
}

// Vérification si on peut démarrer la transcription
const canStartTranscription = computed(() => {
  if (activeTab.value === 'upload') {
    return selectedFile.value !== null
  } else {
    return youtubePreview.value !== null
  }
})

// Démarrer la transcription
const startTranscription = async () => {
  if (!canStartTranscription.value) return
  
  isProcessing.value = true
  
  try {
    let transcriptionData
    
    if (activeTab.value === 'upload' && selectedFile.value) {
      // Logique d'upload de fichier
      transcriptionData = {
        file: selectedFile.value,
        title: fileTitle.value || selectedFile.value.name.replace(/\.[^/.]+$/, ''),
        language: fileLanguage.value
      }
    } else if (activeTab.value === 'youtube' && youtubeUrl.value) {
      // Logique de transcription YouTube
      transcriptionData = {
        youtubeUrl: youtubeUrl.value,
        title: youtubePreview.value?.title || 'Transcription YouTube',
        language: youtubeLanguage.value
      }
    } else {
      throw new Error('Données de transcription invalides')
    }
    
    // Appel API pour créer la transcription
    const response = await TranscriptionAPI.createTranscription(transcriptionData)
    
    if (response.success && response.data) {
      // Afficher une notification de succès
      uiStore.showNotification({
        type: 'success',
        title: 'Transcription créée',
        message: `La transcription "${response.data.fileName}" a été créée avec succès. Traitement en cours...`
      })
      
      // Redirection vers les détails de la transcription ou la liste
      router.push(`/transcriptions/${response.data.transcriptionId}`)
    } else {
      throw new Error('Réponse invalide du serveur')
    }
    
  } catch (error: any) {
    console.error('Erreur lors du démarrage de la transcription:', error)
    
    // Afficher une notification d'erreur
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur de création',
      message: error.message || 'Impossible de créer la transcription. Veuillez réessayer.'
    })
  } finally {
    isProcessing.value = false
  }
}
</script>

<script lang="ts">
export default {
  name: 'CreateTranscription'
}
</script>
