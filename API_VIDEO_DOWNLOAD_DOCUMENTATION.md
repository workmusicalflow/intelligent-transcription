# Documentation de l'intégration de l'API video-download-api.com

## Introduction

Cette documentation détaille l'intégration de l'API [video-download-api.com](https://video-download-api.com/) (également connue sous le nom de loader.to) dans notre application de transcription audio. Cette intégration permet aux utilisateurs de transcrire directement des vidéos YouTube sans avoir à les télécharger manuellement.

## Vue d'ensemble de l'API

L'API loader.to est un service qui permet de télécharger des vidéos YouTube et de les convertir dans différents formats audio et vidéo. Dans notre cas, nous l'utilisons pour convertir des vidéos YouTube en fichiers audio MP3 qui sont ensuite traités par notre pipeline de transcription.

### Caractéristiques principales

- Téléchargement de vidéos YouTube via une simple URL
- Conversion en différents formats (MP3, WAV, MP4, etc.)
- Suivi de la progression du téléchargement et de la conversion
- Récupération du fichier converti via une URL de téléchargement

## Implémentation technique

### Endpoints de l'API

L'API utilise deux endpoints principaux :

1. **Endpoint de téléchargement** : `https://loader.to/ajax/download.php`

   - Méthode : GET
   - Paramètres :
     - `format` : Format souhaité (mp3, wav, etc.)
     - `url` : URL YouTube encodée
     - `api` : Clé API
   - Réponse : JSON contenant un ID de téléchargement et une URL de progression

2. **Endpoint de progression** : Fourni dynamiquement dans la réponse de l'endpoint de téléchargement via le champ `progress_url`
   - Méthode : GET
   - Paramètres :
     - `id` : ID de téléchargement
   - Réponse : JSON contenant la progression (0-1000) et, une fois terminé, l'URL de téléchargement

### Flux de travail

1. L'utilisateur soumet une URL YouTube via notre interface
2. Notre application envoie une requête à l'endpoint de téléchargement
3. L'API commence le téléchargement et la conversion, et retourne un ID unique
4. Notre application interroge périodiquement l'endpoint de progression pour suivre l'avancement
5. Une fois la conversion terminée, l'API fournit une URL de téléchargement
6. Notre application télécharge le fichier audio et le traite avec notre pipeline de transcription

### Fichiers clés

- `youtube_download.php` : Script principal qui gère l'interaction avec l'API
- `config.php` : Configuration des endpoints et de la clé API
- `test_video_api.php` : Script de test pour vérifier le bon fonctionnement de l'API
- `mock_video_api.php` : Serveur mock pour simuler l'API pendant le développement

## Défis rencontrés et solutions

### 1. URL de progression dynamique

**Problème** : Initialement, nous utilisions une URL codée en dur pour vérifier la progression, ce qui causait des erreurs HTTP 301 (redirection).

**Solution** : Nous avons modifié notre code pour utiliser l'URL de progression fournie dynamiquement dans la réponse de l'API via le champ `progress_url`. Cette approche est plus robuste car elle s'adapte automatiquement si l'API change ses endpoints.

```php
// Avant
$progressUrl = VIDEO_DOWNLOAD_PROGRESS_URL . "?id={$downloadId}";

// Après
$progressUrl = $result['progress_url'] ?? (VIDEO_DOWNLOAD_PROGRESS_URL . "?id={$downloadId}");
```

### 2. Gestion des erreurs et des timeouts

**Problème** : Les téléchargements de vidéos longues pouvaient échouer en raison de timeouts ou d'erreurs temporaires.

**Solution** : Implémentation d'un backoff exponentiel pour les requêtes et d'un système de retry pour gérer les erreurs temporaires.

```php
$maxRetries = 3;
$retryCount = 0;
$retryDelay = 2;

while ($httpCode !== 200 && $retryCount < $maxRetries) {
    sleep($retryDelay);
    $retryDelay *= 2;
    $retryCount++;

    // Réessayer la requête...
}
```

### 3. Tests sans dépendance externe

**Problème** : Tester l'intégration nécessitait d'appeler l'API réelle, ce qui n'était pas idéal pour le développement.

**Solution** : Création d'un serveur mock qui simule le comportement de l'API, permettant des tests locaux sans appels réseau.

## Exploitation des données de la réponse JSON

La réponse de l'API contient de nombreuses informations utiles qui peuvent être exploitées pour améliorer l'expérience utilisateur :

```json
{
  "success": true,
  "id": "w3ZPvHyyZR9h2l89W7pFHm1",
  "content": "...",
  "title": "SUPER POWERED RooCode, Cline, Windsurf: These are the CRAZIEST MCP Server I use!",
  "info": {
    "image": "https://i.ytimg.com/vi/_rFissIE6CA/hqdefault.jpg",
    "title": "SUPER POWERED RooCode, Cline, Windsurf: These are the CRAZIEST MCP Server I use!"
  },
  "repeat_download": false,
  "message": "If you want your application to use our API contact us: sp_golubev@protonmail.com or visit https://video-download-api.com/",
  "cachehash": "5ab26863a8061eae3ebe34ea5e676ffa",
  "additional_info": null,
  "progress_url": "https://p.oceansaver.in/api/progress?id=w3ZPvHyyZR9h2l89W7pFHm1"
}
```

## Recommandations pour l'expérience utilisateur

En exploitant les données fournies par l'API, nous pouvons améliorer significativement l'expérience utilisateur :

### 1. Affichage des métadonnées de la vidéo

**Recommandation** : Utiliser les champs `title` et `info.image` pour afficher le titre et la miniature de la vidéo pendant le téléchargement et la transcription.

**Implémentation** :

```php
// Extraire les métadonnées
$videoTitle = $result['title'] ?? $result['info']['title'] ?? 'Vidéo YouTube';
$videoThumbnail = $result['info']['image'] ?? '';

// Afficher dans l'interface
echo '<div class="video-info">';
echo '<img src="' . htmlspecialchars($videoThumbnail) . '" alt="Miniature">';
echo '<h3>' . htmlspecialchars($videoTitle) . '</h3>';
echo '</div>';
```

**Bénéfice** : L'utilisateur peut confirmer visuellement qu'il s'agit de la bonne vidéo, ce qui améliore la confiance dans le processus.

### 2. Barre de progression améliorée

**Recommandation** : Utiliser le champ `progress` de la réponse de progression pour afficher une barre de progression précise et le champ `text` pour afficher l'état actuel.

**Implémentation** :

```javascript
// Côté client (JavaScript)
function updateProgress(progress, text) {
  const progressBar = document.getElementById("progress-bar");
  const progressText = document.getElementById("progress-text");

  progressBar.style.width = progress / 10 + "%";
  progressText.textContent = text;
}

// Appel AJAX pour vérifier la progression
fetch("check_progress.php?id=" + downloadId)
  .then((response) => response.json())
  .then((data) => {
    updateProgress(data.progress, data.text);
  });
```

**Bénéfice** : Une barre de progression précise avec des messages d'état clairs réduit l'anxiété de l'utilisateur pendant l'attente.

### 3. Gestion des erreurs conviviale

**Recommandation** : Utiliser le champ `message` pour afficher des messages d'erreur spécifiques fournis par l'API.

**Implémentation** :

```php
if (!$result['success']) {
    $errorMessage = $result['message'] ?? 'Une erreur est survenue lors du téléchargement de la vidéo.';
    echo '<div class="error-message">' . htmlspecialchars($errorMessage) . '</div>';
}
```

**Bénéfice** : Des messages d'erreur précis aident l'utilisateur à comprendre et potentiellement résoudre le problème.

### 4. Préchargement des données de transcription

**Recommandation** : Commencer à traiter le début du fichier audio dès qu'il est disponible, sans attendre le téléchargement complet pour les vidéos longues.

**Implémentation** : Utiliser des requêtes en streaming pour commencer à traiter le fichier audio par segments.

**Bénéfice** : Réduction du temps d'attente perçu pour les vidéos longues.

### 5. Historique des téléchargements

**Recommandation** : Utiliser le champ `cachehash` pour identifier les vidéos déjà téléchargées et éviter de les télécharger à nouveau.

**Implémentation** :

```php
// Vérifier si la vidéo a déjà été téléchargée
$cachehash = $result['cachehash'] ?? '';
$cachedFile = getCachedFile($cachehash);

if ($cachedFile) {
    // Utiliser le fichier en cache au lieu de télécharger à nouveau
    processAudioFile($cachedFile);
} else {
    // Procéder au téléchargement normal
    // ...

    // Sauvegarder dans le cache pour une utilisation future
    cacheFile($cachehash, $downloadedFile);
}
```

**Bénéfice** : Économie de bande passante et réduction du temps de traitement pour les vidéos populaires.

## Conclusion

L'intégration de l'API video-download-api.com a considérablement amélioré notre application de transcription en permettant aux utilisateurs de transcrire directement des vidéos YouTube. Les défis techniques ont été surmontés grâce à une approche robuste de gestion des erreurs et à l'utilisation des informations dynamiques fournies par l'API.

En exploitant pleinement les données disponibles dans les réponses JSON de l'API, nous pouvons offrir une expérience utilisateur riche et informative qui va au-delà de la simple fonctionnalité de téléchargement.

## Ressources

- [Site officiel de l'API](https://video-download-api.com/)
- [Documentation de l'API](https://loader.to/api.html)
- [Contact pour les questions sur l'API](mailto:sp_golubev@protonmail.com)
