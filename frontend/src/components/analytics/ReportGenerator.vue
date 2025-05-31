<template>
  <div class="space-y-6">
    <!-- Report configuration form -->
    <form @submit.prevent="generateReport" class="space-y-6">
      <!-- Report type -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Type de rapport
        </label>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div
            v-for="type in reportTypes"
            :key="type.id"
            @click="form.type = type.id"
            :class="[
              'relative rounded-lg border p-4 cursor-pointer transition-colors',
              form.type === type.id
                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'
            ]"
          >
            <div class="flex items-center space-x-3">
              <component :is="type.icon" class="h-6 w-6 text-blue-500" />
              <div>
                <div class="font-medium text-gray-900 dark:text-white">
                  {{ type.name }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                  {{ type.description }}
                </div>
              </div>
            </div>
            <div
              v-if="form.type === type.id"
              class="absolute top-2 right-2"
            >
              <CheckCircleIcon class="h-5 w-5 text-blue-500" />
            </div>
          </div>
        </div>
      </div>
      
      <!-- Time period -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Période
          </label>
          <select
            v-model="form.period"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          >
            <option value="7d">7 derniers jours</option>
            <option value="30d">30 derniers jours</option>
            <option value="90d">3 derniers mois</option>
            <option value="1y">12 derniers mois</option>
            <option value="custom">Période personnalisée</option>
          </select>
        </div>
        
        <!-- Custom date range -->
        <div v-if="form.period === 'custom'" class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Date de début
            </label>
            <input
              v-model="form.startDate"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Date de fin
            </label>
            <input
              v-model="form.endDate"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            />
          </div>
        </div>
      </div>
      
      <!-- Filters -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Filtres
        </label>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <!-- Language filter -->
          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
              Langues
            </label>
            <select
              v-model="form.filters.languages"
              multiple
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="fr">Français</option>
              <option value="en">Anglais</option>
              <option value="es">Espagnol</option>
              <option value="de">Allemand</option>
              <option value="it">Italien</option>
            </select>
          </div>
          
          <!-- Status filter -->
          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
              Statuts
            </label>
            <select
              v-model="form.filters.statuses"
              multiple
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="completed">Terminé</option>
              <option value="failed">Échoué</option>
              <option value="processing">En cours</option>
            </select>
          </div>
          
          <!-- User filter -->
          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
              Utilisateurs
            </label>
            <select
              v-model="form.filters.userIds"
              multiple
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="all">Tous les utilisateurs</option>
              <!-- Users would be loaded dynamically -->
            </select>
          </div>
        </div>
      </div>
      
      <!-- Report sections -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
          Sections à inclure
        </label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <label
            v-for="section in reportSections"
            :key="section.id"
            class="relative flex items-start"
          >
            <div class="flex items-center h-5">
              <input
                v-model="form.sections"
                :value="section.id"
                type="checkbox"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
              />
            </div>
            <div class="ml-3 text-sm">
              <div class="font-medium text-gray-700 dark:text-gray-300">
                {{ section.name }}
              </div>
              <div class="text-gray-500 dark:text-gray-400">
                {{ section.description }}
              </div>
            </div>
          </label>
        </div>
      </div>
      
      <!-- Output format -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Format de sortie
        </label>
        <div class="flex space-x-4">
          <label
            v-for="format in outputFormats"
            :key="format.id"
            class="flex items-center"
          >
            <input
              v-model="form.format"
              :value="format.id"
              type="radio"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600"
            />
            <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ format.name }}
            </span>
          </label>
        </div>
      </div>
      
      <!-- Advanced options -->
      <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
        <button
          type="button"
          @click="showAdvanced = !showAdvanced"
          class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
        >
          <component :is="showAdvanced ? ChevronUpIcon : ChevronDownIcon" class="h-4 w-4 mr-1" />
          Options avancées
        </button>
        
        <div v-if="showAdvanced" class="mt-4 space-y-4">
          <!-- Email delivery -->
          <div class="flex items-center">
            <input
              v-model="form.emailDelivery"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
            />
            <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">
              Envoyer par email
            </label>
          </div>
          
          <!-- Email addresses -->
          <div v-if="form.emailDelivery">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Adresses email (séparées par des virgules)
            </label>
            <input
              v-model="form.emailAddresses"
              type="email"
              multiple
              placeholder="email1@example.com, email2@example.com"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            />
          </div>
          
          <!-- Include raw data -->
          <div class="flex items-center">
            <input
              v-model="form.includeRawData"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
            />
            <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">
              Inclure les données brutes
            </label>
          </div>
        </div>
      </div>
      
      <!-- Actions -->
      <div class="flex justify-end space-x-3 pt-6">
        <Button
          type="button"
          variant="secondary"
          @click="resetForm"
          :disabled="loading"
        >
          Réinitialiser
        </Button>
        <Button
          type="submit"
          :loading="loading"
          :disabled="!isFormValid"
        >
          <DocumentIcon class="h-4 w-4 mr-2" />
          Générer le rapport
        </Button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import Button from '@/components/ui/Button.vue'

