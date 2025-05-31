<template>
  <div class="relative">
    <button
      @click="isOpen = !isOpen"
      class="flex items-center space-x-2 p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
    >
      <img
        :src="authStore.user?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(authStore.user?.name || 'User')}&background=3b82f6&color=fff`"
        :alt="authStore.user?.name"
        class="h-8 w-8 rounded-full"
      />
      <ChevronDownIcon class="h-4 w-4 text-gray-400" />
    </button>

    <!-- Dropdown menu -->
    <Transition name="dropdown">
      <div
        v-if="isOpen"
        class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50"
      >
        <div class="py-1">
          <router-link
            to="/profile"
            class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
            @click="isOpen = false"
          >
            <UserIcon class="h-4 w-4 inline mr-2" />
            Profil
          </router-link>
          <router-link
            to="/settings"
            class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
            @click="isOpen = false"
          >
            <CogIcon class="h-4 w-4 inline mr-2" />
            Paramètres
          </router-link>
          <hr class="my-1 border-gray-200 dark:border-gray-600" />
          <button
            @click="handleLogout"
            class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            <ArrowRightOnRectangleIcon class="h-4 w-4 inline mr-2" />
            Déconnexion
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import {
  ChevronDownIcon,
  UserIcon,
  CogIcon,
  ArrowRightOnRectangleIcon
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '@stores/auth'

const authStore = useAuthStore()
const isOpen = ref(false)

const handleLogout = async () => {
  isOpen.value = false
  await authStore.logout()
}
</script>

<script lang="ts">
export default {
  name: 'UserDropdown'
}
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>