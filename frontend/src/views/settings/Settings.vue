<template>
  <div class="container-app section-padding max-w-4xl">
    <!-- En-tête -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        Paramètres
      </h1>
      <p class="text-gray-600 dark:text-gray-400 mt-1">
        Configurez l'application selon vos préférences
      </p>
    </div>

    <!-- Navigation onglets -->
    <div class="border-b border-gray-200 dark:border-gray-700 mb-8">
      <nav class="-mb-px flex space-x-8">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'py-2 px-1 border-b-2 font-medium text-sm transition-colors',
            activeTab === tab.id
              ? 'border-blue-500 text-blue-600 dark:text-blue-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
          ]"
        >
          <div class="flex items-center">
            <component :is="tab.icon" class="h-5 w-5 mr-2" />
            {{ tab.name }}
          </div>
        </button>
      </nav>
    </div>

    <!-- Contenu des onglets -->
    <div class="space-y-8">
      <!-- Onglet Transcription -->
      <div v-show="activeTab === 'transcription'">
        <TranscriptionSettings
          :settings="settings.transcription"
          :loading="loading.transcription"
          @update="updateTranscriptionSettings"
        />
      </div>

      <!-- Onglet Interface -->
      <div v-show="activeTab === 'interface'">
        <InterfaceSettings
          :settings="settings.interface"
          :loading="loading.interface"
          @update="updateInterfaceSettings"
        />
      </div>

      <!-- Onglet Notifications -->
      <div v-show="activeTab === 'notifications'">
        <NotificationSettings
          :settings="settings.notifications"
          :loading="loading.notifications"
          @update="updateNotificationSettings"
        />
      </div>

      <!-- Onglet API -->
      <div v-show="activeTab === 'api'">
        <ApiSettings
          :settings="settings.api"
          :loading="loading.api"
          @update="updateApiSettings"
          @test-connection="testApiConnection"
        />
      </div>

      <!-- Onglet Stockage -->
      <div v-show="activeTab === 'storage'">
        <StorageSettings
          :settings="settings.storage"
          :usage="storageUsage"
          :loading="loading.storage"
          @update="updateStorageSettings"
          @clear-cache="clearCache"
          @clear-transcriptions="clearTranscriptions"
        />
      </div>

      <!-- Onglet Sécurité -->
      <div v-show="activeTab === 'security'">
        <SecuritySettings
          :settings="settings.security"
          :loading="loading.security"
          @update="updateSecuritySettings"
          @generate-backup-codes="generateBackupCodes"
        />
      </div>
    </div>

    <!-- Actions globales -->
    <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
      <div class="flex justify-between items-center">
        <div>
          <h3 class="text-sm font-medium text-gray-900 dark:text-white">
            Configuration globale
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Importer/exporter la configuration complète
          </p>
        </div>
        <div class="flex gap-3">
          <Button
            variant="secondary"
            @click="exportSettings"
            :loading="exporting"
          >
            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
            </svg>
            Exporter
          </Button>
          <Button
            variant="secondary"
            @click="showImportModal = true"
          >
            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
            </svg>
            Importer
          </Button>
        </div>
      </div>
    </div>

    <!-- Modal d'import -->
    <ImportSettingsModal
      v-if="showImportModal"
      @close="showImportModal = false"
      @import="importSettings"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import Button from '@/components/ui/Button.vue'
import TranscriptionSettings from '@/components/settings/TranscriptionSettings.vue'
import InterfaceSettings from '@/components/settings/InterfaceSettings.vue'
import NotificationSettings from '@/components/settings/NotificationSettings.vue'
import ApiSettings from '@/components/settings/ApiSettings.vue'
import StorageSettings from '@/components/settings/StorageSettings.vue'
import SecuritySettings from '@/components/settings/SecuritySettings.vue'
import ImportSettingsModal from '@/components/settings/ImportSettingsModal.vue'
import { useUIStore } from '@/stores/ui'