interface ReportForm {
  type: string
  period: string
  startDate: string
  endDate: string
  filters: {
    languages: string[]
    statuses: string[]
    userIds: string[]
  }
  sections: string[]
  format: string
  emailDelivery: boolean
  emailAddresses: string
  includeRawData: boolean
}

interface Props {
  loading?: boolean
}

interface Emits {
  (e: 'generate', config: ReportForm): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const emit = defineEmits<Emits>()

// State
const showAdvanced = ref(false)

const form = reactive<ReportForm>({
  type: 'overview',
  period: '30d',
  startDate: '',
  endDate: '',
  filters: {
    languages: [],
    statuses: [],
    userIds: []
  },
  sections: ['statistics', 'usage', 'costs'],
  format: 'pdf',
  emailDelivery: false,
  emailAddresses: '',
  includeRawData: false
})

// Report configuration
const reportTypes = [
  {
    id: 'overview',
    name: 'Vue d\'ensemble',
    description: 'Rapport général d\'activité',
    icon: ChartBarIcon
  },
  {
    id: 'performance',
    name: 'Performance',
    description: 'Métriques de performance détaillées',
    icon: LightningBoltIcon
  },
  {
    id: 'costs',
    name: 'Coûts',
    description: 'Analyse financière complète',
    icon: CurrencyEuroIcon
  },
  {
    id: 'custom',
    name: 'Personnalisé',
    description: 'Rapport sur mesure',
    icon: CogIcon
  }
]

const reportSections = [
  {
    id: 'statistics',
    name: 'Statistiques générales',
    description: 'Métriques clés et tendances'
  },
  {
    id: 'usage',
    name: 'Utilisation',
    description: 'Données d\'utilisation détaillées'
  },
  {
    id: 'costs',
    name: 'Coûts',
    description: 'Analyse des coûts et projections'
  },
  {
    id: 'performance',
    name: 'Performance',
    description: 'Temps de traitement et qualité'
  },
  {
    id: 'errors',
    name: 'Erreurs',
    description: 'Journal des erreurs et incidents'
  },
  {
    id: 'users',
    name: 'Utilisateurs',
    description: 'Activité par utilisateur'
  },
  {
    id: 'languages',
    name: 'Langues',
    description: 'Répartition par langue'
  },
  {
    id: 'optimization',
    name: 'Optimisations',
    description: 'Recommandations d\'amélioration'
  }
]

const outputFormats = [
  { id: 'pdf', name: 'PDF' },
  { id: 'excel', name: 'Excel' },
  { id: 'csv', name: 'CSV' },
  { id: 'json', name: 'JSON' }
]

// Computed
const isFormValid = computed(() => {
  return form.type && 
         form.period && 
         form.sections.length > 0 && 
         form.format &&
         (form.period !== 'custom' || (form.startDate && form.endDate))
})

// Methods
function generateReport() {
  if (!isFormValid.value) return
  emit('generate', { ...form })
}

function resetForm() {
  Object.assign(form, {
    type: 'overview',
    period: '30d',
    startDate: '',
    endDate: '',
    filters: {
      languages: [],
      statuses: [],
      userIds: []
    },
    sections: ['statistics', 'usage', 'costs'],
    format: 'pdf',
    emailDelivery: false,
    emailAddresses: '',
    includeRawData: false
  })
  showAdvanced.value = false
}

// Icons
const ChartBarIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
    </svg>
  `
}

const LightningBoltIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
    </svg>
  `
}

const CurrencyEuroIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1m3-9v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2h8a2 2 0 012 2z"></path>
    </svg>
  `
}

const CogIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
    </svg>
  `
}

const CheckCircleIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
  `
}

const ChevronUpIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
    </svg>
  `
}

const ChevronDownIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
    </svg>
  `
}

const DocumentIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
    </svg>
  `
}
</script>