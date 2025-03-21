# Application de Transcription Audio

Une application simple et efficace pour transcrire des fichiers audio et vidéo en texte en utilisant l'API OpenAI Whisper.

## Fonctionnalités

- Téléchargement de fichiers audio et vidéo
- Transcription précise grâce à l'API OpenAI Whisper
- Paraphrase des transcriptions avec l'API OpenAI GPT
- Chat contextuel pour interagir avec le contenu transcrit
- Support pour de nombreuses langues
- Interface simple et intuitive
- Téléchargement des résultats en format texte

## Prérequis

- PHP 8.1 ou supérieur
- Python 3.9 ou supérieur
- Une clé API OpenAI valide

## Installation

1. Clonez ce dépôt ou téléchargez les fichiers

2. Configurez votre clé API OpenAI dans le fichier `.env` :

   ```
   OPENAI_API_KEY=votre_clé_api_openai
   ```

3. Exécutez le script d'installation pour configurer l'environnement Python :

   ```bash
   ./setup_env.sh
   ```

4. Démarrez un serveur PHP local avec le fichier php.ini personnalisé :

   ```bash
   php -S localhost:8000 -c php.ini
   ```

5. Accédez à l'application dans votre navigateur :
   ```
   http://localhost:8000
   ```

## Structure du projet

- `index.php` - Page d'accueil avec formulaire de téléchargement
- `transcribe.php` - Script de traitement de la transcription
- `result.php` - Page d'affichage des résultats
- `download.php` - Script de téléchargement du résultat en TXT
- `config.php` - Configuration (clés API, chemins, etc.)
- `utils.php` - Fonctions utilitaires
- `transcribe.py` - Script Python pour la transcription avec OpenAI Whisper
- `setup_env.sh` - Script d'installation de l'environnement Python
- `chat.php` - Interface de chat contextuel
- `chat_api.php` - API pour interagir avec OpenAI pour le chat
- `context_manager.php` - Gestionnaire de contexte pour le chat
- `CHAT_CONTEXTUEL.md` - Documentation de la fonctionnalité de chat
- `uploads/` - Stockage des fichiers audio téléchargés
- `results/` - Stockage des résultats de transcription
- `exports/` - Stockage des exports d'historique de chat
- `assets/` - Ressources CSS et JavaScript
- `venv/` - Environnement virtuel Python (créé par setup_env.sh)

## Utilisation

1. Accédez à la page d'accueil
2. Téléchargez un fichier audio ou vidéo
3. Sélectionnez la langue du fichier (ou laissez en détection automatique)
4. Cliquez sur "Transcrire"
5. Attendez que la transcription soit terminée
6. Consultez le résultat et téléchargez-le si nécessaire
7. Pour interagir avec le contenu transcrit, cliquez sur "Discuter avec l'assistant"
8. Posez des questions sur le contenu transcrit et obtenez des réponses contextuelles
9. Exportez l'historique de conversation si nécessaire

Pour plus de détails sur la fonctionnalité de chat, consultez [CHAT_CONTEXTUEL.md](CHAT_CONTEXTUEL.md).

## Langues supportées

- Français
- Anglais
- Espagnol
- Allemand
- Italien
- Portugais
- Russe
- Chinois
- Japonais
- Arabe
- Et bien d'autres...

## Limitations

- Taille maximale des fichiers : 100 MB
- Formats supportés : MP3, WAV, MP4, AVI, MOV, etc.

## Dépannage

Si vous rencontrez des problèmes :

1. Vérifiez que votre clé API OpenAI est valide
2. Assurez-vous que Python et PHP sont correctement installés
3. Vérifiez que l'environnement virtuel a été correctement configuré
4. Consultez les logs pour plus d'informations

## Développement futur

- Ajout d'un système de cache pour éviter de retranscrire les mêmes fichiers
- Implémentation d'un traitement asynchrone pour les fichiers volumineux
- Ajout de fonctionnalités de traduction
- Amélioration de l'interface utilisateur
- Persistance des conversations de chat dans une base de données
- Interface AJAX pour le chat avec streaming des réponses en temps réel
- Personnalisation des modèles et paramètres pour le chat contextuel
