# Design System

## Vue d'ensemble

Système de design d'Intelligent Transcription basé sur Tailwind CSS avec composants cohérents.

## 🎨 Palette de Couleurs

### Couleurs Primaires
```css
/* Bleu - Couleur principale */
--primary-50: #eff6ff;
--primary-100: #dbeafe;
--primary-200: #bfdbfe;
--primary-300: #93c5fd;
--primary-400: #60a5fa;
--primary-500: #3b82f6;  /* Couleur principale */
--primary-600: #2563eb;
--primary-700: #1d4ed8;
--primary-800: #1e40af;
--primary-900: #1e3a8a;
```

### Couleurs Secondaires
```css
/* Gris - Couleurs neutres */
--gray-50: #f9fafb;
--gray-100: #f3f4f6;
--gray-200: #e5e7eb;
--gray-300: #d1d5db;
--gray-400: #9ca3af;
--gray-500: #6b7280;   /* Gris secondaire */
--gray-600: #4b5563;
--gray-700: #374151;
--gray-800: #1f2937;
--gray-900: #111827;
```

### Couleurs Sémantiques
```css
/* Succès */
--success-50: #ecfdf5;
--success-100: #d1fae5;
--success-500: #10b981;
--success-600: #059669;
--success-700: #047857;

/* Attention */
--warning-50: #fffbeb;
--warning-100: #fef3c7;
--warning-500: #f59e0b;
--warning-600: #d97706;
--warning-700: #b45309;

/* Erreur */
--error-50: #fef2f2;
--error-100: #fee2e2;
--error-500: #ef4444;
--error-600: #dc2626;
--error-700: #b91c1c;

/* Information */
--info-50: #eff6ff;
--info-100: #dbeafe;
--info-500: #3b82f6;
--info-600: #2563eb;
--info-700: #1d4ed8;
```

## 📝 Typographie

### Famille de Police
```css
/* Police principale */
font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;

/* Police monospace (code) */
font-family: 'Fira Code', 'Monaco', 'Cascadia Code', 'Roboto Mono', monospace;
```

### Échelle Typographique
```css
/* Titres */
.text-xs    { font-size: 0.75rem; line-height: 1rem; }     /* 12px */
.text-sm    { font-size: 0.875rem; line-height: 1.25rem; } /* 14px */
.text-base  { font-size: 1rem; line-height: 1.5rem; }     /* 16px */
.text-lg    { font-size: 1.125rem; line-height: 1.75rem; } /* 18px */
.text-xl    { font-size: 1.25rem; line-height: 1.75rem; }  /* 20px */
.text-2xl   { font-size: 1.5rem; line-height: 2rem; }     /* 24px */
.text-3xl   { font-size: 1.875rem; line-height: 2.25rem; } /* 30px */
.text-4xl   { font-size: 2.25rem; line-height: 2.5rem; }  /* 36px */
.text-5xl   { font-size: 3rem; line-height: 1; }          /* 48px */
```

### Poids de Police
```css
.font-thin       { font-weight: 100; }
.font-extralight { font-weight: 200; }
.font-light      { font-weight: 300; }
.font-normal     { font-weight: 400; }
.font-medium     { font-weight: 500; }
.font-semibold   { font-weight: 600; }
.font-bold       { font-weight: 700; }
.font-extrabold  { font-weight: 800; }
.font-black      { font-weight: 900; }
```

## 📏 Espacements

### Échelle d'Espacement
```css
.p-0    { padding: 0; }
.p-1    { padding: 0.25rem; }   /* 4px */
.p-2    { padding: 0.5rem; }    /* 8px */
.p-3    { padding: 0.75rem; }   /* 12px */
.p-4    { padding: 1rem; }      /* 16px */
.p-5    { padding: 1.25rem; }   /* 20px */
.p-6    { padding: 1.5rem; }    /* 24px */
.p-8    { padding: 2rem; }      /* 32px */
.p-10   { padding: 2.5rem; }    /* 40px */
.p-12   { padding: 3rem; }      /* 48px */
.p-16   { padding: 4rem; }      /* 64px */
```

