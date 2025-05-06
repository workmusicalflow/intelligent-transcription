<?php

namespace Utils;

/**
 * Classe utilitaire pour les réponses HTTP et les messages d'erreur
 */
class ResponseUtils
{
    /**
     * Codes d'erreur et messages associés
     */
    private static $errorMessages = [
        'upload' => 'Erreur lors du téléchargement du fichier',
        'size' => 'Le fichier est trop volumineux',
        'move' => 'Erreur lors du déplacement du fichier',
        'preprocess' => 'Erreur lors du prétraitement du fichier audio',
        'transcription' => 'Erreur lors de la transcription',
        'paraphrase' => 'Erreur lors de la paraphrase du texte',
        'missing_id' => 'ID de résultat manquant',
        'result_not_found' => 'Résultat non trouvé',
        'invalid_result' => 'Résultat invalide',
        'invalid_file' => 'Le fichier n\'est pas un fichier audio ou vidéo valide',
        'youtube' => 'Erreur lors du téléchargement de la vidéo YouTube'
    ];

    /**
     * Redirige vers une URL
     * 
     * @param string $url URL de redirection
     * @return void
     */
    public static function redirect($url)
    {
        header("Location: $url");
        exit;
    }

    /**
     * Affiche un message d'erreur
     * 
     * @param string $code Code d'erreur
     * @param string|null $message Message d'erreur supplémentaire
     * @return string Message d'erreur formaté
     */
    public static function getErrorMessage($code, $message = null)
    {
        // Si un message spécifique est fourni, l'utiliser
        if ($message) {
            return (self::$errorMessages[$code] ?? 'Erreur inconnue') . ': ' . $message;
        }

        // Sinon, utiliser le message par défaut
        return self::$errorMessages[$code] ?? 'Erreur inconnue';
    }

    /**
     * Redirige vers la page spécifiée avec un message d'erreur
     * 
     * @param string $page Page de destination (sans l'extension .php)
     * @param string $message Message d'erreur
     * @return void
     */
    public static function redirectWithError($page, $message = null)
    {
        error_log("Redirecting with error - Page: $page, Message: $message");
        
        $url = $page;
        
        // Ajouter .php si la page ne contient pas déjà .php ou /
        if (strpos($page, '.php') === false && strpos($page, '/') === false) {
            $url = $page . '.php';
        }
        
        // Gestion spéciale pour la page de login
        if ($page === 'login') {
            $url = 'login.php?action=login';
        }
        
        // Ajouter le message d'erreur comme paramètre
        $url .= (strpos($url, '?') === false) ? '?' : '&';
        $url .= 'error=' . urlencode($message ?: 'Une erreur est survenue');
        
        error_log("Final redirect URL: $url");
        self::redirect($url);
    }

    /**
     * Renvoie une réponse JSON
     * 
     * @param array $data Données à renvoyer
     * @param int $statusCode Code de statut HTTP
     * @return void
     */
    public static function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Renvoie une réponse JSON d'erreur
     * 
     * @param string $message Message d'erreur
     * @param int $statusCode Code de statut HTTP
     * @return void
     */
    public static function jsonError($message, $statusCode = 400)
    {
        self::jsonResponse([
            'success' => false,
            'error' => $message
        ], $statusCode);
    }

    /**
     * Renvoie une réponse JSON de succès
     * 
     * @param array $data Données à renvoyer
     * @return void
     */
    public static function jsonSuccess($data)
    {
        $data['success'] = true;
        self::jsonResponse($data);
    }
    
    /**
     * Redirige vers la page spécifiée avec un message de succès
     * 
     * @param string $page Page de destination (sans l'extension .php)
     * @param string $message Message de succès
     * @return void
     */
    public static function redirectWithSuccess($page, $message = null)
    {
        error_log("Redirecting with success - Page: $page, Message: $message");
        
        $url = $page;
        
        // Ajouter .php si la page ne contient pas déjà .php ou /
        if (strpos($page, '.php') === false && strpos($page, '/') === false) {
            $url = $page . '.php';
        }
        
        // Gestion spéciale pour la page de login
        if ($page === 'login') {
            $url = 'login.php?action=login';
        }
        
        // Ajouter le message de succès comme paramètre
        $url .= (strpos($url, '?') === false) ? '?' : '&';
        $url .= 'success=' . urlencode($message ?: 'Opération réussie');
        
        error_log("Final redirect URL: $url");
        self::redirect($url);
    }
}
