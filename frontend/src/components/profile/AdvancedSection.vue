<template>
  <div class="space-y-6">
    <!-- Export des données -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
      <div class="flex justify-between items-start">
        <div class="flex-1">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Exporter mes données
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Téléchargez une copie de toutes vos données personnelles et transcriptions
          </p>
          <div class="mt-4 space-y-2 text-sm text-gray-600 dark:text-gray-400">
            <p>• Informations de profil</p>
            <p>• Toutes les transcriptions</p>
            <p>• Historique des conversations</p>
            <p>• Préférences et paramètres</p>
          </div>
        </div>
        
        <div class="ml-6">
          <Button
            variant="secondary"
            @click="$emit('export-data')"
            :loading="loading"
          >
            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
            </svg>
            Exporter
          </Button>
        </div>
      </div>
    </div>

    <!-- Paramètres de confidentialité -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
        Confidentialité et données
      </h3>
      
      <div class="space-y-6">
        <!-- Analytiques -->
        <div class="flex items-center justify-between">
          <div>
            <h4 class="text-sm font-medium text-gray-900 dark:text-white">
              Données d'utilisation anonymes
            </h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
              Nous aider à améliorer le service en partageant des données d'utilisation anonymisées
            </p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              v-model="analyticsEnabled"
              type="checkbox"
              class="sr-only peer"
              :disabled="loading"
            >
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
          </label>
        </div>

        <!-- Amélioration du modèle -->
        <div class="flex items-center justify-between">
          <div>
            <h4 class="text-sm font-medium text-gray-900 dark:text-white">
              Amélioration du modèle de transcription
            </h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
              Permettre l'utilisation de vos transcriptions pour améliorer nos modèles (données anonymisées)
            </p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              v-model="modelImprovementEnabled"
              type="checkbox"
              class="sr-only peer"
              :disabled="loading"
            >
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
          </label>
        </div>

        <!-- Conservation des fichiers audio -->
        <div>
          <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
            Conservation des fichiers audio
          </h4>
          <div class="space-y-2">
            <label class="flex items-center">
              <input
                v-model="audioRetention"
                type="radio"
                value="delete-immediately"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600"
                :disabled="loading"
              >
              <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                Supprimer immédiatement après transcription (recommandé)
              </span>
            </label>
            <label class="flex items-center">
              <input
                v-model="audioRetention"
                type="radio"
                value="keep-24h"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600"
                :disabled="loading"
              >
              <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                Conserver 24 heures (pour retraitement si nécessaire)
              </span>
            </label>
            <label class="flex items-center">
              <input
                v-model="audioRetention"
                type="radio"
                value="keep-30d"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600"
                :disabled="loading"
              >
              <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                Conserver 30 jours
              </span>
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Zone de danger -->
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6">
      <h3 class="text-lg font-semibold text-red-900 dark:text-red-200 mb-6">
        Zone de danger
      </h3>
      
      <div class="space-y-6">
        <!-- Supprimer toutes les données -->
        <div class="flex items-center justify-between">
          <div>
            <h4 class="text-sm font-medium text-red-900 dark:text-red-200">
              Supprimer toutes mes données
            </h4>
            <p class="text-sm text-red-700 dark:text-red-300 mt-1">
              Supprime définitivement toutes vos transcriptions et conversations
            </p>
          </div>
          <Button
            variant="secondary"
            @click="deleteAllData"
            :loading="loading"
            class="text-red-600 border-red-300 hover:bg-red-50 dark:text-red-400 dark:border-red-600 dark:hover:bg-red-900/30"
          >
            Supprimer les données
          </Button>
        </div>

        <!-- Supprimer le compte -->
        <div class="border-t border-red-200 dark:border-red-800 pt-6">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="text-sm font-medium text-red-900 dark:text-red-200">
                Supprimer mon compte
              </h4>
              <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                Supprime définitivement votre compte et toutes les données associées. Cette action est irréversible.
              </p>
            </div>
            <Button
              variant="secondary"
              @click="showDeleteAccountModal = true"
              :loading="loading"
              class="text-red-600 border-red-300 hover:bg-red-50 dark:text-red-400 dark:border-red-600 dark:hover:bg-red-900/30"
            >
              Supprimer le compte
            </Button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de confirmation de suppression de compte -->
    <DeleteAccountModal
      v-if="showDeleteAccountModal"
      @close="showDeleteAccountModal = false"
      @confirm="handleDeleteAccount"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import Button from '@/components/ui/Button.vue'
import DeleteAccountModal from './DeleteAccountModal.vue'
import { useUIStore } from '@/stores/ui'

interface Props {
  loading: boolean
}

interface Emits {
  (e: 'export-data'): void
  (e: 'delete-account', password: string): void
}

defineProps<Props>()
const emit = defineEmits<Emits>()
const uiStore = useUIStore()

// État réactif
const showDeleteAccountModal = ref(false)
const analyticsEnabled = ref(true)
const modelImprovementEnabled = ref(false)
const audioRetention = ref('delete-immediately')

/**
 * Supprimer toutes les données
 */
function deleteAllData() {
  if (confirm('Êtes-vous sûr de vouloir supprimer toutes vos données ? Cette action est irréversible.')) {
    // TODO: Implémenter la suppression des données
    uiStore.showNotification({
      type: 'info',
      title: 'Info',
      message: 'Suppression des données bientôt disponible'
    })
  }
}

/**
 * Gérer la suppression de compte
 */
function handleDeleteAccount(password: string) {
  emit('delete-account', password)
  showDeleteAccountModal.value = false
}
</script>