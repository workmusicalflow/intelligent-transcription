<template>
  <nav class="skip-links" aria-label="Liens de navigation rapide">
    <a
      v-for="link in skipLinks"
      :key="link.href"
      :href="link.href"
      class="skip-link"
      @click="handleSkipClick"
    >
      {{ link.text }}
    </a>
  </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

interface SkipLink {
  href: string
  text: string
  condition?: () => boolean
}

const route = useRoute()

const baseSkipLinks: SkipLink[] = [
  {
    href: '#main-content',
    text: 'Aller au contenu principal'
  },
  {
    href: '#main-navigation',
    text: 'Aller au menu principal',
    condition: () => !route.meta.hideNavigation
  },
  {
    href: '#search',
    text: 'Aller à la recherche',
    condition: () => ['TranscriptionList', 'Dashboard'].includes(route.name as string)
  },
  {
    href: '#footer',
    text: 'Aller au pied de page'
  }
]

const skipLinks = computed(() => 
  baseSkipLinks.filter(link => !link.condition || link.condition())
)

const handleSkipClick = (event: Event) => {
  const target = event.target as HTMLAnchorElement
  const targetId = target.getAttribute('href')?.substring(1)
  
  if (targetId) {
    const targetElement = document.getElementById(targetId)
    if (targetElement) {
      // Focus l'élément cible pour les lecteurs d'écran
      targetElement.focus()
      // Si l'élément n'est pas focusable naturellement, ajouter tabindex temporairement
      if (!targetElement.hasAttribute('tabindex')) {
        targetElement.setAttribute('tabindex', '-1')
        targetElement.addEventListener('blur', () => {
          targetElement.removeAttribute('tabindex')
        }, { once: true })
      }
    }
  }
}
</script>

<style scoped>
.skip-links {
  position: relative;
  z-index: 1000;
}

.skip-link {
  position: absolute;
  top: -40px;
  left: 6px;
  background: #1f2937;
  color: white;
  padding: 8px 12px;
  text-decoration: none;
  border-radius: 0 0 4px 4px;
  font-size: 0.875rem;
  font-weight: 500;
  transition: top 0.3s ease;
  white-space: nowrap;
}

.skip-link:focus {
  top: 0;
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

.skip-link:hover {
  background: #374151;
}

/* Assurer que les skip links sont visibles même avec des z-index élevés */
.skip-link:focus {
  z-index: 9999;
}
</style>