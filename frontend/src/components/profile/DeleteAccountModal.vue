<template>
  <ModalContainer @close="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
      <!-- En-tête -->
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Supprimer le compte
            </h3>
          </div>
        </div>
      </div>

      <!-- Contenu -->
      <div class="px-6 py-4">
        <div class="mb-4">
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Cette action supprimera définitivement votre compte et toutes les données associées :
          </p>
          <ul class="mt-3 text-sm text-gray-600 dark:text-gray-400 list-disc list-inside space-y-1">
            <li>Votre profil et informations personnelles</li>
            <li>Toutes vos transcriptions</li>
            <li>L'historique de vos conversations</li>
            <li>Vos préférences et paramètres</li>
            <li>Votre historique de facturation</li>
          </ul>
          <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <p class="text-sm font-medium text-red-800 dark:text-red-200">
              ⚠️ Cette action est irréversible
            </p>
            <p class="text-sm text-red-700 dark:text-red-300 mt-1">
              Vous ne pourrez pas récupérer ces données après suppression.
            </p>
          </div>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Confirmation mot de passe -->
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Confirmez avec votre mot de passe
            </label>
            <Input
              id="password"
              v-model="password"
              type="password"
              placeholder="Votre mot de passe"
              :error="error"
              required
              :disabled="loading"
              autocomplete="current-password"
            />
          </div>

          <!-- Confirmation texte -->
          <div>
            <label for="confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Tapez "SUPPRIMER" pour confirmer
            </label>
            <Input
              id="confirmation"
              v-model="confirmationText"
              type="text"
              placeholder="SUPPRIMER"
              required
              :disabled="loading"
            />
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-4">
            <Button
              type="button"
              variant="secondary"
              @click="$emit('close')"
              :disabled="loading"
            >
              Annuler
            </Button>
            
            <Button
              type="submit"
              :loading="loading"
              :disabled="!isFormValid"
              class="bg-red-600 hover:bg-red-700 text-white border-red-600 hover:border-red-700 focus:ring-red-500"
            >
              Supprimer définitivement
            </Button>
          </div>
        </form>
      </div>
    </div>
  </ModalContainer>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import ModalContainer from '@/components/ui/ModalContainer.vue'
import Input from '@/components/ui/Input.vue'
import Button from '@/components/ui/Button.vue'

interface Emits {
  (e: 'close'): void
  (e: 'confirm', password: string): void
}

const emit = defineEmits<Emits>()

// État réactif
const loading = ref(false)
const password = ref('')
const confirmationText = ref('')
const error = ref('')

// Validation du formulaire
const isFormValid = computed(() => {
  return password.value.length > 0 && confirmationText.value === 'SUPPRIMER'
})

/**
 * Soumettre le formulaire
 */
function handleSubmit() {
  if (!isFormValid.value) return
  
  error.value = ''
  loading.value = true
  
  // Simuler une vérification
  setTimeout(() => {
    emit('confirm', password.value)
    loading.value = false
  }, 1000)
}
</script>