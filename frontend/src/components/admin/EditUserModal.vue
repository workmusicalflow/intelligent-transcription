<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

      <!-- Modal -->
      <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="sm:flex sm:items-start">
            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 sm:mx-0 sm:h-10 sm:w-10">
              <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
            </div>
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
              <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Modifier l'utilisateur
              </h3>
              <div class="mt-4">
                <form @submit.prevent="submitEdit" class="space-y-4">
                  <!-- Email -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Adresse email *
                    </label>
                    <input
                      v-model="form.email"
                      type="email"
                      required
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                    />
                  </div>

                  <!-- Nom -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Nom complet *
                    </label>
                    <input
                      v-model="form.name"
                      type="text"
                      required
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                    />
                  </div>

                  <!-- Rôle -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Rôle *
                    </label>
                    <select
                      v-model="form.role"
                      required
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                    >
                      <option value="user">Utilisateur</option>
                      <option value="moderator">Modérateur</option>
                      <option value="admin">Administrateur</option>
                    </select>
                  </div>

                  <!-- Statut -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Statut *
                    </label>
                    <select
                      v-model="form.status"
                      required
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                    >
                      <option value="active">Actif</option>
                      <option value="pending">En attente</option>
                      <option value="suspended">Suspendu</option>
                    </select>
                  </div>

                  <!-- Avatar URL -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      URL de l'avatar (optionnel)
                    </label>
                    <input
                      v-model="form.avatar"
                      type="url"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                      placeholder="https://example.com/avatar.jpg"
                    />
                  </div>

                  <!-- Permissions spéciales -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Permissions spéciales
                    </label>
                    <div class="space-y-2">
                      <div class="flex items-center">
                        <input
                          v-model="form.permissions.canManageUsers"
                          type="checkbox"
                          id="canManageUsers"
                          class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                        <label for="canManageUsers" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                          Peut gérer les utilisateurs
                        </label>
                      </div>
                      <div class="flex items-center">
                        <input
                          v-model="form.permissions.canAccessAnalytics"
                          type="checkbox"
                          id="canAccessAnalytics"
                          class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                        <label for="canAccessAnalytics" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                          Peut accéder aux analytics
                        </label>
                      </div>
                      <div class="flex items-center">
                        <input
                          v-model="form.permissions.canManageSystem"
                          type="checkbox"
                          id="canManageSystem"
                          class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                        <label for="canManageSystem" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                          Peut gérer le système
                        </label>
                      </div>
                    </div>
                  </div>

                  <!-- Actions sur le compte -->
                  <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                      Actions sur le compte
                    </h4>
                    <div class="space-y-2">
                      <div class="flex items-center">
                        <input
                          v-model="form.actions.requirePasswordReset"
                          type="checkbox"
                          id="requirePasswordReset"
                          class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                        <label for="requirePasswordReset" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                          Forcer le changement de mot de passe à la prochaine connexion
                        </label>
                      </div>
                      <div class="flex items-center">
                        <input
                          v-model="form.actions.sendNotification"
                          type="checkbox"
                          id="sendNotificationEdit"
                          class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                        <label for="sendNotificationEdit" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                          Notifier l'utilisateur des modifications
                        </label>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button
            @click="submitEdit"
            :disabled="!isFormValid || loading"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 disabled:opacity-50 disabled:cursor-not-allowed sm:ml-3 sm:w-auto sm:text-sm"
          >
            <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ loading ? 'Enregistrement...' : 'Enregistrer les modifications' }}
          </button>
          <button
            @click="$emit('close')"
            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-gray-300 dark:border-gray-500 dark:hover:bg-gray-500"
          >
            Annuler
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

// Props
interface User {
  id: string
  name: string
  email: string
  avatar?: string
  role: 'admin' | 'user' | 'moderator'
  status: 'active' | 'pending' | 'suspended'
}

const props = defineProps<{
  user: User
}>()

// Events
const emit = defineEmits<{
  close: []
  save: [userData: any]
}>()

// Form data
const form = ref({
  email: '',
  name: '',
  role: 'user' as 'admin' | 'user' | 'moderator',
  status: 'active' as 'active' | 'pending' | 'suspended',
  avatar: '',
  permissions: {
    canManageUsers: false,
    canAccessAnalytics: false,
    canManageSystem: false
  },
  actions: {
    requirePasswordReset: false,
    sendNotification: true
  }
})

const loading = ref(false)

// Computed
const isFormValid = computed(() => {
  return form.value.email && form.value.name && form.value.role && form.value.status
})

// Methods
const submitEdit = async () => {
  if (!isFormValid.value) return
  
  loading.value = true
  try {
    await new Promise(resolve => setTimeout(resolve, 1000)) // Simulation
    emit('save', { 
      id: props.user.id,
      ...form.value 
    })
  } catch (error) {
    console.error('Erreur lors de la modification:', error)
  } finally {
    loading.value = false
  }
}

// Initialize form with user data
onMounted(() => {
  form.value.email = props.user.email
  form.value.name = props.user.name
  form.value.role = props.user.role
  form.value.status = props.user.status
  form.value.avatar = props.user.avatar || ''
  
  // Set permissions based on role
  if (props.user.role === 'admin') {
    form.value.permissions.canManageUsers = true
    form.value.permissions.canAccessAnalytics = true
    form.value.permissions.canManageSystem = true
  } else if (props.user.role === 'moderator') {
    form.value.permissions.canAccessAnalytics = true
  }
})
</script>

<script lang="ts">
export default {
  name: 'EditUserModal'
}
</script>