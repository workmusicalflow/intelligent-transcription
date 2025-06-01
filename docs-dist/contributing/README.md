# Contributing Guide

## Vue d'ensemble

Merci de votre intérêt pour contribuer à Intelligent Transcription ! Ce guide vous aidera à démarrer.

## 🚀 Démarrage Rapide

### 1. Fork et Clone
```bash
# Fork le repo sur GitHub, puis :
git clone https://github.com/YOUR_USERNAME/intelligent-transcription.git
cd intelligent-transcription
git remote add upstream https://github.com/workmusicalflow/intelligent-transcription.git
```

### 2. Configuration Environnement
```bash
# Backend
composer install
cp config.example.php config.php
# Ajouter vos clés API dans config.php

# Frontend
cd frontend
npm install
cd ..

# Démarrer les serveurs
./start-servers.sh
```

## 🏗️ Architecture du Projet

### Backend (Clean Architecture)
```
src/
├── Domain/              # Logique métier pure
│   ├── Transcription/   # Entités de transcription
│   └── User/            # Entités utilisateur
├── Application/         # Cas d'usage
│   ├── Command/         # Commandes (write)
│   └── Query/           # Requêtes (read)
├── Infrastructure/      # Détails techniques
│   ├── External/        # APIs externes
│   └── Persistence/     # Base de données
└── Controllers/         # Points d'entrée HTTP
```

### Frontend (Vue 3 + TypeScript)
```
src/
├── components/          # Composants réutilisables
│   ├── ui/              # Composants UI génériques
│   ├── layout/          # Composants de mise en page
│   └── feature/         # Composants spécifiques
├── views/               # Pages/routes
├── stores/              # Gestion d'état Pinia
├── composables/         # Logique réutilisable
└── api/                 # Clients API
```

## 📋 Standards de Code

### PHP (Backend)

#### PSR-12 + Clean Architecture
```php
<?php

namespace App\Domain\Transcription\Entity;

use App\Domain\Common\Entity\AggregateRoot;
use App\Domain\Transcription\ValueObject\TranscriptionId;
use App\Domain\Transcription\Event\TranscriptionCreated;

/**
 * Représente une transcription audio/vidéo
 * 
 * @package App\Domain\Transcription\Entity
 */
final class Transcription extends AggregateRoot
{
    private function __construct(
        private TranscriptionId $id,
        private AudioFile $audioFile,
        private Language $language,
        private TranscriptionStatus $status
    ) {
        $this->recordEvent(new TranscriptionCreated($this->id));
    }
    
    public static function create(
        TranscriptionId $id,
        AudioFile $audioFile, 
        Language $language
    ): self {
        return new self(
            $id,
            $audioFile,
            $language,
            TranscriptionStatus::pending()
        );
    }
    
    public function startProcessing(): void
    {
        if (!$this->status->isPending()) {
            throw new InvalidTranscriptionStateException(
                'Cannot start processing: transcription is not pending'
            );
        }
        
        $this->status = TranscriptionStatus::processing();
        $this->recordEvent(new TranscriptionProcessingStarted($this->id));
    }
}
```

#### Conventions
- **Namespace** : Follow PSR-4
- **Types** : Use strict typing
- **Documentation** : PHPDoc pour toutes les méthodes publiques
- **Tests** : Un test par méthode publique minimum

### TypeScript (Frontend)

#### Composition API + TypeScript
```vue
<template>
  <div class="transcription-card" data-testid="transcription-card">
    <h3 class="text-lg font-semibold">{{ transcription.title }}</h3>
    <TranscriptionStatus :status="transcription.status" />
    <Button 
      @click="downloadTranscription" 
      :loading="downloading"
      data-testid="download-button"
    >
      Télécharger
    </Button>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { Transcription } from '@/types'
import { transcriptionApi } from '@/api/transcriptions'
import TranscriptionStatus from './TranscriptionStatus.vue'
import Button from '@/components/ui/Button.vue'

/**
 * Carte d'affichage d'une transcription
 * 
 * @example
 * ```vue
 * <TranscriptionCard :transcription="transcription" />
 * ```
 */

interface Props {
  /** La transcription à afficher */
  transcription: Transcription
}

const props = defineProps<Props>()

const downloading = ref(false)

/**
 * Télécharge le fichier de transcription
 */
async function downloadTranscription(): Promise<void> {
  try {
    downloading.value = true
    await transcriptionApi.download(props.transcription.id)
  } catch (error) {
    console.error('Download failed:', error)
  } finally {
    downloading.value = false
  }
}
</script>
```

