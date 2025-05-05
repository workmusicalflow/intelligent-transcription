# Commandes Essentielles du Projet

## Configuration Initiale

```bash
# Installer les dépendances PHP
composer install

# Configurer l'environnement Python (à exécuter une fois)
./setup_env.sh
```

## Démarrage du Serveur

```bash
# Démarrer le serveur PHP de développement
php -S localhost:8000 -c php.ini

# Options disponibles :
# - -S : Spécifie l'adresse et le port
# - -c : Utilise le fichier php.ini personnalisé
```

## Gestion des Assets

```bash
# Compiler les assets CSS (Tailwind)
npx tailwindcss -i ./assets/css/tailwind.css -o ./assets/css/style.css --watch
```

## Commandes Utilitaires

```bash
# Vérifier les erreurs PHP
php -l fichier.php

# Lancer les tests (si disponibles)
php test_mvc.php
```

## Variables d'Environnement

Le fichier `.env` doit contenir :

```
OPENAI_API_KEY=votre_clé_api
```

## Dépannage

```bash
# Voir les logs PHP
tail -f php_errors.log

# Voir les logs Python
tail -f python_api.log
```

## Structure des Répertoires Importants

- `uploads/` : Fichiers audio/vidéo téléchargés
- `results/` : Résultats de transcription
- `temp_audio/` : Fichiers audio temporaires
- `logs/` : Fichiers de log
