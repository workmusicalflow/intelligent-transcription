<template>
  <div class="space-y-6">
    <!-- Loading state -->
    <div v-if="loading" class="flex items-center justify-center py-8">
      <LoadingSpinner size="lg" />
    </div>
    
    <!-- Error state -->
    <div v-else-if="error" class="flex items-center justify-center py-8 text-red-500 dark:text-red-400">
      <div class="text-center">
        <ExclamationIcon class="h-12 w-12 mx-auto mb-2" />
        <p>Erreur lors du chargement des optimisations</p>
      </div>
    </div>
    
    <!-- Optimization content -->
    <div v-else class="space-y-6">
      <!-- Cost forecast and budget -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Current month projection -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Projection mensuelle
              </h3>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                {{ formatCurrency(data.monthlyProjection || 0) }}
              </p>
              <div class="flex items-center mt-2">
                <component 
                  :is="data.projectionTrend >= 0 ? TrendingUpIcon : TrendingDownIcon"
                  :class="[
                    'h-4 w-4 mr-1',
                    data.projectionTrend >= 0 ? 'text-red-500' : 'text-green-500'
                  ]"
                />
                <span :class="[
                  'text-sm font-medium',
                  data.projectionTrend >= 0 ? 'text-red-600' : 'text-green-600'
                ]">
                  {{ Math.abs(data.projectionTrend || 0) }}% vs mois dernier
                </span>
              </div>
            </div>
            <CalendarIcon class="h-12 w-12 text-blue-500" />
          </div>
        </div>
        
        <!-- Budget status -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Budget mensuel
              </h3>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                {{ formatCurrency(data.monthlyBudget || 0) }}
              </p>
              <div class="flex items-center mt-2">
                <div :class="[
                  'text-sm font-medium',
                  getBudgetStatusColor()
                ]">
                  {{ getBudgetStatus() }}
                </div>
              </div>
            </div>
            <component :is="getBudgetIcon()" :class="['h-12 w-12', getBudgetIconColor()]" />
          </div>
          
          <!-- Budget progress bar -->
          <div class="mt-4">
            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
              <span>Utilisé</span>
              <span>{{ budgetUsedPercentage.toFixed(1) }}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
              <div 
                :class="[
                  'h-2 rounded-full transition-all duration-300',
                  getBudgetProgressColor()
                ]"
                :style="{ width: `${Math.min(budgetUsedPercentage, 100)}%` }"
              ></div>
            </div>
          </div>
        </div>
        
        <!-- Potential savings -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Économies potentielles
              </h3>
              <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">
                {{ formatCurrency(data.potentialSavings || 0) }}
              </p>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                Par mois
              </div>
            </div>
            <CurrencyEuroIcon class="h-12 w-12 text-green-500" />
          </div>
        </div>
      </div>
      
      <!-- Optimization recommendations -->
      <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Recommandations d'optimisation
          </h3>
        </div>
        
        <div class="p-6 space-y-4">
          <div
            v-for="recommendation in data.recommendations || []"
            :key="recommendation.id"
            class="flex items-start space-x-4 p-4 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
          >
            <div :class="[
              'flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center',
              getRecommendationColor(recommendation.priority)
            ]">
              <component :is="getRecommendationIcon(recommendation.type)" class="h-5 w-5 text-white" />
            </div>
            
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <h4 class="text-base font-medium text-gray-900 dark:text-white">
                  {{ recommendation.title }}
                </h4>
                <span :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  getPriorityBadgeColor(recommendation.priority)
                ]">
                  {{ getPriorityLabel(recommendation.priority) }}
                </span>
              </div>
              
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                {{ recommendation.description }}
              </p>
              
              <div class="flex items-center justify-between mt-3">
                <div class="flex items-center space-x-4">
                  <div class="text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Économie:</span>
                    <span class="font-medium text-green-600 dark:text-green-400 ml-1">
                      {{ formatCurrency(recommendation.monthlySavings) }}/mois
                    </span>
                  </div>
                  <div class="text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Effort:</span>
                    <span class="font-medium text-gray-900 dark:text-white ml-1">
                      {{ getEffortLabel(recommendation.effort) }}
                    </span>
                  </div>
                </div>
                
                <div class="flex space-x-2">
                  <button
                    v-if="recommendation.actionUrl"
                    @click="openRecommendationAction(recommendation)"
                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/30"
                  >
                    Appliquer
                  </button>
                  <button
                    @click="dismissRecommendation(recommendation.id)"
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-xs font-medium rounded text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                  >
                    Ignorer
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <!-- No recommendations -->
          <div v-if="!data.recommendations?.length" class="text-center py-8">
            <CheckCircleIcon class="h-12 w-12 text-green-500 mx-auto mb-2" />
            <p class="text-gray-500 dark:text-gray-400">
              Aucune optimisation suggérée pour le moment
            </p>
          </div>
        </div>
      </div>
      
      <!-- Cost trends analysis -->
      <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Analyse des tendances
          </h3>
        </div>
        
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
              <h4 class="font-medium text-gray-900 dark:text-white">Tendances par service</h4>
              <div class="space-y-3">
                <div
                  v-for="trend in data.serviceTrends || []"
                  :key="trend.service"
                  class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                >
                  <div>
                    <div class="font-medium text-gray-900 dark:text-white">
                      {{ trend.service }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ formatCurrency(trend.currentCost) }} ce mois
                    </div>
                  </div>
                  <div class="flex items-center">
                    <component 
                      :is="trend.trend >= 0 ? TrendingUpIcon : TrendingDownIcon"
                      :class="[
                        'h-4 w-4 mr-1',
                        trend.trend >= 0 ? 'text-red-500' : 'text-green-500'
                      ]"
                    />
                    <span :class="[
                      'text-sm font-medium',
                      trend.trend >= 0 ? 'text-red-600' : 'text-green-600'
                    ]">
                      {{ Math.abs(trend.trend) }}%
                    </span>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="space-y-4">
              <h4 class="font-medium text-gray-900 dark:text-white">Facteurs d'optimisation</h4>
              <div class="space-y-3">
                <div
                  v-for="factor in optimizationFactors"
                  :key="factor.name"
                  class="flex items-center space-x-3"
                >
                  <div :class="[
                    'w-3 h-3 rounded-full',
                    factor.status === 'good' ? 'bg-green-500' : 
                    factor.status === 'warning' ? 'bg-yellow-500' : 'bg-red-500'
                  ]"></div>
                  <div class="flex-1">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ factor.name }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                      {{ factor.description }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import { useUIStore } from '@/stores/ui'

interface Recommendation {
  id: string
  title: string
  description: string
  type: 'storage' | 'processing' | 'api' | 'cache' | 'general'
  priority: 'high' | 'medium' | 'low'
  effort: 'low' | 'medium' | 'high'
  monthlySavings: number
  actionUrl?: string
}

interface ServiceTrend {
  service: string
  currentCost: number
  trend: number
}

interface OptimizationData {
  monthlyProjection?: number
  projectionTrend?: number
  monthlyBudget?: number
  currentSpend?: number
  potentialSavings?: number
  recommendations?: Recommendation[]
  serviceTrends?: ServiceTrend[]
}

interface Props {
  data: OptimizationData
  loading?: boolean
  error?: boolean
}

interface Emits {
  (e: 'apply-recommendation', id: string): void
  (e: 'dismiss-recommendation', id: string): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  error: false
})

