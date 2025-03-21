# Structure MVC simplifiée pour l'application de transcription audio

Ce dossier contient la nouvelle structure MVC simplifiée pour l'application de transcription audio. Cette structure a été mise en place pour améliorer la maintenabilité et l'évolutivité du code.

## Structure des dossiers

- `src/` : Dossier racine du code source
  - `Controllers/` : Contrôleurs de l'application
  - `Services/` : Services métier
  - `Utils/` : Classes utilitaires
  - `Models/` : Structures de données (à venir)
  - `autoload.php` : Autoloader pour charger automatiquement les classes
  - `bootstrap.php` : Fichier d'initialisation de l'application
  - `config.php` : Configuration de l'application
- `templates/` : Templates HTML (à venir)
- `uploads/` : Fichiers audio téléchargés
- `temp_audio/` : Fichiers audio prétraités
- `results/` : Résultats de transcription
- `exports/` : Exports de conversations

## Principes de conception

### Clean Code

- Noms de variables et de fonctions explicites
- Fonctions courtes et à responsabilité unique
- Commentaires pertinents
- Gestion des erreurs cohérente
- Tests unitaires (à venir)

### MVC simplifié

- **Modèles** : Structures de données
- **Vues** : Templates HTML
- **Contrôleurs** : Gestion des requêtes HTTP et coordination des services
- **Services** : Logique métier

### Principes SOLID

- **S**ingle Responsibility Principle : Chaque classe a une seule responsabilité
- **O**pen/Closed Principle : Les classes sont ouvertes à l'extension mais fermées à la modification
- **L**iskov Substitution Principle : Les sous-classes peuvent être substituées à leurs classes parentes
- **I**nterface Segregation Principle : Les interfaces spécifiques sont préférables aux interfaces génériques
- **D**ependency Inversion Principle : Dépendre des abstractions, pas des implémentations

## Utilisation

Pour utiliser cette nouvelle structure, il suffit d'inclure le fichier `bootstrap.php` au début de chaque script :

```php
require_once __DIR__ . '/src/bootstrap.php';

// Utiliser les contrôleurs
$transcriptionController = new \Controllers\TranscriptionController();
$result = $transcriptionController->showResult();
```

## Évolution future

- Ajout de tests unitaires
- Utilisation de Twig pour les templates
- Ajout d'un routeur pour gérer les URLs
- Ajout d'un système d'authentification
- Ajout d'un système de cache
