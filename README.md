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
