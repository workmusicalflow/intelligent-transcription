<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-hidden">
      <!-- En-tête -->
      <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Importer la configuration
        </h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <XIcon class="h-6 w-6" />
        </button>
      </div>

      <!-- Contenu -->
      <div class="p-6 overflow-y-auto">
        <div class="space-y-6">
          <!-- Zone de dépôt de fichier -->
          <div
            @drop.prevent="handleDrop"
            @dragover.prevent
            @dragenter.prevent
            :class="[
              'border-2 border-dashed rounded-lg p-8 text-center transition-colors',
              isDragging
                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'
            ]"
          >
            <DocumentIcon class="h-12 w-12 text-gray-400 mx-auto mb-4" />
            <div class="space-y-2">
              <p class="text-sm font-medium text-gray-900 dark:text-white">
                Glissez-déposez votre fichier de configuration ici
              </p>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                ou
              </p>
              <Button
                type="button"
                variant="secondary"
                @click="triggerFileInput"
              >
                <UploadIcon class="h-4 w-4 mr-2" />
                Parcourir les fichiers
              </Button>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
              Formats acceptés: .json
            </p>
          </div>

          <!-- Input fichier masqué -->
          <input
            ref="fileInput"
            type="file"
            accept=".json"
            @change="handleFileSelect"
            class="hidden"
          >

          <!-- Fichier sélectionné -->
          <div v-if="selectedFile" class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <DocumentIcon class="h-5 w-5 text-blue-500 mr-2" />
                <div>
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ selectedFile.name }}
                  </p>
                  <p class="text-xs text-gray-600 dark:text-gray-400">
                    {{ formatFileSize(selectedFile.size) }}
                  </p>
                </div>
              </div>
              <button
                @click="removeFile"
                class="text-gray-400 hover:text-red-500"
              >
                <XIcon class="h-4 w-4" />
              </button>
            </div>
          </div>

          <!-- Aperçu du contenu -->
          <div v-if="previewData" class="space-y-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white">
              Aperçu de la configuration
            </h4>
            
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 space-y-3">
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Version:</span>
                <span class="font-medium text-gray-900 dark:text-white">
                  {{ previewData.version }}
                </span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Date d'export:</span>
                <span class="font-medium text-gray-900 dark:text-white">
                  {{ formatDate(previewData.exportDate) }}
                </span>
              </div>
            </div>

            <!-- Sections à importer -->
            <div class="space-y-3">
              <h5 class="text-sm font-medium text-gray-900 dark:text-white">
                Sections à importer:
              </h5>
              <div class="space-y-2">
                <label v-for="section in availableSections" :key="section.key" 
                       class="flex items-center justify-between">
                  <div class="flex items-center">
                    <component :is="section.icon" class="h-4 w-4 mr-2 text-gray-500" />
                    <div>
                      <span class="text-sm text-gray-700 dark:text-gray-300">
                        {{ section.name }}
                      </span>
                      <p class="text-xs text-gray-600 dark:text-gray-400">
                        {{ section.description }}
                      </p>
                    </div>
                  </div>
                  <input
                    v-model="importSections[section.key]"
                    type="checkbox"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
                  >
                </label>
              </div>
            </div>

            <!-- Options d'import -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
              <div class="flex items-start">
                <ExclamationIcon class="h-5 w-5 text-yellow-500 mt-0.5 mr-3" />
                <div class="space-y-3">
                  <div>
                    <span class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                      Options d'import
                    </span>
                  </div>
                  
                  <label class="flex items-center">
                    <input
                      v-model="mergeSettings"
                      type="checkbox"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded mr-2"
                    >
                    <span class="text-sm text-yellow-700 dark:text-yellow-300">
                      Fusionner avec les paramètres existants
                    </span>
                  </label>
                  
                  <p class="text-xs text-yellow-600 dark:text-yellow-400">
                    Si décoché, tous vos paramètres actuels seront remplacés par ceux du fichier importé.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Erreurs -->
          <div v-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <div class="flex items-center">
              <XCircleIcon class="h-5 w-5 text-red-500 mr-2" />
              <span class="text-sm text-red-800 dark:text-red-200">
                {{ error }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-3 p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
        <Button
          variant="secondary"
          @click="$emit('close')"
        >
          Annuler
        </Button>
        <Button
          variant="primary"
          @click="importConfiguration"
          :disabled="!previewData || importing"
          :loading="importing"
        >
          <DownloadIcon class="h-4 w-4 mr-2" />
          Importer
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import Button from '@/components/ui/Button.vue'

interface ConfigurationData {
  version: string
  exportDate: string
  settings: {
    transcription?: any
    interface?: any
    notifications?: any
    api?: any
    storage?: any
    security?: any
  }
}

interface Emits {
  (e: 'close'): void
  (e: 'import', data: ConfigurationData): void
}

const emit = defineEmits<Emits>()

const fileInput = ref<HTMLInputElement>()
const selectedFile = ref<File | null>(null)
const previewData = ref<ConfigurationData | null>(null)
const error = ref<string>('')
const isDragging = ref(false)
const importing = ref(false)
const mergeSettings = ref(true)

const importSections = reactive({
  transcription: true,
  interface: true,
  notifications: true,
  api: false, // Par défaut désactivé pour sécurité
  storage: true,
  security: false // Par défaut désactivé pour sécurité
})

const availableSections = computed(() => [
  {
    key: 'transcription',
    name: 'Transcription',
    description: 'Paramètres de transcription et formats',
    icon: 'MicrophoneIcon'
  },
  {
    key: 'interface',
    name: 'Interface',
    description: 'Thème, langue et préférences d\'affichage',
    icon: 'ViewGridIcon'
  },
  {
    key: 'notifications',
    name: 'Notifications',
    description: 'Préférences de notifications',
    icon: 'BellIcon'
  },
  {
    key: 'api',
    name: 'API',
    description: 'Configuration API (clés sensibles)',
    icon: 'CodeIcon'
  },
  {
    key: 'storage',
    name: 'Stockage',
    description: 'Paramètres de stockage et rétention',
    icon: 'DatabaseIcon'
  },
  {
    key: 'security',
    name: 'Sécurité',
    description: 'Paramètres de sécurité (sensibles)',
    icon: 'ShieldIcon'
  }
].filter(section => previewData.value?.settings[section.key as keyof typeof previewData.value.settings]))

/**
 * Déclencher la sélection de fichier
 */
function triggerFileInput() {
  fileInput.value?.click()
}

/**
 * Gérer la sélection de fichier
 */
function handleFileSelect(event: Event) {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) {
    processFile(file)
  }
}

