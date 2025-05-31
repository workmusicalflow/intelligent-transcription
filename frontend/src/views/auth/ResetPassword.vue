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
          Réinitialiser le mot de passe
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
          {{ !resetCompleted ? 'Entrez votre nouveau mot de passe' : 'Votre mot de passe a été réinitialisé avec succès' }}
        </p>
      </div>

      <!-- Success Message -->
      <div v-if="resetCompleted" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
              Mot de passe réinitialisé
            </h3>
            <div class="mt-2 text-sm text-green-700 dark:text-green-300">
              <p>
                Votre mot de passe a été modifié avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.
              </p>
            </div>
            <div class="mt-4">
              <button
                @click="goToLogin"
                class="bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 hover:bg-green-100 dark:hover:bg-green-900/40 text-sm font-medium px-3 py-2 rounded-md transition-colors duration-200"
              >
                Se connecter
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Token Error -->
      <div v-if="tokenError" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
              Lien invalide ou expiré
            </h3>
            <div class="mt-2 text-sm text-red-700 dark:text-red-300">
              <p>{{ tokenError }}</p>
            </div>
            <div class="mt-4">
              <router-link
                to="/auth/forgot-password"
                class="bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200 hover:bg-red-100 dark:hover:bg-red-900/40 text-sm font-medium px-3 py-2 rounded-md transition-colors duration-200"
              >
                Demander un nouveau lien
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Form -->
      <form v-if="!resetCompleted && !tokenError" class="mt-8 space-y-6" @submit.prevent="submitForm">
        <div class="space-y-4">
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Nouveau mot de passe
            </label>
            <Input
              id="password"
              v-model="form.password"
              type="password"
              placeholder="••••••••"
              :error="errors.password"
              :disabled="isLoading"
              show-password-toggle
              required
              class="w-full"
            />
          </div>

          <div>
            <label for="confirmPassword" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Confirmer le mot de passe
            </label>
            <Input
              id="confirmPassword"
              v-model="form.confirmPassword"
              type="password"
              placeholder="••••••••"
              :error="errors.confirmPassword"
              :disabled="isLoading"
              show-password-toggle
              required
              class="w-full"
            />
          </div>
        </div>

        <!-- Password Strength Indicator -->
        <div v-if="form.password" class="space-y-2">
          <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
            Force du mot de passe
          </div>
          <div class="flex space-x-1">
            <div
              v-for="i in 4"
              :key="i"
              class="h-2 flex-1 rounded-full"
              :class="getStrengthBarClass(i)"
            ></div>
          </div>
          <div class="text-xs" :class="getStrengthTextClass()">
            {{ getStrengthText() }}
          </div>
          <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
            <li :class="{ 'text-green-600 dark:text-green-400': form.password.length >= 8 }">
              ✓ Au moins 8 caractères
            </li>
            <li :class="{ 'text-green-600 dark:text-green-400': /[A-Z]/.test(form.password) }">
              ✓ Une lettre majuscule
            </li>
            <li :class="{ 'text-green-600 dark:text-green-400': /[a-z]/.test(form.password) }">
              ✓ Une lettre minuscule
            </li>
            <li :class="{ 'text-green-600 dark:text-green-400': /\d/.test(form.password) }">
              ✓ Un chiffre
            </li>
            <li :class="{ 'text-green-600 dark:text-green-400': hasSpecialChar }">
              ✓ Un caractère spécial
            </li>
          </ul>
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
            {{ isLoading ? 'Réinitialisation en cours...' : 'Réinitialiser le mot de passe' }}
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
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { authApi } from '@/api/auth'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'

const router = useRouter()
const route = useRoute()

// Form state
const form = ref({
  password: '',
  confirmPassword: ''
})

const isLoading = ref(false)
const resetCompleted = ref(false)
const tokenError = ref('')
const resetToken = ref('')

const errors = ref({
  password: '',
  confirmPassword: '',
  general: ''
})

// Validation
const isFormValid = computed(() => {
  return form.value.password && 
         form.value.confirmPassword && 
         form.value.password === form.value.confirmPassword &&
         getPasswordStrength() >= 3
})

// Password strength calculation
const getPasswordStrength = (): number => {
  const password = form.value.password
  let strength = 0

  if (password.length >= 8) strength++
  if (/[A-Z]/.test(password)) strength++
  if (/[a-z]/.test(password)) strength++
  if (/\d/.test(password)) strength++
  
  const specialChars = /[!@#$%^&*(),.?':{}|<>]/
  if (specialChars.test(password)) strength++

  return strength
}

// Password validation helpers
const hasSpecialChar = computed(() => /[!@#$%^&*(),.?':{}|<>]/.test(form.value.password))

const getStrengthBarClass = (index: number): string => {
  const strength = getPasswordStrength()
  if (index <= strength) {
    if (strength <= 2) return 'bg-red-500'
    if (strength <= 3) return 'bg-yellow-500'
    return 'bg-green-500'
  }
  return 'bg-gray-200 dark:bg-gray-700'
}

const getStrengthText = (): string => {
  const strength = getPasswordStrength()
  if (strength <= 2) return 'Faible'
  if (strength <= 3) return 'Moyen'
  if (strength <= 4) return 'Fort'
  return 'Très fort'
}

const getStrengthTextClass = (): string => {
  const strength = getPasswordStrength()
  if (strength <= 2) return 'text-red-600 dark:text-red-400'
  if (strength <= 3) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-green-600 dark:text-green-400'
}

// Initialize component
onMounted(() => {
  // Get token from URL params or query parameters
  const token = (route.params.token as string) || (route.query.token as string)
  if (!token) {
    tokenError.value = 'Aucun token de réinitialisation fourni. Veuillez utiliser le lien reçu par email.'
    return
  }
  resetToken.value = token
})

// Form validation
const validateForm = (): boolean => {
  errors.value = { password: '', confirmPassword: '', general: '' }

  if (!form.value.password) {
    errors.value.password = 'Le mot de passe est requis'
    return false
  }

  if (form.value.password.length < 8) {
    errors.value.password = 'Le mot de passe doit contenir au moins 8 caractères'
    return false
  }

  if (getPasswordStrength() < 3) {
    errors.value.password = 'Le mot de passe n\'est pas assez fort'
    return false
  }

  if (!form.value.confirmPassword) {
    errors.value.confirmPassword = 'La confirmation du mot de passe est requise'
    return false
  }

  if (form.value.password !== form.value.confirmPassword) {
    errors.value.confirmPassword = 'Les mots de passe ne correspondent pas'
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
    const response = await authApi.resetPassword(resetToken.value, form.value.password)
    
    if (response.success) {
      resetCompleted.value = true
    } else {
      if (response.error?.includes('token') || response.error?.includes('expired') || response.error?.includes('invalid')) {
        tokenError.value = response.error || 'Le lien de réinitialisation est invalide ou a expiré'
      } else {
        errors.value.general = response.error || 'Une erreur est survenue lors de la réinitialisation'
      }
    }
  } catch (error: any) {
    console.error('Password reset error:', error)
    const errorMessage = error.response?.data?.message || 'Une erreur est survenue. Veuillez réessayer.'
    
    if (errorMessage.includes('token') || errorMessage.includes('expired') || errorMessage.includes('invalid')) {
      tokenError.value = errorMessage
    } else {
      errors.value.general = errorMessage
    }
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
  name: 'ResetPassword'
}
</script>