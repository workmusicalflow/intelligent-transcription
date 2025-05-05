# Plan d'implémentation de l'export vers Google Docs

## 1. Configuration initiale

### 1.1 Dépendances requises

```
composer require google/apiclient:^2.12
```

### 1.2 Configuration Google Cloud

- Créer un projet dans la [Google Cloud Console](https://console.cloud.google.com/)
- Activer l'API Google Docs
- Créer des identifiants OAuth 2.0 (type "Application Web")
- Télécharger le fichier JSON des identifiants client et le placer dans un dossier sécurisé de l'application

## 2. Structure des fichiers

### 2.1 Créer une classe GoogleDocsService

```php
// src/Services/GoogleDocsService.php
namespace App\Services;

class GoogleDocsService {
    private $client;
    private $service;
    private $tokenPath;

    // Méthodes à implémenter:
    // - __construct()
    // - authenticate()
    // - appendTranscriptionToDoc()
}
```

## 3. Implémentation des fonctionnalités

### 3.1 Authentification Google

```php
public function authenticate() {
    $this->client = new \Google_Client();
    $this->client->setApplicationName('Intelligent Transcription');
    $this->client->setScopes([\Google_Service_Docs::DOCUMENTS]);
    $this->client->setAuthConfig(CREDENTIALS_PATH);
    $this->client->setAccessType('offline');
    $this->client->setPrompt('select_account consent');

    // Vérifier si un token existe déjà
    if (file_exists($this->tokenPath)) {
        $accessToken = json_decode(file_get_contents($this->tokenPath), true);
        $this->client->setAccessToken($accessToken);
    }

    // Si le token est expiré, le rafraîchir
    if ($this->client->isAccessTokenExpired()) {
        if ($this->client->getRefreshToken()) {
            $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            file_put_contents($this->tokenPath, json_encode($this->client->getAccessToken()));
        } else {
            // Rediriger vers l'URL d'authentification
            return $this->client->createAuthUrl();
        }
    }

    $this->service = new \Google_Service_Docs($this->client);
    return true;
}
```

### 3.2 Ajout de contenu à un document existant

```php
public function appendTranscriptionToDoc($documentId, $transcription, $translation = null) {
    try {
        // Vérifier que le service est initialisé
        if (!$this->service) {
            $this->authenticate();
        }

        $requests = [];

        // Ajouter un titre pour la transcription
        $requests[] = new \Google_Service_Docs_Request([
            'insertText' => [
                'location' => [
                    'index' => 1
                ],
                'text' => "Transcription (" . date('Y-m-d H:i:s') . ")\n\n"
            ]
        ]);

        // Ajouter la transcription
        $requests[] = new \Google_Service_Docs_Request([
            'insertText' => [
                'endOfSegmentLocation' => new \Google_Service_Docs_EndOfSegmentLocation(),
                'text' => $transcription . "\n\n"
            ]
        ]);

        // Ajouter la traduction si elle existe
        if ($translation) {
            $requests[] = new \Google_Service_Docs_Request([
                'insertText' => [
                    'endOfSegmentLocation' => new \Google_Service_Docs_EndOfSegmentLocation(),
                    'text' => "Traduction:\n" . $translation . "\n\n"
                ]
            ]);
        }

        // Ajouter une ligne de séparation
        $requests[] = new \Google_Service_Docs_Request([
            'insertText' => [
                'endOfSegmentLocation' => new \Google_Service_Docs_EndOfSegmentLocation(),
                'text' => "----------------------------------------\n\n"
            ]
        ]);

        // Exécuter les modifications
        $batchUpdateRequest = new \Google_Service_Docs_BatchUpdateDocumentRequest([
            'requests' => $requests
        ]);

        $this->service->documents->batchUpdate($documentId, $batchUpdateRequest);
        return true;
    } catch (\Exception $e) {
        error_log("Erreur lors de l'ajout au document Google Docs: " . $e->getMessage());
        return false;
    }
}
```

## 4. Modifications de l'interface utilisateur

### 4.1 Ajouter un champ pour l'ID Google Docs dans result.php

```php
<div class="export-options">
    <h3>Exporter vers Google Docs</h3>
    <div class="form-group">
        <label for="gdocs-id">ID du document Google Docs:</label>
        <input type="text" id="gdocs-id" name="gdocs_id" placeholder="Entrez l'ID du document Google Docs">
        <p class="help-text">L'ID se trouve dans l'URL du document: docs.google.com/document/d/<strong>ID_DU_DOCUMENT</strong>/edit</p>
    </div>
    <button id="export-gdocs-btn" class="btn-action">Exporter vers Google Docs</button>
    <div id="export-status"></div>
</div>
```

### 4.2 Ajouter le JavaScript pour gérer l'export

```javascript
// assets/js/gdocs-export.js
document.addEventListener("DOMContentLoaded", function () {
  const exportBtn = document.getElementById("export-gdocs-btn");
  const gdocsIdInput = document.getElementById("gdocs-id");
  const exportStatus = document.getElementById("export-status");

  if (exportBtn) {
    exportBtn.addEventListener("click", function () {
      const gdocsId = gdocsIdInput.value.trim();
      if (!gdocsId) {
        exportStatus.textContent =
          "Veuillez entrer un ID de document Google Docs valide";
        exportStatus.className = "error-message";
        return;
      }

      // Récupérer l'ID du résultat depuis l'URL
      const urlParams = new URLSearchParams(window.location.search);
      const resultId = urlParams.get("id");

      // Envoyer la requête d'export
      exportStatus.textContent = "Export en cours...";
      exportStatus.className = "";

      fetch("export_to_gdocs.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `result_id=${resultId}&gdocs_id=${gdocsId}`,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            exportStatus.textContent = "Export réussi!";
            exportStatus.className = "success-message";
          } else {
            exportStatus.textContent = `Erreur: ${data.message}`;
            exportStatus.className = "error-message";
          }
        })
        .catch((error) => {
          exportStatus.textContent = `Erreur: ${error.message}`;
          exportStatus.className = "error-message";
        });
    });
  }
});
```

## 5. Création du point d'entrée pour l'export

### 5.1 Créer export_to_gdocs.php

```php
<?php
// export_to_gdocs.php
require_once 'config.php';
require_once 'src/autoload.php';

use App\Services\GoogleDocsService;

// Vérifier que les paramètres nécessaires sont présents
if (!isset($_POST['result_id']) || !isset($_POST['gdocs_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Paramètres manquants'
    ]);
    exit;
}

$resultId = $_POST['result_id'];
$gdocsId = $_POST['gdocs_id'];

// Vérifier que le résultat existe
$resultPath = RESULT_DIR . '/' . $resultId . '.json';
if (!file_exists($resultPath)) {
    echo json_encode([
        'success' => false,
        'message' => 'Résultat non trouvé'
    ]);
    exit;
}

// Charger le résultat
$result = json_decode(file_get_contents($resultPath), true);
if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'Résultat invalide'
    ]);
    exit;
}

// Déterminer si c'est un résultat de transcription ou de paraphrase
$isParaphrased = isset($result['paraphrased_text']);
$transcription = '';
$translation = null;

if ($isParaphrased) {
    $transcription = $result['original_text'] ?? '';
    $translation = $result['paraphrased_text'] ?? '';
} else {
    $transcription = $result['text'] ?? '';
}

// Initialiser le service Google Docs
$googleDocsService = new GoogleDocsService();

try {
    // Authentifier
    $authResult = $googleDocsService->authenticate();
    if (is_string($authResult)) {
        // Redirection vers l'URL d'authentification nécessaire
        echo json_encode([
            'success' => false,
            'message' => 'Authentification requise',
            'auth_url' => $authResult
        ]);
        exit;
    }

    // Ajouter la transcription au document
    $success = $googleDocsService->appendTranscriptionToDoc($gdocsId, $transcription, $translation);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Export réussi'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de l\'export'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
```

### 5.2 Créer un contrôleur d'authentification

```php
// src/Controllers/GoogleAuthController.php
namespace App\Controllers;

class GoogleAuthController {
    public function authorize() {
        session_start();

        // Stocker l'URL de retour
        if (isset($_GET['return_url'])) {
            $_SESSION['google_auth_return_url'] = $_GET['return_url'];
        }

        $googleDocsService = new \App\Services\GoogleDocsService();
        $authUrl = $googleDocsService->authenticate();

        if (is_string($authUrl)) {
            // Rediriger vers l'URL d'authentification Google
            header('Location: ' . $authUrl);
            exit;
        } else {
            // Déjà authentifié, rediriger vers l'URL de retour
            $returnUrl = $_SESSION['google_auth_return_url'] ?? 'index.php';
            header('Location: ' . $returnUrl);
            exit;
        }
    }

    public function callback() {
        session_start();

        if (isset($_GET['code'])) {
            $googleDocsService = new \App\Services\GoogleDocsService();
            $googleDocsService->handleAuthCode($_GET['code']);

            // Rediriger vers l'URL de retour
            $returnUrl = $_SESSION['google_auth_return_url'] ?? 'index.php';
            unset($_SESSION['google_auth_return_url']);

            header('Location: ' . $returnUrl);
            exit;
        } else {
            // Erreur d'authentification
            echo "Erreur d'authentification Google";
        }
    }
}
```

### 5.3 Ajouter la méthode handleAuthCode à GoogleDocsService

```php
public function handleAuthCode($authCode) {
    $this->client->fetchAccessTokenWithAuthCode($authCode);

    // Stocker le token
    if ($this->client->getAccessToken()) {
        file_put_contents($this->tokenPath, json_encode($this->client->getAccessToken()));
    }
}
```

## 6. Intégration dans result.php

### 6.1 Modifier result.php pour inclure le script JavaScript

```php
<!-- À ajouter dans la section head -->
<script src="assets/js/gdocs-export.js"></script>
```

### 6.2 Ajouter la section d'export dans result.php

```php
<!-- À ajouter après la section des boutons d'action -->
<div class="export-section">
    <h3>Exporter vers Google Docs</h3>
    <div class="form-group">
        <label for="gdocs-id">ID du document Google Docs:</label>
        <input type="text" id="gdocs-id" name="gdocs_id" placeholder="Entrez l'ID du document Google Docs">
        <p class="help-text">L'ID se trouve dans l'URL du document: docs.google.com/document/d/<strong>ID_DU_DOCUMENT</strong>/edit</p>
    </div>
    <button id="export-gdocs-btn" class="btn-action">Exporter vers Google Docs</button>
    <div id="export-status"></div>
</div>
```

## 7. Sécurité et gestion des erreurs

### 7.1 Stockage sécurisé des tokens

- Stocker les tokens dans un répertoire non accessible publiquement
- Utiliser des permissions de fichier restrictives

### 7.2 Gestion des erreurs

- Implémenter des try/catch pour toutes les opérations d'API
- Afficher des messages d'erreur conviviaux
- Journaliser les erreurs pour le débogage

## 8. Tests et déploiement

### 8.1 Tests

- Tester le flux d'authentification
- Tester l'ajout de contenu à un document existant
- Vérifier la gestion des erreurs

### 8.2 Déploiement

- Configurer les URL de redirection autorisées dans la console Google Cloud
- Mettre à jour les instructions d'installation pour inclure la configuration Google
