# Infrastructure Layer

La couche Infrastructure implémente les détails techniques et les adapters pour les interfaces définies dans les couches Domain et Application.

## 🏗️ Structure

### Repository/
Implémentations concrètes des interfaces Repository du Domain Layer.

- **SQLite/** - Implémentations SQLite optimisées
- **InMemory/** - Implémentations en mémoire pour les tests

### External/
Adapters pour les services externes et APIs.

- **OpenAI/** - Client OpenAI API (Whisper, GPT)
- **VideoDownload/** - Services de téléchargement vidéo

### Service/
Services d'infrastructure (cache, stockage, session).

### Http/
Contrôleurs HTTP et middleware web.

- **Controller/** - Contrôleurs utilisant Application Services
- **Middleware/** - Middleware d'authentification et validation

### Configuration/
Gestion centralisée de la configuration.

### Cache/
Implémentations de cache multi-niveaux.

### Storage/
Abstraction du stockage de fichiers.

### Queue/
Système de queue pour le traitement asynchrone.

### Session/
Gestion des sessions utilisateur.

### Persistence/
Gestionnaires de base de données et migrations.

## 🔧 Principes

1. **Inversion de dépendance** - Infrastructure dépend des interfaces Domain/Application
2. **Pas de logique métier** - Uniquement des détails d'implémentation
3. **Abstractions** - Interfaces pour faciliter les tests et la flexibilité
4. **Configuration** - Externalisée et validée
5. **Résilience** - Gestion d'erreurs, retry, circuit breakers

## 📦 Dépendances

- Domain Layer (interfaces uniquement)
- Application Layer (interfaces uniquement)
- Libraries externes (OpenAI, PDO, etc.)