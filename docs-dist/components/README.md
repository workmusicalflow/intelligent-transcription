# Component Documentation

## Vue d'ensemble

Documentation des composants Vue.js réutilisables d'Intelligent Transcription.

## 🎨 Architecture des Composants

### Structure
```
src/components/
├── ui/              # Composants UI génériques
│   ├── Button.vue   # Boutons avec variantes
│   ├── Input.vue    # Champs de saisie
│   └── LoadingSpinner.vue
├── layout/          # Composants de mise en page
│   ├── Sidebar.vue  # Navigation latérale
│   ├── TopNavigation.vue
│   └── UserMenu.vue
├── auth/            # Composants d'authentification
│   ├── PasswordStrengthIndicator.vue
│   ├── TermsModal.vue
│   └── PrivacyModal.vue
└── transcription/   # Composants de transcription
    ├── TranscriptionCard.vue
    ├── TranscriptionProgress.vue
    └── TranscriptionActions.vue
```

## 🧩 Composants UI

### Button

**Fichier :** `src/components/ui/Button.vue`

**Description :** Composant bouton avec variantes, tailles et états.

**Props :**
```typescript
interface Props {
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost'
  size?: 'sm' | 'md' | 'lg'
  loading?: boolean
  disabled?: boolean
  type?: 'button' | 'submit' | 'reset'
}
```

**Utilisation :**
```vue
<template>
  <!-- Bouton primaire -->
  <Button variant="primary" size="lg">
    Créer une transcription
  </Button>
  
  <!-- Bouton avec loading -->
  <Button :loading="isLoading" @click="handleSubmit">
    {{ isLoading ? 'Traitement...' : 'Valider' }}
  </Button>
  
  <!-- Bouton danger -->
  <Button variant="danger" @click="deleteItem">
    Supprimer
  </Button>
</template>
```

### Input

**Fichier :** `src/components/ui/Input.vue`

**Description :** Champ de saisie avec validation, icônes et états.

**Props :**
```typescript
interface Props {
  modelValue?: string
  type?: 'text' | 'email' | 'password' | 'number'
  placeholder?: string
  label?: string
  error?: string
  disabled?: boolean
  required?: boolean
}
```

**Utilisation :**
```vue
<template>
  <!-- Input basique -->
  <Input
    v-model="email"
    type="email"
    placeholder="votre@email.com"
    label="Adresse email"
  />
  
  <!-- Input avec erreur -->
  <Input
    v-model="password"
    type="password"
    :error="passwordError"
    data-testid="password-input"
  >
    <template #prefix>
      <LockIcon class="h-5 w-5" />
    </template>
  </Input>
</template>
```

### LoadingSpinner

**Fichier :** `src/components/ui/LoadingSpinner.vue`

**Description :** Indicateur de chargement animé.

**Props :**
```typescript
interface Props {
  size?: 'sm' | 'md' | 'lg'
  color?: 'primary' | 'white' | 'gray'
}
```

**Utilisation :**
```vue
<template>
  <!-- Spinner dans un bouton -->
  <Button :loading="isLoading">
    <LoadingSpinner v-if="isLoading" size="sm" color="white" />
    Charger
  </Button>
  
  <!-- Spinner pleine page -->
  <div v-if="loading" class="flex justify-center">
    <LoadingSpinner size="lg" />
  </div>
</template>
```

## 🏗️ Composants Layout

### Sidebar

**Fichier :** `src/components/layout/Sidebar.vue`

**Description :** Navigation latérale principale de l'application.

**Fonctionnalités :**
- Navigation responsive
- Indicateurs d'état actif
- Support des icônes
- Gestion des permissions utilisateur

### TopNavigation

**Fichier :** `src/components/layout/TopNavigation.vue`

**Description :** Barre de navigation supérieure.

**Fonctionnalités :**
- Menu utilisateur
- Notifications
- Recherche globale
- Toggle thème sombre

## 🔐 Composants Auth

### PasswordStrengthIndicator

**Fichier :** `src/components/auth/PasswordStrengthIndicator.vue`

**Description :** Indicateur visuel de la force du mot de passe.

**Props :**
```typescript
interface Props {
  password: string
}
```

**Fonctionnalités :**
- Calcul de force en temps réel
- Critères de validation visuels
- Barre de progression colorée
- Messages d'aide

