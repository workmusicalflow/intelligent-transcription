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
