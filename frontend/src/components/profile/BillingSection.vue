<template>
  <div class="space-y-6">
    <!-- Plan actuel -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
        Plan d'abonnement
      </h3>
      
      <div class="flex items-center justify-between">
        <div>
          <div class="flex items-center space-x-3">
            <h4 class="text-xl font-bold text-gray-900 dark:text-white">
              Plan Gratuit
            </h4>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
              Actif
            </span>
          </div>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Jusqu'à 60 minutes de transcription par mois
          </p>
        </div>
        
        <div class="text-right">
          <div class="text-2xl font-bold text-gray-900 dark:text-white">
            0€/mois
          </div>
          <Button
            variant="primary"
            @click="$emit('manage-subscription')"
            class="mt-2"
          >
            Améliorer le plan
          </Button>
        </div>
      </div>
    </div>

    <!-- Utilisation actuelle -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
        Utilisation ce mois-ci
      </h3>
      
      <div class="space-y-4">
        <!-- Progression minutes -->
        <div>
          <div class="flex justify-between text-sm font-medium text-gray-900 dark:text-white mb-2">
            <span>Minutes de transcription</span>
            <span>{{ usage?.transcriptionMinutes || 0 }} / 60</span>
          </div>
          <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
            <div
              class="bg-blue-600 h-2 rounded-full transition-all duration-300"
              :style="{ width: `${Math.min((usage?.transcriptionMinutes || 0) / 60 * 100, 100)}%` }"
            ></div>
          </div>
          <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
            {{ 60 - (usage?.transcriptionMinutes || 0) }} minutes restantes
          </p>
        </div>
        
        <!-- Autres métriques -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4">
          <div class="text-center">
            <div class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ usage?.filesProcessed || 0 }}
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Fichiers traités
            </p>
          </div>
          
          <div class="text-center">
            <div class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ usage?.totalCost || 0 }}€
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Coût total
            </p>
          </div>
          
          <div class="text-center">
            <div class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ usage?.apiCalls || 0 }}
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Appels API
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Plans disponibles -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
        Plans disponibles
      </h3>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Plan Gratuit -->
        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
          <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Gratuit</h4>
          <div class="text-2xl font-bold text-gray-900 dark:text-white mt-2">0€</div>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">par mois</p>
          <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
            <li>✓ 60 minutes/mois</li>
            <li>✓ Formats de base</li>
            <li>✓ Support communautaire</li>
            <li>✗ Chat IA limité</li>
          </ul>
          <Button variant="secondary" class="w-full mt-4" disabled>
            Plan actuel
          </Button>
        </div>
        
        <!-- Plan Pro -->
        <div class="border-2 border-blue-500 rounded-lg p-4 relative">
          <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
            <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-xs font-medium">
              Recommandé
            </span>
          </div>
          <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Pro</h4>
          <div class="text-2xl font-bold text-gray-900 dark:text-white mt-2">9,99€</div>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">par mois</p>
          <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
            <li>✓ 300 minutes/mois</li>
            <li>✓ Tous les formats</li>
            <li>✓ Chat IA illimité</li>
            <li>✓ Support prioritaire</li>
          </ul>
          <Button variant="primary" class="w-full mt-4">
            Passer au Pro
          </Button>
        </div>
        
        <!-- Plan Enterprise -->
        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
          <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Enterprise</h4>
          <div class="text-2xl font-bold text-gray-900 dark:text-white mt-2">29,99€</div>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">par mois</p>
          <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
            <li>✓ Minutes illimitées</li>
            <li>✓ API personnalisée</li>
            <li>✓ Équipe collaborative</li>
            <li>✓ Support dédié</li>
          </ul>
          <Button variant="secondary" class="w-full mt-4">
            Nous contacter
          </Button>
        </div>
      </div>
    </div>

    <!-- Historique de facturation -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Historique de facturation
        </h3>
      </div>
      
      <div class="text-center py-8">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <p class="text-gray-600 dark:text-gray-400 mt-2">
          Aucune facture disponible
        </p>
        <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
          Les factures apparaîtront ici lorsque vous aurez un abonnement payant
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import Button from '@/components/ui/Button.vue'

interface Props {
  subscription: any
  usage: any
  loading: boolean
}

interface Emits {
  (e: 'manage-subscription'): void
  (e: 'download-invoice', invoiceId: string): void
}

defineProps<Props>()
defineEmits<Emits>()
</script>