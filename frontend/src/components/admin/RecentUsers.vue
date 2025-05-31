<template>
  <div class="space-y-3">
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 3" :key="i" class="animate-pulse">
        <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
          <div class="w-10 h-10 bg-gray-200 dark:bg-gray-600 rounded-full"></div>
          <div class="flex-1 space-y-1">
            <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/2"></div>
          </div>
          <div class="w-16 h-5 bg-gray-200 dark:bg-gray-600 rounded"></div>
        </div>
      </div>
    </div>
    
    <div v-else-if="!users?.length" class="text-center py-8">
      <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
      </svg>
      <p class="text-gray-500 dark:text-gray-400">Aucun nouvel utilisateur</p>
    </div>
    
    <div v-else class="space-y-3">
      <div
        v-for="user in users"
        :key="user.id"
        class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer"
        @click="$emit('user-click', user)"
      >
        <!-- Avatar -->
        <div class="flex-shrink-0">
          <div
            v-if="user.avatar"
            class="w-10 h-10 rounded-full bg-cover bg-center"
            :style="{ backgroundImage: `url(${user.avatar})` }"
          ></div>
          <div
            v-else
            class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-medium text-sm"
          >
            {{ getInitials(user.name) }}
          </div>
        </div>
        
        <!-- Informations utilisateur -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center space-x-2">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">
              {{ user.name }}
            </h4>
            <span
              :class="[
                'inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium',
                statusClasses[user.status]
              ]"
            >
              {{ statusLabels[user.status] }}
            </span>
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
            {{ user.email }}
          </p>
          <p class="text-xs text-gray-400 dark:text-gray-500">
            Inscrit {{ formatRelativeTime(user.registeredAt) }}
          </p>
        </div>
        
        <!-- Actions -->
        <div class="flex-shrink-0">
          <div class="flex items-center space-x-1">
            <button
              @click.stop="$emit('view-user', user)"
              class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
              title="Voir le profil"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
              </svg>
            </button>
            
            <div class="relative">
              <button
                @click.stop="toggleUserMenu(user.id)"
                class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                title="Actions"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                </svg>
              </button>
              
              <!-- Menu déroulant -->
              <div
                v-if="openMenuId === user.id"
                class="absolute right-0 top-full mt-1 w-32 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-10"
                @click.stop
              >
                <button
                  @click="$emit('edit-user', user)"
                  class="block w-full text-left px-3 py-2 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                  Modifier
                </button>
                <button
                  v-if="user.status === 'pending'"
                  @click="$emit('approve-user', user)"
                  class="block w-full text-left px-3 py-2 text-xs text-green-700 dark:text-green-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                  Approuver
                </button>
                <button
                  v-if="user.status === 'active'"
                  @click="$emit('suspend-user', user)"
                  class="block w-full text-left px-3 py-2 text-xs text-orange-700 dark:text-orange-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                  Suspendre
                </button>
                <button
                  @click="$emit('delete-user', user)"
                  class="block w-full text-left px-3 py-2 text-xs text-red-700 dark:text-red-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                  Supprimer
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

interface RecentUser {
  id: string
  name: string
  email: string
  avatar?: string
  registeredAt: string
  status: 'active' | 'pending' | 'suspended'
}

interface Props {
  users: RecentUser[]
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const emit = defineEmits<{
  'user-click': [user: RecentUser]
  'view-user': [user: RecentUser]
  'edit-user': [user: RecentUser]
  'approve-user': [user: RecentUser]
  'suspend-user': [user: RecentUser]
  'delete-user': [user: RecentUser]
}>()

const openMenuId = ref<string | null>(null)

const statusClasses = {
  active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
  pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
  suspended: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
}

const statusLabels = {
  active: 'Actif',
  pending: 'En attente',
  suspended: 'Suspendu'
}

function getInitials(name: string): string {
  return name
    .split(' ')
    .map(part => part.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

function formatRelativeTime(dateStr: string): string {
  const date = new Date(dateStr)
  const now = new Date()
  const diffInMs = now.getTime() - date.getTime()
  const diffInHours = Math.floor(diffInMs / (1000 * 60 * 60))
  const diffInDays = Math.floor(diffInHours / 24)
  
  if (diffInHours < 1) {
    return 'il y a moins d\'1h'
  } else if (diffInHours < 24) {
    return `il y a ${diffInHours}h`
  } else if (diffInDays === 1) {
    return 'hier'
  } else if (diffInDays < 7) {
    return `il y a ${diffInDays} jours`
  } else {
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit'
    })
  }
}

function toggleUserMenu(userId: string) {
  openMenuId.value = openMenuId.value === userId ? null : userId
}

function closeMenus() {
  openMenuId.value = null
}

// Fermer les menus quand on clique ailleurs
onMounted(() => {
  document.addEventListener('click', closeMenus)
})

onUnmounted(() => {
  document.removeEventListener('click', closeMenus)
})
</script>

<script lang="ts">
export default {
  name: 'RecentUsers'
}
</script>