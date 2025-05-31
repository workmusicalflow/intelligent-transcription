<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
          <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
          </svg>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
          Mot de passe oublié
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
          {{ !emailSent ? 'Entrez votre adresse email pour recevoir un lien de réinitialisation' : 'Un email de réinitialisation a été envoyé' }}
        </p>
      </div>

      <!-- Success Message -->
      <div v-if="emailSent" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
              Email envoyé avec succès
            </h3>
            <div class="mt-2 text-sm text-green-700 dark:text-green-300">
              <p>
                Un lien de réinitialisation a été envoyé à <strong>{{ sentToEmail }}</strong>.
                Vérifiez votre boîte de réception et suivez les instructions.
              </p>
            </div>
            <div class="mt-4">
              <div class="flex space-x-7">
                <button
                  @click="resendEmail"
                  :disabled="resendCooldown > 0"
                  class="bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 hover:bg-green-100 dark:hover:bg-green-900/40 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium px-3 py-2 rounded-md transition-colors duration-200"
                >
                  {{ resendCooldown > 0 ? `Renvoyer dans ${resendCooldown}s` : 'Renvoyer l\'email' }}
                </button>
                <button
                  @click="goToLogin"
                  class="bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 hover:bg-green-100 dark:hover:bg-green-900/40 text-sm font-medium px-3 py-2 rounded-md transition-colors duration-200"
                >
                  Retour à la connexion
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Form -->
      <form v-if="!emailSent" class="mt-8 space-y-6" @submit.prevent="submitForm">
        <div class="space-y-4">
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Adresse email
            </label>
            <Input
              id="email"
              v-model="form.email"
              type="email"
              placeholder="votre@email.com"
              :error="errors.email"
              :disabled="isLoading"
              required
              class="w-full"
            />
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="errors.general" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                Erreur
              </h3>
              <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                <p>{{ errors.general }}</p>
              </div>
            </div>
          </div>
        </div>

        <div>
          <Button
            type="submit"
            :loading="isLoading"
            :disabled="!isFormValid || isLoading"
            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ isLoading ? 'Envoi en cours...' : 'Envoyer le lien de réinitialisation' }}
          </Button>
        </div>

        <div class="text-center">
          <router-link
            to="/auth/login"
            class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 transition-colors duration-200"
          >
            Retour à la connexion
          </router-link>
        </div>
      </form>

      <!-- Help Section -->
      <div class="mt-8 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
          Besoin d'aide ?
        </h3>
        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
          <li>• Vérifiez votre dossier spam/courrier indésirable</li>
          <li>• Le lien est valide pendant 1 heure</li>
          <li>• Vous pouvez demander un nouveau lien si nécessaire</li>
        </ul>
        <div class="mt-3">
          <a
            href="mailto:support@transcription-intelligente.com?subject=Problème%20de%20connexion"
            class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300"
          >
            Contacter le support →
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { authApi } from '@/api/auth'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'

const router = useRouter()

// Form state
const form = ref({
  email: ''
})

const isLoading = ref(false)
const emailSent = ref(false)
const sentToEmail = ref('')
const resendCooldown = ref(0)

const errors = ref({
  email: '',
  general: ''
})

// Validation
const isFormValid = computed(() => {
  return form.value.email && form.value.email.includes('@')
})

// Cooldown timer
let cooldownInterval: number | null = null

const startCooldown = () => {
  resendCooldown.value = 60
  cooldownInterval = window.setInterval(() => {
    resendCooldown.value--
    if (resendCooldown.value <= 0) {
      clearInterval(cooldownInterval!)
      cooldownInterval = null
    }
  }, 1000)
}

onUnmounted(() => {
  if (cooldownInterval) {
    clearInterval(cooldownInterval)
  }
})

// Form validation
const validateForm = (): boolean => {
  errors.value = { email: '', general: '' }

  if (!form.value.email) {
    errors.value.email = 'L\'adresse email est requise'
    return false
  }

  if (!form.value.email.includes('@')) {
    errors.value.email = 'Veuillez entrer une adresse email valide'
    return false
  }

  return true
}

// Submit form
const submitForm = async () => {
  if (!validateForm()) return

  isLoading.value = true
  errors.value.general = ''

  try {
    const response = await authApi.requestPasswordReset(form.value.email)
    
    if (response.success) {
      emailSent.value = true
      sentToEmail.value = form.value.email
      startCooldown()
    } else {
      errors.value.general = response.error || 'Une erreur est survenue lors de l\'envoi de l\'email'
    }
  } catch (error: any) {
    console.error('Password reset error:', error)
    errors.value.general = error.response?.data?.message || 'Une erreur est survenue. Veuillez réessayer.'
  } finally {
    isLoading.value = false
  }
}

// Resend email
const resendEmail = async () => {
  if (resendCooldown.value > 0) return

  isLoading.value = true
  errors.value.general = ''

  try {
    const response = await authApi.requestPasswordReset(sentToEmail.value)
    
    if (response.success) {
      startCooldown()
    } else {
      errors.value.general = response.error || 'Une erreur est survenue lors du renvoi de l\'email'
    }
  } catch (error: any) {
    console.error('Resend email error:', error)
    errors.value.general = error.response?.data?.message || 'Une erreur est survenue. Veuillez réessayer.'
  } finally {
    isLoading.value = false
  }
}

// Navigation
const goToLogin = () => {
  router.push('/auth/login')
}
</script>

<script lang="ts">
export default {
  name: 'ForgotPassword'
}
</script>