#### Conventions
- **Composition API** : Toujours utiliser `<script setup>`
- **TypeScript** : Interfaces pour toutes les props
- **Data-testid** : Pour tous les éléments interactifs
- **JSDoc** : Documentation des composants complexes

## 🧪 Workflow de Test

### Avant chaque commit
```bash
# Tests backend
vendor/bin/phpunit

# Tests frontend
cd frontend
npm run test
npm run type-check
npm run lint

# Tests E2E (optionnel pour dev)
npm run cypress:run
```

### Test-Driven Development (recommandé)
```bash
# 1. Écrire le test qui échoue
# 2. Écrire le code minimum pour passer
# 3. Refactorer
# 4. Répéter
```

## 🔀 Git Workflow

### Branches
```bash
# Nouvelle feature
git checkout -b feature/transcription-segments

# Bugfix
git checkout -b fix/upload-validation

# Documentation
git checkout -b docs/api-examples
```

### Commits (Convention Conventionnelle)
```bash
# Format
<type>(<scope>): <description>

# Exemples
feat(transcription): add audio segment support
fix(auth): resolve token expiration issue  
docs(api): add GraphQL examples
test(ui): add Button component tests
refactor(domain): extract transcription value objects
```

### Pull Request Process
1. **Fork** et créer une branche
2. **Implémenter** avec tests
3. **Vérifier** que tous les tests passent
4. **Ouvrir PR** avec description détaillée
5. **Répondre** aux commentaires de review
6. **Merge** après approbation

## 📋 Types de Contribution

### 🐛 Bug Reports
**Template d'issue :**
```markdown
## Description
Description claire du problème

## Étapes pour reproduire
1. Aller à...
2. Cliquer sur...
3. Voir l'erreur

## Comportement attendu
Ce qui devrait se passer

## Captures d'écran
Si applicable

## Environnement
- OS: [e.g. macOS 12.0]
- Navigateur: [e.g. Chrome 95]
- Version: [e.g. 1.2.3]
```

### ✨ Feature Requests
**Template d'issue :**
```markdown
## Problème à résoudre
Description du besoin utilisateur

## Solution proposée
Idée de solution

## Alternatives considérées
Autres approches possibles

## Contexte additionnel
Informations supplémentaires
```

### 📝 Documentation
- **API** : Exemples concrets d'utilisation
- **Guides** : Tutoriels pas-à-pas
- **Architecture** : Diagrammes et explications
- **README** : Instructions claires

### 🚀 Nouvelles Features

#### Process de développement
1. **Discussion** : Issue pour discuter l'approche
2. **ADR** : Architecture Decision Record si nécessaire
3. **Design** : Maquettes/diagrammes si UI/UX
4. **Implementation** : Code + tests
5. **Documentation** : Mise à jour des docs

## 🚑 Aide et Support

### 💬 Où poser des questions
- **GitHub Discussions** : Questions générales
- **Issues** : Bugs et demandes de features
- **Discord/Slack** : Chat en temps réel (si disponible)

### 📚 Ressources utiles
- [Clean Architecture](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)
- [Vue 3 Composition API](https://vuejs.org/guide/introduction.html)
- [TypeScript Handbook](https://www.typescriptlang.org/docs/)
- [Conventional Commits](https://www.conventionalcommits.org/)

## 🎆 Reconnaissance

Tous les contributeurs sont reconnus dans :
- **README.md** : Section contributeurs
- **Releases** : Notes de version
- **Documentation** : Pages d'équipe

### Hall of Fame
```markdown
## Contributors

- [@workmusicalflow](https://github.com/workmusicalflow) - Project maintainer
- [@yourname](https://github.com/yourname) - Feature X, Bug fix Y
```

## 📆 Calendrier des Releases

- **Minor releases** : Chaque mois
- **Patch releases** : Selon les besoins
- **Major releases** : Tous les 6 mois

---

**Merci de contribuer à Intelligent Transcription ! 🚀**