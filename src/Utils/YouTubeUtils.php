<?php

namespace Utils;

/**
 * Classe utilitaire pour les fonctionnalités liées à YouTube
 */
class YouTubeUtils
{
    /**
     * Vérifie si une URL est une URL YouTube valide
     * 
     * @param string $url URL à vérifier
     * @return bool True si l'URL est une URL YouTube valide, false sinon
     * @deprecated Utiliser ValidationUtils::validateYoutubeUrl() à la place
     */
    public static function isValidYoutubeUrl($url)
    {
        $validation = \Utils\ValidationUtils::validateYoutubeUrl($url);
        return $validation['valid'];
    }

    /**
     * Extrait l'ID de la vidéo YouTube à partir de l'URL
     * 
     * @param string $url URL YouTube
     * @return string|null ID de la vidéo YouTube ou null si non trouvé
     * @deprecated Utiliser ValidationUtils::validateYoutubeUrl() à la place
     */
    public static function getYoutubeVideoId($url)
    {
        $validation = \Utils\ValidationUtils::validateYoutubeUrl($url);
        if ($validation['valid']) {
            return $validation['video_id'];
        }
        return null;
    }

    /**
     * Télécharge une vidéo YouTube en utilisant l'API loader.to
     * 
     * @param string $youtubeUrl URL YouTube
     * @param string $outputPath Chemin de sortie pour le fichier audio
     * @param string $apiKey Clé API loader.to
     * @return array Résultat du téléchargement
     */
    public static function downloadYoutubeVideo($youtubeUrl, $outputPath, $apiKey)
    {
        $format = 'mp3';
        $encodedUrl = urlencode($youtubeUrl);
        $apiUrl = VIDEO_DOWNLOAD_API_URL . "?format={$format}&url={$encodedUrl}&api={$apiKey}";

        // Initialiser cURL pour la requête initiale
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // Exécuter la requête
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Enregistrer les informations de débogage
        $debug_info = [
            'youtube_url' => $youtubeUrl,
            'api_url' => $apiUrl,
            'http_code' => $httpCode,
            'response' => $response,
            'error' => $error
        ];
        file_put_contents('debug_youtube_download.log', print_r($debug_info, true));

        // Implémenter un backoff exponentiel en cas d'erreur
        $maxRetries = 3;
        $retryCount = 0;
        $retryDelay = 2;

        while ($httpCode !== 200 && $retryCount < $maxRetries) {
            // Attendre avant de réessayer (backoff exponentiel)
            sleep($retryDelay);
            $retryDelay *= 2;
            $retryCount++;

            // Réessayer la requête
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            // Enregistrer les informations de débogage pour la tentative
            $retry_debug_info = [
                'retry' => $retryCount,
                'delay' => $retryDelay / 2,
                'http_code' => $httpCode,
                'response' => $response,
                'error' => $error
            ];
            file_put_contents('debug_youtube_download.log', print_r($retry_debug_info, true), FILE_APPEND);
        }

        if ($httpCode !== 200 || $error) {
            $errorDetail = $error ?: 'Code HTTP ' . $httpCode;
            
            // Analyser l'erreur pour une catégorisation plus précise
            $category = 'api';
            $advice = 'Vérifiez que l\'URL YouTube est valide et que la vidéo est accessible dans votre pays.';
            
            if (stripos($errorDetail, 'timeout') !== false || stripos($errorDetail, 'timed out') !== false) {
                $category = 'network';
                $advice = 'Le service de téléchargement a mis trop de temps à répondre. Vérifiez votre connexion internet et réessayez.';
            } elseif (stripos($errorDetail, 'refused') !== false || stripos($errorDetail, 'reset') !== false) {
                $category = 'network';
                $advice = 'Connexion refusée par le service de téléchargement. Réessayez dans quelques instants.';
            } elseif ($httpCode == 429 || stripos($errorDetail, 'rate limit') !== false || stripos($errorDetail, 'too many') !== false) {
                $category = 'quota';
                $advice = 'Limite d\'utilisation du service de téléchargement atteinte. Veuillez réessayer plus tard.';
            } elseif ($httpCode == 404) {
                $category = 'not_found';
                $advice = 'La vidéo n\'a pas pu être trouvée. Vérifiez que l\'URL est correcte et que la vidéo existe toujours.';
            } elseif ($httpCode == 403) {
                $category = 'access_denied';
                $advice = 'Accès refusé à cette vidéo. Elle est peut-être privée ou soumise à des restrictions.';
            }
            
            return [
                'success' => false,
                'error' => 'Erreur lors du téléchargement de la vidéo',
                'details' => $errorDetail,
                'category' => $category,
                'advice' => $advice
            ];
        }

        // Décoder la réponse JSON
        $result = json_decode($response, true);

        if (!$result || !isset($result['success']) || !$result['success']) {
            $errorMessage = $result['error'] ?? 'Réponse API invalide';
            
            // Catégorisation plus précise des erreurs de l'API de téléchargement
            $category = 'api_response';
            $advice = 'Le service de téléchargement vidéo a retourné une erreur.';
            
            if (stripos($errorMessage, 'private') !== false || stripos($errorMessage, 'restricted') !== false) {
                $category = 'video_private';
                $advice = 'Cette vidéo est privée ou limitée. Vérifiez que la vidéo est publique et sans restrictions d\'âge.';
            } elseif (stripos($errorMessage, 'copyright') !== false || stripos($errorMessage, 'blocked') !== false) {
                $category = 'copyright';
                $advice = 'Cette vidéo est protégée par des droits d\'auteur et ne peut pas être téléchargée.';
            } elseif (stripos($errorMessage, 'exist') !== false || stripos($errorMessage, 'found') !== false || stripos($errorMessage, 'deleted') !== false) {
                $category = 'video_not_found';
                $advice = 'Cette vidéo n\'existe pas ou a été supprimée. Vérifiez l\'URL.';
            } elseif (stripos($errorMessage, 'country') !== false || stripos($errorMessage, 'region') !== false || stripos($errorMessage, 'geo') !== false) {
                $category = 'geo_restriction';
                $advice = 'Cette vidéo n\'est pas disponible dans votre pays en raison de restrictions géographiques.';
            } elseif (stripos($errorMessage, 'quota') !== false || stripos($errorMessage, 'limit') !== false) {
                $category = 'quota';
                $advice = 'Le service de téléchargement a atteint sa limite d\'utilisation. Veuillez réessayer plus tard.';
            }
            
            return [
                'success' => false,
                'error' => 'Erreur du service de téléchargement',
                'details' => $errorMessage,
                'category' => $category,
                'advice' => $advice
            ];
        }

        // Récupérer l'ID de la demande de téléchargement
        $downloadId = $result['id'] ?? '';
        if (empty($downloadId)) {
            return [
                'success' => false,
                'error' => 'Erreur de configuration du téléchargement',
                'details' => 'ID de téléchargement non trouvé dans la réponse API',
                'category' => 'api_response',
                'advice' => 'Le service de téléchargement vidéo a rencontré un problème. Réessayez plus tard ou vérifiez que l\'URL YouTube est correcte.'
            ];
        }

        // Vérifier la progression du téléchargement et attendre qu'il soit terminé
        $downloadUrl = self::waitForDownloadCompletion($result, $downloadId);

        if (empty($downloadUrl)) {
            return [
                'success' => false,
                'error' => 'Impossible d\'obtenir l\'URL de téléchargement après plusieurs tentatives',
                'details' => 'Le service a dépassé le délai d\'attente maximal',
                'category' => 'timeout',
                'advice' => 'Le traitement de la vidéo a pris trop de temps. Essayez avec une vidéo plus courte ou réessayez ultérieurement quand le service sera moins chargé.'
            ];
        }

        // Télécharger le fichier audio depuis l'URL fournie
        $fileContent = self::downloadFile($downloadUrl);

        if ($fileContent === false) {
            return [
                'success' => false,
                'error' => 'Impossible de télécharger le fichier audio après plusieurs tentatives',
                'details' => 'Échec du téléchargement du fichier depuis le serveur',
                'category' => 'download_failure',
                'advice' => 'Le téléchargement du fichier audio a échoué. Vérifiez votre connexion internet et réessayez. Si le problème persiste, la vidéo est peut-être trop volumineuse.'
            ];
        }

        // Enregistrer le fichier audio
        if (file_put_contents($outputPath, $fileContent) === false) {
            return [
                'success' => false,
                'error' => 'Impossible d\'enregistrer le fichier audio',
                'details' => 'Échec lors de l\'écriture du fichier sur le disque',
                'category' => 'file_access',
                'advice' => 'Le système n\'a pas pu enregistrer le fichier audio. Vérifiez les permissions du répertoire et l\'espace disque disponible.'
            ];
        }

        return [
            'success' => true,
            'file_path' => $outputPath
        ];
    }

