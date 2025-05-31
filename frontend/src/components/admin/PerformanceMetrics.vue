<template>
  <div class="space-y-4">
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 6" :key="i" class="animate-pulse">
        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
          <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-gray-200 dark:bg-gray-600 rounded"></div>
            <div class="space-y-1">
              <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded w-20"></div>
              <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-16"></div>
            </div>
          </div>
          <div class="w-16 h-6 bg-gray-200 dark:bg-gray-600 rounded"></div>
        </div>
      </div>
    </div>
    
    <div v-else-if="!metricsData?.length" class="text-center py-8">
      <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
      </svg>
      <p class="text-gray-500 dark:text-gray-400">Aucune métrique disponible</p>
    </div>
    
    <div v-else class="space-y-3">
      <div
        v-for="metric in metricsData"
        :key="metric.key"
        class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
      >
        <div class="flex items-center space-x-3">
          <!-- Icône de métrique -->
          <div 
            :class="[
              'w-8 h-8 rounded flex items-center justify-center',
              getStatusColor(metric.status).bg
            ]"
          >
            <component 
              :is="metric.icon" 
              :class="['w-4 h-4', getStatusColor(metric.status).text]"
            />
          </div>
          
          <!-- Informations de la métrique -->
          <div>
            <h4 class="text-sm font-medium text-gray-900 dark:text-white">
              {{ metric.name }}
            </h4>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              {{ metric.description }}
            </p>
          </div>
        </div>
        
        <!-- Valeur et indicateur -->
        <div class="flex items-center space-x-3">
          <!-- Barre de progression pour les pourcentages -->
          <div v-if="metric.type === 'percentage'" class="flex items-center space-x-2">
            <div class="w-16 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
              <div 
                :class="[
                  'h-full transition-all duration-300',
                  getStatusColor(metric.status).progress
                ]"
                :style="{ width: `${Math.min(100, metric.value)}%` }"
              ></div>
            </div>
            <span 
              :class="[
                'text-sm font-medium min-w-[3rem] text-right',
                getStatusColor(metric.status).text
              ]"
            >
              {{ formatValue(metric.value, metric.unit) }}
            </span>
          </div>
          
          <!-- Valeur simple pour les autres types -->
          <div v-else class="text-right">
            <div 
              :class="[
                'text-lg font-bold',
                getStatusColor(metric.status).text
              ]"
            >
              {{ formatValue(metric.value, metric.unit) }}
            </div>
            <div v-if="metric.trend" class="flex items-center justify-end space-x-1">
              <svg
                v-if="metric.trend > 0"
                class="w-3 h-3 text-red-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
              </svg>
              <svg
                v-else-if="metric.trend < 0"
                class="w-3 h-3 text-green-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
              </svg>
              <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ Math.abs(metric.trend) }}%
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Légende des statuts -->
    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
        <div class="flex items-center space-x-4">
          <div class="flex items-center space-x-1">
            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
            <span>Bon</span>
          </div>
          <div class="flex items-center space-x-1">
            <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
            <span>Attention</span>
          </div>
          <div class="flex items-center space-x-1">
            <div class="w-2 h-2 bg-red-500 rounded-full"></div>
            <span>Critique</span>
          </div>
        </div>
        <button
          @click="$emit('refresh')"
          class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
        >
          Actualiser
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface PerformanceMetric {
  key: string
  name: string
  description: string
  value: number
  unit: string
  type: 'percentage' | 'number' | 'duration'
  status: 'good' | 'warning' | 'critical'
  trend?: number
  icon: any
}

