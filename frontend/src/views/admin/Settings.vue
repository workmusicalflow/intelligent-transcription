<template>
  <div class="container-app section-padding max-w-6xl">
    <!-- En-tête -->
    <div class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Paramètres système
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
          Configuration globale de la plateforme
        </p>
      </div>
      
      <div class="flex items-center space-x-3">
        <button
          @click="resetToDefaults"
          class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md transition-colors"
        >
          Réinitialiser
        </button>
        <button
          @click="saveSettings"
          :disabled="!hasChanges || saving"
          class="bg-blue-500 hover:bg-blue-600 disabled:bg-gray-300 text-white px-4 py-2 rounded-md transition-colors flex items-center"
        >
          <svg v-if="saving" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
        </button>
      </div>
    </div>

    <!-- Navigation par onglets -->
    <div class="border-b border-gray-200 dark:border-gray-700 mb-8">
      <nav class="flex space-x-8">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="activeTab = tab.key"
          :class="[
            'py-2 px-1 border-b-2 font-medium text-sm transition-colors',
            activeTab === tab.key
              ? 'border-blue-500 text-blue-600 dark:text-blue-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
          ]"
        >
          <div class="flex items-center">
            <component :is="tab.icon" class="h-5 w-5 mr-2" />
            {{ tab.label }}
          </div>
        </button>
      </nav>
    </div>

    <!-- Contenu des onglets -->
    <div class="space-y-8">
      <!-- Onglet Général -->
      <div v-if="activeTab === 'general'" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Configuration générale</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Nom de la plateforme
              </label>
              <input
                v-model="settings.general.platformName"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                URL de base
              </label>
              <input
                v-model="settings.general.baseUrl"
                type="url"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Email de support
              </label>
              <input
                v-model="settings.general.supportEmail"
                type="email"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Fuseau horaire
              </label>
              <select
                v-model="settings.general.timezone"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              >
                <option value="Europe/Paris">Europe/Paris</option>
                <option value="Europe/London">Europe/London</option>
                <option value="America/New_York">America/New_York</option>
                <option value="Asia/Tokyo">Asia/Tokyo</option>
              </select>
            </div>
          </div>
          
          <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Description de la plateforme
            </label>
            <textarea
              v-model="settings.general.description"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
            ></textarea>
          </div>
        </div>
      </div>

      <!-- Onglet Transcription -->
      <div v-if="activeTab === 'transcription'" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Paramètres de transcription</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Taille maximum de fichier (MB)
              </label>
              <input
                v-model.number="settings.transcription.maxFileSize"
                type="number"
                min="1"
                max="1000"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Durée maximum (minutes)
              </label>
              <input
                v-model.number="settings.transcription.maxDuration"
                type="number"
                min="1"
                max="180"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Langues supportées
              </label>
              <select
                v-model="settings.transcription.supportedLanguages"
                multiple
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                size="4"
              >
                <option value="fr">Français</option>
                <option value="en">Anglais</option>
                <option value="es">Espagnol</option>
                <option value="de">Allemand</option>
                <option value="it">Italien</option>
                <option value="pt">Portugais</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Formats de fichiers autorisés
              </label>
              <div class="space-y-2">
                <div v-for="format in availableFormats" :key="format" class="flex items-center">
                  <input
                    v-model="settings.transcription.allowedFormats"
                    :value="format"
                    type="checkbox"
                    :id="'format-' + format"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  />
                  <label :for="'format-' + format" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                    {{ format.toUpperCase() }}
                  </label>
                </div>
              </div>
            </div>
          </div>
          
          <div class="mt-6 space-y-4">
            <div class="flex items-center">
              <input
                v-model="settings.transcription.autoDelete"
                type="checkbox"
                id="autoDelete"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label for="autoDelete" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                Supprimer automatiquement les fichiers après transcription
              </label>
            </div>
            
            <div class="flex items-center">
              <input
                v-model="settings.transcription.enableYoutube"
                type="checkbox"
                id="enableYoutube"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label for="enableYoutube" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                Activer la transcription YouTube
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Onglet Limites -->
      <div v-if="activeTab === 'limits'" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Limites utilisateur</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Transcriptions par jour (utilisateur standard)
              </label>
              <input
                v-model.number="settings.limits.dailyTranscriptions"
                type="number"
                min="1"
                max="100"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Transcriptions par mois (utilisateur standard)
              </label>
              <input
                v-model.number="settings.limits.monthlyTranscriptions"
                type="number"
                min="1"
                max="1000"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Stockage maximum par utilisateur (GB)
              </label>
              <input
                v-model.number="settings.limits.storageQuota"
                type="number"
                min="1"
                max="100"
                step="0.1"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Nombre maximum d'utilisateurs simultanés
              </label>
              <input
                v-model.number="settings.limits.maxConcurrentUsers"
                type="number"
                min="1"
                max="10000"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              />
            </div>
          </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Limites système</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Timeout de transcription (minutes)
              </label>
              <input
                v-model.number="settings.limits.transcriptionTimeout"
                type="number"
                min="1"
                max="60"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Taille maximum du cache (GB)
              </label>
              <input
                v-model.number="settings.limits.maxCacheSize"
                type="number"
                min="1"
                max="1000"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Onglet Sécurité -->
      <div v-if="activeTab === 'security'" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Sécurité et authentification</h3>
          
          <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Durée de session (heures)
                </label>
                <input
                  v-model.number="settings.security.sessionDuration"
                  type="number"
                  min="1"
                  max="168"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Tentatives de connexion maximum
                </label>
                <input
                  v-model.number="settings.security.maxLoginAttempts"
                  type="number"
                  min="3"
                  max="10"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                />
              </div>
            </div>
            
            <div class="space-y-4">
              <div class="flex items-center">
                <input
                  v-model="settings.security.requireEmailVerification"
                  type="checkbox"
                  id="requireEmailVerification"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <label for="requireEmailVerification" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                  Exiger la vérification email pour les nouveaux comptes
                </label>
              </div>
              
              <div class="flex items-center">
                <input
                  v-model="settings.security.enableTwoFactor"
                  type="checkbox"
                  id="enableTwoFactor"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <label for="enableTwoFactor" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                  Activer l'authentification à deux facteurs
                </label>
              </div>
              
              <div class="flex items-center">
                <input
                  v-model="settings.security.enableRateLimit"
                  type="checkbox"
                  id="enableRateLimit"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <label for="enableRateLimit" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                  Activer la limitation de débit (rate limiting)
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Onglet Maintenance -->
      <div v-if="activeTab === 'maintenance'" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Mode maintenance</h3>
          
          <div class="space-y-6">
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">
                    Mode maintenance actuel
                  </h3>
                  <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-400">
                    <p>Le mode maintenance est actuellement <span class="font-semibold">{{ settings.maintenance.enabled ? 'activé' : 'désactivé' }}</span></p>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="flex items-center">
              <input
                v-model="settings.maintenance.enabled"
                type="checkbox"
                id="maintenanceEnabled"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label for="maintenanceEnabled" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                Activer le mode maintenance
              </label>
            </div>
            
            <div v-if="settings.maintenance.enabled">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Message de maintenance
              </label>
              <textarea
                v-model="settings.maintenance.message"
                rows="4"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="Message affiché aux utilisateurs pendant la maintenance..."
              ></textarea>
            </div>
          </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Sauvegarde automatique</h3>
          
          <div class="space-y-4">
            <div class="flex items-center">
              <input
                v-model="settings.backup.enabled"
                type="checkbox"
                id="backupEnabled"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label for="backupEnabled" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                Activer les sauvegardes automatiques
              </label>
            </div>
            
            <div v-if="settings.backup.enabled" class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Fréquence de sauvegarde
                </label>
                <select
                  v-model="settings.backup.frequency"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                >
                  <option value="daily">Quotidienne</option>
                  <option value="weekly">Hebdomadaire</option>
                  <option value="monthly">Mensuelle</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Nombre de sauvegardes à conserver
                </label>
                <input
                  v-model.number="settings.backup.retention"
                  type="number"
                  min="1"
                  max="30"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Onglet API -->
      <div v-if="activeTab === 'api'" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Configuration API</h3>
          
          <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Clé API OpenAI
                </label>
                <div class="relative">
                  <input
                    v-model="settings.api.openaiApiKey"
                    :type="showApiKey ? 'text' : 'password'"
                    class="w-full px-3 py-2 pr-10 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                    placeholder="sk-..."
                  />
                  <button
                    @click="showApiKey = !showApiKey"
                    type="button"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center"
                  >
                    <svg v-if="showApiKey" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                    </svg>
                    <svg v-else class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                  </button>
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Organisation OpenAI
                </label>
                <input
                  v-model="settings.api.openaiOrganization"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="org-..."
                />
              </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Limite de requêtes API par minute
                </label>
                <input
                  v-model.number="settings.api.rateLimitPerMinute"
                  type="number"
                  min="1"
                  max="1000"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Timeout des requêtes (secondes)
                </label>
                <input
                  v-model.number="settings.api.requestTimeout"
                  type="number"
                  min="10"
                  max="300"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                />
              </div>
            </div>
            
            <div class="space-y-4">
              <div class="flex items-center">
                <input
                  v-model="settings.api.enableCaching"
                  type="checkbox"
                  id="enableCaching"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <label for="enableCaching" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                  Activer la mise en cache des réponses API
                </label>
              </div>
              
              <div class="flex items-center">
                <input
                  v-model="settings.api.logRequests"
                  type="checkbox"
                  id="logRequests"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <label for="logRequests" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                  Enregistrer les logs des requêtes API
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useUIStore } from '@/stores/ui'

