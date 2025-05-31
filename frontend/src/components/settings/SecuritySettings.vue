<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
      Paramètres de sécurité
    </h3>
    
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Authentification à deux facteurs -->
      <div class="space-y-4">
        <h4 class="text-md font-medium text-gray-900 dark:text-white">
          Authentification à deux facteurs (2FA)
        </h4>
        
        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
              <ShieldIcon class="h-5 w-5 text-blue-500 mr-3" />
              <div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Activer l'authentification à deux facteurs
                </span>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Protection supplémentaire avec une application d'authentification
                </p>
              </div>
            </div>
            <input
              v-model="form.enable2FA"
              type="checkbox"
              :disabled="loading"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
            >
          </div>
          
          <div v-if="form.enable2FA" class="space-y-3 pl-8 border-l-2 border-blue-200 dark:border-blue-800">
            <div class="text-sm text-gray-600 dark:text-gray-400">
              <p class="font-medium mb-2">Configuration 2FA :</p>
              <ol class="list-decimal list-inside space-y-1">
                <li>Installez une app d'authentification (Google Authenticator, Authy, etc.)</li>
                <li>Scannez le QR code ou saisissez la clé secrète</li>
                <li>Sauvegardez vos codes de récupération</li>
              </ol>
            </div>
            
            <Button
              type="button"
              variant="secondary"
              @click="generate2FASetup"
              :loading="generating2FA"
            >
              <QrcodeIcon class="h-4 w-4 mr-2" />
              Configurer 2FA
            </Button>
          </div>
        </div>
      </div>

      <!-- Codes de récupération -->
      <div v-if="form.enable2FA" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <KeyIcon class="h-5 w-5 text-yellow-500 mr-3" />
            <div>
              <span class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                Codes de récupération
              </span>
              <p class="text-sm text-yellow-700 dark:text-yellow-300">
                Générez des codes pour accéder à votre compte sans 2FA
              </p>
            </div>
          </div>
          <Button
            type="button"
            variant="secondary"
            @click="generateBackupCodes"
            :loading="generatingCodes"
          >
            <DocumentDownloadIcon class="h-4 w-4 mr-2" />
            Générer
          </Button>
        </div>
      </div>

      <!-- Timeout de session -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Timeout de session (en minutes)
        </label>
        <div class="flex items-center space-x-4">
          <input
            v-model.number="form.sessionTimeout"
            type="range"
            min="5"
            max="120"
            step="5"
            :disabled="loading"
            class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
          >
          <span class="text-sm font-medium text-gray-900 dark:text-white min-w-0">
            {{ form.sessionTimeout }} min
          </span>
        </div>
        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
          Durée d'inactivité avant déconnexion automatique
        </p>
      </div>

      <!-- Liste blanche d'IP -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Liste blanche d'adresses IP
        </label>
        <div class="space-y-2">
          <div v-for="(ip, index) in form.ipWhitelist" :key="index" class="flex items-center gap-2">
            <input
              v-model="form.ipWhitelist[index]"
              type="text"
              placeholder="192.168.1.1 ou 192.168.1.0/24"
              :disabled="loading"
              class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
            <Button
              type="button"
              variant="secondary"
              @click="removeIpFromWhitelist(index)"
              :disabled="loading"
            >
              <XIcon class="h-4 w-4" />
            </Button>
          </div>
          <Button
            type="button"
            variant="secondary"
            @click="addIpToWhitelist"
            :disabled="loading"
          >
            <PlusIcon class="h-4 w-4 mr-2" />
            Ajouter une IP
          </Button>
        </div>
        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
          Limitez l'accès à votre compte depuis des IPs spécifiques (optionnel)
        </p>
      </div>

      <!-- Journalisation sécurisée -->
      <div>
        <label class="flex items-center justify-between">
          <div>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
              Journalisation des événements de sécurité
            </span>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Enregistrer les connexions, échecs et actions sensibles
            </p>
          </div>
          <input
            v-model="form.logSecurity"
            type="checkbox"
            :disabled="loading"
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
          >
        </label>
      </div>

      <!-- Activité récente -->
      <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
          <h4 class="text-sm font-medium text-gray-900 dark:text-white">
            Activité récente de sécurité
          </h4>
          <Button
            type="button"
            variant="secondary"
            @click="loadSecurityActivity"
            :loading="loadingActivity"
          >
            <RefreshIcon class="h-4 w-4 mr-2" />
            Actualiser
          </Button>
        </div>
        
        <div class="space-y-2">
          <div v-for="activity in securityActivity" :key="activity.id" 
               class="flex items-center justify-between p-2 border border-gray-200 dark:border-gray-600 rounded">
            <div class="flex items-center">
              <component :is="getActivityIcon(activity.type)" 
                         class="h-4 w-4 mr-2" 
                         :class="getActivityColor(activity.type)" />
              <div>
                <span class="text-sm text-gray-900 dark:text-white">{{ activity.description }}</span>
                <p class="text-xs text-gray-600 dark:text-gray-400">{{ activity.ip }} • {{ activity.timestamp }}</p>
              </div>
            </div>
            <span class="text-xs px-2 py-1 rounded-full" :class="[
              activity.success 
                ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200'
                : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-200'
            ]">
              {{ activity.success ? 'Réussi' : 'Échec' }}
            </span>
          </div>
          
          <div v-if="!securityActivity.length" class="text-center text-sm text-gray-600 dark:text-gray-400 py-4">
            Aucune activité récente
          </div>
        </div>
      </div>

      <!-- Recommandations de sécurité -->
      <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-start">
          <InformationCircleIcon class="h-5 w-5 text-blue-500 mt-0.5 mr-3" />
          <div>
            <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">
              Recommandations de sécurité
            </h4>
            <ul class="text-xs text-blue-700 dark:text-blue-300 mt-1 space-y-1">
              <li v-if="!form.enable2FA">• Activez l'authentification à deux facteurs</li>
              <li v-if="form.sessionTimeout > 60">• Réduisez le timeout de session (recommandé: 30 min)</li>
              <li v-if="!form.logSecurity">• Activez la journalisation pour surveiller votre compte</li>
              <li>• Utilisez un mot de passe unique et complexe</li>
              <li>• Vérifiez régulièrement votre activité de sécurité</li>
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

