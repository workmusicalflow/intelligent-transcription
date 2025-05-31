<template>
  <div class="space-y-6">
    <!-- Header with filters -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
      <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          Historique des rapports
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Consultez et téléchargez vos rapports précédents
        </p>
      </div>
      
      <!-- Filters -->
      <div class="flex items-center space-x-3">
        <!-- Type filter -->
        <select
          v-model="filters.type"
          @change="applyFilters"
          class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
        >
          <option value="">Tous les types</option>
          <option value="overview">Vue d'ensemble</option>
          <option value="performance">Performance</option>
          <option value="costs">Coûts</option>
          <option value="custom">Personnalisé</option>
        </select>
        
        <!-- Period filter -->
        <select
          v-model="filters.period"
          @change="applyFilters"
          class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
        >
          <option value="">Toutes les périodes</option>
          <option value="7d">7 derniers jours</option>
          <option value="30d">30 derniers jours</option>
          <option value="90d">3 derniers mois</option>
        </select>
        
        <!-- Search -->
        <div class="relative">
          <SearchIcon class="h-5 w-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" />
          <input
            v-model="filters.search"
            @input="applyFilters"
            type="text"
            placeholder="Rechercher..."
            class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm w-48"
          />
        </div>
      </div>
    </div>
    
    <!-- Loading state -->
    <div v-if="loading" class="flex items-center justify-center py-8">
      <LoadingSpinner size="lg" />
    </div>
    
    <!-- Error state -->
    <div v-else-if="error" class="flex items-center justify-center py-8 text-red-500 dark:text-red-400">
      <div class="text-center">
        <ExclamationIcon class="h-12 w-12 mx-auto mb-2" />
        <p>Erreur lors du chargement de l'historique</p>
      </div>
    </div>
    
    <!-- Reports table -->
    <div v-else-if="paginatedReports.length > 0" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th 
                scope="col" 
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer"
                @click="sort('name')"
              >
                <div class="flex items-center space-x-1">
                  <span>Nom</span>
                  <component :is="getSortIcon('name')" class="h-4 w-4" />
                </div>
              </th>
              <th 
                scope="col" 
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer"
                @click="sort('type')"
              >
                <div class="flex items-center space-x-1">
                  <span>Type</span>
                  <component :is="getSortIcon('type')" class="h-4 w-4" />
                </div>
              </th>
              <th 
                scope="col" 
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer"
                @click="sort('createdAt')"
              >
                <div class="flex items-center space-x-1">
                  <span>Date de création</span>
                  <component :is="getSortIcon('createdAt')" class="h-4 w-4" />
                </div>
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Statut
              </th>
              <th 
                scope="col" 
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer"
                @click="sort('size')"
              >
                <div class="flex items-center space-x-1">
                  <span>Taille</span>
                  <component :is="getSortIcon('size')" class="h-4 w-4" />
                </div>
              </th>
              <th scope="col" class="relative px-6 py-3">
                <span class="sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="report in paginatedReports"
              :key="report.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <component :is="getReportIcon(report.type)" class="h-5 w-5 text-gray-400 mr-3" />
                  <div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ report.name }}
                    </div>
                    <div v-if="report.description" class="text-xs text-gray-500 dark:text-gray-400">
                      {{ report.description }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                  {{ getReportTypeLabel(report.type) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                {{ formatDate(report.createdAt) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  getStatusBadgeColor(report.status)
                ]">
                  <component :is="getStatusIcon(report.status)" class="h-3 w-3 mr-1" />
                  {{ getStatusLabel(report.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ formatFileSize(report.size) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end space-x-2">
                  <!-- Preview -->
                  <button
                    v-if="report.status === 'completed'"
                    @click="previewReport(report)"
                    class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 p-1"
                    title="Aperçu"
                  >
                    <EyeIcon class="h-4 w-4" />
                  </button>
                  
                  <!-- Download -->
                  <button
                    v-if="report.status === 'completed'"
                    @click="downloadReport(report.id)"
                    class="text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300 p-1"
                    title="Télécharger"
                  >
                    <DownloadIcon class="h-4 w-4" />
                  </button>
                  
                  <!-- Regenerate -->
                  <button
                    @click="regenerateReport(report)"
                    class="text-yellow-600 hover:text-yellow-700 dark:text-yellow-400 dark:hover:text-yellow-300 p-1"
                    title="Régénérer"
                  >
                    <RefreshIcon class="h-4 w-4" />
                  </button>
                  
                  <!-- Delete -->
                  <button
                    @click="confirmDelete(report)"
                    class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 p-1"
                    title="Supprimer"
                  >
                    <TrashIcon class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div v-if="totalPages > 1" class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <p class="text-sm text-gray-700 dark:text-gray-300">
              Affichage de 
              <span class="font-medium">{{ (currentPage - 1) * pageSize + 1 }}</span>
              à 
              <span class="font-medium">{{ Math.min(currentPage * pageSize, filteredReports.length) }}</span>
              sur 
              <span class="font-medium">{{ filteredReports.length }}</span>
              rapports
            </p>
          </div>
          <div class="flex items-center space-x-2">
            <button
              @click="currentPage = Math.max(1, currentPage - 1)"
              :disabled="currentPage === 1"
              class="relative inline-flex items-center px-2 py-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <ChevronLeftIcon class="h-5 w-5" />
            </button>
            
            <span class="text-sm text-gray-700 dark:text-gray-300">
              Page {{ currentPage }} sur {{ totalPages }}
            </span>
            
            <button
              @click="currentPage = Math.min(totalPages, currentPage + 1)"
              :disabled="currentPage === totalPages"
              class="relative inline-flex items-center px-2 py-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <ChevronRightIcon class="h-5 w-5" />
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Empty state -->
    <div v-else class="text-center py-12">
      <DocumentIcon class="h-12 w-12 text-gray-400 mx-auto mb-4" />
      <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
        Aucun rapport trouvé
      </h3>
      <p class="text-gray-500 dark:text-gray-400">
        {{ filters.search || filters.type || filters.period ? 'Aucun rapport ne correspond à vos critères.' : 'Vous n\'avez pas encore généré de rapports.' }}
      </p>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <ConfirmationModal
      v-if="reportToDelete"
      title="Supprimer le rapport"
      :message="`Êtes-vous sûr de vouloir supprimer le rapport '${reportToDelete.name}' ? Cette action est irréversible.`"
      confirm-text="Supprimer"
      confirm-variant="danger"
      @confirm="deleteReport"
      @cancel="reportToDelete = null"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import ConfirmationModal from '@/components/ui/ConfirmationModal.vue'

interface Report {
  id: string
  name: string
  description?: string
  type: string
  period: string
  format: string
  status: 'completed' | 'generating' | 'failed' | 'expired'
  size: number
  createdAt: string
  downloadUrl?: string
  previewUrl?: string
}

interface Props {
  reports: Report[]
  loading?: boolean
  error?: boolean
}

interface Emits {
  (e: 'download', id: string): void
  (e: 'delete', id: string): void
  (e: 'regenerate', report: Report): void
  (e: 'preview', report: Report): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  error: false
})

const emit = defineEmits<Emits>()

// State
const currentPage = ref(1)
const pageSize = ref(10)
const sortField = ref('createdAt')
const sortDirection = ref<'asc' | 'desc'>('desc')
const reportToDelete = ref<Report | null>(null)

const filters = reactive({
  search: '',
  type: '',
  period: ''
})

// Computed
const filteredReports = computed(() => {
  let filtered = [...props.reports]
  
  // Apply search filter
  if (filters.search) {
    const search = filters.search.toLowerCase()
    filtered = filtered.filter(report => 
      report.name.toLowerCase().includes(search) ||
      (report.description && report.description.toLowerCase().includes(search))
    )
  }
  
  // Apply type filter
  if (filters.type) {
    filtered = filtered.filter(report => report.type === filters.type)
  }
  
  // Apply period filter
  if (filters.period) {
    const now = new Date()
    const periodMs = {
      '7d': 7 * 24 * 60 * 60 * 1000,
      '30d': 30 * 24 * 60 * 60 * 1000,
      '90d': 90 * 24 * 60 * 60 * 1000
    }[filters.period] || 0
    
    if (periodMs) {
      const cutoff = new Date(now.getTime() - periodMs)
      filtered = filtered.filter(report => new Date(report.createdAt) >= cutoff)
    }
  }
  
  // Apply sorting
  filtered.sort((a, b) => {
    const aValue = a[sortField.value as keyof Report]
    const bValue = b[sortField.value as keyof Report]
    
    let comparison = 0
    if (aValue < bValue) comparison = -1
    if (aValue > bValue) comparison = 1
    
    return sortDirection.value === 'desc' ? -comparison : comparison
  })
  
  return filtered
})

const totalPages = computed(() => Math.ceil(filteredReports.value.length / pageSize.value))

const paginatedReports = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  return filteredReports.value.slice(start, end)
})

// Methods
function sort(field: string) {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDirection.value = 'desc'
  }
  currentPage.value = 1
}

function getSortIcon(field: string) {
  if (sortField.value !== field) return ChevronUpDownIcon
  return sortDirection.value === 'asc' ? ChevronUpIcon : ChevronDownIcon
}

function applyFilters() {
  currentPage.value = 1
}

function getReportIcon(type: string) {
  const iconMap = {
    overview: ChartBarIcon,
    performance: LightningBoltIcon,
    costs: CurrencyEuroIcon,
    custom: CogIcon
  }
  return iconMap[type] || DocumentIcon
}

function getReportTypeLabel(type: string): string {
  const labelMap = {
    overview: 'Vue d\'ensemble',
    performance: 'Performance',
    costs: 'Coûts',
    custom: 'Personnalisé'
  }
  return labelMap[type] || type
}

function getStatusBadgeColor(status: string): string {
  const colorMap = {
    completed: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
    generating: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
    failed: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
    expired: 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
  }
  return colorMap[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
}

function getStatusIcon(status: string) {
  const iconMap = {
    completed: CheckIcon,
    generating: ClockIcon,
    failed: XIcon,
    expired: ExclamationIcon
  }
  return iconMap[status] || QuestionMarkIcon
}

function getStatusLabel(status: string): string {
  const labelMap = {
    completed: 'Terminé',
    generating: 'En cours',
    failed: 'Échoué',
    expired: 'Expiré'
  }
  return labelMap[status] || status
}

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

function downloadReport(id: string) {
  emit('download', id)
}

function previewReport(report: Report) {
  emit('preview', report)
}

function regenerateReport(report: Report) {
  emit('regenerate', report)
}

function confirmDelete(report: Report) {
  reportToDelete.value = report
}

function deleteReport() {
  if (reportToDelete.value) {
    emit('delete', reportToDelete.value.id)
    reportToDelete.value = null
  }
}

// Icons (keeping them simple due to length constraints)
const SearchIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>` }
const ExclamationIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>` }
const DocumentIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>` }
const ChartBarIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>` }
const LightningBoltIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>` }
const CurrencyEuroIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1m3-9v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2h8a2 2 0 012 2z"></path></svg>` }
const CogIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>` }
const CheckIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>` }
const ClockIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>` }
const XIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>` }
const QuestionMarkIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>` }
const EyeIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>` }
const DownloadIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path></svg>` }
const RefreshIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>` }
const TrashIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>` }
const ChevronUpDownIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>` }
const ChevronUpIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>` }
const ChevronDownIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>` }
const ChevronLeftIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>` }
const ChevronRightIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>` }
</script>