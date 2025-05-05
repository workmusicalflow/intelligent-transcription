<?php
// Include les fichiers requis
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/autoload.php';

// Vérifier si l'utilisateur est authentifié comme administrateur (à implémenter selon votre système)
$isAdmin = true; // Temporairement activé pour test

if (!$isAdmin) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Accès non autorisé';
    exit;
}

// Récupérer la catégorie de log demandée
$category = isset($_GET['category']) ? $_GET['category'] : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Sécurité
if ($limit > 50) {
    $limit = 50;
}

// Action pour nettoyer les logs si demandé
if (isset($_GET['cleanup']) && $_GET['cleanup'] === 'true') {
    Utils\PythonErrorUtils::cleanupOldLogs();
    header('Location: error_logs.php?cleaned=true');
    exit;
}

// Récupérer les erreurs
$errors = Utils\PythonErrorUtils::getRecentErrors($category, $limit);

// Récupérer les catégories disponibles
$categories = array_keys(Utils\PythonErrorUtils::ERROR_CATEGORIES);
$categories[] = 'general';

// Formater les erreurs pour l'affichage
$formattedErrors = [];
foreach ($errors as $cat => $categoryErrors) {
    foreach ($categoryErrors as $error) {
        $formattedErrors[] = [
            'category' => $cat,
            'file' => $error['file'],
            'timestamp' => $error['timestamp'],
            'content' => htmlspecialchars($error['content'])
        ];
    }
}

// Trier par timestamp (plus récent en premier)
usort($formattedErrors, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// HTML de base
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs d'erreurs Python</title>
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <style>
        .log-content {
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Logs d'erreurs Python</h1>
            
            <div class="flex space-x-2">
                <a href="index.php" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Retour à l'accueil</a>
                <a href="error_logs.php?cleanup=true" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700" onclick="return confirm('Êtes-vous sûr de vouloir nettoyer les anciens logs ?')">Nettoyer les logs</a>
            </div>
        </div>
        
        <?php if (isset($_GET['cleaned']) && $_GET['cleaned'] === 'true'): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p>Les anciens logs ont été nettoyés avec succès.</p>
            </div>
        <?php endif; ?>
        
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-2">Filtrer par catégorie</h2>
            <div class="flex flex-wrap gap-2">
                <a href="error_logs.php" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">Toutes</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="error_logs.php?category=<?= urlencode($cat) ?>" class="px-3 py-1 <?= $category === $cat ? 'bg-blue-700' : 'bg-blue-500' ?> text-white rounded hover:bg-blue-600"><?= htmlspecialchars($cat) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if (empty($formattedErrors)): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4" role="alert">
                <p>Aucune erreur trouvée dans cette catégorie.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-6">
                <?php foreach ($formattedErrors as $error): ?>
                    <div class="bg-white shadow-md rounded-lg overflow-hidden">
                        <div class="px-4 py-3 bg-gray-800 text-white flex justify-between items-center">
                            <div>
                                <span class="font-semibold"><?= htmlspecialchars($error['file']) ?></span>
                                <span class="ml-2 text-gray-300"><?= htmlspecialchars($error['timestamp']) ?></span>
                            </div>
                            <span class="px-2 py-1 rounded bg-<?= $error['category'] === 'general' ? 'gray' : ($error['category'] === 'api_key' ? 'red' : ($error['category'] === 'network' ? 'yellow' : 'blue')) ?>-600 text-xs font-semibold"><?= htmlspecialchars($error['category']) ?></span>
                        </div>
                        <div class="p-4">
                            <div class="log-content bg-gray-100 p-3 rounded"><?= $error['content'] ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>