const uiStore = useUIStore()

// État
const activeTab = ref('general')
const saving = ref(false)
const showApiKey = ref(false)

// Configuration des onglets
const tabs = [
  {
    key: 'general',
    label: 'Général',
    icon: 'SettingsIcon'
  },
  {
    key: 'transcription',
    label: 'Transcription',
    icon: 'MicrophoneIcon'
  },
  {
    key: 'limits',
    label: 'Limites',
    icon: 'ShieldCheckIcon'
  },
  {
    key: 'security',
    label: 'Sécurité',
    icon: 'LockClosedIcon'
  },
  {
    key: 'maintenance',
    label: 'Maintenance',
    icon: 'CogIcon'
  },
  {
    key: 'api',
    label: 'API',
    icon: 'CodeIcon'
  }
]

// Formats de fichiers disponibles
const availableFormats = ['mp3', 'wav', 'flac', 'aac', 'm4a', 'ogg', 'wma']

// Paramètres
const settings = reactive({
  general: {
    platformName: 'Transcription AI',
    baseUrl: 'https://transcription.example.com',
    supportEmail: 'support@example.com',
    timezone: 'Europe/Paris',
    description: 'Plateforme de transcription automatique alimentée par l\'IA'
  },
  transcription: {
    maxFileSize: 500,
    maxDuration: 120,
    supportedLanguages: ['fr', 'en', 'es'],
    allowedFormats: ['mp3', 'wav', 'flac'],
    autoDelete: false,
    enableYoutube: true
  },
  limits: {
    dailyTranscriptions: 10,
    monthlyTranscriptions: 100,
    storageQuota: 5,
    maxConcurrentUsers: 1000,
    transcriptionTimeout: 30,
    maxCacheSize: 50
  },
  security: {
    sessionDuration: 24,
    maxLoginAttempts: 5,
    requireEmailVerification: true,
    enableTwoFactor: false,
    enableRateLimit: true
  },
  maintenance: {
    enabled: false,
    message: 'Le site est actuellement en maintenance. Nous serons de retour très bientôt.'
  },
  backup: {
    enabled: true,
    frequency: 'daily',
    retention: 7
  },
  api: {
    openaiApiKey: '',
    openaiOrganization: '',
    rateLimitPerMinute: 60,
    requestTimeout: 120,
    enableCaching: true,
    logRequests: false
  }
})

