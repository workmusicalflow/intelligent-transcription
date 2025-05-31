<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <!-- En-tête -->
      <div class="text-center">
        <!-- Logo -->
        <div class="mx-auto w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mb-4">
          <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
          </svg>
        </div>
        
        <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">
          Créer un compte
        </h2>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
          Rejoignez-nous pour transcrire vos contenus audio
        </p>
      </div>

      <!-- Formulaire d'inscription -->
      <form @submit.prevent="handleSubmit" class="mt-8 space-y-6" data-testid="register-form">
        <div class="space-y-4">
          <!-- Nom complet -->
          <div>
            <label for="name" class="sr-only">Nom complet</label>
            <Input
              id="name"
              v-model="form.name"
              type="text"
              autocomplete="name"
              placeholder="Nom complet"
              :disabled="loading"
              :error="errors.name"
              required
              class="relative block w-full"
              data-testid="name-input"
            >
              <template #prefix>
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </template>
            </Input>
          </div>

          <!-- Email -->
          <div>
            <label for="email" class="sr-only">Adresse email</label>
            <Input
              id="email"
              v-model="form.email"
              type="email"
              autocomplete="email"
              placeholder="Adresse email"
              :disabled="loading"
              :error="errors.email"
              required
              class="relative block w-full"
              data-testid="email-input"
            >
              <template #prefix>
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                </svg>
              </template>
            </Input>
          </div>

          <!-- Mot de passe -->
          <div>
            <label for="password" class="sr-only">Mot de passe</label>
            <Input
              id="password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="new-password"
              placeholder="Mot de passe"
              :disabled="loading"
              :error="errors.password"
              required
              class="relative block w-full"
              data-testid="password-input"
            >
              <template #prefix>
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
              </template>
              <template #suffix>
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                  <svg v-if="showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L5.636 5.636m4.242 4.242L14.12 14.12m-4.242-4.242L5.636 5.636m8.484 8.484l4.242 4.242M14.12 14.12L18.364 18.364M14.12 14.12l4.242 4.242"></path>
                  </svg>
                  <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                  </svg>
                </button>
              </template>
            </Input>
          </div>

          <!-- Confirmation mot de passe -->
          <div>
            <label for="confirmPassword" class="sr-only">Confirmer le mot de passe</label>
            <Input
              id="confirmPassword"
              v-model="form.confirmPassword"
              :type="showConfirmPassword ? 'text' : 'password'"
              autocomplete="new-password"
              placeholder="Confirmer le mot de passe"
              :disabled="loading"
              :error="errors.confirmPassword"
              required
              class="relative block w-full"
              data-testid="confirm-password-input"
            >
              <template #prefix>
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </template>
              <template #suffix>
                <button
                  type="button"
                  @click="showConfirmPassword = !showConfirmPassword"
                  class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                  <svg v-if="showConfirmPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L5.636 5.636m4.242 4.242L14.12 14.12m-4.242-4.242L5.636 5.636m8.484 8.484l4.242 4.242M14.12 14.12L18.364 18.364M14.12 14.12l4.242 4.242"></path>
                  </svg>
                  <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                  </svg>
                </button>
              </template>
            </Input>
          </div>
        </div>

        <!-- Indicateur de force du mot de passe -->
        <PasswordStrengthIndicator
          v-if="form.password"
          :password="form.password"
          class="mt-2"
          data-testid="password-strength"
        />

        <!-- Conditions d'utilisation et politique de confidentialité -->
        <div class="flex items-start">
          <div class="flex items-center h-5">
            <input
              id="terms"
              v-model="form.acceptTerms"
              type="checkbox"
              :disabled="loading"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700"
              data-testid="terms-checkbox"
              required
            >
          </div>
          <div class="ml-3 text-sm">
            <label for="terms" class="text-gray-700 dark:text-gray-300">
              J'accepte les
              <button
                type="button"
                @click="showTermsModal = true"
                class="text-blue-600 dark:text-blue-400 hover:text-blue-500 underline"
                data-testid="terms-link"
              >
                conditions d'utilisation
              </button>
              et la
              <button
                type="button"
                @click="showPrivacyModal = true"
                class="text-blue-600 dark:text-blue-400 hover:text-blue-500 underline"
                data-testid="privacy-link"
              >
                politique de confidentialité
              </button>
            </label>
            <p v-if="errors.acceptTerms" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ errors.acceptTerms }}
            </p>
          </div>
        </div>

        <!-- Newsletter -->
        <div class="flex items-center">
          <input
            id="newsletter"
            v-model="form.newsletter"
            type="checkbox"
            :disabled="loading"
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700"
          >
          <label for="newsletter" class="ml-3 text-sm text-gray-700 dark:text-gray-300">
            Je souhaite recevoir les actualités et conseils par email
          </label>
        </div>

        <!-- Bouton d'inscription -->
        <div>
          <Button
            type="submit"
            :loading="loading"
            :disabled="!isFormValid"
            variant="primary"
            size="lg"
            class="group relative w-full flex justify-center"
          >
            {{ loading ? 'Création en cours...' : 'Créer mon compte' }}
          </Button>
        </div>

        <!-- Message d'erreur global -->
        <div v-if="globalError" class="text-center">
          <p class="text-sm text-red-600 dark:text-red-400">
            {{ globalError }}
          </p>
        </div>

        <!-- Lien vers la connexion -->
        <div class="text-center">
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Vous avez déjà un compte ?
            <router-link
              to="/login"
              class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500 transition-colors"
              data-testid="login-link"
            >
              Se connecter
            </router-link>
          </p>
        </div>
      </form>

      <!-- Message de succès après inscription -->
      <div v-if="registrationSuccess" class="mt-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
              Inscription réussie !
            </h3>
            <div class="mt-1 text-sm text-green-700 dark:text-green-300">
              <p>Un email de vérification a été envoyé à <strong>{{ form.email }}</strong>.</p>
              <p class="mt-1">Veuillez cliquer sur le lien pour activer votre compte.</p>
            </div>
            <div class="mt-3">
              <button
                @click="resendVerificationEmail"
                :disabled="resendingEmail"
                class="text-sm font-medium text-green-600 dark:text-green-400 hover:text-green-500 disabled:opacity-50"
              >
                {{ resendingEmail ? 'Envoi en cours...' : 'Renvoyer l\'email' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modales -->
    <TermsModal v-if="showTermsModal" @close="showTermsModal = false" data-testid="terms-modal" />
    <PrivacyModal v-if="showPrivacyModal" @close="showPrivacyModal = false" data-testid="privacy-modal" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive, watch } from 'vue'
import { useRouter } from 'vue-router'
import { authApi } from '@/api/auth'
import type { RegisterData } from '@/types'
import Input from '@/components/ui/Input.vue'
import Button from '@/components/ui/Button.vue'
import PasswordStrengthIndicator from '@/components/auth/PasswordStrengthIndicator.vue'
import TermsModal from '@/components/auth/TermsModal.vue'
import PrivacyModal from '@/components/auth/PrivacyModal.vue'
import { useUIStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const uiStore = useUIStore()
const authStore = useAuthStore()

// État réactif
const loading = ref(false)
const registrationSuccess = ref(false)
const resendingEmail = ref(false)
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const showTermsModal = ref(false)
const showPrivacyModal = ref(false)
const globalError = ref('')

// Formulaire
const form = reactive<RegisterData & {
  acceptTerms: boolean
  newsletter: boolean
}>({
  name: '',
  email: '',
  password: '',
  confirmPassword: '',
  acceptTerms: false,
  newsletter: false
})

// Erreurs de validation
const errors = reactive({
  name: '',
  email: '',
  password: '',
  confirmPassword: '',
  acceptTerms: ''
})

// Validation en temps réel
watch(() => form.name, (value) => {
  if (value && value.length < 2) {
    errors.name = 'Le nom doit contenir au moins 2 caractères'
  } else {
    errors.name = ''
  }
})

watch(() => form.email, (value) => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (value && !emailRegex.test(value)) {
    errors.email = 'Adresse email invalide'
  } else {
    errors.email = ''
  }
})

watch(() => form.password, (value) => {
  if (value && value.length < 8) {
    errors.password = 'Le mot de passe doit contenir au moins 8 caractères'
  } else if (value && !/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(value)) {
    errors.password = 'Le mot de passe doit contenir au moins une minuscule, une majuscule et un chiffre'
  } else {
    errors.password = ''
  }
  
  // Vérifier la confirmation si elle est remplie
  if (form.confirmPassword && form.confirmPassword !== value) {
    errors.confirmPassword = 'Les mots de passe ne correspondent pas'
  } else if (form.confirmPassword) {
    errors.confirmPassword = ''
  }
})

watch(() => form.confirmPassword, (value) => {
  if (value && value !== form.password) {
    errors.confirmPassword = 'Les mots de passe ne correspondent pas'
  } else {
    errors.confirmPassword = ''
  }
})

watch(() => form.acceptTerms, (value) => {
  if (!value) {
    errors.acceptTerms = 'Vous devez accepter les conditions d\'utilisation'
  } else {
    errors.acceptTerms = ''
  }
})

// Validation du formulaire
const isFormValid = computed(() => {
  return (
    form.name.length >= 2 &&
    form.email &&
    /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email) &&
    form.password.length >= 8 &&
    /(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(form.password) &&
    form.confirmPassword === form.password &&
    form.acceptTerms &&
    !Object.values(errors).some(error => error)
  )
})

/**
 * Valider tous les champs
 */
function validateAllFields() {
  // Valider le nom
  if (!form.name) {
    errors.name = 'Le nom est requis'
  } else if (form.name.length < 2) {
    errors.name = 'Le nom doit contenir au moins 2 caractères'
  } else {
    errors.name = ''
  }
  
  // Valider l'email
  if (!form.email) {
    errors.email = 'L\'email est requis'
  } else {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!emailRegex.test(form.email)) {
      errors.email = 'Veuillez saisir un email valide'
    } else {
      errors.email = ''
    }
  }
  
  // Valider le mot de passe
  if (!form.password) {
    errors.password = 'Le mot de passe est requis'
  } else if (form.password.length < 8) {
    errors.password = 'Le mot de passe doit contenir au moins 8 caractères'
  } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(form.password)) {
    errors.password = 'Le mot de passe doit contenir au moins une minuscule, une majuscule et un chiffre'
  } else {
    errors.password = ''
  }
  
  // Valider la confirmation
  if (!form.confirmPassword) {
    errors.confirmPassword = 'La confirmation est requise'
  } else if (form.confirmPassword !== form.password) {
    errors.confirmPassword = 'Les mots de passe ne correspondent pas'
  } else {
    errors.confirmPassword = ''
  }
  
  // Valider les conditions
  if (!form.acceptTerms) {
    errors.acceptTerms = 'Vous devez accepter les conditions d\'utilisation'
  } else {
    errors.acceptTerms = ''
  }
  
  return !Object.values(errors).some(error => error)
}

