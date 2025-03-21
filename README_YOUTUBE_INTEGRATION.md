# Intégration YouTube pour l'Application de Transcription

Cette documentation explique comment l'application de transcription a été étendue pour prendre en charge le téléchargement et la transcription de vidéos YouTube.

## Fonctionnalités

- Interface utilisateur avec onglets pour choisir entre le téléchargement de fichier et l'URL YouTube
- Validation des URL YouTube
- Téléchargement de vidéos YouTube via l'API loader.to
- Conversion automatique en format audio
- Intégration avec le pipeline de transcription existant

## Configuration

L'intégration YouTube nécessite une clé API pour loader.to. Cette clé est configurée dans le fichier `.env` :

```
VIDEO_DOWNLOAD_API_KEY=votre_clé_api
```

## Fichiers modifiés/ajoutés

- **config.php** : Ajout des constantes pour l'API loader.to
- **utils.php** : Ajout des fonctions de validation d'URL YouTube
- **index.php** : Ajout de l'interface à onglets pour le téléchargement de fichier et l'URL YouTube
- **youtube_download.php** : Script de traitement des URL YouTube
- **assets/css/style.css** : Styles pour l'interface à onglets
- **test_video_api.php** : Script de test pour l'API loader.to
- **mock_video_api.php** : Serveur mock pour simuler l'API loader.to
- **test_mock_api.php** : Script de test pour le serveur mock

## Fonctionnement de l'API loader.to

L'API loader.to utilise deux endpoints principaux :

1. **Download Endpoint** :

   - URL : `https://loader.to/ajax/download.php`
   - Méthode : GET
   - Paramètres :
     - `format` : Le format de fichier souhaité (mp3, wav, etc.)
     - `url` : L'URL YouTube encodée
     - `api` : Votre clé API
   - Exemple : `https://loader.to/ajax/download.php?format=mp3&url=https%3A%2F%2Fwww.youtube.com%2Fwatch%3Fv%3DXXX&api=votre_clé_api`
   - Réponse : JSON contenant un ID de téléchargement

2. **Progress Endpoint** :
   - URL : `https://p.oceansaver.in/ajax/progress.php`
   - Méthode : GET
   - Paramètres :
     - `id` : L'ID de téléchargement obtenu précédemment
   - Exemple : `https://p.oceansaver.in/ajax/progress.php?id=abcd1234`
   - Réponse : JSON contenant la progression (0-1000) et, une fois terminé, l'URL de téléchargement

Le processus complet implique :

1. Envoyer l'URL YouTube à l'endpoint de téléchargement
2. Récupérer l'ID de téléchargement dans la réponse
3. Vérifier périodiquement la progression avec l'endpoint de progression
4. Lorsque `success=1` et qu'une `download_url` est fournie, télécharger le fichier audio

## Tests

### Test de l'API réelle

Pour tester l'intégration avec l'API réelle, utilisez le script `test_video_api.php` :

```bash
php test_video_api.php
```

Ce script effectue une requête à l'API loader.to avec une URL YouTube de test, suit la progression du téléchargement et affiche les résultats détaillés.

### Test avec un serveur mock

Pour tester l'intégration sans dépendre de l'API réelle, un serveur mock est fourni :

1. Démarrez le serveur mock :

   ```bash
   php -S localhost:8080 mock_video_api.php
   ```

2. Modifiez temporairement `config.php` pour pointer vers le serveur mock :

   ```php
   define('VIDEO_DOWNLOAD_API_URL', 'http://localhost:8080/download.php');
   define('VIDEO_DOWNLOAD_PROGRESS_URL', 'http://localhost:8080/progress.php');
   ```

3. Testez le serveur mock :

   ```bash
   php test_mock_api.php
   ```

4. Utilisez l'application normalement avec l'onglet YouTube

## Dépannage

### Problèmes courants

1. **Erreur "Could not resolve host"** : Vérifiez votre connexion Internet et que l'URL de l'API est correcte.

2. **Erreur d'authentification** : Vérifiez que votre clé API est correcte et active.

3. **URL YouTube invalide** : Assurez-vous que l'URL est au format correct (ex: https://www.youtube.com/watch?v=VIDEO_ID).

4. **Progression bloquée** : Si la progression du téléchargement semble bloquée, vérifiez les logs et assurez-vous que l'API est accessible.

### Logs de débogage

Les logs de débogage sont disponibles dans les fichiers suivants :

- `debug_youtube_download.log` : Informations sur les requêtes à l'API de téléchargement
- `debug_youtube_progress.log` : Informations sur les requêtes de progression
- `debug_preprocess.log` : Informations sur le prétraitement du fichier audio
- `debug_transcribe.log` : Informations sur le processus de transcription

## Exemple d'utilisation

1. Accédez à la page d'accueil de l'application
2. Cliquez sur l'onglet "YouTube"
3. Collez une URL YouTube (ex: https://www.youtube.com/watch?v=AJpK3YTTKZ4)
4. Sélectionnez la langue (ou laissez sur "Détection automatique")
5. Cliquez sur "Transcrire"
6. Attendez que le téléchargement, le prétraitement et la transcription soient terminés
7. Consultez le résultat de la transcription