interface SecuritySettings {
  enable2FA: boolean
  sessionTimeout: number
  ipWhitelist: string[]
  logSecurity: boolean
}

interface SecurityActivity {
  id: string
  type: 'login' | 'logout' | 'failed_login' | 'settings_change' | '2fa_setup'
  description: string
  ip: string
  timestamp: string
  success: boolean
}

interface Props {
  settings: SecuritySettings
  loading: boolean
}

interface Emits {
  (e: 'update', settings: Partial<SecuritySettings>): void
  (e: 'generate-backup-codes'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const generating2FA = ref(false)
const generatingCodes = ref(false)
const loadingActivity = ref(false)
const securityActivity = ref<SecurityActivity[]>([])

// Formulaire réactif
const form = reactive<SecuritySettings>({
  enable2FA: false,
  sessionTimeout: 30,
  ipWhitelist: [],
  logSecurity: true
})

// État initial pour détecter les changements
let initialForm: SecuritySettings | null = null

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
}

/**
 * Soumettre le formulaire
 */
function handleSubmit() {
  emit('update', { ...form })
  initialForm = { ...form }
}

/**
 * Ajouter une IP à la liste blanche
 */
function addIpToWhitelist() {
  form.ipWhitelist.push('')
}

/**
 * Supprimer une IP de la liste blanche
 */
function removeIpFromWhitelist(index: number) {
  form.ipWhitelist.splice(index, 1)
}

/**
 * Générer la configuration 2FA
 */
async function generate2FASetup() {
  generating2FA.value = true
  
  try {
    // Simuler la génération de la configuration 2FA
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Ici, on ouvrirait une modal avec le QR code et la clé secrète
    console.log('Configuration 2FA générée')
  } catch (error) {
    console.error('Erreur lors de la génération 2FA:', error)
  } finally {
    generating2FA.value = false
  }
}

/**
 * Générer des codes de récupération
 */
async function generateBackupCodes() {
  generatingCodes.value = true
  
  try {
    emit('generate-backup-codes')
    await new Promise(resolve => setTimeout(resolve, 1000))
  } catch (error) {
    console.error('Erreur lors de la génération des codes:', error)
  } finally {
    generatingCodes.value = false
  }
}

/**
 * Charger l'activité de sécurité
 */
async function loadSecurityActivity() {
  loadingActivity.value = true
  
  try {
    // Simuler le chargement de l'activité
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    securityActivity.value = [
      {
        id: '1',
        type: 'login',
        description: 'Connexion réussie',
        ip: '192.168.1.100',
        timestamp: 'Il y a 2 heures',
        success: true
      },
      {
        id: '2',
        type: 'failed_login',
        description: 'Tentative de connexion échouée',
        ip: '203.0.113.42',
        timestamp: 'Il y a 1 jour',
        success: false
      },
      {
        id: '3',
        type: 'settings_change',
        description: 'Modification des paramètres',
        ip: '192.168.1.100',
        timestamp: 'Il y a 2 jours',
        success: true
      }
    ]
  } catch (error) {
    console.error('Erreur lors du chargement de l\'activité:', error)
  } finally {
    loadingActivity.value = false
  }
}

/**
 * Obtenir l'icône pour un type d'activité
 */
function getActivityIcon(type: string) {
  switch (type) {
    case 'login': return 'LoginIcon'
    case 'logout': return 'LogoutIcon'
    case 'failed_login': return 'ExclamationIcon'
    case 'settings_change': return 'CogIcon'
    case '2fa_setup': return 'ShieldIcon'
    default: return 'InformationCircleIcon'
  }
}

/**
 * Obtenir la couleur pour un type d'activité
 */
function getActivityColor(type: string) {
  switch (type) {
    case 'login': return 'text-green-500'
    case 'logout': return 'text-blue-500'
    case 'failed_login': return 'text-red-500'
    case 'settings_change': return 'text-yellow-500'
    case '2fa_setup': return 'text-purple-500'
    default: return 'text-gray-500'
  }
}

// Initialiser au montage
onMounted(() => {
  initializeForm()
  loadSecurityActivity()
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
// Icônes pour la sécurité
const ShieldIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
    </svg>
  `
}

const KeyIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
    </svg>
  `
}

const QrcodeIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
    </svg>
  `
}

const DocumentDownloadIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
    </svg>
  `
}

const XIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
    </svg>
  `
}

const PlusIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
    </svg>
  `
}

const RefreshIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
    </svg>
  `
}

const LoginIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
    </svg>
  `
}

const LogoutIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
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

const CogIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
    </svg>
  `
}

const InformationCircleIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
  `
}

export default {
  components: {
    ShieldIcon,
    KeyIcon,
    QrcodeIcon,
    DocumentDownloadIcon,
    XIcon,
    PlusIcon,
    RefreshIcon,
    LoginIcon,
    LogoutIcon,
    ExclamationIcon,
    CogIcon,
    InformationCircleIcon
  }
}
</script>