// Paramètres originaux pour détecter les changements
const originalSettings = ref({})

// Computed
const hasChanges = computed(() => {
  return JSON.stringify(settings) !== JSON.stringify(originalSettings.value)
})

// Méthodes
const loadSettings = async () => {
  try {
    // TODO: Charger les paramètres depuis l'API
    // Simulation de chargement
    await new Promise(resolve => setTimeout(resolve, 500))
    
    // Copier les paramètres actuels comme référence
    originalSettings.value = JSON.parse(JSON.stringify(settings))
    
    console.log('Paramètres chargés')
  } catch (error) {
    console.error('Erreur lors du chargement des paramètres:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur de chargement',
      message: 'Impossible de charger les paramètres système'
    })
  }
}

const saveSettings = async () => {
  if (!hasChanges.value) return
  
  saving.value = true
  try {
    // TODO: Sauvegarder les paramètres via l'API
    await new Promise(resolve => setTimeout(resolve, 1500))
    
    // Mettre à jour la référence
    originalSettings.value = JSON.parse(JSON.stringify(settings))
    
    uiStore.showNotification({
      type: 'success',
      title: 'Paramètres sauvegardés',
      message: 'La configuration système a été mise à jour avec succès'
    })
    
    console.log('Paramètres sauvegardés:', settings)
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur de sauvegarde',
      message: 'Impossible de sauvegarder les paramètres'
    })
  } finally {
    saving.value = false
  }
}