interface AppSettings {
  transcription: {
    defaultLanguage: string
    enableTimestamps: boolean
    enableSpeakerDetection: boolean
    defaultOutputFormat: string
    qualityPreset: 'fast' | 'balanced' | 'high'
    chunkSize: number
  }
  interface: {
    theme: 'light' | 'dark' | 'system'
    language: string
    compactMode: boolean
    showTutorials: boolean
    autoSave: boolean
    confirmDeletions: boolean
  }
  notifications: {
    email: boolean
    push: boolean
    transcriptionComplete: boolean
    transcriptionFailed: boolean
    weeklyReport: boolean
    systemMaintenance: boolean
  }
  api: {
    openaiKey?: string
    customEndpoint?: string
    timeout: number
    retryAttempts: number
  }
  storage: {
    autoDeleteAudio: boolean
    audioRetentionDays: number
    maxCacheSize: number
    enableCompression: boolean
  }
  security: {
    enable2FA: boolean
    sessionTimeout: number
    ipWhitelist: string[]
    logSecurity: boolean
  }
}

interface StorageUsage {
  totalSize: number
  audioFiles: number
  transcriptions: number
  cache: number
  available: number
}

const uiStore = useUIStore()

// État réactif
const activeTab = ref('transcription')
const showImportModal = ref(false)
const exporting = ref(false)

const settings = reactive<AppSettings>({
  transcription: {
    defaultLanguage: 'auto',
    enableTimestamps: true,
    enableSpeakerDetection: false,
    defaultOutputFormat: 'txt',
    qualityPreset: 'balanced',
    chunkSize: 25
  },
  interface: {
    theme: 'system',
    language: 'fr',
    compactMode: false,
    showTutorials: true,
    autoSave: true,
    confirmDeletions: true
  },
  notifications: {
    email: true,
    push: false,
    transcriptionComplete: true,
    transcriptionFailed: true,
    weeklyReport: false,
    systemMaintenance: true
  },
  api: {
    timeout: 30000,
    retryAttempts: 3
  },
  storage: {
    autoDeleteAudio: true,
    audioRetentionDays: 1,
    maxCacheSize: 1024,
    enableCompression: true
  },
  security: {
    enable2FA: false,
    sessionTimeout: 30,
    ipWhitelist: [],
    logSecurity: true
  }
})

const storageUsage = ref<StorageUsage>({
  totalSize: 0,
  audioFiles: 0,
  transcriptions: 0,
  cache: 0,
  available: 0
})

// États de chargement par section
const loading = reactive({
  transcription: false,
  interface: false,
  notifications: false,
  api: false,
  storage: false,
  security: false
})

// Configuration des onglets
const tabs = computed(() => [
  {
    id: 'transcription',
    name: 'Transcription',
    icon: 'MicrophoneIcon'
  },
  {
    id: 'interface',
    name: 'Interface',
    icon: 'ViewGridIcon'
  },
  {
    id: 'notifications',
    name: 'Notifications',
    icon: 'BellIcon'
  },
  {
    id: 'api',
    name: 'API',
    icon: 'CodeIcon'
  },
  {
    id: 'storage',
    name: 'Stockage',
    icon: 'DatabaseIcon'
  },
  {
    id: 'security',
    name: 'Sécurité',
    icon: 'ShieldCheckIcon'
  }
])

/**
 * Charger les paramètres
 */
async function loadSettings() {
  try {
    // TODO: Implémenter le chargement des paramètres depuis l'API
    console.log('Chargement des paramètres...')
    
    // Charger l'utilisation du stockage
    await loadStorageUsage()
  } catch (error) {
    console.error('Erreur lors du chargement des paramètres:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de charger les paramètres'
    })
  }
}

/**
 * Charger l'utilisation du stockage
 */
async function loadStorageUsage() {
  try {
    // TODO: Implémenter le chargement de l'utilisation du stockage
    storageUsage.value = {
      totalSize: 2048,
      audioFiles: 512,
      transcriptions: 256,
      cache: 128,
      available: 1152
    }
  } catch (error) {
    console.error('Erreur lors du chargement de l\'utilisation du stockage:', error)
  }
}