interface Props {
  metrics: Record<string, any>
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const emit = defineEmits<{
  refresh: []
}>()

// Icônes pour les métriques
const ClockIcon = () => ({
  template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
})

const BoltIcon = () => ({
  template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>'
})

const ExclamationTriangleIcon = () => ({
  template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>'
})

const CpuChipIcon = () => ({
  template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>'
})

const CircleStackIcon = () => ({
  template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>'
})

const ServerIcon = () => ({
  template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>'
})

const metricsData = computed((): PerformanceMetric[] => {
  if (!props.metrics || typeof props.metrics !== 'object') {
    return []
  }

  const metrics: PerformanceMetric[] = []

  // Temps de réponse moyen
  if (props.metrics.avgResponseTime !== undefined) {
    metrics.push({
      key: 'responseTime',
      name: 'Temps de réponse',
      description: 'Temps de réponse moyen de l\'API',
      value: props.metrics.avgResponseTime,
      unit: 'ms',
      type: 'duration',
      status: props.metrics.avgResponseTime < 200 ? 'good' : 
              props.metrics.avgResponseTime < 500 ? 'warning' : 'critical',
      trend: props.metrics.responseTimeTrend,
      icon: ClockIcon
    })
  }

  // Requêtes par seconde
  if (props.metrics.requestsPerSecond !== undefined) {
    metrics.push({
      key: 'requestsPerSecond',
      name: 'Requêtes/sec',
      description: 'Nombre de requêtes par seconde',
      value: props.metrics.requestsPerSecond,
      unit: '/s',
      type: 'number',
      status: props.metrics.requestsPerSecond < 1000 ? 'good' : 
              props.metrics.requestsPerSecond < 2000 ? 'warning' : 'critical',
      trend: props.metrics.requestsTrend,
      icon: BoltIcon
    })
  }

  // Taux d'erreur
  if (props.metrics.errorRate !== undefined) {
    metrics.push({
      key: 'errorRate',
      name: 'Taux d\'erreur',
      description: 'Pourcentage d\'erreurs',
      value: props.metrics.errorRate,
      unit: '%',
      type: 'percentage',
      status: props.metrics.errorRate < 1 ? 'good' : 
              props.metrics.errorRate < 5 ? 'warning' : 'critical',
      trend: props.metrics.errorRateTrend,
      icon: ExclamationTriangleIcon
    })
  }

  // Utilisation CPU
  if (props.metrics.cpuUsage !== undefined) {
    metrics.push({
      key: 'cpuUsage',
      name: 'CPU',
      description: 'Utilisation du processeur',
      value: props.metrics.cpuUsage,
      unit: '%',
      type: 'percentage',
      status: props.metrics.cpuUsage < 70 ? 'good' : 
              props.metrics.cpuUsage < 90 ? 'warning' : 'critical',
      trend: props.metrics.cpuTrend,
      icon: CpuChipIcon
    })
  }

  // Utilisation mémoire
  if (props.metrics.memoryUsage !== undefined) {
    metrics.push({
      key: 'memoryUsage',
      name: 'Mémoire',
      description: 'Utilisation de la mémoire',
      value: props.metrics.memoryUsage,
      unit: '%',
      type: 'percentage',
      status: props.metrics.memoryUsage < 80 ? 'good' : 
              props.metrics.memoryUsage < 95 ? 'warning' : 'critical',
      trend: props.metrics.memoryTrend,
      icon: ServerIcon
    })
  }

  // Utilisation disque
  if (props.metrics.diskUsage !== undefined) {
    metrics.push({
      key: 'diskUsage',
      name: 'Disque',
      description: 'Utilisation du stockage',
      value: props.metrics.diskUsage,
      unit: '%',
      type: 'percentage',
      status: props.metrics.diskUsage < 85 ? 'good' : 
              props.metrics.diskUsage < 95 ? 'warning' : 'critical',
      trend: props.metrics.diskTrend,
      icon: CircleStackIcon
    })
  }

  return metrics
})

function getStatusColor(status: string) {
  const colors = {
    good: {
      bg: 'bg-green-100 dark:bg-green-900/30',
      text: 'text-green-700 dark:text-green-400',
      progress: 'bg-green-500'
    },
    warning: {
      bg: 'bg-yellow-100 dark:bg-yellow-900/30',
      text: 'text-yellow-700 dark:text-yellow-400',
      progress: 'bg-yellow-500'
    },
    critical: {
      bg: 'bg-red-100 dark:bg-red-900/30',
      text: 'text-red-700 dark:text-red-400',
      progress: 'bg-red-500'
    }
  }
  
  return colors[status as keyof typeof colors] || colors.good
}

function formatValue(value: number, unit: string): string {
  if (unit === 'ms') {
    return `${Math.round(value)}ms`
  }
  
  if (unit === '%') {
    return `${Math.round(value)}%`
  }
  
  if (unit === '/s') {
    if (value >= 1000) {
      return `${(value / 1000).toFixed(1)}k/s`
    }
    return `${Math.round(value)}/s`
  }
  
  return `${value}${unit}`
}
</script>

<script lang="ts">
export default {
  name: 'PerformanceMetrics'
}
</script>