### Marges
```css
.m-0    { margin: 0; }
.m-1    { margin: 0.25rem; }    /* 4px */
.m-2    { margin: 0.5rem; }     /* 8px */
.m-4    { margin: 1rem; }       /* 16px */
.m-6    { margin: 1.5rem; }     /* 24px */
.m-8    { margin: 2rem; }       /* 32px */
.m-auto { margin: auto; }
```

## 🔲 Composants UI

### Boutons

#### Variantes
```html
<!-- Bouton Principal -->
<button class="bg-primary-500 hover:bg-primary-600 text-white font-medium py-2 px-4 rounded-lg transition-colors">
  Action Principale
</button>

<!-- Bouton Secondaire -->
<button class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg transition-colors">
  Action Secondaire
</button>

<!-- Bouton Danger -->
<button class="bg-error-500 hover:bg-error-600 text-white font-medium py-2 px-4 rounded-lg transition-colors">
  Action Destructive
</button>

<!-- Bouton Ghost -->
<button class="text-primary-600 hover:bg-primary-50 font-medium py-2 px-4 rounded-lg transition-colors">
  Action Subtile
</button>
```

#### Tailles
```html
<!-- Petit -->
<button class="text-sm py-1 px-3 rounded">
  Petit
</button>

<!-- Moyen (par défaut) -->
<button class="py-2 px-4 rounded-lg">
  Moyen
</button>

<!-- Grand -->
<button class="text-lg py-3 px-6 rounded-xl">
  Grand
</button>
```

### Champs de Saisie

```html
<!-- Input Standard -->
<input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all" 
       type="text" 
       placeholder="Entrez votre texte">

<!-- Input avec Erreur -->
<input class="w-full px-3 py-2 border border-error-300 rounded-lg focus:ring-2 focus:ring-error-500 focus:border-error-500 transition-all" 
       type="text" 
       placeholder="Champ avec erreur">

<!-- Input Désactivé -->
<input class="w-full px-3 py-2 border border-gray-200 bg-gray-50 text-gray-500 rounded-lg cursor-not-allowed" 
       type="text" 
       placeholder="Champ désactivé" 
       disabled>
```

### Cartes

```html
<!-- Carte Standard -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
  <h3 class="text-lg font-semibold text-gray-900 mb-2">Titre de la carte</h3>
  <p class="text-gray-600">Contenu de la carte</p>
</div>

<!-- Carte Interactive -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow cursor-pointer">
  <h3 class="text-lg font-semibold text-gray-900 mb-2">Carte Cliquable</h3>
  <p class="text-gray-600">Carte avec effet hover</p>
</div>
```

## 🌙 Mode Sombre

### Couleurs Mode Sombre
```css
/* Variables CSS pour le mode sombre */
:root {
  --bg-primary: #ffffff;
  --bg-secondary: #f9fafb;
  --text-primary: #111827;
  --text-secondary: #6b7280;
  --border-color: #e5e7eb;
}

.dark {
  --bg-primary: #111827;
  --bg-secondary: #1f2937;
  --text-primary: #f9fafb;
  --text-secondary: #d1d5db;
  --border-color: #374151;
}
```

### Classes Utilitaires
```html
<!-- Background -->
<div class="bg-white dark:bg-gray-800">

<!-- Texte -->
<p class="text-gray-900 dark:text-gray-100">

<!-- Bordures -->
<div class="border border-gray-200 dark:border-gray-700">
```

## 📐 Grille et Layout

### Conteneurs
```css
.container     { max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
.container-sm  { max-width: 768px; margin: 0 auto; padding: 0 1rem; }
.container-lg  { max-width: 1400px; margin: 0 auto; padding: 0 1rem; }
```

### Grille Responsive
```html
<!-- Grille 12 colonnes -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
  <div class="col-span-1">Colonne 1</div>
  <div class="col-span-1">Colonne 2</div>
  <div class="col-span-1">Colonne 3</div>
</div>

<!-- Flexbox -->
<div class="flex flex-col md:flex-row gap-4">
  <div class="flex-1">Flexible</div>
  <div class="w-64">Largeur fixe</div>
</div>
```

