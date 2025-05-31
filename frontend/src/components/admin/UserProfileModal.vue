<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

      <!-- Modal -->
      <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <!-- En-tête -->
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
              Profil utilisateur
            </h3>
            <button
              @click="$emit('close')"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <!-- Profil utilisateur -->
          <div class="space-y-6">
            <!-- Informations de base -->
            <div class="flex items-center space-x-4">
              <div class="flex-shrink-0">
                <img v-if="user.avatar" class="h-16 w-16 rounded-full" :src="user.avatar" :alt="user.name" />
                <div v-else class="h-16 w-16 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                  <span class="text-xl font-medium text-gray-700 dark:text-gray-300">
                    {{ getUserInitials(user.name) }}
                  </span>
                </div>
              </div>
              <div>
                <h4 class="text-xl font-semibold text-gray-900 dark:text-white">{{ user.name }}</h4>
                <p class="text-gray-500 dark:text-gray-400">{{ user.email }}</p>
                <div class="flex items-center space-x-2 mt-2">
                  <span :class="getRoleBadgeClass(user.role)">
                    {{ getRoleLabel(user.role) }}
                  </span>
                  <span :class="getStatusBadgeClass(user.status)">
                    <span :class="getStatusDotClass(user.status)"></span>
                    {{ getStatusLabel(user.status) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ user.transcriptionsCount || 0 }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Transcriptions</div>
              </div>
              <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatDate(user.createdAt, 'relative') }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Inscrit depuis</div>
              </div>
              <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ user.lastLogin ? formatDate(user.lastLogin, 'relative') : 'Jamais' }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Dernière connexion</div>
              </div>
            </div>

            <!-- Informations détaillées -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Informations personnelles -->
              <div>
                <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Informations personnelles</h5>
                <dl class="space-y-2">
                  <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-400">ID utilisateur</dt>
                    <dd class="text-sm text-gray-900 dark:text-white font-mono">{{ user.id }}</dd>
                  </div>
                  <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-400">Date d'inscription</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ formatDate(user.createdAt) }}</dd>
                  </div>
                  <div v-if="user.lastLogin">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">Dernière connexion</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ formatDate(user.lastLogin) }}</dd>
                  </div>
                </dl>
              </div>

              <!-- Activité récente -->
              <div>
                <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Activité récente</h5>
                <div class="space-y-2">
                  <div v-if="userActivity.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                    Aucune activité récente
                  </div>
                  <div v-else v-for="activity in userActivity" :key="activity.id" class="flex items-center space-x-2">
                    <div :class="getActivityIconClass(activity.type)">
                      <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                        <circle cx="10" cy="10" r="3"/>
                      </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-xs text-gray-900 dark:text-white truncate">{{ activity.description }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(activity.timestamp, 'relative') }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Permissions -->
            <div v-if="user.role === 'admin' || user.role === 'moderator'">
              <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Permissions</h5>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <div v-for="permission in userPermissions" :key="permission.key" class="flex items-center space-x-2">
                  <svg v-if="permission.granted" class="h-4 w-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  <svg v-else class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                  <span class="text-sm text-gray-900 dark:text-white">{{ permission.label }}</span>
                </div>
              </div>
            </div>

            <!-- Notes administratives -->
            <div>
              <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Notes administratives</h5>
              <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <textarea
                  v-model="adminNotes"
                  rows="3"
                  class="w-full border-none bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 resize-none focus:outline-none"
                  placeholder="Ajouter des notes sur cet utilisateur (visible uniquement par les administrateurs)..."
                ></textarea>
              </div>
            </div>
          </div>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button
            @click="saveNotes"
            :disabled="notesSaving"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed sm:ml-3 sm:w-auto sm:text-sm"
          >
            {{ notesSaving ? 'Enregistrement...' : 'Enregistrer les notes' }}
          </button>
          <button
            @click="$emit('close')"
            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-gray-300 dark:border-gray-500 dark:hover:bg-gray-500"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

// Props
interface User {
  id: string
  name: string
  email: string
  avatar?: string
  role: 'admin' | 'user' | 'moderator'
  status: 'active' | 'pending' | 'suspended'
  transcriptionsCount: number
  lastLogin: string | null
  createdAt: string
}

const props = defineProps<{
  user: User
}>()

// Events
const emit = defineEmits<{
  close: []
}>()

// Data
const adminNotes = ref('')
const notesSaving = ref(false)
const userActivity = ref<any[]>([])
const userPermissions = ref<any[]>([])