const resetToDefaults = () => {
  if (confirm('Êtes-vous sûr de vouloir réinitialiser tous les paramètres aux valeurs par défaut ?')) {
    // Réinitialiser aux valeurs par défaut
    Object.assign(settings.general, {
      platformName: 'Transcription AI',
      baseUrl: 'https://transcription.example.com',
      supportEmail: 'support@example.com',
      timezone: 'Europe/Paris',
      description: 'Plateforme de transcription automatique alimentée par l\'IA'
    })
    
    Object.assign(settings.transcription, {
      maxFileSize: 500,
      maxDuration: 120,
      supportedLanguages: ['fr', 'en', 'es'],
      allowedFormats: ['mp3', 'wav', 'flac'],
      autoDelete: false,
      enableYoutube: true
    })
    
    Object.assign(settings.limits, {
      dailyTranscriptions: 10,
      monthlyTranscriptions: 100,
      storageQuota: 5,
      maxConcurrentUsers: 1000,
      transcriptionTimeout: 30,
      maxCacheSize: 50
    })
    
    Object.assign(settings.security, {
      sessionDuration: 24,
      maxLoginAttempts: 5,
      requireEmailVerification: true,
      enableTwoFactor: false,
      enableRateLimit: true
    })
    
    Object.assign(settings.maintenance, {
      enabled: false,
      message: 'Le site est actuellement en maintenance. Nous serons de retour très bientôt.'
    })
    
    Object.assign(settings.backup, {
      enabled: true,
      frequency: 'daily',
      retention: 7
    })
    
    Object.assign(settings.api, {
      openaiApiKey: '',
      openaiOrganization: '',
      rateLimitPerMinute: 60,
      requestTimeout: 120,
      enableCaching: true,
      logRequests: false
    })
    
    uiStore.showNotification({
      type: 'info',
      title: 'Paramètres réinitialisés',
      message: 'Tous les paramètres ont été remis aux valeurs par défaut'
    })
  }
}

// Lifecycle
onMounted(() => {
  loadSettings()
})
</script>

<script lang="ts">
export default {
  name: 'AdminSettings'
}
</script>