## 🎯 États et Interactions

### États des Composants
```css
/* Hover */
.hover\:bg-primary-600:hover { background-color: #2563eb; }

/* Focus */
.focus\:ring-2:focus { 
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
}

/* Active */
.active\:bg-primary-700:active { background-color: #1d4ed8; }

/* Disabled */
.disabled\:opacity-50:disabled { opacity: 0.5; }
.disabled\:cursor-not-allowed:disabled { cursor: not-allowed; }
```

### Transitions
```css
.transition-all     { transition: all 150ms ease-in-out; }
.transition-colors  { transition: color, background-color, border-color 150ms ease-in-out; }
.transition-shadow  { transition: box-shadow 150ms ease-in-out; }
.transition-transform { transition: transform 150ms ease-in-out; }
```

## 🔧 Utilitaires Personnalisés

### Classes Personnalisées
```css
/* Élévation (ombres) */
.elevation-1 { box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
.elevation-2 { box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
.elevation-3 { box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1); }
.elevation-4 { box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15); }

/* Bordures arrondies personnalisées */
.rounded-card { border-radius: 12px; }
.rounded-button { border-radius: 8px; }
.rounded-input { border-radius: 6px; }

/* Animations */
.animate-fade-in {
  animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
```

## 📱 Responsive Design

### Breakpoints
```css
/* Breakpoints Tailwind */
sm: 640px   /* Small devices */
md: 768px   /* Medium devices */
lg: 1024px  /* Large devices */
xl: 1280px  /* Extra large devices */
2xl: 1536px /* 2X large devices */
```

### Exemples Responsive
```html
<!-- Texte responsive -->
<h1 class="text-2xl md:text-3xl lg:text-4xl">

<!-- Espacement responsive -->
<div class="p-4 md:p-6 lg:p-8">

<!-- Grille responsive -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
```

## ♿ Accessibilité

### Contrastes
```css
/* Contrastes minimums WCAG AA */
Texte normal: 4.5:1
Texte large: 3:1
Éléments UI: 3:1
```

### Focus Management
```css
/* Focus visible */
.focus\:outline-none:focus { outline: none; }
.focus\:ring-2:focus { 
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
}

/* Skip link */
.skip-link {
  position: absolute;
  top: -40px;
  left: 6px;
  background: #000;
  color: #fff;
  padding: 8px;
  text-decoration: none;
  z-index: 1000;
}

.skip-link:focus {
  top: 6px;
}
```

## 🎨 Exemples d'Application

### Page de Connexion
```html
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center">
  <div class="max-w-md w-full bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6 text-center">
      Connexion
    </h2>
    
    <form class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Email
        </label>
        <input type="email" 
               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-100 transition-all">
      </div>
      
      <button type="submit" 
              class="w-full bg-primary-500 hover:bg-primary-600 text-white font-medium py-2 px-4 rounded-lg transition-colors">
        Se connecter
      </button>
    </form>
  </div>
</div>
```

### Dashboard Card
```html
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
  <div class="flex items-center justify-between mb-4">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
      Transcriptions Récentes
    </h3>
    <button class="text-primary-600 hover:text-primary-700 font-medium transition-colors">
      Voir tout
    </button>
  </div>
  
  <div class="space-y-3">
    <div class="flex items-center justify-between py-2">
      <span class="text-gray-900 dark:text-gray-100">Réunion équipe</span>
      <span class="px-2 py-1 bg-success-100 text-success-700 text-xs rounded-full">
        Terminé
      </span>
    </div>
  </div>
</div>
```

## 📋 Checklist Design

### Avant de Publier
- [ ] Contrastes vérifiés (WCAG AA)
- [ ] Navigation clavier fonctionnelle
- [ ] Responsive sur tous les breakpoints
- [ ] Mode sombre implémenté
- [ ] États hover/focus définis
- [ ] Animations fluides (<300ms)
- [ ] Textes lisibles (min 16px)
- [ ] Loading states inclus
- [ ] Error states gérés