// Methods
const getUserInitials = (name: string): string => {
  return name.split(' ').map(word => word[0]).join('').toUpperCase().slice(0, 2)
}

const getRoleLabel = (role: string): string => {
  const labels: Record<string, string> = {
    'admin': 'Administrateur',
    'user': 'Utilisateur',
    'moderator': 'Modérateur'
  }
  return labels[role] || role
}

const getRoleBadgeClass = (role: string): string => {
  const classes: Record<string, string> = {
    'admin': 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    'moderator': 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
    'user': 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
  }
  return classes[role] || classes.user
}

const getStatusLabel = (status: string): string => {
  const labels: Record<string, string> = {
    'active': 'Actif',
    'pending': 'En attente',
    'suspended': 'Suspendu'
  }
  return labels[status] || status
}

const getStatusBadgeClass = (status: string): string => {
  const classes: Record<string, string> = {
    'active': 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    'pending': 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
    'suspended': 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
  }
  return classes[status] || classes.pending
}

const getStatusDotClass = (status: string): string => {
  const classes: Record<string, string> = {
    'active': 'w-2 h-2 bg-green-400 rounded-full mr-1.5',
    'pending': 'w-2 h-2 bg-yellow-400 rounded-full mr-1.5',
    'suspended': 'w-2 h-2 bg-red-400 rounded-full mr-1.5'
  }
  return classes[status] || classes.pending
}

const getActivityIconClass = (type: string): string => {
  const classes: Record<string, string> = {
    'login': 'w-6 h-6 bg-green-100 text-green-600 rounded-full flex items-center justify-center',
    'transcription': 'w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center',
    'profile': 'w-6 h-6 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center'
  }
  return classes[type] || 'w-6 h-6 bg-gray-100 text-gray-600 rounded-full flex items-center justify-center'
}

const formatDate = (dateString: string, format = 'full'): string => {
  const date = new Date(dateString)
  
  if (format === 'relative') {
    const now = new Date()
    const diff = now.getTime() - date.getTime()
    const days = Math.floor(diff / (1000 * 60 * 60 * 24))
    
    if (days === 0) return 'Aujourd\'hui'
    if (days === 1) return 'Hier'
    if (days < 7) return `Il y a ${days} jours`
    if (days < 30) return `Il y a ${Math.floor(days / 7)} semaines`
    if (days < 365) return `Il y a ${Math.floor(days / 30)} mois`
    return `Il y a ${Math.floor(days / 365)} ans`
  }
  
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const saveNotes = async () => {
  notesSaving.value = true
  try {
    // TODO: Appel API pour sauvegarder les notes
    await new Promise(resolve => setTimeout(resolve, 1000))
    console.log('Notes sauvegardées:', adminNotes.value)
  } catch (error) {
    console.error('Erreur lors de la sauvegarde des notes:', error)
  } finally {
    notesSaving.value = false
  }
}

const loadUserData = async () => {
  try {
    // TODO: Charger les données complètes de l'utilisateur
    
    // Simuler l'activité récente
    userActivity.value = [
      {
        id: '1',
        type: 'login',
        description: 'Connexion à l\'application',
        timestamp: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString()
      },
      {
        id: '2',
        type: 'transcription',
        description: 'Nouvelle transcription créée',
        timestamp: new Date(Date.now() - 6 * 60 * 60 * 1000).toISOString()
      },
      {
        id: '3',
        type: 'profile',
        description: 'Profil mis à jour',
        timestamp: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toISOString()
      }
    ]
    
    // Simuler les permissions
    userPermissions.value = [
      { key: 'manage_users', label: 'Gérer les utilisateurs', granted: props.user.role === 'admin' },
      { key: 'access_analytics', label: 'Accéder aux analytics', granted: props.user.role === 'admin' || props.user.role === 'moderator' },
      { key: 'manage_system', label: 'Gérer le système', granted: props.user.role === 'admin' },
      { key: 'moderate_content', label: 'Modérer le contenu', granted: props.user.role === 'admin' || props.user.role === 'moderator' }
    ]
    
    // Charger les notes admin existantes
    adminNotes.value = 'Aucune note administrative.'
    
  } catch (error) {
    console.error('Erreur lors du chargement des données utilisateur:', error)
  }
}

onMounted(() => {
  loadUserData()
})
</script>

<script lang="ts">
export default {
  name: 'UserProfileModal'
}
</script>