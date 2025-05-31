<template>
  <div class="container-app section-padding max-w-7xl">
    <!-- En-tête avec actions -->
    <div class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Gestion des utilisateurs
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
          Gérer les comptes utilisateurs et leurs permissions
        </p>
      </div>
      
      <div class="flex items-center space-x-3">
        <button
          @click="showInviteModal = true"
          class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md transition-colors flex items-center"
        >
          <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Inviter un utilisateur
        </button>
        <button
          @click="exportUsers"
          :disabled="exporting"
          class="bg-gray-500 hover:bg-gray-600 disabled:bg-gray-300 text-white px-4 py-2 rounded-md transition-colors flex items-center"
        >
          <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          {{ exporting ? 'Export...' : 'Exporter' }}
        </button>
      </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
              </svg>
            </div>
          </div>
          <div class="ml-5 w-0 flex-1">
            <dl>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                Utilisateurs totaux
              </dt>
              <dd class="text-lg font-medium text-gray-900 dark:text-white">
                {{ stats.total || 0 }}
              </dd>
            </dl>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
          <div class="ml-5 w-0 flex-1">
            <dl>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                Utilisateurs actifs
              </dt>
              <dd class="text-lg font-medium text-gray-900 dark:text-white">
                {{ stats.active || 0 }}
              </dd>
            </dl>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
          <div class="ml-5 w-0 flex-1">
            <dl>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                En attente
              </dt>
              <dd class="text-lg font-medium text-gray-900 dark:text-white">
                {{ stats.pending || 0 }}
              </dd>
            </dl>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
              </svg>
            </div>
          </div>
          <div class="ml-5 w-0 flex-1">
            <dl>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                Suspendus
              </dt>
              <dd class="text-lg font-medium text-gray-900 dark:text-white">
                {{ stats.suspended || 0 }}
              </dd>
            </dl>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Recherche -->
        <div class="md:col-span-2">
          <div class="relative">
            <input
              v-model="filters.search"
              @input="debouncedSearch"
              type="text"
              placeholder="Rechercher par nom, email ou ID..."
              class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
            />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- Filtre par rôle -->
        <div>
          <select
            v-model="filters.role"
            @change="applyFilters"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="">Tous les rôles</option>
            <option value="admin">Administrateur</option>
            <option value="user">Utilisateur</option>
            <option value="moderator">Modérateur</option>
          </select>
        </div>

        <!-- Filtre par statut -->
        <div>
          <select
            v-model="filters.status"
            @change="applyFilters"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="">Tous les statuts</option>
            <option value="active">Actif</option>
            <option value="pending">En attente</option>
            <option value="suspended">Suspendu</option>
          </select>
        </div>
      </div>

      <!-- Options de tri -->
      <div class="flex flex-wrap items-center justify-between mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center space-x-4">
          <span class="text-sm text-gray-500 dark:text-gray-400">Trier par :</span>
          <select
            v-model="filters.sortBy"
            @change="applyFilters"
            class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="created_at">Date d'inscription</option>
            <option value="name">Nom</option>
            <option value="email">Email</option>
            <option value="last_login">Dernière connexion</option>
            <option value="transcriptions_count">Nb transcriptions</option>
          </select>
          <button
            @click="toggleSortOrder"
            class="px-2 py-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            :title="filters.sortOrder === 'DESC' ? 'Croissant' : 'Décroissant'"
          >
            <svg class="w-4 h-4 transform transition-transform" :class="{ 'rotate-180': filters.sortOrder === 'ASC' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
          </button>
        </div>

        <div class="flex items-center space-x-2 mt-2 md:mt-0">
          <span class="text-sm text-gray-500 dark:text-gray-400">Par page :</span>
          <select
            v-model="pagination.limit"
            @change="applyFilters"
            class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Table des utilisateurs -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div v-if="loading" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
        <p class="text-gray-500 dark:text-gray-400 mt-2">Chargement des utilisateurs...</p>
      </div>

      <div v-else-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 m-6">
        <div class="flex items-center">
          <svg class="h-5 w-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <span class="text-red-700 dark:text-red-300">{{ error }}</span>
        </div>
      </div>

      <div v-else-if="users.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mt-4 mb-2">
          {{ hasFilters ? 'Aucun utilisateur trouvé' : 'Aucun utilisateur' }}
        </h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6">
          {{ hasFilters 
            ? 'Essayez de modifier vos critères de recherche' 
            : 'Commencez par inviter des utilisateurs' 
          }}
        </p>
        <button 
          v-if="hasFilters"
          @click="clearFilters"
          class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md transition-colors"
        >
          Effacer les filtres
        </button>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Utilisateur
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Rôle
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Statut
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Transcriptions
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Dernière connexion
              </th>
              <th scope="col" class="relative px-6 py-3">
                <span class="sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <img v-if="user.avatar" class="h-10 w-10 rounded-full" :src="user.avatar" :alt="user.name" />
                    <div v-else class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ getUserInitials(user.name) }}
                      </span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ user.name }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ user.email }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getRoleBadgeClass(user.role)">
                  {{ getRoleLabel(user.role) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusBadgeClass(user.status)">
                  <span :class="getStatusDotClass(user.status)"></span>
                  {{ getStatusLabel(user.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                {{ user.transcriptionsCount || 0 }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ user.lastLogin ? formatDate(user.lastLogin) : 'Jamais' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="relative">
                  <button
                    @click="toggleUserMenu(user.id)"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1"
                  >
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                    </svg>
                  </button>
                  
                  <div
                    v-if="openMenuId === user.id"
                    class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-10"
                    @click.stop
                  >
                    <button
                      @click="viewUser(user)"
                      class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                      <svg class="h-4 w-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                      </svg>
                      Voir le profil
                    </button>
                    <button
                      @click="editUser(user)"
                      class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                      <svg class="h-4 w-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                      </svg>
                      Modifier
                    </button>
                    <button
                      v-if="user.status === 'pending'"
                      @click="approveUser(user)"
                      class="block w-full text-left px-4 py-2 text-sm text-green-700 dark:text-green-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                      <svg class="h-4 w-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                      Approuver
                    </button>
                    <button
                      v-if="user.status === 'active'"
                      @click="suspendUser(user)"
                      class="block w-full text-left px-4 py-2 text-sm text-yellow-700 dark:text-yellow-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                      <svg class="h-4 w-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                      </svg>
                      Suspendre
                    </button>
                    <button
                      v-if="user.status === 'suspended'"
                      @click="activateUser(user)"
                      class="block w-full text-left px-4 py-2 text-sm text-green-700 dark:text-green-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                      <svg class="h-4 w-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                      Activer
                    </button>
                    <hr class="border-gray-200 dark:border-gray-700">
                    <button
                      @click="deleteUser(user)"
                      class="block w-full text-left px-4 py-2 text-sm text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20"
                    >
                      <svg class="h-4 w-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                      </svg>
                      Supprimer
                    </button>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.totalPages > 1" class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <p class="text-sm text-gray-700 dark:text-gray-300">
              Affichage {{ ((pagination.currentPage - 1) * pagination.limit) + 1 }} à 
              {{ Math.min(pagination.currentPage * pagination.limit, pagination.totalCount) }} 
              sur {{ pagination.totalCount }} utilisateurs
            </p>
          </div>
          
          <div class="flex items-center space-x-2">
            <button
              @click="goToPage(pagination.currentPage - 1)"
              :disabled="!pagination.hasPrev"
              class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300"
            >
              Précédent
            </button>
            
            <div class="flex space-x-1">
              <button
                v-for="page in visiblePages"
                :key="page"
                @click="goToPage(page)"
                :class="[
                  'px-3 py-2 text-sm border rounded-md',
                  page === pagination.currentPage
                    ? 'bg-blue-500 text-white border-blue-500'
                    : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                ]"
              >
                {{ page }}
              </button>
            </div>
            
            <button
              @click="goToPage(pagination.currentPage + 1)"
              :disabled="!pagination.hasNext"
              class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300"
            >
              Suivant
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal d'invitation -->
    <InviteUserModal
      v-if="showInviteModal"
      @close="showInviteModal = false"
      @invite="handleInviteUser"
    />

    <!-- Modal d'édition -->
    <EditUserModal
      v-if="showEditModal && selectedUser"
      :user="selectedUser"
      @close="showEditModal = false"
      @save="handleEditUser"
    />

    <!-- Modal de profil -->
    <UserProfileModal
      v-if="showProfileModal && selectedUser"
      :user="selectedUser"
      @close="showProfileModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useUIStore } from '@/stores/ui'
import InviteUserModal from '@/components/admin/InviteUserModal.vue'
import EditUserModal from '@/components/admin/EditUserModal.vue'
import UserProfileModal from '@/components/admin/UserProfileModal.vue'

const uiStore = useUIStore()

// Types
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

interface UserStats {
  total: number
  active: number
  pending: number
  suspended: number
}

// État principal
const loading = ref(false)
const error = ref('')
const users = ref<User[]>([])
const stats = ref<UserStats>({
  total: 0,
  active: 0,
  pending: 0,
  suspended: 0
})

// Modals
const showInviteModal = ref(false)
const showEditModal = ref(false)
const showProfileModal = ref(false)
const selectedUser = ref<User | null>(null)

// Menu et export
const openMenuId = ref<string | null>(null)
const exporting = ref(false)

// Filtres et pagination
const filters = ref({
  search: '',
  role: '',
  status: '',
  sortBy: 'created_at',
  sortOrder: 'DESC'
})

const pagination = ref({
  currentPage: 1,
  totalPages: 1,
  totalCount: 0,
  limit: 25,
  hasNext: false,
  hasPrev: false
})

// Computed
const hasFilters = computed(() => {
  return filters.value.search !== '' || 
         filters.value.role !== '' || 
         filters.value.status !== ''
})

const visiblePages = computed(() => {
  const total = pagination.value.totalPages
  const current = pagination.value.currentPage
  const pages: number[] = []
  
  let start = Math.max(1, current - 2)
  let end = Math.min(total, current + 2)
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
})

// Fonctions utilitaires
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

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Fonctions de chargement
const loadUsers = async () => {
  loading.value = true
  error.value = ''
  
  try {
    // TODO: Remplacer par un vrai appel API
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Données simulées
    const mockUsers: User[] = [
      {
        id: '1',
        name: 'Marie Dubois',
        email: 'marie.dubois@example.com',
        role: 'admin',
        status: 'active',
        transcriptionsCount: 45,
        lastLogin: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
        createdAt: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString()
      },
      {
        id: '2',
        name: 'Pierre Martin',
        email: 'pierre.martin@example.com',
        role: 'user',
        status: 'active',
        transcriptionsCount: 12,
        lastLogin: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toISOString(),
        createdAt: new Date(Date.now() - 15 * 24 * 60 * 60 * 1000).toISOString()
      },
      {
        id: '3',
        name: 'Sophie Bernard',
        email: 'sophie.bernard@example.com',
        role: 'moderator',
        status: 'pending',
        transcriptionsCount: 0,
        lastLogin: null,
        createdAt: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toISOString()
      },
      {
        id: '4',
        name: 'Thomas Leroy',
        email: 'thomas.leroy@example.com',
        role: 'user',
        status: 'suspended',
        transcriptionsCount: 8,
        lastLogin: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString(),
        createdAt: new Date(Date.now() - 60 * 24 * 60 * 60 * 1000).toISOString()
      },
      {
        id: '5',
        name: 'Élise Moreau',
        email: 'elise.moreau@example.com',
        role: 'user',
        status: 'active',
        transcriptionsCount: 23,
        lastLogin: new Date(Date.now() - 3 * 60 * 60 * 1000).toISOString(),
        createdAt: new Date(Date.now() - 45 * 24 * 60 * 60 * 1000).toISOString()
      }
    ]
    
    users.value = mockUsers
    
    // Calculer les statistiques
    stats.value = {
      total: mockUsers.length,
      active: mockUsers.filter(u => u.status === 'active').length,
      pending: mockUsers.filter(u => u.status === 'pending').length,
      suspended: mockUsers.filter(u => u.status === 'suspended').length
    }
    
    // Pagination simulée
    pagination.value = {
      currentPage: 1,
      totalPages: 1,
      totalCount: mockUsers.length,
      limit: 25,
      hasNext: false,
      hasPrev: false
    }
    
  } catch (err: any) {
    error.value = err.message
    console.error('Erreur lors du chargement des utilisateurs:', err)
  } finally {
    loading.value = false
  }
}

// Recherche avec délai
let searchTimeout: NodeJS.Timeout
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    pagination.value.currentPage = 1
    loadUsers()
  }, 500)
}

// Gestion des filtres
const applyFilters = () => {
  pagination.value.currentPage = 1
  loadUsers()
}

const toggleSortOrder = () => {
  filters.value.sortOrder = filters.value.sortOrder === 'DESC' ? 'ASC' : 'DESC'
  applyFilters()
}

const clearFilters = () => {
  filters.value.search = ''
  filters.value.role = ''
  filters.value.status = ''
  filters.value.sortBy = 'created_at'
  filters.value.sortOrder = 'DESC'
  pagination.value.currentPage = 1
  loadUsers()
}

// Navigation pagination
const goToPage = (page: number) => {
  if (page >= 1 && page <= pagination.value.totalPages) {
    pagination.value.currentPage = page
    loadUsers()
  }
}

// Gestion du menu
const toggleUserMenu = (userId: string) => {
  openMenuId.value = openMenuId.value === userId ? null : userId
}

const closeMenu = () => {
  openMenuId.value = null
}

// Actions sur les utilisateurs
const viewUser = (user: User) => {
  selectedUser.value = user
  showProfileModal.value = true
  closeMenu()
}

const editUser = (user: User) => {
  selectedUser.value = user
  showEditModal.value = true
  closeMenu()
}

const approveUser = async (user: User) => {
  try {
    // TODO: Appel API pour approuver l'utilisateur
    user.status = 'active'
    
    uiStore.showNotification({
      type: 'success',
      title: 'Utilisateur approuvé',
      message: `${user.name} a été approuvé avec succès`
    })
  } catch (error) {
    console.error('Erreur lors de l\'approbation:', error)
  }
  closeMenu()
}

const suspendUser = async (user: User) => {
  if (confirm(`Êtes-vous sûr de vouloir suspendre ${user.name} ?`)) {
    try {
      // TODO: Appel API pour suspendre l'utilisateur
      user.status = 'suspended'
      
      uiStore.showNotification({
        type: 'warning',
        title: 'Utilisateur suspendu',
        message: `${user.name} a été suspendu`
      })
    } catch (error) {
      console.error('Erreur lors de la suspension:', error)
    }
  }
  closeMenu()
}

const activateUser = async (user: User) => {
  try {
    // TODO: Appel API pour activer l'utilisateur
    user.status = 'active'
    
    uiStore.showNotification({
      type: 'success',
      title: 'Utilisateur activé',
      message: `${user.name} a été activé avec succès`
    })
  } catch (error) {
    console.error('Erreur lors de l\'activation:', error)
  }
  closeMenu()
}

const deleteUser = async (user: User) => {
  if (confirm(`Êtes-vous sûr de vouloir supprimer définitivement ${user.name} ? Cette action est irréversible.`)) {
    try {
      // TODO: Appel API pour supprimer l'utilisateur
      users.value = users.value.filter(u => u.id !== user.id)
      
      uiStore.showNotification({
        type: 'success',
        title: 'Utilisateur supprimé',
        message: `${user.name} a été supprimé définitivement`
      })
    } catch (error) {
      console.error('Erreur lors de la suppression:', error)
    }
  }
  closeMenu()
}

// Export des utilisateurs
const exportUsers = async () => {
  exporting.value = true
  try {
    // TODO: Implémenter l'export réel
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    // Simulation de téléchargement
    const csvContent = 'Name,Email,Role,Status,Transcriptions,Last Login\n' +
      users.value.map(user => [
        user.name,
        user.email,
        user.role,
        user.status,
        user.transcriptionsCount,
        user.lastLogin || 'Jamais'
      ].join(',')).join('\n')
    
    const blob = new Blob([csvContent], { type: 'text/csv' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `utilisateurs_${new Date().toISOString().split('T')[0]}.csv`
    a.click()
    URL.revokeObjectURL(url)
    
    uiStore.showNotification({
      type: 'success',
      title: 'Export terminé',
      message: 'La liste des utilisateurs a été exportée avec succès'
    })
  } catch (error) {
    console.error('Erreur lors de l\'export:', error)
  } finally {
    exporting.value = false
  }
}

// Gestion des modals
const handleInviteUser = async (userData: any) => {
  try {
    // TODO: Appel API pour inviter l'utilisateur
    console.log('Invitation utilisateur:', userData)
    
    uiStore.showNotification({
      type: 'success',
      title: 'Invitation envoyée',
      message: `Une invitation a été envoyée à ${userData.email}`
    })
    
    showInviteModal.value = false
    loadUsers() // Recharger la liste
  } catch (error) {
    console.error('Erreur lors de l\'invitation:', error)
  }
}

const handleEditUser = async (userData: any) => {
  try {
    // TODO: Appel API pour modifier l'utilisateur
    console.log('Modification utilisateur:', userData)
    
    uiStore.showNotification({
      type: 'success',
      title: 'Utilisateur modifié',
      message: 'Les informations ont été mises à jour avec succès'
    })
    
    showEditModal.value = false
    selectedUser.value = null
    loadUsers() // Recharger la liste
  } catch (error) {
    console.error('Erreur lors de la modification:', error)
  }
}

// Fermer le menu quand on clique ailleurs
const handleClickOutside = () => {
  closeMenu()
}

// Lifecycle
onMounted(() => {
  loadUsers()
  document.addEventListener('click', handleClickOutside)
})
</script>

<script lang="ts">
export default {
  name: 'AdminUsers'
}
</script>
