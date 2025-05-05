# Système d'Authentification

Ce document décrit l'implémentation du système d'authentification et de gestion des utilisateurs dans l'application Intelligent Transcription.

## Vue d'ensemble

Le système d'authentification permet de sécuriser l'application avec les fonctionnalités suivantes :

- Authentification des utilisateurs par nom d'utilisateur/mot de passe
- Sessions persistantes avec option "Se souvenir de moi"
- Gestion des utilisateurs (création, édition, suppression)
- Système de permissions flexible
- Interface d'administration
- Protection des routes et ressources

## Architecture

Le système d'authentification suit le modèle MVC et se compose des éléments suivants :

### Base de données

Les tables suivantes sont utilisées pour l'authentification :

- `users` : Stocke les informations des utilisateurs
- `user_sessions` : Gère les sessions utilisateur actives
- `password_reset_tokens` : Stocke les tokens de réinitialisation de mot de passe
- `user_permissions` : Gère les permissions associées aux utilisateurs

### Modèles

- `User` : Gère les opérations CRUD sur les utilisateurs, la gestion des permissions et l'authentification

### Services

- `AuthService` : Service centralisé pour l'authentification, la gestion des sessions et les contrôles d'accès

### Contrôleurs

- `AuthController` : Gère la connexion, déconnexion, réinitialisation de mot de passe et profil utilisateur
- `UserController` : Gère la création, édition et suppression des utilisateurs (admin uniquement)

### Templates

- `auth/login.twig` : Formulaire de connexion
- `auth/profile.twig` : Page de profil utilisateur
- `auth/password_reset.twig` : Formulaire de réinitialisation de mot de passe
- `admin/users/index.twig` : Liste des utilisateurs (admin)
- `admin/users/create.twig` : Création d'utilisateur (admin)
- `admin/users/edit.twig` : Édition d'utilisateur (admin)

## Installation

Pour installer le système d'authentification, exécutez le script `update_auth_schema.php` qui va créer les tables nécessaires dans la base de données SQLite :

```bash
php update_auth_schema.php
```

Ce script crée également un utilisateur administrateur par défaut :
- **Nom d'utilisateur** : admin
- **Mot de passe** : admin123

**Important** : Changez le mot de passe administrateur par défaut après la première connexion !

## Utilisation

### Connexion/Déconnexion

Les utilisateurs accèdent à la page de connexion via `/login.php`. Après l'authentification, ils sont redirigés vers le tableau de bord ou la page demandée.

La déconnexion s'effectue via `/login.php?action=logout`.

### Gestion du profil

Les utilisateurs peuvent modifier leur profil et changer leur mot de passe via `/profile.php`.

### Administration des utilisateurs

Les administrateurs peuvent gérer les utilisateurs via `/admin.php?controller=user` qui permet de :

- Lister tous les utilisateurs
- Créer de nouveaux utilisateurs
- Modifier les informations et permissions des utilisateurs
- Activer/désactiver des comptes utilisateur
- Supprimer des utilisateurs

## Système de permissions

Le système implémente un modèle de permissions flexible. Les permissions par défaut sont :

- `admin.access` : Accès au panneau d'administration
- `users.manage` : Gestion des utilisateurs
- `users.view` : Affichage de la liste des utilisateurs
- `transcriptions.own` : Accès à ses propres transcriptions
- `transcriptions.all` : Accès à toutes les transcriptions

Les administrateurs ont automatiquement toutes les permissions.

### Vérification des permissions

Dans le code, les permissions sont vérifiées via `AuthService` :

```php
// Vérifier si l'utilisateur est connecté
if (AuthService::isAuthenticated()) {
    // Vérifier si l'utilisateur est admin
    if (AuthService::isAdmin()) {
        // Code pour les admins
    }
    
    // Vérifier une permission spécifique
    if (AuthService::hasPermission('transcriptions.all')) {
        // Code pour les utilisateurs ayant cette permission
    }
}

// Exiger l'authentification (redirection si non connecté)
AuthService::requireAuth();

// Exiger une permission spécifique
AuthService::requirePermission('admin.access');
```

## Sécurité

Le système d'authentification intègre plusieurs mesures de sécurité :

### Stockage des mots de passe

Les mots de passe sont hachés avec l'algorithme bcrypt (via `password_hash()`) avant d'être stockés dans la base de données.

### Protection CSRF

Toutes les actions de modification (connexion, modification de profil, gestion des utilisateurs) sont protégées contre les attaques CSRF par un token généré à chaque session.

### Session sécurisée

Les paramètres de session sont configurés pour utiliser des cookies sécurisés (httpOnly, secure). Les sessions sont également stockées en base de données, permettant une invalidation à distance.

### Option "Se souvenir de moi"

L'option "Se souvenir de moi" utilise un token unique stocké dans un cookie pour permettre la reconnexion automatique. Ce token est régénéré à chaque utilisation pour prévenir les attaques de vol de session.

### Nettoyage automatique

Les sessions et tokens expirés sont automatiquement nettoyés du système.

## Intégration avec l'application existante

Le système d'authentification est intégré à l'application existante via les mécanismes suivants :

### Middleware d'authentification

`AuthService::init()` est appelé au début de chaque point d'entrée pour initialiser l'authentification.

### Variables Twig globales

Les templates Twig reçoivent automatiquement les variables d'authentification suivantes :

- `is_authenticated` : Indique si l'utilisateur est connecté
- `is_admin` : Indique si l'utilisateur est administrateur
- `current_user` : Données de l'utilisateur connecté

### Association des ressources

Les transcriptions, conversations et paraphrases sont associées à l'utilisateur qui les a créées via la colonne `user_id` dans les tables correspondantes.

## Personnalisation

### Ajout de nouvelles permissions

Pour ajouter de nouvelles permissions :

1. Ajoutez la permission dans l'interface utilisateur (formulaire d'édition)
2. Implémentez la vérification de permission dans les contrôleurs concernés

### Modification des exigences de mot de passe

Les exigences de mot de passe (longueur minimale, complexité, etc.) peuvent être modifiées dans `ValidationUtils` et les contrôleurs d'authentification.

## Limitations et développements futurs

Fonctionnalités qui pourraient être ajoutées à l'avenir :

- Authentification à deux facteurs (2FA)
- Intégration OAuth pour connexion via services tiers
- Groupes d'utilisateurs avec permissions par groupe
- Expiration et historique des mots de passe
- Journalisation complète des activités utilisateur
- Implémentation de throttling (limitation des tentatives de connexion)