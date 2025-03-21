<?php

namespace Services;

use Utils\FileUtils;
use Utils\ResponseUtils;

/**
 * Service de transcription audio
 */
class TranscriptionService
{
    /**
     * Prétraite un fichier audio pour réduire sa taille
     * 
     * @param string $filePath Chemin du fichier audio
     * @param string $outputDir Répertoire de sortie
     * @param int $targetSizeMb Taille cible en Mo
     * @return array Résultat du prétraitement
     */
    public function preprocessAudio($filePath, $outputDir, $targetSizeMb = 24)
    {
        // Vérifier si le fichier existe
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'error' => "Le fichier $filePath n'existe pas"
            ];
        }

        // Créer le répertoire de sortie si nécessaire
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        // Exécuter le script Python de prétraitement
        $pythonPath = PYTHON_PATH;
        $preprocessScript = BASE_DIR . '/preprocess_audio.py';

        $command = escapeshellcmd($pythonPath) . ' ' .
            escapeshellarg($preprocessScript) . ' ' .
            '--file=' . escapeshellarg($filePath) . ' ' .
            '--output_dir=' . escapeshellarg($outputDir) . ' ' .
            '--target_size_mb=' . escapeshellarg($targetSizeMb);

        // Exécuter la commande
        $output = shell_exec($command);
        $result = json_decode($output, true);

        // Enregistrer les informations de prétraitement
        $preprocess_debug_info = [
            'command' => $command,
            'output' => $output,
            'result' => $result
        ];
        file_put_contents(BASE_DIR . '/debug_preprocess.log', print_r($preprocess_debug_info, true));

        if (!$result || !isset($result['success']) || !$result['success']) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Erreur inconnue lors du prétraitement'
            ];
        }

        return $result;
    }

    /**
     * Transcrit un fichier audio
     * 
     * @param string $filePath Chemin du fichier audio
     * @param string $outputPath Chemin du fichier de sortie
     * @param string|null $language Code de langue (fr, en, etc.)
     * @param bool $forceLanguage Forcer la traduction dans la langue spécifiée
     * @return array Résultat de la transcription
     */
    public function transcribeAudio($filePath, $outputPath, $language = null, $forceLanguage = false)
    {
        // Vérifier si le fichier existe
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'error' => "Le fichier $filePath n'existe pas"
            ];
        }

        // Exécuter le script Python de transcription
        $pythonPath = PYTHON_PATH;
        $scriptPath = BASE_DIR . '/transcribe.py';

        $command = escapeshellcmd($pythonPath) . ' ' .
            escapeshellarg($scriptPath) . ' ' .
            '--file=' . escapeshellarg($filePath) . ' ' .
            '--output=' . escapeshellarg($outputPath);

        // Ajouter le paramètre de langue
        if ($language === 'auto' || $language === null) {
            $command .= ' --language=""';
        } else {
            $command .= ' --language=' . escapeshellarg($language);

            // Ajouter le paramètre pour forcer la traduction dans la langue spécifiée
            if ($forceLanguage) {
                $command .= ' --force-language';
            }
        }

        // Exécuter la commande et capturer la sortie standard et d'erreur
        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin
            1 => array("pipe", "w"),  // stdout
            2 => array("pipe", "w")   // stderr
        );

        $process = proc_open($command, $descriptorspec, $pipes);

        if (!is_resource($process)) {
            return [
                'success' => false,
                'error' => "Impossible de démarrer le processus de transcription"
            ];
        }

        // Lire la sortie standard
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        // Lire la sortie d'erreur
        $error_output = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        // Fermer le processus
        $return_value = proc_close($process);

        // Enregistrer les informations de débogage
        $debug_info = [
            'command' => $command,
            'output' => $output,
            'error_output' => $error_output,
            'return_value' => $return_value
        ];
        file_put_contents(BASE_DIR . '/debug_transcribe.log', print_r($debug_info, true));

        // Décoder la sortie JSON
        $result = json_decode($output, true);

        if (!$result || !isset($result['success']) || !$result['success']) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Erreur inconnue lors de la transcription'
            ];
        }

        return $result;
    }

    /**
     * Traite un fichier audio téléchargé
     * 
     * @param array $file Fichier téléchargé ($_FILES['audio_file'])
     * @param string $language Code de langue
     * @param bool $forceLanguage Forcer la traduction dans la langue spécifiée
     * @return array Résultat du traitement
     */
    public function processUploadedFile($file, $language = 'auto', $forceLanguage = false)
    {
        // Vérifier si le fichier a été téléchargé
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la limite définie dans php.ini (upload_max_filesize)',
                UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la limite définie dans le formulaire HTML (MAX_FILE_SIZE)',
                UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
                UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
                UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
                UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque',
                UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté le téléchargement du fichier'
            ];
            $error_code = $file['error'];
            $error_message = $error_messages[$error_code] ?? 'Erreur inconnue';
            return [
                'success' => false,
                'error' => $error_message
            ];
        }

        // Vérifier la taille du fichier
        if ($file['size'] > MAX_UPLOAD_SIZE_BYTES) {
            return [
                'success' => false,
                'error' => 'Le fichier dépasse la limite de taille (' . MAX_UPLOAD_SIZE_MB . 'MB)'
            ];
        }

        // Créer le répertoire de téléchargement si nécessaire
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0777, true);
        }

        // Générer un nom de fichier unique
        $filename = uniqid('audio_') . '_' . basename($file['name']);
        $filePath = UPLOAD_DIR . '/' . $filename;

        // Déplacer le fichier téléchargé
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return [
                'success' => false,
                'error' => 'Impossible de déplacer le fichier téléchargé'
            ];
        }

        // Vérifier si le fichier est un fichier audio ou vidéo valide
        if (!FileUtils::isValidMediaFile($filePath)) {
            // Supprimer le fichier invalide
            unlink($filePath);
            return [
                'success' => false,
                'error' => 'Le fichier n\'est pas un fichier audio ou vidéo valide'
            ];
        }

        // Prétraiter le fichier audio
        $preprocessResult = $this->preprocessAudio($filePath, TEMP_AUDIO_DIR);
        if (!$preprocessResult['success']) {
            return [
                'success' => false,
                'error' => $preprocessResult['error']
            ];
        }

        // Utiliser le fichier prétraité pour la transcription
        $preprocessedFilePath = $preprocessResult['output_file'];

        // Créer le répertoire de résultats si nécessaire
        if (!is_dir(RESULT_DIR)) {
            mkdir(RESULT_DIR, 0777, true);
        }

        // Générer un nom de fichier pour le résultat
        $resultId = FileUtils::generateUniqueId();
        $resultPath = RESULT_DIR . '/' . $resultId . '.json';

        // Transcrire le fichier audio
        $transcriptionResult = $this->transcribeAudio($preprocessedFilePath, $resultPath, $language, $forceLanguage);
        if (!$transcriptionResult['success']) {
            return [
                'success' => false,
                'error' => $transcriptionResult['error']
            ];
        }

        return [
            'success' => true,
            'result_id' => $resultId,
            'text' => $transcriptionResult['text'],
            'language' => $transcriptionResult['language']
        ];
    }

    /**
     * Récupère un résultat de transcription
     * 
     * @param string $resultId ID du résultat
     * @return array Résultat de la transcription
     */
    public function getTranscriptionResult($resultId)
    {
        $resultPath = RESULT_DIR . '/' . $resultId . '.json';

        if (!file_exists($resultPath)) {
            return [
                'success' => false,
                'error' => 'Résultat non trouvé'
            ];
        }

        $result = json_decode(file_get_contents($resultPath), true);

        if (!$result || !isset($result['success']) || !$result['success']) {
            return [
                'success' => false,
                'error' => 'Résultat invalide'
            ];
        }

        return $result;
    }
}
