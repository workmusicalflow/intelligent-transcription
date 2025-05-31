<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
      Paramètres de notifications
    </h3>
    
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Types de notifications -->
      <div class="space-y-4">
        <h4 class="text-md font-medium text-gray-900 dark:text-white">
          Canaux de notification
        </h4>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Email -->
          <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <EmailIcon class="h-5 w-5 text-gray-500 dark:text-gray-400 mr-3" />
                <div>
                  <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Email
                  </span>
                  <p class="text-sm text-gray-600 dark:text-gray-400">
                    Recevoir des notifications par email
                  </p>
                </div>
              </div>
              <input
                v-model="form.email"
                type="checkbox"
                :disabled="loading"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
              >
            </div>
          </div>

          <!-- Push -->
          <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <BellIcon class="h-5 w-5 text-gray-500 dark:text-gray-400 mr-3" />
                <div>
                  <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Push
                  </span>
                  <p class="text-sm text-gray-600 dark:text-gray-400">
                    Notifications push dans le navigateur
                  </p>
                </div>
              </div>
              <input
                v-model="form.push"
                type="checkbox"
                :disabled="loading"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
              >
            </div>
          </div>
        </div>
      </div>

      <!-- Événements -->
      <div class="space-y-4">
        <h4 class="text-md font-medium text-gray-900 dark:text-white">
          Événements à notifier
        </h4>
        
        <div class="space-y-3">
          <label class="flex items-center justify-between">
            <div class="flex items-center">
              <CheckCircleIcon class="h-5 w-5 text-green-500 mr-3" />
              <div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Transcription terminée
                </span>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Quand une transcription est prête
                </p>
              </div>
            </div>
            <input
              v-model="form.transcriptionComplete"
              type="checkbox"
              :disabled="loading"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
            >
          </label>
          
          <label class="flex items-center justify-between">
            <div class="flex items-center">
              <XCircleIcon class="h-5 w-5 text-red-500 mr-3" />
              <div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Transcription échouée
                </span>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  En cas d'erreur de transcription
                </p>
              </div>
            </div>
            <input
              v-model="form.transcriptionFailed"
              type="checkbox"
              :disabled="loading"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
            >
          </label>
          
          <label class="flex items-center justify-between">
            <div class="flex items-center">
              <ChartBarIcon class="h-5 w-5 text-blue-500 mr-3" />
              <div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Rapport hebdomadaire
                </span>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Résumé de votre activité de la semaine
                </p>
              </div>
            </div>
            <input
              v-model="form.weeklyReport"
              type="checkbox"
              :disabled="loading"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
            >
          </label>
          
          <label class="flex items-center justify-between">
            <div class="flex items-center">
              <CogIcon class="h-5 w-5 text-gray-500 mr-3" />
              <div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Maintenance système
                </span>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Mises à jour et maintenance programmée
                </p>
              </div>
            </div>
            <input
              v-model="form.systemMaintenance"
              type="checkbox"
              :disabled="loading"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
            >
          </label>
        </div>
      </div>

      <!-- Test de notification -->
      <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
        <div class="flex items-center justify-between">
          <div>
            <h4 class="text-sm font-medium text-gray-900 dark:text-white">
              Test des notifications
            </h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
              Envoyer une notification de test pour vérifier vos paramètres
            </p>
          </div>
          <Button
            type="button"
            variant="secondary"
            @click="sendTestNotification"
            :loading="testingNotification"
          >
            <BellIcon class="h-4 w-4 mr-2" />
            Tester
          </Button>
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

interface NotificationSettings {
  email: boolean
  push: boolean
  transcriptionComplete: boolean
  transcriptionFailed: boolean
  weeklyReport: boolean
  systemMaintenance: boolean
}

interface Props {
  settings: NotificationSettings
  loading: boolean
}

interface Emits {
  (e: 'update', settings: Partial<NotificationSettings>): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const testingNotification = ref(false)

// Formulaire réactif
const form = reactive<NotificationSettings>({
  email: true,
  push: false,
  transcriptionComplete: true,
  transcriptionFailed: true,
  weeklyReport: false,
  systemMaintenance: true
})

// État initial pour détecter les changements
let initialForm: NotificationSettings | null = null

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
 * Envoyer une notification de test
 */
async function sendTestNotification() {
  testingNotification.value = true
  
  try {
    // Simuler l'envoi d'une notification de test
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Afficher notification de succès
    if (window.Notification && Notification.permission === 'granted') {
      new Notification('Test de notification', {
        body: 'Vos paramètres de notification fonctionnent correctement !',
        icon: '/favicon.ico'
      })
    }
  } catch (error) {
    console.error('Erreur lors du test de notification:', error)
  } finally {
    testingNotification.value = false
  }
}

// Initialiser au montage
onMounted(() => {
  initializeForm()
  
  // Demander permission pour les notifications push
  if (window.Notification && Notification.permission === 'default') {
    Notification.requestPermission()
  }
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
// Icônes pour les notifications
const EmailIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
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

const CheckCircleIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
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

const ChartBarIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
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

export default {
  components: {
    EmailIcon,
    BellIcon,
    CheckCircleIcon,
    XCircleIcon,
    ChartBarIcon,
    CogIcon
  }
}
</script>