**Utilisation :**
```vue
<template>
  <div>
    <Input
      v-model="password"
      type="password"
      placeholder="Mot de passe"
    />
    
    <PasswordStrengthIndicator 
      v-if="password"
      :password="password"
      class="mt-2"
    />
  </div>
</template>
```

## 📝 Composants Transcription

### TranscriptionCard

**Fichier :** `src/components/transcription/TranscriptionCard.vue`

**Description :** Carte d'affichage d'une transcription.

**Props :**
```typescript
interface Props {
  transcription: Transcription
}
```

**Fonctionnalités :**
- Affichage des métadonnées
- Actions contextuelles
- Indicateur de statut
- Prévisualisation du contenu

### TranscriptionProgress

**Fichier :** `src/components/transcription/TranscriptionProgress.vue`

**Description :** Indicateur de progression de transcription.

**Props :**
```typescript
interface Props {
  progress: number  // 0-100
  status: TranscriptionStatus
  estimatedTime?: number
}
```

## 📏 Conventions de Développement

### Structure de Composant
```vue
<template>
  <!-- Template avec data-testid pour les tests -->
  <div class="component-name" data-testid="component-name">
    <!-- Contenu -->
  </div>
</template>

<script setup lang="ts">
/**
 * Description du composant
 * 
 * @example
 * ```vue
 * <ComponentName :prop="value" />
 * ```
 */

// Imports
import { ref, computed } from 'vue'
import type { ComponentProps } from '@/types'

// Props avec interface TypeScript
interface Props {
  /** Description de la prop */
  prop: string
}

const props = defineProps<Props>()

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: string]
  'action': [data: any]
}>()

// Reactive state
const state = ref('')

// Computed
const computedValue = computed(() => {
  return state.value.toUpperCase()
})

// Methods
function handleAction(): void {
  emit('action', { data: 'example' })
}
</script>

<style scoped>
/* Styles scopés si nécessaires */
.component-name {
  /* Utiliser Tailwind CSS en priorité */
}
</style>
```

### Tests de Composants
```typescript
// ComponentName.test.ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ComponentName from './ComponentName.vue'

describe('ComponentName', () => {
  it('renders correctly', () => {
    const wrapper = mount(ComponentName, {
      props: {
        prop: 'test value'
      }
    })
    
    expect(wrapper.find('[data-testid="component-name"]').exists()).toBe(true)
  })
  
  it('emits events correctly', async () => {
    const wrapper = mount(ComponentName)
    
    await wrapper.find('[data-testid="action-button"]').trigger('click')
    
    expect(wrapper.emitted('action')).toBeTruthy()
  })
})
```

## 🎨 Design System

### Couleurs
```css
/* Couleurs principales */
--color-primary: #3B82F6;     /* blue-500 */
--color-secondary: #6B7280;   /* gray-500 */
--color-success: #10B981;     /* emerald-500 */
--color-warning: #F59E0B;     /* amber-500 */
--color-danger: #EF4444;      /* red-500 */
```

### Espacements
```css
/* Espacements standardisés */
--spacing-xs: 0.25rem;  /* 4px */
--spacing-sm: 0.5rem;   /* 8px */
--spacing-md: 1rem;     /* 16px */
--spacing-lg: 1.5rem;   /* 24px */
--spacing-xl: 2rem;     /* 32px */
```

### Typographie
```css
/* Tailles de texte */
--text-xs: 0.75rem;     /* 12px */
--text-sm: 0.875rem;    /* 14px */
--text-base: 1rem;      /* 16px */
--text-lg: 1.125rem;    /* 18px */
--text-xl: 1.25rem;     /* 20px */
```

## 🔧 Outils de Développement

### Storybook
```bash
# Lancer Storybook
npm run storybook

# Build Storybook
npm run build-storybook
```

### Tests
```bash
# Tests unitaires
npm run test

# Tests en mode watch
npm run test:watch

# Couverture de code
npm run test:coverage
```

### Linting
```bash
# ESLint
npm run lint

# TypeScript check
npm run type-check
```

## 📚 Ressources

- [Vue 3 Composition API](https://vuejs.org/guide/)
- [TypeScript avec Vue](https://vuejs.org/guide/typescript/)
- [Tailwind CSS](https://tailwindcss.com/docs)
- [Vue Test Utils](https://vue-test-utils.vuejs.org/)
- [Storybook pour Vue](https://storybook.js.org/docs/vue/get-started/introduction)