const emit = defineEmits<Emits>()

const router = useRouter()
const uiStore = useUIStore()

// Computed properties
const budgetUsedPercentage = computed(() => {
  if (!props.data.monthlyBudget || !props.data.currentSpend) return 0
  return (props.data.currentSpend / props.data.monthlyBudget) * 100
})

const optimizationFactors = computed(() => [
  {
    name: 'Utilisation du cache',
    description: 'Cache efficacement utilisé',
    status: 'good'
  },
  {
    name: 'Preprocessing audio',
    description: 'Optimisation des fichiers audio',
    status: 'warning'
  },
  {
    name: 'Nettoyage automatique',
    description: 'Suppression des fichiers temporaires',
    status: 'good'
  },
  {
    name: 'Surveillance des coûts',
    description: 'Alertes configurées',
    status: 'warning'
  }
])

// Methods
function getBudgetStatus(): string {
  const percentage = budgetUsedPercentage.value
  if (percentage > 100) return 'Budget dépassé'
  if (percentage > 80) return 'Attention'
  if (percentage > 60) return 'En cours'
  return 'Dans les limites'
}

function getBudgetStatusColor(): string {
  const percentage = budgetUsedPercentage.value
  if (percentage > 100) return 'text-red-600 dark:text-red-400'
  if (percentage > 80) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-green-600 dark:text-green-400'
}

