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
        <p>Erreur lors du chargement des métriques</p>
      </div>
    </div>
    
    <!-- Metrics content -->
    <div v-else class="space-y-6">
      <!-- Key metrics cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div
          v-for="metric in keyMetrics"
          :key="metric.key"
          class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                {{ metric.label }}
              </p>
              <p class="text-xl font-bold text-gray-900 dark:text-white">
                {{ formatMetricValue(metric.value, metric.unit) }}
              </p>
            </div>
            <div :class="[
              'w-10 h-10 rounded-lg flex items-center justify-center',
              getMetricColor(metric.status)
            ]">
              <component :is="getMetricIcon(metric.status)" class="h-5 w-5 text-white" />
            </div>
          </div>
          <div class="mt-2 flex items-center">
            <component 
              :is="metric.trend >= 0 ? TrendingUpIcon : TrendingDownIcon"
              :class="[
                'h-4 w-4 mr-1',
                metric.trend >= 0 ? 'text-green-500' : 'text-red-500'
              ]"
            />
            <span :class="[
              'text-sm font-medium',
              metric.trend >= 0 ? 'text-green-600' : 'text-red-600'
            ]">
              {{ Math.abs(metric.trend) }}%
            </span>
            <span class="text-sm text-gray-500 dark:text-gray-400 ml-1">
              vs semaine précédente
            </span>
          </div>
        </div>
      </div>
      
      <!-- Detailed metrics table -->
      <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Métriques détaillées
          </h3>
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Métrique
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Valeur actuelle
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Moyenne (30j)
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Min/Max
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Tendance
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Statut
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr
                v-for="metric in detailedMetrics"
                :key="metric.key"
                class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
              >
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <component :is="getMetricIcon(metric.status)" 
                               :class="['h-4 w-4 mr-3', getMetricIconColor(metric.status)]" />
                    <div>
                      <div class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ metric.label }}
                      </div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ metric.description }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ formatMetricValue(metric.current, metric.unit) }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ formatMetricValue(metric.average, metric.unit) }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    <div>Min: {{ formatMetricValue(metric.min, metric.unit) }}</div>
                    <div>Max: {{ formatMetricValue(metric.max, metric.unit) }}</div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <component 
                      :is="metric.trend >= 0 ? TrendingUpIcon : TrendingDownIcon"
                      :class="[
                        'h-4 w-4 mr-1',
                        metric.trend >= 0 ? 'text-green-500' : 'text-red-500'
                      ]"
                    />
                    <span :class="[
                      'text-sm font-medium',
                      metric.trend >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
                    ]">
                      {{ Math.abs(metric.trend) }}%
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    getStatusBadgeColor(metric.status)
                  ]">
                    {{ getStatusLabel(metric.status) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Performance alerts -->
      <div v-if="alerts.length > 0" class="space-y-3">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
          Alertes de performance
        </h4>
        <div class="space-y-2">
          <div
            v-for="alert in alerts"
            :key="alert.id"
            :class="[
              'flex items-start p-4 rounded-lg border-l-4',
              getAlertColor(alert.severity)
            ]"
          >
            <component :is="getAlertIcon(alert.severity)" class="h-5 w-5 mr-3 mt-0.5" />
            <div class="flex-1">
              <div class="font-medium text-gray-900 dark:text-white">
                {{ alert.title }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                {{ alert.message }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                {{ formatAlertTime(alert.timestamp) }}
              </div>
            </div>
            <button
              @click="dismissAlert(alert.id)"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <XIcon class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'

interface MetricData {
  key: string
  label: string
  description?: string
  value?: number
  current: number
  average: number
  min: number
  max: number
  unit: 'seconds' | 'percentage' | 'count' | 'bytes' | 'requests'
  trend: number
  status: 'excellent' | 'good' | 'warning' | 'critical'
}

interface Alert {
  id: string
  title: string
  message: string
  severity: 'info' | 'warning' | 'error'
  timestamp: string
}

interface Props {
  metrics: {
    processing?: MetricData
    accuracy?: MetricData
    throughput?: MetricData
    errorRate?: MetricData
    [key: string]: MetricData | undefined
  }
  loading?: boolean
  error?: boolean
}

interface Emits {
  (e: 'dismiss-alert', id: string): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  error: false
})

const emit = defineEmits<Emits>()

// Computed properties
const keyMetrics = computed(() => {
  const metrics = props.metrics
  return [
    {
      key: 'processing',
      label: 'Temps de traitement moyen',
      value: metrics.processing?.current || 0,
      unit: metrics.processing?.unit || 'seconds',
      trend: metrics.processing?.trend || 0,
      status: metrics.processing?.status || 'good'
    },
    {
      key: 'accuracy',
      label: 'Précision moyenne',
      value: metrics.accuracy?.current || 0,
      unit: metrics.accuracy?.unit || 'percentage',
      trend: metrics.accuracy?.trend || 0,
      status: metrics.accuracy?.status || 'good'
    },
    {
      key: 'throughput',
      label: 'Débit (req/min)',
      value: metrics.throughput?.current || 0,
      unit: metrics.throughput?.unit || 'requests',
      trend: metrics.throughput?.trend || 0,
      status: metrics.throughput?.status || 'good'
    },
    {
      key: 'errorRate',
      label: 'Taux d\'erreur',
      value: metrics.errorRate?.current || 0,
      unit: metrics.errorRate?.unit || 'percentage',
      trend: metrics.errorRate?.trend || 0,
      status: metrics.errorRate?.status || 'good'
    }
  ]
})

const detailedMetrics = computed(() => {
  return Object.values(props.metrics).filter(Boolean) as MetricData[]
})

const alerts = computed(() => {
  // Mock alerts - in real implementation, these would come from props or API
  const mockAlerts: Alert[] = []
  
  // Generate alerts based on metric status
  Object.values(props.metrics).forEach(metric => {
    if (!metric) return
    
    if (metric.status === 'critical') {
      mockAlerts.push({
        id: `alert-${metric.key}-critical`,
        title: `${metric.label} critique`,
        message: `La métrique ${metric.label} est en état critique avec une valeur de ${formatMetricValue(metric.current, metric.unit)}`,
        severity: 'error',
        timestamp: new Date().toISOString()
      })
    } else if (metric.status === 'warning') {
      mockAlerts.push({
        id: `alert-${metric.key}-warning`,
        title: `${metric.label} en attention`,
        message: `La métrique ${metric.label} nécessite votre attention`,
        severity: 'warning',
        timestamp: new Date().toISOString()
      })
    }
  })
  
  return mockAlerts
})

// Methods
function getMetricColor(status: string): string {
  const colorMap = {
    excellent: 'bg-green-500',
    good: 'bg-blue-500',
    warning: 'bg-yellow-500',
    critical: 'bg-red-500'
  }
  return colorMap[status] || 'bg-gray-500'
}

function getMetricIconColor(status: string): string {
  const colorMap = {
    excellent: 'text-green-500',
    good: 'text-blue-500',
    warning: 'text-yellow-500',
    critical: 'text-red-500'
  }
  return colorMap[status] || 'text-gray-500'
}

function getMetricIcon(status: string) {
  const iconMap = {
    excellent: CheckCircleIcon,
    good: CheckIcon,
    warning: ExclamationIcon,
    critical: XCircleIcon
  }
  return iconMap[status] || CheckIcon
}

function getStatusBadgeColor(status: string): string {
  const colorMap = {
    excellent: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
    good: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
    warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
    critical: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
  }
  return colorMap[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
}

function getStatusLabel(status: string): string {
  const labelMap = {
    excellent: 'Excellent',
    good: 'Bon',
    warning: 'Attention',
    critical: 'Critique'
  }
  return labelMap[status] || 'Inconnu'
}

function getAlertColor(severity: string): string {
  const colorMap = {
    info: 'border-blue-400 bg-blue-50 dark:bg-blue-900/20',
    warning: 'border-yellow-400 bg-yellow-50 dark:bg-yellow-900/20',
    error: 'border-red-400 bg-red-50 dark:bg-red-900/20'
  }
  return colorMap[severity] || 'border-gray-400 bg-gray-50 dark:bg-gray-900/20'
}

function getAlertIcon(severity: string) {
  const iconMap = {
    info: InformationCircleIcon,
    warning: ExclamationIcon,
    error: XCircleIcon
  }
  return iconMap[severity] || InformationCircleIcon
}

function formatMetricValue(value: number, unit: string): string {
  switch (unit) {
    case 'seconds':
      if (value < 60) return `${value.toFixed(1)}s`
      else if (value < 3600) return `${(value / 60).toFixed(1)}min`
      else return `${(value / 3600).toFixed(1)}h`
    case 'percentage':
      return `${value.toFixed(1)}%`
    case 'count':
      return value.toLocaleString()
    case 'bytes':
      if (value < 1024) return `${value}B`
      else if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)}KB`
      else if (value < 1024 * 1024 * 1024) return `${(value / (1024 * 1024)).toFixed(1)}MB`
      else return `${(value / (1024 * 1024 * 1024)).toFixed(1)}GB`
    case 'requests':
      return value.toLocaleString()
    default:
      return value.toString()
  }
}

function formatAlertTime(timestamp: string): string {
  const date = new Date(timestamp)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / (1000 * 60))
  
  if (diffMins < 1) return 'À l\'instant'
  if (diffMins < 60) return `Il y a ${diffMins} min`
  if (diffMins < 1440) return `Il y a ${Math.floor(diffMins / 60)}h`
  return date.toLocaleDateString('fr-FR')
}

function dismissAlert(id: string) {
  emit('dismiss-alert', id)
}

// Icons
const ExclamationIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
    </svg>
  `
}

const CheckIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
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

const XCircleIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
  `
}

const InformationCircleIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
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

const XIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
    </svg>
  `
}
</script>