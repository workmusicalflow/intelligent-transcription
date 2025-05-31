<template>
  <ModalContainer @close="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] flex flex-col">
      <!-- En-tête -->
      <div class="flex justify-between items-center p-6 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Politique de confidentialité
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

          <h4>1. Introduction</h4>
          <p>
            Cette politique de confidentialité décrit comment nous collectons, utilisons et protégeons vos informations personnelles 
            lorsque vous utilisez notre service de transcription intelligente. Nous nous engageons à protéger votre vie privée 
            et à être transparents sur nos pratiques de données.
          </p>

          <h4>2. Informations que nous collectons</h4>
          
          <h5>2.1 Informations d'identification</h5>
          <ul>
            <li>Nom et adresse email lors de l'inscription</li>
            <li>Mot de passe (stocké de manière chiffrée)</li>
            <li>Préférences de compte et paramètres</li>
          </ul>

          <h5>2.2 Contenu utilisateur</h5>
          <ul>
            <li>Fichiers audio et vidéo que vous uploadez</li>
            <li>Transcriptions générées par notre système</li>
            <li>Conversations avec notre IA contextuelle</li>
            <li>Métadonnées associées (durée, taille, format)</li>
          </ul>

          <h5>2.3 Données d'utilisation</h5>
          <ul>
            <li>Journaux d'accès et d'utilisation du service</li>
            <li>Adresse IP et informations du navigateur</li>
            <li>Statistiques d'utilisation (temps de traitement, fréquence)</li>
            <li>Données de performance et d'erreurs</li>
          </ul>

          <h4>3. Comment nous utilisons vos informations</h4>
          
          <h5>3.1 Fourniture du service</h5>
          <ul>
            <li>Traitement de vos fichiers audio/vidéo pour transcription</li>
            <li>Génération de transcriptions précises</li>
            <li>Fourniture de fonctionnalités d'IA contextuelle</li>
            <li>Gestion de votre compte et préférences</li>
          </ul>

          <h5>3.2 Amélioration du service</h5>
          <ul>
            <li>Analyse des performances pour optimiser nos algorithmes</li>
            <li>Développement de nouvelles fonctionnalités</li>
            <li>Recherche et développement en IA (données anonymisées)</li>
          </ul>

          <h5>3.3 Communication</h5>
          <ul>
            <li>Notifications de service importantes</li>
            <li>Support technique et assistance</li>
            <li>Newsletter (uniquement si vous vous abonnez)</li>
          </ul>

          <h4>4. Partage et divulgation des données</h4>
          <p>Nous ne vendons jamais vos données personnelles. Nous pouvons partager des informations dans les cas suivants :</p>
          
          <h5>4.1 Prestataires de services</h5>
          <ul>
            <li>Services cloud pour le stockage et le traitement (AWS, Google Cloud)</li>
            <li>APIs de transcription (OpenAI Whisper)</li>
            <li>Services de paiement et de facturation</li>
            <li>Outils d'analyse et de monitoring</li>
          </ul>

          <h5>4.2 Obligations légales</h5>
          <ul>
            <li>Réponse aux demandes légales valides</li>
            <li>Protection de nos droits et sécurité</li>
            <li>Prévention de la fraude ou d'activités illégales</li>
          </ul>

          <h4>5. Sécurité des données</h4>
          <p>Nous mettons en place des mesures de sécurité robustes :</p>
          <ul>
            <li>Chiffrement en transit (HTTPS/TLS) et au repos</li>
            <li>Accès limité selon le principe du moindre privilège</li>
            <li>Surveillance continue et détection d'intrusions</li>
            <li>Sauvegardes sécurisées et récupération de données</li>
            <li>Tests de sécurité réguliers et audits</li>
          </ul>

          <h4>6. Rétention des données</h4>
          
          <h5>6.1 Fichiers audio/vidéo</h5>
          <ul>
            <li>Supprimés automatiquement après transcription (24-48h)</li>
            <li>Possibilité de conservation temporaire pour dépannage</li>
            <li>Suppression immédiate sur demande explicite</li>
          </ul>

          <h5>6.2 Transcriptions et conversations</h5>
          <ul>
            <li>Conservées tant que votre compte est actif</li>
            <li>Supprimées 30 jours après fermeture du compte</li>
            <li>Exportation possible avant suppression</li>
          </ul>

          <h5>6.3 Données de compte</h5>
          <ul>
            <li>Conservées tant que votre compte est actif</li>
            <li>Supprimées selon les exigences légales après fermeture</li>
            <li>Journaux d'accès conservés 12 mois maximum</li>
          </ul>

          <h4>7. Vos droits</h4>
          <p>Conformément au RGPD, vous disposez des droits suivants :</p>
          <ul>
            <li><strong>Accès :</strong> Demander une copie de vos données personnelles</li>
            <li><strong>Rectification :</strong> Corriger des données inexactes</li>
            <li><strong>Effacement :</strong> Demander la suppression de vos données</li>
            <li><strong>Portabilité :</strong> Recevoir vos données dans un format structuré</li>
            <li><strong>Limitation :</strong> Limiter le traitement de vos données</li>
            <li><strong>Opposition :</strong> Vous opposer au traitement de vos données</li>
          </ul>

          <h4>8. Cookies et technologies similaires</h4>
          <p>Nous utilisons des cookies pour :</p>
          <ul>
            <li>Maintenir votre session de connexion</li>
            <li>Mémoriser vos préférences</li>
            <li>Analyser l'utilisation du service (cookies analytiques)</li>
            <li>Améliorer les performances (mise en cache)</li>
          </ul>

          <h4>9. Transferts internationaux</h4>
          <p>
            Vos données peuvent être traitées dans des pays hors UE par nos prestataires cloud. 
            Nous nous assurons que ces transferts respectent les exigences du RGPD via des clauses 
            contractuelles types ou des décisions d'adéquation.
          </p>

          <h4>10. Modifications de cette politique</h4>
          <p>
            Nous pouvons mettre à jour cette politique de confidentialité. Les modifications importantes 
            vous seront notifiées par email ou via notre plateforme. La date de dernière mise à jour 
            est indiquée en haut de ce document.
          </p>

          <h4>11. Contact et réclamations</h4>
          <p>
            Pour exercer vos droits ou pour toute question concernant cette politique :
          </p>
          <ul>
            <li>Email : <a href="mailto:privacy@transcription-intelligente.com" class="text-blue-600 dark:text-blue-400 hover:underline">privacy@transcription-intelligente.com</a></li>
            <li>Délégué à la protection des données : <a href="mailto:dpo@transcription-intelligente.com" class="text-blue-600 dark:text-blue-400 hover:underline">dpo@transcription-intelligente.com</a></li>
          </ul>
          <p>
            Vous avez également le droit de déposer une réclamation auprès de la CNIL (Commission Nationale de l'Informatique et des Libertés).
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

.prose h5 {
  @apply text-base font-medium text-gray-800 dark:text-gray-200 mt-4 mb-2;
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

.prose strong {
  @apply font-semibold text-gray-900 dark:text-white;
}
</style>