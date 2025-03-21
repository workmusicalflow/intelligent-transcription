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
     */
    public static function isValidYoutubeUrl($url)
    {
        // Pattern pour les URLs YouTube standard et les URLs YouTube Shorts
        $pattern = '/^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})(\S*)?$/';
        return preg_match($pattern, $url) === 1;
    }

    /**
     * Extrait l'ID de la vidéo YouTube à partir de l'URL
     * 
     * @param string $url URL YouTube
     * @return string|null ID de la vidéo YouTube ou null si non trouvé
     */
    public static function getYoutubeVideoId($url)
    {
        // Pattern pour les URLs YouTube standard et les URLs YouTube Shorts
        $pattern = '/^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})(\S*)?$/';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[4];
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
            return [
                'success' => false,
                'error' => 'Erreur lors du téléchargement de la vidéo: ' . ($error ?: 'Code HTTP ' . $httpCode)
            ];
        }

        // Décoder la réponse JSON
        $result = json_decode($response, true);

        if (!$result || !isset($result['success']) || !$result['success']) {
            return [
                'success' => false,
                'error' => 'Réponse API invalide'
            ];
        }

        // Récupérer l'ID de la demande de téléchargement
        $downloadId = $result['id'] ?? '';
        if (empty($downloadId)) {
            return [
                'success' => false,
                'error' => 'ID de téléchargement non trouvé dans la réponse'
            ];
        }

        // Vérifier la progression du téléchargement et attendre qu'il soit terminé
        $downloadUrl = self::waitForDownloadCompletion($result, $downloadId);

        if (empty($downloadUrl)) {
            return [
                'success' => false,
                'error' => 'Impossible d\'obtenir l\'URL de téléchargement après plusieurs tentatives'
            ];
        }

        // Télécharger le fichier audio depuis l'URL fournie
        $fileContent = self::downloadFile($downloadUrl);

        if ($fileContent === false) {
            return [
                'success' => false,
                'error' => 'Impossible de télécharger le fichier audio après plusieurs tentatives'
            ];
        }

        // Enregistrer le fichier audio
        if (file_put_contents($outputPath, $fileContent) === false) {
            return [
                'success' => false,
                'error' => 'Impossible d\'enregistrer le fichier audio'
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