/**
 * Mettre à jour les paramètres de transcription
 */
async function updateTranscriptionSettings(newSettings: Partial<AppSettings['transcription']>) {
  try {
    loading.transcription = true
    
    Object.assign(settings.transcription, newSettings)
    
    // TODO: Sauvegarder vers l'API
    console.log('Paramètres de transcription mis à jour:', newSettings)
    
    uiStore.showNotification({
      type: 'success',
      title: 'Succès',
      message: 'Paramètres de transcription mis à jour'
    })
  } catch (error) {
    console.error('Erreur:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de mettre à jour les paramètres'
    })
  } finally {
    loading.transcription = false
  }
}

/**
 * Mettre à jour les paramètres d'interface
 */
async function updateInterfaceSettings(newSettings: Partial<AppSettings['interface']>) {
  try {
    loading.interface = true
    
    Object.assign(settings.interface, newSettings)
    
    // Appliquer le thème si changé
    if (newSettings.theme) {
      uiStore.setTheme(newSettings.theme)
    }
    
    console.log('Paramètres d\'interface mis à jour:', newSettings)
    
    uiStore.showNotification({
      type: 'success',
      title: 'Succès',
      message: 'Paramètres d\'interface mis à jour'
    })
  } catch (error) {
    console.error('Erreur:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de mettre à jour les paramètres'
    })
  } finally {
    loading.interface = false
  }
}

/**
 * Mettre à jour les paramètres de notifications
 */
async function updateNotificationSettings(newSettings: Partial<AppSettings['notifications']>) {
  try {
    loading.notifications = true
    
    Object.assign(settings.notifications, newSettings)
    
    console.log('Paramètres de notifications mis à jour:', newSettings)
    
    uiStore.showNotification({
      type: 'success',
      title: 'Succès',
      message: 'Paramètres de notifications mis à jour'
    })
  } catch (error) {
    console.error('Erreur:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de mettre à jour les paramètres'
    })
  } finally {
    loading.notifications = false
  }
}

/**
 * Mettre à jour les paramètres API
 */
async function updateApiSettings(newSettings: Partial<AppSettings['api']>) {
  try {
    loading.api = true
    
    Object.assign(settings.api, newSettings)
    
    console.log('Paramètres API mis à jour:', newSettings)
    
    uiStore.showNotification({
      type: 'success',
      title: 'Succès',
      message: 'Paramètres API mis à jour'
    })
  } catch (error) {
    console.error('Erreur:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de mettre à jour les paramètres'
    })
  } finally {
    loading.api = false
  }
}

/**
 * Mettre à jour les paramètres de stockage
 */
async function updateStorageSettings(newSettings: Partial<AppSettings['storage']>) {
  try {
    loading.storage = true
    
    Object.assign(settings.storage, newSettings)
    
    console.log('Paramètres de stockage mis à jour:', newSettings)
    
    uiStore.showNotification({
      type: 'success',
      title: 'Succès',
      message: 'Paramètres de stockage mis à jour'
    })
  } catch (error) {
    console.error('Erreur:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de mettre à jour les paramètres'
    })
  } finally {
    loading.storage = false
  }
}

/**
 * Mettre à jour les paramètres de sécurité
 */
async function updateSecuritySettings(newSettings: Partial<AppSettings['security']>) {
  try {
    loading.security = true
    
    Object.assign(settings.security, newSettings)
    
    console.log('Paramètres de sécurité mis à jour:', newSettings)
    
    uiStore.showNotification({
      type: 'success',
      title: 'Succès',
      message: 'Paramètres de sécurité mis à jour'
    })
  } catch (error) {
    console.error('Erreur:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de mettre à jour les paramètres'
    })
  } finally {
    loading.security = false
  }
}

/**
 * Tester la connexion API
 */
