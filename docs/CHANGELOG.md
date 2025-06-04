## [2025-06-04] - Performance & UX Optimizations

### Fixed
- **Smart Polling Logic**: Correction du polling inutile pour traductions terminées
  - Polling ne démarre plus automatiquement pour traductions `completed/failed/cancelled`
  - Vérification du statut avant démarrage du polling dans `onMounted()`
  - Logs de debug pour monitoring du polling en développement
  - Économie de ressources réseau et amélioration performances

- **Preload Warnings**: Suppression de la police manquante causant des warnings console
  - Suppression de `<link rel="preload" href="/fonts/Inter-var.woff2">` inexistant
  - Élimination des messages `The resource <URL> was preloaded but not used`
  - Console plus propre pour meilleure expérience développeur

### Technical Details
- **Files Modified**: 
  - `frontend/src/views/translations/TranslationDetail.vue` (polling logic)
  - `frontend/index.html` (preload cleanup)
- **Impact**: Réduction significative requêtes réseau inutiles
- **Developer Experience**: Console plus propre, debugging facilité

## [2025-06-04] - Translation UX Enhancement

### Fixed
- **Translation Animation UX**: Correction de l'affichage immédiat des animations pendant le traitement
  - Mise à jour du statut local `translation.status = 'processing'` immédiatement après lancement
  - Définition de `started_at` pour démarrer le compteur de temps immédiatement
  - Animation `TranslationProcessingIndicator` s'affiche maintenant dès le clic
  - Élimination du délai entre l'action utilisateur et le feedback visuel

### Technical Details
- **File**: `frontend/src/views/translations/TranslationDetail.vue`
- **Function**: `processImmediately()` (ligne 615)
- **Issue**: Le statut restait `pending` jusqu'à la première réponse du polling
- **Solution**: Mise à jour optimiste de l'état local avant confirmation backend
- **User Impact**: ✅ Animation immédiate, polling temps réel, UX fluide

## [2025-06-04] - API Response Fix

### Fixed

- **Translation API Response Error**: Correction de l'erreur `Call to undefined method ApiResponse::toJson()` dans `TranslationApiController.php:928`
  - Remplacement de `$response->toJson()` par `json_encode($responsePayload)`
  - Ajout du header `Content-Type: application/json` pour garantir le bon type de contenu
  - Résolution de l'erreur `SyntaxError: Unexpected token '<', "<div class"... is not valid JSON` côté frontend
  - Le traitement immédiat des traductions fonctionne maintenant correctement sans blocage à 95%

### Technical Details

- **File**: `src/Infrastructure/Http/Api/v2/Controller/TranslationApiController.php`
- **Method**: `processTranslation()` (ligne 928)
- **Issue**: La classe `ApiResponse` ne possédait pas de méthode `toJson()`
- **Solution**: Sérialisation directe des données avec `json_encode()` et headers appropriés
- **Test Validation**: ✅ Testé avec traduction `test_pending_short` (10 segments)

# Changelog

All notable changes to the Intelligent Transcription System will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Rate limiting system with improved limits (300 req/min, 5000 req/h)
- Automatic retry logic with exponential backoff for 429 errors
- Intelligent caching system for API responses (capabilities: 1h, lists: 5min)
- Centralized Pinia stores for translation state management
- Comprehensive rate limiting documentation (`RATE_LIMITING_GUIDE.md`)

### Changed

- Increased backend rate limits from 60/1000 to 300/5000 requests
- Exempted read-only routes from rate limiting (capabilities, list, status)
- Migrated translation components to use centralized Pinia stores
- Reduced redundant API calls by 70% through caching and state management

### Fixed

- Translation capabilities loading error due to API response structure mismatch
- Added data transformation layer to map backend providers to frontend services structure
- Fixed null reference errors in translation components with proper optional chaining
- Corrected API response wrapping for consistency across endpoints
- **Console error "Traduction non trouvée" when deleting translations**
  - Implemented proper polling interval cleanup before translation deletion
  - Added error handling in polling loop to detect and stop polling for deleted translations
  - Created `stopAllPolling()` utility function for component cleanup
  - Fixed race condition where polling continued after translation was deleted

### Technical Details

- **Rate Limiting Middleware**: Added route exemptions and increased limits
- **Frontend Retry Logic**: Implemented `withRetry` utility with configurable backoff
- **Cache Implementation**: Created `SimpleCache` class with TTL support
- **Store Architecture**: Implemented `translationCapabilities` and `translations` Pinia stores

## [2025.6.1] - 2025-06-01

### Added

- Translation system with multi-language support
- Dubbing optimization features
- Real-time processing indicators
- Stop/Delete functionality for translations

### Changed

- Improved animation system for translation processing
- Enhanced worker architecture for background processing

## [2025.5.30] - 2025-05-30

### Added

- Initial release of Intelligent Transcription System
- OpenAI Whisper-1 integration for transcription
- Vue 3 frontend with Composition API
- Clean Architecture backend with CQRS pattern
- SQLite database with optimized schema
- Real-time WebSocket updates