/**
 * Gérer le dépôt de fichier
 */
function handleDrop(event: DragEvent) {
  isDragging.value = false
  const file = event.dataTransfer?.files[0]
  if (file) {
    processFile(file)
  }
}

/**
 * Traiter le fichier sélectionné
 */
async function processFile(file: File) {
  error.value = ''
  selectedFile.value = file

  // Vérifier le type de fichier
  if (!file.name.endsWith('.json')) {
    error.value = 'Seuls les fichiers JSON sont acceptés'
    return
  }

  // Vérifier la taille (max 5MB)
  if (file.size > 5 * 1024 * 1024) {
    error.value = 'Le fichier est trop volumineux (max 5MB)'
    return
  }

  try {
    const content = await file.text()
    const data = JSON.parse(content)

    // Valider la structure
    if (!data.version || !data.settings) {
      error.value = 'Format de fichier invalide - structure incorrecte'
      return
    }

    previewData.value = data
  } catch (err) {
    error.value = 'Impossible de lire le fichier - format JSON invalide'
  }
}

/**
 * Supprimer le fichier sélectionné
 */
function removeFile() {
  selectedFile.value = null
  previewData.value = null
  error.value = ''
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

/**
 * Formater la taille du fichier
 */
function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

/**
 * Formater la date
 */
function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

/**
 * Importer la configuration
 */
async function importConfiguration() {
  if (!previewData.value) return

  importing.value = true

  try {
    // Filtrer les sections à importer
    const filteredSettings: any = {}
    
    Object.keys(importSections).forEach(key => {
      if (importSections[key as keyof typeof importSections] && 
          previewData.value?.settings[key as keyof typeof previewData.value.settings]) {
        filteredSettings[key] = previewData.value.settings[key as keyof typeof previewData.value.settings]
      }
    })

    const configToImport = {
      ...previewData.value,
      settings: filteredSettings,
      mergeSettings: mergeSettings.value
    }

    emit('import', configToImport)
  } catch (err) {
    error.value = 'Erreur lors de l\'import de la configuration'
  } finally {
    importing.value = false
  }
}
</script>

<script lang="ts">
// Icônes pour le modal d'import
const XIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
    </svg>
  `
}

const DocumentIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
    </svg>
  `
}

const UploadIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
    </svg>
  `
}

const DownloadIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
    </svg>
  `
}

const ExclamationIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
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

const MicrophoneIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
    </svg>
  `
}

const ViewGridIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
    </svg>
  `
}

const BellIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
    </svg>
  `
}

const CodeIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
    </svg>
  `
}

const DatabaseIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
    </svg>
  `
}

const ShieldIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
    </svg>
  `
}

export default {
  components: {
    XIcon,
    DocumentIcon,
    UploadIcon,
    DownloadIcon,
    ExclamationIcon,
    XCircleIcon,
    MicrophoneIcon,
    ViewGridIcon,
    BellIcon,
    CodeIcon,
    DatabaseIcon,
    ShieldIcon
  }
}
</script>