    /**
     * Attend que le téléchargement soit terminé et retourne l'URL de téléchargement
     * 
     * @param array $result Résultat de la requête initiale
     * @param string $downloadId ID de téléchargement
     * @return string|null URL de téléchargement ou null si non trouvé
     */
    private static function waitForDownloadCompletion($result, $downloadId)
    {
        $downloadUrl = null;
        $maxAttempts = 30; // Nombre maximum de tentatives
        $attempts = 0;
        $waitTime = 2; // Temps d'attente entre les tentatives en secondes

        while ($attempts < $maxAttempts) {
            // Attendre avant de vérifier la progression (conversion explicite en entier)
            sleep((int)$waitTime);

            // Récupérer l'URL de progression depuis la réponse de l'API
            $progressUrl = $result['progress_url'] ?? (VIDEO_DOWNLOAD_PROGRESS_URL . "?id={$downloadId}");

            // Initialiser cURL pour la requête de progression
            $ch = curl_init($progressUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);

            // Exécuter la requête
            $progressResponse = curl_exec($ch);
            $progressHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $progressError = curl_error($ch);
            curl_close($ch);

            // Vérifier si la requête a réussi
            if ($progressHttpCode !== 200 || $progressError) {
                // Implémenter un backoff pour les erreurs de progression
                $waitTime = min($waitTime * 1.5, 10); // Augmenter le temps d'attente, max 10 secondes
                $attempts++;
                continue;
            }

            // Décoder la réponse JSON
            $progressResult = json_decode($progressResponse, true);

            // Enregistrer les informations de progression
            $progress_debug_info = [
                'attempt' => $attempts + 1,
                'progress_url' => VIDEO_DOWNLOAD_PROGRESS_URL,
                'http_code' => $progressHttpCode,
                'response' => $progressResult,
                'error' => $progressError
            ];
            file_put_contents('debug_youtube_progress.log', print_r($progress_debug_info, true), FILE_APPEND);

            // Vérifier si le téléchargement est terminé
            if (isset($progressResult['success']) && $progressResult['success'] == 1 && isset($progressResult['download_url'])) {
                $downloadUrl = $progressResult['download_url'];
                break;
            }

            // Si la progression est à 100% mais pas d'URL de téléchargement, attendre encore un peu
            if (isset($progressResult['progress']) && $progressResult['progress'] >= 1000) {
                $waitTime = 1; // Réduire le temps d'attente
            } else {
                // Augmenter légèrement le temps d'attente pour les téléchargements plus longs
                $waitTime = min($waitTime * 1.2, 5.0);
            }

            $attempts++;
        }

        return $downloadUrl;
    }

    /**
     * Télécharge un fichier depuis une URL
     * 
     * @param string $url URL du fichier à télécharger
     * @return string|false Contenu du fichier ou false en cas d'erreur
     */
    private static function downloadFile($url)
    {
        $maxDownloadRetries = 3;
        $downloadRetries = 0;
        $fileContent = false;

        while ($fileContent === false && $downloadRetries < $maxDownloadRetries) {
            $fileContent = @file_get_contents($url);
            if ($fileContent === false) {
                $downloadRetries++;
                sleep(2 * $downloadRetries);
            }
        }

        return $fileContent;
    }
}
