<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <!-- Header -->
      <div>
        <div class="mx-auto h-12 w-12 bg-gradient-to-br from-primary-500 to-blue-600 rounded-lg flex items-center justify-center">
          <span class="text-white font-bold text-lg">IT</span>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
          Connexion à votre compte
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
          Ou
          <router-link
            to="/register"
            class="font-medium text-primary-600 hover:text-primary-500"
          >
            créez un nouveau compte
          </router-link>
        </p>
      </div>

      <!-- Form -->
      <form class="mt-8 space-y-6" @submit.prevent="handleSubmit">
        <div class="space-y-4">
          <Input
            v-model="form.email"
            type="email"
            label="Adresse email"
            placeholder="votre@email.com"
            required
            :error="errors.email"
          />
          
          <Input
            v-model="form.password"
            type="password"
            label="Mot de passe"
            placeholder="••••••••"
            required
            :error="errors.password"
          />
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input
              id="remember-me"
              name="remember-me"
              type="checkbox"
              class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
            />
            <label for="remember-me" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
              Se souvenir de moi
            </label>
          </div>

          <div class="text-sm">
            <router-link
              to="/forgot-password"
              class="font-medium text-primary-600 hover:text-primary-500"
            >
              Mot de passe oublié ?
            </router-link>
          </div>
        </div>

        <div>
          <Button
            type="submit"
            :loading="authStore.isLoading"
            fullWidth
            size="lg"
          >
            Se connecter
          </Button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import Input from '@components/ui/Input.vue'
import Button from '@components/ui/Button.vue'
import { useAuthStore } from '@stores/auth'
import type { LoginCredentials } from '@/types'

const router = useRouter()
const authStore = useAuthStore()

// Form state
const form = reactive<LoginCredentials>({
  email: '',
  password: ''
})

const errors = ref<Record<string, string>>({})

// Methods
const validateForm = () => {
  errors.value = {}
  
  if (!form.email) {
    errors.value.email = 'L\'email est requis'
  } else if (!/\S+@\S+\.\S+/.test(form.email)) {
    errors.value.email = 'L\'email n\'est pas valide'
  }
  
  if (!form.password) {
    errors.value.password = 'Le mot de passe est requis'
  } else if (form.password.length < 6) {
    errors.value.password = 'Le mot de passe doit contenir au moins 6 caractères'
  }
  
  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) return
  
  try {
    await authStore.login(form)
    // Redirect is handled by the auth store
  } catch (error) {
    console.error('Login error:', error)
  }
}
</script>

<script lang="ts">
export default {
  name: 'Login'
}
</script>