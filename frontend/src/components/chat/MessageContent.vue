<template>
  <div class="message-content">
    <!-- Contenu formaté avec markdown simple -->
    <div v-html="formattedContent"></div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  content: string
  role: 'user' | 'assistant'
}

const props = defineProps<Props>()

/**
 * Formater le contenu avec un markdown simple
 */
const formattedContent = computed(() => {
  let content = props.content
  
  // Échapper les caractères HTML
  content = content
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#x27;')
  
  // Formatage markdown simple
  
  // Code inline (backticks)
  content = content.replace(/`([^`]+)`/g, '<code>$1</code>')
  
  // Gras (**text** ou __text__)
  content = content.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
  content = content.replace(/__([^_]+)__/g, '<strong>$1</strong>')
  
  // Italique (*text* ou _text_)
  content = content.replace(/\*([^*]+)\*/g, '<em>$1</em>')
  content = content.replace(/_([^_]+)_/g, '<em>$1</em>')
  
  // Liens [text](url)
  content = content.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 underline hover:no-underline">$1</a>')
  
  // Listes à puces (lignes commençant par - ou *)
  content = content.replace(/^[\s]*[-*]\s(.+)$/gm, '<li>$1</li>')
  content = content.replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>')
  
  // Listes numérotées (lignes commençant par 1., 2., etc.)
  content = content.replace(/^[\s]*\d+\.\s(.+)$/gm, '<li>$1</li>')
  
  // Bloc de code (```...```)
  content = content.replace(/```([\\s\\S]*?)```/g, '<pre><code>$1</code></pre>')
  
  // Citations (lignes commençant par >)
  content = content.replace(/^>\s(.+)$/gm, '<blockquote>$1</blockquote>')
  
  // Titres (# ## ###)
  content = content.replace(/^### (.+)$/gm, '<h3>$1</h3>')
  content = content.replace(/^## (.+)$/gm, '<h2>$1</h2>')
  content = content.replace(/^# (.+)$/gm, '<h1>$1</h1>')
  
  // Saut de ligne (double espace + retour ou double retour)
  content = content.replace(/  \\n/g, '<br>')
  content = content.replace(/\\n\\n/g, '</p><p>')
  
  // Envelopper dans des paragraphes si pas déjà fait
  if (!content.includes('<p>') && !content.includes('<li>') && !content.includes('<h')) {
    content = `<p>${content}</p>`
  }
  
  return content
})
</script>

<style scoped>
.message-content :deep(p) {
  margin: 0 0 0.5em 0;
}

.message-content :deep(p:last-child) {
  margin-bottom: 0;
}

.message-content :deep(ul), 
.message-content :deep(ol) {
  margin: 0.5em 0;
  padding-left: 1.5em;
}

.message-content :deep(li) {
  margin: 0.25em 0;
}

.message-content :deep(code) {
  background-color: rgba(0, 0, 0, 0.1);
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
  font-size: 0.85em;
}

.message-content :deep(pre) {
  background-color: rgba(0, 0, 0, 0.05);
  border-radius: 0.375rem;
  padding: 0.75rem;
  overflow-x: auto;
  margin: 0.5em 0;
}

.message-content :deep(pre code) {
  background-color: transparent;
  padding: 0;
}

.message-content :deep(blockquote) {
  border-left: 4px solid currentColor;
  padding-left: 1rem;
  margin: 0.5em 0;
  opacity: 0.8;
  font-style: italic;
}

.message-content :deep(h1),
.message-content :deep(h2),
.message-content :deep(h3) {
  margin: 0.75em 0 0.25em 0;
  font-weight: 600;
}

.message-content :deep(h1) {
  font-size: 1.25em;
}

.message-content :deep(h2) {
  font-size: 1.125em;
}

.message-content :deep(h3) {
  font-size: 1em;
}

/* Styles pour le thème sombre */
.dark .message-content :deep(code) {
  background-color: rgba(255, 255, 255, 0.1);
}

.dark .message-content :deep(pre) {
  background-color: rgba(255, 255, 255, 0.05);
}
</style>