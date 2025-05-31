<template>
  <div class="container-app section-padding max-w-4xl">
    <!-- En-tête -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        Mon profil
      </h1>
      <p class="text-gray-600 dark:text-gray-400 mt-1">
        Gérez vos informations personnelles et préférences
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
      <!-- Onglet Informations personnelles -->
      <div v-show="activeTab === 'personal'">
        <PersonalInfoSection
          :user="user"
          :loading="loading.personal"
          @update="updatePersonalInfo"
          @upload-avatar="uploadAvatar"
        />
      </div>

      <!-- Onglet Sécurité -->
      <div v-show="activeTab === 'security'">
        <SecuritySection
          :loading="loading.security"
          @change-password="handleChangePassword"
        />
      </div>

      <!-- Onglet Préférences -->
      <div v-show="activeTab === 'preferences'">
        <PreferencesSection
          :preferences="user?.preferences"
          :loading="loading.preferences"
          @update="updatePreferences"
        />
      </div>

      <!-- Onglet Historique -->
      <div v-show="activeTab === 'history'">
        <HistorySection
          :stats="userStats"
          :recent-activity="recentActivity"
          :loading="loading.history"
          @load-more="loadMoreActivity"
        />
      </div>

      <!-- Onglet Facturation -->
      <div v-show="activeTab === 'billing'">
        <BillingSection
          :subscription="subscription"
          :usage="usageStats"
          :loading="loading.billing"
          @manage-subscription="manageSubscription"
          @download-invoice="downloadInvoice"
        />
      </div>

      <!-- Onglet Paramètres avancés -->
      <div v-show="activeTab === 'advanced'">
        <AdvancedSection
          :loading="loading.advanced"
          @export-data="exportUserData"
          @delete-account="deleteAccount"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { authApi } from '@/api/auth'
import type { User, UserStats } from '@/types'
import PersonalInfoSection from '@/components/profile/PersonalInfoSection.vue'
import SecuritySection from '@/components/profile/SecuritySection.vue'
import PreferencesSection from '@/components/profile/PreferencesSection.vue'
import HistorySection from '@/components/profile/HistorySection.vue'
import BillingSection from '@/components/profile/BillingSection.vue'
import AdvancedSection from '@/components/profile/AdvancedSection.vue'
import { useAuthStore } from '@/stores/auth'
import { useUIStore } from '@/stores/ui'

const authStore = useAuthStore()
const uiStore = useUIStore()

// État réactif
const activeTab = ref('personal')
const user = ref<User | null>(null)
const userStats = ref<UserStats | null>(null)
const recentActivity = ref<any[]>([])
const subscription = ref<any>(null)
const usageStats = ref<any>(null)

// États de chargement par section
const loading = reactive({
  personal: false,
  security: false,
  preferences: false,
  history: false,
  billing: false,
  advanced: false
})

// Configuration des onglets
const tabs = computed(() => [
  {
    id: 'personal',
    name: 'Informations personnelles',
    icon: 'UserIcon'
  },
  {
    id: 'security',
    name: 'Sécurité',
    icon: 'LockIcon'
  },
  {
    id: 'preferences',
    name: 'Préférences',
    icon: 'CogIcon'
  },
  {
    id: 'history',
    name: 'Historique',
    icon: 'ClockIcon'
  },
  {
    id: 'billing',
    name: 'Facturation',
    icon: 'CreditCardIcon'
  },
  {
    id: 'advanced',
    name: 'Avancé',
    icon: 'AdjustmentsIcon'
  }
])

/**
 * Charger les données utilisateur
 */
async function loadUserData() {
  try {
    // Charger le profil utilisateur
    const userResponse = await authApi.me()
    if (userResponse.success && userResponse.data) {
      user.value = userResponse.data
      authStore.setUser(userResponse.data)
    }
  } catch (error) {
    console.error('Erreur lors du chargement du profil:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de charger les données du profil'
    })
  }
}

/**
 * Mettre à jour les informations personnelles
 */
async function updatePersonalInfo(data: Partial<User>) {
  try {
    loading.personal = true
    
    const response = await authApi.updateProfile(data)
    
    if (response.success && response.data) {
      user.value = response.data
      authStore.setUser(response.data)
      
      uiStore.showNotification({
        type: 'success',
        title: 'Succès',
        message: 'Profil mis à jour avec succès'
      })
    }
  } catch (error) {
    console.error('Erreur lors de la mise à jour:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de mettre à jour le profil'
    })
  } finally {
    loading.personal = false
  }
}

/**
 * Upload d'avatar
 */
async function uploadAvatar(file: File) {
  try {
    loading.personal = true
    
    // TODO: Implémenter l'upload d'avatar
    console.log('Upload avatar:', file)
    
    uiStore.showNotification({
      type: 'success',
      title: 'Succès',
      message: 'Avatar mis à jour avec succès'
    })
  } catch (error) {
    console.error('Erreur lors de l\'upload d\'avatar:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de mettre à jour l\'avatar'
    })
  } finally {
    loading.personal = false
  }
}

