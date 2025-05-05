# Configuration de l'Environnement

Ce document explique comment configurer correctement les variables d'environnement pour ce projet.

## Structure des Fichiers d'Environnement

Le projet utilise des fichiers .env pour stocker les configurations sensibles comme les clés API. Pour des raisons de sécurité, nous recommandons deux configurations possibles :

### Option 1 (Recommandée) : Stocker .env en dehors du répertoire web

Pour une sécurité maximale, stockez le fichier .env en dehors du répertoire web. L'application est configurée pour chercher le fichier .env dans le répertoire :

```
/chemin/vers/inteligent-transcription-env/.env
```

### Option 2 : Stocker .env dans le répertoire du projet

Si l'option 1 n'est pas possible, vous pouvez conserver le fichier .env dans le répertoire principal du projet. Des mesures de sécurité supplémentaires ont été mises en place :

- Protection par .htaccess pour empêcher l'accès direct
- Permissions de fichier restreintes (600 - lecture/écriture pour le propriétaire uniquement)

## Configuration Initiale

1. Copiez le fichier `.env.example` pour créer votre fichier `.env` :
   ```bash
   # Option 1 (recommandée)
   mkdir -p /chemin/vers/inteligent-transcription-env
   cp .env.example /chemin/vers/inteligent-transcription-env/.env
   chmod 600 /chemin/vers/inteligent-transcription-env/.env
   
   # OU Option 2
   cp .env.example .env
   chmod 600 .env
   ```

2. Modifiez le fichier `.env` avec vos propres clés API et configurations :
   ```bash
   # Option 1
   nano /chemin/vers/inteligent-transcription-env/.env
   
   # OU Option 2
   nano .env
   ```

## Variables d'Environnement Requises

Le fichier `.env` doit contenir les variables suivantes :

- `OPENAI_API_KEY` : Votre clé API OpenAI pour Whisper et GPT
- `VIDEO_DOWNLOAD_API_KEY` : Votre clé API pour le service de téléchargement vidéo
- `APP_ENV` : Environment (`development` ou `production`)
- `APP_DEBUG` : Activer/désactiver le mode débogage (`true` ou `false`)
- `MAX_UPLOAD_SIZE_MB` : Taille maximale pour les fichiers uploadés
- `TARGET_AUDIO_SIZE_MB` : Taille cible pour le prétraitement audio

## Sécurité

- Ne jamais committer le fichier `.env` contenant de vraies clés API dans le dépôt
- Toujours utiliser `.env.example` pour documenter les variables requises sans valeurs sensibles
- Vérifier que les permissions du fichier `.env` sont correctement configurées (600)
- En production, désactiver le mode débogage (`APP_DEBUG=false`)

## Contrôle des Versions

L'application utilise un système flexible de chargement qui cherche d'abord le fichier `.env` en dehors du répertoire web, puis à l'intérieur du projet si nécessaire. Cela permet différentes configurations selon l'environnement sans modifier le code.