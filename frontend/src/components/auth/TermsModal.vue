<template>
  <ModalContainer @close="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] flex flex-col">
      <!-- En-tête -->
      <div class="flex justify-between items-center p-6 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Conditions d'utilisation
        </h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
        >
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <!-- Contenu scrollable -->
      <div class="flex-1 overflow-y-auto p-6">
        <div class="prose prose-sm max-w-none dark:prose-invert">
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
            <strong>Dernière mise à jour :</strong> {{ lastUpdated }}
          </p>

          <h4>1. Acceptation des conditions</h4>
          <p>
            En utilisant notre service de transcription intelligente, vous acceptez d'être lié par ces conditions d'utilisation. 
            Si vous n'acceptez pas ces conditions, veuillez ne pas utiliser notre service.
          </p>

          <h4>2. Description du service</h4>
          <p>
            Notre plateforme offre des services de transcription automatique utilisant l'intelligence artificielle pour convertir 
            vos fichiers audio et vidéo en texte. Nous proposons également des fonctionnalités d'analyse et de discussion contextuelle 
            basées sur vos transcriptions.
          </p>

          <h4>3. Compte utilisateur</h4>
          <p>
            Pour utiliser nos services, vous devez créer un compte en fournissant des informations exactes et à jour. 
            Vous êtes responsable de la sécurité de votre mot de passe et de toutes les activités sous votre compte.
          </p>

          <h4>4. Utilisation acceptable</h4>
          <p>Vous vous engagez à :</p>
          <ul>
            <li>Utiliser le service uniquement à des fins légales</li>
            <li>Ne pas transcrire de contenu illégal, diffamatoire ou portant atteinte aux droits d'autrui</li>
            <li>Respecter les droits de propriété intellectuelle</li>
            <li>Ne pas tenter de contourner les mesures de sécurité</li>
            <li>Ne pas utiliser le service pour spammer ou distribuer des malwares</li>
          </ul>

          <h4>5. Propriété intellectuelle</h4>
          <p>
            Vous conservez tous les droits sur vos contenus audio et textuels. Nous nous réservons le droit d'utiliser 
            des données anonymisées pour améliorer nos services, sans divulguer d'informations personnelles.
          </p>

          <h4>6. Confidentialité et sécurité</h4>
          <p>
            Nous nous engageons à protéger vos données conformément à notre politique de confidentialité. 
            Vos fichiers audio sont traités de manière sécurisée et supprimés après transcription, 
            sauf indication contraire de votre part.
          </p>

          <h4>7. Limitations de responsabilité</h4>
          <p>
            Notre service est fourni \"en l'état\". Nous ne garantissons pas une précision de transcription de 100% 
            et ne saurions être tenus responsables des erreurs de transcription ou des dommages indirects.
          </p>

          <h4>8. Suspension et résiliation</h4>
          <p>
            Nous nous réservons le droit de suspendre ou de résilier votre compte en cas de violation de ces conditions. 
            Vous pouvez résilier votre compte à tout moment depuis vos paramètres.
          </p>

          <h4>9. Modifications des conditions</h4>
          <p>
            Nous pouvons modifier ces conditions à tout moment. Les modifications importantes vous seront notifiées 
            par email ou via notre plateforme. L'utilisation continue du service constitue une acceptation des nouvelles conditions.
          </p>

          <h4>10. Tarification et facturation</h4>
          <p>
            Nos tarifs sont affichés clairement sur notre site. Les frais sont facturés selon votre utilisation. 
            Nous nous réservons le droit de modifier nos tarifs avec un préavis de 30 jours.
          </p>

          <h4>11. Support technique</h4>
          <p>
            Nous fournissons un support technique par email et via notre système de tickets. 
            Les temps de réponse peuvent varier selon votre plan d'abonnement.
          </p>

          <h4>12. Droit applicable</h4>
          <p>
            Ces conditions sont régies par le droit français. Tout litige sera soumis aux tribunaux compétents de Paris, France.
          </p>

          <h4>13. Contact</h4>
          <p>
            Pour toute question concernant ces conditions d'utilisation, contactez-nous à : 
            <a href="mailto:support@transcription-intelligente.com" class="text-blue-600 dark:text-blue-400 hover:underline">
              support@transcription-intelligente.com
            </a>
          </p>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-3 p-6 border-t border-gray-200 dark:border-gray-700">
        <Button
          variant="secondary"
          @click="$emit('close')"
        >
          Fermer
        </Button>
      </div>
    </div>
  </ModalContainer>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import ModalContainer from '@/components/ui/ModalContainer.vue'
import Button from '@/components/ui/Button.vue'

interface Emits {
  (e: 'close'): void
}

defineEmits<Emits>()

const lastUpdated = computed(() => {
  return new Date().toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
})
</script>

<style scoped>
.prose h4 {
  @apply text-lg font-semibold text-gray-900 dark:text-white mt-6 mb-3;
}

.prose p {
  @apply text-gray-700 dark:text-gray-300 mb-4 leading-relaxed;
}

.prose ul {
  @apply text-gray-700 dark:text-gray-300 ml-6 mb-4;
}

.prose li {
  @apply mb-2;
}

.prose a {
  @apply text-blue-600 dark:text-blue-400 hover:underline;
}
</style>