/**
 * Gérer le changement de mot de passe
 */
async function handleChangePassword(data: { currentPassword: string; newPassword: string }) {
  try {
    loading.security = true
    
    const response = await authApi.changePassword(data.currentPassword, data.newPassword)
    
    if (response.success) {
      uiStore.showNotification({
        type: 'success',
        title: 'Succès',
        message: 'Mot de passe changé avec succès'
      })
    }
  } catch (error) {
    console.error('Erreur lors du changement de mot de passe:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de changer le mot de passe'
    })
  } finally {
    loading.security = false
  }
}

/**
 * Gérer les sessions
 */
async function manageSessions() {
  try {
    loading.security = true
    
    const response = await authApi.getSessions()
    
    if (response.success) {
      // TODO: Afficher la modal de gestion des sessions
      console.log('Sessions:', response.data)
    }
  } catch (error) {
    console.error('Erreur lors du chargement des sessions:', error)
  } finally {
    loading.security = false
  }
}

/**
 * Activer l'authentification à deux facteurs
 */
async function enable2FA() {
  try {
    loading.security = true
    
    // TODO: Implémenter l'activation 2FA
    console.log('Activation 2FA')
    
    uiStore.showNotification({
      type: 'info',
      title: 'Info',
      message: 'Authentification à deux facteurs bientôt disponible'
    })
  } catch (error) {
    console.error('Erreur lors de l\'activation 2FA:', error)
  } finally {
    loading.security = false
  }
}

/**
 * Mettre à jour les préférences
 */
async function updatePreferences(preferences: Partial<User['preferences']>) {
  try {
    loading.preferences = true
    
    const response = await authApi.updatePreferences(preferences)
    
    if (response.success && response.data) {
      user.value = response.data
      authStore.setUser(response.data)
      
      uiStore.showNotification({
        type: 'success',
        title: 'Succès',
        message: 'Préférences mises à jour'
      })
    }
  } catch (error) {
    console.error('Erreur lors de la mise à jour des préférences:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de mettre à jour les préférences'
    })
  } finally {
    loading.preferences = false
  }
}

/**
 * Charger plus d'activité
 */
async function loadMoreActivity() {
  try {
    loading.history = true
    
    // TODO: Implémenter le chargement de l'historique
    console.log('Chargement de plus d\'activité')
  } catch (error) {
    console.error('Erreur lors du chargement de l\'historique:', error)
  } finally {
    loading.history = false
  }
}

/**
 * Gérer l'abonnement
 */
async function manageSubscription() {
  try {
    loading.billing = true
    
    // TODO: Implémenter la gestion d'abonnement
    console.log('Gestion abonnement')
    
    uiStore.showNotification({
      type: 'info',
      title: 'Info',
      message: 'Gestion d\'abonnement bientôt disponible'
    })
  } catch (error) {
    console.error('Erreur gestion abonnement:', error)
  } finally {
    loading.billing = false
  }
}

/**
 * Télécharger une facture
 */
async function downloadInvoice(invoiceId: string) {
  try {
    loading.billing = true
    
    // TODO: Implémenter le téléchargement de facture
    console.log('Téléchargement facture:', invoiceId)
  } catch (error) {
    console.error('Erreur téléchargement facture:', error)
  } finally {
    loading.billing = false
  }
}

/**
 * Exporter les données utilisateur
 */
async function exportUserData() {
  try {
    loading.advanced = true
    
    // TODO: Implémenter l'export de données
    console.log('Export données utilisateur')
    
    uiStore.showNotification({
      type: 'info',
      title: 'Info',
      message: 'Export de données bientôt disponible'
    })
  } catch (error) {
    console.error('Erreur export données:', error)
  } finally {
    loading.advanced = false
  }
}

/**
 * Supprimer le compte
 */
async function deleteAccount(password: string) {
  try {
    loading.advanced = true
    
    const response = await authApi.deleteAccount(password)
    
    if (response.success) {
      authStore.logout()
      
      uiStore.showNotification({
        type: 'success',
        title: 'Compte supprimé',
        message: 'Votre compte a été supprimé avec succès'
      })
    }
  } catch (error) {
    console.error('Erreur suppression compte:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de supprimer le compte'
    })
  } finally {
    loading.advanced = false
  }
}

// Lifecycle
onMounted(() => {
  loadUserData()
})
</script>

<script lang="ts">
// Icônes pour les onglets
const UserIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
    </svg>
  `
}

const LockIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
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

const ClockIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
  `
}

const CreditCardIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
    </svg>
  `
}

const AdjustmentsIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
    </svg>
  `
}

export default {
  name: 'Profile',
  components: {
    UserIcon,
    LockIcon,
    CogIcon,
    ClockIcon,
    CreditCardIcon,
    AdjustmentsIcon
  }
}
</script>