function getBudgetIcon() {
  const percentage = budgetUsedPercentage.value
  if (percentage > 100) return ExclamationIcon
  if (percentage > 80) return ExclamationIcon
  return CheckCircleIcon
}

function getBudgetIconColor(): string {
  const percentage = budgetUsedPercentage.value
  if (percentage > 100) return 'text-red-500'
  if (percentage > 80) return 'text-yellow-500'
  return 'text-green-500'
}

function getBudgetProgressColor(): string {
  const percentage = budgetUsedPercentage.value
  if (percentage > 100) return 'bg-red-500'
  if (percentage > 80) return 'bg-yellow-500'
  return 'bg-green-500'
}

function getRecommendationColor(priority: string): string {
  const colorMap = {
    high: 'bg-red-500',
    medium: 'bg-yellow-500',
    low: 'bg-blue-500'
  }
  return colorMap[priority] || 'bg-gray-500'
}

function getRecommendationIcon(type: string) {
  const iconMap = {
    storage: DatabaseIcon,
    processing: CpuChipIcon,
    api: CloudIcon,
    cache: ServerIcon,
    general: CogIcon
  }
  return iconMap[type] || CogIcon
}

function getPriorityBadgeColor(priority: string): string {
  const colorMap = {
    high: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
    medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
    low: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400'
  }
  return colorMap[priority] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
}

function getPriorityLabel(priority: string): string {
  const labelMap = {
    high: 'Haute',
    medium: 'Moyenne',
    low: 'Basse'
  }
  return labelMap[priority] || 'Inconnue'
}

function getEffortLabel(effort: string): string {
  const labelMap = {
    low: 'Faible',
    medium: 'Moyen',
    high: 'Élevé'
  }
  return labelMap[effort] || 'Inconnu'
}

function formatCurrency(amount: number): string {
  return `${amount.toFixed(2)}€`
}

function openRecommendationAction(recommendation: Recommendation) {
  if (recommendation.actionUrl) {
    if (recommendation.actionUrl.startsWith('/')) {
      router.push(recommendation.actionUrl)
    } else {
      window.open(recommendation.actionUrl, '_blank')
    }
  }
  emit('apply-recommendation', recommendation.id)
}

function dismissRecommendation(id: string) {
  emit('dismiss-recommendation', id)
  uiStore.showNotification({
    type: 'info',
    title: 'Recommandation ignorée',
    message: 'Cette recommandation ne sera plus affichée'
  })
}

// Icons
const ExclamationIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
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

const CalendarIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
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

const TrendingUpIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
    </svg>
  `
}

const TrendingDownIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
    </svg>
  `
}

const DatabaseIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
    </svg>
  `
}

const CpuChipIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
    </svg>
  `
}

const CloudIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
    </svg>
  `
}

const ServerIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
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
</script>