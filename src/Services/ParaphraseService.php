<?php

namespace Services;

/**
 * Service pour la paraphrase de texte
 */
class ParaphraseService
{
    /**
     * Paraphrase un texte en utilisant l'API OpenAI
     * 
     * @param string $text Texte à paraphraser
     * @param string $style Style de paraphrase (simple, formel, créatif, etc.)
     * @param string $language Langue de sortie
     * @return array Résultat de la paraphrase
     */
    public function paraphraseText($text, $style = 'standard', $language = 'fr')
    {
        // Vérifier si le texte est vide
        if (empty($text)) {
            return [
                'success' => false,
                'error' => 'Le texte à paraphraser est vide'
            ];
        }

        // Exécuter le script Python de paraphrase
        $pythonPath = PYTHON_PATH;
        $scriptPath = BASE_DIR . '/paraphrase.py';

        // Créer un fichier temporaire pour le texte d'entrée
        $inputFile = tempnam(sys_get_temp_dir(), 'paraphrase_input_');
        file_put_contents($inputFile, $text);

        // Créer un fichier temporaire pour le résultat
        $outputFile = tempnam(sys_get_temp_dir(), 'paraphrase_output_');

        $command = escapeshellcmd($pythonPath) . ' ' .
            escapeshellarg($scriptPath) . ' ' .
            '--input=' . escapeshellarg($inputFile) . ' ' .
            '--output=' . escapeshellarg($outputFile) . ' ' .
            '--style=' . escapeshellarg($style) . ' ' .
            '--language=' . escapeshellarg($language);

        // Exécuter la commande et capturer la sortie standard et d'erreur
        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin
            1 => array("pipe", "w"),  // stdout
            2 => array("pipe", "w")   // stderr
        );

        $process = proc_open($command, $descriptorspec, $pipes);

        if (!is_resource($process)) {
            // Nettoyer les fichiers temporaires
            @unlink($inputFile);
            @unlink($outputFile);

            return [
                'success' => false,
                'error' => "Impossible de démarrer le processus de paraphrase"
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
        file_put_contents(BASE_DIR . '/debug_paraphrase.log', print_r($debug_info, true));

        // Lire le résultat
        $result = [];
        if (file_exists($outputFile)) {
            $resultContent = file_get_contents($outputFile);
            $result = json_decode($resultContent, true);
        }

        // Nettoyer les fichiers temporaires
        @unlink($inputFile);
        @unlink($outputFile);

        if (!$result || !isset($result['success']) || !$result['success']) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Erreur inconnue lors de la paraphrase'
            ];
        }

        return $result;
    }

    /**
     * Paraphrase un texte à partir d'un ID de résultat de transcription
     * 
     * @param string $resultId ID du résultat de transcription
     * @param string $style Style de paraphrase (simple, formel, créatif, etc.)
     * @param string $language Langue de sortie
     * @return array Résultat de la paraphrase
     */
    public function paraphraseTranscription($resultId, $style = 'standard', $language = 'fr')
    {
        // Récupérer le résultat de transcription
        $transcriptionService = new TranscriptionService();
        $transcriptionResult = $transcriptionService->getTranscriptionResult($resultId);

        if (!$transcriptionResult['success']) {
            return [
                'success' => false,
                'error' => $transcriptionResult['error']
            ];
        }

        // Paraphraser le texte
        $text = $transcriptionResult['text'];
        return $this->paraphraseText($text, $style, $language);
    }

    /**
     * Récupère les styles de paraphrase disponibles
     * 
     * @return array Liste des styles disponibles
     */
    public function getAvailableStyles()
    {
        return [
            'standard' => 'Standard',
            'simple' => 'Simplifié',
            'formel' => 'Formel',
            'academique' => 'Académique',
            'creatif' => 'Créatif',
            'professionnel' => 'Professionnel',
            'concis' => 'Concis'
        ];
    }
}