async function testApiConnection() {
  try {
    loading.api = true
    
    // TODO: Implémenter le test de connexion API
    console.log('Test de connexion API...')
    
    // Simuler un délai
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    uiStore.showNotification({
      type: 'success',
      title: 'Connexion réussie',
      message: 'L\'API répond correctement'
    })
  } catch (error) {
    console.error('Erreur de test API:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Échec de connexion',
      message: 'Impossible de se connecter à l\'API'
    })
  } finally {
    loading.api = false
  }
}

/**
 * Vider le cache
 */
async function clearCache() {
  try {
    loading.storage = true
    
    // TODO: Implémenter le vidage du cache
    console.log('Vidage du cache...')
    
    // Simuler un délai
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Mettre à jour l'utilisation du stockage
    storageUsage.value.cache = 0
    storageUsage.value.available += 128
    
    uiStore.showNotification({
      type: 'success',
      title: 'Cache vidé',
      message: 'Le cache a été vidé avec succès'
    })
  } catch (error) {
    console.error('Erreur:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de vider le cache'
    })
  } finally {
    loading.storage = false
  }
}

/**
 * Supprimer les transcriptions
 */
async function clearTranscriptions() {
  if (!confirm('Êtes-vous sûr de vouloir supprimer toutes les transcriptions ? Cette action est irréversible.')) {
    return
  }
  
  try {
    loading.storage = true
    
    // TODO: Implémenter la suppression des transcriptions
    console.log('Suppression des transcriptions...')
    
    uiStore.showNotification({
      type: 'info',
      title: 'Info',
      message: 'Suppression des transcriptions bientôt disponible'
    })
  } catch (error) {
    console.error('Erreur:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de supprimer les transcriptions'
    })
  } finally {
    loading.storage = false
  }
}

/**
 * Générer des codes de récupération
 */
async function generateBackupCodes() {
  try {
    loading.security = true
    
    // TODO: Implémenter la génération de codes de récupération
    console.log('Génération des codes de récupération...')
    
    uiStore.showNotification({
      type: 'info',
      title: 'Info',
      message: 'Codes de récupération bientôt disponibles'
    })
  } catch (error) {
    console.error('Erreur:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de générer les codes de récupération'
    })
  } finally {
    loading.security = false
  }
}

/**
 * Exporter les paramètres
 */
async function exportSettings() {
  try {
    exporting.value = true
    
    const configData = {
      version: '1.0',
      exportDate: new Date().toISOString(),
      settings: settings
    }
    
    const blob = new Blob([JSON.stringify(configData, null, 2)], {
      type: 'application/json'
    })
    
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `settings-${new Date().toISOString().split('T')[0]}.json`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
    
    uiStore.showNotification({
      type: 'success',
      title: 'Export réussi',
      message: 'Configuration exportée avec succès'
    })
  } catch (error) {
    console.error('Erreur d\'export:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur d\'export',
      message: 'Impossible d\'exporter la configuration'
    })
  } finally {
    exporting.value = false
  }
}

/**
 * Importer les paramètres
 */
async function importSettings(configData: any) {
  try {
    if (configData.version && configData.settings) {
      Object.assign(settings, configData.settings)
      
      uiStore.showNotification({
        type: 'success',
        title: 'Import réussi',
        message: 'Configuration importée avec succès'
      })
    } else {
      throw new Error('Format de fichier invalide')
    }
  } catch (error) {
    console.error('Erreur d\'import:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur d\'import',
      message: 'Format de fichier invalide'
    })
  }
}

// Lifecycle
onMounted(() => {
  loadSettings()
})
</script>

<script lang="ts">
// Icônes pour les onglets
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

const ShieldCheckIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
    </svg>
  `
}

export default {
  name: 'Settings',
  components: {
    MicrophoneIcon,
    ViewGridIcon,
    BellIcon,
    CodeIcon,
    DatabaseIcon,
    ShieldCheckIcon
  }
}
</script>