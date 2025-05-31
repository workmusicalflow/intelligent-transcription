import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'

interface SEOConfig {
  title?: string
  description?: string
  keywords?: string[]
  image?: string
  url?: string
  type?: 'website' | 'article' | 'profile'
  siteName?: string
  locale?: string
  author?: string
}

/**
 * Composable pour la gestion du SEO et des meta tags
 */
export function useSEO(config: SEOConfig = {}) {
  const route = useRoute()
  const defaultConfig = {
    siteName: 'Transcription Intelligente',
    type: 'website',
    locale: 'fr_FR',
    author: 'Transcription Intelligente',
    image: '/og-image.png'
  }

  const currentConfig = ref({ ...defaultConfig, ...config })

  // Fonction pour mettre à jour les meta tags
  const updateMetaTags = () => {
    const { title, description, keywords, image, url, type, siteName, locale, author } = currentConfig.value

    // Title
    if (title) {
      document.title = `${title} | ${siteName}`
      updateMetaTag('property', 'og:title', title)
      updateMetaTag('name', 'twitter:title', title)
    }

    // Description
    if (description) {
      updateMetaTag('name', 'description', description)
      updateMetaTag('property', 'og:description', description)
      updateMetaTag('name', 'twitter:description', description)
    }

    // Keywords
    if (keywords?.length) {
      updateMetaTag('name', 'keywords', keywords.join(', '))
    }

    // Open Graph
    updateMetaTag('property', 'og:type', type)
    updateMetaTag('property', 'og:site_name', siteName)
    updateMetaTag('property', 'og:locale', locale)
    
    if (image) {
      updateMetaTag('property', 'og:image', image)
      updateMetaTag('name', 'twitter:image', image)
      updateMetaTag('name', 'twitter:card', 'summary_large_image')
    }

    if (url) {
      updateMetaTag('property', 'og:url', url)
      updateMetaTag('name', 'twitter:url', url)
    }

    if (author) {
      updateMetaTag('name', 'author', author)
    }

    // Canonical URL
    if (url) {
      updateLinkTag('canonical', url)
    }
  }

  // Fonction helper pour mettre à jour un meta tag
  const updateMetaTag = (attribute: string, value: string, content: string) => {
    let meta = document.querySelector(`meta[${attribute}="${value}"]`)
    if (!meta) {
      meta = document.createElement('meta')
      meta.setAttribute(attribute, value)
      document.head.appendChild(meta)
    }
    meta.setAttribute('content', content)
  }

  // Fonction helper pour mettre à jour un link tag
  const updateLinkTag = (rel: string, href: string) => {
    let link = document.querySelector(`link[rel="${rel}"]`)
    if (!link) {
      link = document.createElement('link')
      link.setAttribute('rel', rel)
      document.head.appendChild(link)
    }
    link.setAttribute('href', href)
  }

  // Fonction pour mettre à jour la configuration
  const updateSEO = (newConfig: Partial<SEOConfig>) => {
    currentConfig.value = { ...currentConfig.value, ...newConfig }
    updateMetaTags()
  }

  // Watcher pour les changements de route
  watch(
    () => route.path,
    () => {
      // Mettre à jour l'URL dans la config
      currentConfig.value.url = window.location.href
      updateMetaTags()
    },
    { immediate: true }
  )

  onMounted(() => {
    updateMetaTags()
  })

  return {
    updateSEO,
    currentConfig
  }
}

/**
 * Configuration SEO par défaut pour les différentes pages
 */
export const seoConfigs = {
  dashboard: {
    title: 'Tableau de bord',
    description: 'Gérez vos transcriptions et suivez vos statistiques en temps réel.',
    keywords: ['dashboard', 'transcription', 'statistiques', 'gestion']
  },
  transcriptions: {
    title: 'Mes transcriptions',
    description: 'Consultez et gérez toutes vos transcriptions audio et vidéo.',
    keywords: ['transcriptions', 'audio', 'vidéo', 'conversion', 'texte']
  },
  chat: {
    title: 'Chat intelligent',
    description: 'Discutez avec l\'IA pour améliorer et analyser vos transcriptions.',
    keywords: ['chat', 'intelligence artificielle', 'analyse', 'transcription']
  },
  analytics: {
    title: 'Analytiques',
    description: 'Analysez vos performances et optimisez votre utilisation.',
    keywords: ['analytics', 'performance', 'statistiques', 'optimisation']
  },
  profile: {
    title: 'Mon profil',
    description: 'Gérez vos informations personnelles et préférences.',
    keywords: ['profil', 'paramètres', 'compte', 'préférences']
  },
  settings: {
    title: 'Paramètres',
    description: 'Configurez l\'application selon vos besoins.',
    keywords: ['paramètres', 'configuration', 'personnalisation']
  }
}

/**
 * Composable pour les structured data (JSON-LD)
 */
export function useStructuredData(data: Record<string, any>) {
  const addStructuredData = () => {
    const script = document.createElement('script')
    script.type = 'application/ld+json'
    script.textContent = JSON.stringify(data)
    document.head.appendChild(script)
    return script
  }

  const removeStructuredData = (script: HTMLScriptElement) => {
    if (script && script.parentNode) {
      script.parentNode.removeChild(script)
    }
  }

  onMounted(() => {
    const script = addStructuredData()
    onUnmounted(() => {
      removeStructuredData(script)
    })
  })
}