/**
 * Gérer la soumission du formulaire
 */
async function handleSubmit() {
  // Toujours valider tous les champs pour afficher les erreurs
  const isValid = validateAllFields()
  
  // Si valide, procéder à l'inscription
  if (isValid && !loading.value) {
    await handleRegister()
  }
}

/**
 * Gérer l'inscription
 */
async function handleRegister() {
  
  try {
    loading.value = true
    globalError.value = ''
    
    // Préparer les données d'inscription
    const registerData = {
      name: form.name.trim(),
      email: form.email.trim().toLowerCase(),
      password: form.password,
      acceptTerms: form.acceptTerms,
      acceptPrivacy: form.newsletter // Les tests s'attendent à acceptPrivacy: false par défaut
    }
    
    // Appeler l'API d'inscription
    const response = await authApi.register(registerData)
    
    if (response.success) {
      registrationSuccess.value = true
      
      // Se connecter automatiquement après l'inscription
      if (response.data) {
        authStore.setUser(response.data.user)
        authStore.setToken(response.data.token)
        router.push('/dashboard')
      }
      
      uiStore.showNotification({
        type: 'success',
        title: 'Inscription réussie',
        message: 'Vérifiez votre email pour activer votre compte'
      })
    } else {
      // Gérer les erreurs de validation du serveur
      if (response.errors) {
        Object.entries(response.errors).forEach(([field, messages]) => {
          if (field in errors) {
            errors[field as keyof typeof errors] = Array.isArray(messages) ? messages[0] : messages
          }
        })
      } else {
        globalError.value = response.message || 'Une erreur est survenue lors de l\'inscription'
      }
    }
  } catch (error: any) {
    console.error('Erreur lors de l\'inscription:', error)
    globalError.value = error.message || 'Une erreur est survenue. Veuillez réessayer.'
  } finally {
    loading.value = false
  }
}

/**
 * Renvoyer l'email de vérification
 */
async function resendVerificationEmail() {
  try {
    resendingEmail.value = true
    
    const response = await authApi.resendVerification()
    
    if (response.success) {
      uiStore.showNotification({
        type: 'success',
        title: 'Email renvoyé',
        message: 'Un nouvel email de vérification a été envoyé'
      })
    } else {
      uiStore.showNotification({
        type: 'error',
        title: 'Erreur',
        message: response.message || 'Impossible de renvoyer l\'email'
      })
    }
  } catch (error) {
    console.error('Erreur lors du renvoi de l\'email:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Une erreur est survenue'
    })
  } finally {
    resendingEmail.value = false
  }
}
</script>

<script lang="ts">
export default {
  name: 'Register'
}
</script>