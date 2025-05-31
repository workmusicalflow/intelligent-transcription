#!/bin/bash

# Update Documentation Index
# Automatically generates and updates documentation index files

set -e

echo "🗒️  Updating Documentation Index..."

# Function to extract title from markdown file
extract_title() {
    head -n 10 "$1" | grep -E "^#[^#]" | head -n 1 | sed 's/^# //'
}

# Function to extract description from markdown file
extract_description() {
    # Look for first paragraph after title
    sed -n '/^# /,/^$/p' "$1" | tail -n +2 | head -n 1
}

# Function to get last modified date
get_last_modified() {
    if [ -f "$1" ]; then
        git log -1 --format="%ci" "$1" 2>/dev/null | cut -d' ' -f1 || date +%Y-%m-%d
    else
        date +%Y-%m-%d
    fi
}

# Update main README.md
echo "📝 Updating main README.md..."
cat > README.md << 'EOF'
# Intelligent Transcription

> Une application moderne de transcription audio et vidéo utilisant l'IA

[![Documentation](https://img.shields.io/badge/docs-auto--generated-blue)](https://docs.intelligent-transcription.dev)
[![Tests](https://img.shields.io/github/workflow/status/your-org/intelligent-transcription/Tests)](https://github.com/your-org/intelligent-transcription/actions)
[![Coverage](https://img.shields.io/codecov/c/github/your-org/intelligent-transcription)](https://codecov.io/gh/your-org/intelligent-transcription)
[![License](https://img.shields.io/github/license/your-org/intelligent-transcription)](LICENSE)

## 🎯 Fonctionnalités

- **Transcription IA** : Conversion audio/vidéo vers texte avec OpenAI Whisper
- **Chat Contextuel** : Discussion intelligente sur vos transcriptions
- **Interface Moderne** : Interface Vue.js 3 responsive et intuitive
- **Temps Réel** : Suivi en direct du processus de transcription
- **Multi-formats** : Support audio, vidéo et URLs YouTube
- **API GraphQL** : API moderne avec subscriptions en temps réel

## 🚀 Démarrage Rapide

### Prérequis

- PHP 8.2+
- Node.js 18+
- Composer
- SQLite

### Installation

```bash
# Cloner le projet
git clone https://github.com/your-org/intelligent-transcription.git
cd intelligent-transcription

# Backend
composer install
cp config.example.php config.php
# Configurer les clés API dans config.php

# Frontend
cd frontend
npm install

# Démarrer les serveurs
./start-servers.sh
```

### Configuration

1. **OpenAI API** : Obtenir une clé API sur [OpenAI](https://platform.openai.com/)
2. **Base de données** : SQLite configuré automatiquement
3. **Variables d'environnement** : Copier et modifier `config.example.php`

## 📖 Documentation

| Section | Description | Lien |
|---------|-------------|------|
| 🏗️ **Architecture** | Design du système et structure | [Architecture](docs/architecture/) |
| 🔧 **API Reference** | Documentation REST & GraphQL | [API Docs](docs/backend/api/) |
| 🎨 **Components** | Composants UI et Storybook | [Components](docs/components/) |
| 🧪 **Testing** | Tests et couverture | [Testing Guide](docs/testing/) |
| 🚀 **Deployment** | Guide de déploiement | [Deployment](docs/deployment/) |
| 📋 **ADRs** | Décisions architecturales | [ADRs](docs/adr/) |

> 📚 **Documentation complète** : [docs.intelligent-transcription.dev](https://docs.intelligent-transcription.dev)

## 🏗️ Architecture

### Backend (Clean Architecture)

```
src/
├── Domain/          # Entités et logique métier
├── Application/     # Services et cas d'usage
├── Infrastructure/  # Base de données, APIs externes
└── Controllers/     # Points d'entrée HTTP/GraphQL
```

### Frontend (Vue.js 3)

```
src/
├── components/      # Composants réutilisables
├── views/          # Pages de l'application
├── stores/         # Gestion d'état Pinia
├── composables/    # Logique réutilisable
└── api/           # Clients API
```

## 🧪 Tests

```bash
# Tests Backend PHP
php vendor/bin/phpunit

# Tests Frontend
cd frontend
npm run test
npm run test:coverage

# Tests E2E
npm run cypress:run
```

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/amazing-feature`)
3. Commit les changements (`git commit -m 'Add amazing feature'`)
4. Push la branche (`git push origin feature/amazing-feature`)
5. Ouvrir une Pull Request

Voir le [Guide de Contribution](docs/contributing.md) pour plus de détails.

## 📋 Roadmap

- [ ] Interface d'administration avancée
- [ ] Support multi-langues
- [ ] Intégration avec plus de services de stockage
- [ ] API mobile dédiée
- [ ] Amélioration de l'IA contextuelle

## 📄 Licence

Distribué sous la licence MIT. Voir `LICENSE` pour plus d'informations.

## 🙏 Remerciements

- [OpenAI](https://openai.com/) pour l'API Whisper
- [Vue.js](https://vuejs.org/) pour le framework frontend
- [Tailwind CSS](https://tailwindcss.com/) pour le styling

---

<div align="center">
  <strong>⚡ Propulsé par l'IA et développé avec ❤️</strong>
</div>
EOF

# Update docs/README.md with comprehensive documentation index
echo "📚 Updating docs/README.md..."
cat > docs/README.md << 'EOF'
# Documentation Hub

Bienvenue dans la documentation complète d'Intelligent Transcription.

## 📚 Table des Matières

### 🏗️ Architecture
- [Vue d'ensemble du système](architecture/overview.md)
- [Architecture Frontend](architecture/frontend.md)
- [Architecture Backend](architecture/backend.md)
- [Guide de déploiement](architecture/deployment.md)

### 🔧 API Reference
- [API REST Documentation](backend/api/)
- [Types TypeScript](../frontend/docs/api/)
- [Schema GraphQL](../graphql/)

### 🎨 Interface Utilisateur
- [Guide des composants](components/)
- [Storybook](../frontend/storybook-static/)
- [Système de design](design-system/)

### 🧪 Tests & Qualité
- [Guide de test](testing/)
- [Couverture de code](../frontend/coverage/)
- [Métriques qualité](quality/)

### 🚀 Développement
- [Configuration environnement](setup/)
- [Guide de contribution](contributing/)
- [Workflow de développement](workflows/)

### 📋 Décisions
- [Records de décisions architecturales](adr/)
- [Changelog](../CHANGELOG.md)
- [Roadmap](../ROADMAP.md)

## 🔄 Mise à Jour Automatique

Cette documentation est automatiquement générée et mise à jour :

- **À chaque commit** sur la branche `main`
- **Quotidiennement** à 2h00 UTC
- **Sur chaque Pull Request** pour validation

### Sources de Documentation

| Type | Source | Outil | Fréquence |
|------|--------|-------|----------|
| API Backend | Code PHP + OpenAPI | PHPDoc + Redoc | Chaque commit |
| API Frontend | TypeScript interfaces | TypeDoc | Chaque commit |
| Composants | Fichiers Vue + commentaires | vue-docgen-cli | Chaque commit |
| Architecture | Fichiers Markdown | Scripts bash | Manuel + validation |
| Tests | Résultats de tests | Vitest + PHPUnit | Chaque commit |
| Storybook | Stories des composants | Storybook | Chaque commit |

## 🛠️ Outils de Documentation

- **GitHub Actions** : Automatisation CI/CD
- **TypeDoc** : Documentation TypeScript
- **PHPDoc** : Documentation PHP
- **vue-docgen-cli** : Documentation composants Vue
- **Storybook** : Démonstration composants
- **Redoc** : Rendu OpenAPI
- **Markdown** : Documentation manuelle

## 🎯 Standards de Documentation

### Code Comments
```php
/**
 * Creates a new transcription from audio file
 * 
 * @param AudioFile $audioFile The uploaded audio file
 * @param Language $language Target transcription language
 * @return TranscriptionId The created transcription identifier
 * @throws TranscriptionException When transcription fails
 */
public function createTranscription(AudioFile $audioFile, Language $language): TranscriptionId
```

### Component Documentation
```vue
<template>
  <!-- Component implementation -->
</template>

<script setup lang="ts">
/**
 * Password strength indicator component
 * 
 * Displays a visual indicator of password strength with validation criteria.
 * Updates in real-time as the user types their password.
 * 
 * @example
 * ```vue
 * <PasswordStrengthIndicator :password="userPassword" />
 * ```
 */

interface Props {
  /** The password to evaluate */
  password: string
}
</script>
```

### ADR Template
Tous les ADRs suivent le [template standard](adr/template.md) avec :
- Contexte et problème
- Décision prise
- Alternatives considérées
- Conséquences positives/négatives
- Plan d'implémentation

## 📞 Support

Pour toute question sur la documentation :

1. **Issues GitHub** : [Créer une issue](https://github.com/your-org/intelligent-transcription/issues)
2. **Discussions** : [GitHub Discussions](https://github.com/your-org/intelligent-transcription/discussions)
3. **Email** : documentation@intelligent-transcription.dev

---

*Documentation générée automatiquement le $(date +'%d/%m/%Y à %H:%M')*
EOF

# Update component documentation index
if [ -d "frontend/docs/components" ]; then
    echo "🎨 Updating component documentation index..."
    cat > frontend/docs/components/README.md << 'EOF'
# Component Documentation

Documentation automatically generated from Vue component files.

## UI Components

### Form Components
- [Button](Button.md) - Primary button component with variants
- [Input](Input.md) - Text input with validation and icons
- [LoadingSpinner](LoadingSpinner.md) - Loading indication component

### Layout Components
- [Sidebar](../components/layout/Sidebar.md) - Main navigation sidebar
- [TopNavigation](../components/layout/TopNavigation.md) - Top navigation bar
- [UserMenu](../components/layout/UserMenu.md) - User dropdown menu

### Feature Components
- [PasswordStrengthIndicator](../components/auth/PasswordStrengthIndicator.md) - Password validation
- [TranscriptionCard](../components/transcription/TranscriptionCard.md) - Transcription display
- [TranscriptionProgress](../components/transcription/TranscriptionProgress.md) - Progress indicator

## Component Standards

All components should include:

1. **TypeScript interfaces** for props
2. **JSDoc comments** for documentation
3. **Data-testid attributes** for testing
4. **Accessibility attributes** (aria-\*, role)
5. **Responsive design** with Tailwind classes

## Usage Examples

Each component includes usage examples and prop descriptions.
EOF
fi

# Generate changelog from git history
echo "📅 Generating changelog..."
echo "# Changelog" > CHANGELOG.md
echo "" >> CHANGELOG.md
echo "All notable changes to this project will be documented in this file." >> CHANGELOG.md
echo "" >> CHANGELOG.md
echo "## [Unreleased]" >> CHANGELOG.md
echo "" >> CHANGELOG.md

# Get recent commits and format them
git log --oneline -10 --pretty=format:"- %s" >> CHANGELOG.md 2>/dev/null || echo "- Initial commit" >> CHANGELOG.md

echo "✅ Documentation index updated successfully!"
echo "📚 Main documentation: README.md"
echo "🗒️ Documentation hub: docs/README.md"
echo "📅 Changelog: CHANGELOG.md"