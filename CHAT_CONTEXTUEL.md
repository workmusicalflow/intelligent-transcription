# Fonctionnalité de Chat Contextuel

## Introduction

La fonctionnalité de Chat Contextuel permet aux utilisateurs d'interagir avec un assistant IA en utilisant le contexte de la transcription ou de la paraphrase. Cette fonctionnalité enrichit l'expérience utilisateur en permettant de poser des questions, d'obtenir des clarifications ou d'explorer davantage le contenu transcrit.

## Fonctionnalités

- **Chat basé sur le contexte** : L'assistant utilise le contenu transcrit ou paraphrasé comme contexte pour répondre aux questions.
- **Historique de conversation** : Les échanges sont conservés pendant la session pour maintenir la continuité de la conversation.
- **Exportation de l'historique** : Possibilité d'exporter l'historique de la conversation au format texte.
- **Interface intuitive** : Interface utilisateur simple et conviviale pour une expérience fluide.

## Architecture

La fonctionnalité de Chat Contextuel est implémentée avec une architecture modulaire :

1. **Gestionnaire de contexte** (`context_manager.php`) : Stocke et gère le contexte de la transcription/paraphrase.
2. **API de chat** (`chat_api.php`) : Gère la communication avec l'API OpenAI pour générer les réponses.
3. **Interface de chat** (`chat.php`) : Interface utilisateur pour interagir avec l'assistant.

### Flux de données

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ Transcription│────▶│ Gestionnaire│     │  API OpenAI │
│    result.php│     │  de contexte│     │             │
└─────────────┘     └──────┬──────┘     └──────▲──────┘
                           │                    │
                           ▼                    │
                    ┌─────────────┐     ┌──────┴──────┐
                    │ Interface de│     │  API de chat│
                    │    chat     │────▶│             │
                    └─────────────┘     └─────────────┘
```

## Utilisation

1. **Accéder au chat** : Après avoir transcrit un fichier audio ou vidéo, cliquez sur le bouton "Discuter avec l'assistant" sur la page de résultat, ou utilisez le lien "Chat" dans le menu de navigation.

2. **Poser des questions** : Entrez votre question dans le champ de texte et cliquez sur "Envoyer". L'assistant utilisera le contexte de la transcription pour fournir une réponse pertinente.

3. **Exporter l'historique** : Cliquez sur le bouton "Exporter l'historique" pour télécharger l'historique de la conversation au format texte.

## Exemples d'utilisation

- **Clarification** : "Peux-tu m'expliquer ce que signifie [terme spécifique] mentionné dans la transcription ?"
- **Résumé** : "Peux-tu résumer les points principaux de cette transcription ?"
- **Analyse** : "Quels sont les thèmes principaux abordés dans ce contenu ?"
- **Traduction** : "Comment dit-on [phrase de la transcription] en anglais ?"
- **Reformulation** : "Peux-tu reformuler le troisième paragraphe de manière plus simple ?"

## Considérations techniques

- La fonctionnalité utilise l'API OpenAI, qui nécessite une clé API valide configurée dans le fichier `.env`.
- Les conversations sont stockées uniquement pendant la session active et ne sont pas persistantes entre les sessions, sauf si exportées.
- Le contexte est limité à la taille maximale acceptée par l'API OpenAI. Pour les transcriptions très longues, seule une partie peut être utilisée comme contexte.

## Personnalisation

Pour personnaliser le comportement de l'assistant, vous pouvez modifier le fichier `chat_api.php` :

- Changer le modèle utilisé (par défaut : `gpt-3.5-turbo`)
- Ajuster la température pour contrôler la créativité des réponses
- Modifier le message système pour changer le comportement de l'assistant

## Dépannage

- **L'assistant ne répond pas** : Vérifiez que la clé API OpenAI est correctement configurée dans le fichier `.env`.
- **Réponses hors contexte** : Assurez-vous qu'une transcription a été effectuée avant d'utiliser le chat.
- **Erreurs d'affichage** : Vérifiez que tous les fichiers nécessaires sont présents et que les permissions sont correctes.

## Améliorations futures

- Persistance des conversations dans une base de données
- Support pour le streaming des réponses en temps réel
- Interface AJAX pour une expérience utilisateur plus fluide
- Personnalisation du modèle et des paramètres par l'utilisateur
- Support pour les fichiers joints et les références croisées
