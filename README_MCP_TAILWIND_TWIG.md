# Modernisation de l'Application de Transcription

Ce document décrit les améliorations apportées à l'application de transcription audio, notamment l'implémentation de Tailwind CSS et Twig pour améliorer respectivement le design et le templating.

## Améliorations implémentées

### 1. Structure MVC

Une nouvelle architecture a été mise en place pour suivre le pattern MVC (Modèle-Vue-Contrôleur) :

- **Modèles/Services** : `src/Services/` contient les classes de service qui encapsulent la logique métier
- **Vues** : `templates/` contient les fichiers de template Twig
- **Contrôleurs** : `src/Controllers/` contient les contrôleurs qui gèrent les requêtes et coordonnent les services

### 2. Templating avec Twig

Twig a été intégré comme moteur de template :

- `src/Template/TwigManager.php` : Classe utilitaire pour initialiser et utiliser Twig
- `templates/base/layout.twig` : Layout principal de l'application
- Conversion des templates PHP existants en templates Twig organisés hiérarchiquement

### 3. Préparation pour Tailwind CSS

Tailwind CSS v3.4 a été configuré pour améliorer le design :

- `tailwind.config.js` : Configuration de Tailwind avec des couleurs personnalisées
- `postcss.config.js` : Configuration de PostCSS pour le traitement des styles
- `assets/css/tailwind.css` : Fichier source Tailwind avec des composants personnalisés

## Structure des fichiers

```
├── assets/
│   └── css/
│       ├── style.css      # Fichier CSS compilé (existant)
│       └── tailwind.css   # Fichier source Tailwind (à compiler)
├── src/
│   ├── Controllers/
│   │   └── TranscriptionController.php
│   ├── Services/
│   │   ├── ProcessingService.php
│   │   └── TranscriptionService.php
│   └── Template/
│       └── TwigManager.php
├── templates/
│   ├── base/
│   │   └── layout.twig
│   ├── home/
│   │   └── index.twig
│   └── processing/
│       └── show.twig
├── tailwind.config.js
└── postcss.config.js
```

## Tâches à compléter

1. **Compilation de Tailwind CSS** :

   - Réinstaller les dépendances Node.js si nécessaire (`rm -rf node_modules && npm install`)
   - Exécuter la commande de build (`npx tailwindcss -i ./assets/css/tailwind.css -o ./assets/css/style.css --minify`)

2. **Templates Twig manquants** :

   - Créer le template pour les résultats (`templates/result/show.twig`)
   - Créer le template pour le chat (`templates/chat/index.twig`)

3. **Services supplémentaires** :
   - Implémenter `YouTubeService.php` pour le téléchargement et le traitement des vidéos YouTube

## Utilisation

1. L'application utilise maintenant Twig pour le rendu des templates :

   ```php
   TwigManager::display('home/index.twig', [
       'active_page' => 'home',
       'max_upload_size_mb' => MAX_UPLOAD_SIZE_MB
   ]);
   ```

2. Les routes sont gérées dans `index.php` et dirigées vers les contrôleurs appropriés.

3. Les services encapsulent la logique métier et peuvent être utilisés par les contrôleurs.

## Notes techniques

1. **Autoloading** :

   - Un autoloader simple basé sur PSR-4 est utilisé pour charger les classes automatiquement
   - Le namespace racine correspond au dossier `src/`

2. **Configurations** :

   - Les constantes et configurations globales sont définies dans `config.php`

3. **Assets** :
   - Les feuilles de style et scripts se trouvent dans le dossier `assets/`
   - Le fichier `assets/css/style.css` est généré à partir de `assets/css/tailwind.css`
